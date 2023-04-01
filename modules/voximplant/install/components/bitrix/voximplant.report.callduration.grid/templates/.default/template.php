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
		'<div class="telephony-report-call-duration-grid-user">
			<div class="ui-icon ui-icon-common-user telephony-report-call-duration-grid-user-icon">
				<i '. ($userIcon ? 'style="background-image: url(\''. Uri::urnEncode($userIcon) . '\')" ' : ''). '></i>
			</div>
			<div class="telephony-report-call-duration-grid-user-name">
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
		$result = '<div class="telephony-report-call-duration-grid-value-up">' .
				  	htmlspecialcharsEx($result) .
				  '</div>';
	}
	elseif ($value < 0)
	{
		$result = '<div class="telephony-report-call-duration-grid-value-down">' .
				  	htmlspecialcharsEx($result) .
				  '</div>';
	}

	return $result;
}

function createLink($node, $url)
{
	return
		'<div class="telephony-report-call-duration-grid-value-clickable" data-target="'.htmlspecialcharsbx($url).'">'
			. $node .
		'</div>';
}

function wrap($node)
{
	return
		'<div class="telephony-report-call-duration-grid-value">'
			. $node .
		'</div>';
}

foreach ($arResult["GRID"]["ROWS"] as $index => $row)
{
	$employeeName = $row["columns"]["EMPLOYEE"]["valueFormatted"];
	$employeeIcon = $row["columns"]["EMPLOYEE"]["icon"];
	$incomingDuration = $row["columns"]["INCOMING_DURATION"]["valueFormatted"];
	$incomingDurationUrl = $row["columns"]["INCOMING_DURATION"]["url"];
	$outgoingDuration = $row["columns"]["OUTGOING_DURATION"]["valueFormatted"];
	$outgoingDurationUrl = $row["columns"]["OUTGOING_DURATION"]["url"];
	$incomingDynamics = $row["columns"]["INCOMING_DYNAMICS"]["value"];
	$outgoingDynamics = $row["columns"]["OUTGOING_DYNAMICS"]["value"];

	$columns["EMPLOYEE"] = wrap(getUserLayout($employeeName, $employeeIcon));
	$columns["INCOMING_DURATION"] = wrap($incomingDuration == null ? '&mdash;' : createLink($incomingDuration, $incomingDurationUrl));
	$columns["INCOMING_DYNAMICS"] = wrap(getCompareLayout($incomingDynamics));
	$columns["OUTGOING_DURATION"] = wrap($outgoingDuration == null ? '&mdash;' : createLink($outgoingDuration, $outgoingDurationUrl));
	$columns["OUTGOING_DYNAMICS"] = wrap(getCompareLayout($outgoingDynamics));

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
	BX.Voximplant.Report.CallDurationGrid.init({
		gridId: '<?= CUtil::JSEscape($arResult["GRID"]["ID"])?>',
		widgetId: '<?= CUtil::JSEscape($arResult["WIDGET"]["ID"])?>',
		boardId: '<?= CUtil::JSEscape($arResult["BOARD"]["ID"])?>',
	});

	BX.ajax.UpdatePageData = function(){};
</script>
