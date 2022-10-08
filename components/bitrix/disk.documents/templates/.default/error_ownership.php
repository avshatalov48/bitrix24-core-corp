<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @var CBitrixComponent $component */
/** @var CBitrixComponentTemplate $this */
use \Bitrix\Main\Localization\Loc;

$APPLICATION->setTitle(Loc::getMessage('DISK_DOCUMENTS_PAGE_TITLE'));
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'disk-documents-error--modifier');
\Bitrix\Main\UI\Extension::load('ui.fonts.opensans');
?>
<div class="disk-documents-error-wrap">
	<div class="disk-documents-error">
		<div class="disk-documents-error-icon"></div>
		<div class="disk-documents-error-title"><?=GetMessage("DISK_DOCUMENTS_ERROR_1")?></div>
		<div class="disk-documents-error-subtitle"><?=GetMessage("DISK_DOCUMENTS_ERROR_2")?></div>
	</div>
</div>
<?
