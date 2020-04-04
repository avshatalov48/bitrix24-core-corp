<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $APPLICATION;
$userPerms = CCrmPerms::GetCurrentUserPermissions();

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']);

$uid = isset($arParams['UID']) ? $arParams['UID'] : '';
if($uid === '')
{
	$uid = 'mobile_crm_activity_edit';
}
$arResult['UID'] = $arParams['UID'] = $uid;
$arResult['USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();

$contextID = isset($arParams['CONTEXT_ID']) ? $arParams['CONTEXT_ID'] : '';
if($contextID === '' && isset($_REQUEST['context_id']))
{
	$contextID = $_REQUEST['context_id'];
}
$arResult['CONTEXT_ID'] = $contextID;

$commType = '';
$ownerID = 0;
$ownerTypeID = CCrmOwnerType::Undefined;

$commType = $arParams['COMMUNICATION_TYPE'] = isset($arParams['COMMUNICATION_TYPE']) ? strtoupper($arParams['COMMUNICATION_TYPE']) : '';
if($commType == '' && isset($_REQUEST['type']))
{
	$commType = $arParams['COMMUNICATION_TYPE'] = strtoupper($_REQUEST['type']);
}

if($commType === '')
{
	$commType = 'PERSON';
}

$arResult['COMMUNICATION_TYPE'] = $commType;

$ownerID = $arParams['OWNER_ID'] = isset($arParams['OWNER_ID']) ? intval($arParams['OWNER_ID']) : 0;
if($ownerID <= 0 && isset($_REQUEST['owner_id']))
{
	$ownerID = $arParams['OWNER_ID'] = intval($_REQUEST['owner_id']);
}
$arResult['OWNER_ID'] = $ownerID;

$ownerTypeName = $arParams['OWNER_TYPE'] = isset($arParams['OWNER_TYPE']) ? $arParams['OWNER_TYPE'] : '';
if($ownerTypeName === '' && isset($_REQUEST['owner_type']))
{
	$ownerTypeName = $arParams['OWNER_TYPE'] = $_REQUEST['owner_type'];
}
$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
if($ownerTypeID === CCrmOwnerType::Undefined)
{
	$ownerTypeName = '';
}

$arResult['OWNER_TYPE_NAME'] = $ownerTypeName;
$arResult['OWNER_TYPE_ID'] = $ownerTypeID;
$arResult['SHOW_SEARCH_PANEL'] = 'Y';

$needle = '';
$enableSearch = $arResult['ENABLE_SEARCH'] = isset($_REQUEST['SEARCH']) && strtoupper($_REQUEST['SEARCH']) === 'Y';
if($enableSearch)
{
	// decode encodeURIComponent params
	CUtil::JSPostUnescape();
	$needle = isset($_REQUEST['NEEDLE']) ? $_REQUEST['NEEDLE'] : '';
}

$items = array();
$imageless = array();

if(!CCrmOwnerType::IsDefined($ownerTypeID) || $ownerID <= 0)
{
	if($enableSearch && $needle !== '')
	{
		$results = CCrmActivity::FindContactCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10);

		if($commType !== '')
		{
			//If communication type defined add companies communications
			$results = array_merge(
				$results,
				CCrmActivity::FindCompanyCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10)
			);
		}

		$results = array_merge(
			$results,
			CCrmActivity::FindLeadCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10)
		);
	}
	else
	{
		$results = CCrmActivity::GetRecentlyUsedCommunications($commType !== 'PERSON' ? $commType : '');
		foreach($results as &$result)
		{
			CCrmActivity::PrepareCommunicationInfo($result);
		}
		unset($result);
	}

	foreach($results as &$result)
	{
		$entityID = intval($result['ENTITY_ID']);
		$entityTypeID = intval($result['ENTITY_TYPE_ID']);
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if(!CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $userPerms))
		{
			continue;
		}

		$item = array(
			'OWNER_ID' => $entityID,
			'OWNER_TYPE_ID' => $entityTypeID,
			'OWNER_TYPE_NAME' => $entityTypeName,
			'TITLE' => $result['TITLE'],
			'DESCRIPTION' => $result['DESCRIPTION'],
			'IMAGE_URL' => '',
			'COMMUNICATIONS' => array()
		);

		if($result['TYPE'] !== '' && $result['VALUE'] !== '')
		{
			$item['COMMUNICATIONS'][] = array('TYPE' => $result['TYPE'], 'VALUE' => $result['VALUE']);
		}

		if(!isset($imageless[$entityTypeName]))
		{
			$imageless[$entityTypeName] = array();
		}
		$imageless[$entityTypeName][] = $entityID;

		$items["{$entityTypeName}_{$entityID}"] = &$item;
		unset($item);
	}
	unset($result);
}
elseif($ownerTypeID === CCrmOwnerType::Lead)
{
	if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::Lead, $ownerID))
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
		return;
	}

	$entity = CCrmLead::GetByID($ownerID, false);
	if(!is_array($entity))
	{
		ShowError(GetMessage('M_CRM_COMM_SELECTOR_OWNER_NOT_FOUND'));
		return;
	}

	$info = array('ENTITY_TYPE_ID' => CCrmOwnerType::Lead, 'ENTITY_ID' => $ownerID);
	if(CCrmActivity::PrepareCommunicationInfo($info))
	{
		$item = array(
			'OWNER_ID' => $ownerID,
			'OWNER_TYPE_ID' => CCrmOwnerType::Lead,
			'TITLE' => $info['TITLE'],
			'DESCRIPTION' => $info['DESCRIPTION'],
			'IMAGE_URL' => CCrmMobileHelper::GetLeadListImageStub(),
			'COMMUNICATIONS' => array()
		);

		if($commType !== 'PERSON')
		{
			$dbMultiFields = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => CCrmOwnerType::LeadName, 'ELEMENT_ID' => $ownerID, 'TYPE_ID' =>  $commType)
			);

			while($multiField = $dbMultiFields->Fetch())
			{
				if(empty($multiField['VALUE']))
				{
					continue;
				}

				$item['COMMUNICATIONS'][] = array('TYPE' => $commType, 'VALUE' => $multiField['VALUE']);
			}
		}

		$items["LEAD_{$ownerID}"] = &$item;
		unset($item);
	}
}
elseif($ownerTypeID === CCrmOwnerType::Deal)
{
	if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::Deal, $ownerID))
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
		return;
	}

	if($enableSearch && $needle !== '')
	{
		$results = CCrmActivity::FindContactCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10);

		if($commType !== '')
		{
			//If communication type defined add companies communications
			$results = array_merge(
				$results,
				CCrmActivity::FindCompanyCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10)
			);
		}

		$results = array_merge(
			$results,
			CCrmActivity::FindLeadCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10)
		);

		foreach($results as &$result)
		{
			$entityID = intval($result['ENTITY_ID']);
			$entityTypeID = intval($result['ENTITY_TYPE_ID']);
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			if(!CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $userPerms))
			{
				continue;
			}

			$item = array(
				'OWNER_ID' => $entityID,
				'OWNER_TYPE_ID' => $entityTypeID,
				'OWNER_TYPE_NAME' => $entityTypeName,
				'TITLE' => $result['TITLE'],
				'DESCRIPTION' => $result['DESCRIPTION'],
				'IMAGE_URL' => '',
				'COMMUNICATIONS' => array()
			);

			if($result['TYPE'] !== '' && $result['VALUE'] !== '')
			{
				$item['COMMUNICATIONS'][] = array('TYPE' => $result['TYPE'], 'VALUE' => $result['VALUE']);
			}

			if(!isset($imageless[$entityTypeName]))
			{
				$imageless[$entityTypeName] = array();
			}
			$imageless[$entityTypeName][] = $entityID;

			$items["{$entityTypeName}_{$entityID}"] = &$item;
			unset($item);
		}
		unset($result);
	}
	else
	{
		$entity = CCrmDeal::GetByID($ownerID, false);
		if(!is_array($entity))
		{
			ShowError(GetMessage('M_CRM_COMM_SELECTOR_OWNER_NOT_FOUND'));
			return;
		}

		$companyID =  isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
		$company = $companyID > 0 ? CCrmCompany::GetByID($companyID, true) : null;
		if(is_array($company))
		{
			$info = array('ENTITY_TYPE_ID' => CCrmOwnerType::Company, 'ENTITY_ID' => $companyID);
			if(CCrmActivity::PrepareCommunicationInfo($info, $company))
			{
				$companyKey = "COMPANY_{$companyID}";
				$item = array(
					'OWNER_ID' => $companyID,
					'OWNER_TYPE_ID' => CCrmOwnerType::Company,
					'TITLE' => $info['TITLE'],
					'DESCRIPTION' => $info['DESCRIPTION'],
					'IMAGE_URL' => CCrmMobileHelper::PrepareCompanyImageUrl($company, array('WIDTH' => 40, 'HEIGHT' => 40)),
					'COMMUNICATIONS' => array()
				);

				if($commType === 'PERSON')
				{
					$items[$companyKey] = &$item;
					unset($item);
				}
				else
				{
					$companyComms = CCrmActivity::PrepareCommunications(CCrmOwnerType::CompanyName, $companyID, $commType);
					foreach($companyComms as &$comm)
					{
						$item['COMMUNICATIONS'][] = array('TYPE' => $comm['TYPE'], 'VALUE' => $comm['VALUE']);
					}
					unset($comm);

					if(!empty($item['COMMUNICATIONS']))
					{
						$items[$companyKey] = &$item;
					}
					unset($item);
				}
			}
		}

		$contactID =  isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;
		$contact = $contactID > 0 ? CCrmContact::GetByID($contactID, true) : null;
		if(is_array($contact))
		{
			$info = array('ENTITY_TYPE_ID' => CCrmOwnerType::Contact, 'ENTITY_ID' => $contactID);
			if(CCrmActivity::PrepareCommunicationInfo($info, $contact))
			{
				$contactKey = "CONTACT_{$contactID}";
				$item = array(
					'OWNER_ID' => $contactID,
					'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
					'TITLE' => $info['TITLE'],
					'DESCRIPTION' => $info['DESCRIPTION'],
					'IMAGE_URL' => CCrmMobileHelper::PrepareContactImageUrl($contact, array('WIDTH' => 40, 'HEIGHT' => 40)),
					'COMMUNICATIONS' => array()
				);

				if($commType === 'PERSON')
				{
					$items[$contactKey] = &$item;
					unset($item);
				}
				else
				{
					$contactComms = CCrmActivity::PrepareCommunications('CONTACT', $contactID, $commType);
					foreach($contactComms as &$comm)
					{
						$item['COMMUNICATIONS'][] = array('TYPE' => $comm['TYPE'], 'VALUE' => $comm['VALUE']);
					}
					unset($comm);

					if(!empty($item['COMMUNICATIONS']))
					{
						$items[$contactKey] = &$item;
					}
					unset($item);
				}
			}
		}

		// Try to get previous communications
		$dealComms = CCrmActivity::GetCommunicationsByOwner('DEAL', $ownerID, $commType);
		foreach($dealComms as &$comm)
		{
			if(!CCrmActivity::PrepareCommunicationInfo($comm))
			{
				continue;
			}

			$commKey = "{$comm['ENTITY_TYPE']}_{$comm['ENTITY_ID']}";
			if(!isset($items[$commKey]))
			{
				$items[$commKey] = array(
					'OWNER_ID' => $ownerID,
					'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
					'TITLE' => $comm['TITLE'],
					'DESCRIPTION' => $comm['DESCRIPTION'],
					'IMAGE_URL' => '',
					'COMMUNICATIONS' => array()
				);

				$commEntityType = $comm['ENTITY_TYPE'];
				if(!isset($imageless[$commEntityType]))
				{
					$imageless[$commEntityType] = array();
				}
				$imageless[$commEntityType][] = $comm['ENTITY_ID'];
			}

			if($commType !== 'PERSON')
			{
				$isFound = false;
				foreach($items[$commKey]['COMMUNICATIONS'] as &$itemComm)
				{
					if($comm['VALUE'] === $itemComm['VALUE'])
					{
						$isFound = true;
						break;
					}
				}
				unset($itemComm);

				if(!$isFound)
				{
					$items[$commKey]['COMMUNICATIONS'][] = array('TYPE' => $comm['TYPE'], 'VALUE' => $comm['VALUE']);
				}
			}
		}
		unset($comm);
	}
}
elseif($ownerTypeID === CCrmOwnerType::Company)
{
	if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::Company, $ownerID))
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
		return;
	}

	if($enableSearch && $needle !== '')
	{
		$results = CCrmActivity::FindContactCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10);

		if($commType !== '')
		{
			//If communication type defined add companies communications
			$results = array_merge(
				$results,
				CCrmActivity::FindCompanyCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10)
			);
		}

		$results = array_merge(
			$results,
			CCrmActivity::FindLeadCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10)
		);

		foreach($results as &$result)
		{
			$entityID = intval($result['ENTITY_ID']);
			$entityTypeID = intval($result['ENTITY_TYPE_ID']);
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			if(!CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $userPerms))
			{
				continue;
			}

			$item = array(
				'OWNER_ID' => $entityID,
				'OWNER_TYPE_ID' => $entityTypeID,
				'OWNER_TYPE_NAME' => $entityTypeName,
				'TITLE' => $result['TITLE'],
				'DESCRIPTION' => $result['DESCRIPTION'],
				'IMAGE_URL' => '',
				'COMMUNICATIONS' => array()
			);

			if($result['TYPE'] !== '' && $result['VALUE'] !== '')
			{
				$item['COMMUNICATIONS'][] = array('TYPE' => $result['TYPE'], 'VALUE' => $result['VALUE']);
			}

			if(!isset($imageless[$entityTypeName]))
			{
				$imageless[$entityTypeName] = array();
			}
			$imageless[$entityTypeName][] = $entityID;

			$items["{$entityTypeName}_{$entityID}"] = &$item;
			unset($item);
		}
		unset($result);
	}
	else
	{
		$entity = CCrmCompany::GetByID($ownerID, false);
		if(!is_array($entity))
		{
			ShowError(GetMessage('M_CRM_COMM_SELECTOR_OWNER_NOT_FOUND'));
			return;
		}

		$items = array();

		$info = array('ENTITY_TYPE_ID' => CCrmOwnerType::Company, 'ENTITY_ID' => $ownerID);
		if(CCrmActivity::PrepareCommunicationInfo($info, $entity))
		{
			$item = array(
				'OWNER_ID' => $ownerID,
				'OWNER_TYPE_ID' => CCrmOwnerType::Company,
				'TITLE' => $info['TITLE'],
				'DESCRIPTION' => $info['DESCRIPTION'],
				'IMAGE_URL' => CCrmMobileHelper::PrepareCompanyImageUrl($entity, array('WIDTH' => 40, 'HEIGHT' => 40)),
				'COMMUNICATIONS' => array()
			);

			$ownerKey = "COMPANY_{$ownerID}";
			if($commType === 'PERSON')
			{
				$items[$ownerKey] = &$item;
				unset($item);
			}
			else
			{
				$dbMultiFields = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => CCrmOwnerType::CompanyName, 'ELEMENT_ID' => $ownerID, 'TYPE_ID' =>  $commType)
				);

				while($multiField = $dbMultiFields->Fetch())
				{
					if(empty($multiField['VALUE']))
					{
						continue;
					}

					$item['COMMUNICATIONS'][] = array('TYPE' => $commType, 'VALUE' => $multiField['VALUE']);
				}

				if(!empty($item['COMMUNICATIONS']))
				{
					$items[$ownerKey] = &$item;
				}
				unset($item);

				// Try to get previous communications
				$companyComms = CCrmActivity::GetCompanyCommunications($ownerID, $commType, 50);
				foreach($companyComms as &$comm)
				{
					if(!CCrmActivity::PrepareCommunicationInfo($comm))
					{
						continue;
					}

					$commKey = "{$comm['ENTITY_TYPE']}_{$comm['ENTITY_ID']}";
					if(!isset($items[$commKey]))
					{
						$items[$commKey] = array(
							'OWNER_ID' => $ownerID,
							'OWNER_TYPE_ID' => CCrmOwnerType::Company,
							'TITLE' => $comm['TITLE'],
							'DESCRIPTION' => $comm['DESCRIPTION'],
							'IMAGE_URL' => '',
							'COMMUNICATIONS' => array()
						);

						$commEntityType = $comm['ENTITY_TYPE'];
						if(!isset($imageless[$commEntityType]))
						{
							$imageless[$commEntityType] = array();
						}
						$imageless[$commEntityType][] = $comm['ENTITY_ID'];
					}

					$items[$commKey]['COMMUNICATIONS'][] = array('TYPE' => $comm['TYPE'], 'VALUE' => $comm['VALUE']);
				}
				unset($comm);
			}
		}
	}
}
elseif($ownerTypeID === CCrmOwnerType::Contact)
{
	if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::Contact, $ownerID))
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
		return;
	}

	if($enableSearch && $needle !== '')
	{
		$results = CCrmActivity::FindContactCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10);

		if($commType !== '')
		{
			//If communication type defined add companies communications
			$results = array_merge(
				$results,
				CCrmActivity::FindCompanyCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10)
			);
		}

		$results = array_merge(
			$results,
			CCrmActivity::FindLeadCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10)
		);

		foreach($results as &$result)
		{
			$entityID = intval($result['ENTITY_ID']);
			$entityTypeID = intval($result['ENTITY_TYPE_ID']);
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			if(!CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $userPerms))
			{
				continue;
			}

			$item = array(
				'OWNER_ID' => $entityID,
				'OWNER_TYPE_ID' => $entityTypeID,
				'OWNER_TYPE_NAME' => $entityTypeName,
				'TITLE' => $result['TITLE'],
				'DESCRIPTION' => $result['DESCRIPTION'],
				'IMAGE_URL' => '',
				'COMMUNICATIONS' => array()
			);

			if($result['TYPE'] !== '' && $result['VALUE'] !== '')
			{
				$item['COMMUNICATIONS'][] = array('TYPE' => $result['TYPE'], 'VALUE' => $result['VALUE']);
			}

			if(!isset($imageless[$entityTypeName]))
			{
				$imageless[$entityTypeName] = array();
			}
			$imageless[$entityTypeName][] = $entityID;

			$items["{$entityTypeName}_{$entityID}"] = &$item;
			unset($item);
		}
		unset($result);
	}
	else
	{
		$entity = CCrmContact::GetByID($ownerID, false);
		if(!is_array($entity))
		{
			ShowError(GetMessage('M_CRM_COMM_SELECTOR_OWNER_NOT_FOUND'));
			return;
		}

		$info = array('ENTITY_TYPE_ID' => CCrmOwnerType::Contact, 'ENTITY_ID' => $ownerID);
		if(CCrmActivity::PrepareCommunicationInfo($info, $entity))
		{
			$item = array(
				'OWNER_ID' => $ownerID,
				'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
				'TITLE' => $info['TITLE'],
				'DESCRIPTION' => $info['DESCRIPTION'],
				'IMAGE_URL' => CCrmMobileHelper::PrepareContactImageUrl($entity, array('WIDTH' => 40, 'HEIGHT' => 40)),
				'COMMUNICATIONS' => array()
			);

			if($commType === 'PERSON')
			{
				$items["CONTACT_{$ownerID}"] = &$item;
				unset($item);
			}
			else
			{
				$dbMultiFields = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => CCrmOwnerType::ContactName, 'ELEMENT_ID' => $ownerID, 'TYPE_ID' =>  $commType)
				);

				while($multiField = $dbMultiFields->Fetch())
				{
					if(empty($multiField['VALUE']))
					{
						continue;
					}

					$item['COMMUNICATIONS'][] = array('TYPE' => $commType, 'VALUE' => $multiField['VALUE']);
				}

				if(!empty($item['COMMUNICATIONS']))
				{
					$items["CONTACT_{$ownerID}"] = &$item;
				}
				unset($item);
			}
		}
	}
}
elseif($ownerTypeID === CCrmOwnerType::Invoice)
{
	if(!CCrmActivity::CheckReadPermission(CCrmOwnerType::Invoice, $ownerID))
	{
		ShowError(GetMessage('CRM_PERMISSION_DENIED'));
		return;
	}

	if($enableSearch && $needle !== '')
	{
		$results = CCrmActivity::FindContactCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10);

		if($commType !== '')
		{
			//If communication type defined add companies communications
			$results = array_merge(
				$results,
				CCrmActivity::FindCompanyCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10)
			);
		}

		$results = array_merge(
			$results,
			CCrmActivity::FindLeadCommunications($needle, $commType !== 'PERSON' ? $commType : '', 10)
		);

		foreach($results as &$result)
		{
			$entityID = intval($result['ENTITY_ID']);
			$entityTypeID = intval($result['ENTITY_TYPE_ID']);
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			if(!CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $userPerms))
			{
				continue;
			}

			$item = array(
				'OWNER_ID' => $entityID,
				'OWNER_TYPE_ID' => $entityTypeID,
				'OWNER_TYPE_NAME' => $entityTypeName,
				'TITLE' => $result['TITLE'],
				'DESCRIPTION' => $result['DESCRIPTION'],
				'IMAGE_URL' => '',
				'COMMUNICATIONS' => array()
			);

			if($result['TYPE'] !== '' && $result['VALUE'] !== '')
			{
				$item['COMMUNICATIONS'][] = array('TYPE' => $result['TYPE'], 'VALUE' => $result['VALUE']);
			}

			if(!isset($imageless[$entityTypeName]))
			{
				$imageless[$entityTypeName] = array();
			}
			$imageless[$entityTypeName][] = $entityID;

			$items["{$entityTypeName}_{$entityID}"] = &$item;
			unset($item);
		}
		unset($result);
	}
	else
	{
		$entity = CCrmInvoice::GetByID($ownerID, false);
		if(!is_array($entity))
		{
			ShowError(GetMessage('M_CRM_COMM_SELECTOR_OWNER_NOT_FOUND'));
			return;
		}

		$companyID =  isset($entity['UF_COMPANY_ID']) ? intval($entity['UF_COMPANY_ID']) : 0;
		$company = $companyID > 0 ? CCrmCompany::GetByID($companyID, true) : null;
		if(is_array($company))
		{
			$info = array('ENTITY_TYPE_ID' => CCrmOwnerType::Company, 'ENTITY_ID' => $companyID);
			if(CCrmActivity::PrepareCommunicationInfo($info, $company))
			{
				$companyKey = "COMPANY_{$companyID}";
				$item = array(
					'OWNER_ID' => $companyID,
					'OWNER_TYPE_ID' => CCrmOwnerType::Company,
					'TITLE' => $info['TITLE'],
					'DESCRIPTION' => $info['DESCRIPTION'],
					'IMAGE_URL' => CCrmMobileHelper::PrepareCompanyImageUrl($company, array('WIDTH' => 40, 'HEIGHT' => 40)),
					'COMMUNICATIONS' => array()
				);

				if($commType === 'PERSON')
				{
					$items[$companyKey] = &$item;
					unset($item);
				}
				else
				{
					$companyComms = CCrmActivity::PrepareCommunications(CCrmOwnerType::CompanyName, $companyID, $commType);
					foreach($companyComms as &$comm)
					{
						$item['COMMUNICATIONS'][] = array('TYPE' => $comm['TYPE'], 'VALUE' => $comm['VALUE']);
					}
					unset($comm);

					if(!empty($item['COMMUNICATIONS']))
					{
						$items[$companyKey] = &$item;
					}
					unset($item);
				}
			}
		}

		$contactID =  isset($entity['UF_CONTACT_ID']) ? intval($entity['UF_CONTACT_ID']) : 0;
		$contact = $contactID > 0 ? CCrmContact::GetByID($contactID, true) : null;
		if(is_array($contact))
		{
			$info = array('ENTITY_TYPE_ID' => CCrmOwnerType::Contact, 'ENTITY_ID' => $contactID);
			if(CCrmActivity::PrepareCommunicationInfo($info, $contact))
			{
				$contactKey = "CONTACT_{$contactID}";
				$item = array(
					'OWNER_ID' => $contactID,
					'OWNER_TYPE_ID' => CCrmOwnerType::Contact,
					'TITLE' => $info['TITLE'],
					'DESCRIPTION' => $info['DESCRIPTION'],
					'IMAGE_URL' => CCrmMobileHelper::PrepareContactImageUrl($contact, array('WIDTH' => 40, 'HEIGHT' => 40)),
					'COMMUNICATIONS' => array()
				);

				if($commType === 'PERSON')
				{
					$items[$contactKey] = &$item;
					unset($item);
				}
				else
				{
					$contactComms = CCrmActivity::PrepareCommunications('CONTACT', $contactID, $commType);
					foreach($contactComms as &$comm)
					{
						$item['COMMUNICATIONS'][] = array('TYPE' => $comm['TYPE'], 'VALUE' => $comm['VALUE']);
					}
					unset($comm);

					if(!empty($item['COMMUNICATIONS']))
					{
						$items[$contactKey] = &$item;
					}
					unset($item);
				}
			}
		}

		// Try to get previous communications
		$invoiceComms = CCrmActivity::GetCommunicationsByOwner('INVOICE', $ownerID, $commType);
		foreach($invoiceComms as &$comm)
		{
			if(!CCrmActivity::PrepareCommunicationInfo($comm))
			{
				continue;
			}

			$commKey = "{$comm['ENTITY_TYPE']}_{$comm['ENTITY_ID']}";
			if(!isset($items[$commKey]))
			{
				$items[$commKey] = array(
					'OWNER_ID' => $ownerID,
					'OWNER_TYPE_ID' => CCrmOwnerType::Invoice,
					'TITLE' => $comm['TITLE'],
					'DESCRIPTION' => $comm['DESCRIPTION'],
					'IMAGE_URL' => '',
					'COMMUNICATIONS' => array()
				);

				$commEntityType = $comm['ENTITY_TYPE'];
				if(!isset($imageless[$commEntityType]))
				{
					$imageless[$commEntityType] = array();
				}
				$imageless[$commEntityType][] = $comm['ENTITY_ID'];
			}

			if($commType !== 'PERSON')
			{
				$isFound = false;
				foreach($items[$commKey]['COMMUNICATIONS'] as &$itemComm)
				{
					if($comm['VALUE'] === $itemComm['VALUE'])
					{
						$isFound = true;
						break;
					}
				}
				unset($itemComm);

				if(!$isFound)
				{
					$items[$commKey]['COMMUNICATIONS'][] = array('TYPE' => $comm['TYPE'], 'VALUE' => $comm['VALUE']);
				}
			}
		}
		unset($comm);
	}
}

