<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Localization\Loc;

use \Bitrix\Main\UI;

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
	$APPLICATION->SetTitle(Loc::getMessage('OL_STAT_TITLE', ['#LINE_NAME#' => htmlspecialcharsbx($arResult['LINE_NAME'])]));
}

\ShowError($arResult['ERROR_TEXT']);

$isBitrix24Template = (SITE_TEMPLATE_ID == 'bitrix24');
if($isBitrix24Template)
{
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'pagetitle-toolbar-field-view');

	$this->SetViewTarget('inside_pagetitle', 0);
	?><div class="pagetitle-container pagetitle-flexible-space"><?
}

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.filter',
	'',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'FILTER_ID' => $arResult['FILTER_ID'],
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'ENABLE_LIVE_SEARCH' => (bool)\Bitrix\Main\Config\Option::get('imopenlines', 'enable_live_search'),
		'ENABLE_LABEL' => true
	],
	$component,
	[]
);

?>
	<div class="pagetitle-container pagetitle-align-right-container">
		<span onclick="<?=$arResult['BUTTON_EXPORT']?>" class="webform-small-button webform-small-button-transparent">
			<span class="webform-small-button-left"></span>
			<span class="webform-button-icon"></span>
			<span class="webform-small-button-text"><?=Loc::getMessage('OL_STAT_EXCEL')?></span>
			<span class="webform-small-button-right"></span>
			<?if($arResult['LIMIT_EXPORT'] === true):?>
			<span class="tariff-lock"></span>
			<?endif;?>
		</span>
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
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'ROWS' => $arResult['ELEMENTS_ROWS'],
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'SORT' => $arResult['SORT'],
		'ALLOW_COLUMNS_SORT' => true,
		'ALLOW_SORT' => true,
		'ALLOW_PIN_HEADER' => true,
		'ACTION_PANEL' => $arResult['GROUP_ACTIONS'],
		'SHOW_CHECK_ALL_CHECKBOXES' => true,
		'SHOW_ROW_CHECKBOXES' => true,
		'SHOW_ROW_ACTIONS_MENU'     => true,
		'SHOW_GRID_SETTINGS_MENU'   => true,
		'SHOW_NAVIGATION_PANEL'     => true,
		'SHOW_SELECTED_COUNTER'     => true,
		'SHOW_TOTAL_COUNTER'        => true,
		'SHOW_PAGINATION' => true,
		'SHOW_PAGESIZE' => true,
		'PAGE_SIZES' => [
			['NAME' => '20', 'VALUE' => '20'],
			['NAME' => '50', 'VALUE' => '50'],
			['NAME' => '100', 'VALUE' => '100'],
		],
		'TOTAL_ROWS_COUNT' => $arResult['ROWS_COUNT'],
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_JUMP' => 'N',
        'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_ID' => CAjax::GetComponentID('bitrix:imopenlines.statistics.detail', '.default', '')
	],
	$component,
	['HIDE_ICONS' => 'Y']
);

\Bitrix\Imopenlines\Ui\Helper::renderCustomSelectors($arResult['FILTER_ID'], $arResult['FILTER']);
UI\Extension::load([
	'ui.entity-selector',
	'ui.buttons',
	'ui.forms',
	'ui.stepprocessing',
]);
$isStExport = is_array($arResult['STEXPORT_PARAMS']);
if ($isStExport)
{
	?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.UI.StepProcessing.ProcessManager.create(<?=\Bitrix\Main\Web\Json::encode([
					'id' => 'OpenLinesExport',
					'component' => 'bitrix:imopenlines.statistics.detail',
					'componentMode' => 'ajax',
					'messages' => [
					 	'DialogTitle' => Loc::getMessage('OL_STAT_EXCEL_EXPORT_POPUP_TITLE'),
						'DialogSummary' => Loc::getMessage('OL_STAT_EXCEL_EXPORT_POPUP_BODY'),
						'DialogStartButton' => Loc::getMessage('OL_STAT_EXCEL_EXPORT_POPUP_BTN_START'),
						'DialogStopButton' => Loc::getMessage('OL_STAT_EXCEL_EXPORT_POPUP_BTN_STOP'),
						'DialogCloseButton' => Loc::getMessage('OL_STAT_EXCEL_EXPORT_POPUP_BTN_CLOSE'),
						//'RequestError' => Loc::getMessage('OL_STAT_EXCEL_EXPORT_POPUP_REQUEST_ERR'),
						//'WaitingResponse' => Loc::getMessage('OL_STAT_EXCEL_EXPORT_POPUP_WAIT'),
					],
					'queue' => [
						[
							'action' => 'dispatcher',
						],
					],
					'params' => $arResult['STEXPORT_PARAMS'],
					'dialogMaxWidth' => 610,
				])?>);
			}
		);
	</script>
	<?php
}
?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.message({
				LIST_GROUP_ACTION_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('OL_STAT_EXCEL_ACTIONS_TITLE'))?>',
				LIST_GROUP_ACTION_SUMMARY: '<?= CUtil::JSEscape(Loc::getMessage('OL_STAT_EXCEL_ACTIONS_BODY'))?>',
				LIST_GROUP_ACTION_CLOSE: '<?= CUtil::JSEscape(Loc::getMessage('OL_COMPONENT_SESSION_LIST_GROUP_ACTION_CLOSE_TITLE'))?>',
				LIST_GROUP_ACTION_SPAM: '<?= CUtil::JSEscape(Loc::getMessage('OL_COMPONENT_SESSION_LIST_GROUP_ACTION_SPAM_TITLE'))?>',
				LIST_GROUP_ACTION_TRANSFER: '<?= CUtil::JSEscape(Loc::getMessage('OL_COMPONENT_SESSION_LIST_GROUP_ACTION_TRANSFER_TITLE'))?>',
				OL_COMPONENT_SESSION_CONFIRM_GROUP_ACTION: '<?= CUtil::JSEscape(Loc::getMessage('OL_COMPONENT_SESSION_CONFIRM_GROUP_ACTION'))?>',//TODO: del
				OL_COMPONENT_SESSION_GROUP_ACTION_OPEN_LINES_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('OL_COMPONENT_SESSION_GROUP_ACTION_OPEN_LINES_TITLE')) ?>',
			});

			BX.OpenLines.GridActions.gridId = "<?= CUtil::JSEscape($arResult['GRID_ID'])?>";//TODO: del
			BX.OpenLines.Actions.init(
				"<?= CUtil::JSEscape($arResult['GRID_ID'])?>",
				<?=CUtil::PhpToJSObject($arResult['groupActionsData'])?>
			);
		}
	);
</script>
