<?php

namespace Bitrix\Sign\Item\Hr\EntitySelector;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Type\Hr\EntitySelector\EntityType;

final class Entity implements Contract\Item
{
	public function __construct(
		public readonly int $entityId,
		public readonly EntityType $entityType,
	)
	{
	}

	public static function createFromStrings(string $entityId, string $entityType): Entity
	{
		$type = EntityType::fromEntityIdAndType($entityId, $entityType);
		return match ($type) {
			EntityType::FlatDepartment => new Entity((int)substr($entityId, 0, -2), $type),
			default => new Entity((int)$entityId, $type),
		};
	}
}