foreach($imageless as $typeName => &$ids)
{
	if($typeName === CCrmOwnerType::ContactName)
	{
		$dbRes = CCrmContact::GetListEx(array(), array('@ID' => $ids), false, false, array('ID', 'PHOTO'));
		while($contact = $dbRes->Fetch())
		{
			$key = "CONTACT_{$contact['ID']}";
			if(isset($items[$key]))
			{
				$items[$key]['IMAGE_URL'] = CCrmMobileHelper::PrepareContactImageUrl($contact, array('WIDTH' => 40, 'HEIGHT' => 40));
			}
		}
	}
	elseif($typeName === CCrmOwnerType::CompanyName)
	{
		$dbRes = CCrmCompany::GetListEx(array(), array('@ID' => $ids), false, false, array('ID', 'LOGO'));
		while($company = $dbRes->Fetch())
		{
			$key = "COMPANY_{$company['ID']}";
			if(isset($items[$key]))
			{
				$items[$key]['IMAGE_URL'] = CCrmMobileHelper::PrepareCompanyImageUrl($company, array('WIDTH' => 40, 'HEIGHT' => 40));
			}
		}
	}
	elseif($typeName === CCrmOwnerType::LeadName)
	{
		foreach($ids as $id)
		{
			$key = "LEAD_{$id}";
			if(isset($items[$key]))
			{
				$items[$key]['IMAGE_URL'] = CCrmMobileHelper::GetLeadListImageStub();
			}
		}
	}
}
unset($ids);
unset($imageless);

