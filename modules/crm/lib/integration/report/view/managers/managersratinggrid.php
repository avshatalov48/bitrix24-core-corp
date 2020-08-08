<?php

namespace Bitrix\Crm\Integration\Report\View\Managers;

use Bitrix\Report\VisualConstructor\Config\Common;
use Bitrix\Report\VisualConstructor\Views\Component\Base;

class ManagersRatingGrid extends Base
{
	const VIEW_KEY = 'CRM_MANAGERS_RATING_GRID';
	const MAX_RENDER_REPORT_COUNT = 15;
	const USE_IN_VISUAL_CONSTRUCTOR = false;

	public function __construct()
	{
		parent::__construct();

		$this->setDraggable(false);
		$this->setComponentName('bitrix:crm.report.managersrating.grid');
		$this->setPreviewImageUri('/bitrix/images/report/visualconstructor/preview/grid.svg');
		$this->setCompatibleDataType(Common::MULTIPLE_REPORT_TYPE);
	}

	public function handlerFinallyBeforePassToView($calculatedPerformedData)
	{
		$result = parent::handlerFinallyBeforePassToView($calculatedPerformedData);

		if (is_array($result['data']))
		{
			$result = $result['data'][0];
		}
		return $result;
	}
}