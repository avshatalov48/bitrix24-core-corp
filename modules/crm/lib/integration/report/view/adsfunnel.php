<?php

namespace Bitrix\Crm\Integration\Report\View;


use Bitrix\Report\VisualConstructor\Views\Component\Base;

class AdsFunnel extends Base
{
	const VIEW_KEY = 'ads_funnel';
	const MAX_RENDER_REPORT_COUNT = 15;
	const USE_IN_VISUAL_CONSTRUCTOR = false;

	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:crm.report.vc.widget.content.chart');
		$this->addComponentParameters('IS_GRID', false);
		$this->addComponentParameters('IS_TRAFFIC', false);
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/graph.svg');
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