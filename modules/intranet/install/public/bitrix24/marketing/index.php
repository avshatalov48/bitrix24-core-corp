<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sender.start',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'PATH_TO_LETTER_ADD' => '/marketing/letter/edit/0/',
			'IS_CRM_MARKETING_TITLE' => isset($_REQUEST['marketing_title']) && $_REQUEST['marketing_title'] === 'Y',
		]
	]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");