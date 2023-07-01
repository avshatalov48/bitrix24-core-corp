<?php

namespace Bitrix\CrmMobile\Kanban\Entity;

use Bitrix\Crm\Field\Collection;
use Bitrix\Crm\Filter\FactoryOptionable;
use Bitrix\Crm\Item;
use Bitrix\Crm\Kanban\Exception;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display;
use Bitrix\Crm\Service\Display\Field;
use Bitrix\Crm\Service\Factory;
use Bitrix\CrmMobile\Kanban\Entity;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Error;
use Bitrix\Main\Filter\DataProvider;
use Bitrix\Main\Grid\Options;
use Bitrix\Main\Result;

abstract class ListEntity extends Entity
{
	protected const LIMIT = 20;

	protected const ALWAYS_SHOW_FIELD_NAMES = [];
	// @todo maybe need to add more fields
	protected const DEFAULT_SHOW_FIELD_NAMES = ['CREATED_TIME', 'ASSIGNED_BY_ID'];
	protected const DEFAULT_SELECT_FIELD_NAMES = ['PHONE', 'EMAIL', 'ASSIGNED_BY_ID'];
	protected const FIELD_ALIASES = [];

	protected array $factories = [];
	protected ?Options $gridOptions = null;
	protected array $showedFieldsList = [];
	protected ?\Bitrix\Main\UI\Filter\Options $filterOptions = null;

	protected DataProvider $provider;
	protected DataProvider $ufProvider;

	public function __construct()
	{
		$filterFactory = Container::getInstance()->getFilterFactory();
		$settings = $filterFactory->getSettings(
			$this->getEntityTypeId(),
			$this->getGridId(),
		);
		$this->provider = $filterFactory->getDataProvider($settings);
		if ($this->provider instanceof FactoryOptionable)
		{
			$this->provider->setForceUseFactory(true);
		}
		$this->ufProvider = $filterFactory->getUserFieldDataProvider($settings);
	}

	public function getList(): array
	{
		$filter = $this->getPreparedFilter();
		$parameters = $this->buildParameters($filter);
		$items = $this->getPreparedItems($this->getEntityTypeId(), $parameters);

		return [
			'items' => $items,
		];
	}

	/**
	 * @return array
	 */
	protected function getPreparedFilter(): array
	{
		$filter = [];

		if (!empty($this->params['search']))
		{
			$filter['FIND'] = $this->params['search'];
		}

		$presetId = ($this->getFilterParams()['FILTER_PRESET_ID'] ?? null);
		if ($presetId)
		{
			$filterOptions = $this->getFilterOptions();
			$this->setFilterPreset($presetId, $filterOptions);

			$params = $filterOptions->setCurrentFilterPresetId($presetId)->getFilter();
			$filter = array_merge($params, $filter);
		}

		return $filter;
	}

	protected function getFilterPresets(): array
	{
		return [];
	}

	/**
	 * @param array $filter
	 * @return array
	 */
	protected function buildParameters(array $filter = []): array
	{
		$params = [
			'limit' => static::LIMIT,
			// ToDo fix it
			'offset' => $this->pageNavigation ? $this->pageNavigation->getOffset() : 0,
		];
		if (!empty($filter))
		{
			$params['filter'] = $filter;
		}
		else
		{
			$params['filter'] = $this->getFilterParams();
		}

		if (empty($params['order']))
		{
			$params['order'] = ['ID' => 'DESC'];
		}

		return $params;
	}

	/**
	 * @param $entityTypeId
	 * @param array $parameters
	 * @return array
	 */
	protected function getPreparedItems(int $entityTypeId, array $parameters = []): array
	{
		$fieldsCollection = $this->getFieldsCollection($entityTypeId);
		$fieldsMap = $this->getFieldsMap($entityTypeId);

		$displayedFields = $this->getDisplayedFields($fieldsCollection, $fieldsMap);

		$parameters['select'] = $this->getSelectFields($displayedFields);
		$items = $this->getItems($entityTypeId, $parameters);
		$this->appendRelatedEntitiesValues($items);
		$this->prepareActivityCounters($items);

		$allDisplayValues =
			(new Display($entityTypeId, $displayedFields))
				->setItems($items)
				->getAllValues();

		$entityAttributes = $this->getEntityAttributes($items, 'ID');

		$results = [];
		foreach ($items as $id => $item)
		{
			$preparedItem = $this->prepareItem(
				$item,
				[
					'displayValues' => $allDisplayValues[$id] ?? [],
					'fieldsCollection' => $fieldsCollection,
					'permissionEntityAttributes' => $entityAttributes,
				]
			);
			$results[] = $this->buildItemDto($preparedItem);
		}

		return $results;
	}

