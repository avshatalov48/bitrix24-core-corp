<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Entity\Collection;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\EntityInterface;

abstract class IdentifiableCollection extends BaseCollection
{
	public function getIds(): array
	{
		return array_keys($this->items);
	}

	public function intersect(IdentifiableCollection $other): static
	{
		if ($other::getItemClassName() !== static::getItemClassName())
		{
			return new static();
		}

		$resultItems = [];

		foreach ($this->items as $item)
		{
			if (is_null($item->getId()))
			{
				continue;
			}

			foreach ($other->items as $otherItem)
			{
				if ($item->getId() === $otherItem->getId())
				{
					$resultItems[] = $item;

					break;
				}
			}
		}

		return new static(...$resultItems);
	}

	public function add(mixed $item): void
	{
		if (!($item instanceof (EntityInterface::class)))
		{
			throw new ArgumentException("Item must be of type EntityInterface");
		}

		parent::add($item);
	}
}
