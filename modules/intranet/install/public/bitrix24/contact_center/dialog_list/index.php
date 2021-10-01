<?
global $APPLICATION;
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intranet/public_bitrix24/contact_center/dialog_list/index.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_after.php');

$APPLICATION->SetTitle(\Bitrix\Main\Localization\Loc::getMessage('OL_PAGE_STATISTICS_DETAIL_TITLE_NEW'));
?>
<?$APPLICATION->IncludeComponent(
	'bitrix:intranet.contact_center.menu.top',
	'',
	[
		'COMPONENT_BASE_DIR' => '/contact_center/',
		'SECTION_ACTIVE' => 'dialog_list'
	],
	false
);?>
<?$APPLICATION->IncludeComponent(
	'bitrix:imopenlines.statistics.detail',
	'',
	['LIMIT' => '30']
);?>
<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>
