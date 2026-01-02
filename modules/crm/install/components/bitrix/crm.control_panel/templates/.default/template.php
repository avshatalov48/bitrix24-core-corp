<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
$APPLICATION->SetAdditionalCSS("/bitrix/js/crm/css/crm.css");
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');

$this->SetViewTarget('above_pagetitle');
$APPLICATION->IncludeComponent(
	"bitrix:main.interface.buttons",
	"",
	array(
		"ID" => $arResult["CRM_PANEL_MENU_CONTAINER_ID"],
		"ITEMS" => $arResult["ITEMS"],
		"THEME" => defined("AIR_SITE_TEMPLATE") ? "air" : null,
	)
);

$this->EndViewTarget();
Bitrix\Main\UI\Extension::load(['crm.restriction.bitrix24']);

print (\Bitrix\Crm\Tour\CopilotCallAssessment::getInstance())->build();
