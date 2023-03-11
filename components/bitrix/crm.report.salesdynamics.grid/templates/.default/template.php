<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use \Bitrix\Crm\Integration\Report\Handler\SalesDynamics\WonLostAmount;
use Bitrix\Main\Web\Uri;

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.icons',
	'ui.hint',
]);

$rows = [];

function getUserLayout($userName, $userIcon)
{
	return
		'<div class="crm-report-salesdynamics-grid-user">
			<div class="ui-icon ui-icon-common-user crm-report-salesdynamics-grid-user-icon">
				<i '. ($userIcon ? 'style="background-image: url(\''. Uri::urnEncode($userIcon) . '\')" ' : ''). '></i>
			</div>
			<div class="crm-report-salesdynamics-grid-user-name">
				' . $userName . '
			</div>
		</div>';
}

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

foreach ($arResult["GRID"]["ROWS"] as $index => $row)
{
	$columns = $row["columns"];
	$userId = (int)$columns["EMPLOYEE"]["ID"];
	$userName = htmlspecialcharsbx($columns["EMPLOYEE"]["NAME"]);
	$userPath = htmlspecialcharsbx($columns["EMPLOYEE"]["LINK"]);
	$userIcon = htmlspecialcharsbx($columns["EMPLOYEE"]["ICON"]);
	$wonAmount = $columns["WON_AMOUNT"];
	$lostAmount = $columns["LOST_AMOUNT"];
	$revenueDynamics = $columns["REVENUE_DYNAMICS"];
	$conversion = $columns["CONVERSION"];
	$losses = $columns["LOSSES"];

	$targetUrl = $arResult["TARGET_URL"][$userId];

	$columns["EMPLOYEE"] = getUserLayout($userName, $userIcon);
	$columns["WON_AMOUNT"] = wrapLink($wonAmount["TOTAL_FORMATTED"], $targetUrl[WonLostAmount::TOTAL_WON], ["crm-report-salesdynamics-bold"]);
	$columns["LOST_AMOUNT"] = wrapLink($lostAmount["TOTAL_FORMATTED"], $targetUrl[WonLostAmount::TOTAL_LOST], ["crm-report-salesdynamics-bold"]);
	$columns["REVENUE_DYNAMICS"] = getChangeLayout($revenueDynamics["TOTAL"]);
	$columns["CONVERSION"] = '<div class="crm-report-salesdynamics-bold">'.$conversion["TOTAL"] . "%".'</div>';
	$columns["LOSSES"] = '<div class="crm-report-salesdynamics-bold">'.$losses["TOTAL"] . "%".'</div>';

	$rows[] = [
		"id" => $row["id"] . "-1",
		"columns" => $columns,
		"actions" => []
	];

	$columns = [
			"EMPLOYEE" => '<div class="crm-report-salesdynamics-right">'.Loc::getMessage("CRM_REPORT_SALESDYNAMICS_PRIMARY").'</div>',
			"WON_AMOUNT" => wrapLink($wonAmount["PRIMARY_FORMATTED"], $targetUrl[WonLostAmount::PRIMARY_WON]),
			"LOST_AMOUNT" => wrapLink($lostAmount["PRIMARY_FORMATTED"], $targetUrl[WonLostAmount::PRIMARY_LOST]),
			"REVENUE_DYNAMICS" => getChangeLayout($revenueDynamics["PRIMARY"]),
			"CONVERSION" => $conversion["PRIMARY"]."%",
			"LOSSES" => $losses["PRIMARY"]."%",
	];

	$rows[] = [
		"id" => $row["id"] . "-2",
		"columns" => $columns,
		"actions" => []
	];

	$columns = [
		"EMPLOYEE" => '<div class="crm-report-salesdynamics-right">'.Loc::getMessage("CRM_REPORT_SALESDYNAMICS_REPEATED").'</div>',
		"WON_AMOUNT" => wrapLink($wonAmount["RETURN_FORMATTED"], $targetUrl[WonLostAmount::RETURN_WON]),
		"LOST_AMOUNT" => wrapLink($lostAmount["RETURN_FORMATTED"], $targetUrl[WonLostAmount::RETURN_LOST]),
		"REVENUE_DYNAMICS" => getChangeLayout($revenueDynamics["RETURN"]),
		"CONVERSION" => $conversion["RETURN"]."%",
		"LOSSES" => $losses["RETURN"]."%",
	];

	$rows[] = [
		"id" => $row["id"] . "-3",
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
	BX.message({
		'CRM_REPORT_SALESDYNAMICS_HELP': '<?= GetMessageJS("CRM_REPORT_SALESDYNAMICS_HELP")?>'
	});
	BX.Crm.Report.SalesDynamicGrid.init({
		gridId: '<?= CUtil::JSEscape($arResult["GRID"]["ID"])?>',
		widgetId: '<?= CUtil::JSEscape($arResult["WIDGET"]["ID"])?>',
		boardId: '<?= CUtil::JSEscape($arResult["BOARD"]["ID"])?>',
	});

	BX.ajax.UpdatePageData = function(){};
</script>