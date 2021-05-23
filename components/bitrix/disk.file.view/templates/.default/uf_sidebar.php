<?php
use \Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

Loc::loadMessages(__DIR__ . '/template.php');
?>
<div class="disk-detail-sidebar-user-custom-field" id="disk-uf-sidebar-values">
	<? foreach($arResult["USER_FIELDS"] as $arUserField) {?>
		<div class="disk-detail-sidebar-user-custom-field-label"><?php echo htmlspecialcharsbx($arUserField["EDIT_FORM_LABEL"])?>:</div>
		<div class="disk-detail-sidebar-user-custom-field-value">
			<? $APPLICATION->includeComponent(
				"bitrix:system.field.view",
				$arUserField["USER_TYPE"]["USER_TYPE_ID"],
				array("arUserField" => $arUserField),
				null,
				array("HIDE_ICONS"=>"Y")
			); ?>
		</div>
	<? }?>
</div>
