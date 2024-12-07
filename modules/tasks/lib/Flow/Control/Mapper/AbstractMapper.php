<?php

namespace Bitrix\Tasks\Flow\Control\Mapper;

use Bitrix\Tasks\Flow\Control\Dto\FlowDto;
use ReflectionClass;

abstract class AbstractMapper
{
	abstract public function map(FlowDto $flowDto);

	public function toArray(FlowDto $dto): array
	{
		$reflection = new ReflectionClass($dto);
		$properties = $reflection->getProperties();
		$map = [];
		foreach ($properties as $property)
		{
			$name = $property->getName();

			if (!isset($dto->{$name}))
			{
				continue;
			}

			$map[ucfirst($name)] = $dto->{$name};
		}

		return $map;
	}
}