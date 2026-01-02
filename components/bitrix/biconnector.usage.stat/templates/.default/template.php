<?php
/**
 * Bitrix vars
 * @var array $arParams
 * @var array $arResult
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var string $componentPath
 * @var CBitrixComponent $component
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar\Facade\Toolbar;

$request = Application::getInstance()->getContext()->getRequest();

Extension::load([
	'sidepanel',
	'ui.buttons',
	'ui.fonts.opensans',
	'biconnector.grid'
]);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . 'pagetitle-toolbar-field-view');

$frame = $request->get('IFRAME') === 'Y' ? '&IFRAME=Y' : '';

$isViewingOverLimit = $request->get('over_limit') === 'Y';

if ($isViewingOverLimit || $arResult['BICONNECTOR_LIMIT'] > 0)
{
	Toolbar::addButton([
		'link' => $isViewingOverLimit ? '?over_limit=N' . $frame :  '?over_limit=Y' . $frame,
		'text' => $isViewingOverLimit ? Loc::getMessage('CT_BBSU_SHOW_ALL') : Loc::getMessage('CT_BBSU_SHOW_OVERLIMIT'),
		'color' => Buttons\Color::LIGHT_BORDER,
	]);
}

$refreshButton = new Buttons\Button([
	'icon' => Buttons\Icon::RELOAD,
	'color' => Buttons\Color::PRIMARY,
	'click' => "reloadUsageStats",
]);
$refreshButton->addAttribute('title', Loc::getMessage('CT_BBSU_REFRESH'));

Toolbar::addButton($refreshButton);

$arResult['HEADERS'] = [
	[
		'id' => 'ID',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_ID'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'TIMESTAMP_X',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_TIMESTAMP_X'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'KEY_ID',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_ACCESS_KEY'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'SERVICE_ID',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_SERVICE_ID'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'SOURCE_ID',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_SOURCE_ID'),
		'default' => true,
		'editable' => false,
	],
	[
		'id' => 'ROW_NUM',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_ROW_NUM'),
		'default' => true,
		'editable' => false,
		'align' => 'right',
	],
	[
		'id' => 'DATA_SIZE',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_DATA_SIZE_MSGVER_1'),
		'default' => true,
		'editable' => false,
		'align' => 'right',
	],
	[
		'id' => 'REAL_TIME',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_REAL_TIME_MSGVER_1'),
		'default' => false,
		'editable' => false,
		'align' => 'right',
	],
	[
		'id' => 'FILTERS',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_FILTERS'),
		'default' => false,
		'editable' => false,
	],
	[
		'id' => 'FIELDS',
		'name' => Loc::getMessage('CT_BBSU_COLUMN_FIELDS'),
		'default' => false,
		'editable' => false,
	],
];

if (isset($arParams['BI_ANALYTIC']) && $arParams['BI_ANALYTIC'] === 'Y')
{
	$arResult['HEADERS'] = array_filter($arResult['HEADERS'], function($header)  {
		return !in_array($header['id'], ['SERVICE_ID', 'KEY_ID']);
	});
}

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $arResult['GRID_ID'],
		'COLUMNS' => $arResult['HEADERS'],
		'ROWS' => $arResult['ROWS'],
		'SORT' => $arResult['SORT'],
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'ALLOW_ROWS_SORT' => false,
		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_SELECTED_COUNTER' => false,
		'SHOW_TOTAL_COUNTER' => false,
		'EDITABLE' => false,
		'STUB' => empty($arResult['ROWS']) ? $arResult['STUB'] : null,
		'SHOW_NAVIGATION_PANEL' => true,
		'NAV_OBJECT' => $arResult['NAV'],
		'NAV_PARAMS' => [
			'SEF_MODE' => 'N',
		],
		'NAV_PARAM_NAME' => 'page',
		'SHOW_PAGINATION' => true,
		'SHOW_PAGESIZE' => true,
		'PAGE_SIZES' => [
			['NAME' => 10, 'VALUE' => '10'],
			['NAME' => 20, 'VALUE' => '20'],
			['NAME' => 50, 'VALUE' => '50'],
		],

	],
	$component,
	['HIDE_ICONS' => 'Y']
);
?>
	<script>
		function showMore(btn, textToAdd)
		{
			const text = btn.previousSibling;
			text.textContent += textToAdd;
			btn.remove();
			return false;
		}

		function reloadUsageStats()
		{
			BX.Main.gridManager.getInstanceById('<?= $arResult['GRID_ID'] ?>').reload();
		}
	</script>
<?php

if (!\Bitrix\BIConnector\LimitManager::getInstance()->checkLimitWarning())
{
	$APPLICATION->IncludeComponent('bitrix:biconnector.limit.lock', '');
}
