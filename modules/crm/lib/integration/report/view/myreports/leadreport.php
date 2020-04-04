<?php

namespace Bitrix\Crm\Integration\Report\View\MyReports;

use Bitrix\Crm\Integration\Report\Dashboard\MyReports\LeadBoard;
use Bitrix\Crm\Widget\Layout\LeadWidget;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration\Report\View\WidgetPanel;

Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT."/components/bitrix/crm.lead/templates/.default/widget.php");

class LeadReport extends WidgetPanel
{
	const VIEW_KEY = 'widget_lead_report';

	public function getPanelGuid()
	{
		return LeadBoard::getPanelGuid();
	}

	public function getEntityTypes()
	{
		return [\CCrmOwnerType::LeadName];
	}

	public function getDefaultRows()
	{
		return LeadWidget::getDefaultRows([
			'isSupervisor' => $this->isSuperVisor()
		]);
	}

	public function getPathToDemoData()
	{
		return $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/templates/.default/widget';
	}

	public function getDemoTitle()
	{
		return LeadWidget::getDemoTitle();
	}

	public function getDemoContent()
	{
		return LeadWidget::getDemoContent();
	}
}