<?php

namespace Bitrix\Voximplant\Integration\Report\View\CallActivity;

use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Voximplant\Integration\Report\View\ActivityGraphBase;

/**
 * Class CallActivityGraph
 * @package Bitrix\Voximplant\Integration\Report\View\CallDynamics
 */
class CallActivityGraph extends ActivityGraphBase
{
	public const VIEW_KEY = 'call_activity_graph';

	/**
	 * CallActivityGraph constructor.
	 * @throws LoaderException
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setJsClassName('BX.Voximplant.Report.Dashboard.Content.CallActivity');
		Extension::load(['voximplant.report.widget.activity']);
		Extension::load(['voximplant.report.callactivity']);
	}

	public function handlerFinallyBeforePassToView($dataFromReport)
	{
		if (!$dataFromReport['items'])
		{
			return parent::handlerFinallyBeforePassToView($dataFromReport);
		}

		foreach ($dataFromReport['items'] as $index => $row)
		{
			$dataFromReport['items'][$index]['value'] = [
				$row['incoming'],
				$row['missed'],
			];

			unset(
				$dataFromReport['items'][$index]['incoming'],
				$dataFromReport['items'][$index]['missed']
			);
		}

		$report = parent::handlerFinallyBeforePassToView($dataFromReport);

		$report['config']['tooltips'] = [
			[
				'title' => Loc::getMessage('TELEPHONY_REPORT_GRAPH_CALL_ACTIVITY_INCOMING'),
				'color' => '#2fc6f5'
			],
			[
				'title' => Loc::getMessage('TELEPHONY_REPORT_GRAPH_CALL_ACTIVITY_MISSED'),
				'color' => '#f44818'
			]
		];

		return $report;
	}
}