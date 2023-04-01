<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

CUtil::InitJSCore(array('window'));

if(SITE_TEMPLATE_ID === 'bitrix24' && ($arParams['~STYLES_LOADED'] ?? null) !== 'Y')
{
	$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/bitrix24/crm-entity-show.css');
	$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
	$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-paddings pagetitle-toolbar-field-view flexible-layout crm-toolbar');
}

$asset = Bitrix\Main\Page\Asset::getInstance();
$asset->addJs('/bitrix/js/main/utils.js');
$asset->addJs('/bitrix/js/main/popup_menu.js');
$asset->addJs('/bitrix/js/crm/common.js');
$asset->addJs('/bitrix/js/crm/dialog.js');

$gridID = isset($arParams['~GRID_ID']) ? $arParams['~GRID_ID'] : '';
$prefix = $gridID;
$prefixLC = mb_strtolower($gridID);

$nameTemplate = isset($arParams['~NAME_TEMPLATE']) ? $arParams['~NAME_TEMPLATE'] : '';
$extension = isset($arParams['~EXTENSION']) && is_array($arParams['~EXTENSION'])
	? $arParams['~EXTENSION'] : array();
$pagination = isset($arParams['~PAGINATION']) && is_array($arParams['~PAGINATION'])
	? $arParams['~PAGINATION'] : array();
$actionPanel = isset($arParams['~ACTION_PANEL']) && is_array($arParams['~ACTION_PANEL'])
	? $arParams['~ACTION_PANEL'] : array('GROUPS' => array(array('ITEMS' => array())));

