<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Bitrix\Main\UI\Extension::load("ui.tooltip");

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

if (empty($arResult['COMPANY']))
	echo GetMessage('CRM_DATA_EMPTY');
else
{
	foreach($arResult['COMPANY'] as $arCompany)
	{
		?>
		<div class="crm-company-element">
			<div class="crm-company-element-date"><?=FormatDate('x', MakeTimeStamp($arCompany['DATE_CREATE']), (time() + CTimeZone::GetOffset()))?></div>
			<div class="crm-company-element-title"><a href="<?=$arCompany['PATH_TO_COMPANY_SHOW']?>" title="<?=$arCompany['TITLE']?>" bx-tooltip-user-id="COMPANY_<?=$arCompany['~ID']?>" bx-tooltip-loader="<?=htmlspecialcharsbx('/bitrix/components/bitrix/crm.company.show/card.ajax.php')?>" bx-tooltip-classname="crm_balloon_company"><?=$arCompany['TITLE']?></a></div>
			<div class="crm-company-element-status"><?=GetMessage('CRM_COLUMN_COMPANY_TYPE')?>: <span><?=$arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']]?></span></div>
		</div>
		<?
	}
}
?>