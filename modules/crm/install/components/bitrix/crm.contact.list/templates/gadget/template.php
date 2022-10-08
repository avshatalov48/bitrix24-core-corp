<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

if (empty($arResult['CONTACT']))
	echo GetMessage('CRM_DATA_EMPTY');
else
{
	foreach($arResult['CONTACT'] as $arContact)
	{
		?>
		<div class="crm-contact-element">
			<div class="crm-contact-element-date"><?=FormatDate('x', MakeTimeStamp($arContact['DATE_CREATE']), (time() + CTimeZone::GetOffset()))?></div>
			<div class="crm-contact-element-title"><a href="<?=$arContact['PATH_TO_CONTACT_SHOW']?>" title="<?=$arContact['CONTACT_FORMATTED_NAME']?>" bx-tooltip-user-id="CONTACT_<?=$arContact['~ID']?>" bx-tooltip-loader="<?=htmlspecialcharsbx('/bitrix/components/bitrix/crm.contact.show/card.ajax.php')?>" bx-tooltip-classname="crm_balloon_contact"><?=$arContact['CONTACT_FORMATTED_NAME']?></a></div>
			<div class="crm-contact-element-status"><?=GetMessage('CRM_COLUMN_CONTACT_TYPE')?>: <span><?=$arResult['TYPE_LIST'][$arContact['TYPE_ID']]?></span></div>
		</div>
		<?
	}
}
?>