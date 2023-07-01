<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('DisableEventsCheck', true);

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
/**
 * AGENTS ARE REQUIRED FOR FOLLOWING ACTIONS:
 * 	REBUILD SEARCH INDEX
 * 	BUILD TIMELINE
 */
define(
	'NO_AGENT_CHECK',
	!in_array(
		$action,
		[
			'REBUILD_SEARCH_CONTENT',
			'BUILD_TIMELINE',
			'REFRESH_ACCOUNTING',
			'REBUILD_SEMANTICS',
			'REBUILD_CONVERSION_STATISTICS',
			'REBUILD_SECURITY_ATTRS',
			'BACKGROUND_INDEX_REBUILD',
			'BACKGROUND_MERGE',
			'BACKGROUND_DUP_VOL_DATA_PREPARE',
		],
		true
	)
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmLeadListEndResponse'))
{
	function __CrmLeadListEndResponse($result)
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
	__CrmLeadListEndResponse(array('ERROR' => 'Could not include crm module.'));
}

use Bitrix\Crm\Agent\Duplicate\Background\LeadIndexRebuild;
use Bitrix\Crm\Agent\Duplicate\Background\LeadMerge;
use Bitrix\Crm\Agent\Duplicate\Volatile\IndexRebuild;
use Bitrix\Crm\Conversion\EntityConversionException;
use Bitrix\Crm\Conversion\LeadConversionConfig;
use Bitrix\Crm\Conversion\LeadConversionType;
use Bitrix\Crm\Conversion\LeadConversionWizard;
use Bitrix\Crm\Integrity\Volatile;
use Bitrix\Crm\Synchronization\UserFieldSynchronizer;
use Bitrix\Main\Localization\Loc;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	__CrmLeadListEndResponse(array('ERROR' => 'Access denied.'));
}

