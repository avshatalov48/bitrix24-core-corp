<?php
/**
 * @var $component \CatalogProductVariationGridComponent
 * @var $this \CBitrixComponentTemplate
 * @var \CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;

global $APPLICATION;
$APPLICATION->SetTitle(Main\Localization\Loc::getMessage('CRM_TERMINAL_PAYMENT_LIST_COMPONENT_TEMPLATE_TITLE'));

Main\UI\Extension::load([
	'crm.terminal',
	'salescenter.manager',
	'ui.dialogs.messagebox',
]);

if (!empty($arResult['ERROR_MESSAGES']))
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.info.error',
		'',
		[
			'TITLE' => $arResult['ERROR_MESSAGES'][0],
		]
	);

	return;
}

if ($arResult['IS_ROWS_EXIST'])
{
	$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
		'GRID_ID' => $arResult['GRID_ID'],
		'COLUMNS' => $arResult['COLUMNS'],
		'ROWS' => $arResult['ROWS'],
		'SHOW_ROW_CHECKBOXES' => true,
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'AJAX_MODE' => 'Y',
		'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
		'PAGE_SIZES' => [
			['NAME' => '20', 'VALUE' => '20'],
			['NAME' => '50', 'VALUE' => '50'],
			['NAME' => '100', 'VALUE' => '100']
		],
		'DEFAULT_PAGE_SIZE' => 20,
		'AJAX_OPTION_JUMP' => 'N',
		'SHOW_CHECK_ALL_CHECKBOXES' => true,
		'SHOW_ROW_ACTIONS_MENU' => true,
		'SHOW_GRID_SETTINGS_MENU' => true,
		'SHOW_NAVIGATION_PANEL' => true,
		'SHOW_PAGINATION' => true,
		'SHOW_SELECTED_COUNTER' => true,
		'SHOW_TOTAL_COUNTER' => true,
		'TOTAL_ROWS_COUNT' => $arResult['TOTAL_ROWS_COUNT'],
		'SHOW_PAGESIZE' => true,
		'SHOW_ACTION_PANEL' => $arResult['SHOW_ACTION_PANEL'],
		'ACTION_PANEL' => $arResult['ACTION_PANEL'],
		'ALLOW_COLUMNS_SORT' => true,
		'ALLOW_COLUMNS_RESIZE' => true,
		'ALLOW_HORIZONTAL_SCROLL' => true,
		'ALLOW_SORT' => true,
		'ALLOW_PIN_HEADER' => true,
		'AJAX_OPTION_HISTORY' => 'N',
		'USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP' => $arResult['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] ?? false,
		'ENABLE_FIELDS_SEARCH' => $arResult['ENABLE_FIELDS_SEARCH'] ?? 'N',
	]);
}
else
{
	$APPLICATION->IncludeComponent('bitrix:crm.terminal.emptystate', '');
}
?>

<script>
	BX.message(<?=Main\Web\Json::encode(Main\Localization\Loc::loadLanguageFile(__FILE__))?>);

	BX.ready(function () {
		if (!BX.Reflection.getClass('BX.Crm.Component.TerminalPaymentList.Instance'))
		{
			BX.Crm.Component.TerminalPaymentList.Instance = new BX.Crm.Component.TerminalPaymentList({
				gridId: '<?=CUtil::JSEscape($arResult['GRID_ID'])?>',
				settingsSliderUrl: '<?=CUtil::JSEscape($arResult['SETTINGS_PATH'])?>',
			});
		}
	});
</script>
