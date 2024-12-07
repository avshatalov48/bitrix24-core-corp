<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\EntityRequisite;
use Bitrix\Main;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ORM;

class RequisiteDedupeDataSource extends MatchHashDedupeDataSource
{
	public function __construct($typeID, DedupeParams $params)
	{
		if (($typeID & DuplicateIndexType::REQUISITE) !== $typeID)
		{
			throw new Main\NotSupportedException("Type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
		}

		parent::__construct($typeID, $params);
	}

	final protected function getOrmEntity(): ORM\Entity
	{
		return DuplicateRequisiteMatchCodeTable::getEntity();
	}

	final protected function applyQueryFilterByMatches(Query $query, DuplicateCriterion $criterion): Query
	{
		$matches = $criterion->getMatches();

		$countryId = isset($matches['RQ_COUNTRY_ID']) ? (int)$matches['RQ_COUNTRY_ID'] : 0;
		if ($countryId <= 0)
		{
			throw new Main\ArgumentException("Parameter 'RQ_COUNTRY_ID' is required.", 'matches');
		}

		$fieldName = $matches['RQ_FIELD_NAME'] ?? '';
		if ($fieldName === '')
		{
			throw new Main\ArgumentException("Parameter 'RQ_FIELD_NAME' is required.", 'matches');
		}

		$value = $matches['VALUE'] ?? '';
		if ($value === '')
		{
			throw new Main\ArgumentException("Parameter 'VALUE' is required.", 'matches');
		}

		$query->addFilter('=RQ_COUNTRY_ID', $countryId);
		$query->addFilter('=RQ_FIELD_NAME', $fieldName);
		$query->addFilter('=VALUE', $value);

		return $query;
	}

	protected function getEntityMatchesByHash($entityTypeID, $entityID, $matchHash): ?array
	{
		$countryId = EntityRequisite::getCountryIdByDuplicateCriterionScope($this->getScope());
		$allMatches = DuplicateRequisiteCriterion::loadEntityMatches(
			$entityTypeID,
			$entityID,
			$countryId,
			DuplicateIndexType::resolveName($this->getTypeID())
		);
		foreach ($allMatches as $matches)
		{
			if (DuplicateRequisiteCriterion::prepareMatchHash($matches) === $matchHash)
			{
				return $matches;
			}
		}

		return null;
	}

	protected function createCriterionFromMatches(array $matches): DuplicateCriterion
	{
		return DuplicateRequisiteCriterion::createFromMatches($matches);
	}

	protected function prepareResult(array &$map, DedupeDataSourceResult $result): void
	{
		$entityTypeID = $this->getEntityTypeID();

		foreach ($map as $matchHash => &$entry)
		{
			$isValidEntry = false;
			$primaryQty = isset($entry['PRIMARY']) ? count($entry['PRIMARY']) : 0;
			if ($primaryQty > 1)
			{
				$matches = $this->getEntityMatchesByHash(
					$entityTypeID,
					$entry['PRIMARY'][0],
					$matchHash
				);
				if (is_array($matches))
				{
					$criterion = $this->createCriterionFromMatches($matches);
					$dup = new Duplicate($criterion, array());
					foreach ($entry['PRIMARY'] as $entityID)
					{
						$dup->addEntity(new DuplicateEntity($entityTypeID, $entityID));
					}

					$result->addItem($matchHash, $dup);
					$isValidEntry = true;
				}
			}

			if (!$isValidEntry)
			{
				$result->addInvalidItem((string)$matchHash);
			}
		}
		unset($entry);
	}

	/**
	 * @deprecated
	 * @see DedupeDataSource::isEmptyEntity()
	 * 
	 * @noinspection All
	 */
	public function calculateEntityCount(DuplicateCriterion $criterion, array $options = null)
	{
		$entityTypeID = $this->getEntityTypeID();
		$enablePermissionCheck = $this->isPermissionCheckEnabled();
		$userID = $this->getUserID();

		$query = new Main\Entity\Query(DuplicateRequisiteMatchCodeTable::getEntity());
		$query->addSelect('QTY');
		$query->registerRuntimeField('', new Main\Entity\ExpressionField('QTY', 'COUNT(*)'));
		$query->addFilter('=ENTITY_TYPE_ID', $entityTypeID);

		if($enablePermissionCheck)
		{
			$permissionSql = $this->preparePermissionSql();
			if($permissionSql === false)
			{
				//Access denied;
				return 0;
			}
			if(is_string($permissionSql) && $permissionSql !== '')
			{
				$query->addFilter('@ENTITY_ID', new Main\DB\SqlExpression($permissionSql));
			}
		}

		$matches = $criterion->getMatches();

		$countryId = isset($matches['RQ_COUNTRY_ID']) ? (int)$matches['RQ_COUNTRY_ID'] : 0;
		if($countryId <= 0)
		{
			throw new Main\ArgumentException("Parameter 'RQ_COUNTRY_ID' is required.", 'matches');
		}

		$fieldName = isset($matches['RQ_FIELD_NAME']) ? $matches['RQ_FIELD_NAME'] : '';
		if($fieldName === '')
		{
			throw new Main\ArgumentException("Parameter 'RQ_FIELD_NAME' is required.", 'matches');
		}

		$value = isset($matches['VALUE']) ? $matches['VALUE'] : '';
		if($value === '')
		{
			throw new Main\ArgumentException("Parameter 'VALUE' is required.", 'matches');
		}

		$query->addFilter('=RQ_COUNTRY_ID', $countryId);
		$query->addFilter('=RQ_FIELD_NAME', $fieldName);
		$query->addFilter('=VALUE', $value);

		$rootEntityID = 0;
		if(is_array($options) && isset($options['ROOT_ENTITY_ID']))
		{
			$rootEntityID =  (int)$options['ROOT_ENTITY_ID'];
		}
		if($rootEntityID > 0)
		{
			$query->addFilter('!ENTITY_ID', $rootEntityID);
			$query->addFilter(
				'!@ENTITY_ID',
				DuplicateIndexMismatch::prepareQueryField($criterion, $entityTypeID, $rootEntityID, $userID)
			);
		}

		$query = DedupeDataSource::registerRuntimeFieldsByParams($query, $this->getParams());

		$limit = 0;
		if(is_array($options) && isset($options['LIMIT']))
		{
			$limit =  (int)$options['LIMIT'];
		}
		if($limit > 0)
		{
			$query->setLimit($limit);
		}

		$dbResult = $query->exec();
		$fields = $dbResult->fetch();
		return is_array($fields) && isset($fields['QTY']) ? intval($fields['QTY']) : 0;
	}
}
