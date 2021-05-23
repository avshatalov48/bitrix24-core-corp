<?
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!CModule::IncludeModule('crm'))
	return;

IncludeModuleLangFile(__FILE__);

$CrmPerms = new CCrmPerms($USER->GetID());

if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	echo GetMessage('CRM_LOC_IMP_LOAD_ACCESS_DENIED');
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
	die();
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/location_import.php");

$arLoadParams = array(
	'STEP' => intval($_REQUEST['STEP']),
	'CSVFILE' => $_REQUEST['CSVFILE'],
	'TMP_PATH' => $_REQUEST['TMP_PATH'],
	'LOADZIP' => $_REQUEST['LOADZIP'],
	'DLSERVER' => 'www.1c-bitrix.ru',
	'DLPORT' => 80,
	'DLPATH' => '/download/files/locations/',
	'DLMETHOD' => 'GET',
	'DLZIPFILE' => 'zip_ussr.csv'
);

$arLoadResult = saleLocationLoadFile($arLoadParams);

$arLoadResult = $APPLICATION->ConvertCharsetArray($arLoadResult, SITE_CHARSET, 'utf-8');

echo json_encode($arLoadResult);

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>