<?php
use Bitrix\Main\Localization\Loc;
$APPLICATION->SetPageProperty("BodyClass", "bx-disk-404-align-center");
\Bitrix\Main\UI\Extension::load([
	"ui.design-tokens",
	"ui.fonts.montserrat"
]);

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var \Bitrix\Disk\Internals\BaseComponent $component */
?>

<div class="bx-disk-grid">
	<div class="bx-disk-404-container">
		<div class="bx-disk-404-image">
			<img alt="" src="/bitrix/components/bitrix/disk.error.page/templates/.default/images/disk-error-page.svg">
		</div>
		<div class="bx-disk-404-title"><?= Loc::getMessage('DISK_ERROR_PAGE_TITLE_V2') ?></div>
		<div class="bx-disk-404-description">
			<p><?= Loc::getMessage('DISK_ERROR_PAGE_BASE_DESCRIPTION_V2') ?></p>
		</div>
	</div>
</div>
