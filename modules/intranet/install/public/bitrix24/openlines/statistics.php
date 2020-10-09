<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/public_bitrix24/openlines/statistics.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_after.php');

$APPLICATION->SetTitle(GetMessage('OL_PAGE_STATISTICS_DETAIL_TITLE'));
?>
<?$APPLICATION->IncludeComponent(
	'bitrix:imopenlines.menu.top',
	'',
	[
		'COMPONENT_BASE_DIR' => '/openlines/',
	],
	false
);?>
<?$APPLICATION->IncludeComponent('bitrix:imopenlines.statistics.detail', '', ['LIMIT' => '30']);?>

<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>
