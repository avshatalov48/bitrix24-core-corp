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
			'CONVERT_UF_ADDRESSES',
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
if(!function_exists('__CrmContactListEndResponse'))
{
	function __CrmContactListEndResponse($result)
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
	__CrmContactListEndResponse(array('ERROR' => 'Could not include crm module.'));
}

use Bitrix\Crm;
use Bitrix\Crm\Agent\Duplicate\Background\ContactIndexRebuild;
use Bitrix\Crm\Agent\Duplicate\Background\ContactMerge;
use Bitrix\Crm\Agent\Duplicate\Volatile\IndexRebuild;
use Bitrix\Crm\Agent\Requisite\ContactAddressConvertAgent;
use Bitrix\Crm\Agent\Requisite\ContactUfAddressConvertAgent;
use Bitrix\Crm\Integrity\Volatile;

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	__CrmContactListEndResponse(array('ERROR' => 'Access denied.'));
}

if ($_REQUEST['MODE'] == 'SEARCH')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmContact::CheckReadPermission(0, $userPerms))
	{
		__CrmContactListEndResponse(array('ERROR' => 'Access denied.'));
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

	$arData = array();
	$search = trim($_REQUEST['VALUE']);
	if (!empty($search))
	{
		$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y'? true: false;
		$arFilter = array();
		if (is_numeric($search))
		{
			$arFilter['ID'] = (int)$search;
		}
		else if (preg_match('/(.*)\[(\d+?)\]/i'.BX_UTF_PCRE_MODIFIER, $search, $arMatches))
		{
			$arFilter['ID'] = (int) $arMatches[2];
			$searchString = trim($arMatches[1]);
			if (is_string($searchString) && $searchString !== '')
			{
				$arFilter['%FULL_NAME'] = $searchString;
				$arFilter['LOGIC'] = 'OR';
			}
			unset($searchString);
		}
		else
		{
			$searchParts = preg_split('/[\s]+/', $search, 2, PREG_SPLIT_NO_EMPTY);
			if(count($searchParts) < 2)
			{
				$arFilter['LOGIC'] = 'OR';
				$arFilter['%NAME'] = $search;
				$arFilter['%LAST_NAME'] = $search;
			}
			else
			{
				$arFilter['LOGIC'] = 'OR';
				$arFilter["__INNER_FILTER_NAME_1"] = ['%NAME' => $searchParts[0], '%LAST_NAME' => $searchParts[1]];
				$arFilter["__INNER_FILTER_NAME_2"] = ['%LAST_NAME' => $searchParts[0], '%NAME' => $searchParts[1]];
				$arFilter["__INNER_FILTER_NAME_3"] = ['%NAME' => $searchParts[0], '%SECOND_NAME' => $searchParts[1]];
			}
		}
		$arContactTypeList = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
		$arSelect = array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO', 'TYPE_ID');
		$arOrder = array('LAST_NAME' => 'ASC', 'NAME' => 'ASC');
		$obRes = CCrmContact::GetListEx($arOrder, $arFilter, false, array('nTopCount' => $nPageTop), $arSelect);
		$arImages = array();
		$arLargeImages = array();

		$i = 0;
		$contactIndex = array();
		$contactTypes = CCrmStatus::GetStatusList('CONTACT_TYPE');
		while ($arRes = $obRes->Fetch())
		{
			$photoID = intval($arRes['PHOTO']);
			if ($photoID > 0 && !isset($arImages[$photoID]))
			{
				$arImages[$photoID] = CFile::ResizeImageGet($photoID, array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
				$arLargeImages[$photoID] = CFile::ResizeImageGet($photoID, array('width' => 38, 'height' => 38), BX_RESIZE_IMAGE_EXACT);
			}

			// advanced info
			$advancedInfo = array();
			if (isset($arRes['TYPE_ID']) && $arRes['TYPE_ID'] != '' && isset($contactTypes[$arRes['TYPE_ID']]))
			{
				$advancedInfo['contactType'] = array(
					'id' => $arRes['TYPE_ID'],
					'name' => $contactTypes[$arRes['TYPE_ID']]
				);
			}

			$arData[$i] =
				array(
					'id' => $multi? 'C_'.$arRes['ID']: $arRes['ID'],
					'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'),
						array(
							'contact_id' => $arRes['ID']
						)
					),
					'title' => CCrmContact::PrepareFormattedName(
						array(
							'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
							'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
							'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
							'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
						)
					),
					'desc' => empty($arRes['COMPANY_TITLE'])? "": $arRes['COMPANY_TITLE'],
					'image' => isset($arImages[$photoID]['src']) ? $arImages[$photoID]['src'] : '',
					'largeImage' => isset($arLargeImages[$photoID]['src']) ? $arLargeImages[$photoID]['src'] : '',
					'type' => 'contact'
				)
			;
			if (!empty($advancedInfo))
				$arData[$i]['advancedInfo'] = $advancedInfo;
			unset($advancedInfo);

			// requisites
			if ($requireRequisiteData)
				$arData[$i]['advancedInfo']['requisiteData'] = CCrmEntitySelectorHelper::PrepareRequisiteData(
					CCrmOwnerType::Contact, $arRes['ID'], array('VIEW_DATA_ONLY' => true)
				);

			$contactIndex[$arRes['ID']] = &$arData[$i];
			$i++;
		}

		// advanced info - phone number, e-mail
		if (!empty($contactIndex))
		{
			$obRes = CCrmFieldMulti::GetList(
				['ID' => 'asc'],
				['ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => array_keys($contactIndex)]
			);
			while ($arRes = $obRes->Fetch())
			{
				if (
					isset($contactIndex[$arRes['ELEMENT_ID']])
					&& ($arRes['TYPE_ID'] === 'PHONE' || $arRes['TYPE_ID'] === 'EMAIL')
				)
				{
					$item = &$contactIndex[$arRes['ELEMENT_ID']];
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
		unset($contactIndex);
	}

	__CrmContactListEndResponse($arData);
}
elseif ($action === 'REBUILD_SEARCH_CONTENT')
{
	$agent = \Bitrix\Crm\Agent\Search\ContactSearchContentRebuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isActive())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmContactListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmContactListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'REBUILD_SECURITY_ATTRS')
{
	$agent = \Bitrix\Crm\Agent\Security\ContactAttributeRebuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isRegistered())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmContactListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmContactListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'BUILD_TIMELINE')
{
	$agent = \Bitrix\Crm\Agent\Timeline\ContactTimelineBuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isActive())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmContactListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmContactListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'BUILD_DUPLICATE_INDEX')
{
	$agent = \Bitrix\Crm\Agent\Duplicate\ContactDuplicateIndexRebuildAgent::getInstance();
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
		__CrmContactListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmContactListEndResponse(
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
		__CrmContactListEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmContactListEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmContactListEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if(!CCrmContact::CheckUpdatePermission(0))
	{
		__CrmContactListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX', 'N') !== 'Y')
	{
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_CONTACT_LIST_REBUILD_DUPLICATE_INDEX_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? (int)($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? (int)($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? (int)($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmContact::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmContact::GetListEx(
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
		CCrmContact::RebuildDuplicateIndex($itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX_PROGRESS', serialize($progressData));
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_CONTACT_LIST_REBUILD_DUPLICATE_INDEX_PROGRESS_SUMMARY',
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
		COption::RemoveOption('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX');
		COption::RemoveOption('crm', '~CRM_REBUILD_CONTACT_DUP_INDEX_PROGRESS');
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_CONTACT_LIST_REBUILD_DUPLICATE_INDEX_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'REBUILD_ACT_STATISTICS')
{
	//~CRM_REBUILD_CONTACT_ACT_STATISTICS
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmContact::CheckUpdatePermission(0))
	{
		__CrmContactListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_ACT_STATISTICS', 'N') !== 'Y')
	{
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_CONTACT_LIST_REBUILD_ACT_STATISTICS_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_ACT_STATISTICS_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmContact::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmContact::GetListEx(
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
		Crm\Activity\CommunicationStatistics::rebuild(\CCrmOwnerType::Contact, $itemIDs);

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_REBUILD_CONTACT_ACT_STATISTICS_PROGRESS', serialize($progressData));
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_CONTACT_LIST_REBUILD_ACT_STATISTICS_PROGRESS_SUMMARY',
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
		COption::RemoveOption('crm', '~CRM_REBUILD_CONTACT_ACT_STATISTICS');
		COption::RemoveOption('crm', '~CRM_REBUILD_CONTACT_ACT_STATISTICS_PROGRESS');
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_CONTACT_LIST_REBUILD_ACT_STATISTICS_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'GET_ROW_COUNT')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmContact::CheckReadPermission(0, $userPerms))
	{
		__CrmContactListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmContactListEndResponse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];
	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
	$result = CCrmContact::GetListEx(array(), $filter, array(), false, array(), array());

	$text = '';
	if(is_numeric($result))
	{
		$text = GetMessage('CRM_CONTACT_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
		if($text === '')
		{
			$text = $result;
		}
	}
	__CrmContactListEndResponse(array('DATA' => array('TEXT' => $text)));
}
elseif ($action === 'GET_REQUISITE_PRESETS')
{
	$entityTypeName = isset($_POST['ENTITY_TYPE_NAME']) ? $_POST['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmContactListEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmContactListEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmContactListEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if(!CCrmContact::CheckReadPermission(0))
	{
		__CrmContactListEndResponse(array('ERROR' => 'Access denied.'));
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

	__CrmContactListEndResponse(
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
		__CrmContactListEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmContactListEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmContactListEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	$presetID = isset($params['PRESET_ID']) ? (int)$params['PRESET_ID'] : 0;
	if($presetID <= 0)
	{
		__CrmContactListEndResponse(array('ERROR' => "Preset ID is not specified."));
	}

	if(!(CCrmContact::CheckReadPermission(0) && CCrmContact::CheckUpdatePermission(0)))
	{
		__CrmContactListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$progressData = COption::GetOptionString('crm', '~CRM_CONTACT_REQUISITES_BUILD_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmContact::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmContact::GetListEx(
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
			CCrmContact::CreateRequisite($itemID, $presetID);
		}

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_CONTACT_REQUISITES_BUILD_PROGRESS', serialize($progressData));
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_CONTACT_REQUISITES_BUILD_PROGRESS_SUMMARY',
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
		COption::RemoveOption('crm', '~CRM_CONTACT_REQUISITES_BUILD_PROGRESS');
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_CONTACT_REQUISITES_BUILD_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'SKIP_CONVERT_REQUISITES')
{
	COption::RemoveOption('crm', '~CRM_TRANSFER_REQUISITES_TO_CONTACT');
}
elseif ($action === 'CONVERT_REQUISITES')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmContactListEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmContactListEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmContactListEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	$presetID = isset($params['PRESET_ID']) ? (int)$params['PRESET_ID'] : 0;
	if($presetID <= 0)
	{
		__CrmContactListEndResponse(array('ERROR' => 'Preset ID is not specified.'));
	}

	if(!(CCrmContact::CheckReadPermission(0) && CCrmContact::CheckUpdatePermission(0)))
	{
		__CrmContactListEndResponse(array('ERROR' => 'Access denied.'));
	}

	if(COption::GetOptionString('crm', '~CRM_TRANSFER_REQUISITES_TO_CONTACT', 'N') !== 'Y')
	{
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'NOT_REQUIRED',
				'SUMMARY' => GetMessage('CRM_CONTACT_REQUISITES_TRANSFER_NOT_REQUIRED_SUMMARY')
			)
		);
	}

	$progressData = COption::GetOptionString('crm', '~CRM_CONTACT_REQUISITES_TRANSFER_PROGRESS',  '');
	$progressData = $progressData !== '' ? unserialize($progressData, ['allowed_classes' => false]) : array();
	$lastItemID = isset($progressData['LAST_ITEM_ID']) ? (int)($progressData['LAST_ITEM_ID']) : 0;
	$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? (int)($progressData['PROCESSED_ITEMS']) : 0;
	$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? (int)($progressData['TOTAL_ITEMS']) : 0;
	if($totalItemQty <= 0)
	{
		$totalItemQty = CCrmContact::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
	}

	$filter = array('CHECK_PERMISSIONS' => 'N');
	if($lastItemID > 0)
	{
		$filter['>ID'] = $lastItemID;
	}

	$dbResult = CCrmContact::GetListEx(
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
		new Crm\Requisite\InvoiceRequisiteConverter(CCrmOwnerType::Contact, $presetID, true, 300),
		new Crm\Requisite\AddressRequisiteConverter(CCrmOwnerType::Contact, $presetID, true)
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
				__CrmContactListEndResponse(array('ERROR' => $e->getLocalizedMessage()));
			}
			catch(Exception $e)
			{
				__CrmContactListEndResponse(array('ERROR' => $e->getMessage()));
			}
		}

		$progressData['TOTAL_ITEMS'] = $totalItemQty;
		$processedItemQty += $itemQty;
		$progressData['PROCESSED_ITEMS'] = $processedItemQty;
		$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

		COption::SetOptionString('crm', '~CRM_CONTACT_REQUISITES_TRANSFER_PROGRESS', serialize($progressData));
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'PROGRESS',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_CONTACT_REQUISITES_TRANSFER_PROGRESS_SUMMARY',
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
				__CrmContactListEndResponse(array('ERROR' => $e->getLocalizedMessage()));
			}
			catch(Exception $e)
			{
				__CrmContactListEndResponse(array('ERROR' => $e->getMessage()));
			}
		}

		COption::RemoveOption('crm', '~CRM_CONTACT_REQUISITES_TRANSFER_PROGRESS');
		COption::RemoveOption('crm', '~CRM_TRANSFER_REQUISITES_TO_CONTACT');
		__CrmContactListEndResponse(
			array(
				'STATUS' => 'COMPLETED',
				'PROCESSED_ITEMS' => $processedItemQty,
				'TOTAL_ITEMS' => $totalItemQty,
				'SUMMARY' => GetMessage(
					'CRM_CONTACT_REQUISITES_TRANSFER_COMPLETED_SUMMARY',
					array('#PROCESSED_ITEMS#' => $processedItemQty)
				)
			)
		);
	}
}
elseif ($action === 'CONVERT_ADDRESSES')
{
	/** @var ContactAddressConvertAgent $agent */
	$agent = ContactAddressConvertAgent::getInstance();
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
		__CrmContactListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmContactListEndResponse(
		[
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		]
	);
}
elseif ($action === 'CONVERT_UF_ADDRESSES')
{
	/** @var ContactUfAddressConvertAgent $agent */
	$agent = ContactUfAddressConvertAgent::getInstance();
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
		__CrmContactListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmContactListEndResponse(
		[
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		]
	);
}
elseif ($action === 'BACKGROUND_INDEX_REBUILD')
{
	$userId = CCrmSecurityHelper::GetCurrentUserID();
	$isNeedToShowDupIndexProcess = false;
	$agent = ContactIndexRebuild::getInstance($userId);
	if ($agent->isActive())
	{
		$state = $agent->state()->getData();
		if (isset($state['STATUS']) && $state['STATUS'] === ContactIndexRebuild::STATUS_RUNNING)
		{
			$isNeedToShowDupIndexProcess = true;
		}
	}

	if(!$isNeedToShowDupIndexProcess)
	{
		__CrmContactListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	__CrmContactListEndResponse(
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
	$agent = ContactMerge::getInstance($userId);
	if ($agent->isActive())
	{
		$state = $agent->state()->getData();
		if (isset($state['STATUS']) && $state['STATUS'] === ContactMerge::STATUS_RUNNING)
		{
			$isNeedToShowDupMergeProcess = true;
		}
	}

	if(!$isNeedToShowDupMergeProcess)
	{
		__CrmContactListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	__CrmContactListEndResponse(
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
	$typeInfo = Volatile\TypeInfo::getInstance()->getIdsByEntityTypes([CCrmOwnerType::Contact]);
	$stateMap = [];
	if (isset($typeInfo[CCrmOwnerType::Contact]))
	{
		foreach ($typeInfo[CCrmOwnerType::Contact] as $id)
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
		__CrmContactListEndResponse(array('STATUS' => 'COMPLETED'));
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

	__CrmContactListEndResponse(
		[
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $percentage,
			'TOTAL_ITEMS' => 100,
		]
	);
}
