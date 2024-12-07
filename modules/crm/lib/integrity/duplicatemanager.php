<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\Communication\Type;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\Integrity\Volatile;
use Bitrix\Crm\Item;
use Bitrix\Main;
use CCrmOwnerType;

class DuplicateManager
{
	public static function getCriterionRegistrar(int $entityTypeId): CriterionRegistrar
	{
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return new CriterionRegistrar\Decorator\OrganizationCriterion(
				new CriterionRegistrar\Decorator\PersonCriterion(
					new CriterionRegistrar\Decorator\CommunicationCriterion(
						new CriterionRegistrar\Decorator\VolatileCriterion(
							new CriterionRegistrar\EntityRanking(),
						),
					),
				),
				Item\Lead::FIELD_NAME_COMPANY_TITLE,
			);
		}

		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			return new CriterionRegistrar\Decorator\OrganizationCriterion(
				new CriterionRegistrar\Decorator\CommunicationCriterion(
					new CriterionRegistrar\Decorator\VolatileCriterion(
						new CriterionRegistrar\EntityRanking(),
					),
				),
				Item::FIELD_NAME_TITLE,
			);
		}

		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			return new CriterionRegistrar\Decorator\PersonCriterion(
				new CriterionRegistrar\Decorator\CommunicationCriterion(
					new CriterionRegistrar\Decorator\VolatileCriterion(
						new CriterionRegistrar\EntityRanking(),
					),
				),
			);
		}

		return new CriterionRegistrar\NullRegistrar();
	}

	public static function getCriterionRegistrarForReindex(int $entityTypeId): CriterionRegistrar
	{
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return new CriterionRegistrar\Decorator\OrganizationCriterion(
				new CriterionRegistrar\Decorator\PersonCriterion(
					new CriterionRegistrar\Decorator\CommunicationCriterion(
						new CriterionRegistrar\Decorator\VolatileCriterionReindex(
							new CriterionRegistrar\EntityRanking(),
						),
					),
				),
				Item\Lead::FIELD_NAME_COMPANY_TITLE,
			);
		}

		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			return new CriterionRegistrar\Decorator\OrganizationCriterion(
				new CriterionRegistrar\Decorator\CommunicationCriterion(
					new CriterionRegistrar\Decorator\VolatileCriterionReindex(
						new CriterionRegistrar\EntityRanking(),
					),
				),
				Item::FIELD_NAME_TITLE,
			);
		}

		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			return new CriterionRegistrar\Decorator\PersonCriterion(
				new CriterionRegistrar\Decorator\CommunicationCriterion(
					new CriterionRegistrar\Decorator\VolatileCriterionReindex(
						new CriterionRegistrar\EntityRanking(),
					),
				),
			);
		}

		return new CriterionRegistrar\NullRegistrar();
	}

	/**
	* @return DuplicateCriterion
	*/
	public static function createCriterion($typeID, array $matches)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if ($typeID === DuplicateIndexType::PERSON)
		{
			return DuplicatePersonCriterion::createFromMatches($matches);
		}
		elseif ($typeID === DuplicateIndexType::ORGANIZATION)
		{
			return DuplicateOrganizationCriterion::createFromMatches($matches);
		}
		elseif (
			$typeID === DuplicateIndexType::COMMUNICATION_PHONE
			|| $typeID === DuplicateIndexType::COMMUNICATION_EMAIL
			|| $typeID === DuplicateIndexType::COMMUNICATION_SLUSER
		)
		{
			if (!isset($matches['TYPE']))
			{
				$matches['TYPE'] = Type::PHONE_NAME;

				if ($typeID === DuplicateIndexType::COMMUNICATION_PHONE)
				{
					$matches['TYPE'] = Type::PHONE_NAME;
				}
				elseif ($typeID === DuplicateIndexType::COMMUNICATION_EMAIL)
				{
					$matches['TYPE'] = Type::EMAIL_NAME;
				}
				elseif ($typeID === DuplicateIndexType::COMMUNICATION_SLUSER)
				{
					$matches['TYPE'] = Type::SLUSER_NAME;
				}
			}

			return DuplicateCommunicationCriterion::createFromMatches($matches);
		}
		elseif(($typeID & DuplicateIndexType::REQUISITE) === $typeID)
		{
			return DuplicateRequisiteCriterion::createFromMatches($matches);
		}
		elseif(($typeID & DuplicateIndexType::BANK_DETAIL) === $typeID)
		{
			return DuplicateBankDetailCriterion::createFromMatches($matches);
		}
		else
		{
			foreach (DuplicateVolatileCriterion::getAllSupportedDedupeTypes() as $volatileTypeId)
			{
				if (($typeID & $volatileTypeId) === $typeID)
				{
					return DuplicateVolatileCriterion::createFromMatches($matches);
				}
			}
		}

		throw new Main\NotSupportedException(
			"Criterion type(s): '"
			. DuplicateIndexType::resolveName($typeID)
			. "' is not supported in current context"
		);
	}
	/**
	* @return Duplicate
	*/
	public static function createDuplicate(
		$typeID, array $matches, $entityTypeID, $rootEntityID, $userID, $enablePermissionCheck, $enableRanking, $limit = 0
	)
	{
		return self::createCriterion($typeID, $matches)
			->createDuplicate(
				$entityTypeID,
				$rootEntityID,
				$userID,
				$enablePermissionCheck,
				$enableRanking,
				$limit
			)
		;
	}
	/**
	* @return DuplicateIndexBuilder
	*/
	public static function createIndexBuilder(
		$typeID,
		$entityTypeID,
		$userID,
		$enablePermissionCheck = false,
		$options = null
	)
	{
		$scope = self::parseScopeOption($options);
		$contextId = self::parseContextIdOption($options);

		return new DuplicateIndexBuilder(
			$typeID,
			new DedupeParams($entityTypeID, $userID, $enablePermissionCheck, $scope, $contextId)
		);
	}
	/**
	 * @return DuplicateIndexBuilder
	 */
	public static function createAutomaticIndexBuilder(
		$typeID,
		$entityTypeID,
		$userID,
		$enablePermissionCheck = false,
		$options = null
	)
	{
		$scope = self::parseScopeOption($options);
		$contextId = self::parseContextIdOption($options);
		$params = new DedupeParams($entityTypeID, $userID, $enablePermissionCheck, $scope, $contextId);

		if (isset($options['LAST_INDEX_DATE']) && $options['LAST_INDEX_DATE'] instanceof Main\Type\DateTime)
		{
			$params->setIndexDate($options['LAST_INDEX_DATE']);
		}
		if (isset($options['CHECK_CHANGED_ONLY']) && $options['CHECK_CHANGED_ONLY'])
		{
			$params->setCheckChangedOnly(true);
		}
		return new AutomaticDuplicateIndexBuilder($typeID, $params);
	}
	public static function setDuplicateIndexItemStatus(
		$userID,
		$entityTypeID,
		$typeID,
		$matchHash,
		$scope,
		$statusID,
		$isAutomatic
	)
	{
		if ($isAutomatic)
		{
			AutomaticDuplicateIndexBuilder::setStatusID(
				$userID,
			$entityTypeID,
				$typeID,
				$matchHash,
			$scope,
				$statusID
		);
		}
		else
		{
			DuplicateIndexBuilder::setStatusID(
				$userID,
				$entityTypeID,
				$typeID,
				$matchHash,
				$scope,
				$statusID
			);
		}
	}
	public static function deleteDuplicateIndexItems($filter, $isAutomatic)
	{
		if ($isAutomatic)
		{
			Entity\AutomaticDuplicateIndexTable::deleteByFilter($filter);
		}
		else
		{
			Entity\DuplicateIndexTable::deleteByFilter($filter);
		}
	}

	public static function getExistedTypeScopeMap(int $entityTypeId, int $userId, bool $isAutomatic)
	{
		if ($isAutomatic)
		{
			return AutomaticDuplicateIndexBuilder::getExistedTypeScopeMap($entityTypeId, $userId);
		}
		else
		{
			return DuplicateIndexBuilder::getExistedTypeScopeMap($entityTypeId, $userId);
		}
	}

	public static function markDuplicateIndexAsJunk($entityTypeID, $entityID)
	{
		DuplicateIndexBuilder::markAsJunk($entityTypeID, $entityID);
		AutomaticDuplicateIndexBuilder::markAsDirty($entityTypeID, $entityID);
	}
	public static function markDuplicateIndexAsDirty($entityTypeID, $entityID)
	{
		AutomaticDuplicateIndexBuilder::markAsDirty($entityTypeID, $entityID);
	}

	public static function onChangeEntityAssignedBy($entityTypeID, $entityID)
	{
		DuplicateEntityMatchHash::setDateModify($entityTypeID, $entityID);
	}
	public static function removeIndexes(array $typeIDs, $entityTypeID, $userID, $enablePermissionCheck = false, $options = null)
	{
		$scope = self::parseScopeOption($options);
		$contextId = self::parseContextIdOption($options);
		$params = new DedupeParams($entityTypeID, $userID, $enablePermissionCheck, $scope, $contextId);
		foreach($typeIDs as $typeID)
		{
			$builder = new DuplicateIndexBuilder($typeID, $params);
			$builder->remove();
		}
	}
	public static function getMatchHash($typeID, array $matches)
	{
		if($typeID === DuplicateIndexType::PERSON)
		{
			return DuplicatePersonCriterion::prepareMatchHash($matches);
		}
		elseif($typeID === DuplicateIndexType::ORGANIZATION)
		{
			return DuplicateOrganizationCriterion::prepareMatchHash($matches);
		}
		elseif($typeID === DuplicateIndexType::COMMUNICATION_EMAIL
			|| $typeID === DuplicateIndexType::COMMUNICATION_PHONE
			|| $typeID === DuplicateIndexType::COMMUNICATION_SLUSER)
		{
			return DuplicateCommunicationCriterion::prepareMatchHash($matches);
		}
		elseif(($typeID & DuplicateIndexType::REQUISITE) === $typeID)
		{
			return DuplicateRequisiteCriterion::createFromMatches($matches);
		}
		elseif(($typeID & DuplicateIndexType::BANK_DETAIL) === $typeID)
		{
			return DuplicateBankDetailCriterion::createFromMatches($matches);
		}

		throw new Main\NotSupportedException("Criterion type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
	}
	/**
	 * Get types supported by deduplication system for specified entity type.
	 * @param int $entityTypeID Entity Type ID.
	 * @return array
	 */
	public static function getSupportedDedupeTypes($entityTypeID)
	{
		$entityTypeID = (int)$entityTypeID;

		if($entityTypeID !== CCrmOwnerType::Lead
			&& $entityTypeID !== CCrmOwnerType::Contact
			&& $entityTypeID !== CCrmOwnerType::Company)
		{
			return array();
		}

		$result = array();
		if($entityTypeID === CCrmOwnerType::Lead || $entityTypeID === CCrmOwnerType::Contact)
		{
			$result = array_merge($result, DuplicatePersonCriterion::getSupportedDedupeTypes());
		}
		if($entityTypeID === CCrmOwnerType::Lead || $entityTypeID === CCrmOwnerType::Company)
		{
			$result = array_merge($result, DuplicateOrganizationCriterion::getSupportedDedupeTypes());
		}
		$result = array_merge($result, DuplicateCommunicationCriterion::getSupportedDedupeTypes());
		if ($entityTypeID === CCrmOwnerType::Contact || $entityTypeID === CCrmOwnerType::Company)
		{
			$result = array_merge(
				$result,
				DuplicateRequisiteCriterion::getSupportedDedupeTypes(),
				DuplicateBankDetailCriterion::getSupportedDedupeTypes()
			);
		}

		// Volatile types
		$volatileTypesByEntityId = [];
		$idsByEntityTypes = Volatile\TypeInfo::getInstance()->getIdsByEntityTypes([$entityTypeID]);
		if (is_array($idsByEntityTypes[$entityTypeID]))
		{
			foreach ($idsByEntityTypes[$entityTypeID] as $volatileTypeId)
			{
				$data = Volatile\Type\State::getInstance()->get($volatileTypeId)->getData();
				if (
					in_array(
						$data['stateId'],
						[
							Volatile\Type\State::STATE_ASSIGNED,
							Volatile\Type\State::STATE_INDEX,
							Volatile\Type\State::STATE_READY,
						],
						true
					)
				)
				{
					$volatileTypesByEntityId[] = $volatileTypeId;
				}
			}
		}

		return array_merge($result, $volatileTypesByEntityId);
	}
	public static function parseScopeOption($options)
	{
		$scope = DuplicateIndexType::DEFAULT_SCOPE;
		if (is_array($options))
		{
			if (isset($options['SCOPE']))
			{
				if(!DuplicateIndexType::checkScopeValue($options['SCOPE']))
				{
					throw new Main\ArgumentException("Option has invalid value", 'SCOPE');
				}

				$scope = $options['SCOPE'];
			}
		}

		return $scope;
	}
	public static function parseContextIdOption($options)
	{

		$contextId = '';
		if (is_array($options) && isset($options['CONTEXT_ID']) && is_string($options['CONTEXT_ID']))
		{
			$contextId = $options['CONTEXT_ID'];
		}

		return $contextId;
	}
	public static function getDedupeTypeScopeMap($entityTypeID)
	{
		$result = [];

		$rqFieldScopeMap = $bdFieldScopeMap = null;
		foreach (self::getSupportedDedupeTypes($entityTypeID) as $typeID)
		{
			$scopes = [''];

			$isRequisite = (($typeID & DuplicateIndexType::REQUISITE) === $typeID);
			$isBankDetail = (($typeID & DuplicateIndexType::BANK_DETAIL) === $typeID);
			if($isRequisite || $isBankDetail)
			{
				if ($isRequisite)
					$fieldScopeMap = &$rqFieldScopeMap;
				else
					$fieldScopeMap = &$bdFieldScopeMap;
				if ($fieldScopeMap === null)
				{
					$fieldScopeMap = [];
					if ($isRequisite)
						$fieldsMap = DuplicateRequisiteCriterion::getIndexedFieldsMap($entityTypeID, true);
					else
						$fieldsMap = DuplicateBankDetailCriterion::getIndexedFieldsMap($entityTypeID, true);
					foreach ($fieldsMap as $scope => $fields)
					{
						foreach ($fields as $fieldName)
						{
							$fieldScopeMap[DuplicateIndexType::resolveID($fieldName)][$scope] = true;
						}
					}
				}
				$scopes = isset($fieldScopeMap[$typeID]) ? array_keys($fieldScopeMap[$typeID]) : [];
				unset($fieldScopeMap);
			}
			else
			{
				$isVolatile = false;
				$volatileTypeId = DuplicateIndexType::UNDEFINED;
				foreach (DuplicateVolatileCriterion::getSupportedDedupeTypes() as $currentTypeId)
				{
					if ((($typeID & $currentTypeId) === $currentTypeId))
					{
						$volatileTypeId = $currentTypeId;
						$isVolatile = true;
						break;
					}
				}
				if ($isVolatile)
				{
					$typeInfo = Volatile\TypeInfo::getInstance();
					$info = $typeInfo->getById($volatileTypeId);
					if (
						!empty($info)
						&& isset($info['CATEGORY_INFO']['categoryId'])
						&& isset($info['CATEGORY_INFO']['params']['countryId'])
					)
					{
						$categoryId = $info['CATEGORY_INFO']['categoryId'];
						$countryId = $info['CATEGORY_INFO']['params']['countryId'];
						switch ($categoryId)
						{
							case Volatile\FieldCategory::REQUISITE:
								$scopes = [EntityRequisite::formatDuplicateCriterionScope($countryId)];
								break;
							case Volatile\FieldCategory::BANK_DETAIL:
								$scopes = [EntityBankDetail::formatDuplicateCriterionScope($countryId)];
								break;
						}
					}
				}
			}
			if (!empty($scopes))
			{
				$result[$typeID] = $scopes;
			}
		}

		return $result;
	}
	public static function prepareEntityListQueries($entityTypeID, array $comparisonData)
	{
		$queries = array();
		foreach($comparisonData as $data)
		{
			$type = $data['TYPE'];
			$matches = $data['MATCHES'];
			$item = self::createCriterion($type, $matches);
			$item->setStrictComparison(isset($data['ENABLE_STRICT_MODE']) && $data['ENABLE_STRICT_MODE'] == true);
			$query = $item->prepareSearchQuery($entityTypeID, array('ENTITY_ID'))->getQuery();
			$queries[] = "({$query})";
		}

		return $queries;
	}
	public static function prepareEntityListFilter(array &$filter, array $comparisonData, $entityTypeID, $entityAlias = '')
	{
		if($entityAlias === '')
		{
			$entityAlias = 'L';
		}

		$queries = array();
		foreach($comparisonData as $data)
		{
			$type = $data['TYPE'];
			$matches = $data['MATCHES'];
			$item = self::createCriterion($type, $matches);
			$item->setStrictComparison(isset($data['ENABLE_STRICT_MODE']) && $data['ENABLE_STRICT_MODE'] == true);
			$query = $item->prepareSearchQuery($entityTypeID, array('ENTITY_ID'))->getQuery();
			$queries[] = "({$query})";
		}

		if(!isset($filter['__JOINS']))
		{
			$filter['__JOINS'] = array();
		}

		$filter['__JOINS'][] = array(
			'TYPE' => 'INNER',
			'SQL' => 'INNER JOIN('.implode("\nUNION\n", $queries).') DP ON DP.ENTITY_ID = '.$entityAlias.'.ID'
		);
	}
}
