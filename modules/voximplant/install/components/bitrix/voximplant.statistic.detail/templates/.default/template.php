<?php
/**
 * @var array $arResult
 * @var array $arParams
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
 * @var \CBitrixComponentTemplate $this
 * @var \CBitrixComponent $component
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Security\Permissions;

\Bitrix\Main\Loader::includeModule("ui");

CJSCore::Init([
	'main.polyfill.promise',
	'voximplant.common',
	'voximplant_transcript',
	'crm_activity_planner',
	'player',
	'ui.buttons',
	'ui.buttons.icons',
	'ui.progressbar',
	'ui.notification',
	'ui.icons.b24',
	'ui.icons',
	'ui.hint',
	'ui.stepprocessing'
]);

\Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/components/bitrix/voximplant.statistic.detail/player/skins/audio/audio.css");

ShowError($arResult["ERROR_TEXT"] ?? '');
if (!$arResult["ENABLE_EXPORT"])
{
	$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", [], $component);
}

$isBitrix24Template = (SITE_TEMPLATE_ID == "bitrix24");
if($isBitrix24Template)
{
	$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
	$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."pagetitle-toolbar-field-view");
}

$inReportSlider = (($arResult['IS_EXTERNAL_FILTER'] ?? null) && $arResult['REPORT_PARAMS']['from_analytics'] === 'Y');

if ($inReportSlider)
{
	$APPLICATION->SetTitle(Loc::getMessage('TEL_STAT_DETAIL_SLIDER_TITLE'));

	foreach ($arResult['HEADERS'] as $key => $header)
	{
		switch ($header['id'])
		{
			case 'COST_TEXT':
			case 'CALL_VOTE':
			case 'COMMENT':
				$arResult['HEADERS'][$key]['default'] = false;
				break;
		}
	}
}

\Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
	"GRID_ID" => $arResult["GRID_ID"],
	"FILTER_ID" => $arResult["FILTER_ID"],
	"FILTER" => $arResult["FILTER"],
	"FILTER_PRESETS" => $arResult["FILTER_PRESETS"] ?? null,
	"ENABLE_LIVE_SEARCH" => false,
	"DISABLE_SEARCH" => $inReportSlider,
	"ENABLE_LABEL" => true
]);

if (!$inReportSlider)
{
?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<?php
			$button = new \Bitrix\UI\Buttons\Button([
				'text' => Loc::getMessage('TEL_STAT_EXPORT_TO_EXCEL'),
				'icon' => \Bitrix\UI\Buttons\Icon::DOWNLOAD,
				'color' => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
				'dataset' => [
					'toolbar-collapsed-icon' => Bitrix\UI\Buttons\Icon::DOWNLOAD,
				]
			]);
			$button->addAttribute('id', 'vi-stat-export');
			$button->setDisabled(!$arResult['ENABLE_EXPORT']);

			\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($button);
		?>
	</div>
<?php
}

if($isBitrix24Template)
{
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
?><div id="tel-stat-grid-container"><?php

	$actionPanel = false;
	$userPermissions = Permissions::createWithCurrentUser();
	if (!$inReportSlider)
	{
		$actionPanel = [
			"GROUPS" => [
				"TYPE" => [
					"ITEMS" => [
						[
							"ID" => "download_records",
							"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
							"TEXT" => Loc::getMessage("TEL_STAT_ACTION_VOX_DOWNLOAD_2"),
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

		if ($userPermissions->canPerform(Permissions::ENTITY_CALL_RECORD, Permissions::ACTION_MODIFY))
		{
			$actionPanel['GROUPS']['TYPE']['ITEMS'][] = [
				"ID" => "download_records",
				"TYPE" => \Bitrix\Main\Grid\Panel\Types::BUTTON,
				"TEXT" => Loc::getMessage("TEL_STAT_ACTION_VOX_DELETE_RECORD"),
				"VALUE" => "create_delete_records_list",
				"ONCHANGE" => [
					[
						"ACTION" => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						"DATA" => [
							['JS' => "BX.VoximplantStatisticDetail.Instance.openDeleteConfirm(true)"]
						]
					]
				],
			];
		}
	}

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
			"SHOW_ROW_CHECKBOXES" => !$inReportSlider,
			"SHOW_CHECK_ALL_CHECKBOXES" => false,
			"SHOW_SELECTED_COUNTER" => !$inReportSlider,
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
		$component,
		array("HIDE_ICONS" => "Y")
	);
?></div><?php

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
		"TEL_STAT_OUT_OF": '<?=GetMessageJS("TEL_STAT_OUT_OF")?>',
		"TEL_STAT_RECORDS_ALREADY_DOWNLOADED": '<?=GetMessageJS("TEL_STAT_RECORDS_ALREADY_DOWNLOADED")?>',
		"TEL_STAT_RECORDS_ALREADY_DOWNLOADED_TITLE": '<?=GetMessageJS("TEL_STAT_RECORDS_ALREADY_DOWNLOADED_TITLE")?>',
		"TEL_STAT_RECORDS_DOWNLOADED_AVAILABLE": '<?=GetMessageJS("TEL_STAT_RECORDS_DOWNLOADED_AVAILABLE")?>',
		"TEL_STAT_ACTION_VOX_DOWNLOAD_HINT": '<?=GetMessageJS("TEL_STAT_ACTION_VOX_DOWNLOAD_HINT")?>',
		"TEL_STAT_ACTION_CONFIRM_DELETE_RECORD": '<?=GetMessageJS("TEL_STAT_ACTION_CONFIRM_DELETE_RECORD")?>',
		"TEL_STAT_ACTION_CANCEL_DELETE_RECORD": '<?=GetMessageJS("TEL_STAT_ACTION_CANCEL_DELETE_RECORD")?>',
		"TEL_STAT_RECORDS_ALREADY_DELETED_TITLE": '<?=GetMessageJS("TEL_STAT_RECORDS_ALREADY_DELETED_TITLE")?>',
		"TEL_STAT_RECORDS_DELETED_AVAILABLE": '<?=GetMessageJS("TEL_STAT_RECORDS_DELETED_AVAILABLE")?>',
		"TEL_STAT_RECORDS_DELETE_CONFIRM": '<?=GetMessageJS("TEL_STAT_RECORDS_DELETE_CONFIRM")?>',
		"TEL_STAT_RECORDS_DELETE_CONFIRM_TITLE": '<?=GetMessageJS("TEL_STAT_RECORDS_DELETE_CONFIRM_TITLE")?>',
		"TEL_STAT_RECORDS_DELETE_PROGRESS": '<?=GetMessageJS("TEL_STAT_RECORDS_DELETE_PROGRESS")?>',
	});

	BX.ready(function() {
		new BX.VoximplantStatisticDetail({
			gridContainer: BX('<?=CUtil::JSEscape($arResult['GRID_ID'])?>'),
			exportButton: BX('vi-stat-export'),
			exportAllowed: <?= $arResult["ENABLE_EXPORT"] ? 'true' : 'false' ?>,
			exportParams: <?= CUtil::PhpToJSObject($arResult['EXPORT_PARAMS'])?>,
			exportType: 'excel',
			reportParams: <?= CUtil::PhpToJSObject($arResult['REPORT_PARAMS'] ?? null)?>
		});
	});
</script>