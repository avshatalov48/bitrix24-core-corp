<?
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/extranet/contacts/.left.menu.php");

$aMenuLinks = Array(
	Array(
		GetMessage("MENU_CONTACT"),
		"/extranet/contacts/index.php",
		Array(),
		Array(),
		""
	),
	Array(
		GetMessage("MENU_EMPLOYEE"),
		"/extranet/contacts/employees.php",
		Array(),
		Array(),
		""
	)
);
?>