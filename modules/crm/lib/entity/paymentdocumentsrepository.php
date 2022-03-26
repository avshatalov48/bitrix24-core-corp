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
	private $entityAmount = null;

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

		$result = new Main\Result;

		if ($this->fetchEntity())
		{
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

	private function fetchEntity(): bool
	{
		if ($this->ownerTypeId === \CCrmOwnerType::Deal)
		{
			return $this->fetchDeal();
		}

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($this->ownerTypeId))
		{
			return $this->fetchDynamicEntity();
		}

		return false;
	}

	/**
	 * @return bool
	 */
	private function fetchDeal(): bool
	{
		$filter = ['=ID' => $this->ownerId, 'CHECK_PERMISSIONS' => 'N'];
		$select = ['ID', 'CATEGORY_ID', 'CURRENCY_ID', 'OPPORTUNITY'];

		$deal = \CCrmDeal::GetListEx([], $filter, false, false, $select)->Fetch();

		if ($deal)
		{
			$this->entityAmount = (float)$deal['OPPORTUNITY'];
			$this->currencyId = $deal['CURRENCY_ID'];
			$this->currencyFormat = \CCurrencyLang::GetFormatDescription($this->currencyId);
			return true;
		}

		return false;
	}

	private function fetchDynamicEntity(): bool
	{
		$factory = Crm\Service\Container::getInstance()->getFactory($this->ownerTypeId);
		if ($factory && $factory->isLinkWithProductsEnabled())
		{
			$dynamicEntity = $factory->getItem($this->ownerId);
			if ($dynamicEntity)
			{
				$this->entityAmount = $dynamicEntity->getOpportunity();
				$this->currencyId = $dynamicEntity->getCurrencyId();
				$this->currencyFormat = \CCurrencyLang::GetFormatDescription($this->currencyId);
				return true;
			}
		}

		return false;
	}

	/**
	 * @return void
	 */
	private function fetchOrders()
	{
		$this->orderIds = Crm\Binding\OrderEntityTable::getOrderIdsByOwner(
			$this->ownerId,
			$this->ownerTypeId
		);
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

			$document['FORMATTED_DATE'] = ConvertTimeStamp($document['DATE']->getTimestamp(), 'SHORT');
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

		$select = ['ID', 'ORDER_ID', 'ACCOUNT_NUMBER', 'PAID', 'DATE_BILL', 'SUM', 'CURRENCY'];
		$filter = ['=ORDER_ID' => $this->orderIds];
		$result = [];

		$payments = Sale\PaymentCollection::getList([
			'select' => $select,
			'filter' => $filter,
		]);
		$paymentIds = [];
		while ($payment = $payments->fetch())
		{
			$paymentIds[] = $payment['ID'];
			$payment['TYPE'] = 'PAYMENT';
			$payment['ORIG_SUM'] = $payment['SUM'];
			$payment['ORIG_CURRENCY'] = $payment['CURRENCY'];
			$payment['DATE'] = $payment['DATE_BILL'];

			$payment['STAGE'] = ($payment['PAID'] === 'Y') ? PaymentStage::PAID : PaymentStage::NOT_PAID;

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
			'select' => ['PRICE' => 'BASKET.PRICE', 'ORDER_DELIVERY_ID', 'QUANTITY'],
			'filter' => ['=ORDER_DELIVERY_ID' => $shipmentIds]
		]);
		while ($shipmentItem = $shipmentBasketResult->fetch())
		{
			if (!isset($documentTotals[$shipmentItem['ORDER_DELIVERY_ID']]))
			{
				$documentTotals[$shipmentItem['ORDER_DELIVERY_ID']] = 0;
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
				'!SYSTEM' => 'Y',
			],
		]);
		while ($shipment = $shipments->fetch())
		{
			$result[] = $shipment;
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
			'ENTITY_AMOUNT' => $this->entityAmount,
			'DOCUMENTS' => $this->documents,
			'ORDER_IDS' => $this->orderIds,
			'CURRENCY_ID' => $this->currencyId,
			'CURRENCY_FORMAT' => $this->currencyFormat,
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
