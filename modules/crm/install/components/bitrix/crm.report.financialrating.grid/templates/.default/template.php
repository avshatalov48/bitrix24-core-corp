<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Main\UI\Extension;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.icons',
	'ui.hint',
]);

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
			<div class="crm-report-regularcustomers-grid-rating-text" style="color: ' . $changeRecord['color'] . '">' . htmlspecialcharsEx($changeRecord['label']) . '</div>
		</div>';
}


function wrapLink($str, $url, array $classList = [], $delta = null)
{
	$classList[] = "crm-report-financial-rating-grid-value-box";

	$extraClass = "";
	$extraValue = "";
	if (!is_null($delta))
	{
		$extraClass = $delta >= 0 ? "crm-report-financial-rating-value-up" : "crm-report-financial-rating-value-down";
		$extraValue = '<div class="crm-report-financial-rating-grid-value-extra">' . ($delta > 0 ? "+" : "") . $delta . '</div>';
	}
	if($url == '')
	{
		return
			'<div class="'.implode(' ', $classList).'">
				<div class="crm-report-financial-rating-grid-value-main ' . $extraClass . '">'.htmlspecialcharsEx($str).'</div>
				' . $extraValue . '
			</div>';
	}
	else
	{
		$classList[] = "crm-report-financial-rating-grid-value-clickable";
		return
			'<div class="'.implode(' ', $classList).'" data-target="'.htmlspecialcharsbx($url).'">
				<div class="crm-report-financial-rating-grid-value-main ' . $extraClass . '">'.htmlspecialcharsEx($str).'</div>
				' . $extraValue . '
			</div>';
	}
}

foreach ($arResult["GRID"]["ROWS"] as $index => $row)
{
	$columns = $row["columns"];

	$columns["CLIENT_TITLE"] = wrapLink($columns["CLIENT_TITLE"]["valueFormatted"], $columns["CLIENT_TITLE"]["targetUrl"], ['crm-report-financial-rating-text-blue']);
	$columns["DEAL_TOTAL_COUNT"] = wrapLink($columns["DEAL_TOTAL_COUNT"]["valueFormatted"], $columns["DEAL_TOTAL_COUNT"]["targetUrl"]);
	$columns["DEAL_WON_COUNT"] = wrapLink($columns["DEAL_WON_COUNT"]["valueFormatted"], $columns["DEAL_WON_COUNT"]["targetUrl"], [], $columns["DEAL_WON_COUNT"]["delta"]);
	$columns["DEAL_WON_AMOUNT"] = wrapLink($columns["DEAL_WON_AMOUNT"]["valueFormatted"], $columns["DEAL_WON_AMOUNT"]["targetUrl"], ["crm-report-financial-rating-bold"], $columns["DEAL_WON_AMOUNT"]["delta"]);
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
	BX.Crm.Report.FinancialRatingGrid.init({
		gridId: '<?= CUtil::JSEscape($arResult["GRID"]["ID"])?>',
		widgetId: '<?= CUtil::JSEscape($arResult["WIDGET"]["ID"])?>',
		boardId: '<?= CUtil::JSEscape($arResult["BOARD"]["ID"])?>',
	});

	BX.ajax.UpdatePageData = function(){};
</script>