$arResult['ITEMS'] = array_values($items);
unset($items);

$arResult['SEARCH_PAGE_URL'] = $APPLICATION->GetCurPageParam(
	'AJAX_CALL=Y&SEARCH=Y&FORMAT=json&apply_filter=Y&save=Y',
	array('AJAX_CALL', 'SEARCH', 'FORMAT', 'save', 'apply_filter', 'clear_filter')
);

$arResult['RELOAD_URL_TEMPLATE'] = $APPLICATION->GetCurPageParam(
	'AJAX_CALL=Y&FORMAT=json&type=#type#&owner_id=#owner_id#&owner_type=#owner_type#',
	array('AJAX_CALL', 'SEARCH', 'FORMAT', 'save', 'apply_filter', 'clear_filter', 'type', 'owner_id', 'owner_type')
);

$arResult['SEARCH_PLACEHOLDER'] = GetMessage("M_CRM_COMM_SELECT_SEARCH_PLACEHOLDER_{$commType}");

//$arResult['SERVICE_URL'] = SITE_DIR.'bitrix/components/bitrix/mobile.crm.comm.selector/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
$format = isset($_REQUEST['FORMAT']) ? strtolower($_REQUEST['FORMAT']) : '';
// Only JSON format is supported
if($format !== '' && $format !== 'json')
{
	$format = '';
}
$this->IncludeComponentTemplate($format);
