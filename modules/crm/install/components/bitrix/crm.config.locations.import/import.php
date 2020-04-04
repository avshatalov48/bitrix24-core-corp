<?
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule('crm'))
	return;

$CrmPerms = new CCrmPerms($USER->GetID());

if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	echo GetMessage('CRM_LOC_IMP_ERROR_ACCESS_DENIED');
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
	die();
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/location_import.php");

$arImportParams = array(
	'STEP' => intval($_REQUEST['STEP']),
	'CSVFILE' => $_REQUEST['CSVFILE'],
	'LOADZIP' => $_REQUEST['LOADZIP'],
	'SYNC' => $_REQUEST['SYNC'],
	'STEP_LENGTH' => $_REQUEST['STEP_LENGTH'],
	'DLZIPFILE' => 'zip_ussr.csv'
);

if(isset($_REQUEST['TMP_PATH']))
	$arImportParams['TMP_PATH'] = $_REQUEST['TMP_PATH'];

$arImportResult = saleLocationImport($arImportParams);

$arImportResult = $APPLICATION->ConvertCharsetArray($arImportResult, SITE_CHARSET, 'utf-8');

echo json_encode($arImportResult);

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>