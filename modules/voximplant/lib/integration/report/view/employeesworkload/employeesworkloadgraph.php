<?php

namespace Bitrix\Voximplant\Integration\Report\View\EmployeesWorkload;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Voximplant\Integration\Report\View\ClusteredGraphBase;

/**
 * Class EmployeesWorkloadGraph
 * @package Bitrix\Voximplant\Integration\Report\View\CallDynamics
 */
class EmployeesWorkloadGraph extends ClusteredGraphBase
{
	public const VIEW_KEY = 'employees_workload_graph';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * EmployeesWorkloadGraph constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setJsClassName('BX.Voximplant.Report.Dashboard.Content.EmployeesWorkload');
		Extension::load(["voximplant.report.employeesworkload"]);
	}

	/**
	 * Prepares report data before insertion into view.
	 *
	 * @param array $reportData
	 *
	 * @return array
	 */
	public function handlerFinallyBeforePassToView($reportData)
	{
		$result = $this->getConfig();

		$result['yAxes'][0]['logarithmic'] = true;
		$result['yAxes'][0]['treatZeroAs'] = 0.1;

		$pointCount = count($reportData);
		if ($pointCount === 0)
		{
			return $result;
		}

		foreach ($reportData as $point)
		{
			$column['groupingField'] = $point['value']['USER_NAME'];

			if ($point['value']['INCOMING'] > 0)
			{
				$column['value_1'] = $point['value']['INCOMING'];
				$column['targetUrl_1'] = $point['url']['INCOMING'];
				$column['balloon']['count']['value_1'] = $point['value']['INCOMING'];
				$column['balloon']['compare']['value_1'] = $point['value']['INCOMING_COMPARE'];
			}
			else
			{
				//hack to enable 0 value on logarithmic chart
				$column['value_1'] = 0.1;
				$column['balloon']['count']['value_1'] = 0;
				$column['balloon']['compare']['value_1'] = $point['value']['INCOMING_COMPARE'];
			}

			if ($point['value']['OUTGOING'] > 0)
			{
				$column['value_2'] = $point['value']['OUTGOING'];
				$column['targetUrl_2'] = $point['url']['OUTGOING'];
				$column['balloon']['count']['value_2'] = $point['value']['OUTGOING'];
				$column['balloon']['compare']['value_2'] = $point['value']['OUTGOING_COMPARE'];
			}
			else
			{
				$column['value_2'] = 0.1;
				$column['balloon']['count']['value_2'] = 0;
				$column['balloon']['compare']['value_2'] = $point['value']['OUTGOING_COMPARE'];
			}

			if ($point['value']['MISSED'] > 0)
			{
				$column['value_3'] = $point['value']['MISSED'];
				$column['targetUrl_3'] = $point['url']['MISSED'];
				$column['balloon']['count']['value_3'] = $point['value']['MISSED'];
				$column['balloon']['compare']['value_3'] = $point['value']['MISSED_COMPARE'];
			}
			else
			{
				$column['value_3'] = 0.1;
				$column['balloon']['count']['value_3'] = 0;
				$column['balloon']['compare']['value_3'] = $point['value']['MISSED_COMPARE'];
			}

			$result['data'][] = $column;
		}

		$result['series'] = [
			$this->getSeries('1', Loc::getMessage('TELEPHONY_REPORT_GRAPH_EMPLOYEES_WORKLOAD_INCOMING'), '#30d1cb'),
			$this->getSeries('2', Loc::getMessage('TELEPHONY_REPORT_GRAPH_EMPLOYEES_WORKLOAD_OUTGOING'), '#2fc6f5'),
			$this->getSeries('3', Loc::getMessage('TELEPHONY_REPORT_GRAPH_EMPLOYEES_WORKLOAD_MISSED'), '#f54819')
		];

		return $result;
	}
}