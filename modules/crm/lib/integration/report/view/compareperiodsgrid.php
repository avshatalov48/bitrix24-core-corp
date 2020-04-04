<?php

namespace Bitrix\Crm\Integration\Report\View;

use Bitrix\Crm\Integration\Report\Handler\SalesDynamics\BaseGraph;
use Bitrix\Main\Context;
use Bitrix\Main\Type\Date;
use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Grid;

class ComparePeriodsGrid extends Grid
{
	const VIEW_KEY = 'crm_analytics_compare_periods_grid';
	const USE_IN_VISUAL_CONSTRUCTOR = false;

	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:crm.report.compareperiods.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_GROUPED_REPORT_TYPE);
	}

	public function handlerFinallyBeforePassToView($dataFromReport)
	{
		$result = [];

		$currentPeriod = $dataFromReport[0];
		$previousPeriod = $dataFromReport[1];

		$maxPoints = max(count($currentPeriod['items']), count($previousPeriod['items']));

		$dateGrouping = $currentPeriod['config']['dateGrouping'];
		$dateFormat = $dateGrouping === BaseGraph::GROUP_MONTH ? "f Y": Context::getCurrent()->getCulture()->getLongDateFormat();

		if ($maxPoints == 0)
		{
			return $result;
		}

		for ($i = 0; $i < $maxPoints; $i++)
		{
			$dateCurrent = $currentPeriod['items'][$i]['groupBy'] ? new Date($currentPeriod['items'][$i]['groupBy'], BaseGraph::DATE_INDEX_FORMAT) : null;
			$datePrev = $previousPeriod['items'][$i]['groupBy'] ? new Date($previousPeriod['items'][$i]['groupBy'], BaseGraph::DATE_INDEX_FORMAT) : null;

			$formattedDateCurrent = $dateCurrent ? FormatDate($dateFormat, $dateCurrent) : "&mdash;";
			$formattedDatePrev = $datePrev ? FormatDate($dateFormat, $datePrev) : "&mdash;";
			$result[$i]['value'] = [
				'dateCurrent' => $dateCurrent,
				'dateCurrentFormatted' => $formattedDateCurrent,
				'datePrev' => $datePrev,
				'datePrevFormatted' => $formattedDatePrev,
				'amountCurrent' => $currentPeriod['items'][$i]['value'],
				'amountPrev' => $previousPeriod['items'][$i]['value']
			];
			$result[$i]['targetUrl'] = [
				'amountCurrent' => $currentPeriod['items'][$i]['targetUrl'],
				'amountPrev' => $previousPeriod['items'][$i]['targetUrl']
			];
		}

		return $result;
	}
}