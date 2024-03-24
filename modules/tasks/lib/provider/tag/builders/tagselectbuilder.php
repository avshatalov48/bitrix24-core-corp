<?php

namespace Bitrix\Tasks\Provider\Tag\Builders;

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

		$this->fillWithPrimaries();

		return $this->select;
	}

	public static function getDefaultSelect(): array
	{
		return [
			'ID',
			'NAME',
			'USER_ID',
		];
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