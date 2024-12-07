<?php

define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : [];
$siteId = (is_array($params) && isset($params['SITE_ID']))? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $params['SITE_ID']), 0, 2) : '';
if($siteId !== '')
{
	define('SITE_ID', $siteId);
}

$action = $_REQUEST['ACTION'] ?? '';

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Security\Sign\BadSignatureException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\Web\Json;

if(!function_exists('__CrmCompanyStExportEndResponse'))
{
	function __CrmCompanyStExportEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}
if(!function_exists('__CrmExportWriteDataToFile'))
{
	function __CrmExportWriteDataToFile($filePath, $data)
	{
		$file = fopen($filePath, 'ab');
		$fileSize = filesize($filePath);
		if(is_resource($file))
		{
			if($fileSize <= 0)
			{
				// add UTF-8 BOM marker
				fwrite($file, chr(239).chr(187).chr(191));
			}
			fwrite($file, $data);
			fclose($file);
			unset($file);
		}
	}
}

if (!is_string($siteId) || $siteId == '')
{
	__CrmCompanyStExportEndResponse(array('ERROR' => 'Site ID is not specified.'));
}


if (!CModule::IncludeModule('crm'))
{
	__CrmCompanyStExportEndResponse(array('ERROR' => 'Could not include crm module.'));
}

/** @global CMain $APPLICATION */
global $APPLICATION;

