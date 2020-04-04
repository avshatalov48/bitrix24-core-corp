<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmConfigPermsEndResponse'))
{
	function __CrmConfigPermsEndResponse($result)
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
	__CrmConfigPermsEndResponse(array('ERROR' => 'Could not include crm module.'));
}


/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'REBUILD_ENTITY_ATTRS' - Rebuild entity attributes
 */

$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	__CrmConfigPermsEndResponse(array('ERROR' => 'Access denied.'));
}

$action = isset($_POST['ACTION']) ? strtoupper($_POST['ACTION']) : '';
if($action === 'REBUILD_ENTITY_ATTRS')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmConfigPermsEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmConfigPermsEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID === CCrmOwnerType::Company)
	{
		if(!CCrmCompany::CheckUpdatePermission(0))
		{
			__CrmConfigPermsEndResponse(array('ERROR' => 'Access denied.'));
		}

		if(COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_ATTR', 'N') !== 'Y')
		{
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'NOT_REQUIRED',
					'SUMMARY' => GetMessage('CRM_CONFIG_PERMS_REBUILD_ATTR_NOT_REQUIRED_SUMMARY')
				)
			);
		}

		$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_COMPANY_ATTR_PROGRESS',  '');
		$progressData = $progressData !== '' ? unserialize($progressData) : array();
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
			array('nTopCount' => 10),
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
			CCrmCompany::RebuildEntityAccessAttrs($itemIDs);

			$progressData['TOTAL_ITEMS'] = $totalItemQty;
			$processedItemQty += $itemQty;
			$progressData['PROCESSED_ITEMS'] = $processedItemQty;
			$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

			COption::SetOptionString('crm', '~CRM_REBUILD_COMPANY_ATTR_PROGRESS', serialize($progressData));
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'PROGRESS',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_PROGRESS_SUMMARY',
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
			COption::RemoveOption('crm', '~CRM_REBUILD_COMPANY_ATTR');
			COption::RemoveOption('crm', '~CRM_REBUILD_COMPANY_ATTR_PROGRESS');
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'COMPLETED',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_COMPLETED_SUMMARY',
						array('#PROCESSED_ITEMS#' => $processedItemQty)
					)
				)
			);
		}
	}
	elseif($entityTypeID === CCrmOwnerType::Contact)
	{
		if(!CCrmContact::CheckUpdatePermission(0))
		{
			__CrmConfigPermsEndResponse(array('ERROR' => 'Access denied.'));
		}

		if(COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_ATTR', 'N') !== 'Y')
		{
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'NOT_REQUIRED',
					'SUMMARY' => GetMessage('CRM_CONFIG_PERMS_REBUILD_ATTR_NOT_REQUIRED_SUMMARY')
				)
			);
		}

		$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_CONTACT_ATTR_PROGRESS',  '');
		$progressData = $progressData !== '' ? unserialize($progressData) : array();
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
			array('nTopCount' => 10),
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
			CCrmContact::RebuildEntityAccessAttrs($itemIDs);

			$progressData['TOTAL_ITEMS'] = $totalItemQty;
			$processedItemQty += $itemQty;
			$progressData['PROCESSED_ITEMS'] = $processedItemQty;
			$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

			COption::SetOptionString('crm', '~CRM_REBUILD_CONTACT_ATTR_PROGRESS', serialize($progressData));
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'PROGRESS',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_PROGRESS_SUMMARY',
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
			COption::RemoveOption('crm', '~CRM_REBUILD_CONTACT_ATTR');
			COption::RemoveOption('crm', '~CRM_REBUILD_CONTACT_ATTR_PROGRESS');
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'COMPLETED',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_COMPLETED_SUMMARY',
						array('#PROCESSED_ITEMS#' => $processedItemQty)
					)
				)
			);
		}
	}
	elseif($entityTypeID === CCrmOwnerType::Deal)
	{
		if(!CCrmDeal::CheckUpdatePermission(0))
		{
			__CrmConfigPermsEndResponse(array('ERROR' => 'Access denied.'));
		}

		if(COption::GetOptionString('crm', '~CRM_REBUILD_DEAL_ATTR', 'N') !== 'Y')
		{
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'NOT_REQUIRED',
					'SUMMARY' => GetMessage('CRM_CONFIG_PERMS_REBUILD_ATTR_NOT_REQUIRED_SUMMARY')
				)
			);
		}

		$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_DEAL_ATTR_PROGRESS',  '');
		$progressData = $progressData !== '' ? unserialize($progressData) : array();
		$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
		$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
		$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
		if($totalItemQty <= 0)
		{
			$totalItemQty = CCrmDeal::GetListEx(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
		}

		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($lastItemID > 0)
		{
			$filter['>ID'] = $lastItemID;
		}

		$dbResult = CCrmDeal::GetListEx(
			array('ID' => 'ASC'),
			$filter,
			false,
			array('nTopCount' => 10),
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
			CCrmDeal::RebuildEntityAccessAttrs($itemIDs);

			$progressData['TOTAL_ITEMS'] = $totalItemQty;
			$processedItemQty += $itemQty;
			$progressData['PROCESSED_ITEMS'] = $processedItemQty;
			$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

			COption::SetOptionString('crm', '~CRM_REBUILD_DEAL_ATTR_PROGRESS', serialize($progressData));
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'PROGRESS',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_PROGRESS_SUMMARY',
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
			COption::RemoveOption('crm', '~CRM_REBUILD_DEAL_ATTR');
			COption::RemoveOption('crm', '~CRM_REBUILD_DEAL_ATTR_PROGRESS');
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'COMPLETED',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_COMPLETED_SUMMARY',
						array('#PROCESSED_ITEMS#' => $processedItemQty)
					)
				)
			);
		}
	}
	elseif($entityTypeID === CCrmOwnerType::Lead)
	{
		if(!CCrmLead::CheckUpdatePermission(0))
		{
			__CrmConfigPermsEndResponse(array('ERROR' => 'Access denied.'));
		}

		if(COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_ATTR', 'N') !== 'Y')
		{
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'NOT_REQUIRED',
					'SUMMARY' => GetMessage('CRM_CONFIG_PERMS_REBUILD_ATTR_NOT_REQUIRED_SUMMARY')
				)
			);
		}

		$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_LEAD_ATTR_PROGRESS',  '');
		$progressData = $progressData !== '' ? unserialize($progressData) : array();
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
			array('nTopCount' => 10),
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
			CCrmLead::RebuildEntityAccessAttrs($itemIDs);

			$progressData['TOTAL_ITEMS'] = $totalItemQty;
			$processedItemQty += $itemQty;
			$progressData['PROCESSED_ITEMS'] = $processedItemQty;
			$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

			COption::SetOptionString('crm', '~CRM_REBUILD_LEAD_ATTR_PROGRESS', serialize($progressData));
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'PROGRESS',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_PROGRESS_SUMMARY',
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
			COption::RemoveOption('crm', '~CRM_REBUILD_LEAD_ATTR');
			COption::RemoveOption('crm', '~CRM_REBUILD_LEAD_ATTR_PROGRESS');
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'COMPLETED',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_COMPLETED_SUMMARY',
						array('#PROCESSED_ITEMS#' => $processedItemQty)
					)
				)
			);
		}
	}
	elseif($entityTypeID === CCrmOwnerType::Quote)
	{
		if(!CCrmQuote::CheckUpdatePermission(0))
		{
			__CrmConfigPermsEndResponse(array('ERROR' => 'Access denied.'));
		}

		if(COption::GetOptionString('crm', '~CRM_REBUILD_QUOTE_ATTR', 'N') !== 'Y')
		{
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'NOT_REQUIRED',
					'SUMMARY' => GetMessage('CRM_CONFIG_PERMS_REBUILD_ATTR_NOT_REQUIRED_SUMMARY')
				)
			);
		}

		$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_QUOTE_ATTR_PROGRESS',  '');
		$progressData = $progressData !== '' ? unserialize($progressData) : array();
		$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
		$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
		$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
		if($totalItemQty <= 0)
		{
			$totalItemQty = CCrmQuote::GetList(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
		}

		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($lastItemID > 0)
		{
			$filter['>ID'] = $lastItemID;
		}

		$dbResult = CCrmQuote::GetList(
			array('ID' => 'ASC'),
			$filter,
			false,
			array('nTopCount' => 10),
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
			CCrmQuote::RebuildEntityAccessAttrs($itemIDs);

			$progressData['TOTAL_ITEMS'] = $totalItemQty;
			$processedItemQty += $itemQty;
			$progressData['PROCESSED_ITEMS'] = $processedItemQty;
			$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

			COption::SetOptionString('crm', '~CRM_REBUILD_QUOTE_ATTR_PROGRESS', serialize($progressData));
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'PROGRESS',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_PROGRESS_SUMMARY',
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
			COption::RemoveOption('crm', '~CRM_REBUILD_QUOTE_ATTR');
			COption::RemoveOption('crm', '~CRM_REBUILD_QUOTE_ATTR_PROGRESS');
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'COMPLETED',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_COMPLETED_SUMMARY',
						array('#PROCESSED_ITEMS#' => $processedItemQty)
					)
				)
			);
		}
	}
	elseif($entityTypeID === CCrmOwnerType::Invoice)
	{
		if(!CCrmInvoice::CheckUpdatePermission(0))
		{
			__CrmConfigPermsEndResponse(array('ERROR' => 'Access denied.'));
		}

		if(COption::GetOptionString('crm', '~CRM_REBUILD_INVOICE_ATTR', 'N') !== 'Y')
		{
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'NOT_REQUIRED',
					'SUMMARY' => GetMessage('CRM_CONFIG_PERMS_REBUILD_ATTR_NOT_REQUIRED_SUMMARY')
				)
			);
		}

		$progressData = COption::GetOptionString('crm', '~CRM_REBUILD_INVOICE_ATTR_PROGRESS',  '');
		$progressData = $progressData !== '' ? unserialize($progressData) : array();
		$lastItemID = isset($progressData['LAST_ITEM_ID']) ? intval($progressData['LAST_ITEM_ID']) : 0;
		$processedItemQty = isset($progressData['PROCESSED_ITEMS']) ? intval($progressData['PROCESSED_ITEMS']) : 0;
		$totalItemQty = isset($progressData['TOTAL_ITEMS']) ? intval($progressData['TOTAL_ITEMS']) : 0;
		if($totalItemQty <= 0)
		{
			$totalItemQty = CCrmInvoice::GetList(array(), array('CHECK_PERMISSIONS' => 'N'), array(), false);
		}

		$filter = array('CHECK_PERMISSIONS' => 'N');
		if($lastItemID > 0)
		{
			$filter['>ID'] = $lastItemID;
		}

		$dbResult = CCrmInvoice::GetList(
			array('ID' => 'ASC'),
			$filter,
			false,
			array('nTopCount' => 10),
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
			CCrmInvoice::RebuildEntityAccessAttrs($itemIDs);

			$progressData['TOTAL_ITEMS'] = $totalItemQty;
			$processedItemQty += $itemQty;
			$progressData['PROCESSED_ITEMS'] = $processedItemQty;
			$progressData['LAST_ITEM_ID'] = $itemIDs[$itemQty - 1];

			COption::SetOptionString('crm', '~CRM_REBUILD_INVOICE_ATTR_PROGRESS', serialize($progressData));
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'PROGRESS',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_PROGRESS_SUMMARY',
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
			COption::RemoveOption('crm', '~CRM_REBUILD_INVOICE_ATTR');
			COption::RemoveOption('crm', '~CRM_REBUILD_INVOICE_ATTR_PROGRESS');
			__CrmConfigPermsEndResponse(
				array(
					'STATUS' => 'COMPLETED',
					'PROCESSED_ITEMS' => $processedItemQty,
					'TOTAL_ITEMS' => $totalItemQty,
					'SUMMARY' => GetMessage(
						'CRM_CONFIG_PERMS_REBUILD_ATTR_COMPLETED_SUMMARY',
						array('#PROCESSED_ITEMS#' => $processedItemQty)
					)
				)
			);
		}
	}
	else
	{
		__CrmConfigPermsEndResponse(array('ERROR' => 'Specified entity type is not supported.'));
	}
}