<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Crm\EntityRequisite;

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

CUtil::JSPostUnescape();
$GLOBALS['APPLICATION']->RestartBuffer();
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

$action = isset($_POST['ACTION']) ? $_POST['ACTION'] : '';
if ($action === 'FIND_DUPLICATES')
{
	$userPermissions = CCrmPerms::GetCurrentUserPermissions();
	$params = isset($_POST['PARAMS']) && is_array($_POST['PARAMS']) ? $_POST['PARAMS'] : array();
	$entityTypeName = isset($params['ENTITY_TYPE_NAME']) ? $params['ENTITY_TYPE_NAME'] : '';
	$entityID = isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0;
	if($entityTypeName === '')
	{
		__CrmRequisiteEditEndResponse(array('ERROR' => 'Entity type is not specified.'));
	}

	$entityTypeID = CCrmOwnerType::ResolveID($entityTypeName);
	if($entityTypeID === CCrmOwnerType::Undefined)
	{
		__CrmRequisiteEditEndResponse(array('ERROR' => 'Undefined entity type is specified.'));
	}

	if($entityTypeID !== CCrmOwnerType::Company && $entityTypeID !== CCrmOwnerType::Contact)
	{
		__CrmRequisiteEditEndResponse(array('ERROR' => "The '{$entityTypeName}' type is not supported in current context."));
	}

	if(!EntityRequisite::checkUpdatePermissionOwnerEntity($entityTypeID, $entityID))
	{
		__CrmRequisiteEditEndResponse(array('ERROR' => 'Access denied.'));
	}

	$userProfileUrlTemplate = COption::GetOptionString("main", "TOOLTIP_PATH_TO_USER", "", SITE_ID);

	$checker = new \Bitrix\Crm\Integrity\CompanyDuplicateChecker();
	$checker->setStrictComparison(false);

	$groupResults = array();
	$groupData = isset($params['GROUPS']) && is_array($params['GROUPS']) ? $params['GROUPS'] : array();
	$allRequisiteFieldsMap = Bitrix\Crm\EntityRequisite::getDuplicateCriterionFieldsMap();
	$allBankDetailsFieldsMap =  Bitrix\Crm\EntityBankDetail::getDuplicateCriterionFieldsMap();
	foreach($groupData as &$group)
	{
		$presetId = (int)$group['PRESET_ID'];
		$countryId = (new EntityRequisite())->getCountryIdByPresetId((int)$presetId);
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
		if(!$isRequisiteField && !$isBankDetailsField)
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
				$fieldName => (string)$group[$fieldName]
			];
		}

		$fields = [
			'RQ' => [
				$requisitePseudoId => $requisite
			]
		];

		if (!empty($requisiteList))
		{
			$fields['RQ'] = $requisiteList;
		}
		// endregion Requisites

		$adapter = \Bitrix\Crm\EntityAdapterFactory::create($fields, $entityTypeID);
		$dups = $checker->findDuplicates($adapter, new \Bitrix\Crm\Integrity\DuplicateSearchParams());

		$entityInfoByType = array();
		foreach($dups as &$dup)
		{
			/** @var \Bitrix\Crm\Integrity\Duplicate $dup */
			$entities = $dup->getEntities();
			if(!(is_array($entities) && !empty($entities)))
			{
				continue;
			}

			//Each entity type limited by 50 items
			foreach($entities as &$entity)
			{
				/** @var \Bitrix\Crm\Integrity\DuplicateEntity $entity */
				$entityTypeID = $entity->getEntityTypeID();
				$entityID = $entity->getEntityID();

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
		unset($dup);

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
			'ENTITY_TOTAL_TEXT' => \Bitrix\Crm\Integrity\Duplicate::entityCountToText($totalEntities)
		);
		unset($dupInfos);
	}
	unset($group);

	__CrmRequisiteEditEndResponse(array('GROUP_RESULTS' => $groupResults));
}
else
{
	__CrmRequisiteEditEndResponse(array('ERROR' => "Action '{$action}' is not supported in current context."));
}