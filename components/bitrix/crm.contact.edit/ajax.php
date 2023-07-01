<?php

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

global $DB, $APPLICATION;

if (!function_exists('__CrmContactEditEndResponse'))
{
	function __CrmContactEditEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if (!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}

		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');

		die();
	}
}

if (!CModule::IncludeModule('crm'))
{
	__CrmContactEditEndResponse(array('ERROR' => 'Could not include crm module.'));
}
/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 *  'SAVE_CONTACT'
 *  'ENABLE_SONET_SUBSCRIPTION'
 *  'FIND_DUPLICATES'
 *  'FIND_LOCALITIES'
 */
if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
{
	__CrmContactEditEndResponse(array('ERROR' => 'Access denied.'));
}

if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
	__CrmContactEditEndResponse(array('ERROR' => 'Request method is not allowed.'));
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
CUtil::JSPostUnescape();
$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = $_POST['ACTION'] ?? '';
if ($action === 'SAVE_CONTACT')
{
	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : [];
	if ( count($data) === 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
		die();
	}

	$ID = (int)($data['id'] ?? 0);
	$arFields = [
		'NAME' => $data['name'] ?? '',
		'SECOND_NAME' => $data['secondName'] ?? '',
		'LAST_NAME' => $data['lastName'] ?? ''
	];

	if (isset($data['export']))
	{
		$arFields['EXPORT'] = mb_strtoupper($data['export']) === 'Y' ? 'Y' : 'N';
	}

	$email = $data['email'] ?? '';
	if ($email !== '')
	{
		if (!isset($arFields['FM']))
		{
			$arFields['FM'] = [];
		}

		$arFields['FM']['EMAIL'] = [
			'n0' => [
				'VALUE_TYPE' => 'WORK',
				'VALUE' => $email
			]
		];
	}

	$phone = $data['phone'] ?? '';
	if ($phone !== '')
	{
		if (!isset($arFields['FM']))
		{
			$arFields['FM'] = [];
		}

		$arFields['FM']['PHONE'] = [
			'n0' => [
				'VALUE_TYPE' => 'WORK',
				'VALUE' => $phone
			]
		];
	}

	$CrmContact = new CCrmContact();
	if ($ID > 0)
	{
		if ($CrmContact->Update($ID, $arFields, true, array('DISABLE_USER_FIELD_CHECK' => true, 'REGISTER_SONET_EVENT' => true)))
		{
			$info = CCrmEntitySelectorHelper::PrepareEntityInfo('CONTACT', $ID);
			echo CUtil::PhpToJSObject(
				[
					'DATA' => $data,
					'INFO' => [
						'title' => $info['TITLE'],
						'url' => $info['URL']
					]
				]
			);
		}
	}
	else
	{
		$ID = $CrmContact->Add($arFields, true, array('DISABLE_USER_FIELD_CHECK' => true, 'REGISTER_SONET_EVENT' => true));
		if (is_int($ID) && $ID > 0)
		{
			$data['id'] = $ID;
			$info = CCrmEntitySelectorHelper::PrepareEntityInfo(
				'CONTACT',
				$ID,
				['NAME_TEMPLATE' => $_POST['NAME_TEMPLATE'] ?? '']
			);

			echo CUtil::PhpToJSObject(
				[
					'DATA' => $data,
					'INFO' => [
						'title' => $info['TITLE'],
						'url' => $info['URL']
					]
				]
			);
		}
		else
		{
			echo CUtil::PhpToJSObject(
				['ERROR' => $CrmContact->LAST_ERROR]
			);
		}
	}
}
elseif($action === 'ENABLE_SONET_SUBSCRIPTION')
{
	$userID = CCrmSecurityHelper::GetCurrentUserID();
	$entityTypeName = isset($_POST['ENTITY_TYPE']) ? mb_strtoupper($_POST['ENTITY_TYPE']) : '';
	$entityID = isset($_POST['ENTITY_ID']) ? (int)($_POST['ENTITY_ID']) : 0;
	if ($userID > 0 && $entityTypeName === CCrmOwnerType::ContactName && $entityID > 0 && CCrmContact::CheckReadPermission($entityID))
	{

		$isEnabled = CCrmSonetSubscription::IsRelationRegistered(
			CCrmOwnerType::Contact,
			$entityID,
			CCrmSonetSubscriptionType::Observation,
			$userID
		);

		$enable = isset($_POST['ENABLE']) && mb_strtoupper($_POST['ENABLE']) === 'Y';

		if ($isEnabled !== $enable && \Bitrix\Crm\Settings\Crm::isLiveFeedRecordsGenerationEnabled())
		{
			if ($enable)
			{
				CCrmSonetSubscription::RegisterSubscription(CCrmOwnerType::Contact, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
			else
			{
				CCrmSonetSubscription::UnRegisterSubscription(CCrmOwnerType::Contact, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
		}
	}
}
elseif($action === 'FIND_DUPLICATES')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : [];
	$entityTypeName = $params['ENTITY_TYPE_NAME'] ?? '';
	if ($entityTypeName === '')
	{
		__CrmContactEditEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if ($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmContactEditEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if ($entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmContactEditEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if (!(CCrmContact::CheckCreatePermission($userPermissions) || CCrmContact::CheckUpdatePermission(0, $userPermissions)))
	{
		__CrmContactEditEndResponse(array('ERROR' => 'Access denied.'));
	}

	$userProfileUrlTemplate = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", "", SITE_ID);

	$checker = new \Bitrix\Crm\Integrity\ContactDuplicateChecker();
	$groupResults = [];
	$groupData = isset($params['GROUPS']) && is_array($params['GROUPS']) ? $params['GROUPS'] : [];
	foreach ($groupData as &$group)
	{
		$fields = [];
		$fieldNames = [];
		if (isset($group['LAST_NAME']))
		{
			$fieldNames[] = 'LAST_NAME';
			$fields['LAST_NAME'] = $group['LAST_NAME'];
		}
		if (isset($group['NAME']))
		{
			$fieldNames[] = 'NAME';
			$fields['NAME'] = $group['NAME'];
		}
		if (isset($group['SECOND_NAME']))
		{
			$fieldNames[] = 'SECOND_NAME';
			$fields['SECOND_NAME'] = $group['SECOND_NAME'];
		}

		$phones = $group['PHONES'] ?? ($group['PHONE'] ?? null);
		$hasPhones = is_array($phones) && !empty($phones);

		$emails = $group['EMAILS'] ?? ($group['EMAIL'] ?? null);
		$hasEmails = is_array($emails) && !empty($emails);

		if ($hasPhones || $hasEmails)
		{
			$fields['FM'] = [];
			if ($hasPhones)
			{
				$fieldNames[] = 'FM.PHONE';
				$fields['FM']['PHONE'] = [];
				foreach ($phones as $phone)
				{
					if (is_string($phone) && $phone !== '')
					{
						$fields['FM']['PHONE'][] = array('VALUE' => $phone);
					}
				}
			}
			if ($hasEmails)
			{
				$fieldNames[] = 'FM.EMAIL';
				$fields['FM']['EMAIL'] = [];
				foreach ($emails as $email)
				{
					if (is_string($email) && $email !== '')
					{
						$fields['FM']['EMAIL'][] = array('VALUE' => $email);
					}
				}
			}
		}

		// region Requisites
		$requisiteList = [];

		$duplicateRequsiiteFieldsMap = Bitrix\Crm\EntityRequisite::getDuplicateCriterionFieldsMap();
		foreach ($duplicateRequsiiteFieldsMap as $countryId => $requisiteDupFields)
		{
			foreach ($requisiteDupFields as $requsiiteDupFieldName)
			{
				$groupId = $requsiiteDupFieldName.'|'.$countryId;
				if (isset($group[$groupId]) && is_array($group[$groupId]))
				{
					foreach ($group[$groupId] as $requisiteFields)
					{
						if (
							!empty($requisiteFields['ID'])
							&& $requisiteFields['PRESET_ID'] > 0
							&& $requisiteFields['PRESET_COUNTRY_ID'] > 0
							&& count($requisiteFields) > 3
						)
						{
							$requisitePseudoId = $requisiteFields['ID'];
							$presetId = (int)$requisiteFields['PRESET_ID'];
							$presetCountryId = (int)$requisiteFields['PRESET_COUNTRY_ID'];
							foreach ($requisiteFields as $fieldName => $value)
							{
								if (in_array($fieldName, $duplicateRequsiiteFieldsMap[$countryId], true))
								{
									if (!isset($requisiteList[$requisitePseudoId]))
									{
										$requisiteList[$requisitePseudoId] = array(
											'ID' => $requisitePseudoId,
											'PRESET_ID' => $presetId,
											'PRESET_COUNTRY_ID' => $presetCountryId
										);
									}
									$requisiteList[$requisitePseudoId][$fieldName] = $value;
								}
							}
						}
					}
				}
			}
		}

		unset(
			$duplicateRequsiiteFieldsMap,
			$countryId,
			$requisiteDupFields,
			$requsiiteDupFieldName,
			$groupId,
			$requisiteFields,
			$requisitePseudoId,
			$presetId,
			$presetCountryId,
			$fieldName,
			$value
		);

		// region Bank details
		$duplicateBankDetailFieldsMap = Bitrix\Crm\EntityBankDetail::getDuplicateCriterionFieldsMap();
		foreach ($duplicateBankDetailFieldsMap as $countryId => $bankDetailDupFields)
		{
			foreach ($bankDetailDupFields as $bankDetailDupFieldName)
			{
				$groupId = $bankDetailDupFieldName.'|'.$countryId;
				if (isset($group[$groupId]) && is_array($group[$groupId]))
				{
					foreach ($group[$groupId] as $bankDetailFields)
					{
						if (!empty($bankDetailFields['ID']) && !empty($bankDetailFields['REQUISITE_ID'])
							&& $bankDetailFields['PRESET_ID'] > 0 && $bankDetailFields['PRESET_COUNTRY_ID'] > 0
							&& count($bankDetailFields) > 4)
						{
							$bankDetailPseudoId = $bankDetailFields['ID'];
							$requisitePseudoId = $bankDetailFields['REQUISITE_ID'];
							$presetId = (int)$bankDetailFields['PRESET_ID'];
							$presetCountryId = (int)$bankDetailFields['PRESET_COUNTRY_ID'];
							foreach ($bankDetailFields as $fieldName => $value)
							{
								if (in_array($fieldName, $duplicateBankDetailFieldsMap[$countryId], true))
								{
									if (!isset($requisiteList[$requisitePseudoId]))
									{
										$requisiteList[$requisitePseudoId] = array(
											'ID' => $requisitePseudoId,
											'PRESET_ID' => $presetId,
											'PRESET_COUNTRY_ID' => $presetCountryId
										);
									}
									if (!isset($requisiteList[$requisitePseudoId]['BD']))
										$requisiteList[$requisitePseudoId]['BD'] = [];
									$bankDetailList = &$requisiteList[$requisitePseudoId]['BD'];
									if (!isset($bankDetailList[$bankDetailPseudoId]))
									{
										$bankDetailList[$bankDetailPseudoId] = array(
											'ID' => $bankDetailPseudoId,
											'ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
											'ENTITY_ID' => $requisitePseudoId,
											'COUNTRY_ID' => $presetCountryId
										);
									}
									$bankDetailList[$bankDetailPseudoId][$fieldName] = $value;
									unset($bankDetailList);
								}
							}
						}
					}
				}
			}
		}
		unset(
			$duplicateBankDetailFieldsMap,
			$countryId,
			$bankDetailDupFields,
			$bankDetailDupFieldName,
			$groupId,
			$bankDetailFields,
			$bankDetailPseudoId,
			$requisitePseudoId,
			$presetId,
			$presetCountryId,
			$fieldName,
			$value
		);
		// endregion Bank details

		if (!empty($requisiteList))
		{
			$fields['RQ'] = $requisiteList;
		}
		// endregion Requisites

		$adapter = \Bitrix\Crm\EntityAdapterFactory::create($fields, CCrmOwnerType::Contact);
		$dups = $checker->findDuplicates($adapter, new \Bitrix\Crm\Integrity\DuplicateSearchParams($fieldNames));

		$ignoredEntities = (array)($params['IGNORED_ITEMS'] ?? []);
		$entityInfoByType = [];
		foreach ($dups as &$dup)
		{
			if (!($dup instanceof \Bitrix\Crm\Integrity\Duplicate))
			{
				continue;
			}

			$entities = $dup->getEntities();
			if (!(is_array($entities) && !empty($entities)))
			{
				continue;
			}

			//Each entity type limited by 50 items
			foreach ($entities as &$entity)
			{
				if (!($entity instanceof \Bitrix\Crm\Integrity\DuplicateEntity))
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

				if (!isset($entityInfoByType[$entityTypeID]))
				{
					$entityInfoByType[$entityTypeID] = array($entityID => []);
				}
				elseif(count($entityInfoByType[$entityTypeID]) < 50 && !isset($entityInfoByType[$entityTypeID][$entityID]))
				{
					$entityInfoByType[$entityTypeID][$entityID] = [];
				}
			}
		}

		$totalEntities = 0;
		$entityMultiFields = [];
		foreach ($entityInfoByType as $entityTypeID => &$entityInfos)
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
				[],
				array(
					'=ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeID),
					'@ELEMENT_ID' => array_keys($entityInfos),
					'@TYPE_ID' => array('PHONE', 'EMAIL')
				),
				false,
				false,
				array('ELEMENT_ID', 'TYPE_ID', 'VALUE')
			);

			if (is_object($multiFieldResult))
			{
				$entityMultiFields[$entityTypeID] = [];
				while($multiFields = $multiFieldResult->Fetch())
				{
					$entityID = isset($multiFields['ELEMENT_ID']) ? intval($multiFields['ELEMENT_ID']) : 0;
					if ($entityID <= 0)
					{
						continue;
					}

					if (!isset($entityMultiFields[$entityTypeID][$entityID]))
					{
						$entityMultiFields[$entityTypeID][$entityID] = [];
					}

					$typeID = $multiFields['TYPE_ID'] ?? '';
					$value = $multiFields['VALUE'] ?? '';
					if ($typeID === '' || $value === '')
					{
						continue;
					}

					if (!isset($entityMultiFields[$entityTypeID][$entityID][$typeID]))
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

		$dupInfos = [];
		foreach ($dups as &$dup)
		{
			if (!($dup instanceof \Bitrix\Crm\Integrity\Duplicate))
			{
				continue;
			}

			$entities = $dup->getEntities();
			$entityCount = is_array($entities) ? count($entities) : 0;
			if ($entityCount === 0)
			{
				continue;
			}

			$dupInfo = array('ENTITIES' => []);
			foreach ($entities as &$entity)
			{
				if (!($entity instanceof \Bitrix\Crm\Integrity\DuplicateEntity))
				{
					continue;
				}

				$entityTypeID = $entity->getEntityTypeID();
				$entityID = $entity->getEntityID();

				$info = array(
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID
				);

				if (isset($entityInfoByType[$entityTypeID]) && isset($entityInfoByType[$entityTypeID][$entityID]))
				{
					$entityInfo = $entityInfoByType[$entityTypeID][$entityID];
					if (isset($entityInfo['TITLE']))
					{
						$info['TITLE'] = $entityInfo['TITLE'];
					}
					if (isset($entityInfo['POST']))
					{
						$info['POST'] = $entityInfo['POST'];
					}
					if (isset($entityInfo['RESPONSIBLE_ID']))
					{
						$responsibleID = $entityInfo['RESPONSIBLE_ID'];

						$info['RESPONSIBLE_ID'] = $responsibleID;
						if (isset($entityInfo['RESPONSIBLE_FULL_NAME']))
						{
							$info['RESPONSIBLE_FULL_NAME'] = $entityInfo['RESPONSIBLE_FULL_NAME'];
						}
						if (isset($entityInfo['RESPONSIBLE_PHOTO_URL']))
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

					if ($isEditable && isset($entityInfo['EDIT_URL']))
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

				if (
					isset($entityMultiFields[$entityTypeID])
					&& isset($entityMultiFields[$entityTypeID][$entityID])
				)
				{
					$multiFields = $entityMultiFields[$entityTypeID][$entityID];
					if (isset($multiFields['PHONE']))
					{
						$info['PHONE'] = $multiFields['PHONE'];
					}

					if (isset($multiFields['EMAIL']))
					{
						$info['EMAIL'] = $multiFields['EMAIL'];
					}
				}

				$dupInfo['ENTITIES'][] = &$info;
				unset($info);
			}
			unset($entity);

			$criterion = $dup->getCriterion();
			if ($criterion instanceof \Bitrix\Crm\Integrity\DuplicateCriterion)
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
			'GROUP_ID' => $group['GROUP_ID'] ?? '',
			'FIELD_ID' => $group['FIELD_ID'] ?? '',
			'HASH_CODE' => (int)($group['HASH_CODE'] ?? 0),
			'TOTAL_DUPLICATES' => $totalEntities,
			'ENTITY_TOTAL_TEXT' => \Bitrix\Crm\Integrity\Duplicate::entityCountToText($totalEntities)
		);
		unset($dupInfos);
	}
	unset($group);

	__CrmContactEditEndResponse(array('GROUP_RESULTS' => $groupResults));
}
elseif($action === 'FIND_LOCALITIES')
{
	$localityType = $_POST['LOCALITY_TYPE'] ?? 'COUNTRY';
	$needle = $_POST['NEEDLE'] ?? '';
	if ($localityType === 'COUNTRY')
	{
		$result = \Bitrix\Crm\EntityAddress::getCountries(array('CAPTION' => $needle));
		__CrmContactEditEndResponse(array('DATA' => array('ITEMS' => $result)));
	}
	else
	{
		__CrmContactEditEndResponse(array('ERROR' => "Locality '{$localityType}' is not supported in current context."));
	}
}
else
{
	__CrmContactEditEndResponse(array('ERROR' => "Action '{$action}' is not supported in current context."));
}
