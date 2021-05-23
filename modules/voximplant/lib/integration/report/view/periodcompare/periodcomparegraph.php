<?php

namespace Bitrix\Voximplant\Integration\Report\View\PeriodCompare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Voximplant\Integration\Report\CallType;
use Bitrix\Voximplant\Integration\Report\View\LinearGraphBase;

/**
 * Class PeriodCompareGraph
 * @package Bitrix\Voximplant\Integration\Report\View\PeriodCompare
 */
class PeriodCompareGraph extends LinearGraphBase
{
	public const VIEW_KEY = 'period_compare_graph';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * PeriodCompareGraph constructor.
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function __construct()
	{
		parent::__construct();
		Extension::load(["voximplant.report.periodcompare"]);
	}

	/**
	 * Prepares report data for insertion into view.
	 *
	 * @param array $reportData
	 *
	 * @return array
	 */
	public function handlerFinallyBeforePassToView($reportData)
	{
		$result = $this->getConfig();

		$pointCount = count($reportData);
		if ($pointCount === 0)
		{
			return $result;
		}

		$callTypeTitle = $this->getCallTypeTitleById($reportData[0]['callType']);

		for ($i = 0; $i < $pointCount; $i++)
		{
			$point = $reportData[$i];

			$column = [
				'groupingField' => $point['value']['CURRENT_DATE'] . '<br>' . $point['value']['PREVIOUS_DATE'],
			];

			$column['value_1'] = $point['value']['CURRENT_VALUE'];
			$column['targetUrl_1'] = $point['url']['CURRENT_VALUE'];
			$column['balloon']['count']['value_1'] = $point['value']['CURRENT_VALUE'];
			$column['balloon']['compare'] = $point['value']['DYNAMICS'];

			$column['value_2'] = $point['value']['PREVIOUS_VALUE'];
			$column['targetUrl_2'] = $point['url']['PREVIOUS_VALUE'];
			$column['balloon']['count']['value_2'] = $point['value']['PREVIOUS_VALUE'];

			$column['balloon']['callType'] = $callTypeTitle;

			$result['dataProvider'][$i] = $column;
		}

		$result['graphs'] = [
			$this->getGraph(1, Loc::getMessage('TELEPHONY_REPORT_GRAPH_PERIOD_COMPARE_CURRENT'), '#64b1e2', 'PeriodCompare'),
			$this->getGraph(2, Loc::getMessage('TELEPHONY_REPORT_GRAPH_PERIOD_COMPARE_PREVIOUS'), '#fda505', 'PeriodCompare'),
		];

		$result['categoryAxis']['labelFrequency'] = ceil(count($result['dataProvider']) / 10);

		return $result;
	}

	/**
	 * Returns a language phrase by call type.
	 *
	 * @param $id
	 *
	 * @return string
	 */
	protected function getCallTypeTitleById($id): string
	{
		switch ($id)
		{
			case CallType::INCOMING:
				return Loc::getMessage('TELEPHONY_REPORT_GRAPH_PERIOD_COMPARE_INCOMING');
			case CallType::OUTGOING:
				return Loc::getMessage('TELEPHONY_REPORT_GRAPH_PERIOD_COMPARE_OUTGOING');
			case CallType::MISSED:
				return Loc::getMessage('TELEPHONY_REPORT_GRAPH_PERIOD_COMPARE_MISSED');
			case CallType::CALLBACK:
				return Loc::getMessage('TELEPHONY_REPORT_GRAPH_PERIOD_COMPARE_CALLBACK');
			default:
				return Loc::getMessage('TELEPHONY_REPORT_GRAPH_PERIOD_COMPARE_ALL_CALLS');
		}
	}
}