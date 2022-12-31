<?php

namespace Bitrix\Crm\Deal;

use Bitrix\Crm\Order;
use Bitrix\Main\Entity;
use Bitrix\Crm\Binding;
use Bitrix\Main\DB;
use Bitrix\Crm\Workflow;
use Bitrix\Main;
use Bitrix\Sale;

/**
 * Class provides several methods to fetch payments, related to deals
 * @package Bitrix\Crm\Deal
 */
final class PaymentsRepository
{
	/**
	 * @throws Main\LoaderException
	 */
	public function __construct()
	{
		Main\Loader::includeModule('sale');
	}

	/**
	 * Returns map [dealId => stage of the latest related payment]
	 * @param array $dealIds
	 * @return array<int, string>
	 */
	public function getPaymentStages(array $dealIds): array
	{
		if (empty($dealIds))
		{
			return [];
		}

		static $result = [];

		$dealIdsForLoadingStages = [];
		foreach ($dealIds as $dealId)
		{
			if (!isset($result[$dealId]))
			{
				$dealIdsForLoadingStages[] = $dealId;
			}
		}

		if ($dealIdsForLoadingStages)
		{
			$result += $this->loadPaymentStages($dealIdsForLoadingStages);
		}

		$dealIdsAsKey = array_fill_keys($dealIds, true);

		return array_filter(
			$result,
			static function ($key) use ($dealIdsAsKey)
			{
				return isset($dealIdsAsKey[$key]);
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	protected function loadPaymentStages(array $dealIds) : array
	{
		$result = [];

		$paymentRepository = Sale\Repository\PaymentRepository::getInstance();

		$dbRes = Order\PaymentCollection::getList([
			'select' => ['ID', 'DEAL_ID' => 'DEAL_BINDING.OWNER_ID'],
			'filter' => [
				'@DEAL_ID' => $dealIds,
			],
			'order' => ['ORDER_ID' => 'desc', 'ID' => 'desc'],
			'runtime' => [
				new Entity\ReferenceField(
					'DEAL_BINDING',
					Binding\OrderEntityTable::class,
					[
						'=this.ORDER_ID' => 'ref.ORDER_ID',
						'=ref.OWNER_TYPE_ID' => new DB\SqlExpression(\CCrmOwnerType::Deal)
					],
					['join_type' => 'inner']
				)
			]
		]);
		while ($payment = $dbRes->fetch())
		{
			if (isset($result[$payment['DEAL_ID']]))
			{
				continue;
			}

			$paymentObject = $paymentRepository->getById($payment['ID']);
			if ($paymentObject)
			{
				$result[$payment['DEAL_ID']] = Workflow\PaymentWorkflow::createFrom($paymentObject)->getStage();
			}
		}

		return $result;
	}
}
