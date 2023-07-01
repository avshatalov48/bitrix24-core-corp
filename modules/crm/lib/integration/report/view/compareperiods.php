<?php

namespace Bitrix\Crm\Integration\Report\View;

use Bitrix\Crm\Integration\Report\Handler\SalesDynamics\BaseGraph;
use Bitrix\Main\Type\Date;
use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart\Serial;

class ComparePeriods extends Serial
{
	const VIEW_KEY = 'crm_analytics_compare_periods';
	const USE_IN_VISUAL_CONSTRUCTOR = false;
	const ENABLE_SORTING = false;

	public function __construct()
	{
		parent::__construct();
		$this->setDraggable(false);
	}

	public function handlerFinallyBeforePassToView($dataFromReport)
	{
		//return parent::handlerFinallyBeforePassToView($dataFromReport);
		$baseCurrency = \CCrmCurrency::GetAccountCurrencyID();
		$totalAmountCurrent = 0;
		$totalAmountPrev = 0;

		$result = [
			'type' => $this->getAmChartType(),
			'theme' => 'none',
			'language' => 'ru',
			'pathToImages' => self::AM_CHART_LIB_PATH.'/images/',
			'zoomOutText' => 'AM_CHART_SHOW_ALL_BUTTON_TEXT',
			'dataProvider' => [],
			'dataDateFormat' => 'YYYY-MM-DD',
			'valueAxes' => [
				[
					'integersOnly' => true,
					'reversed' => false,
					'axisAlpha' => 0,
					'position' => 'left'
				]
			],
			'startDuration' => 0.5,
			'graphs' => [],
			'categoryField' => 'groupingField',
			'categoryAxis' => [
				'axisAlpha' => 0,
				'fillAlpha' => 0.05,
				'gridAlpha' => 0,
				'position' => 'bottom',
				'dashLength' => 1,
				'minorGridEnabled' => true
			],
			'export' => [
				'enabled' => true,
				'position' => 'bottom-right'
			],
			'legend' => [
				'useGraphSettings' => true,
				'equalWidths' => false,
				'position' => "bottom",
				'valueText' => '',
			],
			'chartCursor' => [
				'enabled' => true,
				'oneBalloonOnly' => true,
				'categoryBalloonEnabled' => true,
				'categoryBalloonColor' => "#000000",
				'cursorAlpha' => 1,
				'zoomable' => true,
			],
		];

		if (empty($dataFromReport))
		{
			return $result;
		}

		$currentPeriod = is_array($dataFromReport[0]) ? $dataFromReport[0] : [];
		$previousPeriod = is_array($dataFromReport[1]) ? $dataFromReport[1] : [];

		$maxPoints = max(count($currentPeriod['items']), count($previousPeriod['items']));

		if ($maxPoints == 0)
		{
			return $result;
		}

		$dateFormatForLabel = $currentPeriod['config']['dateFormatForLabel'];

		for ($i = 0; $i < $maxPoints; $i++)
		{
			$dateCurrent = null;
			if (isset($currentPeriod['items'][$i]['groupBy']))
			{
				$dateCurrent = new Date($currentPeriod['items'][$i]['groupBy'], BaseGraph::DATE_INDEX_FORMAT);
			}

			$datePrev = null;
			if (isset($previousPeriod['items'][$i]['groupBy']))
			{
				$datePrev = new Date($previousPeriod['items'][$i]['groupBy'], BaseGraph::DATE_INDEX_FORMAT);
			}

			$formattedDateCurrent = $dateCurrent ? FormatDate($dateFormatForLabel, $dateCurrent) : "-";
			$formattedDatePrev = $datePrev ? FormatDate($dateFormatForLabel, $datePrev) : "-";
			$result['dataProvider'][$i] = [
				'groupingField' => $formattedDateCurrent.'<br>'.$formattedDatePrev,
				'balloon' => [
					'dateCurrent' => $formattedDateCurrent,
					'datePrev' => $formattedDatePrev,
				]
			];
			if (isset($currentPeriod['items'][$i]))
			{
				$result['dataProvider'][$i]['value_1'] = $currentPeriod['items'][$i]['value'];
				$result['dataProvider'][$i]['targetUrl_1'] = $currentPeriod['items'][$i]['targetUrl'];
				$result['dataProvider'][$i]['balloon']['amountCurrent'] = $currentPeriod['items'][$i]['value'];
				$result['dataProvider'][$i]['balloon']['amountCurrentFormatted'] = \CCrmCurrency::MoneyToString($currentPeriod['items'][$i]['value'], $baseCurrency);

				$totalAmountCurrent += $currentPeriod['items'][$i]['value'];
			}
			if (isset($previousPeriod['items'][$i]))
			{
				$result['dataProvider'][$i]['value_2'] = $previousPeriod['items'][$i]['value'];
				$result['dataProvider'][$i]['targetUrl_2'] = $previousPeriod['items'][$i]['targetUrl'];
				$result['dataProvider'][$i]['balloon']['amountPrev'] = $previousPeriod['items'][$i]['value'];
				$result['dataProvider'][$i]['balloon']['amountPrevFormatted'] = \CCrmCurrency::MoneyToString($previousPeriod['items'][$i]['value'], $baseCurrency);

				$totalAmountPrev += $previousPeriod['items'][$i]['value'];
			}
		}

		$totalAmountCurrentFormatted = \CCrmCurrency::MoneyToString($totalAmountCurrent, $baseCurrency);
		$totalAmountCurrentFormatted = str_replace("&nbsp;", " ", $totalAmountCurrentFormatted);
		$totalAmountPrevFormatted = \CCrmCurrency::MoneyToString($totalAmountPrev, $baseCurrency);
		$totalAmountPrevFormatted = str_replace("&nbsp;", " ", $totalAmountPrevFormatted);


		$graph = [
			"bullet" => "round",
			//"labelText" => "[[value]]",
			"title" => $currentPeriod['config']['reportTitle']." (".$totalAmountCurrentFormatted.")",
			"fillColors" => $currentPeriod['config']['reportColor'],
			"lineColor" => $currentPeriod['config']['reportColor'],
			"valueField" => 'value_1',
			"descriptionField" => 'label_1',
			"fillAlphas" => 0,
			"type" => "line",
			"balloonFunction" => "BX.Crm.Report.Dashboard.Content.SalesComparePeriods.renderBalloon",
			"balloon" => [
				"borderThickness" => 0,
			],
		];
		$result['graphs'][] = $graph;

		$graph = [
			"bullet" => "round",
			//"labelText" => "[[value]]",
			"title" => $previousPeriod['config']['reportTitle']." (".$totalAmountPrevFormatted.")",
			"fillColors" => $previousPeriod['config']['reportColor'],
			"lineColor" => $previousPeriod['config']['reportColor'],
			"valueField" => 'value_2',
			"descriptionField" => 'label_2',
			"fillAlphas" => 0,
			"balloonFunction" => "BX.Crm.Report.Dashboard.Content.SalesComparePeriods.renderBalloon",
			"balloon" => [
				"borderThickness" => 0,
			],
		];
		$result['graphs'][] = $graph;

		$result['categoryAxis']['autoGridCount'] = true;
		$result['categoryAxis']['minHorizontalGap'] = 0;
		$result['categoryAxis']['labelFrequency'] = ceil(count($result['dataProvider']) / 10);

		return $result;
	}
}