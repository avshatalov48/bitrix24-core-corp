<?php

namespace Bitrix\Crm\Integration\Report\View\MyReports;

use Bitrix\Crm\Integration\Report\Dashboard\MyReports\InvoiceBoard;
use Bitrix\Crm\Widget\Layout\InvoiceWidget;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration\Report\View\WidgetPanel;

Loc::loadLanguageFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT."/components/bitrix/crm.invoice/templates/.default/widget.php");

class InvoiceReport extends WidgetPanel
{
	const VIEW_KEY = 'widget_invoice_report';

	public function getPanelGuid()
	{
		return InvoiceBoard::getPanelGuid();
	}

	public function getEntityTypes()
	{
		return [\CCrmOwnerType::InvoiceName];
	}

	public function getDefaultRows()
	{
		return InvoiceWidget::getDefaultRows([
			'isSupervisor' => $this->isSuperVisor()
		]);
	}

	public function getPathToDemoData()
	{
		return Application::getDocumentRoot().'/bitrix/components/bitrix/crm.invoice/templates/.default/widget';
	}

	public function getDemoTitle()
	{
		return InvoiceWidget::getDemoTitle();
	}

	public function getDemoContent()
	{
		return InvoiceWidget::getDemoContent();
	}
}