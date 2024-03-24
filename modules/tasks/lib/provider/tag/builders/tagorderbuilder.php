<?php

namespace Bitrix\Tasks\Provider\Tag\Builders;

class TagOrderBuilder
{
	private array $order;

	public function buildOrder(array $order): array
	{
		$this->order = $order;
		foreach ($this->order as $field => $orderBy)
		{
			$orderBy = mb_strtoupper($orderBy);
			$field = mb_strtoupper($field);

			return [
				$field => $orderBy,
			];
		}

		return [];
	}
}