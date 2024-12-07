<?php

namespace Bitrix\Crm\Service\Communication\Channel\Property;

final class PropertiesCollection implements \Iterator, \ArrayAccess, \Countable
{
	/**
	 * @var Property[] $properties
	 */
	protected array $properties = [];

	/**
	 * @param Property[] $properties
	 */
	public function __construct(array $properties)
	{
		foreach ($properties as $property)
		{
			$this->properties[$property->getCode()] = $property;
		}
	}

	public function toArray(): array
	{
		$properties = [];
		foreach ($this->properties as $property)
		{
			$properties[] = $property->toArray();
		}

		return $properties;
	}

	public function hasProperty(string $code): bool
	{
		return ($this->getProperty($code) !== null);
	}

	public function getProperty(string $code): ?Property
	{
		return $this[$code];
	}

	public function getPropertyNameList(): array
	{
		$names = [];
		foreach ($this->properties as $property)
		{
			$names[] = $property->getCode();
		}

		return $names;
	}

	public function current(): ?Property
	{
		return current($this->properties);
	}

	public function next(): void
	{
		next($this->properties);
	}

	public function key(): string
	{
		return key($this->properties);
	}

	public function valid(): bool
	{
		return (key($this->properties) !== null);
	}

	public function rewind(): void
	{
		reset($this->properties);
	}

	public function offsetExists($offset): bool
	{
		return isset($this->properties[$offset]);
	}

	public function offsetGet($offset): ?Property
	{
		if(isset($this->properties[$offset]) && is_string($offset))
		{
			return $this->properties[$offset];
		}

		return null;
	}

	public function offsetSet($offset, $value): void
	{
		if($value instanceof Property && $value->getCode() === $offset)
		{
			$this->properties[$value->getCode()] = $value;
		}
	}

	public function offsetUnset($offset): void
	{
		unset($this->properties[$offset]);
	}

	public function count(): int
	{
		return count($this->properties);
	}
}
