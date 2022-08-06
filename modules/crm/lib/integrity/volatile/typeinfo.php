<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Bitrix\Crm\Integrity\Volatile;

use Bitrix\Crm\Agent\Duplicate\Volatile\Cleaner;
use Bitrix\Crm\Agent\Duplicate\Volatile\IndexRebuild;
use Bitrix\Crm\Integrity\DuplicateIndexType;
use Bitrix\Crm\Integrity\DuplicateIndexTypeSettingsTable;
use Bitrix\Crm\Integrity\DuplicateVolatileCriterion;
use Bitrix\Crm\Integrity\Volatile\Type\State;
use Bitrix\Main\EventManager;
use CCrmOwnerType;
use CCrmSecurityHelper;

class TypeInfo
{
	/** @var array|null */
	protected $info = null;

	/** @var bool */
	protected $isEventHadlersRegistered = false;

	/** @var array|null */
	protected $indexByVolatileTypeId = null;

	/** @var array|null */
	protected $indexByEntityTypeId = null;

	public static function getInstance(): TypeInfo
	{
		static $instance = null;

		if ($instance === null)
		{
			$instance = new static();
		}

		return $instance;
	}

	protected function __construct()
	{
		if (!$this->isEventHadlersRegistered)
		{
			EventManager::getInstance()->addEventHandler(
				'crm',
				'\\Bitrix\\Crm\\Integrity\\DuplicateIndexTypeSettingsTable::OnAfterAdd',
				[$this, 'onChangeTypeInfo']
			);
			EventManager::getInstance()->addEventHandler(
				'crm',
				'\\Bitrix\\Crm\\Integrity\\DuplicateIndexTypeSettingsTable::OnAfterUpdate',
				[$this, 'onChangeTypeInfo']
			);
			EventManager::getInstance()->addEventHandler(
				'crm',
				'\\Bitrix\\Crm\\Integrity\\DuplicateIndexTypeSettingsTable::OnAfterDelete',
				[$this, 'onChangeTypeInfo']
			);
			$this->isEventHadlersRegistered = true;
		}
	}

	public function onChangeTypeInfo()
	{
		$this->info = null;
		$this->indexByVolatileTypeId = null;
		$this->indexByEntityTypeId = null;
	}

	protected function ensureInformationLoaded()
	{
		if ($this->info === null)
		{
			$this->loadInfo();
		}
	}

	protected function loadInfo()
	{
		$this->info = [];
		$this->indexByVolatileTypeId = [];
		$this->indexByEntityTypeId = [];

		// Volatile types info
		$countryIdMap = [];
		$fieldPathNameMap = [];
		$fieldInfo = FieldInfo::getInstance();
		$res = DuplicateIndexTypeSettingsTable::getList(
			[
				'order' => ['ID' => 'ASC'],
				'select' => [
					'ID',
					'ACTIVE',
					'DESCRIPTION',
					'ENTITY_TYPE_ID',
					'STATE_ID',
					'FIELD_PATH',
					'FIELD_NAME',
				]
			]
		);
		$index = 0;
		while ($row = $res->fetch())
		{
			$volatileTypeId = (int)$row['ID'];
			$entityTypeId = (int)$row['ENTITY_TYPE_ID'];
			if (
				DuplicateIndexType::isDefined($volatileTypeId)
				&& CCrmOwnerType::IsDefined($entityTypeId)
				&& is_string($row['ACTIVE'])
				&& is_string($row['DESCRIPTION'])
				&& is_string($row['FIELD_PATH'])
				&& is_string($row['FIELD_NAME'])
				&& $row['FIELD_NAME'] !== ''
			)
			{
				if (!isset($this->indexByEntityTypeId[$entityTypeId]))
				{
					$this->indexByEntityTypeId[$entityTypeId] = [];
				}
				$this->indexByEntityTypeId[$entityTypeId][] = $index;
				$this->indexByVolatileTypeId[$volatileTypeId] = $index;
				$this->info[$index] = [
					'ID' => $volatileTypeId,
					'ACTIVE' => $row['ACTIVE'],
					'DESCRIPTION' => $row['DESCRIPTION'],
					'ENTITY_TYPE_ID' => $entityTypeId,
					'STATE_ID' => (int)$row['STATE_ID'],
					'FIELD_PATH' => $row['FIELD_PATH'],
					'FIELD_NAME' => $row['FIELD_NAME'],
				];
				$fieldPathName = $fieldInfo->getPathName($row['FIELD_PATH'], $row['FIELD_NAME']);
				$fieldPathNameMap[$volatileTypeId] = $fieldPathName;
				$categoryInfo = FieldCategory::getInstance()->getCategoryByPath($fieldPathName);
				$this->info[$index]['CATEGORY_INFO'] = $categoryInfo;
				if (isset($categoryInfo['params']['countryId']))
				{
					$countryIdMap[$categoryInfo['params']['countryId']] = true;
				}
				$index++;
			}
		}

		if (!empty($this->info))
		{
			$fieldMap = $fieldInfo->getFieldInfo(
				array_keys($this->indexByEntityTypeId),
				array_keys($countryIdMap)
			);

			foreach ($this->info as $index => $typeInfo)
			{
				if (isset($fieldMap[$typeInfo['ENTITY_TYPE_ID']][$fieldPathNameMap[$typeInfo['ID']]]))
				{
					$this->info[$index]['DESCRIPTION'] =
						$fieldMap[$typeInfo['ENTITY_TYPE_ID']][$fieldPathNameMap[$typeInfo['ID']]]['title']
					;
				}
			}
		}
	}

