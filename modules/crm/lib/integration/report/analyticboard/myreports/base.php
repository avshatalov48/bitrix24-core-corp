<?php

namespace Bitrix\Crm\Integration\Report\AnalyticBoard\MyReports;

use Bitrix\Report\VisualConstructor\AnalyticBoard;

abstract class Base extends AnalyticBoard
{
	const REPORT_GUID = "";

	/**
	 * Reset board to default
	 */
	public function resetToDefault()
	{
		parent::resetToDefault();
		$this->resetWidgetRows(static::REPORT_GUID);
	}

	public function resetWidgetRows($widgetId)
	{
		$options = \CUserOptions::GetOption('crm.widget_panel', $widgetId, array());
		unset($options['rows']);
		\CUserOptions::SetOption('crm.widget_panel', $widgetId, $options);
	}
}