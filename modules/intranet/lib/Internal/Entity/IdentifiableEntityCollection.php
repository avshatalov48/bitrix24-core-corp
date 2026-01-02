<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity;

use Bitrix\Main\Entity\EntityCollection;
use Bitrix\Main\Entity\EntityInterface;

abstract class IdentifiableEntityCollection extends EntityCollection
{
	public function findById(mixed $id): ?EntityInterface
	{
		return $this->find(fn (EntityInterface $item) => $item->getId() === $id);
	}

	public function removeItem(EntityInterface $item): void
	{
		unset($this->items[array_search($item, $this->items)]);
	}
}
