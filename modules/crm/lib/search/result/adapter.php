<?php

namespace Bitrix\Crm\Search\Result;

use Bitrix\Crm\Category\Entity\Category;
use Bitrix\Crm\Search\Result;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Search\ResultItem;
use Bitrix\Main\Web\Uri;

abstract class Adapter
{
	/** @var Category|null */
	protected ?Category $category = null;
	protected array $categoryLabels = [];

	/**
	 * Convert \Bitrix\Crm\Search\Result to array of \Bitrix\Main\Search\ResultItem
	 *
	 * @param Result $result
	 * @return ResultItem[]
	 */
	public function adapt(Result $result): array
	{
		$adaptedResult = [];

		$entityIds = $result->getIds();
		if (empty($entityIds))
		{
			return $adaptedResult;
		}
		$items = $this->loadItemsByIds($entityIds);

		$entityTypeId = $this->getEntityTypeId();
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
		foreach ($items as $item)
		{
			$entityId = $this->prepareEntityId($item);
			$resultItem = new ResultItem(
				$this->prepareTitle($item),
				new Uri(
					\CCrmOwnerType::GetEntityShowPath(
						$entityTypeId,
						$entityId,
						false
					)
				)
			);
			$resultItem->setModule('crm');
			$resultItem->setType($entityTypeName);
			$resultItem->setId($entityId);
			$resultItem->setSubTitle($this->prepareSubTitle($item));
			$resultItem->setAttributes($this->prepareAttributes($item));

			$adaptedResult[$entityId] = $resultItem;
		}

		if ($this->areMultifieldsSupported())
		{
			$this->addMultifieldsAttributes($adaptedResult);
		}
		$adaptedResult = array_values($adaptedResult);

		return $this->sortResultByPriorityAndTitle($adaptedResult, $result->getPrioritizedIds());
	}

	protected function addMultifieldsAttributes(array $adaptedResult): void
	{
		$entityIds = [];
		/** @var ResultItem $resultItem */
		foreach ($adaptedResult as $resultItem)
		{
			$entityIds[] = $resultItem->getId();
		}
		if (empty($entityIds))
		{
			return;
		}

		$multiFieldsList = \CCrmFieldMulti::GetListEx(
			[],
			[
				'=ENTITY_ID' => \CCrmOwnerType::ResolveName($this->getEntityTypeId()),
				'@ELEMENT_ID' => $entityIds,
				'@TYPE_ID' => ['PHONE', 'EMAIL'],
			]
		);

		$attributes = [];
		while ($multiFields = $multiFieldsList->Fetch())
		{
			if (!isset($adaptedResult[$multiFields['ELEMENT_ID']]))
			{
				continue;
			}

			if (!isset($attributes[$multiFields['ELEMENT_ID']]))
			{
				$attributes[$multiFields['ELEMENT_ID']] = [];
			}

			$typeId = mb_strtolower($multiFields['TYPE_ID']);
			if (!isset($attributes[$multiFields['ELEMENT_ID']][$typeId]))
			{
				$attributes[$multiFields['ELEMENT_ID']][$typeId] = [];
			}

			$attributes[$multiFields['ELEMENT_ID']][$typeId][] = [
				'type' => $multiFields['VALUE_TYPE'],
				'value' => $multiFields['VALUE'],
			];
		}

		foreach ($attributes as $entityId => $data)
		{
			if (!isset($adaptedResult[$entityId]))
			{
				continue;
			}

			foreach ($data as $typeId => $items)
			{
				$adaptedResult[$entityId]->setAttribute($typeId, $items);
			}
		}
	}

	protected function sortResultByPriorityAndTitle(array $adaptedResult, array $prioritizedIds): array
	{
		$sortedResult = [];
		foreach ($prioritizedIds as $priority => $ids)
		{
			$samePriorityItems = [];
			foreach ($adaptedResult as $result)
			{
				if (in_array($result['id'], $ids))
				{
					$samePriorityItems[] = $result;
				}
			}
			Collection::sortByColumn(
				$samePriorityItems,
				['title' => SORT_ASC]
			);
			$sortedResult = array_merge($sortedResult, $samePriorityItems);
		}

		return $sortedResult;
	}

	protected function prepareEntityId(array $item)
	{
		return (int)$item['ID'];
	}

	abstract protected function loadItemsByIds(array $ids): array;

	abstract protected function getEntityTypeId(): int;

	abstract protected function prepareTitle(array $item): string;

	abstract protected function prepareSubTitle(array $item): string;

	abstract protected function areMultifieldsSupported(): bool;

	protected function prepareAttributes(array $item): array
	{
		return [];
	}

	/**
	 * @param Category|null $category
	 * @return Adapter
	 */
	public function setCategory(?Category $category): Adapter
	{
		$this->category = $category;

		return $this;
	}

	public function addCategoryLabel(int $categoryId, string $label): self
	{
		$this->categoryLabels[$categoryId] = $label;

		return $this;
	}

	protected function addCategoryLabelToSubtitle(int $categoryId, array &$subtitles): void
	{
		if (isset($this->categoryLabels[$categoryId]))
		{
			$subtitles[] = $this->categoryLabels[$categoryId];
		}
	}
}
