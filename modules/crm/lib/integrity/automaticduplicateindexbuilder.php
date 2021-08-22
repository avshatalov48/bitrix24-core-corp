<?php

namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\Integrity\Entity\AutomaticDuplicateIndexTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\NotImplementedException;
use Bitrix\Main\Type\DateTime;

class AutomaticDuplicateIndexBuilder extends DuplicateIndexBuilder
{
	const STEP_PROCESS_UPDATED_ITEMS = 'PROCESS_UPDATED_ITEMS';
	const STEP_BUILD_NEW_ITEMS = 'BUILD_NEW_ITEMS';

	protected $rebuildChangedOnly = true;

	protected $step = null;

	public function __construct($typeID, DedupeParams $params)
	{
		$this->step = self::STEP_BUILD_NEW_ITEMS;
		$this->rebuildChangedOnly = $params->isCheckChangedOnly() && ($params->getIndexDate() instanceof DateTime);
		if (!$this->rebuildChangedOnly)
		{
			$params->clearIndexDate();
		}

		$params->setLimitByAssignedUser(true);
		parent::__construct($typeID, $params);
	}

	public function remove()
	{
		if (!$this->rebuildChangedOnly)
		{
			Entity\AutomaticDuplicateIndexTable::deleteByFilter(
				array(
					'TYPE_ID' => $this->typeID,
					'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
					'USER_ID' => $this->getUserID(),
					'=SCOPE' => $this->getScope()
				)
			);
		}
	}

	public function removeUnusedIndexByTypeIds(array $typeIds)
	{
		Entity\AutomaticDuplicateIndexTable::deleteByFilter(
			array(
				'TYPE_ID' => $typeIds,
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'USER_ID' => $this->getUserID(),
				'=SCOPE' => $this->getScope()
			)
		);
	}

	public function removeUnusedIndexByScope(string $scope)
	{
		Entity\AutomaticDuplicateIndexTable::deleteByFilter(
			array(
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'USER_ID' => $this->getUserID(),
				'=SCOPE' => $scope
			)
		);
	}

	public function build(array &$progressData)
	{
		if (isset($progressData['STEP']) && in_array($progressData['STEP'],
				[self::STEP_BUILD_NEW_ITEMS, self::STEP_PROCESS_UPDATED_ITEMS]))
		{
			$this->step = $progressData['STEP'];
		}
		if ($this->step === self::STEP_BUILD_NEW_ITEMS)
		{
			$this->params->setLimitByDirtyIndexItems(false);
			$inProgress = $this->internalBuild($progressData);
			if ($inProgress)
			{
				return true;
			}
			elseif ($this->rebuildChangedOnly)
			{
				$progressData['STEP'] = self::STEP_PROCESS_UPDATED_ITEMS;
				return true;
			}
		}

		if ($this->step === self::STEP_PROCESS_UPDATED_ITEMS)
		{
			$progressData['OFFSET'] = 0;
			$this->params->clearIndexDate();
			$this->params->setLimitByDirtyIndexItems(true);
			$inProgress = $this->internalBuild($progressData);
			if ($inProgress)
			{
				return true;
			}

			// remove dirty index items
			static::deleteDuplicateIndexByFilter([
				'TYPE_ID' => $this->getTypeID(),
				'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
				'USER_ID' => $this->getUserID(),
				'SCOPE' => $this->getScope(),
				'IS_DIRTY' => true
			]);

			// set pending status to all queue
			Entity\AutomaticDuplicateIndexTable::setStatusByFilter(
				\Bitrix\Crm\Integrity\DuplicateStatus::PENDING,
				[
					'TYPE_ID' => $this->getTypeID(),
					'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
					'USER_ID' => $this->getUserID(),
					'=SCOPE' => $this->getScope(),
					'!STATUS_ID' => \Bitrix\Crm\Integrity\DuplicateStatus::PENDING
				]
			);
		}

		return false;
	}

	public static function removeIndex(
		int $userID,
		int $entityTypeID,
		int $typeID,
		string $matchHash,
		string $scope
	)
	{
		Entity\AutomaticDuplicateIndexTable::deleteByFilter([
			'TYPE_ID' => $typeID,
			'ENTITY_TYPE_ID' => $entityTypeID,
			'USER_ID' => $userID,
			'=MATCH_HASH' => $matchHash,
			'=SCOPE' => $scope
		]);
	}

	protected function deleteDuplicateIndexByFilter(array $filter)
	{
		if (isset($filter['MATCH_HASH']))
		{
			if (is_array($filter['MATCH_HASH']))
			{
				$filter['@MATCH_HASH'] = $filter['MATCH_HASH'];
			}
			else
			{
				$filter['=MATCH_HASH'] = $filter['MATCH_HASH'];
			}
			unset($filter['MATCH_HASH']);
		}
		if (isset($filter['SCOPE']))
		{
			$filter['=SCOPE'] = $filter['SCOPE'];
			unset($filter['SCOPE']);
		}
		if (isset($filter['IS_DIRTY']))
		{
			$filter['=IS_DIRTY'] = $filter['IS_DIRTY'];
			unset($filter['IS_DIRTY']);
		}
		Entity\AutomaticDuplicateIndexTable::deleteByFilter($filter);
	}

