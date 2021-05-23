<?php

namespace Bitrix\Voximplant\Integration\Report\View\CallActivity;

use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

/**
 * Class CallActivity
 * @package Bitrix\Voximplant\Integration\Report\View\CallActivity
 */
class CallActivityGrid extends Base
{
	public const VIEW_KEY = 'call_activity_grid';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * CallActivityGrid constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:voximplant.report.callactivity.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}
}