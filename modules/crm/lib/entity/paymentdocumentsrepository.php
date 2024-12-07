<?php

namespace Bitrix\Crm\Entity;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Workflow\PaymentStage;
use Bitrix\Crm\Workflow\PaymentWorkflow;
use Bitrix\Crm\Workflow\EntityStageTable;
use Bitrix\Sale;
use Bitrix\Catalog;

/**
 * Class retrieves documents related to the entity.
 *
 * You should instanciate this class objects with Service Locator:
 * Bitrix\Main\DI\ServiceLocator::getInstance()->get('crm.entity.paymentDocumentsRepository');
 */
class PaymentDocumentsRepository
{
	private const ERROR_CODE_ENTITY_NOT_FOUND = 'ENTITY_NOT_FOUND';

	/** @var int */
	private $ownerTypeId = null;

	/** @var int */
	private $ownerId = null;

	/** @var float */
	private $entityAmount = 0.0;

	/** @var array */
	private $orders = [];

	/** @var array */
	private $documents = [];

	/** @var int[] */
	private $orderIds = [];

	/** @var string */
	private $currencyId = null;

	/** @var array */
	private $currencyFormat = [];

	/** @var bool */
	private $isUsedInventoryManagement;

	/** @var float */
	private $paidSum = 0.0;

	/** @var float */
	private $totalSum = 0.0;

	/**
	 * @throws Main\LoaderException
	 */
	public function __construct()
	{
		$this->includeModules('crm', 'sale', 'currency');

		$this->isUsedInventoryManagement = false;
		if (Main\Loader::includeModule('catalog'))
		{
			$this->isUsedInventoryManagement = Catalog\Config\State::isUsedInventoryManagement();
		}
	}

	/**
	 * Entry point.
	 * @param int $ownerId
	 * @return Main\Result
	 */
	public function getDocumentsForEntity(int $ownerTypeId, int $ownerId): Main\Result
	{
		$this->ownerTypeId = $ownerTypeId;
		$this->ownerId = $ownerId;
		$this->paidSum = 0.0;
		$this->orders = [];

		$result = new Main\Result;

		if ($this->fetchEntity())
		{
			$this->fetchOrderIds();
			$this->fetchOrders();
			$this->fetchDocuments();
			$this->subscribeToPullEvents();

			$result->setData($this->formatResult());
		}
		else
		{
			$result->addError(
				new Main\Error("Entity {$this->ownerId} not found", static::ERROR_CODE_ENTITY_NOT_FOUND)
			);
		}

		return $result;
	}

	public function doDocumentsExistForEntity(int $ownerTypeId, int $ownerId): bool
	{
		$this->ownerTypeId = $ownerTypeId;
		$this->ownerId = $ownerId;

		$paymentDocuments = $this->getPaymentDocumentsForEntityByFilter($this->ownerId, $this->ownerTypeId);
		if (!empty($paymentDocuments))
		{
			return true;
		}

		$emptyDeliveryServiceId = Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
		$shipments = $this->getDeliveriesForEntityByFilter($this->ownerId, $this->ownerTypeId, select: ['ID', 'DELIVERY_ID']);
		$shipmentDocuments = array_filter($shipments, static function ($shipment) use ($emptyDeliveryServiceId) {
			return (int)$shipment['DELIVERY_ID'] !== $emptyDeliveryServiceId;
		});
		if (!empty($shipmentDocuments))
		{
			return true;
		}

		$realizationDocuments = $this->getRealizationDocumentsIdsByShipments($shipments);
		if (!empty($realizationDocuments))
		{
			return true;
		}

		$checkDocuments = $this->fetchCheckDocuments();
		if (!empty($checkDocuments))
		{
			return true;
		}

		return false;
	}

	public function getPaymentDocumentsForEntityByFilter(
		int $ownerId,
		int $ownerTypeId,
		array $filter = [],
		array $select = ['ID'],
		array $order = []
	): array
	{
		$this->ownerTypeId = $ownerTypeId;
		$this->ownerId = $ownerId;

		if ($this->doesEntityExist())
		{
			$this->fetchOrderIds();

			if (count($this->orderIds) <= 0)
			{
				return [];
			}

			$payments = Sale\PaymentCollection::getList([
				'select' => $select,
				'filter' => array_merge($filter, ['=ORDER_ID' => $this->orderIds]),
				'order' => $order
			]);

			return $payments->fetchAll();
		}

		return [];
	}

	public function getDeliveryDocumentsForEntityByFilter(
		int $ownerId,
		int $ownerTypeId,
		array $filter = [],
		array $select = ['ID'],
		array $order = []
	): array
	{
		$filter['!DELIVERY_ID'] = Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();

		return $this->getDeliveriesForEntityByFilter($ownerId, $ownerTypeId, $filter, $select, $order);
	}

