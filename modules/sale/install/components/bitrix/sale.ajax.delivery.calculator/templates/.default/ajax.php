<?
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$APPLICATION->IncludeComponent(
	'bitrix:sale.ajax.delivery.calculator',
	'',
	array(
		"AJAX_CALL" => "Y",
		"STEP" => intval($_REQUEST["STEP"]),
		"DELIVERY" => $_REQUEST["DELIVERY"],
		"PROFILE" => $_REQUEST["PROFILE"],
		"DELIVERY_ID" => $_REQUEST["DELIVERY_ID"],
		"ORDER_WEIGHT" => doubleval($_REQUEST["WEIGHT"]),
		"ORDER_PRICE" => doubleval($_REQUEST["PRICE"]),
		"LOCATION_TO" => intval($_REQUEST["LOCATION"]),
		"LOCATION_ZIP" => $_REQUEST["LOCATION_ZIP"],
		"CURRENCY" => $_REQUEST["CURRENCY"],
		"TEMP" => $_REQUEST["TEMP"],
		"ITEMS" => $_REQUEST["ITEMS"],
		"EXTRA_PARAMS" => isset($_REQUEST["EXTRA_PARAMS"]) ? $_REQUEST["EXTRA_PARAMS"] : array(),
		"ORDER_DATA" => isset($_REQUEST["ORDER_DATA"]) ? $_REQUEST["ORDER_DATA"] : array()
	),
	null,
	array('HIDE_ICONS' => 'Y')
);

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>