	public static function getExistedTypes($entityTypeID, $userID, $scope = null)
	{
		$filter = array(
			'=USER_ID' => $userID,
			'=ENTITY_TYPE_ID' => $entityTypeID
		);
		if ($scope !== null)
		{
			$filter['=SCOPE'] = $scope;
		}
		$dbResult = AutomaticDuplicateIndexTable::getList(
			array(
				'select' => array('TYPE_ID'),
				'order' => array('TYPE_ID' => 'ASC'),
				'group' => array('TYPE_ID'),
				'filter' => $filter
			)
		);

		$result = array();
		while ($fields = $dbResult->fetch())
		{
			$result[] = intval($fields['TYPE_ID']);
		}
		return $result;
	}

	public static function getExistedTypeScopeMap($entityTypeID, $userID)
	{
		$dbResult = AutomaticDuplicateIndexTable::getList(
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
		while ($fields = $dbResult->fetch())
		{
			$typeID = (int)$fields['TYPE_ID'];
			if (!isset($result[$typeID]))
			{
				$result[$typeID] = array();
			}
			if (!isset($result[$typeID][$fields['SCOPE']]))
			{
				$result[$typeID][$fields['SCOPE']] = true;
			}
		}

		foreach ($result as $typeID => $scopes)
			$result[$typeID] = array_keys($scopes);

		return $result;
	}

	public static function markAsDirty($entityTypeID, $entityID)
	{
		if (!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}
		if (!\CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new ArgumentException("Is not defined or invalid", 'entityTypeID');
		}

		if (!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}
		if ($entityID <= 0)
		{
			throw new ArgumentException("Must be greater than zero", 'entityID');
		}

		Entity\AutomaticDuplicateIndexTable::markAsDirty($entityTypeID, $entityID);
	}

	protected static function doSetStatusId(
		int $userID,
		int $entityTypeID,
		int $typeID,
		string $matchHash,
		string $scope,
		int $statusID
	)
	{
		$item = Entity\AutomaticDuplicateIndexTable::query()
			->where('USER_ID', $userID)
			->where('ENTITY_TYPE_ID', $entityTypeID)
			->where('TYPE_ID', $typeID)
			->where('SCOPE', $scope)
			->where('MATCH_HASH', $matchHash)
			->setLimit(1)
			->fetchObject();

		if ($item)
		{
			$item
				->setStatusId($statusID)
				->save();
		}
	}

	protected function prepareTableData($matchHash, Duplicate $item, array &$sortParams, $enablePrimaryKey = true)
	{
		$data = parent::prepareTableData($matchHash, $item, $sortParams, $enablePrimaryKey);
		$data['IS_DIRTY'] = 'N';

		return $data;
	}

	protected function saveDuplicateIndexItem(array $fields)
	{
		$primaryFields = ['USER_ID', 'ENTITY_TYPE_ID', 'TYPE_ID', 'MATCH_HASH', 'SCOPE'];
		$query = AutomaticDuplicateIndexTable::query()
			->setSelect(['ID']);

		foreach ($primaryFields as $fieldCode)
		{
			if (isset($fields[$fieldCode]))
			{
				$query->where($fieldCode, $fields[$fieldCode]);
			}
			else
			{
				throw new \Bitrix\Main\ArgumentException($fieldCode . " must be set");
			}
		}

		$existedItem = $query->fetchObject();
		if ($existedItem)
		{
			$hasChanges = false;
			foreach ($fields as $code => $value)
			{
				if ($existedItem[$code] !== $value)
				{
					$existedItem[$code] = $value;
					$hasChanges = true;
				}
			}
			if ($hasChanges)
			{
				$existedItem->save();
			}
		}
		else
		{
			Entity\AutomaticDuplicateIndexTable::add($fields);
		}
	}

	protected function getRootEntityID($matchHash)
	{
		$existedItem = $this->getQueryForMatchHash((string)$matchHash)
			->setSelect(['ROOT_ENTITY_ID'])
			->fetch();

		return $existedItem ? (int)$existedItem['ROOT_ENTITY_ID'] : 0;
	}

	protected function getPrimaryKey($matchHash)
	{
		$existedItem = $this->getQueryForMatchHash((string)$matchHash)
			->setSelect(['ID'])
			->fetch();

		return $existedItem ? $existedItem['ID'] : null;
	}

	protected function getQueryForMatchHash(string $matchHash)
	{
		return Entity\AutomaticDuplicateIndexTable::query()
			->where('USER_ID', $this->getUserID())
			->where('ENTITY_TYPE_ID', $this->getEntityTypeID())
			->where('TYPE_ID', $this->getTypeID())
			->where('SCOPE', $this->getScope())
			->where('MATCH_HASH', $matchHash)
			->setLimit(1);
	}

	protected function duplicateIndexExists($primary)
	{
		return $primary > 0;
	}

	protected static function markDuplicateIndexAsJunk($entityTypeID, $entityID)
	{
		throw new NotImplementedException('Automatic duplicate index can not be junk');
	}

	protected function processInvalidDirtyItems(array $invalidHashes)
	{
		if (empty($invalidHashes))
		{
			return;
		}
		static::deleteDuplicateIndexByFilter([
			'TYPE_ID' => $this->getTypeID(),
			'ENTITY_TYPE_ID' => $this->getEntityTypeID(),
			'USER_ID' => $this->getUserID(),
			'SCOPE' => $this->getScope(),
			'MATCH_HASH' => $invalidHashes,
			'IS_DIRTY' => true,
		]);
	}

	protected function markAsNotDirty($primary)
	{
		Entity\AutomaticDuplicateIndexTable::update(
			$primary,
			[
				'IS_DIRTY' => false,
			]
		);
	}
}
