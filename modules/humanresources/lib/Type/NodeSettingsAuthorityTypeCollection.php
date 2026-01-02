<?php

declare(strict_types=1);

namespace Bitrix\HumanResources\Type;

class NodeSettingsAuthorityTypeCollection extends BaseCollection
{
	public function __construct(NodeSettingsAuthorityType ...$items)
	{
		$this->items = $items;
	}

	protected function isValid(mixed $item): bool
	{
		return $item instanceof NodeSettingsAuthorityType;
	}
}