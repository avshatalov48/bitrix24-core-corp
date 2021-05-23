<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;

Extension::load(["ui.icons", "ui.hint"]);

$rows = [];

function createLink($node, $url)
{
	return
		'<div class="telephony-report-call-activity-grid-value-clickable" data-target="'.htmlspecialcharsbx($url).'">'
			. $node .
		'</div>';
}

function wrap($node)
{
	return
		'<div class="telephony-report-call-activity-grid-value">'
			. $node .
		'</div>';
}

foreach ($arResult["GRID"]["ROWS"] as $index => $row)
{
	$columns = $row["columns"];

	$columns["DATE"] = wrap(htmlspecialcharsbx($columns["DATE"]["valueFormatted"]));
	$columns["INCOMING"] = wrap(createLink((int)$columns["INCOMING"]["value"], $columns["INCOMING"]["url"]));
	$columns["MISSED"] = wrap(createLink((int)$columns["MISSED"]["value"], $columns["MISSED"]["url"]));

	$rows[] = [
		"id" => $row["id"],
		"columns" => $columns,
		"actions" => []
	];
}

$arResult["GRID"]["ROWS"] = $rows;

?>

<?
$APPLICATION->IncludeComponent(
	"bitrix:main.ui.grid",
	"",
	array(
		"GRID_ID" => $arResult["GRID"]["ID"],
		"COLUMNS" => $arResult["GRID"]["COLUMNS"],
		"ROWS" => $arResult["GRID"]["ROWS"],
		"SHOW_ROW_CHECKBOXES" => false,
		"SHOW_GRID_SETTINGS_MENU" => false,
		"SHOW_PAGINATION" => false,
		"SHOW_SELECTED_COUNTER" => false,
		"SHOW_TOTAL_COUNTER" => false,
		"ACTION_PANEL" => [],
		"TOTAL_ROWS_COUNT" => null,
		"ALLOW_COLUMNS_SORT" => true,
		"ALLOW_COLUMNS_RESIZE" => false,
		"ALLOW_SORT" => true
	)
);
?>
<script>
	BX.Voximplant.Report.CallActivityGrid.init({
		gridId: '<?= CUtil::JSEscape($arResult["GRID"]["ID"])?>',
		widgetId: '<?= CUtil::JSEscape($arResult["WIDGET"]["ID"])?>',
		boardId: '<?= CUtil::JSEscape($arResult["BOARD"]["ID"])?>',
	});

	BX.ajax.UpdatePageData = function(){};
</script>