	/**
	 * @param int $entityTypeId
	 * @return Factory
	 */
	protected function getFactory(int $entityTypeId): Factory
	{
		if (empty($this->factories[$entityTypeId]))
		{
			$this->factories[$entityTypeId] = Container::getInstance()->getFactory($entityTypeId);
		}

		return $this->factories[$entityTypeId];
	}

	/**
	 * @param int $entityTypeId
	 * @param array $parameters
	 * @return array
	 */
	protected function getItems(int $entityTypeId, array $parameters): array
	{
		$requestFilter = $parameters['filter'] ?? [];

		$filter = [];
		$this->provider->prepareListFilter($filter, $requestFilter);
		$this->ufProvider->prepareListFilter($filter, $this->getFilterFieldsArray(), $requestFilter);
		$this->prepareActivityFilter($filter);
		$this->prepareEntityTypeFilter($filter);
		$parameters['filter'] = $this->provider->prepareFilterValue($filter);

		$items = $this->getFactory($entityTypeId)->getItemsFilteredByPermissions($parameters);

		return $this->getFormattedItemsResults($items);
	}

	// @todo refactoring required
	protected function prepareActivityFilter(&$filter): void
	{
		if(isset($filter['=ACTIVITY_COUNTER']))
		{
			if(is_array($filter['=ACTIVITY_COUNTER']))
			{
				$counterTypeId = \Bitrix\Crm\Counter\EntityCounterType::joinType(
					array_filter($filter['=ACTIVITY_COUNTER'], 'is_numeric')
				);
			}
			else
			{
				$counterTypeId = (int)$filter['=ACTIVITY_COUNTER'];
			}

			$counter = null;
			if($counterTypeId > 0)
			{
				$counterUserIds = [];
				if(isset($filter['=ASSIGNED_BY_ID']))
				{
					if(is_array($filter['=ASSIGNED_BY_ID']))
					{
						$counterUserIds = array_filter($filter['=ASSIGNED_BY_ID'], 'is_numeric');
					}
					elseif($filter['=ASSIGNED_BY_ID'] > 0)
					{
						$counterUserIds[] = (int)$filter['=ASSIGNED_BY_ID'];
					}
				}

				$counter = \Bitrix\Crm\Counter\EntityCounterFactory::create(
					$this->getEntityTypeId(),
					$counterTypeId,
				);
				$filter['@ID'] = new SqlExpression($counter->getEntityListSqlExpression([
						'USER_IDS' => $counterUserIds,
				]));

				unset($filter['=ASSIGNED_BY_ID'], $filter['=ACTIVITY_COUNTER']);
			}
		}
	}

	protected function prepareEntityTypeFilter(array &$filter): void
	{
		// may implemented in a child class
	}

	protected function appendRelatedEntitiesValues(array &$items): void
	{
		// may implemented in a child class
	}

	/**
	 * @param array $items
	 * @return array
	 */
	protected function getFormattedItemsResults(array $items = []): array
	{
		$results = [];
		foreach ($items as $item)
		{
			$results[$item->getId()] = $this->getItemData($item);
		}

		return $results;
	}

	/**
	 * @param Item $item
	 * @return array
	 */
	protected function getItemData(Item $item): array
	{
		return array_merge(
			$item->getData(),
			[
				'FM' => $item->getFm(),
			]
		);
	}

	/**
	 * @param int $entityTypeId
	 * @return Collection
	 */
	protected function getFieldsCollection(int $entityTypeId): Collection
	{
		return $this->getFactory($entityTypeId)->getFieldsCollection();
	}

	protected function getFieldsMap(int $entityTypeId): array
	{
		return array_flip($this->getFactory($entityTypeId)->getFieldsMap());
	}

