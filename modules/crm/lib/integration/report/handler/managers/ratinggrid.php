<?php

namespace Bitrix\Crm\Integration\Report\Handler\Managers;


use Bitrix\Crm\PhaseSemantics;

class RatingGrid extends Rating
{
	public function getMultipleData()
	{
		$calculatedData = $this->getCalculatedData();

		$result = [];
		foreach ($calculatedData as $row)
		{
			$userId = $row['USER_ID'];
			$result[$userId] = [
				'value' => [
					'userId' => $userId,
					'userFields' => $this->getUserInfo($userId),
					'successDealCount' => $row['COUNT_WON'],
					'successDealCountPrev' => $row['COUNT_WON_PREV'],
					'totalDealCount' => $row['COUNT_TOTAL'],
					'totalDealAmount' => $row['AMOUNT_TOTAL'],
					'totalDealAmountPrev' => $row['AMOUNT_TOTAL_PREV'],
					'successDealAmount' => $row['AMOUNT_WON'],
					'successDealAmountPrev' => $row['AMOUNT_WON_PREV'],
					'averageSuccessDealAmount' => $row['COUNT_WON'] > 0 ? $row['AMOUNT_WON'] / $row['COUNT_WON'] : 0,
					'averageSuccessDealAmountPrev' => $row['COUNT_WON_PREV'] > 0 ? $row['AMOUNT_WON_PREV'] / $row['COUNT_WON_PREV'] : 0,
				],
				'targetUrl' => [
					'totalDealCount' => $this->getTargetUrl('/crm/deal/analytics/list/', [
						'ASSIGNED_BY_ID' => $row['USER_ID'],
					]),
					'totalDealAmount' => $this->getTargetUrl('/crm/deal/analytics/list/', [
						'ASSIGNED_BY_ID' => $row['USER_ID'],
					]),
					'successDealCount' => $this->getTargetUrl('/crm/deal/analytics/list/', [
						'ASSIGNED_BY_ID' => $row['USER_ID'],
						'STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS,
					]),
					'successDealAmount' => $this->getTargetUrl('/crm/deal/analytics/list/', [
						'ASSIGNED_BY_ID' => $row['USER_ID'],
						'STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS,
					]),
				]
			];
		}

		return $result;
	}
}