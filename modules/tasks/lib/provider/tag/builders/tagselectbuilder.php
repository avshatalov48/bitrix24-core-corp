<?php

namespace Bitrix\Tasks\Provider\Tag\Builders;

use Bitrix\Tasks\Internals\Task\LabelTable;

class TagSelectBuilder
{
	private array $select;

	public function buildSelect(array $select): array
	{
		$this->select = $select;
		if (empty($this->select))
		{
			$this->select = static::getDefaultSelect();
		}

		$this->fillWithPrimaries()->replaceTaskAlias();

		return array_filter($this->select, static fn (string $field): string => in_array($field, static::getWhiteList(), true));
	}

	public static function getDefaultSelect(): array
	{
		return [
			'ID',
			'NAME',
			'USER_ID',
			LabelTable::getRelationAlias() . '.TASK_ID',
			LabelTable::getRelationAlias() . '.ID',
		];
	}

	public static function getWhiteList(): array
	{
		return [
			'ID',
			'USER_ID',
			'GROUP_ID',
			'NAME',
			LabelTable::getRelationAlias() . '.TASK_ID',
			LabelTable::getRelationAlias() . '.ID',
		];
	}

	private function replaceTaskAlias(): static
	{
		if (in_array('TASK_ID', $this->select, true))
		{
			unset($this->select['TASK_ID']);
			$this->select[] = LabelTable::getRelationAlias() . '.TASK_ID';
			$this->select[] = LabelTable::getRelationAlias() . '.ID';
		}

		return $this;
	}

	private function fillWithPrimaries(): static
	{
		if (!in_array('ID', $this->select, true))
		{
			$this->select[] = 'ID';
		}

		return $this;
	}
}