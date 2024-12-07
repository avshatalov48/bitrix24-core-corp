<?php

namespace Bitrix\Tasks\Flow\Control\Mapper;

use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\AbstractCommand;
use Bitrix\Tasks\Flow\Internal\Entity\FlowEntity;
use ReflectionClass;

class FlowCommandMapper
{
	public function map(AbstractCommand $command): FlowEntity
	{
		$entity = new FlowEntity(false);
		$data = $this->toArray($command);
		foreach ($data as $key => $value)
		{
			if (is_scalar($value) || $value instanceof DateTime)
			{
				$setter = 'set' . $key;
				try
				{
					$entity->$setter($value);
				}
				catch (\Exception $e) {}
			}
		}

		return $entity;
	}

	public function toArray(AbstractCommand $dto): array
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