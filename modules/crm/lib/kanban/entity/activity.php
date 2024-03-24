<?php

namespace Bitrix\Crm\Kanban\Entity;

use Bitrix\Crm\Activity\Provider\Tasks\Task;
use Bitrix\Crm\Field;
use Bitrix\Crm\Field\Assigned;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use CCrmFieldInfoAttr;

Loc::loadMessages(__FILE__);

class Activity extends Entity
{
	use ActivityTrait;

	public function isCustomSectionSupported(): bool
	{
		return true;
	}

	public function isActivityCountersSupported(): bool
	{
		return true;
	}

	public function fillStageTotalSums(array $filter, array $runtime, array &$stages): void
	{
		// activity kanban working without knowing the number of elements in the column
		foreach ($stages as &$stage)
		{
			$stage['count'] = $this->getEntityActivities()->calculateTotalForActivityStage($stage['id'], $filter);
		}
	}

	public function getTypeName(): string
	{
		return \CCrmOwnerType::ActivityName;
	}

	public function getItemsSelectPreset(): array
	{
		return [
			'ID',
		];
	}

	public function isTotalPriceSupported(): bool
	{
		return false;
	}

	public function getFilterPresets(): array
	{
		return (new \Bitrix\Crm\Filter\Preset\Activity())
			->setDefaultValues($this->getFilter()->getDefaultFieldIDs())
			->getDefaultPresets()
		;
	}

	public function getStagesList(): array
	{
		$stageList = $this->getEntityActivities()->getStagesList($this->getCategoryId());

		foreach ($stageList as &$item)
		{
			if ($item['STATUS_ID'] === EntityActivities::STAGE_IDLE)
			{
				$item['NAME'] = Loc::getMessage('KANBAN_ACTIVITY_STAGE_NO_DEADLINE');
			}
		}
		unset($item);

		return $stageList;
	}

	protected function getDefaultAdditionalSelectFields(): array
	{
		return [
			'RESPONSIBLE_ID' => Loc::getMessage('CRM_KANBAN_ACTIVITY_ENTITY_FIELD_RESPONSIBLE'),
			'OWNER_ENTITY' => Loc::getMessage('CRM_KANBAN_ACTIVITY_ENTITY_FIELD_PROVIDER_OWNER'),
			'DEADLINE' => Loc::getMessage('CRM_KANBAN_ACTIVITY_ENTITY_FIELD_DEADLINE'),
			'SUBJECT' => Loc::getMessage('CRM_KANBAN_ACTIVITY_ENTITY_FIELD_SUBJECT'),
			'PROVIDER_ID' => Loc::getMessage('CRM_KANBAN_ACTIVITY_ENTITY_FIELD_PROVIDER_ID'),
			'PROVIDER_TYPE_ID' => Loc::getMessage('CRM_KANBAN_ACTIVITY_ENTITY_FIELD_PROVIDER_TYPE_ID'),
			'OWNER_ID' => '',
			'OWNER_TYPE_ID' => '',
		];
	}

