<?php

namespace Bitrix\Voximplant\Integration\Report\View\AverageCallTime;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Voximplant\Integration\Report\View\ClusteredGraphBase;

/**
 * Class AverageCallTime
 * @package Bitrix\Voximplant\Integration\Report\View\AverageCallTime
 */
class AverageCallTimeGraph extends ClusteredGraphBase
{
	public const VIEW_KEY = 'avg_call_time_graph';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * AverageCallTimeGraph constructor.
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setDraggable(false);
		$this->setHeight(480);
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
		$this->setJsClassName('BX.Voximplant.Report.Dashboard.Content.AverageCallTime');
		Extension::load(['voximplant.report.averagecalltime']);
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

		foreach ($reportData as $point)
		{
			$column['groupingField'] = $point['value']['USER_NAME'];

			$averageCallTime = ((int)$point['value']['AVG_CALL_TIME']) / 60;
			//hack to display values less than 0.1 on a logarithmic graph
			$column['value_1'] = ($averageCallTime > 0.1) ? $averageCallTime : 0.11;

			$column['balloon']['count']['value_1'] = $point['value']['AVG_CALL_TIME_FORMATTED'];
			$column['balloon']['compare']['value_1'] = $point['value']['DYNAMICS'];
			$column['balloon']['compare']['value_1_formatted'] = $point['value']['DYNAMICS_FORMATTED'];

			$column['bullet'] = $point['value']['USER_ICON'];

			$result['data'][] = $column;
		}

		$result['series'] = [
			$this->getSeries('1', Loc::getMessage('TELEPHONY_REPORT_GRAPH_AVG_CALL_TIME'), '#f54819')
		];

		$result['series'][0]['heatRules'] = [[
			'target' => 'columns.template',
			'property' => 'fill',
			'min' => '#2FCEF6',
			'max' => '#00DEBA',
			'dataField' => 'valueY'
		]];

		$result['series'][0]['columns']['width'] = '50%';
		$result['series'][0]['columns']['maxWidth'] = 66;
		$result['series'][0]['columns']['strokeOpacity'] = 0;
		$result['series'][0]['columns']['column']['cornerRadiusTopLeft'] = 60;
		$result['series'][0]['columns']['column']['cornerRadiusTopRight'] = 60;
		$result['series'][0]['columns']['column']['cornerRadiusBottomLeft'] = 60;
		$result['series'][0]['columns']['column']['cornerRadiusBottomRight'] = 60;

		$result['yAxes'][0]['maxPrecision'] = 0;
		$result['yAxes'][0]['logarithmic'] = true;
		$result['yAxes'][0]['treatZeroAs'] = 0.1;

		unset($result['legend']);

		return $result;
	}
}