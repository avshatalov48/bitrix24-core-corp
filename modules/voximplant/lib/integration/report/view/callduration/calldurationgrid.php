<?php

namespace Bitrix\Voximplant\Integration\Report\View\CallDuration;

use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

/**
 * Class CallDuration
 * @package Bitrix\Voximplant\Integration\Report\View\CallDuration
 */
class CallDurationGrid extends Base
{
	public const VIEW_KEY = 'call_duration_grid';
	public const MAX_RENDER_REPORT_COUNT = 1;
	public const USE_IN_VISUAL_CONSTRUCTOR = false;

	/**
	 * CallDurationGrid constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:voximplant.report.callduration.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}

	public function handlerFinallyBeforePassToView($reportData)
	{
		$result = [];
		foreach ($reportData as $point)
		{
			if ($point['value']['INCOMING_DURATION'] == null && $point['value']['OUTGOING_DURATION'] == null)
			{
				continue;
			}

			$result['data'][] = $point;
		}

		return $result;
	}
}