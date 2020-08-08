<?php

namespace Bitrix\Crm\Integration\Report\Handler\Order;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Web\Uri;

/**
 * Class Order
 * @package Bitrix\Crm\Integration\Report\Handler\Order
 */
class ResponsibleGrid extends BaseGrid
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
			self::GROUPING_BY_RESPONSIBLE_ID => 'stage'
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

		$query->addSelect('STATUS_ID');
		$query->addSelect('RESPONSIBLE_ID');
		$query->addSelect(Query::expr()->sum('PRICE'), 'SUM');
		$query->addSelect(Query::expr()->count('STATUS_ID'), 'ORDER_COUNT');
		$query->setGroup(['CURRENCY', 'STATUS_ID', 'RESPONSIBLE_ID']);

		return $query;
	}

	protected function formatData(array $data = []): array
	{
		$resultResponsible = [];
		$commonCount = 0;
		foreach ($data as $item)
		{
			$id = (int)$item['RESPONSIBLE_ID'];
			if (!isset($resultResponsible[$id]))
			{
				$resultResponsible[$id] = [
					self::WHAT_WILL_CALCULATE_ORDER_SUM => 0,
					self::WHAT_WILL_CALCULATE_ORDER_COUNT => 0,
					self::WHAT_WILL_CALCULATE_ORDER_LOSES_SUM => 0,
					self::WHAT_WILL_CALCULATE_ORDER_LOSES_COUNT => 0,
					self::WHAT_WILL_CALCULATE_ORDER_WON_SUM => 0,
					self::WHAT_WILL_CALCULATE_ORDER_WON_COUNT => 0,
				];
			}

			$itemSum = $this->convertSumToReportCurrency($item['SUM'], $item['CURRENCY']);
			$itemCount = (float)$item['ORDER_COUNT'];
			$commonCount += $itemCount;

			$resultResponsible[$id][self::WHAT_WILL_CALCULATE_ORDER_SUM] += $itemSum;
			$resultResponsible[$id][self::WHAT_WILL_CALCULATE_ORDER_COUNT] += $itemCount;

			if (OrderStatus::getSemanticID($item['STATUS_ID']) === PhaseSemantics::SUCCESS)
			{
				$resultResponsible[$id][self::WHAT_WILL_CALCULATE_ORDER_WON_SUM] += $itemSum;
				$resultResponsible[$id][self::WHAT_WILL_CALCULATE_ORDER_WON_COUNT] += $itemCount;
			}
			elseif (OrderStatus::getSemanticID($item['STATUS_ID']) === PhaseSemantics::FAILURE)
			{
				$resultResponsible[$id][self::WHAT_WILL_CALCULATE_ORDER_LOSES_SUM] += $itemSum;
				$resultResponsible[$id][self::WHAT_WILL_CALCULATE_ORDER_LOSES_COUNT] += $itemCount;
			}
		}

		$items = [];
		$amountCount = 0;
		$amountSum = '';
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;
		if (!empty($resultResponsible) && !empty($calculateValue))
		{
			foreach ($resultResponsible as $id => $responsible)
			{
				$resultItem = [
					'groupBy' => $id,
					'logo' => $responsible['icon'],
					'slider' => true,
					'targetUrl' => $this->getTargetUrl('/shop/orders/analytics/list/', [
						'RESPONSIBLE_ID' => $id
					]),
				];
				if ($calculateValue === self::WHAT_WILL_CALCULATE_ORDER_CONVERSION)
				{
					$orderCount = $responsible[self::WHAT_WILL_CALCULATE_ORDER_COUNT];
					$wonOrderCount = $responsible[self::WHAT_WILL_CALCULATE_ORDER_WON_COUNT];
					$value = $orderCount > 0 ? round(($wonOrderCount / $orderCount) * 100, 2) : 0;
					$resultItem['postfix'] = '%';
					$amountCount += $wonOrderCount;
				}
				else
				{
					$value = $responsible[$calculateValue];
					$amountCount += $value;
				}


				if ($this->isSumCalculation())
				{
					$value = \CCrmCurrency::MoneyToString($value, \CCrmCurrency::GetAccountCurrencyID());
				}

				$resultItem['value'] = $value;
				$responsibleInfo = $this->getUserInfo($id);
				$resultItem['label'] = $responsibleInfo['name'];
				$resultItem['profileUrl'] = $responsibleInfo['link'];
				$items[$id] = $resultItem;
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

	public function getTargetUrl($baseUri, $params = [])
	{
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;
		$paramsStatuses = (!empty($params['STATUS_ID']) && is_array($params['STATUS_ID'])) ? $params['STATUS_ID'] : null;

		switch ($calculateValue)
		{
			case self::WHAT_WILL_CALCULATE_ORDER_LOSES_COUNT:
			case self::WHAT_WILL_CALCULATE_ORDER_LOSES_SUM:
				$failureStatuses = [];
				$allStatuses = OrderStatus::getListInCrmFormat();
				foreach ($allStatuses as $status)
				{
					$statusId = $status["STATUS_ID"];
					if (
						OrderStatus::getSemanticID($statusId) === PhaseSemantics::FAILURE
						&& (empty($paramsStatuses) || in_array($statusId, $paramsStatuses))
					)
					{
						$failureStatuses[] = $statusId;
					}
				}
				$params['STATUS_ID'] = $failureStatuses;
				break;
			case self::WHAT_WILL_CALCULATE_SUCCESS_ORDER_DATA_FOR_FUNNEL:
			case self::WHAT_WILL_CALCULATE_ORDER_WON_SUM:
			case self::WHAT_WILL_CALCULATE_ORDER_WON_COUNT:
				$finalStatus = OrderStatus::getFinalStatus();
				if (empty($paramsStatuses) || in_array($finalStatus, $paramsStatuses))
				{
					$params['STATUS_ID'] = $finalStatus;
				}
				break;
		}

		return parent::getTargetUrl($baseUri, $params);
	}
}