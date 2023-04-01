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
		'<div class="telephony-report-employees-workload-grid-user">
			<div class="ui-icon ui-icon-common-user telephony-report-employees-workload-grid-user-icon">
				<i '. ($userIcon ? 'style="background-image: url(\''. Uri::urnEncode($userIcon) . '\')" ' : ''). '></i>
			</div>
			<div class="telephony-report-employees-workload-grid-user-name">
				' . htmlspecialcharsbx($userName) . '
			</div>
		</div>';
}

function getCompareLayout($value, $negative = false)
{
	if (is_null($value))
	{
		return '&mdash;';
	}

	$result = $value . '%';
	$postfix = $negative ? '-negative' : '';

	if ($value > 0)
	{
		$result = '<div class="telephony-report-employees-workload-grid-value-up'.$postfix.'">' .
				  	htmlspecialcharsEx($result) .
				  '</div>';
	}
	elseif ($value < 0)
	{
		$result = '<div class="telephony-report-employees-workload-grid-value-down'.$postfix.'">' .
				  	htmlspecialcharsEx($result) .
				  '</div>';
	}

	return $result;
}

function getValueCompareLayout($value, $compare, $negative = false)
{
	$compare = getCompareLayout($compare, $negative);

	if ($compare === '&mdash;')
	{
		return $value;
	}

	return
		'<div class="telephony-report-employees-workload-grid-value-compare">'
		. $value .
		'</div>' . $compare;
}

function createLink($node, $url)
{
	return
		'<div class="telephony-report-employees-workload-grid-value-clickable" data-target="'.htmlspecialcharsbx($url).'">'
			. $node .
		'</div>';
}

function wrapGridValue($node)
{
	return
		'<div class="telephony-report-employees-workload-grid-value">'
			. $node .
		'</div>';
}

foreach ($arResult["GRID"]["ROWS"] as $index => $row)
{
	$columns = $row["columns"];

	$columns["EMPLOYEE"] = wrapGridValue(getUserLayout($columns["EMPLOYEE"]["valueFormatted"], $columns["EMPLOYEE"]["icon"]));
	$columns["INCOMING"] = wrapGridValue(createLink($columns["INCOMING"]["value"], $columns["INCOMING"]["url"]));
	$columns["OUTGOING"] = wrapGridValue(createLink($columns["OUTGOING"]["value"], $columns["OUTGOING"]["url"]));
	$columns["MISSED"] = wrapGridValue(createLink(getValueCompareLayout($columns["MISSED"]["value"], $columns["MISSED"]["dynamics"], true), $columns["MISSED"]["url"]));
	$columns["COUNT"] = wrapGridValue(createLink($columns["COUNT"]["value"], $columns["COUNT"]["url"]));
	$columns["DYNAMICS"] = wrapGridValue(getCompareLayout($columns["DYNAMICS"]["value"]));

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
	BX.Voximplant.Report.EmployeesWorkloadGrid.init({
		gridId: '<?= CUtil::JSEscape($arResult["GRID"]["ID"])?>',
		widgetId: '<?= CUtil::JSEscape($arResult["WIDGET"]["ID"])?>',
		boardId: '<?= CUtil::JSEscape($arResult["BOARD"]["ID"])?>',
	});

	BX.ajax.UpdatePageData = function(){};
</script>
