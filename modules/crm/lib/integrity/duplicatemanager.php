<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm\CommunicationType;
use Bitrix\Crm\Integrity\DuplicateIndexType;

class DuplicateManager
{
	/**
	* @return DuplicateCriterion
	*/
	public static function createCriterion($typeID, array $matches)
	{
		if($typeID === DuplicateIndexType::PERSON)
		{
			return DuplicatePersonCriterion::createFromMatches($matches);
		}
		elseif($typeID === DuplicateIndexType::ORGANIZATION)
		{
			return DuplicateOrganizationCriterion::createFromMatches($matches);
		}
		elseif($typeID === DuplicateIndexType::COMMUNICATION_PHONE
			|| $typeID === DuplicateIndexType::COMMUNICATION_EMAIL
		)
		{
			if(!isset($matches['TYPE']))
			{
				$matches['TYPE'] = $typeID === DuplicateIndexType::COMMUNICATION_PHONE
					? CommunicationType::PHONE_NAME : CommunicationType::EMAIL_NAME;
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
			throw new Main\NotSupportedException("Criterion type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
		}
	}
	/**
	* @return Duplicate
	*/
	public static function createDuplicate($typeID, array $matches, $entityTypeID, $rootEntityID, $userID, $enablePermissionCheck, $enableRanking, $limit = 0)
	{
		return self::createCriterion($typeID, $matches)->createDuplicate($entityTypeID, $rootEntityID, $userID, $enablePermissionCheck, $enableRanking, $limit);
	}
	/**
	* @return DuplicateIndexBuilder
	*/
	public static function createIndexBuilder($typeID, $entityTypeID, $userID, $enablePermissionCheck = false, $options = null)
	{
		$scope = self::parseScopeOption($options);

		return new DuplicateIndexBuilder($typeID, new DedupeParams($entityTypeID, $userID, $enablePermissionCheck, $scope));
	}
	public static function removeIndexes(array $typeIDs, $entityTypeID, $userID, $enablePermissionCheck = false, $options = null)
	{
		$scope = self::parseScopeOption($options);
		$params = new DedupeParams($entityTypeID, $userID, $enablePermissionCheck, $scope);
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
			|| $typeID === DuplicateIndexType::COMMUNICATION_PHONE)
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

		if($entityTypeID !== \CCrmOwnerType::Lead
			&& $entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company)
		{
			return array();
		}

		$result = array();
		if($entityTypeID === \CCrmOwnerType::Lead || $entityTypeID === \CCrmOwnerType::Contact)
		{
			$result = array_merge($result, DuplicatePersonCriterion::getSupportedDedupeTypes());
		}
		if($entityTypeID === \CCrmOwnerType::Lead || $entityTypeID === \CCrmOwnerType::Company)
		{
			$result = array_merge($result, DuplicateOrganizationCriterion::getSupportedDedupeTypes());
		}
		$result = array_merge($result, DuplicateCommunicationCriterion::getSupportedDedupeTypes());
		if ($entityTypeID === \CCrmOwnerType::Contact || $entityTypeID === \CCrmOwnerType::Company)
		{
			$result = array_merge(
				$result,
				DuplicateRequisiteCriterion::getSupportedDedupeTypes(),
				DuplicateBankDetailCriterion::getSupportedDedupeTypes()
			);
		}
		return $result;
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
	public static function getDedupeTypeScopeMap($entityTypeID)
	{
		$result = array();

		$rqFieldScopeMap = $bdFieldScopeMap = null;
		foreach (self::getSupportedDedupeTypes($entityTypeID) as $typeID)
		{
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
					$fieldScopeMap = array();
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
				$scopes = isset($fieldScopeMap[$typeID]) ? array_keys($fieldScopeMap[$typeID]) : array();
				unset($fieldScopeMap);
			}
			else
			{
				$scopes = array('');
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