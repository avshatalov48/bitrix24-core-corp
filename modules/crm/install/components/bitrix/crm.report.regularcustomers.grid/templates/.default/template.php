<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Main\UI\Extension;

Extension::load(["ui.icons", "ui.hint"]);

$rows = [];

function getConversionLayout(array $changeRecord)
{
	if($changeRecord['value'] === false)
	{
		return '&mdash;';
	}

	return
		'<div class="crm-report-regularcustomers-grid-rating">
			<div class="crm-report-regularcustomers-grid-rating-icon" style="background: ' . $changeRecord['color'] . '"></div>
			<div class="crm-report-regularcustomers-grid-rating-value">' . $changeRecord['value'] . '%</div>
			<div class="crm-report-regularcustomers-grid-rating-text" style="color: ' . $changeRecord['color'] . '">' . $changeRecord['label'] . '</div>
		</div>';
}

function wrapLink($str, $url, array $classList = [])
{
	$classList[] = "crm-report-regularcustomers-grid-value";
	if($url == '')
	{
		return '<div class="'.implode(' ', $classList).'">'.$str.'</div>';
	}
	else
	{
		$classList[] = "crm-report-regularcustomers-grid-value-clickable";
		return '<div class="'.implode(' ', $classList).'" data-target="'.htmlspecialcharsbx($url).'">'.$str.'</div>';
	}
}

foreach ($arResult["GRID"]["ROWS"] as $index => $row)
{
	$columns = $row["columns"];

	$columns["CLIENT_TITLE"] = wrapLink($columns["CLIENT_TITLE"]["valueFormatted"], $columns["CLIENT_TITLE"]["targetUrl"]);
	$columns["DEAL_COUNT"] = wrapLink($columns["DEAL_COUNT"]["valueFormatted"], $columns["DEAL_COUNT"]["targetUrl"]);
	$columns["DEAL_WON_COUNT"] = wrapLink($columns["DEAL_WON_COUNT"]["valueFormatted"], $columns["DEAL_WON_COUNT"]["targetUrl"]);
	$columns["DEAL_WON_AMOUNT"] = wrapLink($columns["DEAL_WON_AMOUNT"]["valueFormatted"], $columns["DEAL_WON_AMOUNT"]["targetUrl"], ["crm-report-regularcustomers-bold"]);
	$columns["CONVERSION"] = getConversionLayout($columns["CONVERSION"]);

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
	BX.Crm.Report.RegularCustomersGrid.init({
		gridId: '<?= CUtil::JSEscape($arResult["GRID"]["ID"])?>',
		widgetId: '<?= CUtil::JSEscape($arResult["WIDGET"]["ID"])?>',
		boardId: '<?= CUtil::JSEscape($arResult["BOARD"]["ID"])?>',
	});

	BX.ajax.UpdatePageData = function(){};
</script>