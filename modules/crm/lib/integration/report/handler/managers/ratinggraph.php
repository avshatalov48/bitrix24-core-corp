<?php

namespace Bitrix\Crm\Integration\Report\Handler\Managers;

use Bitrix\Crm\PhaseSemantics;

class RatingGraph extends Rating
{
	public function getMultipleGroupedData()
	{
		$calculatedData = $this->getCalculatedData();
		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();
		$normalizedData = [];
		$userIds = [];
		foreach ($calculatedData as $row)
		{
			[$userId, $countWon, $countTotal, $amountWon, $amountWonPrev] = [$row['USER_ID'], $row['COUNT_WON'], $row['COUNT_TOTAL'], $row['AMOUNT_WON'], $row['AMOUNT_WON_PREV']];
			if(!$normalizedData[$userId])
			{
				$normalizedData[$userId] = [
					'userId' => $userId,
					'countWon' => 0,
					'countTotal' => 0,
					'amountWon' => 0,
					'amountWonPrev' => 0,
				];
			}
			$normalizedData[$userId]['countWon'] += $countWon;
			$normalizedData[$userId]['countTotal'] += $countTotal;
			$normalizedData[$userId]['amountWon'] += $amountWon;
			$normalizedData[$userId]['amountWonPrev'] += $amountWonPrev;
			$userIds[] = $userId;
		}

		$items = [];
		$labels = [];

		$this->preloadUserInfo($userIds);

		foreach ($normalizedData as $key => $fields)
		{
			$userId = $key;
			$userInfo = $this->getUserInfo($userId, ['avatarWidth' => 60, 'avatarHeight' => 60]);

			$items[] = [
				'groupBy' => $key,
				'value' => $fields['amountWon'],
				'balloon' => [
					'userName' => $userInfo['name'],
					'amountWon' => $fields['amountWon'],
					'amountWonPrev' => $fields['amountWonPrev'],
					'amountWonFormatted' => \CCrmCurrency::MoneyToString($fields['amountWon'], $baseCurrency),
					'amountWonPrevFormatted' => \CCrmCurrency::MoneyToString($fields['amountWonPrev'], $baseCurrency),
					'avgWonAvgAmountFormatted' => \CCrmCurrency::MoneyToString($fields['countWon'] ? $fields['amountWon'] / $fields['countWon'] : 0, $baseCurrency),
					'countWon' => $fields['countWon'],
					'countTotal' => $fields['countTotal'],
					'icon' => $userInfo['icon']
				],
				'targetUrl' => $this->getTargetUrl('/crm/deal/analytics/list/', [
					'ASSIGNED_BY_ID' => $userId,
					'STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS,
				])
			];

			$labels[$userId] = $userInfo['name'];
		}

		$result = [
			"items" => $items,
			"config" => [
				"groupsLabelMap" => $labels,
				"reportTitle" => $this->getFormElementValue("label"),
				"reportColor" => $this->getFormElementValue("color"),
				"reportTitleShort" => $this->getFormElementValue("label"),
				"reportTitleMedium" => $this->getFormElementValue("label"),
			]
		];
		return $result;
	}
}