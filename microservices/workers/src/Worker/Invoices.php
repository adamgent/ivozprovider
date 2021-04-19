<?php

namespace Worker;

use Ivoz\Core\Application\Service\EntityTools;
use Ivoz\Core\Infrastructure\Persistence\Redis\RedisMasterFactory;
use Ivoz\Core\Infrastructure\Persistence\Redis\Sentinel;
use Ivoz\Provider\Domain\Job\InvoicerJobInterface;
use Ivoz\Provider\Domain\Model\BillableCall\BillableCallRepository;
use Ivoz\Provider\Domain\Model\Invoice\InvoiceDto;
use Ivoz\Provider\Domain\Model\Invoice\InvoiceInterface;
use Ivoz\Provider\Domain\Model\Invoice\InvoiceRepository;
use Ivoz\Provider\Domain\Service\Invoice\Generator;
use Psr\Log\LoggerInterface;
use Ivoz\Core\Domain\Service\DomainEventPublisher;
use Ivoz\Core\Application\RequestId;
use Ivoz\Core\Application\RegisterCommandTrait;
use Symfony\Component\HttpFoundation\Response;

class Invoices
{
    use RegisterCommandTrait;

    private $eventPublisher;
    private $requestId;
    private $entityTools;
    private $invoiceRepository;
    private $billableCallRepository;
    private $generator;
    private $redisMasterFactory;
    private $redisDb;
    private $logger;

    public function __construct(
        DomainEventPublisher $eventPublisher,
        RequestId $requestId,
        EntityTools $entityTools,
        InvoiceRepository $invoiceRepository,
        BillableCallRepository $billableCallRepository,
        Generator $generator,
        RedisMasterFactory $redisMasterFactory,
        int $redisDb,
        LoggerInterface $logger
    ) {
        $this->eventPublisher = $eventPublisher;
        $this->requestId = $requestId;
        $this->entityTools = $entityTools;
        $this->invoiceRepository = $invoiceRepository;
        $this->billableCallRepository = $billableCallRepository;
        $this->generator = $generator;
        $this->redisMasterFactory = $redisMasterFactory;
        $this->redisDb = $redisDb;
        $this->logger = $logger;
    }

    public function create()
    {
        try {
            $id = $this->getInvoiceId();

            $this->registerCommand('Worker', 'invoices', ['id' => $id]);

            $this->logger->info("[INVOICER] ID = " . $id);

            $this->billableCallRepository->resetInvoiceId($id);

            /** @var InvoiceInterface | null $invoice */
            $invoice = $this->invoiceRepository->find($id);
            if (!$invoice) {
                $this->logger->error("Invoice #${id} was not found!");
                return null;
            }

            /** @var InvoiceDto $invoiceDto */
            $invoiceDto = $this->entityTools->entityToDto($invoice);

            $invoiceDto->setStatus(InvoiceInterface::STATUS_PROCESSING);
            $this->entityTools->persistDto($invoiceDto, $invoice, true);

            $this->logger->info("[INVOICER] Status = processing");

            $content = $this->generator->getInvoicePDFContents($id);
            $tempPath = "/opt/irontec/ivozprovider/storage/invoice";
            if (!file_exists($tempPath)) {
                mkdir($tempPath);
            }
            $tempPdf = $tempPath."/temp". $invoice->getId() .".pdf";
            file_put_contents($tempPdf, $content);

            $totals = $this->generator->getTotals();
            /** @var InvoiceDto $invoiceDto */
            $invoiceDto
                ->setPdfPath($tempPdf)
                ->setPdfBaseName('invoice-' . $invoice->getNumber() . '.pdf')
                ->setPdfMimeType('application/pdf; charset=binary')
                ->setTotal($totals["totalPrice"])
                ->setTotalWithTax($totals["totalWithTaxes"])
                ->setStatus(InvoiceInterface::STATUS_CREATED);

            $this->entityTools->persistDto($invoiceDto, $invoice);
            $this->logger->info("[INVOICER] Status = created");
        } catch (\Exception $e) {
            $this->logger->info("[INVOICER] Status = error");
            $this->logger->error("[INVOICER] Error was: ".$e->getMessage());

            if (!isset($invoiceDto)) {
                exit(1);
            }

            if (!isset($invoice)) {
                exit(1);
            }

            $invoiceDto->setStatus(InvoiceInterface::STATUS_ERROR);
            $invoiceDto->setStatusMsg(
                $e->getMessage()
            );

            $this->entityTools->persistDto(
                $invoiceDto,
                $invoice,
                true
            );
        } finally {
            return new Response('');
        }
    }

    private function getInvoiceId()
    {
        $redisMaster = $this
            ->redisMasterFactory
            ->create(
                $this->redisDb
            );

        try {
            $timeoutSeconds = 60 * 60;
            $response = $redisMaster->blPop(
                [InvoicerJobInterface::CHANNEL],
                $timeoutSeconds
            );

            return intval(end($response));
        } catch (\RedisException $e) {
            $this->logger->error('Invoicer timeout');
            exit(1);
        }
    }
}
