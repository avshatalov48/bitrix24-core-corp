<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

global $APPLICATION;

$APPLICATION->SetTitle($arResult['TITLE']);

$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
$controlPanel = ['GROUPS' => [['ITEMS' => []]]];
$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();

$getTextWithHoverTemplate = static function(string $text): string
{
	ob_start();
	?>
	<span>
		<?= htmlspecialcharsbx($text) ?>
	</span>
	<?php
	return (string)ob_get_clean();
};

$prepareGridData = static function ($field) use (
	$getTextWithHoverTemplate
)
{
	return [
		'ID' => $field['ID'],
		'TITLE' => $getTextWithHoverTemplate($field['TITLE']),
		'TYPE' => $getTextWithHoverTemplate($field['TYPE']),
	];
};
if (!isset($gridRows))
{
	$gridRows = [];
}

foreach ($arResult['GRID_DATA'] as $field)
{
	$gridRows[] = [
		'data' => $prepareGridData($field),
	];
}

$APPLICATION->IncludeComponent('bitrix:main.ui.grid',
   "",
   [
	   'GRID_ID' => $arResult['GRID_ID'],
	   'COLUMNS' => $arResult['COLUMNS'],
	   'ROWS' => $gridRows,
	   'SHOW_ROW_CHECKBOXES' => true,
	   'SHOW_TOTAL_COUNTER' => true,
	   'TOTAL_ROWS_COUNT' => $arResult['TOTAL_COUNT'],
	   'ACTION_PANEL' => $controlPanel,
	   'ALLOW_COLUMNS_SORT' => false,
	   'ALLOW_SORT' => false,
	   'ALLOW_COLUMNS_RESIZE' => true,
	   'AJAX_MODE' => 'Y',
	   'AJAX_OPTION_HISTORY' => 'N',
	   'SHOW_GROUP_DELETE_BUTTON' => true,
   ]);
