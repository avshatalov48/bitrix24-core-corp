<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'salescenter.component.store-settings',
	'ui.forms',
	'ui.buttons',
	'ui.hint',
]);

Localization\Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Localization\Loc::getMessage('SC_COMPANY_CONTACTS_YOUR_CONTACTS'));

$this->setViewTarget("inside_pagetitle_below", 100); ?>
	<span class="salescenter-company-contacts-header-link" onclick="BX.Salescenter.Manager.openHowToWork(event);"><?=Localization\Loc::getMessage('SC_COMPANY_CONTACTS_HOW_TO_WORK')?><span>
<? $this->endViewTarget();?>
<?
$APPLICATION->SetPageProperty(
	"BodyClass",
	$APPLICATION->GetPageProperty("BodyClass") . " no-paddings no-background no-hidden"
);
?>
<div id="salescenter-companycontacts-root"></div>
<script>
	BX.ready(function() {
		var options = <?=CUtil::PhpToJSObject($arResult)?>;
		new BX.Salescenter.Component.StoreSettings('salescenter-companycontacts-root', options);
	})
</script>