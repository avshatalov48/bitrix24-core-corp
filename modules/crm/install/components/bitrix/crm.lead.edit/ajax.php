<?php
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmLeadEditEndResponse'))
{
	function __CrmLeadEditEndResponse($result)
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
	__CrmLeadEditEndResponse(array('ERROR' => 'Could not include crm module.'));
}

/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 *  'ENABLE_SONET_SUBSCRIPTION'
 *  'FIND_DUPLICATES'
 *  'FIND_LOCALITIES'
 */
if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
{
	__CrmLeadEditEndResponse(array('ERROR' => 'Access denied.'));
}
if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
	__CrmLeadEditEndResponse(array('ERROR' => 'Request method is not allowed.'));
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if($action === 'ENABLE_SONET_SUBSCRIPTION')
{
	$userID = CCrmSecurityHelper::GetCurrentUserID();
	$entityTypeName = isset($_POST['ENTITY_TYPE'])? mb_strtoupper($_POST['ENTITY_TYPE']) : '';
	$entityID = isset($_POST['ENTITY_ID']) ? intval($_POST['ENTITY_ID']) : 0;
	if($userID > 0 && $entityTypeName === CCrmOwnerType::LeadName && $entityID > 0 && CCrmLead::CheckReadPermission($entityID))
	{

		$isEnabled = CCrmSonetSubscription::IsRelationRegistered(
			CCrmOwnerType::Lead,
			$entityID,
			CCrmSonetSubscriptionType::Observation,
			$userID
		);

		$enable = isset($_POST['ENABLE']) && mb_strtoupper($_POST['ENABLE']) === 'Y' ;

		if ($isEnabled !== $enable && \Bitrix\Crm\Settings\Crm::isLiveFeedRecordsGenerationEnabled())
		{
			if ($enable)
			{
				CCrmSonetSubscription::RegisterSubscription(CCrmOwnerType::Lead, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
			else
			{
				CCrmSonetSubscription::UnRegisterSubscription(CCrmOwnerType::Lead, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
		}
	}
}
elseif($action === 'FIND_DUPLICATES')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName === '')
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Lead)
	{
		__CrmLeadEditEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if(!(CCrmLead::CheckCreatePermission($userPermissions) || CCrmLead::CheckUpdatePermission(0, $userPermissions)))
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Access denied.'));
	}

	$userProfileUrlTemplate = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", "", SITE_ID);

	$checker = new \Bitrix\Crm\Integrity\LeadDuplicateChecker();
	$groupResults = array();
	$groupData = isset($params['GROUPS']) && is_array($params['GROUPS']) ? $params['GROUPS'] : array();
	foreach($groupData as &$group)
	{
		$fields = array();
		$fieldNames = array();
		if(isset($group['LAST_NAME']))
		{
			$fieldNames[] = 'LAST_NAME';
			$fields['LAST_NAME'] = $group['LAST_NAME'];
		}
		if(isset($group['NAME']))
		{
			$fieldNames[] = 'NAME';
			$fields['NAME'] = $group['NAME'];
		}
		if(isset($group['SECOND_NAME']))
		{
			$fieldNames[] = 'SECOND_NAME';
			$fields['SECOND_NAME'] = $group['SECOND_NAME'];
		}
		if(isset($group['COMPANY_TITLE']))
		{
			$fieldNames[] = 'COMPANY_TITLE';
			$fields['COMPANY_TITLE'] = $group['COMPANY_TITLE'];
		}

		$phones = isset($group['PHONES']) ? $group['PHONES'] : (isset($group['PHONE']) ? $group['PHONE'] : null);
		$hasPhones = is_array($phones) && !empty($phones);

		$emails = isset($group['EMAILS']) ? $group['EMAILS'] : (isset($group['EMAIL']) ? $group['EMAIL'] : null);
		$hasEmails = is_array($emails) && !empty($emails);

		if($hasPhones || $hasEmails)
		{
			$fields['FM'] = array();
			if($hasPhones)
			{
				$fieldNames[] = 'FM.PHONE';
				$fields['FM']['PHONE'] = array();
				foreach($phones as $phone)
				{
					if(is_string($phone) && $phone !== '')
					{
						$fields['FM']['PHONE'][] = array('VALUE' => $phone);
					}
				}
			}
			if($hasEmails)
			{
				$fieldNames[] = 'FM.EMAIL';
				$fields['FM']['EMAIL'] = array();
				foreach($emails as $email)
				{
					if(is_string($email) && $email !== '')
					{
						$fields['FM']['EMAIL'][] = array('VALUE' => $email);
					}
				}
			}
		}
		$adapter = \Bitrix\Crm\EntityAdapterFactory::create($fields, CCrmOwnerType::Lead);
		$dups = $checker->findDuplicates($adapter, new \Bitrix\Crm\Integrity\DuplicateSearchParams($fieldNames));

		$ignoredEntities = (array)($params['IGNORED_ITEMS'] ?? []);
		$entityInfoByType = array();
		foreach($dups as &$dup)
		{
			if(!($dup instanceof \Bitrix\Crm\Integrity\Duplicate))
			{
				continue;
			}

			$entities = $dup->getEntities();
			if(!(is_array($entities) && !empty($entities)))
			{
				continue;
			}

			//Each entity type limited by 50 items
			foreach($entities as &$entity)
			{
				if(!($entity instanceof \Bitrix\Crm\Integrity\DuplicateEntity))
				{
					continue;
				}

				$entityTypeID = $entity->getEntityTypeID();
				$entityID = $entity->getEntityID();

				$isIgnoredEntity = false;
				foreach ($ignoredEntities as $ignoredEntity)
				{
					if (
						$ignoredEntity['ENTITY_TYPE_ID'] == $entityTypeID
						&& $ignoredEntity['ENTITY_ID'] == $entityID
					)
					{
						$dup->removeEntity($entity);
						$isIgnoredEntity = true;
					}
				}
				if ($isIgnoredEntity)
				{
					continue;
				}

				if(!isset($entityInfoByType[$entityTypeID]))
				{
					$entityInfoByType[$entityTypeID] = array($entityID => array());
				}
				elseif(count($entityInfoByType[$entityTypeID]) < 50 && !isset($entityInfoByType[$entityTypeID][$entityID]))
				{
					$entityInfoByType[$entityTypeID][$entityID] = array();
				}
			}
		}

		$totalEntities = 0;
		$entityMultiFields = array();
		foreach($entityInfoByType as $entityTypeID => &$entityInfos)
		{
			$totalEntities += count($entityInfos);
			CCrmOwnerType::PrepareEntityInfoBatch(
				$entityTypeID,
				$entityInfos,
				false,
				array(
					'ENABLE_RESPONSIBLE' => true,
					'ENABLE_EDIT_URL' => true,
					'PHOTO_SIZE' => array('WIDTH'=> 21, 'HEIGHT'=> 21)
				)
			);

			$multiFieldResult = CCrmFieldMulti::GetListEx(
				array(),
				array(
					'=ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeID),
					'@ELEMENT_ID' => array_keys($entityInfos),
					'@TYPE_ID' => array('PHONE', 'EMAIL')
				),
				false,
				false,
				array('ELEMENT_ID', 'TYPE_ID', 'VALUE')
			);

			if(is_object($multiFieldResult))
			{
				$entityMultiFields[$entityTypeID] = array();
				while($multiFields = $multiFieldResult->Fetch())
				{
					$entityID = isset($multiFields['ELEMENT_ID']) ? intval($multiFields['ELEMENT_ID']) : 0;
					if($entityID <= 0)
					{
						continue;
					}

					if(!isset($entityMultiFields[$entityTypeID][$entityID]))
					{
						$entityMultiFields[$entityTypeID][$entityID] = array();
					}

					$typeID = isset($multiFields['TYPE_ID']) ? $multiFields['TYPE_ID'] : '';
					$value = isset($multiFields['VALUE']) ? $multiFields['VALUE'] : '';
					if($typeID === '' || $value === '')
					{
						continue;
					}

					if(!isset($entityMultiFields[$entityTypeID][$entityID][$typeID]))
					{
						$entityMultiFields[$entityTypeID][$entityID][$typeID] = array($value);
					}
					elseif(count($entityMultiFields[$entityTypeID][$entityID][$typeID]) < 10)
					{
						$entityMultiFields[$entityTypeID][$entityID][$typeID][] = $value;
					}
				}
			}
		}
		unset($entityInfos);

		$dupInfos = array();
		foreach($dups as &$dup)
		{
			if(!($dup instanceof \Bitrix\Crm\Integrity\Duplicate))
			{
				continue;
			}

			$entities = $dup->getEntities();
			$entityCount = is_array($entities) ? count($entities) : 0;
			if($entityCount === 0)
			{
				continue;
			}

			$dupInfo = array('ENTITIES' => array());
			foreach($entities as &$entity)
			{
				if(!($entity instanceof \Bitrix\Crm\Integrity\DuplicateEntity))
				{
					continue;
				}

				$entityTypeID = $entity->getEntityTypeID();
				$entityID = $entity->getEntityID();

				$info = array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID
				);

				if(isset($entityInfoByType[$entityTypeID]) && isset($entityInfoByType[$entityTypeID][$entityID]))
				{
					$entityInfo = $entityInfoByType[$entityTypeID][$entityID];
					if(isset($entityInfo['TITLE']))
					{
						$info['TITLE'] = $entityInfo['TITLE'];
					}
					if(isset($entityInfo['RESPONSIBLE_ID']))
					{
						$responsibleID = $entityInfo['RESPONSIBLE_ID'];

						$info['RESPONSIBLE_ID'] = $responsibleID;
						if(isset($entityInfo['RESPONSIBLE_FULL_NAME']))
						{
							$info['RESPONSIBLE_FULL_NAME'] = $entityInfo['RESPONSIBLE_FULL_NAME'];
						}
						if(isset($entityInfo['RESPONSIBLE_PHOTO_URL']))
						{
							$info['RESPONSIBLE_PHOTO_URL'] = $entityInfo['RESPONSIBLE_PHOTO_URL'];
						}
						$info['RESPONSIBLE_URL'] = CComponentEngine::MakePathFromTemplate(
							$userProfileUrlTemplate,
							array('user_id' => $responsibleID, 'USER_ID' => $responsibleID)
						);
					}

					$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
					$isReadable = CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, $entityID, $userPermissions);
					$isEditable = CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $userPermissions);

					if($isEditable && isset($entityInfo['EDIT_URL']))
					{
						$info['URL'] = $entityInfo['EDIT_URL'];
					}
					elseif($isReadable && isset($entityInfo['SHOW_URL']))
					{
						$info['URL'] = $entityInfo['SHOW_URL'];
					}
					else
					{
						$info['URL'] = '';
					}
				}

				if(isset($entityMultiFields[$entityTypeID])
					&& isset($entityMultiFields[$entityTypeID][$entityID]))
				{
					$multiFields = $entityMultiFields[$entityTypeID][$entityID];
					if(isset($multiFields['PHONE']))
					{
						$info['PHONE'] = $multiFields['PHONE'];
					}
					if(isset($multiFields['EMAIL']))
					{
						$info['EMAIL'] = $multiFields['EMAIL'];
					}
				}

				$dupInfo['ENTITIES'][] = &$info;
				unset($info);
			}
			unset($entity);

			$criterion = $dup->getCriterion();
			if($criterion instanceof \Bitrix\Crm\Integrity\DuplicateCriterion)
			{
				$dupInfo['CRITERION'] = array(
					'TYPE_NAME' => $criterion->getTypeName(),
					'MATCHES' => $criterion->getMatches()
				);
			}
			$dupInfos[] = &$dupInfo;
			unset($dupInfo);
		}
		unset($dup);

		$groupResults[] = array(
			'DUPLICATES' => &$dupInfos,
			'GROUP_ID' => isset($group['GROUP_ID']) ? $group['GROUP_ID'] : '',
			'FIELD_ID' => isset($group['FIELD_ID']) ? $group['FIELD_ID'] : '',
			'HASH_CODE' => isset($group['HASH_CODE']) ? intval($group['HASH_CODE']) : 0,
			'TOTAL_DUPLICATES' => $totalEntities,
			'ENTITY_TOTAL_TEXT' => \Bitrix\Crm\Integrity\Duplicate::entityCountToText($totalEntities)
		);
		unset($dupInfos);
	}
	unset($group);

	__CrmLeadEditEndResponse(array('GROUP_RESULTS' => $groupResults));
}
elseif($action === 'FIND_LOCALITIES')
{
	$localityType = isset($_POST['LOCALITY_TYPE']) ? $_POST['LOCALITY_TYPE'] : 'COUNTRY';
	$needle = isset($_POST['NEEDLE']) ? $_POST['NEEDLE'] : '';
	if($localityType === 'COUNTRY')
	{
		$result = \Bitrix\Crm\EntityAddress::getCountries(array('CAPTION' => $needle));
		__CrmLeadEditEndResponse(array('DATA' => array('ITEMS' => $result)));
	}
	else
	{
		__CrmLeadEditEndResponse(array('ERROR' => "Locality '{$localityType}' is not supported in current context."));
	}
}
elseif($action === 'GET_SECONDARY_ENTITY_INFOS')
{
	$userID = CCrmSecurityHelper::GetCurrentUserID();
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if($userID <= 0 || !CCrmDeal::CheckReadPermission(0, $userPermissions))
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Access denied.'));
	}


	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();

	$ownerTypeName = isset($params['OWNER_TYPE_NAME']) ? $params['OWNER_TYPE_NAME'] : '';
	if($ownerTypeName === '')
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Owner type is not specified.'));
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if($ownerTypeID === CCrmOwnerType::Undefined)
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Undefined owner type is specified.'));
	}

	if($ownerTypeID !== CCrmOwnerType::Lead)
	{
		$typeName = CCrmOwnerType::ResolveName($ownerTypeID);
		__CrmLeadEditEndResponse(array('ERROR' => "Type '{$typeName}' is not supported in current context."));
	}

	$primaryTypeName = isset($params['PRIMARY_TYPE_NAME']) ? $params['PRIMARY_TYPE_NAME'] : '';
	if($primaryTypeName === '')
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Primary type is not specified.'));
	}

	$primaryTypeID = CCrmOwnerType::ResolveID($primaryTypeName);
	if($primaryTypeID !== CCrmOwnerType::Company)
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Primary type is not supported in current context.'));
	}

	$primaryID = isset($params['PRIMARY_ID']) ? (int)$params['PRIMARY_ID'] : 0;
	if($primaryID <= 0)
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Primary ID is not specified.'));
	}

	$secondaryTypeName = isset($params['SECONDARY_TYPE_NAME']) ? $params['SECONDARY_TYPE_NAME'] : '';
	if($secondaryTypeName === '')
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Secondary type is not specified.'));
	}

	$secondaryTypeID = CCrmOwnerType::ResolveID($secondaryTypeName);
	if($secondaryTypeID !== CCrmOwnerType::Contact)
	{
		__CrmLeadEditEndResponse(array('ERROR' => 'Secondary type is not supported in current context.'));
	}

	$dbResult = CCrmLead::GetListEx(
		array('ID' => 'DESC'),
		array(
			'=COMPANY_ID' => $primaryID,
			'=ASSIGNED_BY_ID' => $userID,
			'=IS_RETURN_CUSTOMER' => 'Y',
			'CHECK_PERMISSIONS' => 'N'
		),
		false,
		array('nTopCount' => 5),
		array('ID')
	);

	$ownerIDs = array();
	while($ary = $dbResult->Fetch())
	{
		$ownerIDs[] = (int)$ary['ID'];
	}

	$secondaryIDs = array();
	foreach($ownerIDs as $ownerID)
	{
		$entityIDs = \Bitrix\Crm\Binding\LeadContactTable::getLeadContactIDs($ownerID);
		foreach($entityIDs as $entityID)
		{
			if(CCrmContact::CheckReadPermission($entityID, $userPermissions))
			{
				$secondaryIDs[] = $entityID;
			}
		}

		if(!empty($secondaryIDs))
		{
			break;
		}
	}

	if(empty($secondaryIDs))
	{
		$secondaryIDs = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($primaryID);
	}

	$secondaryInfos = array();
	foreach($secondaryIDs as $entityID)
	{
		if(!CCrmContact::CheckReadPermission($entityID, $userPermissions))
		{
			continue;
		}

		$secondaryInfos[]  = CCrmEntitySelectorHelper::PrepareEntityInfo(
			CCrmOwnerType::ContactName,
			$entityID,
			array(
				'ENTITY_EDITOR_FORMAT' => true,
				'REQUIRE_REQUISITE_DATA' => true,
				'REQUIRE_MULTIFIELDS' => true,
				'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
			)
		);
	}

	__CrmLeadEditEndResponse(array('ENTITY_INFOS' => $secondaryInfos));
}
else
{
	__CrmLeadEditEndResponse(array('ERROR' => "Action '{$action}' is not supported in current context."));
}
