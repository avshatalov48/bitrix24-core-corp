<?php

namespace Bitrix\Crm\Integration\Report\View;


use Bitrix\Report\VisualConstructor\Views\Component\Base;

class TrafficGrid extends Base
{
	const VIEW_KEY = 'traffic_grid';
	const MAX_RENDER_REPORT_COUNT = 15;
	const USE_IN_VISUAL_CONSTRUCTOR = false;

	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:crm.report.vc.widget.content.chart');
		$this->setComponentTemplateName('grid');
		$this->addComponentParameters('IS_GRID', true);
		$this->addComponentParameters('IS_TRAFFIC', true);
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
	}

	/**
	 * Handle all data prepared for this view.
	 *
	 * @param array $dataFromReport Calculated data from report handler.
	 * @return array
	 */
	public function handlerFinallyBeforePassToView($dataFromReport)
	{
		return $dataFromReport;
	}
}