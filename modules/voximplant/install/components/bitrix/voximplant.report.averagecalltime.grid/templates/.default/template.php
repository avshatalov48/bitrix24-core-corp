<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Uri;

Extension::load(["ui.icons", "ui.hint", "ui.fonts.opensans"]);

$rows = [];

function getUserLayout($userName, $userIcon)
{
	return
		'<div class="telephony-report-avg-call-time-grid-user">
			<div class="ui-icon ui-icon-common-user telephony-report-avg-call-time-grid-user-icon">
				<i '. ($userIcon ? 'style="background-image: url(\''. Uri::urnEncode($userIcon) . '\')" ' : ''). '></i>
			</div>
			<div class="telephony-report-avg-call-time-grid-user-name">
				' . htmlspecialcharsbx($userName) . '
			</div>
		</div>';
}

function getCompareLayout($value, $formatted)
{
	if ($value == null)
	{
		return '&mdash;';
	}

	if ((int)$value > 0)
	{
		return '<div class="telephony-report-avg-call-time-grid-value-up">+' .
			   	htmlspecialcharsEx($formatted) .
			   '</div>';
	}

	if ((int)$value < 0)
	{
		return '<div class="telephony-report-avg-call-time-grid-value-down">-' .
			   	htmlspecialcharsEx($formatted) .
			   '</div>';
	}
}

function wrapGridValue($node)
{
	return
		'<div class="telephony-report-avg-call-time-grid-value">'
			. $node .
		'</div>';
}

foreach ($arResult["GRID"]["ROWS"] as $index => $row)
{
	$columns = $row["columns"];

	$columns["EMPLOYEE"] = wrapGridValue(getUserLayout($columns["EMPLOYEE"]["valueFormatted"], $columns["EMPLOYEE"]["icon"]));
	$columns["AVG_CALL_TIME"] = wrapGridValue(htmlspecialcharsbx($columns["AVG_CALL_TIME"]["valueFormatted"]));
	$columns["DYNAMICS"] = wrapGridValue(getCompareLayout($columns["DYNAMICS"]["value"], $columns["DYNAMICS"]["valueFormatted"]));

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
	BX.Voximplant.Report.AverageCallTimeGrid.init({
		gridId: '<?= CUtil::JSEscape($arResult["GRID"]["ID"])?>',
		widgetId: '<?= CUtil::JSEscape($arResult["WIDGET"]["ID"])?>',
		boardId: '<?= CUtil::JSEscape($arResult["BOARD"]["ID"])?>',
	});

	BX.ajax.UpdatePageData = function(){};
</script>
