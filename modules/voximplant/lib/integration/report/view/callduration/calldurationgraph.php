<?php

namespace Bitrix\Voximplant\Integration\Report\View\CallDuration;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Voximplant\Integration\Report\View\ClusteredGraphBase;

/**
 * Class CallDurationGraph
 * @package Bitrix\Voximplant\Integration\Report\View\CallDynamics
 */
class CallDurationGraph extends ClusteredGraphBase
{
	public const VIEW_KEY = 'call_duration_graph';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	protected const HOUR = 3600;
	protected const MINUTE = 60;
	protected const FIFTEEN_MINUTES = 900;

	/**
	 * CallDurationGraph constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setJsClassName('BX.Voximplant.Report.Dashboard.Content.CallDuration');
		Extension::load(['voximplant.report.callduration']);
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

		$pointCount = count($reportData);
		if ($pointCount === 0)
		{
			return $result;
		}

		$maxIncomingDuration = $maxOutgoingDuration = 0;
		foreach ($reportData as $point)
		{
			if ($point['value']['INCOMING_DURATION'] > $maxIncomingDuration)
			{
				$maxIncomingDuration = $point['value']['INCOMING_DURATION'];
			}

			if ($point['value']['OUTGOING_DURATION'] > $maxOutgoingDuration)
			{
				$maxOutgoingDuration = $point['value']['OUTGOING_DURATION'];
			}
		}

		$maxDurationInHours = ($maxIncomingDuration > $maxOutgoingDuration ?
			floor($maxIncomingDuration / 3600) :
			floor($maxOutgoingDuration / 3600));

		foreach ($reportData as $point)
		{
			$column['groupingField'] = $point['value']['USER_NAME'];

			if ($point['value']['INCOMING_DURATION'] == null && $point['value']['OUTGOING_DURATION'] == null)
			{
				continue;
			}

			if ($maxDurationInHours >= 1)
			{
				if ($point['value']['INCOMING_DURATION'] > 0)
				{
					$incomingDurationInHours = round($point['value']['INCOMING_DURATION'] / 3600, 1);
					$column['value_1'] = ($incomingDurationInHours < 0.1 ? 0.1 : $incomingDurationInHours);
					$column['targetUrl_1'] = $point['url']['INCOMING_DURATION'];
					$column['balloon']['count']['value_1'] = $point['value']['INCOMING_DURATION_FORMATTED'];
					$column['balloon']['compare']['value_1'] = $point['value']['INCOMING_DYNAMICS'];
				}
				else
				{
					$column['value_1'] = 0.1;
					$column['balloon']['count']['value_1'] = 0;
					$column['balloon']['compare']['value_1'] = $point['value']['INCOMING_DYNAMICS'];
				}

				if ($point['value']['OUTGOING_DURATION'] > 0)
				{
					$outgoingDurationInHours = round($point['value']['OUTGOING_DURATION'] / 3600, 1);
					$column['value_2'] = ($outgoingDurationInHours < 0.1 ? 0.1 : $outgoingDurationInHours);
					$column['targetUrl_2'] = $point['url']['OUTGOING_DURATION'];
					$column['balloon']['count']['value_2'] = $point['value']['OUTGOING_DURATION_FORMATTED'];
					$column['balloon']['compare']['value_2'] = $point['value']['OUTGOING_DYNAMICS'];
				}
				else
				{
					$column['value_2'] = 0.1;
					$column['balloon']['count']['value_2'] = 0;
					$column['balloon']['compare']['value_2'] = $point['value']['OUTGOING_DYNAMICS'];
				}
			}
			else
			{
				$column['value_1'] = $point['value']['INCOMING_DURATION'] / 3600;
				$column['targetUrl_1'] = $point['url']['INCOMING_DURATION'];
				$column['balloon']['count']['value_1'] = $point['value']['INCOMING_DURATION_FORMATTED'];
				$column['balloon']['compare']['value_1'] = $point['value']['INCOMING_DYNAMICS'];

				$column['value_2'] = $point['value']['OUTGOING_DURATION'] / 3600;
				$column['targetUrl_2'] = $point['url']['OUTGOING_DURATION'];
				$column['balloon']['count']['value_2'] = $point['value']['OUTGOING_DURATION_FORMATTED'];
				$column['balloon']['compare']['value_2'] = $point['value']['OUTGOING_DYNAMICS'];
			}

			$result['data'][] = $column;
		}

		$result['series'] = [
			$this->getSeries('1', Loc::getMessage('TELEPHONY_REPORT_GRAPH_CALL_DURATION_INCOMING'), '#30d1cb'),
			$this->getSeries('2', Loc::getMessage('TELEPHONY_REPORT_GRAPH_CALL_DURATION_OUTGOING'), '#2fc6f5'),
		];

		if ($maxDurationInHours >= 1)
		{
			$result['yAxes'][0]['logarithmic'] = true;
			$result['yAxes'][0]['treatZeroAs'] = 0.1;
		}
		else
		{
			$result['yAxes'][0]['renderer'] = [
				'grid' => ['template' => ['disabled' => true]],
				'labels' => ['template' => ['disabled' => true]],
			];

			$result['yAxes'][0]['axisRanges'] = [
				[
					'value' => 0,
					'label' => ["text" => "{value}"]
				],
			];

			for ($yAxisValue = self::FIFTEEN_MINUTES; $yAxisValue < self::HOUR; $yAxisValue += self::FIFTEEN_MINUTES)
			{
				$result['yAxes'][0]['axisRanges'][] = [
					'value' => $yAxisValue / self::HOUR,
					'label' => [
						'text' => $yAxisValue / self::MINUTE .' '. Loc::getMessage('TELEPHONY_REPORT_GRAPH_CALL_DURATION_MIN')
					],
				];
			}

			$result['yAxes'][0]['axisRanges'][] = [
				'value' => self::HOUR,
				'label' => [
					'text' => 1 .' '. Loc::getMessage('TELEPHONY_REPORT_GRAPH_CALL_DURATION_HOUR')
				],
			];
		}

		return $result;
	}
}