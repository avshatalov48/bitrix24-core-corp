<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use \Bitrix\Crm\Integration\Report\Handler\SalesDynamics\WonLostAmount;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.icons',
	'ui.hint',
]);

$rows = [];

function getChangeLayout(array $changeRecord)
{
	if($changeRecord['value'] === false)
	{
		return '&mdash;';
	}

	return
		'<div class="crm-report-salesdynamics-grid-rating">
			<div class="crm-report-salesdynamics-grid-rating-icon" style="background: ' . $changeRecord['color'] . '"></div>
			<div class="crm-report-salesdynamics-grid-rating-value">' . $changeRecord['value'] . '%</div>
			<div class="crm-report-salesdynamics-grid-rating-text" style="color: ' . $changeRecord['color'] . '">' . htmlspecialcharsEx($changeRecord['label']) . '</div>
		</div>';
}

function wrapLink($str, $url, array $classList = [])
{
	$classList[] = "crm-report-salesdynamics-grid-value";
	if($url == '')
	{
		return '<div class="'.implode(' ', $classList).'">'.htmlspecialcharsEx($str).'</div>';
	}
	else
	{
		$classList[] = "crm-report-salesdynamics-grid-value-clickable";
		return '<div class="'.implode(' ', $classList).'" data-target="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsEx($str).'</div>';
	}
}

//$targetUrl = $arResult["TARGET_URL"][$userId];

foreach ($arResult["GRID"]["ROWS"] as $index => $row)
{
	$columns = $row["columns"];

	$columns["CURRENT_DATE"] = wrapLink($columns["CURRENT_DATE"]["valueFormatted"], $columns["CURRENT_DATE"]["targetUrl"]);
	$columns["PREV_DATE"] = wrapLink($columns["PREV_DATE"]["valueFormatted"], $columns["PREV_DATE"]["targetUrl"]);
	$columns["WON_CURRENT"] = wrapLink($columns["WON_CURRENT"]["valueFormatted"], $columns["WON_CURRENT"]["targetUrl"], ["crm-report-salesdynamics-bold"]);
	$columns["WON_PREV"] = wrapLink($columns["WON_PREV"]["valueFormatted"], $columns["WON_PREV"]["targetUrl"], ["crm-report-salesdynamics-bold"]);
	$columns["DYNAMICS"] = getChangeLayout($columns["DYNAMICS"]["value"]);

	$rows[] = [
		"id" => $row["id"],
		"columns" => $columns,
		"actions" => []
	];
}

// region: total
$total = $arResult["GRID"]["TOTAL"];
$columns = $total["columns"];

$columns["CURRENT_DATE"] = wrapLink($columns["CURRENT_DATE"]["valueFormatted"], "");
$columns["PREV_DATE"] = wrapLink($columns["PREV_DATE"]["valueFormatted"], "", ["crm-report-salesdynamics-grid-total"]);
$columns["WON_CURRENT"] = wrapLink($columns["WON_CURRENT"]["valueFormatted"], "", ["crm-report-salesdynamics-bold"]);
$columns["WON_PREV"] = wrapLink($columns["WON_PREV"]["valueFormatted"], "", ["crm-report-salesdynamics-bold"]);
$columns["DYNAMICS"] = getChangeLayout($columns["DYNAMICS"]["value"]);

$rows[] = [
	"id" => $total["id"],
	"columns" => $columns,
	"actions" => []
];
// endregion

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
		"SHOW_GRID_SETTINGS_MENU" => true,
		"SHOW_PAGINATION" => false,
		"SHOW_SELECTED_COUNTER" => false,
		"SHOW_TOTAL_COUNTER" => false,
		"ACTION_PANEL" => [],
		"TOTAL_ROWS_COUNT" => null,
		"ALLOW_COLUMNS_SORT" => true,
		"ALLOW_COLUMNS_RESIZE" => true,
	)
);
?>
<script>
	BX.Crm.Report.SalesDynamicGrid.init({
		gridId: '<?= CUtil::JSEscape($arResult["GRID"]["ID"])?>',
		widgetId: '<?= CUtil::JSEscape($arResult["WIDGET"]["ID"])?>',
		boardId: '<?= CUtil::JSEscape($arResult["BOARD"]["ID"])?>',
	});

	BX.ajax.UpdatePageData = function(){};
</script>