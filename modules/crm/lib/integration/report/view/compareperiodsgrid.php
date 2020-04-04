<?php

namespace Bitrix\Crm\Integration\Report\View;

use Bitrix\Report\VisualConstructor\Views\Component\Grid;

class ComparePeriodsGrid extends Grid
{
	const VIEW_KEY = 'crm_analytics_compare_periods_grid';
	const USE_IN_VISUAL_CONSTRUCTOR = false;

	public function handlerFinallyBeforePassToView($dataFromReport)
	{
		$result = parent::handlerFinallyBeforePassToView($dataFromReport);

		ksort($result['items']);
		return $result;
	}



}