<?php

namespace Bitrix\Tasks\Provider\Tag\Builders;

use Bitrix\Main\ORM\Query\Filter\ConditionTree;
use Bitrix\Tasks\Internals\Task\LabelTable;

class TagFilterBuilder
{
	private ConditionTree $tree;

	private array $filter;

	public function __construct()
	{
		$this->tree = new ConditionTree();
	}

	public function buildFilter(array $filter): ConditionTree
	{
		$this->filter = $filter;

		foreach ($this->filter as $key => $value)
		{
			switch ($key)
			{
				case 'ID':
				case 'TAG_ID':
					if (!is_array($value))
					{
						$value = [$value];
					}
					$value = array_map('intval', $value);
					$this->tree->whereIn($key, $value);
					break;

				case 'TASK_ID':
					if (!is_array($value))
					{
						$value = [$value];
					}
					$value = array_map('intval', $value);
					$this->tree->whereIn(LabelTable::getRelationAlias() . ".{$key}", $value);
					break;

				case 'NAME':
					$this->tree->whereLike($key, "%{$value}%");
					break;

				case 'GROUP_ID':
				case 'USER_ID':
					$this->tree->where($key, (int)$value);
					break;
			}
		}

		return $this->tree;
	}
}