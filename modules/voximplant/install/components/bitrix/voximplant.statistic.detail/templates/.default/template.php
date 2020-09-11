<?
/**
 * @var array $arResult
 * @var array $arParams
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

CJSCore::Init([
	'main.polyfill.promise',
	'voximplant.common',
	'voximplant_transcript',
	'crm_activity_planner',
	'player',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.progressbar',
	'ui.notification'
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/components/bitrix/voximplant.statistic.detail/player/skins/audio/audio.css");

ShowError($arResult["ERROR_TEXT"]);
if (!$arResult["ENABLE_EXPORT"])
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());
}

$isBitrix24Template = (SITE_TEMPLATE_ID == "bitrix24");
if($isBitrix24Template)
{
	$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
	$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."pagetitle-toolbar-field-view");
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
		"ENABLE_LIVE_SEARCH" => false,
		"ENABLE_LABEL" => true
	),
	$component,
	array()
);

?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<button id="vi-stat-export" class="ui-btn ui-btn-md ui-btn-themes ui-btn-light-border <?=($arResult['ENABLE_EXPORT'] ? '' : 'ui-btn-disabled')?>"><?=GetMessage("TEL_STAT_EXPORT_TO_EXCEL")?></button>
	</div>
<?
if($isBitrix24Template)
{
	?></div><?
	$this->EndViewTarget();

	$isAdmin = CModule::IncludeModule('bitrix24') ? \CBitrix24::isPortalAdmin($USER->getId()) : $USER->IsAdmin();
	if($isAdmin)
	{
		echo Bitrix\Voximplant\Ui\Helper::getStatisticStepper();
	}
}
$totalContainer = '
	<div class="main-grid-panel-content">
		<span class="main-grid-panel-content-title">' . Loc::getMessage("TEL_STAT_TOTAL") . ':</span>&nbsp;
		<a href="#" onclick="BX.VoximplantStatisticDetail.Instance.onShowTotalClick(event);">' . Loc::getMessage("TEL_STAT_SHOW_COUNT") . '</a>
	</div>
';
?><div id="tel-stat-grid-container"><?

	$actionPanel = [
		"GROUPS" => [
			"TYPE" => [
				"ITEMS" => [
					[
						"ID" => "download_records",
						"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
						"TEXT" => Loc::getMessage("TEL_STAT_ACTION_DOWNLOAD"),
						"VALUE" => "create_download_records_list",
						"ONCHANGE" => [
							[
								"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
								"DATA" => [
									['JS' => "BX.VoximplantStatisticDetail.Instance.downloadSelectedVoxRecords()"]
								]
							]
						],
					],
				],
			]
		],
	];

	$APPLICATION->IncludeComponent(
		"bitrix:main.ui.grid",
		"",
		array(
			"GRID_ID" => $arResult["GRID_ID"],
			"HEADERS" => $arResult["HEADERS"],
			"ROWS" => $arResult["ROWS"],
			"NAV_OBJECT" => $arResult["NAV_OBJECT"],
			"SORT" => $arResult["SORT"],
			"ALLOW_COLUMNS_SORT" => true,
			"ALLOW_SORT" => true,
			"ALLOW_PIN_HEADER" => true,
			"SHOW_PAGINATION" => true,
			"SHOW_PAGESIZE" => true,
			"SHOW_ROW_CHECKBOXES" => true,
			"SHOW_CHECK_ALL_CHECKBOXES" => false,
			"SHOW_SELECTED_COUNTER" => true,
			"PAGE_SIZES" => array(
				array("NAME" => "10", "VALUE" => "10"),
				array("NAME" => "20", "VALUE" => "20"),
				array("NAME" => "50", "VALUE" => "50"),
				array("NAME" => "100", "VALUE" => "100"),
			),
			"SHOW_ACTION_PANEL" => true,
			"ACTION_PANEL" => $actionPanel,
			"TOTAL_ROWS_COUNT_HTML" => $totalContainer,
			"AJAX_MODE" => "Y",
			"AJAX_ID" => CAjax::GetComponentID('bitrix:voximplant.statistic.detail', '.default', ''),
			"AJAX_OPTION_JUMP" => "N",
			"AJAX_OPTION_HISTORY" => "N",
		),
		$component, array("HIDE_ICONS" => "Y")
	);
?></div><?
\Bitrix\Voximplant\Ui\Helper::renderCustomSelectors($arResult['FILTER_ID'], $arResult['FILTER']);
?>

<script>
	BX.message({
		"TEL_STAT_EXPORT_DETAIL_TO_EXCEL": '<?=GetMessageJS("TEL_STAT_EXPORT_DETAIL_TO_EXCEL")?>',
		"TEL_STAT_EXPORT_DETAIL_TO_EXCEL_DESCRIPTION": '<?=GetMessageJS("TEL_STAT_EXPORT_DETAIL_TO_EXCEL_DESCRIPTION")?>',
		"TEL_STAT_EXPORT_DETAIL_TO_EXCEL_LONG_PROCESS": '<?=GetMessageJS("TEL_STAT_EXPORT_DETAIL_TO_EXCEL_LONG_PROCESS")?>',
		"TEL_STAT_EXPORT_ERROR": '<?=GetMessageJS("TEL_STAT_EXPORT_ERROR")?>',
		"TEL_STAT_ACTION_EXECUTE": '<?=GetMessageJS("TEL_STAT_ACTION_EXECUTE")?>',
		"TEL_STAT_ACTION_STOP": '<?=GetMessageJS("TEL_STAT_ACTION_STOP")?>',
		"TEL_STAT_ACTION_CLOSE": '<?=GetMessageJS("TEL_STAT_ACTION_CLOSE")?>',
		"TEL_STAT_ERROR": '<?=GetMessageJS("TEL_STAT_ERROR")?>',
		"TEL_STAT_DOWNLOAD_VOX_RECORD_ERROR": '<?=GetMessageJS("TEL_STAT_DOWNLOAD_VOX_RECORD_ERROR")?>',
		"TEL_STAT_CANCEL": '<?=GetMessageJS("TEL_STAT_CANCEL")?>',
		"TEL_STAT_LOADING": '<?=GetMessageJS("TEL_STAT_LOADING")?>',
		"TEL_STAT_OUT_OF": '<?=GetMessageJS("TEL_STAT_OUT_OF")?>'
	});

	BX.ready(function() {
		new BX.VoximplantStatisticDetail({
			gridContainer: BX('<?=CUtil::JSEscape($arResult['GRID_ID'])?>'),
			exportButton: BX('vi-stat-export'),
			exportAllowed: <?= $arResult["ENABLE_EXPORT"] ? 'true' : 'false' ?>,
			exportParams: <?= CUtil::PhpToJSObject($arResult['EXPORT_PARAMS'])?>,
			exportType: 'excel'
		});
	});
</script>