	public function getDeliveriesForEntityByFilter(
		int $ownerId,
		int $ownerTypeId,
		array $filter = [],
		array $select = ['ID'],
		array $order = []
	): array
	{
		$this->ownerTypeId = $ownerTypeId;
		$this->ownerId = $ownerId;

		if ($this->doesEntityExist())
		{
			$this->fetchOrderIds();

			if (count($this->orderIds) <= 0)
			{
				return [];
			}

			$filter = array_merge($filter, ['=ORDER_ID' => $this->orderIds]);
			$filter['=SYSTEM'] = 'N';

			$dbRes = Sale\Shipment::getList([
				'select' => $select,
				'filter' => $filter,
				'order' => $order
			]);

			return $dbRes->fetchAll();
		}

		return [];
	}

	public function getRealizationDocumentsIdsByShipments(array $shipments): array
	{
		$result = [];

		$shipmentIds = array_column($shipments, 'ID');

		$shipmentRealizations = Crm\Order\Internals\ShipmentRealizationTable::getList([
			'select' => ['SHIPMENT_ID'],
			'filter' => [
				'=SHIPMENT_ID' => $shipmentIds,
				'=IS_REALIZATION' => 'Y',
			],
		]);
		while ($shipmentRealization = $shipmentRealizations->fetch())
		{
			$result[] = (int)$shipmentRealization['SHIPMENT_ID'];
		}

		return $result;
	}

	private function doesEntityExist(): bool
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($this->ownerTypeId);
		$item = null;

		if ($factory && $factory->isLinkWithProductsEnabled())
		{
			$item = $factory->getItem($this->ownerId, ['ID']);
		}

