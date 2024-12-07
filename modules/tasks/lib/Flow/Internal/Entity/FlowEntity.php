<?php

namespace Bitrix\Tasks\Flow\Internal\Entity;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Flow\Internal\EO_Flow;
use ReflectionClass;
use ReflectionException;
use Stringable;

class FlowEntity extends EO_Flow implements Arrayable
{
	public function toArray(): array
	{
		$data = [];
		$values = $this->collectValues();
		foreach ($values as $key => $value)
		{
			if (is_scalar($value) || is_array($value) || $value instanceof DateTime)
			{
				$data[$key] = $value;
			}
			elseif (is_object($value))
			{
				$data[$key] = $this->convertObjectToString($value);
			}
		}

		return $data;
	}

	protected function convertObjectToString(mixed $value): string
	{
		try
		{
			$reflection = new ReflectionClass($value);
		}
		catch (ReflectionException)
		{
			return '';
		}

		if ($value instanceof Stringable)
		{
			return (string)$value;
		}

		if ($reflection->hasMethod('toString'))
		{
			return $value->toString();
		}

		return '';
	}
}