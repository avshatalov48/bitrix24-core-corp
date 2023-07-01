<?php

namespace Bitrix\Crm\Dto;

abstract class Caster
{
	protected bool $isNullable = false;

	public function nullable(bool $isNullable = true): Caster
	{
		$this->isNullable = $isNullable;

		return $this;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	public function cast($value)
	{
		if ($this->isNullable && $value === null)
		{
			return null;
		}

		return $this->castSingleValue($value);
	}

	abstract protected function castSingleValue($value);
}
