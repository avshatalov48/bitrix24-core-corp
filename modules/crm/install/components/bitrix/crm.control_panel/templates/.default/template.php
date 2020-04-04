<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
} 

$APPLICATION->SetAdditionalCSS("/bitrix/js/crm/css/crm.css");

$isBitrix24 = SITE_TEMPLATE_ID === "bitrix24";
if ($isBitrix24)
{
	$this->SetViewTarget("above_pagetitle");
}

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.buttons",
	"",
	array(
		"ID" => $arResult["CRM_PANEL_MENU_CONTAINER_ID"],
		"ITEMS" => $arResult["ITEMS"],
	)
);

if ($isBitrix24)
{
	$this->EndViewTarget("sidebar");
}

if ($arResult["ENABLE_SEARCH"])
{
	$this->SetViewTarget("pagetitle", 10);
	?>
	<span id="<?=$arResult["CRM_PANEL_SEARCH_CONTAINER_ID"]?>" class="crm-search-block">
		<form class="crm-search" action="<?=htmlspecialcharsbx($arResult["SEARCH_PAGE_URL"])?>" method="get">
			<button type="submit" class="crm-search-btn"></button>
			<span class="crm-search-inp-wrap"><input
					id="<?=$arResult["CRM_PANEL_SEARCH_INPUT_ID"]?>"
					class="crm-search-inp"
					name="q"
					type="text"
					autocomplete="off"
					placeholder="<?=GetMessage("CRM_CONTROL_PANEL_SEARCH_PLACEHOLDER")?>"/></span>
			<input type="hidden" name="where" value="crm"><?
			$APPLICATION->IncludeComponent(
				"bitrix:search.title",
				"backend",
				array(
					"NUM_CATEGORIES" => 1,
					"CATEGORY_0_TITLE" => "CRM",
					"CATEGORY_0" => array(0 => "crm"),
					"USE_LANGUAGE_GUESS" => "N",
					"PAGE" => $arResult["PATH_TO_SEARCH_PAGE"],
					"CONTAINER_ID" => $arResult["CRM_PANEL_SEARCH_CONTAINER_ID"],
					"INPUT_ID" => $arResult["CRM_PANEL_SEARCH_INPUT_ID"],
					"SHOW_INPUT" => "N"
				),
				$component,
				array("HIDE_ICONS" => true)
			);
			?></form>
	</span>
	<?

	$this->EndViewTarget();
}