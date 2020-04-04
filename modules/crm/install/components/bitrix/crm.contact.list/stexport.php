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

	$errorOccured = false;

	$exportType = isset($_REQUEST['type']) ? strtolower($_REQUEST['type']) : '';
	if($exportType === 'csv' || $exportType === 'excel')
	{
		if ($exportType === 'csv')
		{
			$exportFileExt = 'csv';
		}
		else
		{
			$exportFileExt = 'xls';
		}
		$exportFileDir = isset($_SESSION['CRM_EXPORT_TEMP_DIR']) ? $_SESSION['CRM_EXPORT_TEMP_DIR'] : '';
		$exportFileName = "contacts.{$exportFileExt}";
		$exportFilePath = $exportFileDir !== '' ? "{$exportFileDir}{$exportFileName}" : '';

		if($exportFilePath !== '' && file_exists($exportFilePath))
		{
			$file = fopen($exportFilePath, 'rb');
			if(is_resource($file))
			{
				$fileSize = filesize($exportFilePath);
				fclose($file);
				unset($file);
				if ($fileSize !== false)
				{
					while (ob_get_level() > 0)
					{
						ob_end_clean();
					}

					Header('Content-Type: text/csv; charset='.LANG_CHARSET);
					Header("Content-Disposition: attachment;filename={$exportFileName}");
					Header('Content-Type: application/octet-stream');
					Header('Content-Transfer-Encoding: binary');
					Header('Content-Length: '.$fileSize);

					readfile($exportFilePath);
				}
				else
				{
					\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
					$errMsg = GetMessage('CRM_CONTACT_LIST_STEXPORT_ERR_GET_FILE_SIZE').PHP_EOL;
					$errorOccured = true;
				}
			}
		}
		else
		{
			\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
			$errMsg = GetMessage('CRM_CONTACT_LIST_STEXPORT_ERR_FILE_NOT_FOUND').PHP_EOL;
			$errorOccured = true;
		}
	}
	else
	{
		\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
		$errMsg = GetMessage('CRM_CONTACT_LIST_STEXPORT_ERR_INVALID_TYPE').PHP_EOL;
		$errorOccured = true;
	}

	if ($errorOccured)
	{
		$bom = '';
		if (defined('BX_UTF') && BX_UTF)
			$bom = chr(239).chr(187).chr(191);
		$fileSize = strlen($errMsg) + strlen($bom);

		while (ob_get_level() > 0)
		{
			ob_end_clean();
		}

		Header('Content-Type: text/csv; charset='.LANG_CHARSET);
		Header("Content-Disposition: attachment;filename=error.txt");
		Header('Content-Type: application/octet-stream');
		Header('Content-Transfer-Encoding: binary');
		Header('Content-Length: '.$fileSize);

		if ($bom !== '')
			echo $bom;
		echo $errMsg;
	}
}
?>
