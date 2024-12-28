<?php

namespace Bitrix\Tasks\Control\Log;

class Change implements \ArrayAccess
{
	public function __construct(
		protected mixed $fromValue,
		protected mixed $toValue,
	)
	{
	}

	public function getFromValue(): mixed
	{
		return $this->fromValue;
	}

	public function getToValue(): mixed
	{
		return $this->toValue;
	}

	public function toArray(): array
	{
		return [
			'FROM_VALUE' => $this->fromValue,
			'TO_VALUE' => $this->toValue,
		];
	}

	public function offsetSet($offset, $value): void
	{
		if ($offset === 'FROM_VALUE')
		{
			$this->fromValue = $value;
		}
		elseif ($offset === 'TO_VALUE')
		{
			$this->toValue = $value;
		}
	}

	public function offsetExists($offset): bool
	{
		return ($offset === 'FROM_VALUE' || $offset === 'TO_VALUE');
	}

	public function offsetUnset($offset): void
	{
		if ($offset === 'FROM_VALUE')
		{
			$this->fromValue = null;
		}
		elseif ($offset === 'TO_VALUE')
		{
			$this->toValue = null;
		}
	}

	public function offsetGet($offset): mixed
	{
		if ($offset === 'FROM_VALUE')
		{
			return $this->fromValue;
		}

		if ($offset === 'TO_VALUE')
		{
			return $this->toValue;
		}

		return null;
	}
}
