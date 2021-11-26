<?php

namespace Bitrix\Crm\Deal;

use Bitrix\Crm\Order\PaymentCollection;
use Bitrix\Crm\Workflow\PaymentWorkflow;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Sale;

/**
 * Class provides several methods to fetch payments, related to deals
 * @package Bitrix\Crm\Deal
 */
final class PaymentsRepository
{
	use OrdersMapMixin;

	/**
	 * @throws LoaderException
	 */
	public function __construct()
	{
		Loader::includeModule('sale');
	}

	/**
	 * Returns map [dealId => stage of latest related payment]
	 * @param array $dealIds
	 * @return array<int, string>
	 */
	public function getPaymentStages(array $dealIds): array
	{
		if (count($dealIds) === 0)
		{
			return [];
		}

		$orderToDealMap = $this->getOrderToDealMap($dealIds);
		$orderIds = array_keys($orderToDealMap);

		if (count($orderIds) === 0)
		{
			return [];
		}

		$result = [];
		$paymentRepository = Sale\Repository\PaymentRepository::getInstance();

		$payments = PaymentCollection::getList([
			'select' => ['ID', 'ORDER_ID'],
			'filter' => ['=ORDER_ID' => $orderIds],
			'order' => ['ORDER_ID' => 'desc', 'ID' => 'desc'],
		]);
		while ($payment = $payments->fetch())
		{
			$paymentId = (int)$payment['ID'];
			$orderId = (int)$payment['ORDER_ID'];
			$dealId = $orderToDealMap[$orderId];
			if ($dealId && !isset($result[$dealId]))
			{
				$paymentObject = $paymentRepository->getById($paymentId);
				if ($paymentObject)
				{
					$result[$dealId] = PaymentWorkflow::createFrom($paymentObject)->getStage();
				}
			}
		}

		return $result;
	}
}
