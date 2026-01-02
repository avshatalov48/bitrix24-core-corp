<?php

namespace Bitrix\Crm\Controller;

use Bitrix\Crm\History\Entity\InvoiceStatusHistoryTable;
use Bitrix\Crm\History\InvoiceStatusHistoryEntry;
use Bitrix\Crm\History\StageHistory\AbstractStageHistory;
use Bitrix\Crm\History\StageHistory\DealStageHistory;
use Bitrix\Crm\History\StageHistory\EntityStageHistory;
use Bitrix\Crm\History\StageHistory\LeadStageHistory;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Engine\Response\DataType\Page;

class StageHistory extends Base
{
	public function listAction(
		int $entityTypeId,
		array $order = [],
		array $filter = [],
		array $select = [],
		PageNavigation $pageNavigation = null
	): ?Page
	{
		if ($entityTypeId === \CCrmOwnerType::Invoice)
		{
			return $this->legacyInvoiceList($order, $filter, $select, $pageNavigation);
		}

		$entity = $this->getEntity($entityTypeId);
		if (!$entity)
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));

			return null;
		}

		$allowedFields = $this->getAllowedFields($entityTypeId);

		$preparedOrder = $this->prepareOrder($order, $allowedFields);
		if (!$this->validateOrder($preparedOrder, $allowedFields))
		{
			return null;
		}

		if (!$this->validateFilter($filter, $allowedFields))
		{
			return null;
		}

		$preparedFilter = $this->prepareFilter($entityTypeId, $filter);
		if ($preparedFilter === null)
		{
			return null;
		}

		return new Page(
			'items',
			$this->externalizeItems(
				$entity->getListFilteredByPermissions([
					'order' => $preparedOrder,
					'filter' => $preparedFilter,
					'select' => $this->prepareSelect($select, $allowedFields),
					'offset' => $pageNavigation?->getOffset(),
					'limit' => $pageNavigation?->getLimit(),
				]),
			),
			static fn() => $entity->getItemsCountFilteredByPermissions($preparedFilter),
		);
	}

	private function getEntity(int $entityTypeId): ?AbstractStageHistory
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return null;
		}

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			return new EntityStageHistory($factory);
		}

		return match ($entityTypeId)
		{
			\CCrmOwnerType::Lead => new LeadStageHistory($factory),
			\CCrmOwnerType::Deal => new DealStageHistory($factory),
			default => null,
		};
	}

	private function getAllowedFields(int $entityTypeId): array
	{
		$fields = [
			'ID',
			'TYPE_ID',
			'OWNER_ID',
			'CREATED_TIME',
		];

		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			$fields[] = 'STATUS_SEMANTIC_ID';
			$fields[] = 'STATUS_ID';
		}
		elseif ($entityTypeId === \CCrmOwnerType::Deal || \CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			$fields[] = 'CATEGORY_ID';
			$fields[] = 'STAGE_SEMANTIC_ID';
			$fields[] = 'STAGE_ID';
		}

		return $fields;
	}

	/**
	 * Compatibility with the previous version. Case-insensitive, and don't error on invalid fields and values.
	 */
	private function prepareOrder(array $order, array $fields): array
	{
		$result = [];
		foreach ($order as $sortField => $sortOrder)
		{
			if (in_array($sortField, $fields))
			{
				$result[$sortField] =
					mb_strtoupper($sortOrder) === 'DESC'
						? 'DESC'
						: 'ASC';
			}
		}

		return $result;
	}

	private function prepareFilter(int $entityTypeId, array $filter): ?array
	{
		try
		{
			$internalized = $this->internalizeFilter($filter);
		}
		catch (ArgumentException)
		{
			return null;
		}

		if (\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			// force an owner type
			$internalized['=OWNER_TYPE_ID'] = $entityTypeId;
		}

		return $internalized;
	}

	private function internalizeFilter(array $filter): array
	{
		$sqlWhere = new \CSQLWhere();

		$result = [];
		foreach ($filter as $filterKey => $value)
		{
			$filterCondition = $sqlWhere->MakeOperation($filterKey);

			// if no operation, change to strong equality
			if ($filterCondition['OPERATION'] === 'E' && !is_numeric($filterKey) && $filterKey !== 'LOGIC')
			{
				$filterKey = '=' . $filterKey;
			}

			if (is_array($value))
			{
				$value = $this->internalizeFilter($value);
			}
			elseif ($filterCondition['FIELD'] === 'CREATED_TIME')
			{
				$value = $this->prepareDatetime((string)$value);
				if ($value === null)
				{
					// quickly unwind recursion
					throw new ArgumentException('Invalid CREATED_TIME');
				}
			}

			$result[$filterKey] = $value;
		}

		return $result;
	}

	private function prepareSelect(array $select, array $allowedFields): array
	{
		$result = array_intersect($select, $allowedFields);

		if (empty($result))
		{
			$result = $allowedFields;
		}

		if (!in_array('ID', $result))
		{
			$result[] = 'ID';
		}

		return $result;
	}

	private function externalizeItems(array $items): array
	{
		$converter = Container::getInstance()->getOrmObjectConverter();

		return array_map($converter->toArray(...), $items);
	}

	private function legacyInvoiceList(
		array $order = [],
		array $filter = [],
		array $select = [],
		PageNavigation $pageNavigation = null,
	): ?Page
	{
		static $allowedFields = [
			'ID',
			'TYPE_ID',
			'OWNER_ID',
			'CREATED_TIME',
			'STATUS_SEMANTIC_ID',
			'STATUS_ID',
		];

		$preparedOrder = $this->prepareOrder($order, $allowedFields);
		if (!$this->validateOrder($preparedOrder, $allowedFields))
		{
			return null;
		}

		if (!$this->validateFilter($filter, $allowedFields))
		{
			return null;
		}

		$preparedFilter = $this->prepareFilter(\CCrmOwnerType::Invoice, $filter);
		if ($preparedFilter === null)
		{
			return null;
		}

		$itemArrays = InvoiceStatusHistoryEntry::getListFilteredByPermissions([
			'order' => $preparedOrder,
			'filter' => $preparedFilter,
			'select' => $this->prepareSelect($select, $allowedFields),
			'offset' => $pageNavigation?->getOffset(),
			'limit' => $pageNavigation?->getLimit(),
		]);
		// be careful, wakeUp requires primary. but `prepareSelect` always adds 'ID', so we will be fine
		$collection = InvoiceStatusHistoryTable::wakeUpCollection($itemArrays);

		return new Page(
			'items',
			$this->externalizeItems($collection->getAll()),
			static fn() => InvoiceStatusHistoryEntry::getItemsCountFilteredByPermissions($preparedFilter),
		);
	}
}
