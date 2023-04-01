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
		'<div class="telephony-report-call-dynamics-grid-user">
			<div class="ui-icon ui-icon-common-user telephony-report-call-dynamics-grid-user-icon">
				<i '. ($userIcon ? 'style="background-image: url(\''. Uri::urnEncode($userIcon) . '\')" ' : ''). '></i>
			</div>
			<div class="telephony-report-call-dynamics-grid-user-name">
				' . htmlspecialcharsbx($userName) . '
			</div>
		</div>';
}

function getCompareLayout($value)
{
	if (is_null($value))
	{
		return '&mdash;';
	}

	$result = $value . '%';

	if ($value > 0)
	{
		$result = '<div class="telephony-report-call-dynamics-grid-value-up">' .
				  	htmlspecialcharsEx($result) .
				  '</div>';
	}
	elseif ($value < 0)
	{
		$result = '<div class="telephony-report-call-dynamics-grid-value-down">' .
				  	htmlspecialcharsEx($result) .
				  '</div>';
	}

	return $result;
}

function getValueCompareLayout($value, $compare)
{
	$compare = getCompareLayout($compare);

	if ($compare === '&mdash;')
	{
		return $value;
	}

	return
		'<div class="telephony-report-call-dynamics-grid-value-compare">'
		. $value .
		'</div>' . $compare;
}

function createLink($node, $url)
{
	return
		'<div class="telephony-report-call-dynamics-grid-value-clickable" data-target="'.htmlspecialcharsbx($url).'">'
		. $node .
		'</div>';
}

function wrapGridValue($node)
{
	return
		'<div class="telephony-report-call-dynamics-grid-value">'
		. $node .
		'</div>';
}

foreach ($arResult["GRID"]["ROWS"] as $index => $row)
{
	$columns = $row["columns"];

	$columns["EMPLOYEE"] = wrapGridValue(getUserLayout($columns["EMPLOYEE"]["valueFormatted"], $columns["EMPLOYEE"]["icon"]));
	$columns["INCOMING"] = wrapGridValue(createLink($columns["INCOMING"]["value"], $columns["INCOMING"]["url"]));
	$columns["OUTGOING"] = wrapGridValue(createLink($columns["OUTGOING"]["value"], $columns["OUTGOING"]["url"]));
	$columns["MISSED"] = wrapGridValue(createLink($columns["MISSED"]["value"], $columns["MISSED"]["url"]));
	$columns["CALLBACK"] = wrapGridValue(createLink($columns["CALLBACK"]["value"], $columns["CALLBACK"]["url"]));
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
	BX.message({
		'TELEPHONY_REPORT_CALL_DYNAMICS_HELP': '<?= GetMessageJS("TELEPHONY_REPORT_CALL_DYNAMICS_HELP")?>'
	});

	BX.Voximplant.Report.CallDynamics.init({
		gridId: '<?= CUtil::JSEscape($arResult["GRID"]["ID"])?>',
		widgetId: '<?= CUtil::JSEscape($arResult["WIDGET"]["ID"])?>',
		boardId: '<?= CUtil::JSEscape($arResult["BOARD"]["ID"])?>',
	});

	BX.ajax.UpdatePageData = function(){};
</script>
