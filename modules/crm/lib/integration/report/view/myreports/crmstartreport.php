<?php

namespace Bitrix\Crm\Integration\Report\View\MyReports;

use Bitrix\Crm\Integration\Report\Dashboard\MyReports\CrmStartBoard;
use Bitrix\Crm\Integration\Report\View\WidgetPanel;
use Bitrix\Crm\Widget\Layout\StartCrmWidget;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT."/components/bitrix/crm.channel_tracker/templates/.default/widget.php");

class CrmStartReport extends WidgetPanel
{
	const VIEW_KEY = 'widget_crm_start_report';

	public function getPanelGuid()
	{
		return CrmStartBoard::getPanelGuid();
	}

	public function getEntityTypes()
	{
		return [
			\CCrmOwnerType::ActivityName,
			\CCrmOwnerType::LeadName,
			\CCrmOwnerType::DealName,
			\CCrmOwnerType::ContactName,
			\CCrmOwnerType::CompanyName,
			\CCrmOwnerType::InvoiceName,
		];
	}

	public function getDefaultRows()
	{
		return StartCrmWidget::getDefaultRows([
			'isSupervisor' => $this->isSuperVisor()
		]);
	}

	public function getPathToDemoData()
	{
		return Application::getDocumentRoot() . '/bitrix/components/bitrix/crm.channel_tracker/templates/.default/widget';
	}

	public function getDemoTitle()
	{
		return StartCrmWidget::getDemoTitle();
	}
}