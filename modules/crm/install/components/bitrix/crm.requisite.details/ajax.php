<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Crm\EntityAdapterFactory;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integrity\Duplicate;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
use Bitrix\Crm\Integrity\DuplicateCriterion;
use Bitrix\Crm\Integrity\DuplicateEntity;
use Bitrix\Crm\Integrity\DuplicateSearchParams;
use Bitrix\Crm\Service\Container;

global $DB, $APPLICATION;
if(!function_exists('__CrmRequisiteEditEndResponse'))
{
	function __CrmRequisiteEditEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
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
	__CrmRequisiteEditEndResponse(array('ERROR' => 'Could not include crm module.'));
}
/*
 * 'FIND_LOCALITIES'
 */

if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
{
	__CrmRequisiteEditEndResponse(array('ERROR' => 'Access denied.'));
}
if ($_SERVER['REQUEST_METHOD'] != 'POST')
{
	__CrmRequisiteEditEndResponse(array('ERROR' => 'Request method is not allowed.'));
}

$GLOBALS['APPLICATION']->RestartBuffer();
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = $_POST['ACTION'] ?? '';
if ($action === 'FIND_DUPLICATES')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = (isset($_POST['PARAMS']) && is_array($_POST['PARAMS'])) ? $_POST['PARAMS'] : [];
	$entityTypeName = $params['ENTITY_TYPE_NAME'] ?? '';
	$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
	if ($entityTypeName === '')
	{
		__CrmRequisiteEditEndResponse(['ERROR' => 'Entity type is not specified.']);
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if ($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmRequisiteEditEndResponse(['ERROR' => 'Undefined entity type is specified.']);
	}

	if ($entityTypeID !== CCrmOwnerType::Company && $entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmRequisiteEditEndResponse(['ERROR' => "The '{$entityTypeName}' type is not supported in current context."]);
	}

	if (!EntityRequisite::checkUpdatePermissionOwnerEntity($entityTypeID, $entityID))
	{
		__CrmRequisiteEditEndResponse(['ERROR' => 'Access denied.']);
	}

	$userProfileUrlTemplate = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", "", SITE_ID);

	$checker = new \Bitrix\Crm\Integrity\CompanyDuplicateChecker();
	$checker->setStrictComparison(false);

	$groupResults = [];
	$groupData = isset($params['GROUPS']) && is_array($params['GROUPS']) ? $params['GROUPS'] : [];
	$allRequisiteFieldsMap = Bitrix\Crm\EntityRequisite::getDuplicateCriterionFieldsMap();
	$allBankDetailsFieldsMap = Bitrix\Crm\EntityBankDetail::getDuplicateCriterionFieldsMap();
	foreach ($groupData as $group)
	{
		$presetId = (int)$group['PRESET_ID'];
		$countryId = (new EntityRequisite())->getCountryIdByPresetId($presetId);
		if (!$countryId)
		{
			continue;
		}
		$requisiteFieldsMap = $allRequisiteFieldsMap[$countryId];
		$bankDetailsFieldsMap = $allBankDetailsFieldsMap[$countryId];
		if (!$requisiteFieldsMap && !$bankDetailsFieldsMap)
		{
			continue;
		}

		$fieldName = (string)$group['FIELD_ID'];

		$requisitePseudoId = (int)$group['ID'];
		$requisitePseudoId = $requisitePseudoId > 0 ? $requisitePseudoId : 'n0';

		$bankDetailPseudoId = (int)$group['BANK_DETAILS_ID'];
		$bankDetailPseudoId = $bankDetailPseudoId > 0 ? $bankDetailPseudoId : 'n0';

		$isRequisiteField = $isBankDetailsField = false;

		if (in_array($fieldName, $requisiteFieldsMap, true))
		{
			$isRequisiteField = true;
		}
		if (in_array($fieldName, $bankDetailsFieldsMap, true))
		{
			$isBankDetailsField = true;
		}
		if (!$isRequisiteField && !$isBankDetailsField)
		{
			continue;
		}

		$requisite = [
			'ID' => $requisitePseudoId,
			'PRESET_ID' => $presetId,
			'PRESET_COUNTRY_ID' => $countryId,
		];

		if ($isRequisiteField)
		{
			$requisite[$fieldName] = (string)$group[$fieldName];
		}

		if ($isBankDetailsField)
		{
			$requisite['BD'][$bankDetailPseudoId] = [
				'ID' => $bankDetailPseudoId,
				'ENTITY_TYPE_ID' => CCrmOwnerType::Requisite,
				'ENTITY_ID' => $requisitePseudoId,
				'COUNTRY_ID' => $countryId,
				$fieldName => (string)$group[$fieldName],
			];
		}

		$fields = [
			'RQ' => [
				$requisitePseudoId => $requisite,
			],
		];

		if (!empty($requisiteList))
		{
			$fields['RQ'] = $requisiteList;
		}
		// endregion Requisites

		$adapter = EntityAdapterFactory::create($fields, $entityTypeID);
		$dups = $checker->findDuplicates($adapter, new DuplicateSearchParams());

		$entityInfoByType = [];
		foreach ($dups as $dup)
		{
			/** @var Duplicate $dup */
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
			'ENTITY_TOTAL_TEXT' => Duplicate::entityCountToText($totalEntities),
		];
		unset($dupInfos);
	}

	__CrmRequisiteEditEndResponse(['GROUP_RESULTS' => $groupResults]);
}
else
{
	__CrmRequisiteEditEndResponse(['ERROR' => "Action '$action' is not supported in current context."]);
}