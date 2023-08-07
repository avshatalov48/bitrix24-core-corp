<?php

namespace Bitrix\Voximplant\Integration\Report\View\LostCalls;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Voximplant\Integration\Report\CallType;
use Bitrix\Voximplant\Integration\Report\View\LinearGraphBase;

/**
 * Class LostCallsGraph
 * @package Bitrix\Voximplant\Integration\Report\View\LostCalls
 */
class LostCallsGraph extends LinearGraphBase
{
	public const VIEW_KEY = 'lost_calls_graph';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * LostCallsGraph constructor.
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function __construct()
	{
		parent::__construct();
		Extension::load(["voximplant.report.lostcalls"]);
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

		for ($i = 0; $i < $pointCount; $i++)
		{
			$point = $reportData[$i];

			$column = [
				'groupingField' => $point['value']['DATE_FORMATTED'],
			];

			$column['value_1'] = $point['value']['LOST_CALLS_COUNT'];
			$column['targetUrl_1'] = $point['url']['LOST_CALLS_COUNT'];
			$column['balloon']['count']['value_1'] = $point['value']['LOST_CALLS_COUNT'];
			$column['balloon']['compare']['value_1'] = $point['value']['DYNAMICS'];

			$result['dataProvider'][$i] = $column;
		}

		$result['graphs'][] = $this->getGraph(1, '', '#f54819', 'LostCalls');

		$result['categoryAxis']['labelFrequency'] = ceil(count($result['dataProvider']) / 10);
		$result['legend'] = null;

		return $result;
	}
}