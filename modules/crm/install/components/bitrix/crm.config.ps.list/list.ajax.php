<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmPSListEndResponse'))
{
	function __CrmPSListEndResponse($result)
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

if (!CModule::IncludeModule('crm'))
{
	__CrmPSListEndResponse(array('ERROR' => 'Could not include crm module.'));
}

use Bitrix\Crm;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	__CrmPSListEndResponse(array('ERROR' => 'Access denied.'));
}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if ($action === 'SKIP_CONVERT_PS_REQUISITES')
{
	$converter = new Crm\Requisite\Conversion\PSRequisiteConverter();
	$converter->skipConvert();
}
elseif ($action === 'CONVERT_PS_REQUISITES')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!(CCrmCompany::CheckCreatePermission() && CCrmCompany::CheckUpdatePermission(0)
		&& CCrmCompany::CheckReadPermission(0) && $userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE')))
	{
		__CrmPSListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_TRANSFER_PS_PARAMS_TO_REQUISITES', 'N') !== 'Y')
	{
		__CrmPSListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_PS_REQUISITES_TRANSFER_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	if (!CModule::IncludeModule('sale'))
	{
		__CrmPSListEndResponse(array('ERROR' => 'Could not include sale module.'));
	}

	$converter = new Crm\Requisite\Conversion\PSRequisiteConverter();
	try
	{
		$converter->convert();
	}
	catch(Exception $e)
	{
		__CrmPSListEndResponse(array('ERROR' => $e->getMessage()));
	}

	$progressData = $converter::getProgressData();

	if (!is_array($progressData))
		__CrmPSListEndResponse(array('ERROR' => 'Invalid progress data.'));

	if (isset($progressData['PS_COMPLETE']) && $progressData['PS_COMPLETE'] === 'Y')
	{
		$converter->complete();
		__CrmPSListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'SUMMARY' => GetMessage('CRM_PS_REQUISITES_TRANSFER_COMPLETED_SUMMARY')
			)
		);
	}
	else
	{
		$presetsProcessed = (isset($progressData['PRESETS']) && $progressData['PRESETS'] === 'Y') ? 'Y' : 'N';
		$requisiteProcessed = (isset($progressData['REQUISITE']) && $progressData['REQUISITE'] === 'Y') ? 'Y' : 'N';
		$invoicesInProgress = (isset($progressData['NEXT_INVOICE_ID'])) ? 'Y' : 'N';
		$summary = '...';
		
		if ($invoicesInProgress === 'Y')
		{
			$processedItemQty = isset($progressData['COUNT_INVOICE_UPDATED']) ?
				(int)$progressData['COUNT_INVOICE_UPDATED'] : 0;
			$totalItemQty = isset($progressData['COUNT_INVOICE']) ? (int)$progressData['COUNT_INVOICE'] : 0;
			$summary = GetMessage(
				'CRM_PS_REQUISITES_TRANSFER_PROGRESS_SUMMARY3',
				array(
					'#PROCESSED_ITEMS#' => $processedItemQty,
					'#TOTAL_ITEMS#' => $totalItemQty
				)
			);
			__CrmPSListEndResponse(
				array(
					'STATUS' => 'PROGRESS',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => $summary
				)
			);
		}
		
		if ($presetsProcessed === 'Y' && $requisiteProcessed !== 'Y')
		{
			$summary = GetMessage('CRM_PS_REQUISITES_TRANSFER_PROGRESS_SUMMARY1');
		}
		else if ($requisiteProcessed === 'Y')
		{
			$summary = GetMessage('CRM_PS_REQUISITES_TRANSFER_PROGRESS_SUMMARY2');
		}

		__CrmPSListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'SUMMARY' => $summary
			)
		);
	}
}
?>