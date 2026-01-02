<?php

use Bitrix\Crm\ItemMiniCard\Builder\MiniCardHtmlBuilder;
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');

if (empty($arResult['CONTACT']))
	echo Loc::getMessage('CRM_DATA_EMPTY');
else
{
	foreach($arResult['CONTACT'] as $arContact)
	{
		?>
		<div class="crm-contact-element">
			<div class="crm-contact-element-date"><?=FormatDate('x', MakeTimeStamp($arContact['DATE_CREATE']), (time() + CTimeZone::GetOffset()))?></div>
			<div class="crm-contact-element-title">
				<?=
					(new MiniCardHtmlBuilder(CCrmOwnerType::Contact, (int)$arContact['~ID']))
						->setTitle($arContact['CONTACT_FORMATTED_NAME'] ?? '')
						->build()
				?>
			</div>
			<div class="crm-contact-element-status">
				<?= Loc::getMessage('CRM_COLUMN_CONTACT_TYPE') ?>: <span><?=$arResult['TYPE_LIST'][$arContact['TYPE_ID']]?></span></div>
		</div>
		<?php
	}
}
?>