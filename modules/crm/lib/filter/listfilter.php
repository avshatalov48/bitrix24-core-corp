<?php

namespace Bitrix\Crm\Filter;

use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;

class ListFilter
{
	public const TYPE_STRING = 'string';
	public const TYPE_TEXT = 'text';
	public const TYPE_NUMBER = 'number';
	public const TYPE_USER = 'entity_selector';
	public const TYPE_DATE = 'date';
	public const TYPE_BOOLEAN = 'checkbox';
	public const TYPE_CRM_ENTITY = 'crm_entity';
	public const TYPE_ENTITY_SELECTOR = 'entity_selector';
	public const TYPE_LIST = 'list';
	public const TYPE_PARENT = 'parent';

	protected bool $isCrmTrackingEnabled = false;
	protected bool $isStagesEnabled = false;
	protected int $entityTypeId;
	protected array $fields = [];
	protected string $fieldStageSemantic;

	public function __construct(int $entityTypeId, array $fields, string $fieldStageSemantic = 'STAGE_SEMANTIC_ID')
	{
		$this->entityTypeId = $entityTypeId;
		$this->fields = $this->getFieldsToFilter($fields);
		$this->fieldStageSemantic = $fieldStageSemantic;

		$factory = Container::getInstance()->getFactory($entityTypeId);
		$this->isStagesEnabled = $factory->isStagesEnabled();
		$this->isCrmTrackingEnabled = $factory->isCrmTrackingEnabled();
	}

	protected function getFieldsToFilter(array $fields): array
	{
		$results = [];
		foreach ($fields as $name => $field)
		{
			// @todo maybe need to set type for fields in prepareFields method?
			if (is_array($field))
			{
				$type = $field['type'] ?? 'string';
			}
			else
			{
				$type = empty($field->getType()) ? 'string' : $field->getType();
			}

			$results[$name] = [
				'type' => $type,
			];
		}

		return $results;
	}

