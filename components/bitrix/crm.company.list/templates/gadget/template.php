<?php

use Bitrix\Crm\ItemMiniCard\Builder\MiniCardHtmlBuilder;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

Bitrix\Main\UI\Extension::load(["ui.tooltip", "ui.fonts.opensans"]);

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
			<div class="crm-company-element-title">
				<?=
					(new MiniCardHtmlBuilder(CCrmOwnerType::Company, (int)$arCompany['~ID']))
						->setTitle($arCompany['TITLE'] ?? '')
						->build()
				?>
			</div>
			<div class="crm-company-element-status"><?=GetMessage('CRM_COLUMN_COMPANY_TYPE')?>: <span><?=$arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']]?></span></div>
		</div>
		<?
	}
}
?>