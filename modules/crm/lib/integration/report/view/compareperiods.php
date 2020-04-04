<?php

namespace Bitrix\Crm\Integration\Report\View;


use Bitrix\Report\VisualConstructor\Views\JsComponent\AmChart\LinearGraph;

class ComparePeriods extends LinearGraph
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
		$result = parent::handlerFinallyBeforePassToView($dataFromReport);

		if (!empty($dataFromReport[0]['config']['dataDateFormat']))
		{
			$result['dataDateFormat'] = $dataFromReport[0]['config']['dataDateFormat'];

			if ($dataFromReport[0]['config']['dataDateFormat'] === 'DD')
			{
				$result['categoryAxis']['dateFormats'] = [
					['period' => 'DD', 'format' => 'DD'],
					['period' => 'MM', 'format' => ''],
				];
			}
			elseif ($dataFromReport[0]['config']['dataDateFormat'] === 'MM')
			{
				$result['categoryAxis']['minPeriod'] = 'MM';
				$result['categoryAxis']['dateFormats'] = [
					['period' => 'MM', 'format' => 'MMM'],
					['period' => 'YYYY', 'format' => ''],
				];
			}

		}


		if (!empty($dataFromReport[0]['config']['chartCursor']['categoryBalloonDateFormat']))
		{
			$result['chartCursor']['categoryBalloonDateFormat'] = $dataFromReport[0]['config']['chartCursor']['categoryBalloonDateFormat'];
		}


		if (!empty($dataFromReport[0]['config']['categoryAxis']['labelFrequency']))
		{
			$result['categoryAxis']['labelFrequency'] = $dataFromReport[0]['config']['categoryAxis']['labelFrequency'];
		}

		foreach ($result['graphs'] as $i => &$graph)
		{
			$graph['balloonText'] .=  ' ([[description]])';
		}

		return $result;
	}
}