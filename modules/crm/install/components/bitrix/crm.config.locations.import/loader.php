<?php

use Bitrix\Main\Loader;

/** @global CUser $USER */
/** @global CMain $APPLICATION */

const STOP_STATISTICS = true;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!Loader::includeModule('crm'))
{
	return;
}

IncludeModuleLangFile(__FILE__);

$CrmPerms = new CCrmPerms($USER->GetID());

if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	echo GetMessage('CRM_LOC_IMP_LOAD_ACCESS_DENIED');
	require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
	die();
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/sale/general/location_import.php");

$arLoadParams = [
	'STEP' => (int)($_REQUEST['STEP'] ?? 0),
	'CSVFILE' => (string)($_REQUEST['CSVFILE'] ?? ''),
	'TMP_PATH' => (string)($_REQUEST['TMP_PATH'] ?? ''),
	'LOADZIP' => $_REQUEST['LOADZIP'] ?? '',
	'DLSERVER' => 'https://www.1c-bitrix.ru',
	'DLPORT' => null,
	'DLPATH' => '/download/files/locations/',
	'DLMETHOD' => 'GET',
	'DLZIPFILE' => 'zip_ussr.csv',
];

$arLoadResult = saleLocationLoadFile($arLoadParams);

echo json_encode($arLoadResult);

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
