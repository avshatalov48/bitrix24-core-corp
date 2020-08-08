<?php

namespace Bitrix\Crm\Integration\Report\Handler\Order;

use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Web\Uri;

/**
 * Class Order
 * @package Bitrix\Crm\Integration\Report\Handler\Order
 */
class BuyersGrid extends BaseGrid
{
	public function __construct()
	{
		parent::__construct();
		$this->setTitle('Order');
		$this->setCategoryKey('sale');
	}
	/**
	 * @return array
	 */
	public function getGroupByOptions()
	{
		return [
			self::GROUPING_BY_STATUS_ID => 'stage'
		];
	}

	/**
	 *
	 * @param null $groupingValue Grouping field value.
	 *
	 * @return array
	 */
	public function getWhatWillCalculateOptions($groupingValue = null)
	{
		return [
			self::WHAT_WILL_CALCULATE_ORDER_COUNT => 'count'
		];
	}

	/**
	 * @param Query $query
	 *
	 * @return Query
	 */
	public function prepareQuery(Query $query): Query
	{
		$query = parent::prepareQuery($query);

		$query->addSelect('USER_ID');
		$query->addSelect('STATUS_ID');
		$query->addSelect('PRICE');
		$query->addSelect('CURRENCY');
		$query->addSelect(Query::expr()->count("ID"), "ORDER_COUNT");
		$query->addSelect(Query::expr()->sum("PRICE"), "ORDER_SUM");


		$query->setGroup(['CURRENCY', 'STATUS_ID', 'USER_ID']);
		return $query;
	}

	protected function formatData(array $data = []): array
	{
		$resultByUser = [];
		$commonCount = 0;
		foreach ($data as $item)
		{
			$id = (int)$item['USER_ID'];
			$count = (int)$item['ORDER_COUNT'];
			if (!isset($resultByUser[$id]))
			{
				$resultByUser[$id] = [
					self::WHAT_WILL_CALCULATE_ORDER_COUNT => 0,
					self::WHAT_WILL_CALCULATE_ORDER_WON_SUM => 0,
					self::WHAT_WILL_CALCULATE_ORDER_WON_COUNT => 0,
				];
			}

			if ($item['STATUS_ID'] === OrderStatus::getFinalStatus())
			{
				$itemSum = $this->convertSumToReportCurrency($item['ORDER_SUM'], $item['CURRENCY']);
				$resultByUser[$id][self::WHAT_WILL_CALCULATE_ORDER_WON_SUM] += $itemSum;
				$resultByUser[$id][self::WHAT_WILL_CALCULATE_ORDER_WON_COUNT] += $count;
			}

			$resultByUser[$id][self::WHAT_WILL_CALCULATE_ORDER_COUNT] += $count;
		}

		$items = [];
		$amountCount = 0;
		$amountSum = '';
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;
		if (!empty($resultByUser))
		{
			foreach ($resultByUser as $id => $userFields)
			{
				$value = $userFields[$calculateValue];
				if ($calculateValue === self::WHAT_WILL_CALCULATE_ORDER_AVERAGE_SUM && $userFields[self::WHAT_WILL_CALCULATE_ORDER_COUNT] > 0)
				{
					$value = $userFields[self::WHAT_WILL_CALCULATE_ORDER_SUM] / $userFields[self::WHAT_WILL_CALCULATE_ORDER_COUNT];
				}

				$amountCount += $value;
				if ($this->isSumCalculation())
				{
					$value = \CCrmCurrency::MoneyToString($value, \CCrmCurrency::GetAccountCurrencyID());
				}
				$userInfo = $this->getUserInfo($id);
				$items[$id] = [
					'groupBy' => $id,
					'name' => $userInfo['name'],
					'profileUrl' => '/shop/settings/sale_buyers_profile/?USER_ID='.$id.'&publicSidePanel=Y',
					'totalCount' => $userFields[self::WHAT_WILL_CALCULATE_ORDER_COUNT],
					'successSum' => $userFields[self::WHAT_WILL_CALCULATE_ORDER_WON_SUM],
					'successCount' => $userFields[self::WHAT_WILL_CALCULATE_ORDER_WON_COUNT],
					'successUrl' => $this->getTargetUrl('/shop/orders/analytics/list/', [
						'STATUS_ID' => OrderStatus::getFinalStatus(),
						'USER' => $userInfo['name']
					]),
					'slider' => true,
					'targetUrl' => $this->getTargetUrl('/shop/orders/analytics/list/', [
						'USER' => $userInfo['name']
					]),
				];
			}
		}

		if ($this->isSumCalculation())
		{
			$amountSum = \CCrmCurrency::MoneyToString($amountCount, \CCrmCurrency::GetAccountCurrencyID());
		}

		return [
			'items' => $items,
			'amount' => [
				'count' => $amountCount,
				'sum' => $amountSum,
				'ratio' => ($commonCount > 0) ? ($amountCount/$commonCount) : 0
			],
		];
	}

	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();
		$items = (isset($calculatedData['items']) && is_array($calculatedData['items'])) ? $calculatedData['items'] : [];
		return $items;
	}

}