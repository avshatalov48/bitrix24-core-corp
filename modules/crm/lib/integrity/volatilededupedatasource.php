<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class VolatileDedupeDataSource extends MatchHashDedupeDataSource
{
	public function __construct($typeID, DedupeParams $params)
	{
		if(!in_array($typeID, DuplicateVolatileCriterion::getAllSupportedDedupeTypes(), true))
		{
			throw new Main\NotSupportedException(
				"Type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context"
			);
		}

		parent::__construct($typeID, $params);
	}

	protected function getEntityMatchesByHash($entityTypeID, $entityID, $matchHash): ?array
	{
		$allMatches = DuplicateVolatileCriterion::loadEntityMatches($entityTypeID, $entityID, $this->getTypeID());
		foreach($allMatches as $matches)
		{
			if(DuplicateVolatileCriterion::prepareMatchHash($matches) === $matchHash)
			{
				return $matches;
			}
		}
		return null;
	}

	protected function createCriterionFromMatches(array $matches): DuplicateVolatileCriterion
	{
		return DuplicateVolatileCriterion::createFromMatches($matches);
	}

	protected function prepareResult(array &$map, DedupeDataSourceResult $result)
	{
		$entityTypeID = $this->getEntityTypeID();
		foreach($map as $matchHash => &$entry)
		{
			$isValidEntry = false;
			$primaryQty = isset($entry['PRIMARY']) ? count($entry['PRIMARY']) : 0;
			if($primaryQty > 1)
			{
				$matches = $this->getEntityMatchesByHash($entityTypeID, $entry['PRIMARY'][0], $matchHash);
				if(is_array($matches))
				{
					$criterion = $this->createCriterionFromMatches($matches);
					$dup = new Duplicate($criterion, array());
					foreach($entry['PRIMARY'] as $entityID)
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

	public function calculateEntityCount(DuplicateCriterion $criterion, array $options = null)
	{
		$entityTypeID = $this->getEntityTypeID();
		$enablePermissionCheck = $this->isPermissionCheckEnabled();
		$userID = $this->getUserID();

		$query = DuplicateVolatileMatchCodeTable::query();
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
		$typeId = $matches['TYPE_ID'] ?? DuplicateIndexType::UNDEFINED;
		if($typeId === DuplicateIndexType::UNDEFINED)
		{
			throw new Main\ArgumentException("Parameter 'TYPE_ID' is required.", 'matches');
		}

		$value = $matches['VALUE'] ?? '';
		if($value === '')
		{
			throw new Main\ArgumentException("Parameter 'VALUE' is required.", 'matches');
		}

		$query->addFilter('=TYPE_ID', $typeId);
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
