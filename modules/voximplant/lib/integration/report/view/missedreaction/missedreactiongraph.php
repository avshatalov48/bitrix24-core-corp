<?php

namespace Bitrix\Voximplant\Integration\Report\View\MissedReaction;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Voximplant\Integration\Report\View\ClusteredGraphBase;

/**
 * Class MissedReaction
 * @package Bitrix\Voximplant\Integration\Report\View\MissedReaction
 */
class MissedReactionGraph extends ClusteredGraphBase
{
	public const VIEW_KEY = 'missed_reaction_graph';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * MissedReactionGraph constructor.
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setDraggable(false);
		$this->setHeight(480);
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
		$this->setJsClassName('BX.Voximplant.Report.Dashboard.Content.MissedReaction');
		Extension::load(['voximplant.report.missedreaction']);
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

			$column['value_1'] = $point['value']['MISSED'];
			$column['targetUrl_1'] = $point['url']['MISSED'];

			$column['balloon']['count']['value_1'] = $point['value']['MISSED'];
			$column['balloon']['count']['value_2'] = $point['value']['UNANSWERED'];
			$column['balloon']['count']['value_3'] = $point['value']['AVG_RESPONSE_TIME_FORMATTED'];
			$column['balloon']['compare']['value_3'] = $point['value']['DYNAMICS'];
			$column['balloon']['compare']['value_3_formatted'] = $point['value']['DYNAMICS_FORMATTED'];

			$column['bullet'] = $point['value']['USER_ICON'];

			$result['data'][] = $column;
		}

		$result['series'] = [
			$this->getSeries('1', Loc::getMessage('TELEPHONY_REPORT_GRAPH_EMPLOYEES_WORKLOAD_MISSED'), '#f54819')
		];

		$result['series'][0]['heatRules'] = [[
			'target' => 'columns.template',
			'property' => 'fill',
			'min' => '#fda505',
			'max' => '#f44818',
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

		unset($result['legend']);

		return $result;
	}
}