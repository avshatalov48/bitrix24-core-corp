<?php

namespace Bitrix\Crm\Deal;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\Workflow\PaymentStage;
use Bitrix\Crm\Workflow\PaymentWorkflow;
use Bitrix\Crm\Workflow\EntityStageTable;
use Bitrix\Sale;

/**
 * Class retrieves documents related to the deal.
 *
 * You should instanciate this class objects with Service Locator:
 * Bitrix\Main\DI\ServiceLocator::getInstance()->get('crm.deal.paymentDocumentsRepository');
 */
class PaymentDocumentsRepository
{
	public const ERROR_CODE_DEAL_NOT_FOUND = 'DEAL_NOT_FOUND';

	/** @var int */
	private $dealId = null;

	/** @var float */
	private $dealAmount = null;

	/** @var array */
	private $documents = [];

	/** @var int[] */
	private $orderIds = [];

	/** @var string */
	private $currencyId = null;

	/** @var array */
	private $currencyFormat = [];

	/**
	 * @throws Main\LoaderException
	 */
	public function __construct()
	{
		$this->includeModules('crm', 'sale', 'currency');
	}

	/**
	 * Entry point.
	 * @param int $dealId
	 * @return Main\Result
	 */
	public function getDocumentsForDeal(int $dealId): Main\Result
	{
		$this->dealId = $dealId;

		$result = new Main\Result;

		if ($this->fetchDeal())
		{
			$this->fetchOrders();
			$this->fetchDocuments();
			$this->subscribeToPullEvents();

			$result->setData($this->formatResult());
		}
		else
		{
			$result->addError(
				new Main\Error("Deal {$this->dealId} not found", static::ERROR_CODE_DEAL_NOT_FOUND)
			);
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	private function fetchDeal(): bool
	{
		$filter = ['=ID' => $this->dealId, 'CHECK_PERMISSIONS' => 'N'];
		$select = ['ID', 'CATEGORY_ID', 'CURRENCY_ID', 'OPPORTUNITY'];

		$deal = \CCrmDeal::GetListEx([], $filter, false, false, $select)->Fetch();

		if ($deal)
		{
			$this->dealAmount = (float)$deal['OPPORTUNITY'];
			$this->currencyId = $deal['CURRENCY_ID'];
			$this->currencyFormat = \CCurrencyLang::GetFormatDescription($this->currencyId);
			return true;
		}

		return false;
	}

	/**
	 * @return void
	 */
	private function fetchOrders()
	{
		$rows = Crm\Order\DealBinding::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=DEAL_ID' => $this->dealId
			]
		]);
		while ($row = $rows->fetch())
		{
			if ($row['ORDER_ID'] && (int)$row['ORDER_ID'] > 0)
			{
				$this->orderIds[] = (int)$row['ORDER_ID'];
			}
		}
	}

	/**
	 * @return void
	 */
	private function fetchDocuments()
	{
		$documents = $this->fetchPayments();
		$documents = array_merge($documents, $this->fetchShipments());

		foreach ($documents as &$document)
		{
			if ($document['CURRENCY'] !== $this->currencyId)
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
	private function fetchPayments(): array
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
	private function fetchShipments(): array
	{
		if (empty($this->orderIds))
		{
			return [];
		}

		$select = [
			'ID', 'ORDER_ID', 'ACCOUNT_NUMBER', 'DATE_INSERT', 'STATUS_ID', 'PRICE_DELIVERY', 'BASE_PRICE_DELIVERY',
			'CURRENCY', 'DELIVERY_ID', 'DELIVERY_NAME', 'ALLOW_DELIVERY', 'DEDUCTED'
		];
		$filter = ['=ORDER_ID' => $this->orderIds, '!SYSTEM' => 'Y'];
		$result = [];

		$shipments = Sale\ShipmentCollection::getList([
			'select' => $select,
			'filter' => $filter,
		]);
		while ($shipment = $shipments->fetch())
		{
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
			'DEAL_ID' => $this->dealId,
			'DEAL_AMOUNT' => $this->dealAmount,
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