	public function getBaseFields(): array
	{
		return [
			'ID' => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
			],
			'RESPONSIBLE_ID' => [
				'TYPE' => Field::TYPE_USER,
				'ATTRIBUTES' => [
					\CCrmFieldInfoAttr::ReadOnly,
					\CCrmFieldInfoAttr::CanNotBeEmptied,
					\CCrmFieldInfoAttr::HasDefaultValue,
				],
				'CLASS' => Assigned::class,
			],
			'SUBJECT' => [
				'TYPE' => Field::TYPE_STRING,
				'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
			],
			'DEADLINE' => [
				'TYPE' => Field::TYPE_DATETIME,
				'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
			],
			'PROVIDER_ID' => [
				'TYPE' => Field::TYPE_CRM_ACTIVITY_PROVIDER,
				'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
			],
			'PROVIDER_TYPE_ID' => [
				'TYPE' => Field::TYPE_CRM_ACTIVITY_PROVIDER_TYPE,
				'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
			],
			'OWNER_ENTITY' => [
				'TYPE' => 'crm',
				'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
			],
			'OWNER_TYPE_ID' => [
				'TYPE' => Field::TYPE_INTEGER,
				'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
			],
			'COMPLETED' => [
				'TYPE' => 'string',
				'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly],
			],
		];
	}

	public function getAdditionalEditFields(): array
	{
		return (array)$this->getAdditionalEditFieldsFromOptions();
	}

	public function isCustomPriceFieldsSupported(): bool
	{
		return false;
	}

	public function isInlineEditorSupported(): bool
	{
		return false;
	}

	public function getAssignedByFieldName(): string
	{
		return 'RESPONSIBLE_ID';
	}

	public function hasOpenedField(): bool
	{
		return false;
	}

	public function isStageEmpty(string $stageId): bool
	{
		return false;
	}

	public function updateItemStage(int $id, string $stageId, array $newStateParams, array $stages): \Bitrix\Main\Result
	{
		$result = $this->getItemViaLoadedItems($id);
		if (!$result->isSuccess())
		{
			return $result;
		}

		return $this->changeStageByActivity($stageId, $id);
	}

	public function getRequiredFieldsByStages(array $stages): array
	{
		return [];
	}

	public function prepareItemCommonFields(array $item): array
	{
		if (isset($item['PROVIDER_TYPE_ID'], $item['PROVIDER_ID']))
		{
			$item['PROVIDER_TYPE_ID'] = $item['PROVIDER_ID'] . '-' . $item['PROVIDER_TYPE_ID'];
		}

		$item = parent::prepareItemCommonFields($item);

		if (isset($item['OWNER_ID'], $item['OWNER_TYPE_ID']))
		{
			$item['OWNER_ENTITY'] = \CCrmOwnerTypeAbbr::ResolveByTypeID($item['OWNER_TYPE_ID']) . '_' . $item['OWNER_ID'];
			unset($item['OWNER_ID'], $item['OWNER_TYPE_ID']);
		}
		elseif (isset($item['OWNER_ID']))
		{
			unset($item['OWNER_ID']);
		}
		elseif (isset($item['OWNER_TYPE_ID']))
		{
			unset($item['OWNER_TYPE_ID']);
		}

		return $item;
	}

	public function isCategoriesSupported(): bool
	{
		return false;
	}

	protected function getActivityData(int $ownerTypeId, int $ownerId): ?array
	{
		$result = $this->getItemViaLoadedItems($ownerId);
		if ($result->isSuccess())
		{
			return $result->getData()['item'];
		}

		return null;
	}

	public function prepareFilter(array &$filter, ?string $viewMode = null): void
	{
		if (!empty($filter['CREATED']))
		{
			$daysAgo = (int)$filter['CREATED'];
			if ($daysAgo > 0 && $daysAgo < 365)
			{
				$dt = \CCrmDateTimeHelper::getUserDate(
					(new DateTime())->add("-P{$daysAgo}D")
				);

				$filter['>=CREATED'] = $dt;
			}

			unset($filter['CREATED']);
		}

		if (!empty($filter['TYPE_ID']))
		{
			Task::transformTaskInFilter(
				$filter,
				'TYPE_ID',
				true
			);
		}
	}

	public function applySubQueryBasedFilters(array &$filter, ?string $viewMode = null): void
	{
		$filterFactory = Container::getInstance()->getFilterFactory();
		$provider = $filterFactory->getDataProvider(
			$filterFactory::getSettingsByGridId(\CCrmOwnerType::Activity, $this->getGridId()),
		);

		$this->applyCountersFilter($filter, $provider);
	}

	public function isActivityCountersFilterSupported(): bool
	{
		return true;
	}

	public function getFilterLazyLoadParams(): ?array
	{
		$component = 'bitrix:crm.activity.list';
		$data = [
			'filterId' => urlencode($this->getGridId()),
		];

		return [
			'GET_LIST' => [
				'component' => $component,
				'action' => 'getList',
				'data' => $data,
			],
			'GET_FIELD' =>[
				'component' => $component,
				'action' => 'getField',
				'data' => $data,
			],
		];
	}

	public function getItems(array $parameters): \CDBResult
	{
		$filter = $parameters['filter'] ?? [];

		$stageId = $filter['ACTIVITY_STAGE_ID'] ?? '';
		unset($filter['ACTIVITY_STAGE_ID']);

		$stageFilterInstance = Entity\ActivityStages\Factory::getStageInstance($stageId);

		$parameters['filter'] = $stageFilterInstance->getFilterParams($filter);
		$parameters['order'] = ['ID' => 'DESC'];

		return $this->getEntityActivities()->prepareItemsResult(
			$stageId,
			parent::getItems($parameters),
			$filter
		);
	}
}
