<?php

namespace Bitrix\Sign\Item\B2e;

use Bitrix\Sign\Item\Collection;

/**
 * @extends Collection<KanbanCategory>
 */
final class KanbanCategoryCollection extends Collection
{
	protected function getItemClassName(): string
	{
		return KanbanCategory::class;
	}

	public function findByCode(string $code): ?KanbanCategory
	{
		if (!$code)
		{
			return null;
		}

		return $this->findByRule(static fn(KanbanCategory $item): bool => $code === $item->code);
	}

	public function findById(int $id): ?KanbanCategory
	{
		if ($id < 1)
		{
			return null;
		}

		return $this->findByRule(static fn(KanbanCategory $item): bool => $id === $item->id);
	}

	public function getDefaultCategory():?KanbanCategory
	{
		return $this->findByRule(static fn(KanbanCategory $item): bool => $item->isDefault);
	}

	public function isManyCategoriesAvailable(): bool
	{
		return $this->count() > 1;
	}
}