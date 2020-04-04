<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(CModule::IncludeModule('crm'))
{
	global $APPLICATION;

	$fileName = isset($_REQUEST['name']) ? strtolower($_REQUEST['name']) : '';
	if($fileName === 'errata')
	{
		$errataFileDir = isset($_SESSION['CRM_IMPORT_TEMP_DIR']) ? $_SESSION['CRM_IMPORT_TEMP_DIR'] : '';
		$errataFilePath = $errataFileDir !== '' ? "{$errataFileDir}errata.csv" : '';

		if($errataFilePath !== '' && file_exists($errataFilePath))
		{
			$file = fopen($errataFilePath, 'rb');
			if(is_resource($file))
			{
				$APPLICATION->RestartBuffer();

				Header('Content-Type: text/csv; charset='.LANG_CHARSET);
				Header('Content-Disposition: attachment;filename=errata.csv');
				Header('Content-Type: application/octet-stream');
				Header('Content-Transfer-Encoding: binary');

				echo fread($file, filesize($errataFilePath));
				fclose($file);
				unset($file);
			}
		}
	}
	elseif($fileName === 'duplicate')
	{
		$duplicateFileDir = isset($_SESSION['CRM_IMPORT_TEMP_DIR']) ? $_SESSION['CRM_IMPORT_TEMP_DIR'] : '';
		$duplicateFilePath = $duplicateFileDir !== '' ? "{$duplicateFileDir}duplicate.csv" : '';

		if($duplicateFilePath !== '' && file_exists($duplicateFilePath))
		{
			$file = fopen($duplicateFilePath, 'rb');
			if(is_resource($file))
			{
				$APPLICATION->RestartBuffer();

				Header('Content-Type: text/csv; charset='.LANG_CHARSET);
				Header('Content-Disposition: attachment;filename=duplicate.csv');
				Header('Content-Type: application/octet-stream');
				Header('Content-Transfer-Encoding: binary');

				echo fread($file, filesize($duplicateFilePath));
				fclose($file);
				unset($file);
			}
		}
	}
}
?>
