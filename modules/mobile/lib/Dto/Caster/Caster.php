<?php

namespace Bitrix\Mobile\Dto\Caster;

abstract class Caster
{
	protected bool $isCollection = false;

	protected bool $isNullable = false;

	public function __construct(bool $isCollection = false)
	{
		$this->isCollection = $isCollection;
	}

	public function isCollection(): bool
	{
		return $this->isCollection;
	}

	public function markAsCollection(bool $isCollection = true): Caster
	{
		$this->isCollection = $isCollection;
		return $this;
	}

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
		if ($this->isCollection())
		{
			$result = [];
			if (is_array($value))
			{
				foreach ($value as $singleVal)
				{
					if ($this->isNullable && $singleVal === null)
					{
						$result[] = $singleVal;
					}
					else
					{
						$result[] = $this->castSingleValue($singleVal);
					}
				}
			}
			return $result;
		}

		if ($this->isNullable && $value === null)
		{
			return $value;
		}

		return $this->castSingleValue($value);
	}

	abstract protected function castSingleValue($value);
}
