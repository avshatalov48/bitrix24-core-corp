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

?>
<div class="bx-disk-bizproc-section">
<?
$APPLICATION->IncludeComponent("bitrix:disk.bizproc.list", ".default", Array(
		"MODULE_ID"     => \Bitrix\Disk\Driver::INTERNAL_MODULE_ID,
		"STORAGE_ID"   => $arParams["STORAGE"]->getId(),
		"EDIT_URL"      => $arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT"],
		"CREATE_URL_BLANK" => CComponentEngine::MakePathFromTemplate($arResult["PATH_TO_DISK_BIZPROC_WORKFLOW_EDIT"], array("ID" => 0)),
		"SET_TITLE"     => "Y",
		"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"]
	),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>
</div>
