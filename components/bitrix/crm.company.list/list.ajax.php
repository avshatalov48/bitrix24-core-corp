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
			'BUILD_DUPLICATE_INDEX',
			'CONVERT_ADDRESSES',
			'CONVERT_UF_ADDRESSES'
		],
		true
	)
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmCompanyListEndResponse'))
{
	function __CrmCompanyListEndResponse($result)
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
	__CrmCompanyListEndResponse(array('ERROR' => 'Could not include crm module.'));
}

use Bitrix\Crm;
use Bitrix\Crm\Agent\Requisite\CompanyAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\CompanyUfAddressConvertAgent;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	__CrmCompanyListEndResponse(array('ERROR' => 'Access denied.'));
}

if ($_REQUEST['MODE'] == 'SEARCH')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmCompany::CheckReadPermission(0, $userPerms))
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Access denied.'));
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

	$requireRequisiteData = (
		is_array($_REQUEST['OPTIONS']) && isset($_REQUEST['OPTIONS']['REQUIRE_REQUISITE_DATA'])
		&& $_REQUEST['OPTIONS']['REQUIRE_REQUISITE_DATA'] === 'Y'
	);

	$onlyMyCompanies = (
		is_array($_REQUEST['OPTIONS']) && isset($_REQUEST['OPTIONS']['ONLY_MY_COMPANIES'])
		&& $_REQUEST['OPTIONS']['ONLY_MY_COMPANIES'] === 'Y'
	);
	$notMyCompanies = (
		is_array($_REQUEST['OPTIONS']) && isset($_REQUEST['OPTIONS']['NOT_MY_COMPANIES'])
		&& $_REQUEST['OPTIONS']['NOT_MY_COMPANIES'] === 'Y'
	);

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
				$arFilter['LOGIC'] = 'OR';
			}
		}
		else
		{
			$arFilter['%TITLE'] = $search;
		}

		$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
		$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');

		if ($onlyMyCompanies)
		{
			$arFilter = array(
				'LOGIC' => 'AND',
				'=IS_MY_COMPANY' => 'Y',
				$arFilter
			);
		}
		else if ($notMyCompanies)
		{
			$arFilter = array(
				'LOGIC' => 'AND',
				'=IS_MY_COMPANY' => 'N',
				$arFilter
			);
		}

		$arSelect = array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO');
		$arOrder = array('TITLE' => 'ASC', 'LAST_NAME' => 'ASC', 'NAME' => 'ASC');
		$obRes = CCrmCompany::GetList($arOrder, $arFilter, $arSelect, $nPageTop);
		$arImages = array();
		$arLargeImages = array();
		$i = 0;
		$companyIndex = array();
		while ($arRes = $obRes->Fetch())
		{
			$logoID = intval($arRes['LOGO']);
			if ($logoID > 0 && !isset($arImages[$logoID]))
			{
				$arImages[$logoID] = CFile::ResizeImageGet($logoID, array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
				$arLargeImages[$logoID] = CFile::ResizeImageGet($logoID, array('width' => 38, 'height' => 38), BX_RESIZE_IMAGE_EXACT);
			}

			$arDesc = Array();
			if (isset($arCompanyTypeList[$arRes['COMPANY_TYPE']]))
				$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
			if (isset($arCompanyIndustryList[$arRes['INDUSTRY']]))
				$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];
			$arData[$i] =
				array(
					'id' => $multi ? 'CO_' . $arRes['ID'] : $arRes['ID'],
					'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'),
						array(
							'company_id' => $arRes['ID']
						)
					),
					'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
					'desc' => implode(', ', $arDesc),
					'image' => isset($arImages[$logoID]['src']) ? $arImages[$logoID]['src'] : '',
					'largeImage' => isset($arLargeImages[$logoID]['src']) ? $arLargeImages[$logoID]['src'] : '',
					'type' => 'company'
				);

			// requisites
			if ($requireRequisiteData)
				$arData[$i]['advancedInfo']['requisiteData'] = CCrmEntitySelectorHelper::PrepareRequisiteData(
					CCrmOwnerType::Company, $arRes['ID'], array('VIEW_DATA_ONLY' => true)
				);

			$companyIndex[$arRes['ID']] = &$arData[$i];
			$i++;
		}

		// advanced info - phone number, e-mail
		$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => array_keys($companyIndex)));
		while ($arRes = $obRes->Fetch())
		{
			if (isset($companyIndex[$arRes['ELEMENT_ID']])
				&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL'))
			{
				$item = &$companyIndex[$arRes['ELEMENT_ID']];
				if (!is_array($item['advancedInfo']))
					$item['advancedInfo'] = array();
				if (!is_array($item['advancedInfo']['multiFields']))
					$item['advancedInfo']['multiFields'] = array();
				$item['advancedInfo']['multiFields'][] = array(
					'ID' => $arRes['ID'],
					'TYPE_ID' => $arRes['TYPE_ID'],
					'VALUE_TYPE' => $arRes['VALUE_TYPE'],
					'VALUE' => $arRes['VALUE']
				);
				unset($item);
			}
		}
		unset($companyIndex);
	}

	__CrmCompanyListEndResponse($arData);
}
elseif ($action === 'REBUILD_SEARCH_CONTENT')
{
	$agent = \Bitrix\Crm\Agent\Search\CompanySearchContentRebuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isActive())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmCompanyListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmCompanyListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'BUILD_TIMELINE')
{
	$agent = \Bitrix\Crm\Agent\Timeline\CompanyTimelineBuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isActive())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmCompanyListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmCompanyListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'BUILD_DUPLICATE_INDEX')
{
	$agent = \Bitrix\Crm\Agent\Duplicate\CompanyDuplicateIndexRebuildAgent::getInstance();
	$isAgentEnabled = $agent->isEnabled();
	if ($isAgentEnabled)
	{
		if (!$agent->isActive())
		{
			$agent->enable(false);
			$isAgentEnabled = false;
		}
	}
	if(!$isAgentEnabled)
	{
		__CrmCompanyListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmCompanyListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'REBUILD_DUPLICATE_INDEX')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Company)
	{
		__CrmCompanyListEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if(!CCrmCompany::CheckUpdatePermission(0))
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX', 'N') !== 'Y')
	{
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_COMPANY_LIST_REBUILD_DUPLICATE_INDEX_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX_PROGRESS',  '');
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
		$totalItemQty = CCrmCompany::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmCompany::GetListEx(
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
			$itemIDs[] = intval($fields['ID']);
			$itemQty++;
		}
	}

	if($itemQty > 0)
	{
		CCrmCompany::RebuildDuplicateIndex($itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX_PROGRESS', serialize($progressData));
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_LIST_REBUILD_DUPLICATE_INDEX_PROGRESS_SUMMARY',
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
		COption::RemoveOption('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX');
		COption::RemoveOption('crm', '~CRM_REBUILD_COMPANY_DUP_INDEX_PROGRESS');
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_LIST_REBUILD_DUPLICATE_INDEX_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'REBUILD_ACT_STATISTICS')
{
	//~CRM_REBUILD_COMPANY_ACT_STATISTICS
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmCompany::CheckUpdatePermission(0))
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_ACT_STATISTICS', 'N') !== 'Y')
	{
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_COMPANY_LIST_REBUILD_ACT_STATISTICS_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_ACT_STATISTICS_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmCompany::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmCompany::GetListEx(
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
		Crm\Activity\CommunicationStatistics::rebuild(\CCrmOwnerType::Company, $itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_COMPANY_ACT_STATISTICS_PROGRESS', serialize($progressData));
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_LIST_REBUILD_ACT_STATISTICS_PROGRESS_SUMMARY',
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
		COption::RemoveOption('crm', '~CRM_REBUILD_COMPANY_ACT_STATISTICS');
		COption::RemoveOption('crm', '~CRM_REBUILD_COMPANY_ACT_STATISTICS_PROGRESS');
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_LIST_REBUILD_ACT_STATISTICS_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'GET_ROW_COUNT')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmCompany::CheckReadPermission(0, $userPerms))
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmCompanyListEndResponse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];
	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
	$result = CCrmCompany::GetListEx(array(), $filter, array(), false, array(), array());

	$text = '';
	if(is_numeric($result))
	{
		$text = GetMessage('CRM_COMPANY_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
		if($text === '')
		{
			$text = $result;
		}
	}
	__CrmCompanyListEndResponse(array('DATA' => array('TEXT' => $text)));
}
elseif ($action === 'GET_REQUISITE_PRESETS')
{
	$entityTypeName = isset($_POST['ENTITY_TYPE_NAME']) ? $_POST['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Company)
	{
		__CrmCompanyListEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if(!CCrmCompany::CheckReadPermission(0))
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$presetEntity = new \Bitrix\Crm\EntityPreset();
	$dbResult = $presetEntity->getList(
		array(
			'order' => array('SORT' => 'ASC'),
			'filter' => array('ENTITY_TYPE_ID' => CCrmOwnerType::Requisite, 'ACTIVE' => 'Y'),
			'select' => array('ID', 'NAME')
		)
	);

	$items = array();
	while($item = $dbResult->fetch())
	{
		$items[] = $item;
	}

	__CrmCompanyListEndResponse(
		array(
			'ACTION' => 'GET_REQUISITE_PRESETS',
			'RESULT' => array('ITEMS' => $items)
		)
	);
}
elseif ($action === 'BUILD_REQUISITES')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Company)
	{
		__CrmCompanyListEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	$presetID = isset($params['PRESET_ID']) ? (int)$params['PRESET_ID'] : 0;
	if($presetID <= 0)
	{
		__CrmCompanyListEndResponse(array('ERROR' => "Preset ID is not specified."));
	}

	if(!(CCrmCompany::CheckReadPermission(0) && CCrmCompany::CheckUpdatePermission(0)))
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$progressData = COption::GetOptionString('crm', '~CRM_COMPANY_REQUISITES_BUILD_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? (int)($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? (int)($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? (int)($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmCompany::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmCompany::GetListEx(
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
		foreach($itemIDs as $itemID)
		{
			CCrmCompany::CreateRequisite($itemID, $presetID);
		}

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_COMPANY_REQUISITES_BUILD_PROGRESS', serialize($progressData));
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_REQUISITES_BUILD_PROGRESS_SUMMARY',
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
		COption::RemoveOption('crm', '~CRM_COMPANY_REQUISITES_BUILD_PROGRESS');
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_REQUISITES_BUILD_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'SKIP_CONVERT_REQUISITES')
{
	COption::RemoveOption('crm', '~CRM_TRANSFER_REQUISITES_TO_COMPANY');
}
elseif ($action === 'CONVERT_REQUISITES')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Company)
	{
		__CrmCompanyListEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	$presetID = isset($params['PRESET_ID']) ? (int)$params['PRESET_ID'] : 0;
	if($presetID <= 0)
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Preset ID is not specified.'));
	}

	if(!(CCrmCompany::CheckReadPermission(0) && CCrmCompany::CheckUpdatePermission(0)))
	{
		__CrmCompanyListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_TRANSFER_REQUISITES_TO_COMPANY', 'N') !== 'Y')
	{
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_COMPANY_REQUISITES_TRANSFER_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_COMPANY_REQUISITES_TRANSFER_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? (int)($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? (int)($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? (int)($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmCompany::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmCompany::GetListEx(
		array('ID' => 'ASC'),
		$filter,
		false,
		array('nTopCount' => 5),
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

	/** @var Crm\Requisite\EntityRequisiteConverter[] $converters */
	$converters = array(
		new Crm\Requisite\InvoiceRequisiteConverter(CCrmOwnerType::Company, $presetID, true, 300),
		new Crm\Requisite\AddressRequisiteConverter(CCrmOwnerType::Company, $presetID, true)
	);

	if($itemQty > 0)
	{
		foreach($converters as $converter)
		{
			try
			{
				/** @var Crm\Requisite\EntityRequisiteConverter $converter */
				$converter->validate();
				foreach($itemIDs as $itemID)
				{
					$converter->processEntity($itemID);
				}
			}
			catch(Crm\Requisite\RequisiteConvertException $e)
			{
				__CrmCompanyListEndResponse(array('ERROR' => $e->getLocalizedMessage()));
			}
			catch(Exception $e)
			{
				__CrmCompanyListEndResponse(array('ERROR' => $e->getMessage()));
			}
		}

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_COMPANY_REQUISITES_TRANSFER_PROGRESS', serialize($progressData));
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_REQUISITES_TRANSFER_PROGRESS_SUMMARY',
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
		foreach($converters as $converter)
		{
			try
			{
				/** @var Crm\Requisite\EntityRequisiteConverter $converter */
				$converter->complete();
			}
			catch(Crm\Requisite\RequisiteConvertException $e)
			{
				__CrmCompanyListEndResponse(array('ERROR' => $e->getLocalizedMessage()));
			}
			catch(Exception $e)
			{
				__CrmCompanyListEndResponse(array('ERROR' => $e->getMessage()));
			}
		}

		COption::RemoveOption('crm', '~CRM_COMPANY_REQUISITES_TRANSFER_PROGRESS');
		COption::RemoveOption('crm', '~CRM_TRANSFER_REQUISITES_TO_COMPANY');
		__CrmCompanyListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_COMPANY_REQUISITES_TRANSFER_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'CONVERT_ADDRESSES')
{
	/** @var CompanyAddressConvertAgent $agent */
	$agent = CompanyAddressConvertAgent::getInstance();
	$isAgentEnabled = $agent->isEnabled();
	if ($isAgentEnabled)
	{
		if (!$agent->isActive())
		{
			$agent->enable(false);
			$isAgentEnabled = false;
		}
	}
	if(!$isAgentEnabled)
	{
		__CrmCompanyListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmCompanyListEndResponse(
		[
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		]
	);
}
elseif ($action === 'CONVERT_UF_ADDRESSES')
{
	/** @var CompanyUfAddressConvertAgent $agent */
	$agent = CompanyUfAddressConvertAgent::getInstance();
	$isAgentEnabled = $agent->isEnabled();
	if ($isAgentEnabled)
	{
		if (!$agent->isActive())
		{
			if (CCrmOwnerType::IsDefined($agent->getSourceEntityTypeId()))
			{
				// Disable if was running but is not active
				// Source entity type is known only after start the agent
				$agent->enable(false);
			}
			$isAgentEnabled = false;
		}
	}
	if(!$isAgentEnabled)
	{
		__CrmCompanyListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmCompanyListEndResponse(
		[
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		]
	);
}
?>