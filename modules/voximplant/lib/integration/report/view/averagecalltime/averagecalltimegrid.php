<?php

namespace Bitrix\Voximplant\Integration\Report\View\AverageCallTime;

use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

/**
 * Class AverageCallTime
 * @package Bitrix\Voximplant\Integration\Report\View\AverageCallTime
 */
class AverageCallTimeGrid extends Base
{
	public const VIEW_KEY = 'avg_call_time_grid';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * LostCallsGrid constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:voximplant.report.averagecalltime.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}
}