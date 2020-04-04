<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

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
/** @var CBitrixComponent $component */

if ($arResult['LINE_NAME'])
{
	$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage("OL_STAT_TITLE", Array('#LINE_NAME#' => htmlspecialcharsbx($arResult['LINE_NAME']))));
}

ShowError($arResult["ERROR_TEXT"]);

if (!$arResult["ENABLE_EXPORT"])
{
	CBitrix24::initLicenseInfoPopupJS();
	?>
	<script type="text/javascript">
		function viOpenTrialPopup(dialogId)
		{
			B24.licenseInfoPopup.show(dialogId, "<?=CUtil::JSEscape($arResult["TRIAL_TEXT"]['TITLE'])?>", "<?=CUtil::JSEscape($arResult["TRIAL_TEXT"]['TEXT'])?>");
		}
	</script>
	<?
}

$isBitrix24Template = (SITE_TEMPLATE_ID == "bitrix24");
if($isBitrix24Template)
{
	$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
	$APPLICATION->SetPageProperty("BodyClass", "pagetitle-toolbar-field-view");
	$this->SetViewTarget("inside_pagetitle", 0);
	?><div class="pagetitle-container pagetitle-flexible-space"><?
}

$APPLICATION->IncludeComponent(
	"bitrix:main.ui.filter",
	"",
	array(
		"GRID_ID" => $arResult["GRID_ID"],
		"FILTER_ID" => $arResult["FILTER_ID"],
		"FILTER" => $arResult["FILTER"],
		"FILTER_PRESETS" => $arResult["FILTER_PRESETS"],
		"ENABLE_LIVE_SEARCH" => true,
		"ENABLE_LABEL" => true
	),
	$component,
	array()
);

?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<a class="webform-small-button webform-small-button-transparent <?=($arResult['ENABLE_EXPORT'] ? '' : 'btn-lock')?>" href="<?=$arResult['EXPORT_HREF']?>">
			<span class="webform-small-button-left"></span>
			<span class="webform-button-icon"></span>
			<span class="webform-small-button-text"><?=GetMessage("OL_STAT_EXCEL")?></span>
			<span class="webform-small-button-right"></span>
		</a>
	</div>
<?

if($isBitrix24Template)
{
	?></div><?
	$this->EndViewTarget();

	$isAdmin = CModule::IncludeModule('bitrix24') ? \CBitrix24::isPortalAdmin($USER->getId()) : $USER->IsAdmin();
	if($isAdmin)
	{
		echo Bitrix\Imopenlines\Ui\Helper::getStatisticStepper();
	}
}

$APPLICATION->IncludeComponent(
	"bitrix:main.ui.grid",
	"",
	array(
		"GRID_ID" => $arResult["GRID_ID"],
		"HEADERS" => $arResult["HEADERS"],
		"ROWS" => $arResult["ELEMENTS_ROWS"],
		"NAV_OBJECT" => $arResult["NAV_OBJECT"],
		"SORT" => $arResult["SORT"],
		"ALLOW_COLUMNS_SORT" => true,
		"ALLOW_SORT" => true,
		"ALLOW_PIN_HEADER" => true,
		"SHOW_PAGINATION" => true,
		"SHOW_PAGESIZE" => true,
		"SHOW_ROW_CHECKBOXES" => false,
        "SHOW_CHECK_ALL_CHECKBOXES" => false,
        "SHOW_SELECTED_COUNTER" => false,
		"PAGE_SIZES" => Array(
			array("NAME" => "20", "VALUE" => "20"),
			array("NAME" => "50", "VALUE" => "50"),
			array("NAME" => "100", "VALUE" => "100"),
		),
		"TOTAL_ROWS_COUNT" => $arResult["ROWS_COUNT"],
		"AJAX_MODE" => "Y",
		"AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_HISTORY" => "N",
		"AJAX_ID" => CAjax::GetComponentID('bitrix:imopenlines.statistics.detail', '.default', '')
	),
	$component, array("HIDE_ICONS" => "Y")
);

\Bitrix\Imopenlines\Ui\Helper::renderCustomSelectors($arResult['FILTER_ID'], $arResult['FILTER']);
?>