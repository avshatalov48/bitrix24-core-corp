<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Type;


class IntegerCollection extends BaseCollection
{
	public function __construct(int ...$items)
	{
		$this->items = $items;
	}

	protected function isValid(mixed $item): bool
	{
		return is_integer($item);
	}
}

