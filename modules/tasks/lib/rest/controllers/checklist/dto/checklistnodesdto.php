<?php

namespace Bitrix\Tasks\Rest\Controllers\Checklist\Dto;

class CheckListNodesDto
{
	public readonly array $nodes;

	public static function createFromArray(array $items): static
	{
		foreach ($items as $id => $item)
		{
			$item['ID'] = ((int)($item['ID'] ?? null) === 0 ? null : (int)$item['ID']);

			$item['IS_COMPLETE'] = (
				($item['IS_COMPLETE'] === true)
				|| ((int) $item['IS_COMPLETE'] > 0)
			);
			$item['IS_IMPORTANT'] = (
				($item['IS_IMPORTANT'] === true)
				|| ((int) $item['IS_IMPORTANT'] > 0)
			);

			$items[$item['NODE_ID']] = $item;

			unset($items[$id]);
		}

		$instance = new static();
		$instance->nodes = $items;

		return $instance;
	}
}