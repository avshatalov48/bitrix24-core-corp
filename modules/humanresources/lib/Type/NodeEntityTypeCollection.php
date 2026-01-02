<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Type;

class NodeEntityTypeCollection extends BaseCollection
{
	public function __construct(NodeEntityType ...$items)
	{
		$this->items = $items;
	}

	protected function isValid(mixed $item): bool
	{
		return $item instanceof NodeEntityType;
	}
}

