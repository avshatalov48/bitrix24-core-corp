<?php

/** @var $this CBitrixComponentTemplate */
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
/** @var BaseComponent $component */

use Bitrix\Disk\Document\OnlyOffice\Editor\ConfigBuilder;
use Bitrix\Disk\Internals\BaseComponent;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
Loc::loadLanguageFile(__DIR__ . '/template.php');

/** @var array $arResult */
Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'disk',
	'main.loader',
]);

$containerId = 'not-found-file-'.$this->randString();

$headerText = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_MODE_VIEW');
if ($arResult['EDITOR']['MODE'] === ConfigBuilder::MODE_EDIT)
{
	$headerText = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_HEADER_MODE_EDIT');
}


$errorTitle = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CLOUD_ERROR_COMMON_TITLE');
$errorDescription = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CLOUD_ERROR_COMMON_DESCR', ['#LIMIT#' => $arResult['CLOUD_ERROR']['LIMIT']['LIMIT_VALUE']]);
$isRestriction = !empty($arResult['CLOUD_ERROR']['LIMIT']['RESTRICTION']);
$isDemoEnd = !empty($arResult['CLOUD_ERROR']['DEMO']['END']);
if ($isRestriction)
{
	$errorTitle = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CLOUD_ERROR_RESTRICTION_TITLE_1', ['#LIMIT#' => $arResult['CLOUD_ERROR']['LIMIT']['LIMIT_VALUE']]);
	$errorDescription = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CLOUD_ERROR_RESTRICTION_DESCR_1', ['#LIMIT#' => $arResult['CLOUD_ERROR']['LIMIT']['LIMIT_VALUE']]);
}
if ($isDemoEnd)
{
	$errorTitle = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CLOUD_ERROR_DEMO_RESTRICTION_TITLE');
	$errorDescription = Loc::getMessage('DISK_FILE_EDITOR_ONLYOFFICE_CLOUD_ERROR_DEMO_RESTRICTION_DESCR');
}
?>

<?php include __DIR__ . '/base-info-skeleton.php' ?>

<script>
	<?='BX.message(' . \CUtil::PhpToJSObject(Loc::loadLanguageFile(__DIR__ . '/template.php')) . ');'?>

	(new BX.Disk.Editor.CustomErrorControl).showCommonWarning({
		fileName: "<?= $isRestriction ? '' : \CUtil::JSEscape($arResult['OBJECT']['NAME']) ?>",
		container: document.querySelector('[data-id="<?= $containerId ?>"]'),
		targetNode: document.querySelector('[data-id="<?= $containerId ?>-base"]'),
		title: "<?=\CUtil::JSEscape($errorTitle) ?>",
		description: "<?=\CUtil::JSEscape($errorDescription) ?>",
		linkToDownload: "<?= $isRestriction ? '' : $arResult['LINK_TO_DOWNLOAD'] ?>",
	});

	<?php
	if (!empty($arResult['CLOUD_ERROR']['ERRORS']))
	{
		?>console.error(<?= Json::encode($arResult['CLOUD_ERROR']['ERRORS']) ?>)<?
	}
	?>
</script>
