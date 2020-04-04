<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return ;
}

if (!CModule::IncludeModule('report'))
{
	ShowError(GetMessage('REPORT_MODULE_NOT_INSTALLED'));
	return ;
}

if(!CCrmCurrency::EnsureReady())
{
	ShowError(CCrmCurrency::GetLastError());
}

$this->IncludeComponentTemplate();

?>
