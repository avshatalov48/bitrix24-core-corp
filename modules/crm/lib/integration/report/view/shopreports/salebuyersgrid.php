<?php

namespace Bitrix\Crm\Integration\Report\View\ShopReports;

use Bitrix\Crm\Integration\Report\Handler\SalesDynamics;
use Bitrix\Crm\Integration\Report\Handler\SalesDynamics\BaseGraph;
use Bitrix\Main\Context;
use Bitrix\Main\Type\Date;
use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

class SaleBuyersGrid extends Base
{
	const VIEW_KEY = 'crm_sale_buyers_grid';
	const MAX_RENDER_REPORT_COUNT = 15;
	const USE_IN_VISUAL_CONSTRUCTOR = false;

	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:crm.report.salebuyers.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}

	/**
	 * Handle all data prepared for this view.
	 *
	 * @param array $dataFromReport Calculated data from report handler.
	 * @return array
	 */
	public function handlerFinallyBeforePassToView($dataFromReport)
	{
		return $dataFromReport[0];
	}
}