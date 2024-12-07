<?php

namespace Bitrix\Sign\Serializer;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Exception\SignException;
use ReflectionClass;

class ItemPropertyJsonSerializer implements Contract\Serializer
{
	public function serialize(Contract\Item $item): array
	{
		$result = [];
		if ($item instanceof Contract\ItemCollection)
		{
			foreach ($item->toArray() as $value)
			{
				if ($value instanceof Contract\Item)
				{
					$result[] = $this->serialize($value);
				}
				if (is_string($value))
				{
					$result[] = $value;
				}
			}

			return $result;
		}

		foreach ((new ReflectionClass($item))->getProperties() as $property)
		{
			//$property->setAccessible(true);
			$value = $property->getValue($item);
			$result[$property->getName()] = ($value instanceof Contract\Item)
				? $this->serialize($value)
				: $value
			;
		}
		return $result;
	}

	public function deserialize(array $data, string|Contract\Item|Contract\ItemCollection $item): Contract\Item|Contract\ItemCollection
	{
		if (is_string($item) && is_subclass_of($item, Contract\ItemCollection::class))
		{
			$item = new $item;
		}
		if ($item instanceof Contract\ItemCollection)
		{
			if (!method_exists($item, 'addItem'))
			{
				return $item;
			}

			$reflectionClass = new ReflectionClass($item);
			$method = $reflectionClass->getMethod('addItem');
			$parameter = $method->getParameters()[0] ?? null;
			if (!$parameter)
			{
				return $item;
			}

			$type = $parameter->getType();
			if (!is_subclass_of($type->getName(), Contract\Item::class))
			{
				return $item;
			}
			foreach($data as $value)
			{
				$item->addItem($this->deserialize($value, $type->getName()));
			}

			return $item;
		}

		if (is_string($item))
		{
			if (!is_subclass_of($item, Contract\Item::class))
			{
				throw new SignException('Wrong parent class of item for deserialization.');
			}

			$arguments = [];
			$reflectionClass = new ReflectionClass($item);
			foreach ($reflectionClass->getProperties() as $property)
			{
				$name = $property->getName();
				$value = $data[$name] ?? null;

				$type = $property->getType();
				$typeName = $type->getName();
				if (!$type->isBuiltin())
				{
					$value = $this->deserialize($value, $typeName);
				}

				$arguments[$name] = $value;
			}

			return $reflectionClass->newInstance(...$arguments);
		}

		foreach ((new ReflectionClass($item))->getProperties() as $property)
		{
			$name = $property->getName();
			$value = $property->getValue($item);
			if ($value instanceof Contract\Item)
			{
				$value = $this->deserialize($data[$name], $value);
			}
			else
			{
				$value = $data[$name] ?? null;
			}

			$property->setValue($item, $value);
		}

		return $item;
	}
}