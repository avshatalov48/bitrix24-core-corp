<?php

namespace Bitrix\Voximplant\Integration\Report\View\PeriodCompare;

use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

/**
 * Class PeriodCompareGrid
 * @package Bitrix\Voximplant\Integration\Report\View\GeneralAnalysis
 */
class PeriodCompareGrid extends Base
{
	public const VIEW_KEY = 'period_compare_grid';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * PeriodCompareGrid constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:voximplant.report.periodcompare.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}
}