if (isset($_REQUEST['MODE']) && $_REQUEST['MODE'] === 'SEARCH')
{
	Loc::loadMessages(__FILE__);

	if(!CCrmLead::CheckReadPermission(0, $userPerms))
	{
		__CrmLeadListEndResponse(array('ERROR' => 'Access denied.'));
	}

	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	// Limit count of items to be found
	$nPageTop = 50;		// 50 items by default
	if (isset($_REQUEST['LIMIT_COUNT']) && ($_REQUEST['LIMIT_COUNT'] >= 0))
	{
		$rawNPageTop = (int) $_REQUEST['LIMIT_COUNT'];
		if ($rawNPageTop === 0)
			$nPageTop = false;		// don't limit
		elseif ($rawNPageTop > 0)
			$nPageTop = $rawNPageTop;
	}

	$arData = array();
	$search = trim($_REQUEST['VALUE']);
	if (!empty($search))
	{
		$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y' ? true : false;
		$arFilter = array();
		if (is_numeric($search))
		{
			$arFilter['ID'] = (int)$search;
			$arFilter['%TITLE'] = $search;
			$arFilter['LOGIC'] = 'OR';
		}
		else if (preg_match('/(.*)\[(\d+?)\]/i' . BX_UTF_PCRE_MODIFIER, $search, $arMatches))
		{
			$arFilter['ID'] = (int)$arMatches[2];
			$searchString = trim($arMatches[1]);
			if (is_string($searchString) && $searchString !== '')
			{
				$arFilter['%TITLE'] = $searchString;
				$arFilter['%FULL_NAME'] = $searchString;
				$arFilter['LOGIC'] = 'OR';
			}
			unset($searchString);
		}
		else
		{
			$arFilter['%TITLE'] = trim($search);
			$arFilter['%FULL_NAME'] = trim($search);
			$arFilter['LOGIC'] = 'OR';
		}

		$arSelect = array('ID', 'TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'STATUS_ID');
		$arOrder = array('TITLE' => 'ASC');
		$obRes = CCrmLead::GetListEx($arOrder, $arFilter, false, array('nTopCount' => $nPageTop), $arSelect);
		$arFiles = array();
		$i = 0;
		$leadIndex = array();
		while ($arRes = $obRes->Fetch())
		{
			$arData[$i] =
				array(
					'id' => $multi ? 'L_' . $arRes['ID'] : $arRes['ID'],
					'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_lead_show'),
						array(
							'lead_id' => $arRes['ID']
						)
					),
					'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
					'desc' => CCrmLead::PrepareFormattedName(
						array(
							'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
							'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
							'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
							'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
						)
					),
					'type' => 'lead'
				);
			$leadIndex[$arRes['ID']] = &$arData[$i];
			$i++;
		}

		// advanced info - phone number, e-mail
		if (!empty($leadIndex))
		{
			$obRes = CCrmFieldMulti::GetList(
				['ID' => 'asc'],
				['ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => array_keys($leadIndex)]
			);
			while ($arRes = $obRes->Fetch())
			{
				if (
					isset($leadIndex[$arRes['ELEMENT_ID']])
					&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL')
				)
				{
					$item = &$leadIndex[$arRes['ELEMENT_ID']];
					if (!is_array($item['advancedInfo']))
					{
						$item['advancedInfo'] = [];
					}
					if (!is_array($item['advancedInfo']['multiFields']))
					{
						$item['advancedInfo']['multiFields'] = [];
					}
					$item['advancedInfo']['multiFields'][] = [
						'ID' => $arRes['ID'],
						'TYPE_ID' => $arRes['TYPE_ID'],
						'VALUE_TYPE' => $arRes['VALUE_TYPE'],
						'VALUE' => $arRes['VALUE']
					];
					unset($item);
				}
			}
		}
		unset($leadIndex);
	}

	__CrmLeadListEndResponse($arData);
}
elseif ($action === 'REBUILD_SEARCH_CONTENT')
{
	$agent = \Bitrix\Crm\Agent\Search\LeadSearchContentRebuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isActive())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmLeadListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmLeadListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'REFRESH_ACCOUNTING')
{
	$agent = \Bitrix\Crm\Agent\Accounting\LeadAccountSyncAgent::getInstance();
	if($agent->isEnabled() && !$agent->isRegistered())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmLeadListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmLeadListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'BUILD_TIMELINE')
{
	$agent = \Bitrix\Crm\Agent\Timeline\LeadTimelineBuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isActive())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmLeadListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmLeadListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'REBUILD_SECURITY_ATTRS')
{
	$agent = \Bitrix\Crm\Agent\Security\LeadAttributeRebuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isRegistered())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmLeadListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmLeadListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'SAVE_PROGRESS' && check_bitrix_sessid())
{
	$ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
	$typeName = isset($_REQUEST['TYPE']) ? $_REQUEST['TYPE'] : '';
	$statusID = isset($_REQUEST['VALUE']) ? $_REQUEST['VALUE'] : '';

	$targetTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Lead);
	if($statusID === '' || $ID <= 0  || $typeName !== $targetTypeName)
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Invalid data!')
		);
		die();
	}

	$entityAttrs = $userPerms->GetEntityAttr($targetTypeName, array($ID));
	if (!$userPerms->CheckEnityAccess($targetTypeName, 'WRITE', $entityAttrs[$ID]))
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Access denied!')
		);
		die();
	}

	$arPreviousFields = CCrmLead::GetByID($ID, false);

	if(!is_array($arPreviousFields))
	{
		$APPLICATION->RestartBuffer();
		echo CUtil::PhpToJSObject(
			array('ERROR' => 'Not found!')
		);
		die();
	}

	if(isset($arPreviousFields['STATUS_ID']) && $arPreviousFields['STATUS_ID'] === $statusID)
	{
		__CrmLeadListEndResponse(array('TYPE' => $targetTypeName, 'ID' => $ID, 'VALUE' => $statusID));
	}

	$arFields = array('STATUS_ID' => $statusID);
	$CCrmLead = new CCrmLead(false);
	if($CCrmLead->Update(
		$ID,
		$arFields,
		true,
		true,
		array(/*'DISABLE_USER_FIELD_CHECK' => true,*/ 'REGISTER_SONET_EVENT' => true))
	)
	{
		$arErrors = array();
		CCrmBizProcHelper::AutoStartWorkflows(
			CCrmOwnerType::Lead,
			$ID,
			CCrmBizProcEventType::Edit,
			$arErrors
		);

		//Region automation
		$starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $ID);
		$starter->setUserIdFromCurrent()->runOnUpdate(['STATUS_ID' => $statusID], []);
		//end region

		__CrmLeadListEndResponse(array('TYPE' => $targetTypeName, 'ID' => $ID, 'VALUE' => $statusID));
	}
	else
	{
		$checkExceptions = $CCrmLead->GetCheckExceptions();
		$errorMessage = $entity->LAST_ERROR;
		$responseData = array(
			'TYPE' => CCrmOwnerType::LeadName,
			'ID' => $ID,
			'VALUE' => isset($arPreviousFields['STATUS_ID']) ? $arPreviousFields['STATUS_ID'] : ''
		);
		if(!empty($checkExceptions))
		{
			$checkErrors = array();
			foreach($checkExceptions as $exception)
			{
				if($exception instanceof \CAdminException)
				{
					foreach($exception->GetMessages() as $message)
					{
						$checkErrors[$message['id']] = $message['text'];
					}
				}
			}
			$responseData['CHECK_ERRORS'] = $checkErrors;
			$responseData['CONTEXT'] = array('STATUS_ID' => $statusID);
		}

		__CrmLeadListEndResponse($responseData);
	}

	__CrmLeadListEndResponse(array('TYPE' => $targetTypeName, 'ID' => $ID, 'VALUE' => $statusID));
}
elseif ($action === 'REBUILD_DUPLICATE_INDEX')
{
	Loc::loadMessages(__FILE__);

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmLeadListEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmLeadListEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Lead)
	{
		__CrmLeadListEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if(!CCrmLead::CheckUpdatePermission(0))
	{
		__CrmLeadListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_DUP_INDEX', 'N') !== 'Y')
	{
		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_LEAD_LIST_REBUILD_DUPLICATE_INDEX_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_DUP_INDEX_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();

	if(empty($progressData) && intval(\Bitrix\Crm\BusinessTypeTable::getCount()) === 0)
	{
		//Try to fill BusinessTypeTable on first iteration
		\Bitrix\Crm\BusinessTypeTable::installDefault();
	}

	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmLead::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmLead::GetListEx(
		array('ID' => 'ASC'),
		$filter,
		false,
		array('nTopCount' => 100),
		array('ID')
	);

	$itemIDs = array();
	$itemQty = 0;
	if(is_object($dbResult))
	{
		while($fields = $dbResult->Fetch())
		{
			$itemIDs[] = intval($fields['ID']);
			$itemQty++;
		}
	}

	if($itemQty > 0)
	{
		CCrmLead::RebuildDuplicateIndex($itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_LEAD_DUP_INDEX_PROGRESS', serialize($progressData));
		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_LEAD_LIST_REBUILD_DUPLICATE_INDEX_PROGRESS_SUMMARY',
					array(
						'#PROCESSED_ITEMS#' => $processedItemQty,
						'#TOTAL_ITEMS#' => $totalItemQty
					)
				)
			)
		);
	}
	else
	{
		COption::RemoveOption('crm', '~CRM_REBUILD_LEAD_DUP_INDEX');
		COption::RemoveOption('crm', '~CRM_REBUILD_LEAD_DUP_INDEX_PROGRESS');
		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_LEAD_LIST_REBUILD_DUPLICATE_INDEX_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'REBUILD_STATISTICS')
{
	//~CRM_REBUILD_LEAD_STATISTICS
	Loc::loadMessages(__FILE__);

	if(!CCrmLead::CheckUpdatePermission(0))
	{
		__CrmLeadListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_STATISTICS', 'N') !== 'Y')
	{
		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_LEAD_LIST_REBUILD_STATISTICS_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_STATISTICS_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmLead::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmLead::GetListEx(
		array('ID' => 'ASC'),
		$filter,
		false,
		array('nTopCount' => 200),
		array('ID')
	);

	$itemIDs = array();
	$itemQty = 0;
	if(is_object($dbResult))
	{
		while($fields = $dbResult->Fetch())
		{
			$itemIDs[] = (int)$fields['ID'];
			$itemQty++;
		}
	}

	if($itemQty > 0)
	{
		CCrmLead::RebuildStatistics($itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_LEAD_STATISTICS_PROGRESS', serialize($progressData));
		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_LEAD_LIST_REBUILD_STATISTICS_PROGRESS_SUMMARY',
					array(
						'#PROCESSED_ITEMS#' => $processedItemQty,
						'#TOTAL_ITEMS#' => $totalItemQty
					)
				)
			)
		);
	}
	else
	{
		COption::RemoveOption('crm', '~CRM_REBUILD_LEAD_STATISTICS');
		COption::RemoveOption('crm', '~CRM_REBUILD_LEAD_STATISTICS_PROGRESS');
		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_LEAD_LIST_REBUILD_STATISTICS_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'REBUILD_SUM_STATISTICS')
{
	//~CRM_REBUILD_LEAD_SUM_STATISTICS
	Loc::loadMessages(__FILE__);

	if(!CCrmLead::CheckUpdatePermission(0))
	{
		__CrmLeadListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_SUM_STATISTICS', 'N') !== 'Y')
	{
		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_LEAD_LIST_REBUILD_STATISTICS_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_SUM_STATISTICS_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmLead::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmLead::GetListEx(
		array('ID' => 'ASC'),
		$filter,
		false,
		array('nTopCount' => 20),
		array('ID')
	);

	$itemIDs = array();
	$itemQty = 0;
	if(is_object($dbResult))
	{
		while($fields = $dbResult->Fetch())
		{
			$itemIDs[] = (int)$fields['ID'];
			$itemQty++;
		}
	}

	if($itemQty > 0)
	{
		CCrmLead::RebuildStatistics(
			$itemIDs,
			array(
				'FORCED' => true,
				'ENABLE_SUM_STATISTICS' => true,
				'ENABLE_HISTORY'=> false,
				'ENABLE_ACTIVITY_STATISTICS' => false
			)
		);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_LEAD_SUM_STATISTICS_PROGRESS', serialize($progressData));
		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_LEAD_LIST_REBUILD_STATISTICS_PROGRESS_SUMMARY',
					array(
						'#PROCESSED_ITEMS#' => $processedItemQty,
						'#TOTAL_ITEMS#' => $totalItemQty
					)
				)
			)
		);
	}
	else
	{
		COption::RemoveOption('crm', '~CRM_REBUILD_LEAD_SUM_STATISTICS');
		COption::RemoveOption('crm', '~CRM_REBUILD_LEAD_SUM_STATISTICS_PROGRESS');
		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_LEAD_LIST_REBUILD_STATISTICS_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'REBUILD_CONVERSION_STATISTICS')
{
	$agent = \Bitrix\Crm\Agent\Statistics\LeadConversionStatisticsRebuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isRegistered())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmLeadListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmLeadListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS']
		)
	);
}
elseif ($action === 'REBUILD_SEMANTICS')
{
	$agent = \Bitrix\Crm\Agent\Semantics\LeadSemanticsRebuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isRegistered())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmLeadListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmLeadListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS']
		)
	);
}
elseif ($action === 'GET_ROW_COUNT')
{
	Loc::loadMessages(__FILE__);

	if(!CCrmLead::CheckReadPermission(0, $userPerms))
	{
		__CrmLeadListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmLeadListEndResponse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];
	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
	$result = CCrmLead::GetListEx(array(), $filter, array(), false, array(), array());

	$text = '';
	if(is_numeric($result))
	{
		$text = GetMessage('CRM_LEAD_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
		if($text === '')
		{
			$text = $result;
		}
	}
	__CrmLeadListEndResponse(array('DATA' => array('TEXT' => $text)));
}
elseif ($action === 'DELETE' && check_bitrix_sessid())
{
	Loc::loadMessages(__FILE__);

	if(!CCrmLead::CheckDeletePermission(0, $userPerms))
	{
		__CrmLeadListEndResponse(array('ERROR' => GetMessage('CRM_LEAD_LIST_DELETION_ACCESS_ERROR')));
	}

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmLeadListEndResponse(array('ERROR' => GetMessage('CRM_LEAD_LIST_DELETION_PARAM_ERROR')));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmLeadListEndResponse(array('ERROR' => GetMessage('CRM_LEAD_LIST_DELETION_PARAM_ERROR')));
	}

	if($entityTypeID !== CCrmOwnerType::Lead)
	{
		__CrmLeadListEndResponse(array('ERROR' => GetMessage('CRM_LEAD_LIST_DELETION_PARAM_ERROR')));
	}

	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if($gridID === '')
	{
		__CrmLeadListEndResponse(array('ERROR' => GetMessage('CRM_LEAD_LIST_DELETION_PARAM_ERROR')));
	}

	$contextID = isset($params['CONTEXT_ID']) ? $params['CONTEXT_ID'] : '';
	if($contextID === '')
	{
		__CrmLeadListEndResponse(array('ERROR' => GetMessage('CRM_LEAD_LIST_DELETION_PARAM_ERROR')));
	}

	$progressData = isset($_SESSION['CRM_LEAD_DELETE_PROGRESS'])
	&& isset($_SESSION['CRM_LEAD_DELETE_PROGRESS'][$contextID])
	&& is_array($_SESSION['CRM_LEAD_DELETE_PROGRESS'][$contextID])
		? $_SESSION['CRM_LEAD_DELETE_PROGRESS'][$contextID] : array();

	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? $progressData['LAST_ITEM_ID'] : 0;
	$totalItemQty = 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? $progressData['PROCESSED_ITEMS'] : 0;

	$processAll = isset($params['PROCESS_ALL']) && mb_strtoupper($params['PROCESS_ALL']) === 'Y';
	$slotLength = 5;
	$effectiveItemIDs = array();
	if(!$processAll)
	{
		$entityIDs = isset($params['ENTITY_IDS']) && is_array($params['ENTITY_IDS']) ? $params['ENTITY_IDS'] : array();
		if(empty($entityIDs))
		{
			__CrmLeadListEndResponse(array('ERROR' => 'Entity IDs are not specified.'));
		}
		$totalItemQty = count($entityIDs);

		sort($entityIDs, SORT_NUMERIC);
		$index = $lastItemID > 0 ? array_search($lastItemID, $entityIDs) : false;
		if(is_int($index) && $index >= 0)
		{
			$index++;
		}
		else
		{
			$index = 0;
		}

		$effectiveItemIDs = array_slice($entityIDs, $index, $slotLength);
		for($i = 0; $i < $slotLength; $i++)
		{
			if(!CCrmLead::CheckDeletePermission($effectiveItemIDs[$i], $userPerms))
			{
				unset($effectiveItemIDs[$i]);
			}
		}
	}
	else
	{
		$userFilterHash = isset($params['USER_FILTER_HASH']) ? $params['USER_FILTER_HASH'] : '';
		if($userFilterHash === '')
		{
			__CrmLeadListEndResponse(array('ERROR' => 'Filter hash is not specified.'));
		}

		$filter = \Bitrix\Crm\Context\GridContext::getFilter($gridID);
		$filterHash = \Bitrix\Crm\Context\GridContext::getFilterHash($gridID);

		if(!is_array($filter) || $filterHash === '')
		{
			__CrmLeadListEndResponse(array('ERROR' => GetMessage('CRM_LEAD_LIST_DELETION_FILTER_NOT_FOUND_ERROR')));
		}

		if($filterHash !== $userFilterHash)
		{
			__CrmLeadListEndResponse(array('ERROR' => GetMessage('CRM_LEAD_LIST_DELETION_FILTER_OUTDATED_ERROR')));
		}

		$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? $progressData['TOTAL_ITEMS'] : 0;
		if($totalItemQty <= 0)
		{
			$totalItemQty = CCrmLead::GetListEx(array(), $filter, array(), false, array(), array());
		}

		if($lastItemID > 0)
		{
			$filter['>ID'] = $lastItemID;
		}

		$result = CCrmLead::GetListEx(
			array('ID' => 'ASC'),
			$filter,
			false,
			array('nTopCount' => $slotLength),
			array('ID')
		);

		if(is_object($result))
		{
			while($fields = $result->Fetch())
			{
				$itemID = (int)$fields['ID'];
				if(CCrmLead::CheckDeletePermission($itemID, $userPerms))
				{
					$effectiveItemIDs[] = $itemID;
				}
			}
		}
	}

	$entity = new CCrmLead(false);
	$bizProc = new CCrmBizProc(CCrmOwnerType::LeadName);
	$itemQty = 0;

	if(!empty($effectiveItemIDs))
	{
		foreach($effectiveItemIDs as $itemID)
		{
			$itemID = (int)$itemID;

			$bizProc->Delete($itemID, $userPerms->GetEntityAttr(CCrmOwnerType::LeadName, array($itemID)));
			$entity->Delete($itemID, array('CHECK_DEPENDENCIES' => true, 'PROCESS_BIZPROC' => false));

			$lastItemID = $itemID;
			$itemQty++;
		}
	}

	if($itemQty > 0)
	{
		$processedItemQty += $itemQty;
		$_SESSION['CRM_LEAD_DELETE_PROGRESS'][$contextID] = array(
			'LAST_ITEM_ID' => $lastItemID,
			'PROCESSED_ITEMS' => $processedItemQty,
			'TOTAL_ITEMS' => $totalItemQty
		);

		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_LEAD_LIST_DELETION_PROGRESS_SUMMARY',
					array(
						'#PROCESSED_ITEMS#' => $processedItemQty,
						'#TOTAL_ITEMS#' => $totalItemQty
					)
				)
			)
		);
	}
	else
	{
		__CrmLeadListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_LEAD_LIST_DELETION_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'PREPARE_BATCH_CONVERSION' && check_bitrix_sessid())
{
	CUtil::JSPostUnescape();

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	$IDs = isset($params['IDS']) && is_array($params['IDS']) ? $params['IDS'] : array();
	$enableUserFieldCheck = !isset($params['ENABLE_USER_FIELD_CHECK'])
		|| mb_strtoupper($params['ENABLE_USER_FIELD_CHECK']) === 'Y';
	$enableConfigCheck = !isset($params['ENABLE_CONFIG_CHECK'])
		|| mb_strtoupper($params['ENABLE_CONFIG_CHECK']) === 'Y';

	$configParams = isset($params['CONFIG']) && is_array($params['CONFIG']) ? $params['CONFIG'] : array();
	$config = new \Bitrix\Crm\Conversion\LeadConversionConfig();
	$config->fromJavaScript($configParams);

	$progressData = array(
		'CONFIG' => $config->externalize(),
		'ENABLE_CONFIG_CHECK' => $enableConfigCheck,
		'ENABLE_USER_FIELD_CHECK' => $enableUserFieldCheck,
		'CURRENT_ENTITY_INDEX' => 0,
		'CURRENT_ENTITY_ID' => 0,
		'PROCESSED_ENTITIES' => 0
	);

	if(!empty($IDs))
	{
		$progressData['ENTITY_IDS'] = $IDs;
		$progressData['TOTAL_ENTITIES'] = count($IDs);
	}
	else
	{
		if (isset($params['FILTER']) && is_array($params['FILTER']))
		{
			CCrmLead::PrepareFilter($params['FILTER']);
		}
		$filter = isset($params['FILTER']) && is_array($params['FILTER'])
			? $params['FILTER']
			: []
		;

		if(empty($filter))
		{
			$filter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
				\Bitrix\Crm\Filter\Factory::getSettingsByGridId(CCrmOwnerType::Lead, (string)$gridID)
			)->getValue();
		}

		$progressData['FILTER'] = $filter;
		$progressData['TOTAL_ENTITIES'] = \CCrmLead::GetListEx(
			array(),
			$filter,
			array(),
			false
		);
	}

	if (!isset($_SESSION['CRM_LEAD_BATCH_CONVERSION_DATA']))
	{
		$_SESSION['CRM_LEAD_BATCH_CONVERSION_DATA'] = array();
	}
	$_SESSION['CRM_LEAD_BATCH_CONVERSION_DATA'][$gridID] = $progressData;

	$errors = array();
	$settings = \Bitrix\Crm\Settings\ConversionSettings::getCurrent();
	if (!$settings->isAutocreationEnabled())
	{
		$errors[] = GetMessage('CRM_LEAD_LIST_BATCH_CONVERSION_AUTOCREATION_DISABLED');
	}

	$needForSync = false;
	$entityConfigs = $config->getItems();
	$syncFieldNames = array();
	foreach ($entityConfigs as $entityTypeID => $entityConfig)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if (!CCrmAuthorizationHelper::CheckCreatePermission($entityTypeName, $currentUserPermissions) && !CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, 0, $currentUserPermissions))
		{
			continue;
		}

		$isActive = $entityConfig->isActive();

		if($isActive
			&& \CCrmBizProcHelper::HasParameterizedAutoWorkflows($entityTypeID, \CCrmBizProcEventType::Create)
		)
		{
			$ex = new EntityConversionException(
				\CCrmOwnerType::Lead,
				$entityTypeID,
				EntityConversionException::TARG_DST,
				EntityConversionException::HAS_WORKFLOWS
			);
			$errors[] = $ex->getLocalizedMessage();
		}

		$enableSync = $isActive;
		if ($enableSync)
		{
			$syncFields = UserFieldSynchronizer::getSynchronizationFields(CCrmOwnerType::Lead, $entityTypeID);
			$enableSync = !empty($syncFields);
			foreach ($syncFields as $field)
			{
				$syncFieldNames[$field['ID']] = UserFieldSynchronizer::getFieldLabel($field);
			}
		}

		if ($enableSync && !$needForSync)
		{
			$needForSync = true;
		}
		$entityConfig->enableSynchronization($enableSync);
	}

	$status = 'READY';
	if(!empty($errors))
	{
		$status = 'ERROR';
	}

	if($needForSync)
	{
		$status = 'REQUIRES_SYNCHRONIZATION';
	}

	Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead.list/templates/.default/template.php');

	__CrmLeadListEndResponse(
		[
			'DATA' => [
				'STATUS' => $status,
				'REQUIRES_SYNCHRONIZATION' => $needForSync,
				'CONFIG' => $config->toJavaScript(),
				'FIELD_NAMES' => array_values($syncFieldNames),
				'ERRORS' => $errors,
				'messages' =>  [
					'accessDenied' =>  Loc::getMessage('CRM_LEAD_CONV_ACCESS_DENIED'),
					'generalError' =>  Loc::getMessage('CRM_LEAD_CONV_GENERAL_ERROR'),
					'dialogTitle' =>  Loc::getMessage('CRM_LEAD_CONV_DIALOG_TITLE'),
					'syncEditorLegend' =>  Loc::getMessage('CRM_LEAD_CONV_DIALOG_SYNC_LEGEND'),
					'syncEditorFieldListTitle' =>  Loc::getMessage('CRM_LEAD_CONV_DIALOG_SYNC_FILED_LIST_TITLE'),
					'syncEditorEntityListTitle' =>  Loc::getMessage('CRM_LEAD_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE'),
					'continueButton' =>  Loc::getMessage('CRM_LEAD_CONV_DIALOG_CONTINUE_BTN'),
					'cancelButton' =>  Loc::getMessage('CRM_LEAD_CONV_DIALOG_CANCEL_BTN'),
					'selectButton' =>  Loc::getMessage('CRM_LEAD_CONV_ENTITY_SEL_BTN'),
					'openEntitySelector' =>  Loc::getMessage('CRM_LEAD_CONV_OPEN_ENTITY_SEL'),
					'entitySelectorTitle' =>  Loc::getMessage('CRM_LEAD_CONV_ENTITY_SEL_TITLE'),
					'contact' =>  Loc::getMessage('CRM_LEAD_CONV_ENTITY_SEL_CONTACT'),
					'company' =>  Loc::getMessage('CRM_LEAD_CONV_ENTITY_SEL_COMPANY'),
					'noresult' =>  Loc::getMessage('CRM_LEAD_CONV_ENTITY_SEL_SEARCH_NO_RESULT'),
					'search' =>  Loc::getMessage('CRM_LEAD_CONV_ENTITY_SEL_SEARCH'),
					'last' =>  Loc::getMessage('CRM_LEAD_CONV_ENTITY_SEL_LAST'),
				],
			],
		]
	);
}
elseif ($action === 'STOP_BATCH_CONVERSION' && check_bitrix_sessid())
{
	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';

	if($gridID !== '' && isset($_SESSION['CRM_LEAD_BATCH_CONVERSION_DATA']))
	{
		unset($_SESSION['CRM_LEAD_BATCH_CONVERSION_DATA'][$gridID]);
	}

	__CrmLeadListEndResponse(array('STATUS' => 'SUCCESS'));
}
elseif ($action === 'PROCESS_BATCH_CONVERSION' && check_bitrix_sessid())
{
	CUtil::JSPostUnescape();

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	$configParams = isset($params['CONFIG']) && is_array($params['CONFIG']) ? $params['CONFIG'] : array();
	$config = new LeadConversionConfig();
	$config->fromJavaScript($configParams);

	$progressData = isset($_SESSION['CRM_LEAD_BATCH_CONVERSION_DATA']) && isset($_SESSION['CRM_LEAD_BATCH_CONVERSION_DATA'][$gridID])
		? $_SESSION['CRM_LEAD_BATCH_CONVERSION_DATA'][$gridID] : null;

	if(!is_array($progressData))
	{
		__CrmLeadListEndResponse(array('STATE' => 'ERROR', 'ERROR' => 'NOT FOUND'));
	}

	$enableUserFieldCheck = isset($progressData['ENABLE_USER_FIELD_CHECK'])
		? (bool)$progressData['ENABLE_USER_FIELD_CHECK'] : true;

	$enableConfigCheck = isset($progressData['ENABLE_CONFIG_CHECK'])
		? (bool)$progressData['ENABLE_CONFIG_CHECK'] : true;

	$entityIDs = isset($progressData['ENTITY_IDS']) && is_array($progressData['ENTITY_IDS'])
		? $progressData['ENTITY_IDS'] : array();
	$filter = isset($progressData['FILTER']) && is_array($progressData['FILTER'])
		? $progressData['FILTER'] : array();

	$processedQty = isset($progressData['PROCESSED_ENTITIES']) ? (int)$progressData['PROCESSED_ENTITIES'] : 0;
	$entityQty = isset($progressData['TOTAL_ENTITIES']) ? (int)$progressData['TOTAL_ENTITIES'] : 0;

	$currentEntityIndex = isset($progressData['CURRENT_ENTITY_INDEX']) ? (int)$progressData['CURRENT_ENTITY_INDEX'] : 0;
	$currentEntityID = isset($progressData['CURRENT_ENTITY_ID']) ? (int)$progressData['CURRENT_ENTITY_ID'] : 0;

	if($currentEntityID <= 0)
	{
		if(!empty($entityIDs))
		{
			$currentEntityIndex = 0;
			$currentEntityID = $entityIDs[0];
		}
		else
		{
			$dbResult = \CCrmLead::GetListEx(
				array('ID' => 'ASC'),
				$filter,
				false,
				array('nTopCount' => 1),
				array('ID')
			);
			$fields = $dbResult->Fetch();
			if(is_array($fields))
			{
				$currentEntityID = (int)$fields['ID'];
			}
		}
	}

	$entityConfigs = $config->getItems();
	foreach($entityConfigs as $entityTypeID => $entityConfig)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if(!CCrmAuthorizationHelper::CheckCreatePermission($entityTypeName, $currentUserPermissions)
			&& !CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, 0, $currentUserPermissions))
		{
			continue;
		}

		if(!$entityConfig->isActive())
		{
			continue;
		}

		if(!UserFieldSynchronizer::needForSynchronization(CCrmOwnerType::Lead, $entityTypeID))
		{
			continue;
		}

		if($entityConfig->isSynchronizationEnabled())
		{
			UserFieldSynchronizer::synchronize(\CCrmOwnerType::Lead, $entityTypeID);
		}
		else
		{
			UserFieldSynchronizer::markAsSynchronized(\CCrmOwnerType::Lead, $entityTypeID);
		}
	}

	$config->setTypeID(LeadConversionType::resolveByEntityID($currentEntityID));
	if($enableConfigCheck && !$config->isSupported())
	{
		$errorText = GetMessage('CRM_LEAD_LIST_BATCH_CONVERSION_CONFIG_IS_NOT_SUPPORTED');
	}
	else
	{
		$wizard = new LeadConversionWizard($currentEntityID, $config);
		if(!$enableUserFieldCheck)
		{
			$wizard->enableUserFieldCheck(false);
		}

		$errorText = '';
		if(!$wizard->execute())
		{
			$errorText = $wizard->getErrorText();
			$wizard->undo();
		}
		LeadConversionWizard::remove($currentEntityID);
	}

	$processedQty++;

	$resultData = array(
		'PROCESSED_ITEMS' => $processedQty,
		'TOTAL_ITEMS' => $entityQty
	);

	if($errorText !== '')
	{
		CCrmOwnerType::TryGetEntityInfo(
			CCrmOwnerType::Lead,
			$currentEntityID,
			$entityInfo,
			false
		);

		$resultData['ERRORS'] = array(array('message' => $errorText, 'customData' => array('info' => $entityInfo)));
	}

	$isInProgress = true;
	if(!empty($entityIDs))
	{
		if($currentEntityIndex < ($entityQty - 1))
		{
			$currentEntityIndex++;
			$currentEntityID = $entityIDs[$currentEntityIndex];
		}
		else
		{
			$isInProgress = false;
		}
	}
	else
	{
		$filter['>ID'] = $currentEntityID;
		$dbResult = \CCrmLead::GetListEx(
			array('ID' => 'ASC'),
			$filter,
			false,
			array('nTopCount' => 1),
			array('ID')
		);
		$fields = $dbResult->Fetch();
		if(is_array($fields))
		{
			$currentEntityID = (int)$fields['ID'];
		}
		else
		{
			$isInProgress = false;
		}
	}
	$resultData['STATUS'] = $isInProgress ? 'PROGRESS' : 'COMPLETED';

	$progressData['PROCESSED_ENTITIES'] = $processedQty;
	$progressData['CURRENT_ENTITY_INDEX'] = $currentEntityIndex;
	$progressData['CURRENT_ENTITY_ID'] = $currentEntityID;

	$_SESSION['CRM_LEAD_BATCH_CONVERSION_DATA'][$gridID] = $progressData;

	__CrmLeadListEndResponse($resultData);
}
elseif ($action === 'CHECK_ACTIVE_LEAD')
{
	$existActiveLeads = 'N';

	$dbRes = \CCrmLead::GetListEx(array('DATE_CREATE' => 'desc'), array("STATUS_SEMANTIC_ID" => \Bitrix\Crm\PhaseSemantics::PROCESS), false, array("nPageSize" => 1), array("ID"));
	$dbRes->NavStart(1, false);
	if ($dbRes->GetNext())
	{
		$existActiveLeads = 'Y';
	}

	__CrmLeadListEndResponse(array('EXIST_LEADS' => $existActiveLeads));
}
elseif ($action === 'BACKGROUND_INDEX_REBUILD')
{
	$userId = CCrmSecurityHelper::GetCurrentUserID();
	$isNeedToShowDupIndexProcess = false;
	$agent = LeadIndexRebuild::getInstance($userId);
	if ($agent->isActive())
	{
		$state = $agent->state()->getData();
		if (isset($state['STATUS']) && $state['STATUS'] === LeadIndexRebuild::STATUS_RUNNING)
		{
			$isNeedToShowDupIndexProcess = true;
		}
	}

	if(!$isNeedToShowDupIndexProcess)
	{
		__CrmLeadListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	__CrmLeadListEndResponse(
		[
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => (int)round(100 * $state['PROCESSED_ITEMS'] / $state['TOTAL_ITEMS']),
			'TOTAL_ITEMS' => 100,
		]
	);
}
elseif ($action === 'BACKGROUND_MERGE')
{
	$userId = CCrmSecurityHelper::GetCurrentUserID();
	$isNeedToShowDupMergeProcess = false;
	$agent = LeadMerge::getInstance($userId);
	if ($agent->isActive())
	{
		$state = $agent->state()->getData();
		if (isset($state['STATUS']) && $state['STATUS'] === LeadMerge::STATUS_RUNNING)
		{
			$isNeedToShowDupMergeProcess = true;
		}
	}

	if(!$isNeedToShowDupMergeProcess)
	{
		__CrmLeadListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	__CrmLeadListEndResponse(
		[
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => (int)round(100 * $state['PROCESSED_ITEMS'] / $state['FOUND_ITEMS']),
			'TOTAL_ITEMS' => 100,
		]
	);
}
elseif ($action === 'BACKGROUND_DUP_VOL_DATA_PREPARE')
{
	$isNeedToShowDupVolDataPrepare = false;
	$typeInfo = Volatile\TypeInfo::getInstance()->getIdsByEntityTypes([CCrmOwnerType::Lead]);
	$stateMap = [];
	if (isset($typeInfo[CCrmOwnerType::Lead]))
	{
		foreach ($typeInfo[CCrmOwnerType::Lead] as $id)
		{
			$agent = IndexRebuild::getInstance($id);
			if ($agent->isActive())
			{
				$state = $agent->state()->getData();
				/** @noinspection PhpClassConstantAccessedViaChildClassInspection */
				if (isset($state['STATUS']) && $state['STATUS'] === IndexRebuild::STATUS_RUNNING)
				{
					$stateMap[$id] = $state;
					$isNeedToShowDupVolDataPrepare = true;
				}
			}
		}
	}

	if(!$isNeedToShowDupVolDataPrepare)
	{
		__CrmLeadListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$percentageSum = 0;
	$percentageCount = 0;
	foreach ($stateMap as $state)
	{
		$percentage = (int)round(
			100 * $state['PROGRESS_VARS']['PROCESSED_ITEMS'] / $state['PROGRESS_VARS']['TOTAL_ITEMS']
		);
		$percentage = ($percentage > 100) ? 100 : $percentage;
		$percentageSum += $percentage;
		$percentageCount++;
	}

	$percentage = (int)round($percentageSum / $percentageCount);
	$percentage = ($percentage > 100) ? 100 : $percentage;

	__CrmLeadListEndResponse(
		[
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $percentage,
			'TOTAL_ITEMS' => 100,
		]
	);
}
