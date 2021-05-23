<?php

namespace Bitrix\Voximplant\Integration\Report\View\CallDynamics;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Voximplant\Integration\Report\View\LinearGraphBase;

/**
 * Class CallDynamicsGraph
 * @package Bitrix\Voximplant\Integration\Report\View\GeneralAnalysis
 */
class CallDynamicsGraph extends LinearGraphBase
{
	public const VIEW_KEY = 'call_dynamics_graph';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * CallDynamicsGraph constructor.
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function __construct()
	{
		parent::__construct();
		Extension::load(["voximplant.report.calldynamics"]);
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
				'groupingField' => $point['value']['DATE'],
			];

			$column['value_1'] = $point['value']['INCOMING'];
			$column['targetUrl_1'] = $point['url']['INCOMING'];
			$column['balloon']['count']['value_1'] = $point['value']['INCOMING'];
			$column['balloon']['compare']['value_1'] = $point['value']['INCOMING_COMPARE'];

			$column['value_2'] = $point['value']['OUTGOING'];
			$column['targetUrl_2'] = $point['url']['OUTGOING'];
			$column['balloon']['count']['value_2'] = $point['value']['OUTGOING'];
			$column['balloon']['compare']['value_2'] = $point['value']['OUTGOING_COMPARE'];

			$column['value_3'] = $point['value']['MISSED'];
			$column['targetUrl_3'] = $point['url']['MISSED'];
			$column['balloon']['count']['value_3'] = $point['value']['MISSED'];
			$column['balloon']['compare']['value_3'] = $point['value']['MISSED_COMPARE'];

			$column['value_4'] = $point['value']['CALLBACK'];
			$column['targetUrl_4'] = $point['url']['CALLBACK'];
			$column['balloon']['count']['value_4'] = $point['value']['CALLBACK'];
			$column['balloon']['compare']['value_4'] = $point['value']['CALLBACK_COMPARE'];

			$result['dataProvider'][$i] = $column;
		}

		$result['graphs'] = [
			$this->getGraph(1, Loc::getMessage('TELEPHONY_REPORT_GRAPH_CALL_DYNAMICS_INCOMING'), '#96b833', 'CallDynamics'),
			$this->getGraph(2, Loc::getMessage('TELEPHONY_REPORT_GRAPH_CALL_DYNAMICS_OUTGOING'), '#64b1e2', 'CallDynamics'),
			$this->getGraph(3, Loc::getMessage('TELEPHONY_REPORT_GRAPH_CALL_DYNAMICS_MISSED'), '#f54819', 'CallDynamics'),
			$this->getGraph(4, Loc::getMessage('TELEPHONY_REPORT_GRAPH_CALL_DYNAMICS_CALLBACK'), '#fda505', 'CallDynamics'),
		];

		$result['categoryAxis']['labelFrequency'] = ceil(count($result['dataProvider']) / 10);

		return $result;
	}
}