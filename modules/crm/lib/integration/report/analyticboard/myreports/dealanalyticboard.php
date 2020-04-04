<?php

namespace Bitrix\Crm\Integration\Report\AnalyticBoard\MyReports;

class DealAnalyticBoard extends Base
{
	public const REPORT_GUID = 'deal_widget';

	public function resetToDefault()
	{
		parent::resetToDefault();

		$additionalWidgetGuid = 'deal_category_widget';
		$this->resetWidgetRows($additionalWidgetGuid);
	}

}