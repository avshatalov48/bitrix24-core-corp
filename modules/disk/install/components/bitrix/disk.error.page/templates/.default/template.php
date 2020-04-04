<?php
use Bitrix\Main\Localization\Loc;

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
		<div class="bx-disk-404-image"><img src="/bitrix/components/bitrix/disk.error.page/templates/.default/images/404.png"></div>
		<div class="bx-disk-404-title"><?= Loc::getMessage('DISK_ERROR_PAGE_TITLE') ?></div>
		<div class="bx-disk-404-description">
			<p><?= Loc::getMessage('DISK_ERROR_PAGE_BASE_DESCRIPTION') ?></p>
			<p><?= Loc::getMessage('DISK_ERROR_PAGE_BASE_SOLUTION_1') ?></p>
			<p><?= Loc::getMessage('DISK_ERROR_PAGE_BASE_SOLUTION_2') ?></p>
		</div>
	</div>
</div>