	/**
	 * Prepare ORM filter from data, received from the frontend filter
	 *
	 * @param array $filter
	 * @param array $requestFilter
	 */
	public function prepareListFilter(array &$filter, array $requestFilter): void
	{
		if (isset($requestFilter['FIND']) && !empty($requestFilter['FIND']))
		{
			$filter['SEARCH_CONTENT'] = $requestFilter['FIND'];
			SearchEnvironment::prepareSearchFilter($this->getEntityTypeId(), $filter, [
				'ENABLE_PHONE_DETECTION' => false,
			]);
		}

		if ($this->isCrmTrackingEnabled)
		{
			$runtime = [];
			\Bitrix\Crm\Tracking\UI\Filter::buildOrmFilter($filter, $requestFilter, $this->getEntityTypeId(), $runtime);
		}

		foreach ($this->getFieldNamesByType(static::TYPE_NUMBER) as $fieldName)
		{
			if (isset($requestFilter[$fieldName]) && $requestFilter[$fieldName] === false)
			{
				$filter[$fieldName] = $requestFilter[$fieldName];
			}
			elseif (isset($requestFilter['!' . $fieldName]) && $requestFilter['!' . $fieldName] === false)
			{
				$filter['!' . $fieldName] = $requestFilter['!' . $fieldName];
			}
			if (isset($requestFilter[$fieldName . '_from']) && $requestFilter[$fieldName . '_from'] > 0)
			{
				$filter['>=' . $fieldName] = $requestFilter[$fieldName . '_from'];
			}
			if (isset($requestFilter[$fieldName . '_to']) && $requestFilter[$fieldName . '_to'] > 0)
			{
				$filter['<=' . $fieldName] = $requestFilter[$fieldName . '_to'];
			}
			if (isset($requestFilter[$fieldName]) && $requestFilter[$fieldName] > 0)
			{
				$filter['=' . $fieldName] = $requestFilter[$fieldName];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_STRING) as $fieldName)
		{
			if (isset($requestFilter[$fieldName]) && $requestFilter[$fieldName] === false)
			{
				$filter[$fieldName] = $requestFilter[$fieldName];
			}
			elseif (isset($requestFilter['!' . $fieldName]) && $requestFilter['!' . $fieldName] === false)
			{
				$filter['!' . $fieldName] = $requestFilter['!' . $fieldName];
			}
			if (!empty($requestFilter[$fieldName]))
			{
				$filter['%' . $fieldName] = $requestFilter[$fieldName];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_USER) as $fieldName)
		{
			if (empty($requestFilter[$fieldName] ?? null)) {
				continue;
			}
			if ($fieldName === 'ASSIGNED_BY_ID' && is_array($requestFilter['ASSIGNED_BY_ID']))
			{
				if ($this->isAssignedAllUser($requestFilter['ASSIGNED_BY_ID']))
				{
					unset($requestFilter['ASSIGNED_BY_ID']);
				}
				elseif (in_array('other-users', $requestFilter['ASSIGNED_BY_ID'], true))
				{
					$filter['!ASSIGNED_BY_ID'] = Container::getInstance()->getContext()->getUserId();
					unset($requestFilter['ASSIGNED_BY_ID']);
				}
				elseif(!in_array('all-users', $requestFilter['ASSIGNED_BY_ID'], true))
				{
					$filter['=ASSIGNED_BY_ID'] = $requestFilter[$fieldName];
				}
			}
			else
			{
				$filter['=' . $fieldName] = (
				is_array($requestFilter[$fieldName])
					? $requestFilter[$fieldName]
					: (int)$requestFilter[$fieldName]
				);
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_CRM_ENTITY) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				$filter['=' . $fieldName] = is_array($requestFilter[$fieldName]) ? $requestFilter[$fieldName]
					: (int)$requestFilter[$fieldName];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_ENTITY_SELECTOR) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				$filter['=' . $fieldName] = is_array($requestFilter[$fieldName]) ? $requestFilter[$fieldName]
					: (int)$requestFilter[$fieldName];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_DATE) as $fieldName)
		{
			if (isset($requestFilter[$fieldName]) && $requestFilter[$fieldName] === false)
			{
				$filter[$fieldName] = $requestFilter[$fieldName];
			}
			elseif (isset($requestFilter['!' . $fieldName]) && $requestFilter['!' . $fieldName] === false)
			{
				$filter['!' . $fieldName] = $requestFilter['!' . $fieldName];
			}
			if (!empty($requestFilter[$fieldName . '_from']))
			{
				$filter['>=' . $fieldName] = $requestFilter[$fieldName . '_from'];
			}
			if (!empty($requestFilter[$fieldName . '_to']))
			{
				$filter['<=' . $fieldName] = $requestFilter[$fieldName . '_to'];
			}
		}

		foreach ($this->getFieldNamesByType(static::TYPE_BOOLEAN) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				$filterValue = $requestFilter[$fieldName] === 'Y';

				$filter['=' . $fieldName] = $filterValue;
			}
		}

		$fieldStageSemantic = $this->getFieldStageSemantic();
		// The `ACTIVITY_COUNTER` token is a mark for future processing, and it will be, transform by special logic
		$filtersToSpecialCalculate = ['ACTIVITY_COUNTER'];
		foreach ($this->getFieldNamesByType(static::TYPE_LIST) as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				if ($fieldName === $fieldStageSemantic && $this->isStagesEnabled)
				{
					Filter::applyStageSemanticFilter($filter, $requestFilter, $fieldStageSemantic);
				}
				else if (in_array($fieldName, $filtersToSpecialCalculate))
				{
					$filter[$fieldName] = $requestFilter[$fieldName];
				}
				else
				{
					$filter['=' . $fieldName] = $requestFilter[$fieldName];
				}
			}
		}

		$parentFields = $this->getFieldNamesByType(static::TYPE_PARENT);
		foreach ($parentFields as $fieldName)
		{
			if (!empty($requestFilter[$fieldName]))
			{
				$filter[$fieldName] = ParentFieldManager::transformEncodedFilterValueIntoInteger($fieldName,
					$requestFilter[$fieldName]);
			}
		}
	}

	protected function getFieldNamesByType(string $type): array
	{
		$separated = [];
		foreach ($this->getFields() as $fieldName => $fieldParams)
		{
			if (!empty($fieldParams['type']) && $fieldParams['type'] === $type)
			{
				$separated[] = $fieldName;
			}
		}

		return $separated;
	}

	/**
	 * @return bool
	 */
	protected function isCrmTrackingEnabled(): bool
	{
		return $this->isCrmTrackingEnabled;
	}

	/**
	 * @return int|null
	 */
	protected function getEntityTypeId(): ?int
	{
		return $this->entityTypeId;
	}

	/**
	 * @return array
	 */
	protected function getFields(): array
	{
		return $this->fields;
	}

	/**
	 * @return string
	 */
	protected function getFieldStageSemantic(): string
	{
		return $this->fieldStageSemantic;
	}

	private function isAssignedAllUser(array $assignedFilter): bool
	{
		return (
			in_array('all-users', $assignedFilter, true)
			|| (
				in_array('other-users', $assignedFilter, true)
				&& $this->isCurrentUserInFilter($assignedFilter)
			)
		);
	}

	private function isCurrentUserInFilter(array $assignedFilter): bool
	{
		return (
			Container::getInstance()->getContext()->getUserId() > 0
			&& in_array(Container::getInstance()->getContext()->getUserId(), $assignedFilter)
		);
	}
}
