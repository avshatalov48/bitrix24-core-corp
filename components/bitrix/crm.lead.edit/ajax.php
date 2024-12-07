<?php

use Bitrix\Crm\Binding\ContactCompanyTable;
use Bitrix\Crm\Binding\LeadContactTable;
use Bitrix\Crm\EntityAdapterFactory;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Format\PersonNameFormatter;
use Bitrix\Crm\Integrity\Duplicate;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Integrity\DuplicateCriterion;
use Bitrix\Crm\Integrity\DuplicateEntity;
use Bitrix\Crm\Integrity\DuplicateSearchParams;
use Bitrix\Crm\Service\Container;

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
elseif ($action === 'FIND_DUPLICATES')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = (isset($_POST['PARAMS']) && is_array($_POST['PARAMS'])) ? $_POST['PARAMS'] : [];
	$entityTypeName = $params['ENTITY_TYPE_NAME'] ?? '';
	if ($entityTypeName === '')
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Entity type is not specified.']);
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if ($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Undefined entity type is specified.']);
	}

	if ($entityTypeID !== CCrmOwnerType::Lead)
	{
		__CrmLeadEditEndResponse(['ERROR' => "The '{$entityTypeName}' type is not supported in current context."]);
	}

	if (!(CCrmLead::CheckCreatePermission($userPermissions) || CCrmLead::CheckUpdatePermission(0, $userPermissions)))
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Access denied.']);
	}

	$userProfileUrlTemplate = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", "", SITE_ID);

	$checker = new \Bitrix\Crm\Integrity\LeadDuplicateChecker();
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
		if (isset($group['COMPANY_TITLE']))
		{
			$fieldNames[] = 'COMPANY_TITLE';
			$fields['COMPANY_TITLE'] = $group['COMPANY_TITLE'];
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
						$fields['FM']['PHONE'][] = ['VALUE' => $phone];
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
						$fields['FM']['EMAIL'][] = ['VALUE' => $email];
					}
				}
			}
		}
		$adapter = EntityAdapterFactory::create($fields, CCrmOwnerType::Lead);
		$dups = $checker->findDuplicates($adapter, new DuplicateSearchParams($fieldNames));

		$ignoredEntities = (array)($params['IGNORED_ITEMS'] ?? []);
		$entityInfoByType = [];
		foreach ($dups as $dup)
		{
			if (!($dup instanceof Duplicate))
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
				if (!($entity instanceof DuplicateEntity))
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
					$entityInfoByType[$entityTypeID] = [$entityID => []];
				}
				elseif (
					count($entityInfoByType[$entityTypeID]) < 50
					&& !isset($entityInfoByType[$entityTypeID][$entityID])
				)
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
				[
					'ENABLE_RESPONSIBLE' => true,
					'ENABLE_EDIT_URL' => true,
					'PHOTO_SIZE' => ['WIDTH' => 20, 'HEIGHT' => 20],
				]
			);

			$multiFieldResult = CCrmFieldMulti::GetListEx(
				[],
				[
					'=ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeID),
					'@ELEMENT_ID' => array_keys($entityInfos),
					'@TYPE_ID' => ['PHONE', 'EMAIL'],
				],
				false,
				false,
				['ELEMENT_ID', 'TYPE_ID', 'VALUE']
			);

			if (is_object($multiFieldResult))
			{
				$entityMultiFields[$entityTypeID] = [];
				while ($multiFields = $multiFieldResult->Fetch())
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
						$entityMultiFields[$entityTypeID][$entityID][$typeID] = [$value];
					}
					elseif (count($entityMultiFields[$entityTypeID][$entityID][$typeID]) < 10)
					{
						$entityMultiFields[$entityTypeID][$entityID][$typeID][] = $value;
					}
				}
			}
		}
		unset($entityInfos);

		$dupInfos = [];
		foreach ($dups as $dup)
		{
			if (!($dup instanceof Duplicate))
			{
				continue;
			}

			$entities = $dup->getEntities();
			$entityCount = is_array($entities) ? count($entities) : 0;
			if ($entityCount === 0)
			{
				continue;
			}

			$dupInfo = ['ENTITIES' => []];
			$criterionMatchType = '';
			$criterionMatchValue = '';
			$criterion = $dup->getCriterion();
			if ($criterion instanceof DuplicateCriterion)
			{
				$matches = $criterion->getMatches();

				if ($criterion instanceof DuplicateCommunicationCriterion)
				{
					$criterionMatchType = $matches['TYPE'];
					$criterionMatchValue = DuplicateCommunicationCriterion::prepareCode(
						$criterionMatchType,
						$matches['VALUE']
					);
				}

				$dupInfo['CRITERION'] = [
					'TYPE_NAME' => $criterion->getTypeName(),
					'MATCHES' => $matches,
				];
			}
			foreach ($entities as $entity)
			{
				if (!($entity instanceof DuplicateEntity))
				{
					continue;
				}

				$entityTypeID = $entity->getEntityTypeID();
				$entityID = $entity->getEntityID();

				$info = [
					'ENTITY_TYPE_ID' => $entityTypeID,
					'ENTITY_ID' => $entityID,
				];

				$isReadable = Container::getInstance()
					->getUserPermissions()
					->checkReadPermissions(
						$entityTypeID,
						$entityID
					)
				;

				if (
					$isReadable
					&& $entityTypeID === CCrmOwnerType::Company
					&& $entityID > 0
					&& CCrmCompany::isMyCompany((int)$entityID)
				)
				{
					$isReadable = $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
				}

				if ($isReadable)
				{
					if (isset($entityInfoByType[$entityTypeID][$entityID]))
					{
						$entityInfo = $entityInfoByType[$entityTypeID][$entityID];
						$info['CATEGORY_NAME'] =
							$entityInfo['CATEGORY_NAME'] ?? CCrmOwnerType::GetDescription($entityTypeID)
						;
						if (isset($entityInfo['TITLE']))
						{
							$info['TITLE'] = $entityInfo['TITLE'];
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
								['user_id' => $responsibleID, 'USER_ID' => $responsibleID]
							);
						}

						$isEditable = CCrmAuthorizationHelper::CheckUpdatePermission(
							$entityTypeName,
							$entityID,
							$userPermissions
						);

						if ($isEditable && isset($entityInfo['EDIT_URL']))
						{
							$info['URL'] = $entityInfo['EDIT_URL'];
						}
						elseif (isset($entityInfo['SHOW_URL']))
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
						foreach (['PHONE', 'EMAIL'] as $matchType)
						{
							if (isset($multiFields[$matchType]))
							{
								$info[$matchType] = $multiFields[$matchType];

								if (
									$criterionMatchType !== ''
									&& $criterionMatchValue !== ''
									&& $matchType === $criterionMatchType
									&& is_array($info[$matchType])
								)
								{
									foreach ($info[$matchType] as $index => $matchValue)
									{
										if (
											$criterionMatchValue === DuplicateCommunicationCriterion::prepareCode(
												$matchType,
												$matchValue
											)
										)
										{
											if (!isset($info['MATCH_INDEX'][$matchType]))
											{
												$info['MATCH_INDEX'][$matchType] = [];
											}
											$info['MATCH_INDEX'][$matchType][] = $index;
										}
									}
								}
							}
						}
					}
				}
				else
				{
					$info['TITLE'] = CCrmViewHelper::GetHiddenEntityCaption($entityTypeID);
					$info['IS_HIDDEN'] = 'Y';
				}

				$dupInfo['ENTITIES'][] = &$info;
				unset($info);
			}

			$dupInfos[] = &$dupInfo;
			unset($dupInfo);
		}

		$groupResults[] = [
			'DUPLICATES' => &$dupInfos,
			'GROUP_ID' => $group['GROUP_ID'] ?? '',
			'FIELD_ID' => $group['FIELD_ID'] ?? '',
			'HASH_CODE' => (int)($group['HASH_CODE'] ?? 0),
			'TOTAL_DUPLICATES' => $totalEntities,
			'ENTITY_TOTAL_TEXT' => Duplicate::entityCountToText($totalEntities),
		];
		unset($dupInfos);
	}
	unset($group);

	__CrmLeadEditEndResponse(['GROUP_RESULTS' => $groupResults]);
}
elseif ($action === 'FIND_LOCALITIES')
{
	$localityType = $_POST['LOCALITY_TYPE'] ?? 'COUNTRY';
	$needle = $_POST['NEEDLE'] ?? '';
	if ($localityType === 'COUNTRY')
	{
		$result = EntityAddress::getCountries(['CAPTION' => $needle]);
		__CrmLeadEditEndResponse(['DATA' => ['ITEMS' => $result]]);
	}
	else
	{
		__CrmLeadEditEndResponse(['ERROR' => "Locality '$localityType' is not supported in current context."]);
	}
}
elseif ($action === 'GET_SECONDARY_ENTITY_INFOS')
{
	$userID = CCrmSecurityHelper::GetCurrentUserID();
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	if ($userID <= 0 || !CCrmDeal::CheckReadPermission(0, $userPermissions))
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Access denied.']);
	}

	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : [];

	$ownerTypeName = $params['OWNER_TYPE_NAME'] ?? '';
	if ($ownerTypeName === '')
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Owner type is not specified.']);
	}

	$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
	if ($ownerTypeID === CCrmOwnerType::Undefined)
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Undefined owner type is specified.']);
	}

	if ($ownerTypeID !== CCrmOwnerType::Lead)
	{
		$typeName = CCrmOwnerType::ResolveName($ownerTypeID);
		__CrmLeadEditEndResponse(['ERROR' => "Type '$typeName' is not supported in current context."]);
	}

	$primaryTypeName = $params['PRIMARY_TYPE_NAME'] ?? '';
	if ($primaryTypeName === '')
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Primary type is not specified.']);
	}

	$primaryTypeID = CCrmOwnerType::ResolveID($primaryTypeName);
	if ($primaryTypeID !== CCrmOwnerType::Company)
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Primary type is not supported in current context.']);
	}

	$primaryID = isset($params['PRIMARY_ID']) ? (int)$params['PRIMARY_ID'] : 0;
	if ($primaryID <= 0)
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Primary ID is not specified.']);
	}

	$secondaryTypeName = $params['SECONDARY_TYPE_NAME'] ?? '';
	if ($secondaryTypeName === '')
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Secondary type is not specified.']);
	}

	$secondaryTypeID = CCrmOwnerType::ResolveID($secondaryTypeName);
	if ($secondaryTypeID !== CCrmOwnerType::Contact)
	{
		__CrmLeadEditEndResponse(['ERROR' => 'Secondary type is not supported in current context.']);
	}

	$dbResult = CCrmLead::GetListEx(
		['ID' => 'DESC'],
		[
			'=COMPANY_ID' => $primaryID,
			'=ASSIGNED_BY_ID' => $userID,
			'=IS_RETURN_CUSTOMER' => 'Y',
			'CHECK_PERMISSIONS' => 'N',
		],
		false,
		['nTopCount' => 5],
		['ID']
	);

	$ownerIDs = [];
	while ($ary = $dbResult->Fetch())
	{
		$ownerIDs[] = (int)$ary['ID'];
	}

	$secondaryIDs = [];
	foreach ($ownerIDs as $ownerID)
	{
		$entityIDs = LeadContactTable::getLeadContactIDs($ownerID);
		foreach ($entityIDs as $entityID)
		{
			if (CCrmContact::CheckReadPermission($entityID, $userPermissions))
			{
				$secondaryIDs[] = $entityID;
			}
		}

		if (!empty($secondaryIDs))
		{
			break;
		}
	}

	if (empty($secondaryIDs))
	{
		$secondaryIDs = ContactCompanyTable::getCompanyContactIDs($primaryID);
	}

	$secondaryInfos = [];
	foreach ($secondaryIDs as $entityID)
	{
		if (!CCrmContact::CheckReadPermission($entityID, $userPermissions))
		{
			continue;
		}

		$secondaryInfos[] = CCrmEntitySelectorHelper::PrepareEntityInfo(
			CCrmOwnerType::ContactName,
			$entityID,
			[
				'ENTITY_EDITOR_FORMAT' => true,
				'REQUIRE_REQUISITE_DATA' => true,
				'REQUIRE_MULTIFIELDS' => true,
				'NAME_TEMPLATE' => PersonNameFormatter::getFormat(),
			]
		);
	}

	__CrmLeadEditEndResponse(['ENTITY_INFOS' => $secondaryInfos]);
}
else
{
	__CrmLeadEditEndResponse(['ERROR' => "Action '$action' is not supported in current context."]);
}