		return (bool)$item;
	}

	private function fetchEntity(): bool
	{
		if ((int)$this->ownerTypeId < 0 || (int)$this->ownerId < 0)
		{
			return false;
		}

		$factory = Crm\Service\Container::getInstance()->getFactory($this->ownerTypeId);
		if ($factory && $factory->isLinkWithProductsEnabled())
		{
			$item = $factory->getItem($this->ownerId);
			if ($item)
			{
				$this->entityAmount = $item->getOpportunity();
				$this->totalSum = $this->entityAmount;
				$this->currencyId = $item->getCurrencyId();
				$this->currencyFormat = \CCurrencyLang::GetFormatDescription($this->currencyId);
				return true;
			}
		}

		return false;
	}

	/**
	 * @return void
	 */
	private function fetchOrderIds(): void
	{
		$this->orderIds = Crm\Binding\OrderEntityTable::getOrderIdsByOwner(
			$this->ownerId,
			$this->ownerTypeId
		);
	}

	private function fetchOrders(): void
	{
		if (count($this->orderIds) <= 0)
		{
			return;
		}

		$orders = Sale\Internals\OrderTable::getList([
			'select' => ['ID', 'ACCOUNT_NUMBER', 'PRICE', 'CURRENCY'],
			'filter' => [
				'=ID' => $this->orderIds,
			],
		]);
		while ($order = $orders->fetch())
		{
			$this->orders[] = [
				'ID' => $order['ID'],
				'ACCOUNT_NUMBER' => $order['ACCOUNT_NUMBER'],
				'TITLE' => Main\Localization\Loc::getMessage(
					'PAYMENT_DOCUMENT_REPOSITORY_ORDER_TITLE',
					[
						'#ACCOUNT_NUMBER#' => $order['ACCOUNT_NUMBER']
					]
				),
				'PRICE_FORMAT' => \CCrmCurrency::MoneyToString($order['PRICE'], $order['CURRENCY']),
			];
		}
	}

	/**
	 * @return void
	 */
	private function fetchDocuments()
	{
		$documents = $this->fetchPaymentDocuments();

		$shipments = $this->fetchShipments();
		if ($shipments)
		{
			$shipmentDocuments = $this->fetchShipmentDocuments($shipments);
			$documents = array_merge($documents, $shipmentDocuments);

			if ($this->isUsedInventoryManagement)
			{
				$realizationDocuments = $this->fetchRealizationDocuments($shipments);
				$documents = array_merge($documents, $realizationDocuments);
			}
		}

		$documents = array_merge($documents, $this->fetchCheckDocuments());

		foreach ($documents as &$document)
		{
			if ($document['CURRENCY'] && $document['CURRENCY'] !== $this->currencyId)
			{
				$document['SUM'] = \CCrmCurrency::ConvertMoney(
					$document['ORIG_SUM'],
					$document['ORIG_CURRENCY'],
					$this->currencyId
				);
				$document['CURRENCY'] = $this->currencyId;
			}

			if (isset($document['DATE']))
			{
				$document['FORMATTED_DATE'] = ConvertTimeStamp($document['DATE']->getTimestamp(), 'SHORT');
			}
		}

		$this->documents = $documents;
	}

	/**
	 * @return array
	 */
	private function fetchPaymentDocuments(): array
	{
		if (count($this->orderIds) <= 0)
		{
			return [];
		}

		$select = [
			'ID',
			'ORDER_ID',
			'ACCOUNT_NUMBER',
			'PAID',
			'DATE_BILL',
			'SUM',
			'CURRENCY',
			'PAY_SYSTEM_NAME',
			'DATE_PAID',
			'TERMINAL_PAYMENT_ID' => 'TERMINAL_PAYMENT.PAYMENT_ID',
		];
		$filter = ['=ORDER_ID' => $this->orderIds];

		$payments = Sale\PaymentCollection::getList([
			'select' => $select,
			'filter' => $filter,
			'runtime' => [
				Crm\Service\Container::getInstance()->getTerminalPaymentService()->getRuntimeReferenceField('left'),
			],
		]);

		return $this->processPaymentDocuments($payments);
	}

	private function processPaymentDocuments(Main\DB\Result $paymentDocuments): array
	{
		$result = [];
		$paymentIds = [];

		while ($payment = $paymentDocuments->fetch())
		{
			$paymentIds[] = $payment['ID'];
			if ((int)$payment['TERMINAL_PAYMENT_ID'] > 0)
			{
				$payment['TYPE'] = 'TERMINAL_PAYMENT';
			}
			else
			{
				$payment['TYPE'] = 'PAYMENT';
			}
			$payment['ORIG_SUM'] = $payment['SUM'];
			$payment['ORIG_CURRENCY'] = $payment['CURRENCY'];
			$payment['DATE'] = $payment['DATE_BILL'];
			if ($payment['DATE_PAID'])
			{
				$payment['FORMATTED_DATE_PAID'] = ConvertTimeStamp($payment['DATE_PAID']->getTimestamp(), 'FULL');
			}

			$payment['STAGE'] = ($payment['PAID'] === 'Y') ? PaymentStage::PAID : PaymentStage::NOT_PAID;

			if ($payment['PAID'] === 'Y')
			{
				$this->paidSum += $payment['SUM'];
				$this->totalSum -= $payment['SUM'];
			}

			$result[$payment['ID']] = $payment;
		}

		if (count($paymentIds) > 0)
		{
			$paymentStages = EntityStageTable::getList([
				'select' => ['ENTITY_ID', 'STAGE'],
				'filter' => ['=ENTITY_ID' => $paymentIds, '=WORKFLOW_CODE' => PaymentWorkflow::getWorkflowCode()]
			]);
			while ($paymentStage = $paymentStages->fetch())
			{
				if ($result[$paymentStage['ENTITY_ID']])
				{
					$result[$paymentStage['ENTITY_ID']]['STAGE'] = $paymentStage['STAGE'];
				}
			}
		}

		return array_values($result);
	}
	/**
	 * @return array
	 */
	private function fetchShipmentDocuments(array $shipments): array
	{
		$result = [];

		foreach ($shipments as $shipment)
		{
			$isEmptyDeliveryService = (
				$shipment['DELIVERY_CLASS_NAME'] === '\\' . Sale\Delivery\Services\EmptyDeliveryService::class
				|| is_subclass_of($shipment['DELIVERY_CLASS_NAME'], Sale\Delivery\Services\EmptyDeliveryService::class)
			);

			if ($isEmptyDeliveryService)
			{
				continue;
			}

			$shipment['TYPE'] = 'SHIPMENT';
			$shipment['ORIG_SUM'] = $shipment['PRICE_DELIVERY'];
			$shipment['ORIG_CURRENCY'] = $shipment['CURRENCY'];
			$shipment['SUM'] = $shipment['PRICE_DELIVERY'];
			$shipment['DATE'] = $shipment['DATE_INSERT'];

			$result[] = $shipment;
		}

		return $result;
	}

	/**
	 * @return array
	 */
	private function fetchRealizationDocuments(array $shipments): array
	{
		$result = [];

		$preparedShipments = [];
		foreach ($shipments as $shipment)
		{
			$preparedShipments[$shipment['ID']] = $shipment;
		}

		$shipmentRealizations = Crm\Order\Internals\ShipmentRealizationTable::getList([
			'select' => ['SHIPMENT_ID'],
			'filter' => [
				'=SHIPMENT_ID' => array_keys($preparedShipments),
				'=IS_REALIZATION' => 'Y',
			],
		]);
		while ($shipmentRealization = $shipmentRealizations->fetch())
		{
			$shipment = $preparedShipments[$shipmentRealization['SHIPMENT_ID']] ?? null;
			if ($shipment)
			{
				$shipment['TYPE'] = 'SHIPMENT_DOCUMENT';
				$shipment['ORIG_SUM'] = $shipment['PRICE_DELIVERY'];
				$shipment['ORIG_CURRENCY'] = $shipment['CURRENCY'];
				$shipment['DATE'] = $shipment['DATE_INSERT'];

				$result[] = $shipment;
			}
		}

		$documentTotals = $this->calculateDocumentTotals($shipments);
		foreach ($result as $index => $document)
		{
			$result[$index]['SUM'] = $documentTotals[$document['ID']] ?? 0;
		}

		return $result;
	}

	private function calculateDocumentTotals(array $documentList): array
	{
		$documentTotals = [];

		$shipmentIds = array_column($documentList, 'ID');
		$shipmentBasketResult = Sale\ShipmentItem::getList([
			'select' => [
				'PRICE' => 'BASKET.PRICE',
				'VAT_INCLUDED' => 'BASKET.VAT_INCLUDED',
				'VAT_RATE' => 'BASKET.VAT_RATE',
				'ORDER_DELIVERY_ID',
				'QUANTITY',
			],
			'filter' => ['=ORDER_DELIVERY_ID' => $shipmentIds]
		]);
		while ($shipmentItem = $shipmentBasketResult->fetch())
		{
			if (!isset($documentTotals[$shipmentItem['ORDER_DELIVERY_ID']]))
			{
				$documentTotals[$shipmentItem['ORDER_DELIVERY_ID']] = 0;
			}

			if ($shipmentItem['VAT_INCLUDED'] !== 'Y')
			{
				$shipmentItem['PRICE'] += (float)$shipmentItem['PRICE'] * (float)$shipmentItem['VAT_RATE'];
			}

			$documentTotals[$shipmentItem['ORDER_DELIVERY_ID']] += (float)$shipmentItem['PRICE'] * $shipmentItem['QUANTITY'];
		}

		return $documentTotals;
	}

	private function fetchShipments(): array
	{
		$result = [];

		if (empty($this->orderIds))
		{
			return $result;
		}

		$shipments = Sale\ShipmentCollection::getList([
			'select' => [
				'ID', 'ORDER_ID', 'ACCOUNT_NUMBER', 'DATE_INSERT', 'STATUS_ID', 'PRICE_DELIVERY', 'BASE_PRICE_DELIVERY',
				'CURRENCY', 'DELIVERY_ID', 'DELIVERY_NAME', 'ALLOW_DELIVERY', 'DEDUCTED', 'EMP_DEDUCTED_ID',
				'DELIVERY_CLASS_NAME' => 'DELIVERY.CLASS_NAME'
			],
			'filter' => [
				'=ORDER_ID' => $this->orderIds,
				'!=SYSTEM' => 'Y',
			],
		]);
		while ($shipment = $shipments->fetch())
		{
			$result[] = $shipment;
		}

		return $result;
	}

	private function fetchCheckDocuments(): array
	{
		$result = [];

		if (empty($this->orderIds))
		{
			return $result;
		}

		foreach (Crm\Order\Manager::getCheckData($this->orderIds) as $check)
		{
			$check['TYPE'] = 'CHECK';
			$result[] = $check;
		}

		return $result;
	}

	/**
	 * @return void
	 */
	private function subscribeToPullEvents()
	{
		$userId = (int)\CCrmSecurityHelper::GetCurrentUserID();

		if ($userId <= 0 || !Main\Loader::includeModule('pull'))
		{
			return;
		}

		\CPullWatch::Add($userId, 'CRM_ENTITY_ORDER');

		if (count($this->orderIds) > 0 && Main\Loader::includeModule('salescenter'))
		{
			foreach ($this->orderIds as $orderId)
			{
				\CPullWatch::Add($userId, "SALESCENTER_ORDER_PAYMENT_VIEWED_$orderId");
			}
		}
	}

	/**
	 * Makes outgoing data structure
	 * @return array
	 */
	private function formatResult(): array
	{
		return [
			'OWNER_ID' => $this->ownerId,
			'OWNER_TYPE_ID' => $this->ownerTypeId,
			'ORDERS' => $this->orders,
			'DOCUMENTS' => $this->documents,
			'ORDER_IDS' => $this->orderIds,
			'CURRENCY_ID' => $this->currencyId,
			'CURRENCY_FORMAT' => $this->currencyFormat,
			'ENTITY_AMOUNT' => $this->entityAmount,
			'PAID_AMOUNT' => $this->paidSum,
			'TOTAL_AMOUNT' => $this->totalSum > 0 ? $this->totalSum : 0.0,
		];
	}

	/**
	 * @param string[] ...$modules
	 * @return bool
	 * @throws Main\LoaderException
	 */
	private function includeModules(string ...$modules): bool
	{
		foreach ($modules as $module)
		{
			if (!Main\Loader::includeModule($module))
			{
				throw new Main\LoaderException("Module $module not included");
			}
		}
		return true;
	}
}
