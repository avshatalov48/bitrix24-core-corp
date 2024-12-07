<?php

namespace Bitrix\CrmMobile\Kanban\ControllerStrategy;

use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Kanban\EntityActivityCounter;
use Bitrix\Crm\Security\Manager;
use Bitrix\CrmMobile\Kanban\Kanban;
use Bitrix\Main\Result;
use Bitrix\Main\UI\Filter\Options;

abstract class Base implements StrategyInterface
{
	protected const TMP_FILTER_PRESET_ID = 'tmp_filter';

	protected array $params;
	protected int $entityTypeId;

	public function setParams(array $params): Base
	{
		$this->params = $params;

		return $this;
	}

	public function setEntityTypeId(int $entityTypeId): Base
	{
		$this->entityTypeId = $entityTypeId;

		return $this;
	}

	protected function getFilterParams(): array
	{
		return ($this->params['filterParams'] ?? []);
	}

	protected function setFilterPreset(string $presetId, Options $filterOptions): void
	{
		if ($presetId === 'default_filter')
		{
			$presetId = 'tmp_filter';
		}

		$presets = $filterOptions->getPresets();

		if ($presetId !== self::TMP_FILTER_PRESET_ID && !empty($presets[$presetId]))
		{
			$preset = $presets[$presetId];

			$data = [
				'fields' => $preset['fields'] ?? [],
				'preset_id' => $presetId,
				'rows' => (empty($preset['fields']) || !is_array($preset['fields'])) ? [] : array_keys($preset['fields']),
				'name' => $preset['name'],
			];
		}
		elseif ($presetId === self::TMP_FILTER_PRESET_ID)
		{
			$fields = [];

			$currentFilter = $this->getCurrentFilter();
			if ($currentFilter)
			{
				$tmpFilter = $currentFilter['tmpFields'] ?? [];
				foreach ($tmpFilter as $fieldName => $field)
				{
					$fields[$fieldName] = $field;
				}
			}

			$data = [
				'fields' => $fields,
				'preset_id' => self::TMP_FILTER_PRESET_ID,
				'rows' => array_keys($fields),
			];
		}
		else
		{
			return;
		}

		$filterOptions->setFilterSettings($presetId, $data);
		$filterOptions->save();
	}

	public function getEntityType(): string
	{
		return \CCrmOwnerType::ResolveName($this->entityTypeId);
	}

	protected function prepareActivityCounters(array &$items): void
	{
		if (empty($items))
		{
			return;
		}

		$errors = [];
		$entityActivityCounter = new EntityActivityCounter(
			$this->getEntityTypeId(),
			array_keys($items),
			$errors,
		);
		$entityActivityCounter->appendToEntityItems($items);
	}

	public function getEntityTypeId(): int
	{
		return $this->entityTypeId;
	}

	protected function getEntityAttributes(array $items, string $columnIdName = 'id'): ?array
	{
		if (empty($items))
		{
			return null;
		}

		$ids = array_column($items, $columnIdName);
		$entityTypeName = $this->getEntityType();

		return Manager::resolveController($entityTypeName)->getPermissionAttributes($entityTypeName, $ids);
	}

	public function prepareFilterPresets(Entity $entity, array $presets, ?string $defaultPresetName): array
	{
		$results = [];

		foreach ($presets as $id => $preset)
		{
			$name = html_entity_decode($preset['name'] ?? '', ENT_QUOTES);

			if ($id === null || $id === 'default_filter' || $id === 'tmp_filter')
			{
				continue;
			}

			$default = ($id === $defaultPresetName);

			$results[] = compact('id', 'name', 'default');
		}

		return $results;
	}

	public function prepareFilter(Entity $entity): void
	{
		// can be implemented in a child class
	}

	public function getGridId(): string
	{
		$kanban = $this->getKanbanInstance();

		return $kanban->getEntity()->getGridId();
	}

	public function changeCategory(array $ids, int $categoryId): Result
	{
		$kanban = $this->getKanbanInstance();
		$userPermissions = $kanban->getCurrentUserPermissions();

		return $kanban->getEntity()->updateItemsCategory($ids, $categoryId, $userPermissions);
	}

	protected function getKanbanInstance(): Kanban
	{
		return Kanban::getInstance($this->getEntityType(), $this->getFilterParams());
	}

	public function getCurrentFilter()
	{
		return $this->params['filter'] ?? null;
	}
}
