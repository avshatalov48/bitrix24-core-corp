<?php

use Bitrix\Crm\EntityAdapterFactory;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Integrity\CompanyDuplicateChecker;
use Bitrix\Crm\Integrity\Duplicate;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Integrity\DuplicateCriterion;
use Bitrix\Crm\Integrity\DuplicateEntity;
use Bitrix\Crm\Integrity\DuplicateSearchParams;

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if (!function_exists('__CrmCompanyEditEndResponse'))
{
	function __CrmCompanyEditEndResponse($result)
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
	__CrmCompanyEditEndResponse(array('ERROR' => 'Could not include crm module.'));
}
/*
 * ONLY 'POST' METHOD SUPPORTED
 * SUPPORTED ACTIONS:
 * 'SAVE_COMPANY'
 * 'ENABLE_SONET_SUBSCRIPTION'
 * 'FIND_DUPLICATES'
 * 'FIND_LOCALITIES'
 */

if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
{
	__CrmCompanyEditEndResponse(array('ERROR' => 'Access denied.'));
}
if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
	__CrmCompanyEditEndResponse(array('ERROR' => 'Request method is not allowed.'));
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
CUtil::JSPostUnescape();
$GLOBALS['APPLICATION']->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = $_POST['ACTION'] ?? '';
if ($action === 'SAVE_COMPANY')
{
	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : [];
	if (count($data) === 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'SOURCE DATA ARE NOT FOUND!'));
		die();
	}

	$arFields = [
		'TITLE' => $data['title'] ?? '',
		'COMPANY_TYPE' => $data['companyType'] ?? '',
		'INDUSTRY' => $data['industry'] ?? '',
		'ADDRESS_LEGAL' => $data['addressLegal'] ?? '',
		'FM' => []
	];

	$email = $data['email'] ?? '';
	if ($email !== '')
	{
		if (!check_email($email))
		{
			echo CUtil::PhpToJSObject(
				array('ERROR' => GetMessage('CRM_COMPANY_EDIT_INVALID_EMAIL', array('#VALUE#' => $email)))
			);
			die();
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
		$arFields['FM']['PHONE'] = [
			'n0' => [
				'VALUE_TYPE' => 'WORK',
				'VALUE' => $phone
			]
		];
	}

	$CrmCompany = new CCrmCompany();
	$ID = $CrmCompany->Add($arFields, true, array('DISABLE_USER_FIELD_CHECK' => true, 'REGISTER_SONET_EVENT' => true));
	if (is_int($ID) && $ID > 0)
	{
		$data['id'] = $ID;
		$info = CCrmEntitySelectorHelper::PrepareEntityInfo('COMPANY', $ID);
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
			array('ERROR' => $CrmCompany->LAST_ERROR)
		);
	}
}
elseif($action === 'ENABLE_SONET_SUBSCRIPTION')
{
	$userID = CCrmSecurityHelper::GetCurrentUserID();
	$entityTypeName = isset($_POST['ENTITY_TYPE']) ? mb_strtoupper($_POST['ENTITY_TYPE']) : '';
	$entityID = isset($_POST['ENTITY_ID']) ? (int)($_POST['ENTITY_ID']) : 0;
	if (
		$userID > 0 
		&& $entityTypeName === CCrmOwnerType::CompanyName 
		&& $entityID > 0 
		&& CCrmCompany::CheckReadPermission($entityID)
	)
	{

		$isEnabled = CCrmSonetSubscription::IsRelationRegistered(
			CCrmOwnerType::Company,
			$entityID,
			CCrmSonetSubscriptionType::Observation,
			$userID
		);

		$enable = isset($_POST['ENABLE']) && mb_strtoupper($_POST['ENABLE']) === 'Y' ;

		if ($isEnabled !== $enable && \Bitrix\Crm\Settings\Crm::isLiveFeedRecordsGenerationEnabled())
		{
			if ($enable)
			{
				CCrmSonetSubscription::RegisterSubscription(CCrmOwnerType::Company, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
			}
			else
			{
				CCrmSonetSubscription::UnRegisterSubscription(CCrmOwnerType::Company, $entityID, CCrmSonetSubscriptionType::Observation, $userID);
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
		__CrmCompanyEditEndResponse(['ERROR' => 'Entity type is not specified.']);
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if ($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmCompanyEditEndResponse(['ERROR' => 'Undefined entity type is specified.']);
	}

	if ($entityTypeID !== CCrmOwnerType::Company)
	{
		__CrmCompanyEditEndResponse(['ERROR' => "The '{$entityTypeName}' type is not supported in current context."]);
	}

	if (
		!(CCrmCompany::CheckCreatePermission($userPermissions)
			|| CCrmCompany::CheckUpdatePermission(0, $userPermissions)
		)
	)
	{
		__CrmCompanyEditEndResponse(['ERROR' => 'Access denied.']);
	}

	$userProfileUrlTemplate = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", "", SITE_ID);

	$checker = new CompanyDuplicateChecker();
	$checker->setStrictComparison(false);

	$groupResults = [];
	$groupData = isset($params['GROUPS']) && is_array($params['GROUPS']) ? $params['GROUPS'] : [];
	foreach ($groupData as $group)
	{
		$fields = [];
		$fieldNames = [];
		if (isset($group['TITLE']))
		{
			$fieldNames[] = 'TITLE';
			$fields['TITLE'] = $group['TITLE'];
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

		// region Requisites
		$requisiteList = [];

		$duplicateRequsiiteFieldsMap = Bitrix\Crm\EntityRequisite::getDuplicateCriterionFieldsMap();
		foreach ($duplicateRequsiiteFieldsMap as $countryId => $requisiteDupFields)
		{
			foreach ($requisiteDupFields as $requsiiteDupFieldName)
			{
				$groupId = $requsiiteDupFieldName . '|' . $countryId;
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
								if (in_array($fieldName, $requisiteDupFields, true))
								{
									if (!isset($requisiteList[$requisitePseudoId]))
									{
										$requisiteList[$requisitePseudoId] = [
											'ID' => $requisitePseudoId,
											'PRESET_ID' => $presetId,
											'PRESET_COUNTRY_ID' => $presetCountryId,
										];
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
				$groupId = $bankDetailDupFieldName . '|' . $countryId;
				if (isset($group[$groupId]) && is_array($group[$groupId]))
				{
					foreach ($group[$groupId] as $bankDetailFields)
					{
						if (
							!empty($bankDetailFields['ID']) && !empty($bankDetailFields['REQUISITE_ID'])
							&& $bankDetailFields['PRESET_ID'] > 0
							&& $bankDetailFields['PRESET_COUNTRY_ID'] > 0
							&& count($bankDetailFields) > 4
						)
						{
							$bankDetailPseudoId = $bankDetailFields['ID'];
							$requisitePseudoId = $bankDetailFields['REQUISITE_ID'];
							$presetId = (int)$bankDetailFields['PRESET_ID'];
							$presetCountryId = (int)$bankDetailFields['PRESET_COUNTRY_ID'];
							foreach ($bankDetailFields as $fieldName => $value)
							{
								if (in_array($fieldName, $bankDetailDupFields, true))
								{
									if (!isset($requisiteList[$requisitePseudoId]))
									{
										$requisiteList[$requisitePseudoId] = [
											'ID' => $requisitePseudoId,
											'PRESET_ID' => $presetId,
											'PRESET_COUNTRY_ID' => $presetCountryId,
										];
									}
									if (!isset($requisiteList[$requisitePseudoId]['BD']))
									{
										$requisiteList[$requisitePseudoId]['BD'] = [];
									}
									$bankDetailList = &$requisiteList[$requisitePseudoId]['BD'];
									if (!isset($bankDetailList[$bankDetailPseudoId]))
									{
										$bankDetailList[$bankDetailPseudoId] = [
											'ID' => $bankDetailPseudoId,
											'ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
											'ENTITY_ID' => $requisitePseudoId,
											'COUNTRY_ID' => $presetCountryId,
										];
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

		$adapter = EntityAdapterFactory::create($fields, CCrmOwnerType::Company);
		$dups = $checker->findDuplicates($adapter, new DuplicateSearchParams($fieldNames));

		$ignoredEntities = (array)($params['IGNORED_ITEMS'] ?? []);
		$entityInfoByType = [];
		foreach ($dups as $dup)
		{
			$entities = $dup->getEntities();
			if (!(is_array($entities) && !empty($entities)))
			{
				continue;
			}

			//Each entity type limited by 50 items
			foreach ($entities as $entity)
			{
				/** @var DuplicateEntity $entity */
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
			foreach ($entities as &$entity)
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

				if (isset($entityInfoByType[$entityTypeID]) && isset($entityInfoByType[$entityTypeID][$entityID]))
				{
					$entityInfo = $entityInfoByType[$entityTypeID][$entityID];
					if (isset($entityInfo['TITLE']))
					{
						$info['TITLE'] = $entityInfo['TITLE'];
					}
					$info['CATEGORY_NAME'] = $entityInfo['CATEGORY_NAME'] ?? CCrmOwnerType::GetDescription($entityTypeID);
					if ($entityTypeID === CCrmOwnerType::Company)
					{
						$info['IS_MY_COMPANY'] = $entityInfo['IS_MY_COMPANY'] ? 'Y' : 'N';
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

						$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
						$isReadable = CCrmAuthorizationHelper::CheckReadPermission(
							$entityTypeName,
							$entityID,
							$userPermissions
						);
						$isEditable = CCrmAuthorizationHelper::CheckUpdatePermission(
							$entityTypeName,
							$entityID,
							$userPermissions
						);

						if ($isEditable && isset($entityInfo['EDIT_URL']))
						{
							$info['URL'] = $entityInfo['EDIT_URL'];
						}
						elseif ($isReadable && isset($entityInfo['SHOW_URL']))
						{
							$info['URL'] = $entityInfo['SHOW_URL'];
						}
						else
						{
							$info['URL'] = '';
						}
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
				$dupInfo['ENTITIES'][] = &$info;
				unset($info);
			}
			unset($entity);

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

	__CrmCompanyEditEndResponse(['GROUP_RESULTS' => $groupResults]);
}
elseif ($action === 'FIND_LOCALITIES')
{
	$localityType = $_POST['LOCALITY_TYPE'] ?? 'COUNTRY';
	$needle = $_POST['NEEDLE'] ?? '';
	if ($localityType === 'COUNTRY')
	{
		$result = EntityAddress::getCountries(['CAPTION' => $needle]);
		__CrmCompanyEditEndResponse(['DATA' => ['ITEMS' => $result]]);
	}
	else
	{
		__CrmCompanyEditEndResponse(['ERROR' => "Locality '$localityType' is not supported in current context."]);
	}
}
else
{
	__CrmCompanyEditEndResponse(['ERROR' => "Action '$action' is not supported in current context."]);
}
