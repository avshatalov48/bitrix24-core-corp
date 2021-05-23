<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

use Bitrix\Main\Localization\Loc;

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

$storage = \Bitrix\Disk\Driver::getInstance()->getStorageByGroupId($arResult['VARIABLES']['group_id']);
$arResult['VARIABLES']['STORAGE'] = $storage;
$arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT"] = CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_GROUP_DISK_BIZPROC_WORKFLOW_EDIT'], array('group_id' => $arResult['VARIABLES']['group_id']));
$arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_ADMIN"] = CComponentEngine::MakePathFromTemplate($arResult['PATH_TO_GROUP_DISK_BIZPROC_WORKFLOW_ADMIN'], array('group_id' => $arResult['VARIABLES']['group_id']));
$arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT_TOOLBAR"] = CComponentEngine::MakePathFromTemplate($arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT"], array("ID" => 0));
?>
<div class="bx-disk-bizproc-section">
<?
$APPLICATION->IncludeComponent(
	"bitrix:disk.bizproc.edit",
	"",
	Array(
		"MODULE_ID"          => \Bitrix\Disk\Driver::INTERNAL_MODULE_ID,
		"STORAGE_ID"      => $arResult['VARIABLES']['STORAGE']->getId(),
		"ID"                 => $arResult['VARIABLES']['ID'],
		"EDIT_PAGE_TEMPLATE" => $arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT"],
		"LIST_PAGE_URL"      => $arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_ADMIN"],
		"SHOW_TOOLBAR"       => "Y",
		"SET_TITLE"          => "Y",
		"HIDE_TAB_PERMISSION" => "Y"
	)
);
?>
</div>