	/**
	 * @param array $item
	 * @param array $params
	 * @return \Bitrix\CrmMobile\Kanban\Dto\Field[]
	 * @todo temporary
	 *
	 */
	protected function prepareFields(array $item = [], array $params = []): array
	{
		$fields = [];
		foreach ($params['displayValues'] as $fieldName => $fieldValue)
		{
			$field = $params['fieldsCollection']->getField($fieldName);
			if (!$field || $this->isExcludedField($field->getName()) || !isset($fieldValue['value']))
			{
				continue;
			}

			$dtoField = new \Bitrix\CrmMobile\Kanban\Dto\Field([
				'name' => $field->getName(),
				'title' => $field->getTitle(),
				'type' => $field->getType(),
				'value' => $fieldValue['value'],
				'config' => $fieldValue['config'] ?? [],
				'multiple' => $field->isMultiple(),
			]);
			$this->prepareField($dtoField);

			$fields[] = $dtoField;
		}

		return $fields;
	}

	protected function prepareBadges(array $item = [], array $params = []): array
	{
		return [];
	}

	protected function getDisplayedFields(Collection $fieldsCollection, array $fieldsMap): array
	{
		$showedFields = $this->getShowedFieldsList($fieldsCollection, $fieldsMap);

		$results = [];
		foreach ($showedFields as $displayFieldName)
		{
			$results[$displayFieldName] =
				$this
					->createField($displayFieldName, $fieldsCollection)
					->setContext(Field::MOBILE_CONTEXT); // @todo need more flexible just like in kanban
		}

		return $results;
	}

	protected function getShowedFieldsList(Collection $fieldsCollection, array $fieldsMap): array
	{
		if (empty($this->showedFieldsList))
		{
			$this->showedFieldsList = [];
			foreach (static::ALWAYS_SHOW_FIELD_NAMES as $showedFieldName)
			{
				if (
					$fieldsCollection->hasField($showedFieldName)
					&& !in_array($showedFieldName, $this->showedFieldsList, true)
				)
				{
					$this->showedFieldsList[] = $showedFieldName;
				}
			}

			$showedFieldsNames = $this->getShowedFieldsNames($fieldsCollection);
			foreach ($showedFieldsNames as $showedFieldName)
			{
				$showedFieldName = trim($showedFieldName);
				$showedFieldName = ($fieldsMap[$showedFieldName] ?? $showedFieldName);

				if (!empty($showedFieldName) && $fieldsCollection->hasField($showedFieldName))
				{
					$this->showedFieldsList[] = $showedFieldName;
				}
			}
		}

		return $this->showedFieldsList;
	}

	/**
	 * @param Collection $fieldsCollection
	 * @return array
	 */
	protected function getShowedFieldsNames(Collection $fieldsCollection): array
	{
		$options = $this->getCurrentOptions();

		if ($options['columns'] === '' || $options['columns'] === null)
		{
			$visibleFieldsNames = [];
			foreach ($fieldsCollection as $field)
			{
				$showUserFieldInList = ($field->isUserField() && $field->getUserField()['SHOW_IN_LIST'] === 'Y');
				if (
					$showUserFieldInList
					|| in_array($field->getName(), self::DEFAULT_SHOW_FIELD_NAMES)
				)
				{
					$visibleFieldsNames[] = $field->getName();
				}
			}
		}
		else
		{
			$visibleFieldsNames = explode(',', $options['columns']);
		}

		$this->addFieldAliases($visibleFieldsNames);

		return $visibleFieldsNames;
	}

	protected function addFieldAliases(array &$fieldNames): void
	{
		foreach (static::FIELD_ALIASES as $name => $alias)
		{
			$index = array_search($name, $fieldNames, true);
			if ($index !== false)
			{
				unset($fieldNames[$index]);
				$fieldNames[] = $alias;
			}
		}
	}

	/**
	 * @param array $displayedFields
	 * @return array
	 */
	protected function getSelectFields(array $displayedFields = []): array
	{
		return array_unique([
			...array_keys($displayedFields),
			...static::DEFAULT_SELECT_FIELD_NAMES,
		]);
	}

