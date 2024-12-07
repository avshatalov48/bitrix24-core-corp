<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Main;
use Bitrix\Main\NotImplementedException;

class DuplicateIndexBuilder
{
	protected $typeID = DuplicateIndexType::UNDEFINED;
	protected $params = null;
	protected $dataSource = null;

	protected static $typeScopeMap = null;

	public function __construct($typeID, DedupeParams $params)
	{
		$this->typeID = $typeID;
		$this->params = $params;
	}
	public function getTypeID()
	{
		return $this->typeID;
	}
	/**
	 * @return DedupeParams
	 */
	public function getParams()
	{
		return $this->params;
	}
	public function getEntityTypeID()
	{
		return $this->params->getEntityTypeID();
	}
	public function getUserID()
	{
		return $this->params->getUserID();
	}
	public function isPermissionCheckEnabled()
	{
		return $this->params->isPermissionCheckEnabled();
	}
	public function getScope()
	{
		return $this->params->getScope();
	}
	public function getDataSource()
	{
		if($this->dataSource === null)
		{
			$this->dataSource = DedupeDataSource::create($this->typeID, $this->params);
		}
		return $this->dataSource;
	}
	public function dropDataSourceCache()
	{
		$this->getDataSource()->dropDedupeCache();
	}
	public function isExists()
	{
		$params = array(
			'TYPE_ID' => $this->typeID,
			'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
			'USER_ID' => $this->getUserID()
		);

		if($this->typeID === DuplicateIndexType::PERSON)
		{
			return DuplicatePersonCriterion::checkIndex($params);
		}
		elseif($this->typeID === DuplicateIndexType::ORGANIZATION)
		{
			return DuplicateOrganizationCriterion::checkIndex($params);
		}
		elseif($this->typeID === DuplicateIndexType::COMMUNICATION_PHONE
			|| $this->typeID === DuplicateIndexType::COMMUNICATION_EMAIL)
		{
			return DuplicateCommunicationCriterion::checkIndex($params);
		}
		elseif(($this->typeID & DuplicateIndexType::REQUISITE) === $this->typeID)
		{
			return DuplicateRequisiteCriterion::checkIndex($params);
		}
		elseif(($this->typeID & DuplicateIndexType::BANK_DETAIL) === $this->typeID)
		{
			return DuplicateBankDetailCriterion::checkIndex($params);
		}

		foreach (DuplicateVolatileCriterion::getSupportedDedupeTypes() as $currentTypeId)
		{
			if ((($this->typeID & $currentTypeId) === $currentTypeId))
			{
				return DuplicateVolatileCriterion::checkIndex($params);
			}
		}

		throw new Main\NotSupportedException(
			"Criterion type(s): '"
			. DuplicateIndexType::resolveName($this->typeID)
			. "' is not supported in current context"
		);
	}
	public function remove()
	{
		$this->deleteDuplicateIndexByFilter(
			array(
				'TYPE_ID' => $this->typeID,
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'USER_ID' => $this->getUserID(),
				'SCOPE' => $this->getScope()
			)
		);
	}
	public function build(array &$progressData)
	{
		return $this->internalBuild($progressData);
	}
	public function getTotalCount()
	{
		return $this->getDataSource()->getTotalCount();
	}
	public function processMismatchRegistration(DuplicateCriterion $criterion, $entityID = 0)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			$entityID = $this->getRootEntityID($criterion->getMatchHash());
		}

		if($entityID <= 0)
		{
			return;
		}

		if (
			$criterion->isEmpty(
				$this->getEntityTypeID(),
				$entityID,
				$this->getUserID(),
				$this->isPermissionCheckEnabled()
			)
		)
		{
			$this->deleteDuplicateIndexByFilter(
				array(
					'USER_ID' => $this->getUserID(),
					'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
					'TYPE_ID' => $this->typeID,
					'MATCH_HASH' => $criterion->getMatchHash()
				)
			);
		}
	}
	public function processEntityDeletion(DuplicateCriterion $criterion, $entityID, array $params = array())
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			return;
		}

		$matchHash = $criterion->getMatchHash();

		$userID = $this->getUserID();
		$entityTypeID = $this->getEntityTypeID();
		$rootEntityID = $this->getRootEntityID($matchHash);
		$enablePermissionCheck = $this->isPermissionCheckEnabled();

		//Actual root entity is required for manual merging. User can select any entity as root entity.
		$actualRootEntityID = isset($params['ROOT_ENTITY_ID']) ? (int)$params['ROOT_ENTITY_ID'] : 0;
		if($actualRootEntityID <= 0)
		{
			$actualRootEntityID = $rootEntityID;
		}

		if (
			$criterion->isEmpty(
				$entityTypeID,
				$actualRootEntityID,
				$userID,
				$enablePermissionCheck
			)
		)
		{
			$this->deleteDuplicateIndexByFilter(
				array(
					'USER_ID' => $this->getUserID(),
					'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
					'TYPE_ID' => $this->typeID,
					'MATCH_HASH' => $matchHash
				)
			);
			return;
		}

		if($entityID !== $rootEntityID)
		{
			return;
		}

		// if $entityID == ROOT_ENTITY_ID,
		// reassign ROOT_ENTITY_ID or remove record from index because $entityID was deleted:
		$this->buildDuplicateByMatchHash($matchHash);
	}

	public function buildDuplicateByMatchHash($matchHash): ?Duplicate
	{
		$item = $this->getDataSource()->getDuplicateByMatchHash($matchHash);

		if(!$item)
		{
			$this->deleteDuplicateIndexByFilter(
				array(
					'USER_ID' => $this->getUserID(),
					'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
					'TYPE_ID' => $this->typeID,
					'MATCH_HASH' => $matchHash
				)
			);
			return null;
		}

		$rankings = $item->getAllRankings();
		DuplicateEntityRanking::initializeBulk($rankings,
			array('CHECK_PERMISSIONS' => $this->isPermissionCheckEnabled(), 'USER_ID' => $this->getUserID())
		);
		$rootEntityInfo = array();
		if(!$this->tryResolveRootEntity($item, $matchHash, $rootEntityInfo))
		{
			$this->deleteDuplicateIndexByFilter(
				array(
					'USER_ID' => $this->getUserID(),
					'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
					'TYPE_ID' => $this->typeID,
					'MATCH_HASH' => $matchHash
				)
			);
			return null;
		}

		$rootEntityID = $rootEntityInfo['ENTITY_ID'];
		$item->setRootEntityID($rootEntityID);
		$sortParams = $this->prepareSortParams(array($rootEntityID));
		$data = $this->prepareTableData($matchHash, $item, $sortParams, true);
		$this->saveDuplicateIndexItem($data);

		return $item;
	}

	public static function getExistedTypes($entityTypeID, $userID, $scope = null)
	{
		$filter = array(
			'=USER_ID' => $userID,
			'=ENTITY_TYPE_ID' => $entityTypeID
		);
		if ($scope !== null)
			$filter['=SCOPE'] = $scope;
		$dbResult = Entity\DuplicateIndexTable::getList(
			array(
				'select' => array('TYPE_ID'),
				'order' => array('TYPE_ID' => 'ASC'),
				'group' => array('TYPE_ID'),
				'filter' => $filter
			)
		);

		$result = array();
		while($fields = $dbResult->fetch())
		{
			$result[] = intval($fields['TYPE_ID']);
		}
		return $result;
	}
	public static function getExistedTypeScopeMap($entityTypeID, $userID)
	{
		$dbResult = Entity\DuplicateIndexTable::getList(
			array(
				'select' => array('TYPE_ID', 'SCOPE'),
				'order' => array('TYPE_ID' => 'ASC', 'SCOPE' => 'ASC'),
				'group' => array('TYPE_ID', 'SCOPE'),
				'filter' => array(
					'=USER_ID' => $userID,
					'=ENTITY_TYPE_ID' => $entityTypeID
				)
			)
		);

		$result = array();
		while($fields = $dbResult->fetch())
		{
			$typeID = (int)$fields['TYPE_ID'];
			if (!isset($result[$typeID]))
				$result[$typeID] = array();
			if (!isset($result[$typeID][$fields['SCOPE']]))
				$result[$typeID][$fields['SCOPE']] = true;
		}

		foreach ($result as $typeID => $scopes)
			$result[$typeID] = array_keys($scopes);

		return $result;
	}
	public static function markAsJunk($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException("Is not defined or invalid", 'entityTypeID');
		}

		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if($entityID <= 0)
		{
			throw new Main\ArgumentException("Must be greater than zero", 'entityID');
		}

		static::markDuplicateIndexAsJunk($entityTypeID, $entityID);
	}

	public static function setStatusID($userID, $entityTypeID, $typeID, $matchHash, $scope, $statusID)
	{
		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}

		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if(!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new Main\ArgumentException("Is not defined or invalid", 'entityTypeID');
		}

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}
		if(!DuplicateIndexType::isDefined($typeID))
		{
			throw new Main\ArgumentException("Is not defined or invalid", 'typeID');
		}
		$matchHash = (string)$matchHash;
		$scope = (string)$scope;
		$statusID = (int)$statusID;

		static::doSetStatusId($userID, $entityTypeID, $typeID, $matchHash, $scope, $statusID);
	}
	protected static function doSetStatusId(
		int $userID,
		int $entityTypeID,
		int $typeID,
		string $matchHash,
		string $scope,
		int	$statusID
	)
	{
		Entity\DuplicateIndexTable::setStatusID($userID, $entityTypeID, $typeID, $matchHash, $scope, $statusID);
	}
	protected static function getTypeScopeMap($entityTypeID)
	{
		if (!\CCrmOwnerType::isDefined($entityTypeID))
			return array();

		if (self::$typeScopeMap === null)
		{
			self::$typeScopeMap = array();
		}

		if (!isset(self::$typeScopeMap[$entityTypeID]))
		{
			self::$typeScopeMap[$entityTypeID] = array();
			foreach (DuplicateManager::getDedupeTypeScopeMap($entityTypeID) as $typeID => $scopes)
				self::$typeScopeMap[$entityTypeID][$typeID] = array_fill_keys($scopes, true);
		}

		return self::$typeScopeMap[$entityTypeID];
	}
	protected function internalBuild(array &$progressData)
	{
		$offset = isset($progressData['OFFSET']) ? max((int)$progressData['OFFSET'], 0) : 0;
		$limit = isset($progressData['LIMIT']) ? max((int)$progressData['LIMIT'], 0) : 0;

		$dataSource = $this->getDataSource();
		$result = $dataSource->getList($offset, $limit);

		$rankings = $result->getAllRankings();
		DuplicateEntityRanking::initializeBulk($rankings,
			array('CHECK_PERMISSIONS' => $this->isPermissionCheckEnabled(), 'USER_ID' => $this->getUserID())
		);

		$rootEntityIDs = array();
		$items = $result->getItems();
		foreach($items as $matchHash => $item)
		{
			$rootEntityInfo = array();
			if($this->tryResolveRootEntity($item, $matchHash, $rootEntityInfo))
			{
				$entityID = $rootEntityInfo['ENTITY_ID'];
				$rootEntityIDs[] = $entityID;
				$item->setRootEntityID($entityID);
			}
			else
			{
				$result->removeItem($matchHash);
			}
		}

		$sortParams = $this->prepareSortParams($rootEntityIDs);
		$effectiveItemCount = 0;

		$items = $result->getItems();
		foreach($items as $matchHash => $item)
		{
			$enableOverwrite = $item->getOption('enableOverwrite', true);
			if(
				!$enableOverwrite
				&& $this->duplicateIndexExists($this->getPrimaryKey($matchHash)))
			{
				if ($this->params->limitByDirtyIndexItems())
				{
					$this->markAsNotDirty($this->getPrimaryKey($matchHash));
				}
				continue;
			}

			$data = $this->prepareTableData($matchHash, $item, $sortParams, true);
			$data['STATUS_ID'] = DuplicateStatus::PENDING;
			$this->saveDuplicateIndexItem($data);
			$effectiveItemCount++;
		}
		if ($this->params->limitByDirtyIndexItems())
		{
			$this->processInvalidDirtyItems($result->getInvalidItems());
		}

		$processedItemCount = $result->getProcessedItemCount();
		$progressData['EFFECTIVE_ITEM_COUNT'] = $effectiveItemCount;
		$progressData['PROCESSED_ITEM_COUNT'] = $processedItemCount;
		$progressData['OFFSET'] = $offset + $processedItemCount;

		return $this->isInProgress($progressData);
	}
	public function isInProgress(array &$progressData)
	{
		return isset($progressData['PROCESSED_ITEM_COUNT']) && $progressData['PROCESSED_ITEM_COUNT'] > 0;
	}
	protected function getRootEntityID($matchHash)
	{
		$query = new Main\Entity\Query(Entity\DuplicateIndexTable::getEntity());
		$query->addSelect('ROOT_ENTITY_ID');

		$query->addFilter('=USER_ID', $this->getUserID());
		$query->addFilter('=ENTITY_TYPE_ID', $this->getEntityTypeID());
		$query->addFilter('=TYPE_ID', $this->typeID);
		$query->addFilter('=MATCH_HASH', $matchHash);

		$query->setLimit(1);

		$fields = $query->exec()->fetch();
		return is_array($fields) ? (int)$fields['ROOT_ENTITY_ID'] : 0;
	}
	protected function getPrimaryKey($matchHash)
	{
		return array(
			'USER_ID' => $this->getUserID(),
			'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
			'TYPE_ID' => $this->typeID,
			'MATCH_HASH' => $matchHash,
			'SCOPE' => $this->getScope()
		);
	}
	protected function checkRootEntityMismatches($rootEntityID, $matchHash, array $entities)
	{
		$map = array();
		/** @var DuplicateEntity $entity */
		foreach($entities as $entity)
		{
			$entityID = $entity->getEntityID();
			if($entityID === $rootEntityID)
			{
				continue;
			}

			$entityCriterion = $entity->getCriterion();
			$entityMatchHash = $entityCriterion ? $entityCriterion->getMatchHash() : $matchHash;
			if(!isset($map[$entityMatchHash]))
			{
				$map[$entityMatchHash] = array();
			}
			$map[$entityMatchHash][] = $entityID;
		}

		foreach($map as $entityMatchHash => $entityIDs)
		{
			$mismatches = array_intersect(
				$entityIDs,
				DuplicateIndexMismatch::getMismatches(
					$this->getEntityTypeID(),
					$rootEntityID,
					$this->typeID,
					$entityMatchHash,
					$this->getUserID(),
					100
				)
			);

			if(count($entityIDs) > count($mismatches))
			{
				return true;
			}
		}
		return false;
	}
	protected function tryResolveRootEntity(Duplicate $item, $matchHash, array &$entityInfo)
	{
		$entityTypeID = $this->getEntityTypeID();
		$entities = $item->getEntitiesByType($entityTypeID);

		/** @var DuplicateEntity[] $entities */
		$qty = count($entities);
		if($qty == 0)
		{
			return false;
		}
		elseif($qty === 1)
		{
			$entity = $entities[0];
			$entityID = $entity->getEntityID();

			$entityInfo['ENTITY_ID'] = $entityID;
			return true;
		}

		$entityID = $item->getRootEntityID();
		$entity = $entityID > 0 ? $item->findEntity($entityTypeID, $entityID) : null;
		if($entity)
		{
			if($this->checkRootEntityMismatches($entityID, $matchHash, $entities))
			{
				$entityInfo['ENTITY_ID'] = $entityID;
				return true;
			}
		}

		usort($entities, array('Bitrix\Crm\Integrity\DuplicateEntity', 'compareByRanking'));
		for($i = ($qty - 1); $i >= 0; $i--)
		{
			$entity = $entities[$i];
			if($entity->getCriterion() !== null)
			{
				continue;
			}

			$entityID = $entity->getEntityID();

			if($this->checkRootEntityMismatches($entityID, $matchHash, $entities))
			{
				$entityInfo['ENTITY_ID'] = $entityID;
				return true;
			}
		}
		return false;
	}
	protected function prepareSortParams(array $entityIDs)
	{
		$resut = [
			'PERS' => [],
			'ORG' => [],
			'COMM' => [],
			'RQ' => [],
			'BD' => [],
			'VOL' => [],
		];
		if(!empty($entityIDs))
		{
			$entityTypeID = $this->getEntityTypeID();
			if($entityTypeID === \CCrmOwnerType::Lead)
			{
				$resut['PERS'] = DuplicatePersonCriterion::prepareSortParams($entityTypeID, $entityIDs);
				$resut['ORG'] = DuplicateOrganizationCriterion::prepareSortParams($entityTypeID, $entityIDs);
			}
			elseif($entityTypeID === \CCrmOwnerType::Contact)
			{
				$resut['PERS'] = DuplicatePersonCriterion::prepareSortParams($entityTypeID, $entityIDs);
			}
			elseif($entityTypeID === \CCrmOwnerType::Company)
			{
				$resut['ORG'] = DuplicateOrganizationCriterion::prepareSortParams($entityTypeID, $entityIDs);
			}
			$resut['COMM'] = DuplicateCommunicationCriterion::prepareSortParams($entityTypeID, $entityIDs);
			if ($entityTypeID === \CCrmOwnerType::Contact || $entityTypeID === \CCrmOwnerType::Company)
			{
				$scope = $this->getScope();
				if ($scope !== DuplicateIndexType::DEFAULT_SCOPE)
				{
					$countryId = EntityRequisite::getCountryIdByDuplicateCriterionScope($scope);
					$resut['RQ'] = DuplicateRequisiteCriterion::prepareSortParams($entityTypeID, $entityIDs, $countryId);
					$resut['BD'] = DuplicateBankDetailCriterion::prepareSortParams($entityTypeID, $entityIDs, $countryId);
				}
			}
			$resut['VOL'] = DuplicateVolatileCriterion::prepareSortParams($entityTypeID, $entityIDs);
		}
		return $resut;
	}
	protected function prepareTableData($matchHash, Duplicate $item, array &$sortParams, $enablePrimaryKey = true)
	{
		$data = array(
			'ROOT_ENTITY_ID' => 0,
			'ROOT_ENTITY_NAME' => '',
			'ROOT_ENTITY_TITLE' => '',
			'ROOT_ENTITY_PHONE' => '',
			'ROOT_ENTITY_EMAIL' => ''
		);
		foreach (DuplicateRequisiteCriterion::getSupportedDedupeTypes() as $typeID)
		{
			$fieldName = DuplicateIndexType::resolveName($typeID);
			$data["ROOT_ENTITY_{$fieldName}"] = '';
		}
		foreach (DuplicateBankDetailCriterion::getSupportedDedupeTypes() as $typeID)
		{
			$fieldName = DuplicateIndexType::resolveName($typeID);
			$data["ROOT_ENTITY_{$fieldName}"] = '';
		}
		$data['QUANTITY'] = 0;

		$entityTypeID = $this->getEntityTypeID();

		if($enablePrimaryKey)
		{
			$data['USER_ID'] = $this->getUserID();
			$data['ENTITY_TYPE_ID'] = $entityTypeID;
			$data['TYPE_ID'] = $this->typeID;
			$data['MATCH_HASH'] = $matchHash;
			$data['SCOPE'] = $this->getScope();

			$criterion = $item->getCriterion();
			$data['MATCHES'] = serialize($criterion->getMatches());
		}

		$entityID = $item->getRootEntityID();
		if($entityID > 0)
		{
			$data['ROOT_ENTITY_ID'] = $entityID;

			$pers = isset($sortParams['PERS']) ? $sortParams['PERS'] : null;
			if(is_array($pers) && isset($pers[$entityID]) && isset($pers[$entityID]['FULL_NAME']))
			{
				$data['ROOT_ENTITY_NAME'] = $pers[$entityID]['FULL_NAME'];
			}
			$org = isset($sortParams['ORG']) ? $sortParams['ORG'] : null;
			if(is_array($org) && isset($org[$entityID]) && isset($org[$entityID]['TITLE']))
			{
				$data['ROOT_ENTITY_TITLE'] = $org[$entityID]['TITLE'];
			}

			$comm = isset($sortParams['COMM']) ? $sortParams['COMM'] : null;
			if(is_array($comm) && isset($comm[$entityID]))
			{
				if(isset($comm[$entityID]['PHONE']))
				{
					$data['ROOT_ENTITY_PHONE'] = $comm[$entityID]['PHONE'];
				}
				if(isset($comm[$entityID]['EMAIL']))
				{
					$data['ROOT_ENTITY_EMAIL'] = $comm[$entityID]['EMAIL'];
				}
			}

			$rq = isset($sortParams['RQ']) ? $sortParams['RQ'] : null;
			if(is_array($rq) && isset($rq[$entityID]))
			{
				foreach (DuplicateRequisiteCriterion::getSupportedDedupeTypes() as $typeID)
				{
					$fieldName = DuplicateIndexType::resolveName($typeID);
					if (is_array($rq[$entityID][$fieldName]))
					{
						foreach ($rq[$entityID][$fieldName] as $scope => $value)
						{
							if ($scope === $this->getScope())
								$data["ROOT_ENTITY_{$fieldName}"] = $value;
						}
					}
				}
			}

			$bd = isset($sortParams['BD']) ? $sortParams['BD'] : null;
			if(is_array($bd) && isset($bd[$entityID]))
			{
				foreach (DuplicateBankDetailCriterion::getSupportedDedupeTypes() as $typeID)
				{
					$fieldName = DuplicateIndexType::resolveName($typeID);
					if (isset($bd[$entityID][$fieldName]))
					{
						foreach ($bd[$entityID][$fieldName] as $scope => $value)
						{
							if ($scope === $this->getScope())
								$data["ROOT_ENTITY_{$fieldName}"] = $value;
						}
					}
				}
			}

		}

		return $data;
	}
	protected function deleteDuplicateIndexByFilter(array $filter)
	{
		Entity\DuplicateIndexTable::deleteByFilter($filter);
	}
	protected function saveDuplicateIndexItem(array $fields)
	{
		Entity\DuplicateIndexTable::upsert($fields);
	}
	protected function duplicateIndexExists($primary)
	{
		return Entity\DuplicateIndexTable::exists($primary);
	}
	protected static function markDuplicateIndexAsJunk($entityTypeID, $entityID)
	{
		Entity\DuplicateIndexTable::markAsJunk($entityTypeID, $entityID);
	}

	protected function processInvalidDirtyItems(array $invalidHashes)
	{
		// do nothing. Used only in AutomaticDuplicateIndexBuilder
	}

	protected function markAsNotDirty($primary)
	{
		throw new NotImplementedException('This duplicate index can not be dirty');
	}
}
