<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class CrmReportVcContentWidgetPanel extends CBitrixComponent
{
	public function executeComponent()
	{
		if(!Loader::includeModule("crm"))
		{
			ShowError("CRM module is not installed");
			return;
		}
		if(!Loader::includeModule("report"))
		{
			ShowError("Report module is not installed");
			return;
		}

		$this->arResult['WIDGET_PANEL_PARAMS'] = $this->arParams['~WIDGET_PANEL_PARAMS'];
		$this->includeComponentTemplate();
	}
}