if ($action === 'STEXPORT')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmCompanyStExportEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$exportType = isset($params['EXPORT_TYPE']) ? $params['EXPORT_TYPE'] : '';
	if(!in_array($exportType, ['csv', 'excel'], true))
	{
		__CrmCompanyStExportEndResponse(
			array('ERROR' => "The export type '{$exportType}' is not supported in current context.")
		);
	}

	$entityTypeId = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeId === CCrmOwnerType::Undefined)
	{
		__CrmCompanyStExportEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeId !== CCrmOwnerType::Company)
	{
		__CrmCompanyStExportEndResponse(
			array('ERROR' => "The '{$entityTypeName}' type is not supported in current context.")
		);
	}

	if(!CCrmPerms::IsAuthorized() || !CCrmCompany::CheckExportPermission())
	{
		__CrmCompanyStExportEndResponse(array('ERROR' => 'Access denied.'));
	}

	$stepTimeInterval = 2;    // sec
	$stepStartTime = time();
	$defaultBlockSize = 100;   // items per block

	$processToken = isset($params['PROCESS_TOKEN']) ? $params['PROCESS_TOKEN'] : '';
	if($processToken === '')
	{
		__CrmCompanyStExportEndResponse(array('ERROR' => 'Process token is not specified.'));
	}

	$isMyCompanyMode = ($entityTypeId === CCrmOwnerType::Company && isset($params['MY_COMPANY_MODE'])
		&& $params['MY_COMPANY_MODE'] === 'Y');
	
	$cParams = is_array($params['COMPONENT_PARAMS']) ? $params['COMPONENT_PARAMS'] : [];

	$optionSuffix = $isMyCompanyMode ? 'mycompany' : 'company';
	$application = Application::getInstance();
	$localStorage = $application->getLocalSession('crm_stexport_' . $optionSuffix);
	$progressData = $localStorage->getData();
	$progressData = $progressData["progressData"] ?? [];

	if ($progressData)
	{
		try
		{
			$progressData = Json::decode((new Signer())->unsign($progressData));
		}
		catch (BadSignatureException|ArgumentException $exception)
		{
			$progressData = [];
		}
	}

	if (!is_array($progressData))
	{
		$progressData = [];
	}

	$lastToken = $progressData['PROCESS_TOKEN'] ?? '';
	$isNewToken = ($processToken !== $lastToken);
	$startTime = time();
	$initialOptions = ['REQUISITE_MULTILINE' => 'N'];
	if ($isNewToken)
	{
		$filePath = '';
		$processedItems = 0;
		$totalItems = 0;
		$blockSize = $defaultBlockSize;
		if (is_array($_REQUEST['INITIAL_OPTIONS'])
			&& isset($_REQUEST['INITIAL_OPTIONS']['REQUISITE_MULTILINE'])
			&& $_REQUEST['INITIAL_OPTIONS']['REQUISITE_MULTILINE'] === 'Y')
		{
			$initialOptions['REQUISITE_MULTILINE'] = 'Y';
		}
	}
	else
	{
		$filePath = isset($progressData['FILE_PATH']) ? $progressData['FILE_PATH'] : 0;
		$processedItems = isset($progressData['PROCESSED_ITEMS']) ? (int)$progressData['PROCESSED_ITEMS'] : 0;
		$totalItems = isset($progressData['TOTAL_ITEMS']) ? (int)$progressData['TOTAL_ITEMS'] : 0;
		$blockSize = isset($progressData['BLOCK_SIZE']) ? (int)$progressData['BLOCK_SIZE'] : $defaultBlockSize;
		if (is_array($progressData['INITIAL_OPTIONS'])
			&& isset($progressData['INITIAL_OPTIONS']['REQUISITE_MULTILINE'])
			&& $progressData['INITIAL_OPTIONS']['REQUISITE_MULTILINE'] === 'Y')
		{
			$initialOptions['REQUISITE_MULTILINE'] = 'Y';
		}
	}

	if (!is_string($filePath) || $filePath == '' || !CheckDirPath($filePath))
	{
		if (!$isNewToken)
		{
			$localStorage->clear();
			$processedItems = 0;
			$totalItems = 0;
			$blockSize = $defaultBlockSize;
		}

		if ($exportType === 'csv')
		{
			$fileExt = 'csv';
		}
		else
		{
			$fileExt = 'xls';
		}
		$fileName = "companies.{$fileExt}";
		$tempDir = $_SESSION['CRM_EXPORT_TEMP_DIR'] =
			CTempFile::GetDirectoryName(1, array('crm', uniqid('company_export_')));
		CheckDirPath($tempDir);
		$filePath = "{$tempDir}{$fileName}";

		// Save progress
		$progressData = array(
			'FILE_PATH' => $filePath,
			'PROCESS_TOKEN' => $processToken,
			'INITIAL_OPTIONS' => $initialOptions,
			'BLOCK_SIZE' => $blockSize,
			'PROCESSED_ITEMS' => $processedItems,
			'TOTAL_ITEMS' => $totalItems
		);
		$progressData = ['progressData' => (new Signer())->sign(Json::encode($progressData))];
		$localStorage->setData($progressData);
	}

	do
	{
		if ($processedItems < 0 || $totalItems < 0 || $totalItems < $processedItems
			|| ($processedItems > 0 && $totalItems === $processedItems))
		{
			__CrmCompanyStExportEndResponse(array('ERROR' => 'Progress data is incorrect.'));
		}

		$nextBlockNumber = (int)floor($processedItems / $blockSize) + 1;

		ob_start();
		$cResult = $APPLICATION->IncludeComponent(
			'bitrix:crm.company.list',
			'',
			array(
				'COMPANY_COUNT' => $blockSize,
				'PATH_TO_COMPANY_LIST' => isset($cParams['PATH_TO_COMPANY_LIST']) ? $cParams['PATH_TO_COMPANY_LIST'] : '',
				'PATH_TO_COMPANY_SHOW' => isset($cParams['PATH_TO_COMPANY_SHOW']) ? $cParams['PATH_TO_COMPANY_SHOW'] : '',
				'PATH_TO_COMPANY_EDIT' => isset($cParams['PATH_TO_COMPANY_EDIT']) ? $cParams['PATH_TO_COMPANY_EDIT'] : '',
				'PATH_TO_CONTACT_EDIT' => isset($cParams['PATH_TO_CONTACT_EDIT']) ? $cParams['PATH_TO_CONTACT_EDIT'] : '',
				'PATH_TO_DEAL_EDIT' => isset($cParams['PATH_TO_DEAL_EDIT']) ? $cParams['PATH_TO_DEAL_EDIT'] : '',
				'NAME_TEMPLATE' => isset($cParams['NAME_TEMPLATE']) ? $cParams['NAME_TEMPLATE'] : '',
				'MYCOMPANY_MODE' => isset($cParams['MYCOMPANY_MODE']) ?
					$cParams['MYCOMPANY_MODE'] : ($isMyCompanyMode ? 'Y' : 'N'),
				'NAVIGATION_CONTEXT_ID' => isset($cParams['NAVIGATION_CONTEXT_ID']) ?
					$cParams['NAVIGATION_CONTEXT_ID'] : 'COMPANY',
				'STEXPORT_MODE' => 'Y',
				'STEXPORT_INITIAL_OPTIONS' => $initialOptions,
				'STEXPORT_TOTAL_ITEMS' => $totalItems,
				'EXPORT_TYPE' => $exportType,
				'PAGE_NUMBER' => $nextBlockNumber
			)
		);
		$exportData = ob_get_contents();
		ob_end_clean();

		$processedItemsOnStep = 0;

		if (is_array($cResult))
		{
			if (isset($cResult['ERROR']))
			{
				__CrmCompanyStExportEndResponse(array('ERROR' => $cResult['ERROR']));
			}
			else
			{
				if (isset($cResult['PROCESSED_ITEMS']))
					$processedItemsOnStep = (int)$cResult['PROCESSED_ITEMS'];

				// Get total items quantity on 1st step.
				if ($nextBlockNumber === 1 && isset($cResult['TOTAL_ITEMS']))
					$totalItems = (int)$cResult['TOTAL_ITEMS'];
			}
		}

		if($processedItemsOnStep > 0)
		{
			$processedItems += $processedItemsOnStep;

			__CrmExportWriteDataToFile($filePath, $exportData);
		}
		unset($exportData);

		// Save progress
		$progressData = array(
			'FILE_PATH' => $filePath,
			'PROCESS_TOKEN' => $processToken,
			'INITIAL_OPTIONS' => $initialOptions,
			'BLOCK_SIZE' => $blockSize,
			'PROCESSED_ITEMS' => $processedItems,
			'TOTAL_ITEMS' => $totalItems
		);
		$progressData = ['progressData' => (new Signer())->sign(Json::encode($progressData))];
		$localStorage->setData($progressData);

		$stepTime = time() - $stepStartTime;
		$timeExceeded = ($stepTime < 0 || $stepTime >= $stepTimeInterval);

	} while (
		!$timeExceeded
		&& $processedItems < $totalItems
		&& $processedItemsOnStep > 0
		&& $processedItemsOnStep >= $blockSize
	);

	if($processedItems < $totalItems && $processedItemsOnStep > 0 && $processedItemsOnStep >= $blockSize)
	{
		__CrmCompanyStExportEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItems,
				'TOTAL_ITEMS' => $totalItems,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_LIST_STEXPORT_PROGRESS_SUMMARY',
					array(
						'#PROCESSED_ITEMS#' => $processedItems,
						'#TOTAL_ITEMS#' => $totalItems
					)
				)
			)
		);
	}
	else
	{
		$fileUrl = '/bitrix/components/bitrix/crm.company.list/stexport.php?type='.$exportType;
		CUserOptions::DeleteOption('crm', 'crm_stexport_'.$optionSuffix);
		__CrmCompanyStExportEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItems,
				'TOTAL_ITEMS' => $totalItems,
				'SUMMARY_HTML' => '<div>'.
					htmlspecialcharsbx(GetMessage('CRM_COMPANY_LIST_STEXPORT_COMPLETED_SUMMARY1')).'<br/>'.
					htmlspecialcharsbx(
						GetMessage(
							'CRM_COMPANY_LIST_STEXPORT_COMPLETED_SUMMARY2',
							array('#PROCESSED_ITEMS#' => $processedItems)
						)
					).'<br/><br/></div><div><a href="'.htmlspecialcharsbx($fileUrl).'">'.
					htmlspecialcharsbx(GetMessage('CRM_COMPANY_LIST_STEXPORT_DOWNLOAD')).'</a></div>'
			)
		);
	}
}
