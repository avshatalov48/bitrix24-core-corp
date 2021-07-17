<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

/** @global CMain $APPLICATION */
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public/crm/configs/face-tracker/index.php");
$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('FACEID_PUBLIC_PAGE_TITLE'));

$APPLICATION->includeComponent('bitrix:crm.control_panel', '',
	array(
		'ID' => 'CONFIG',
		'ACTIVE_ITEM_ID' => ''
	)
);

if (
	\Bitrix\Main\Loader::includeModule('crm')
	&& CCrmAuthorizationHelper::CheckConfigurationUpdatePermission()
)
{
	$APPLICATION->IncludeComponent(
		"bitrix:faceid.tracker.settings",
		".default"
	);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");