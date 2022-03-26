<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/face-tracker/index.php");
$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('FACEID_PUBLIC_PAGE_TITLE'));

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:faceid.tracker',
		'POPUP_COMPONENT_PARAMS' => [
			'LIMIT' => '30'
		],
	]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");