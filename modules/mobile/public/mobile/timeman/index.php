<?
define("BX_DONT_INCLUDE_MOBILE_TEMPLATE_CSS", "Y");
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

?><?$APPLICATION->IncludeComponent(
	'bitrix:timeman',
	'mobile',
	array(),
	null
);?><?

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
