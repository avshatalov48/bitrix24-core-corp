<?php


namespace Bitrix\Crm\Integration\Report\Handler\Customers;

use Bitrix\Crm\Integration\Report\Handler\Deal;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\DealTable;

class FinancialRatingGraph extends FinancialRating
{
	protected const COLORS = [
		"#9dcf00",
		"#14cbc3",
		"#ffa900",
		"#2fc6f6",
	];

	public function getMultipleGroupedData()
	{
		$calculatedData = $this->getCalculatedData();
		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();
		$normalizedData = [];
		foreach ($calculatedData as $row)
		{
			[$ownerType, $ownerId, $countWon, $countTotal, $amount, $amountPrev] = [$row['OWNER_TYPE'], $row['OWNER_ID'], $row['WON_COUNT'], $row['TOTAL_COUNT'], $row['WON_AMOUNT'], $row['PREV_WON_AMOUNT']];
			$key = $ownerType . "_" . $ownerId;
			if(!$normalizedData[$key])
			{
				$normalizedData[$key] = [
					'ownerType' => $ownerType,
					'ownerId' => $ownerId,
					'countWon' => 0,
					'countTotal' => 0,
					'amountWon' => 0,
					'amountWonPrev' => 0,
				];
			}
			$normalizedData[$key]['countWon'] += $countWon;
			$normalizedData[$key]['countTotal'] += $countTotal;
			$normalizedData[$key]['amountWon'] += $amount;
			$normalizedData[$key]['amountWonPrev'] += $amountPrev;
		}

		$items = [];
		$labels = [];
		$i = 0;
		foreach ($normalizedData as $key => $fields)
		{
			$ownerTypeId = \CCrmOwnerType::ResolveID($fields['ownerType']);
			$ownerId = (int)$fields['ownerId'];
			$caption = \CCrmOwnerType::GetCaption($ownerTypeId, $ownerId);
			$labels[$key] = $caption;
			$items[] = [
				'groupBy' => $key,
				'value' => $fields['amountWon'],
				'balloon' => [
					'color' => static::COLORS[($i >= 3 ? 3 : $i)],
					'wonAmount' => $fields['amountWon'],
					'wonAmountFormatted' => \CCrmCurrency::MoneyToString($fields['amountWon'], $baseCurrency),
					'wonAmountPrev' => $fields['amountWonPrev'],
					'wonAmountPrevFormatted' => \CCrmCurrency::MoneyToString($fields['amountWonPrev'], $baseCurrency),
					'clientTitle' => \CCrmOwnerType::GetCaption($ownerTypeId, $ownerId),
					'wonCount' => $fields['countWon'],
					'totalCount' => $fields['countTotal'],
					'avgWonAvgAmountFormatted' => \CCrmCurrency::MoneyToString($fields['countWon'] > 0 ? $fields['amountWon'] / $fields['countWon'] : 0, $baseCurrency)
				],
				'targetUrl' => \CCrmOwnerType::GetDetailsUrl($ownerTypeId, $ownerId)
			];
			$i++;
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