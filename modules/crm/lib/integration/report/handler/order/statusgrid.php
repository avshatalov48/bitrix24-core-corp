<?php

namespace Bitrix\Crm\Integration\Report\Handler\Order;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

/**
 * Class Order
 * @package Bitrix\Crm\Integration\Report\Handler\Order
 */
class StatusGrid extends BaseGrid
{
	/**
	 * @return array
	 */
	public function getGroupByOptions()
	{
		return [
			self::GROUPING_BY_USER_ID => 'stage'
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
		$query->addSelect(Query::expr()->sum('PRICE'), 'SUM');
		$query->addSelect(Query::expr()->count('STATUS_ID'), 'ORDER_COUNT');
		$query->setGroup(['CURRENCY', 'STATUS_ID']);


		return $query;
	}

	protected function formatData(array $data = []): array
	{
		$calculateField = $this->getFormElement('calculate');
		$calculateValue = $calculateField ? $calculateField->getValue() : null;
		$semanticIdsFilter = [];
		if ($calculateValue === self::WHAT_WILL_CALCULATE_SUCCESS_ORDER_DATA_FOR_FUNNEL)
		{
			$semanticIdsFilter = [PhaseSemantics::SUCCESS];
		}

		$resultStatuses = $this->prepareStatuses($semanticIdsFilter);

		$amountCount = 0;
		$amountSum = 0;
		foreach ($data as $item)
		{
			$currentStatusId = $item['STATUS_ID'];
			$sum = $this->convertSumToReportCurrency($item['SUM'], $item['CURRENCY']);
			$amountSum += $sum;
			$count = (float)$item['ORDER_COUNT'];
			$amountCount += $count;

			if (isset($resultStatuses[$currentStatusId]))
			{
				$resultStatuses[$currentStatusId][self::WHAT_WILL_CALCULATE_ORDER_SUM] += $sum;
				$resultStatuses[$currentStatusId][self::WHAT_WILL_CALCULATE_ORDER_COUNT] += $count;
			}
		}

		if ($calculateValue !== self::WHAT_WILL_CALCULATE_SUCCESS_ORDER_DATA_FOR_FUNNEL && $this->isConversionCalculateMode())
		{
			$resultStatuses = $this->calculateConversionStatuses($resultStatuses);
		}

		$resultItems = [];
		foreach ($resultStatuses as $id => $status)
		{
			$value = $status[self::WHAT_WILL_CALCULATE_ORDER_COUNT];
			$resultItems[$id] = [
				'groupBy' => $id,
				'label' => $status['NAME'],
				'value' => $value,
				'color' => $status['COLOR'],
				'link' => $this->getTargetUrl('/shop/orders/analytics/list/', [
					'STATUS_ID' => $id
				]),
				'additionalValues' => [
					'firstAdditionalValue' => [
						'value' => $value,
					],
					'secondAdditionalValue' => [
						'value' => \CCrmCurrency::MoneyToString($status[self::WHAT_WILL_CALCULATE_ORDER_SUM], \CCrmCurrency::GetAccountCurrencyID()),
						'currencyId' => \CCrmCurrency::GetAccountCurrencyID()
					],
					'thirdAdditionalValue' => [
						'value' => $amountCount ? round(($value / $amountCount) * 100,	2) : 0,
						'unitOfMeasurement' => '%',
						'helpLink' => 'someLink',
						'helpInSlider' => true
					],
				]
			];
		}

		return [
			'items' => $resultItems,
			'amount' => [
				'sum' => $amountSum,
				'count' => $amountCount,
			],
			'config' => [
				'additionalValues' => [
					'firstAdditionalValue' => [
						'titleShort' => Loc::getMessage('CRM_REPORT_ORDER_HANDLER_ORDER_COUNT_SHORT_TITLE'),
					],
					'secondAdditionalValue' => [
						'titleShort' => Loc::getMessage('CRM_REPORT_ORDER_HANDLER_ORDER_SUM_SHORT_TITLE'),
					],
					'thirdAdditionalValue' => [
						'titleShort' => Loc::getMessage('CRM_REPORT_ORDER_HANDLER_ORDER_CONVERSION_SHORT_TITLE'),
					],
				]
			]
		];
	}

	/**
	 * @param array $semantics
	 *
	 * @return array
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	protected function prepareStatuses(array $semantics = []): array
	{
		$resultStatuses = [];
		$allStatuses = \CCrmViewHelper::GetOrderStatusInfos();
		foreach ($allStatuses as $status)
		{
			if (!empty($semantics) && !in_array(OrderStatus::getSemanticID($status['STATUS_ID']), $semantics, true))
			{
				continue;
			}

			$status[self::WHAT_WILL_CALCULATE_ORDER_SUM] = 0;
			$status[self::WHAT_WILL_CALCULATE_ORDER_COUNT] = 0;
			$resultStatuses[$status['STATUS_ID']] = $status;
		}

		return $resultStatuses;
	}

	public function getTargetUrl($baseUri, $params = [])
	{
		$statusId = (!empty($params['STATUS_ID']) && is_string($params['STATUS_ID'])) ? $params['STATUS_ID'] : null;
		if (
			!empty($statusId)
			&& $this->isConversionCalculateMode()
			&& OrderStatus::getSemanticID($statusId) !== PhaseSemantics::FAILURE
		)
		{
			$params['STATUS_ID'] = [];
			$allStatuses = OrderStatus::getListInCrmFormat();
			if (isset($allStatuses[$statusId]))
			{
				$sort = $allStatuses[$statusId]['SORT'];
				foreach ($allStatuses as $id => $status)
				{
					if ($sort <= $status['SORT'] && OrderStatus::getSemanticID($status['STATUS_ID']) !== PhaseSemantics::FAILURE)
					{
						$params['STATUS_ID'][] = $id;
					}
				}
			}
		}

		return parent::getTargetUrl($baseUri, $params);
	}

	/**
	 * @param array $resultStatuses
	 *
	 * @return array
	 * @throws \Bitrix\Main\NotImplementedException
	 */
	protected function calculateConversionStatuses(array $resultStatuses): array
	{
		$conversionCount = 0;
		$conversionSum = 0;
		foreach ($resultStatuses as $status) {
			if (OrderStatus::getSemanticID($status['STATUS_ID']) !== PhaseSemantics::FAILURE)
			{
				$conversionSum += $status[self::WHAT_WILL_CALCULATE_ORDER_SUM];
				$conversionCount += $status[self::WHAT_WILL_CALCULATE_ORDER_COUNT];
			}
		}

		$previousStatusesCount = 0;
		$previousStatusesSum = 0;
		foreach ($resultStatuses as $id => $status) {
			if (OrderStatus::getSemanticID($status['STATUS_ID']) !== PhaseSemantics::FAILURE)
			{
				$currentSum = $status[self::WHAT_WILL_CALCULATE_ORDER_SUM];
				$currentCount = $status[self::WHAT_WILL_CALCULATE_ORDER_COUNT];
				$resultStatuses[$id][self::WHAT_WILL_CALCULATE_ORDER_SUM] = $conversionSum - $previousStatusesSum;
				$resultStatuses[$id][self::WHAT_WILL_CALCULATE_ORDER_COUNT] = $conversionCount - $previousStatusesCount;
				$previousStatusesCount += $currentCount;
				$previousStatusesSum += $currentSum;
			}
		}
		unset($resultStatuses[OrderStatus::getFinalStatus()]);
		return $resultStatuses;
}
}