	/**
	 * @param string $fieldName
	 * @param Collection $fieldsCollection
	 * @return Field
	 * @throws FieldNotFoundException
	 */
	protected function createField(string $fieldName, Collection $fieldsCollection): Field
	{
		$field = $fieldsCollection->getField($fieldName);
		if (!$field)
		{
			throw new FieldNotFoundException('Field: ' . $fieldName . ' not found');
		}

		$fieldId = $field->getName();
		if (!empty($field->getUserField()))
		{
			return Field::createFromUserField($fieldId, $field->getUserField());
		}

		$displayField = (Field::createByType($field->getType(), $fieldId))->setTitle($field->getTitle());

		foreach ($field->getSettings() as $name => $setting)
		{
			$displayField->addDisplayParam($name, $setting);
		}

		if ($entityType = $field->getCrmStatusType())
		{
			$displayField->addDisplayParam('ENTITY_TYPE', $entityType);
		}

		if ($valueType = $field->getValueType())
		{
			$displayField->addDisplayParam('VALUE_TYPE', $valueType);
		}

		return $displayField;
	}

	/**
	 * @return array
	 */
	protected function getCurrentOptions(): array
	{
		$options = $this->getOptions();
		return ($options ? $options['views'][$options['current_view']] : []);
	}

	/**
	 * @return array|false|mixed
	 */
	protected function getOptions()
	{
		return $this->getGridOptions()->GetOptions();
	}

	/**
	 * @return Options
	 */
	protected function getGridOptions(): Options
	{
		if (!($this->gridOptions instanceof Options))
		{
			$this->gridOptions = new Options($this->getGridId(), $this->getFilterPresets());
		}

		return $this->gridOptions;
	}

	protected function getItemName(array $item): string
	{
		return $item['TITLE'];
	}

	protected function getItemDateFormatted(array $item): string
	{
		$dateFormatter = new \Bitrix\Crm\Format\Date();
		return $dateFormatter->format($item['CREATED_TIME'], true);
	}

	public function updateItemStage(int $id, int $stageId): Result
	{
		throw new Exception('UpdateItemStage not implemented');
	}

	/**
	 * @param int $id
	 * @param array $params
	 * @return Result
	 */
	public function deleteItem(int $id, array $params = []): Result
	{
		$result = new Result();

		$entity = $this->getEntityClass();
		if (!$entity->Delete($id, $params))
		{
			$result->addError(new Error($entity->LAST_ERROR));
		}

		return $result;
	}

	/**
	 * @return mixed
	 * @todo must be replace after support new api in the crm
	 */
	abstract protected function getEntityClass();

	/**
	 * @return \Bitrix\Main\UI\Filter\Options
	 */
	protected function getFilterOptions(): \Bitrix\Main\UI\Filter\Options
	{
		if (!$this->filterOptions)
		{
			$gridId = $this->getGridId();
			$this->filterOptions = new \Bitrix\Main\UI\Filter\Options($gridId, $this->getFilterPresets());
		}

		return $this->filterOptions;
	}

	/**
	 * @return array
	 */
	protected function getFilterFieldsArray(): array
	{
		$entityFilter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
			new \Bitrix\Crm\Filter\ContactSettings(['ID' => $this->getGridId(), 'flags' => 0])
		);
		return $entityFilter->getFieldArrays();
	}

	/**
	 * @param array $item
	 * @param string $type
	 * @return array|array[]
	 */
	protected function getSelfContactInfo(array $item, string $type): array
	{
		return $this->getClientInfoByType($item, $type, $this->getItemName($item));
	}

	protected function getClientInfoByType(array $item, string $type, string $title, bool $hidden = true): array
	{
		if (empty($item['FM']))
		{
			return [];
		}

		if ($type !== \CCrmOwnerType::ContactName && $type !== \CCrmOwnerType::CompanyName)
		{
			throw new ArgumentException('Unsupported contact type');
		}

		$data = [];
		foreach ($item['FM'] as $fmType => $fmItem)
		{
			$fmType = $fmItem->getTypeId();
			$complexName = $fmType . '_' . $fmItem->getValueType();
			$data[mb_strtolower($fmType)][] = [
				'value' => $fmItem->getValue(),
				'complexName' => \CCrmFieldMulti::GetEntityNameByComplex($complexName, false),
				'valueType' => $fmItem->getValueType()
			];
		}

		$type = mb_strtolower($type);
		return [
			$type => [
				array_merge($data, [
					'id' => $item['ID'],
					'title' => $title,
					'subtitle' => '',
					'type' => $type,
					'hidden' => $hidden,
					'hiddenInKanbanFields' => $hidden,
				]),
			],
		];
	}

	protected function getAssignedById(array $item): ?int
	{
		return $item['ASSIGNED_BY_ID'];
	}
}
