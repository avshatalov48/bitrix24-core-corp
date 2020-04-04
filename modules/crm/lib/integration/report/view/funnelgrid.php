<?php

namespace Bitrix\Crm\Integration\Report\View;

use Bitrix\Report\VisualConstructor\Fields\Valuable\Hidden;
use Bitrix\Report\VisualConstructor\Handler\BaseWidget;
use Bitrix\Report\VisualConstructor\Views\Component\Grid;

class FunnelGrid extends Grid
{
	const VIEW_KEY = 'CRM_FUNNEL_GRID';
	const CLASSIC_CALCULATE_MODE = '0';
	const CONVERSION_CALCULATE_MODE = '1';

	public function collectWidgetHandlerFormElements(BaseWidget $widgetHandler)
	{
		parent::collectWidgetHandlerFormElements($widgetHandler);

		$calculateModeField = new Hidden('calculateMode');
		$calculateModeField->setDefaultValue(self::CLASSIC_CALCULATE_MODE);
		$widgetHandler->addFormElement($calculateModeField);
	}
}