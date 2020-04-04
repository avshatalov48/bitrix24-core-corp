<?php
namespace Bitrix\Crm\Integration\Report\View;

use Bitrix\Main\UI\Extension;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

/**
 * Class SalesPlan
 * @package Bitrix\Crm\Integration\Report\View
 */
class SalesPlan extends Base
{

	const VIEW_KEY = 'sales_plan_view';
	const MAX_RENDER_REPORT_COUNT = 15;
	const USE_IN_VISUAL_CONSTRUCTOR = false;

	public function __construct()
	{
		parent::__construct();
		Extension::load('crm.report.salestarget');
		$this->setDraggable(false);
		$this->setHeight('auto');
		$this->setJsClassName('BX.Crm.Report.Dashboard.Content.SalesTarget');
		$this->setComponentName('bitrix:crm.report.vc.widget.content.salestarget');
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