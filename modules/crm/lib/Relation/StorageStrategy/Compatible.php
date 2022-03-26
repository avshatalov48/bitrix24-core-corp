<?php

namespace Bitrix\Crm\Relation\StorageStrategy;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Compatible extends \Bitrix\Crm\Relation\StorageStrategy
{
	/** @var \CCrmDeal|\CCrmLead|\CCrmInvoice|\CCrmCompany|\CCrmContact|string */
	protected $compatibleChildEntityClass;
	/** @var string */
	protected $parentIdFieldName;

	/**
	 * Compatible constructor.
	 *
	 * @param string $compatibleChildEntityClass
	 * @param string $parentIdFieldName
	 */
	public function __construct(string $compatibleChildEntityClass, string $parentIdFieldName)
	{
		$this->compatibleChildEntityClass = $compatibleChildEntityClass;
		$this->parentIdFieldName = $parentIdFieldName;
	}

	/**
	 * @inheritDoc
	 */
	public function getParentElements(ItemIdentifier $child, int $parentEntityTypeId): array
	{
		$data = $this->getList([
			'select' => [$this->parentIdFieldName],
			'filter' => [
				'ID' => $child->getEntityId(),
				'>' . $this->parentIdFieldName => 0,
			]
		]);

		if (empty($data))
		{
			return [];
		}

		return [new ItemIdentifier($parentEntityTypeId, (int)$data[0][$this->parentIdFieldName])];
	}

	/**
	 * @inheritDoc
	 */
	public function getChildElements(ItemIdentifier $parent, int $childEntityTypeId): array
	{
		$data = $this->getList([
			'select' => ['ID'],
			'filter' => [
				$this->parentIdFieldName => $parent->getEntityId(),
			],
		]);

		$children = [];
		foreach ($data as $row)
		{
			$children[] = new ItemIdentifier($childEntityTypeId, (int)$row['ID']);
		}

		return $children;
	}

	/**
	 * @inheritDoc
	 */
	public function areItemsBound(ItemIdentifier $parent, ItemIdentifier $child): bool
	{
		$data = $this->getList([
			'filter' => [
				'ID' => $child->getEntityId(),
				$this->parentIdFieldName => $parent->getEntityId(),
			],
		]);

		return !empty($data);
	}

	/**
	 * @param array $params
	 *
	 * @return array[]
	 */
	protected function getList(array $params): array
	{
		$select = $params['select'] ?? ['ID'];
		$filter = $params['filter'] ?? [];

		$filter['CHECK_PERMISSIONS'] = 'N';

		$callArguments = [
			'$arOrder' => [],
			'$arFilter' => $filter,
			'$arGroupBy' => false,
			'$arNavStartParams' => false,
			'$arSelectFields' => $select
		];

		if (is_callable([$this->compatibleChildEntityClass, 'GetListEx']))
		{
			$dbResult = $this->compatibleChildEntityClass::GetListEx(...array_values($callArguments));
		}
		else
		{
			$dbResult = $this->compatibleChildEntityClass::GetList(...array_values($callArguments));
		}

		if (!is_object($dbResult))
		{
			return [];
		}

		$result = [];
		while ($row = $dbResult->Fetch())
		{
			$result[] = $row;
		}

		return $result;
	}

	/**
	 * @inheritDoc
	 */
	protected function createBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		$toExcludeFromTimeline = [$parent];

		$previousParentId = $this->getPreviousParentId($child);
		if ($previousParentId > 0)
		{
			$toExcludeFromTimeline[] = new ItemIdentifier($parent->getEntityTypeId(), $previousParentId);
		}

		return $this->editBinding($child, $parent->getEntityId(), $toExcludeFromTimeline);
	}

	protected function getPreviousParentId(ItemIdentifier $child): int
	{
		$childEntry = $this->getList([
			'select' => [$this->parentIdFieldName],
			'filter' => [
				'ID' => $child->getEntityId(),
			],
		]);

		return (int)($childEntry[$this->parentIdFieldName] ?? 0);
	}

	/**
	 * @inheritDoc
	 */
	protected function deleteBinding(ItemIdentifier $parent, ItemIdentifier $child): Result
	{
		return $this->editBinding($child, 0, [$parent]);
	}

	/**
	 * @param ItemIdentifier $child
	 * @param int $value
	 * @param ItemIdentifier[] $toExcludeFromTimeline
	 * @return Result
	 */
	protected function editBinding(ItemIdentifier $child, int $value, array $toExcludeFromTimeline): Result
	{
		$result = new Result();

		if (!$this->compatibleChildEntityClass::Exists($child->getEntityId()))
		{
			return $result->addError(new Error('The child item does not exist: ' . $child));
		}

		/** @var \CCrmDeal|\CCrmLead|\CCrmInvoice|\CCrmCompany|\CCrmContact $entity */
		$entity = new $this->compatibleChildEntityClass(false);

		$fields = [
			$this->parentIdFieldName => $value,
		];
		$options = [
			'EXCLUDE_FROM_RELATION_REGISTRATION' => $toExcludeFromTimeline,
		];

		//methods have different signature
		if ($entity instanceof \CCrmInvoice)
		{
			$isSuccess = $entity->Update($child->getEntityId(), $fields, $options);
		}
		else
		{
			$isSuccess = $entity->Update($child->getEntityId(), $fields, true, $options);
		}

		if (!$isSuccess)
		{
			$result->addError(new Error($entity->LAST_ERROR));
		}

		return $result;
	}

	protected function replaceBindings(ItemIdentifier $fromItem, ItemIdentifier $toItem): Result
	{
		if (method_exists($this->compatibleChildEntityClass, 'Rebind'))
		{
			$this->compatibleChildEntityClass::Rebind(
				$toItem->getEntityTypeId(),
				$fromItem->getEntityId(),
				$toItem->getEntityId()
			);
		}

		return new Result();
	}
}
