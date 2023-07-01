<?php

namespace Bitrix\Crm\Integration\Report\View;

use Bitrix\Main\UI\Extension;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart\LinearGraph;

class SalesDynamicsGraph extends LinearGraph
{
	const VIEW_KEY = 'CRM_SALES_DYNAMICS';

	public function __construct()
	{
		parent::__construct();
		$this->setDraggable(false);
		Extension::load(["crm.report.salesdynamics"]);
	}

	public function handlerFinallyBeforePassToView($calculatedPerformedData)
	{
		$result = parent::handlerFinallyBeforePassToView($calculatedPerformedData);
		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();
		$sums = [
			1 => 0,
			2 => 0
		];

		if(is_array($result['dataProvider']))
		{
			$result['categoryAxis']['autoGridCount'] = true;
			$result['categoryAxis']['minHorizontalGap'] = 0;
			$result['categoryAxis']['labelFrequency'] = ceil(count($result['dataProvider']) / 10);
			//$result['categoryAxis']['labelFrequency'] = 1;

			$isFirst = true;
			$amountInitialPrev = 0;
			$amountReturnPrev = 0;
			$amountTotalPrev = 0;
			foreach ($result['dataProvider'] as $k => $item)
			{
				if(!isset($result['dataProvider'][$k]['value_1']))
				{
					$result['dataProvider'][$k]['value_1'] = 0;
				}
				if(!isset($result['dataProvider'][$k]['value_2']))
				{
					$result['dataProvider'][$k]['value_2'] = 0;
				}
				if(!isset($result['dataProvider'][$k]['balloon']['amountInitial']))
				{
					$result['dataProvider'][$k]['balloon']['amountInitial'] = 0;
				}
				if(!isset($result['dataProvider'][$k]['balloon']['amountReturn']))
				{
					$result['dataProvider'][$k]['balloon']['amountReturn'] = 0;
				}

				$sums[1] += $result['dataProvider'][$k]['value_1'];
				$sums[2] += $result['dataProvider'][$k]['value_2'];

				$amountInitial = $result['dataProvider'][$k]['balloon']['amountInitial'];
				$amountReturn = $result['dataProvider'][$k]['balloon']['amountReturn'];
				$amountTotal = $amountInitial + $amountReturn;


				$result['dataProvider'][$k]['balloon']['amountTotal'] = $amountTotal;
				$result['dataProvider'][$k]['balloon']['amountInitialFormatted'] = \CCrmCurrency::MoneyToString($amountInitial, $baseCurrency);
				$result['dataProvider'][$k]['balloon']['amountReturnFormatted'] = \CCrmCurrency::MoneyToString($amountReturn, $baseCurrency);
				$result['dataProvider'][$k]['balloon']['amountTotalFormatted'] = \CCrmCurrency::MoneyToString($amountTotal, $baseCurrency);

				if($isFirst)
				{
					$isFirst = false;
				}
				else
				{
					$result['dataProvider'][$k]['balloon']['amountInitialPrev'] = $amountInitialPrev;
					$result['dataProvider'][$k]['balloon']['amountReturnPrev'] = $amountReturnPrev;
					$result['dataProvider'][$k]['balloon']['amountTotalPrev'] = $amountTotalPrev;
				}

				$amountInitialPrev = $amountInitial;
				$amountReturnPrev = $amountReturn;
				$amountTotalPrev = $amountTotal;
			}
		}

		if(is_array($result['graphs']))
		{
			foreach ($result['graphs'] as $k => $graph)
			{
				$result['graphs'][$k]["balloonFunction"] = "BX.Crm.Report.Dashboard.Content.SalesDynamics.renderBalloon";
				$result['graphs'][$k]["balloon"]["borderThickness"] = 0;
			}

			$totalAmountFormatted_1 = \CCrmCurrency::MoneyToString($sums[1], $baseCurrency);
			$totalAmountFormatted_1 = str_replace("&nbsp;", " ", $totalAmountFormatted_1);
			$totalAmountFormatted_2 = \CCrmCurrency::MoneyToString($sums[2], $baseCurrency);
			$totalAmountFormatted_2 = str_replace("&nbsp;", " ", $totalAmountFormatted_2);

			if (!isset($result['graphs'][0]['title']))
			{
				$result['graphs'][0]['title'] = '';
			}
			$result['graphs'][0]['title'] .= " (" . $totalAmountFormatted_1 . ")";
			if (!isset($result['graphs'][1]['title']))
			{
				$result['graphs'][1]['title'] = '';
			}
			$result['graphs'][1]['title'] .= " (" . $totalAmountFormatted_2 . ")";
		}

		$result['legend']['valueText'] = '';


		return $result;
	}
}