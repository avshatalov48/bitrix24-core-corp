<?php

namespace Bitrix\Tasks\Provider\Tag\Builders;

use Bitrix\Tasks\Provider\TaskQueryInterface;

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
			if (!in_array($field, TagSelectBuilder::getWhiteList(), true))
			{
				return [];
			}

			return [
				$field => in_array($orderBy, static::getWhiteList(), true) ? $orderBy : TaskQueryInterface::SORT_ASC,
			];
		}

		return [];
	}

	public static function getWhiteList(): array
	{
		return [TaskQueryInterface::SORT_ASC, TaskQueryInterface::SORT_DESC];
	}
}