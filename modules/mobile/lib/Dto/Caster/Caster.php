<?php

namespace Bitrix\Mobile\Dto\Caster;

abstract class Caster
{
	/** @var bool */
	protected $isCollection = false;

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
					$result[] = $this->castSingleValue($singleVal);
				}
			}
			return $result;
		}

		return $this->castSingleValue($value);
	}

	abstract protected function castSingleValue($value);
}