//region Filter
//Skip reneding of grid filter for internal grid request (filter already created)
if(!Bitrix\Main\Grid\Context::isInternalRequest() && ($arParams['~HIDE_FILTER'] ?? null) !== true)
{
	if (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER')
	{
		$disableNavigationBar = 'Y';
	}
	else
	{
		$disableNavigationBar = isset($arParams['~DISABLE_NAVIGATION_BAR']) ? $arParams['~DISABLE_NAVIGATION_BAR'] : 'N';
	}
	$filterParams = isset($arParams['~FILTER_PARAMS']) ? $arParams['~FILTER_PARAMS'] : array();
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.filter',
		isset($arParams['~FILTER_TEMPLATE']) ? $arParams['~FILTER_TEMPLATE'] : 'title',
		array_merge(
			array(
				'GRID_ID' => $gridID,
				'FILTER_ID' => $gridID,
				'FILTER' => isset($arParams['~FILTER']) ? $arParams['~FILTER'] : array(),
				'FILTER_PRESETS' => isset($arParams['~FILTER_PRESETS']) ? $arParams['~FILTER_PRESETS'] : array(),
				'RENDER_INTO_VIEW' => isset($arParams['~RENDER_FILTER_INTO_VIEW']) ? $arParams['~RENDER_FILTER_INTO_VIEW'] : '',
				'DISABLE_NAVIGATION_BAR' => $disableNavigationBar,
				'NAVIGATION_BAR' => isset($arParams['~NAVIGATION_BAR']) ? $arParams['~NAVIGATION_BAR'] : null,
				'LIMITS' => isset($arParams['~LIVE_SEARCH_LIMIT_INFO']) ? $arParams['~LIVE_SEARCH_LIMIT_INFO'] : null,
				'ENABLE_LIVE_SEARCH' => isset($arParams['~ENABLE_LIVE_SEARCH']) && $arParams['~ENABLE_LIVE_SEARCH'] === true,
				'DISABLE_SEARCH' => isset($arParams['~DISABLE_SEARCH']) && $arParams['~DISABLE_SEARCH'] === true,
			),
			$filterParams
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}
//endregion

//region Navigation
$navigationHtml = '';
$navigationObject = null;
if(isset($arParams['~PAGINATION']) && is_array($arParams['~PAGINATION']))
{
	ob_start();
	$APPLICATION->IncludeComponent(
		'bitrix:crm.pagenavigation',
		'',
		$pagination,
		$component,
		array('HIDE_ICONS' => 'Y')
	);
	$navigationHtml = ob_get_contents();
	ob_end_clean();
}
elseif(isset($arParams['~NAV_OBJECT']) && is_object($arParams['~NAV_OBJECT']))
{
	$navigationObject = $arParams['~NAV_OBJECT'];
}
//endregion

//region Row Count
$rowCountHtml = '';
if(isset($arParams['~ENABLE_ROW_COUNT_LOADER']) && $arParams['~ENABLE_ROW_COUNT_LOADER'] === true)
{
	$rowCountHtml = str_replace(
		array('%prefix%', '%all%', '%show%'),
		array(CUtil::JSEscape(mb_strtolower($gridID)), GetMessage('CRM_ALL'), GetMessage('CRM_SHOW_ROW_COUNT')),
		'<div id="%prefix%_row_count_wrapper">%all%: <a id="%prefix%_row_count" href="#">%show%</a></div>'
	);
}
//endregion

//region Grid
$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $gridID,
		'HEADERS' => $arParams['~HEADERS'] ?? [],
		'HEADERS_SECTIONS' => $arParams['~HEADERS_SECTIONS'] ?? [],
		'ENABLE_FIELDS_SEARCH' => $arParams['~ENABLE_FIELDS_SEARCH'] ?? 'N',
		'SORT' => $arParams['~SORT'] ?? [],
		'SORT_VARS' => $arParams['~SORT_VARS'] ?? [],
		'ROWS' => $arParams['~ROWS'] ?? [],
		'ROW_LAYOUT' => $arParams['~ROW_LAYOUT'] ?? [],
		'AJAX_MODE' => $arParams['~AJAX_MODE'] ?? 'Y', //Strongly required
		'FORM_ID' => $arParams['~FORM_ID'] ?? '',
		'TAB_ID' => $arParams['~TAB_ID'] ?? '',
		'AJAX_ID' => $arParams['~AJAX_ID'] ?? '',
		'AJAX_OPTION_JUMP' => $arParams['~AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arParams['~AJAX_OPTION_HISTORY'],
		"PRESERVE_HISTORY" => $arParams['~PRESERVE_HISTORY'] ?? false,
		'MESSAGES' => $arParams['~MESSAGES'] ?? [],
		"NAV_STRING" => $navigationHtml,
		"NAV_PARAM_NAME" => 'page',
		"CURRENT_PAGE" => isset($pagination['PAGE_NUM']) ? (int)$pagination['PAGE_NUM'] : 1,
		"ENABLE_NEXT_PAGE" => isset($pagination['ENABLE_NEXT_PAGE']) ? (bool)$pagination['ENABLE_NEXT_PAGE'] : false,
		"PAGE_SIZES" => [
			["NAME" => "5", "VALUE" => "5"],
			["NAME" => "10", "VALUE" => "10"],
			["NAME" => "20", "VALUE" => "20"],
			["NAME" => "50", "VALUE" => "50"],
			["NAME" => "100", "VALUE" => "100"],
			//Temporary limited by 100
			//array("NAME" => "200", "VALUE" => "200"),
		],
		"ALLOW_COLUMNS_SORT" => true,
		"ALLOW_ROWS_SORT" => false,
		"ALLOW_COLUMNS_RESIZE" => true,
		"ALLOW_HORIZONTAL_SCROLL" => true,
		"ALLOW_SORT" => ($arParams['ALLOW_SORT'] ?? true) === true,
		"ALLOW_PIN_HEADER" => true,
		"ACTION_PANEL" => $actionPanel,
		"SHOW_CHECK_ALL_CHECKBOXES" => isset($arParams['SHOW_CHECK_ALL_CHECKBOXES']) ? (bool)($arParams['SHOW_CHECK_ALL_CHECKBOXES']) : true,
		"SHOW_ROW_CHECKBOXES" => isset($arParams['SHOW_ROW_CHECKBOXES']) ? (bool)($arParams['SHOW_ROW_CHECKBOXES']) : true,
		"SHOW_ROW_ACTIONS_MENU" => isset($arParams['SHOW_ROW_ACTIONS_MENU']) ? (bool)($arParams['SHOW_ROW_ACTIONS_MENU']) : true,
		"SHOW_GRID_SETTINGS_MENU" => true,
		"SHOW_MORE_BUTTON" => true,
		"SHOW_NAVIGATION_PANEL" => isset($arParams['SHOW_NAVIGATION_PANEL']) ? (bool)($arParams['SHOW_NAVIGATION_PANEL']) : true,
		"SHOW_PAGINATION" => isset($arParams['SHOW_PAGINATION']) ? (bool)($arParams['SHOW_PAGINATION']) : true,
		"ENABLE_COLLAPSIBLE_ROWS" => isset($arParams['ENABLE_COLLAPSIBLE_ROWS']) ? (bool)($arParams['ENABLE_COLLAPSIBLE_ROWS']) : false,
		"SHOW_SELECTED_COUNTER" => isset($arParams['SHOW_SELECTED_COUNTER']) ? (bool)($arParams['SHOW_SELECTED_COUNTER']) : true,
		"SHOW_TOTAL_COUNTER" => isset($arParams['SHOW_TOTAL_COUNTER']) ? (bool)($arParams['SHOW_TOTAL_COUNTER']) : true,
		"SHOW_PAGESIZE" => isset($arParams['SHOW_PAGESIZE']) ? (bool)($arParams['SHOW_PAGESIZE']) : true,
		"SHOW_ACTION_PANEL" => isset($arParams['SHOW_ACTION_PANEL']) ? (bool)($arParams['SHOW_ACTION_PANEL']) : true,
		"TOTAL_ROWS_COUNT_HTML" => $rowCountHtml,
		"TOTAL_ROWS_COUNT" => isset($arParams['TOTAL_ROWS_COUNT']) ? (int)$arParams['TOTAL_ROWS_COUNT'] : null,
		"ADVANCED_EDIT_MODE" => (bool)($arParams['ADVANCED_EDIT_MODE'] ?? false),
	],
	$component,
	array('HIDE_ICONS' => 'Y')
);
//endregion

$extensionConfig = isset($extension['CONFIG']) ? $extension['CONFIG'] : null;
if(is_array($extensionConfig))
{
	$extensionID = isset($extension['ID']) ? $extension['ID'] : $gridID;
	$extensionMessages = isset($extension['MESSAGES']) && is_array($extension['MESSAGES']) ? $extension['MESSAGES'] : array();
	$extensionMessages['deletionWarning'] = GetMessage('CRM_INTERFACE_GRID_DELETION_WARNING');
	$extensionMessages['goToDetails'] = GetMessage('CRM_INTERFACE_GRID_GO_TO_DETAILS');
	$extensionConfig['destroyPreviousExtension'] = true;
	?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.Crm.Page.initialize();
				BX.CrmUIGridExtension.messages = <?=CUtil::PhpToJSObject($extensionMessages)?>;
				BX.CrmUIGridExtension.create(
					"<?=CUtil::JSEscape($extensionID)?>",
					<?=CUtil::PhpToJSObject($extensionConfig)?>
				);
			}
		);
	</script><?
}
?>