	public function getIds(): array
	{
		$this->ensureInformationLoaded();

		return array_keys($this->indexByVolatileTypeId);
	}

	public function getIdsByEntityTypes(array $entityTypeIds): array
	{
		$result = [];

		$this->ensureInformationLoaded();

		foreach($entityTypeIds as $entityTypeId)
		{
			if (isset($this->indexByEntityTypeId[$entityTypeId]))
			{
				foreach ($this->indexByEntityTypeId[$entityTypeId] as $index)
				{
					if (!isset($result[$entityTypeId]))
					{
						$result[$entityTypeId] = [];
					}

					$result[$entityTypeId][] = $this->info[$index]['ID'];
				}
			}
		}

		return $result;
	}

	public function get(): array
	{
		$result = [];

		$this->ensureInformationLoaded();

		foreach ($this->indexByVolatileTypeId as $volatileTypeId => $index)
		{
			$result[$volatileTypeId] = $this->info[$index];
		}

		return $result;
	}

	public function getById(int $volatileTypeId): array
	{
		$this->ensureInformationLoaded();

		if (isset($this->indexByVolatileTypeId[$volatileTypeId]))
		{
			return $this->info[$this->indexByVolatileTypeId[$volatileTypeId]];
		}

		return [];
	}

	private function getCurrentUserId()
	{
		return CCrmSecurityHelper::GetCurrentUserID();
	}

	public function assign(int $entityTypeId, int $volatileTypeId, string $fieldPath, string $fieldName)
	{
		$state = State::getInstance();
		$fields = [
			'ENTITY_TYPE_ID' => $entityTypeId,
			'DESCRIPTION' => '',
			'FIELD_PATH' => $fieldPath,
			'FIELD_NAME' => $fieldName,
		];

		if (
			DuplicateIndexTypeSettingsTable::getList(
				[
					'select' => ['ID'],
					'filter' => ['ID' => $volatileTypeId]
				]
			)->fetch()
		)
		{
			$state->set($volatileTypeId, State::STATE_FREE);
			EventHandler::onAssignVolatileTypes([$volatileTypeId]);
			DuplicateIndexTypeSettingsTable::update($volatileTypeId, $fields);
		}
		else
		{
			$fields['ID'] = $volatileTypeId;
			DuplicateIndexTypeSettingsTable::add($fields);
			EventHandler::onAssignVolatileTypes(
				[$volatileTypeId],
				DuplicateVolatileCriterion::getSupportedEntityTypes()
			);
		}

		$state->set($volatileTypeId, State::STATE_ASSIGNED);

		IndexRebuild::getInstance($volatileTypeId)->start(['USER_ID' => $this->getCurrentUserId()]);

		Cleaner::getInstance()->start([]);
	}

	public function release(int $volatileTypeId)
	{
		$state = State::getInstance();
		$state->set($volatileTypeId, State::STATE_FREE);

		EventHandler::onAssignVolatileTypes([$volatileTypeId]);

		Cleaner::getInstance()->start([]);
	}
}
