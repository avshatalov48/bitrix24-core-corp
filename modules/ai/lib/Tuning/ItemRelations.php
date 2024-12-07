<?php

namespace Bitrix\AI\Tuning;

/**
 * Relations between items. Parent and childs
 */
class ItemRelations
{
	protected array $relations = [];

	public function __construct(
		private Collection $items,
	) {}

	public function addRelation(string $parentCode, array $childCodes): bool
	{
		$check = $this->checkItem($parentCode);
		foreach ($childCodes as $childCode)
		{
			$check = $check && $this->checkItem($childCode);
		}

		if (!$check)
		{
			return false;
		}

		$this->relations[$parentCode] = $childCodes;

		return true;
	}

	protected function checkItem(string $code): bool
	{
		return (bool)$this->items->get($code);
	}

	public function toArray(): array
	{
		$data = [];
		foreach ($this->relations as $parent => $children)
		{
			$data[] = [
				'parent' => $parent,
				'children' => $children,
			];
		}

		return $data;
	}
}
