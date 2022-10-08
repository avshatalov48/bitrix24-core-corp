<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;

class PersonDedupeDataSource extends MatchHashDedupeDataSource
{
	public function __construct(DedupeParams $params)
	{
		parent::__construct(DuplicateIndexType::PERSON, $params);
	}
	/**
	 * @return Array
	 */
	protected function loadEntityMatches($entityTypeID, $entityID)
	{
		return DuplicatePersonCriterion::loadEntityMatches($entityTypeID, $entityID);
	}
	/**
	 * @return Array
	 */
	protected function loadEntitesMatches($entityTypeID, array $entityIDs)
	{
		return DuplicatePersonCriterion::loadEntitiesMatches($entityTypeID, $entityIDs);
	}
	/**
	 * @return array|null
	 */
	protected function getEntityMatchesByHash($entityTypeID, $entityID, $matchHash)
	{
		$matches = DuplicatePersonCriterion::loadEntityMatches($entityTypeID, $entityID);
		if(!is_array($matches))
		{
			return null;
		}

		if(DuplicatePersonCriterion::prepareMatchHash($matches) === $matchHash)
		{
			return $matches;
		}

		if(isset($matches['SECOND_NAME']) && $matches['SECOND_NAME'] !== '')
		{
			$matches['SECOND_NAME'] = '';
			if(DuplicatePersonCriterion::prepareMatchHash($matches) === $matchHash)
			{
				return $matches;
			}
		}

		if(isset($matches['NAME']) && $matches['NAME'] !== '')
		{
			$matches['NAME'] = '';
			if(DuplicatePersonCriterion::prepareMatchHash($matches) === $matchHash)
			{
				return $matches;
			}
		}

		return null;
	}
	/**
	 * @return DuplicateCriterion
	 */
	protected function createCriterionFromMatches(array $matches)
	{
		return DuplicatePersonCriterion::createFromMatches($matches);
	}
	protected function prepareResult(array &$map, DedupeDataSourceResult $result)
	{
		$entityTypeID = $this->getEntityTypeID();
		foreach($map as $matchHash => &$entry)
		{
			$isValidEntry = false;
			$primaryQty = isset($entry['PRIMARY']) ? count($entry['PRIMARY']) : 0;
			$secondaryQty = isset($entry['SECONDARY']) ? count($entry['SECONDARY']) : 0;

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

			if($primaryQty > 0 && $secondaryQty > 0)
			{
				foreach($entry['SECONDARY'] as $secondaryEntityID)
				{
					$secondaryEntityMatches = $this->getEntityMatchesByHash($entityTypeID, $secondaryEntityID, $matchHash);
					if(is_array($secondaryEntityMatches))
					{
						$criterion = $this->createCriterionFromMatches($secondaryEntityMatches);
						$secondaryEntityMatchHash = $criterion->getMatchHash();
						if($secondaryEntityMatchHash === '')
						{
							continue;
						}

						$dup = $result->getItem($secondaryEntityMatchHash);
						if(!$dup)
						{
							$dup = new Duplicate($criterion, array(new DuplicateEntity($entityTypeID, $secondaryEntityID)));
							$dup->setOption('enableOverwrite', false);
							$dup->setRootEntityID($secondaryEntityID);
						}
						else
						{
							$dup->addEntity(new DuplicateEntity($entityTypeID, $secondaryEntityID));
						}

						$result->addItem($secondaryEntityMatchHash, $dup);
						$isValidEntry = true;
						foreach($entry['PRIMARY'] as $primaryEntityID)
						{
							$matches = $this->getEntityMatchesByHash($entityTypeID, $primaryEntityID, $matchHash);
							if(is_array($matches))
							{
								$entity = new DuplicateEntity($entityTypeID, $primaryEntityID);
								$entity->setCriterion($this->createCriterionFromMatches($matches));
								$dup->addEntity($entity);
							}
						}
					}
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
		$count = $this->innerCalculateEntityCount($criterion, $options);

		$matches = $criterion->getMatches();
		$name = isset($matches['NAME']) ? $matches['NAME'] : '';
		$secondName = isset($matches['SECOND_NAME']) ? $matches['SECOND_NAME'] : '';
		$lastName = isset($matches['LAST_NAME']) ? $matches['LAST_NAME'] : '';

		if($secondName !== '' && $name !== '')
		{
			$count += $this->innerCalculateEntityCount(
				DuplicatePersonCriterion::createFromMatches(array('LAST_NAME' => $lastName, 'NAME' => $name)),
				$options
			);
		}
		if($name !== '')
		{
			$count += $this->innerCalculateEntityCount(
				DuplicatePersonCriterion::createFromMatches(array('LAST_NAME' => $lastName)),
				$options
			);
		}

		return $count;
	}
	protected function innerCalculateEntityCount(DuplicateCriterion $criterion, array $options = null)
	{
		$entityTypeID = $this->getEntityTypeID();
		$enablePermissionCheck = $this->isPermissionCheckEnabled();
		$userID = $this->getUserID();

		$query = new Main\Entity\Query(DuplicatePersonMatchCodeTable::getEntity());
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
		$lastName = isset($matches['LAST_NAME']) ? $matches['LAST_NAME'] : '';
		if($lastName === '')
		{
			throw new Main\ArgumentException("Parameter 'LAST_NAME' is required.", 'matches');
		}
		$query->addFilter('=LAST_NAME', $lastName);
		$query->addFilter('=NAME', isset($matches['NAME']) ? $matches['NAME'] : '');
		$query->addFilter('=SECOND_NAME', isset($matches['SECOND_NAME']) ? $matches['SECOND_NAME'] : '');

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
