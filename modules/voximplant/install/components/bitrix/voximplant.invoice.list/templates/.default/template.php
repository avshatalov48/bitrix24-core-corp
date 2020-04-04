<?
/**
 * Global variables
 * @var array $arResult
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load(['ui.viewer']);

\Bitrix\UI\Toolbar\Facade\Toolbar::addFilter([
	'GRID_ID' => $arResult["GRID_ID"],
	'FILTER_ID' => $arResult["FILTER_ID"],
	'FILTER' => $arResult["FILTER"],
	'FILTER_PRESETS' => [],
	'DISABLE_SEARCH' => true,
	'ENABLE_LIVE_SEARCH' => false,
	'ENABLE_LABEL' => false,
	'RESET_TO_DEFAULT_MODE' => false,
]);

\Bitrix\UI\Toolbar\Facade\Toolbar::addButton([
	"text" => Loc::getMessage("VOX_INVOICES_REQUEST_ORIGINAL"),
	"color" => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
	"click" => new \Bitrix\UI\Buttons\JsHandler(
		"BX.Voximplant.Invoices.showClosingDocumentsRequest",
		"BX.Voximplant.Invoices"
	),
]);

?>
<div id="tel-stat-grid-container">
<?
	$APPLICATION->IncludeComponent(
		"bitrix:main.ui.grid",
		"",
		array(
			"GRID_ID" => $arResult["GRID_ID"],
			"HEADERS" => $arResult["HEADERS"],
			"ROWS" => $arResult["ROWS"],
			"NAV_OBJECT" => $arResult["NAV_OBJECT"],
			"SORT" => $arResult["SORT"],
			"ALLOW_COLUMNS_SORT" => true,
			"ALLOW_SORT" => true,
			"ALLOW_PIN_HEADER" => true,
			"SHOW_PAGINATION" => true,
			"SHOW_PAGESIZE" => true,
			"SHOW_ROW_CHECKBOXES" => false,
			"SHOW_CHECK_ALL_CHECKBOXES" => false,
			"SHOW_SELECTED_COUNTER" => false,
			"PAGE_SIZES" => array(
				array("NAME" => "10", "VALUE" => "10"),
				array("NAME" => "20", "VALUE" => "20"),
				array("NAME" => "50", "VALUE" => "50"),
				array("NAME" => "100", "VALUE" => "100"),
			),
			'SHOW_ACTION_PANEL' => true,
			"TOTAL_ROWS_COUNT" => $arResult["ROWS_COUNT"],
			"AJAX_MODE" => "Y",
			"AJAX_ID" => CAjax::GetComponentID('bitrix:voximplant.invoice.list', '.default', ''),
			"AJAX_OPTION_JUMP" => "N",
			"AJAX_OPTION_HISTORY" => "N",
		),
		$component, array("HIDE_ICONS" => "Y")
	);
?>
</div>

<script>
	BX.Voximplant.Invoices.init({
		downloadUrlTemplate: '<?=CUtil::JSEscape($arResult['DOWNLOAD_URL'])?>'
	});
</script>
