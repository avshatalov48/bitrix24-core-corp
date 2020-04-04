<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent(
	"bitrix:bizproc.workflow.instances",
	"",
	array(
		"SET_TITLE" => 'Y',
		"NAME_TEMPLATE" => CSite::GetNameFormat(),
	),
	$component
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");