<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Model\ResourceLinkedEntityData;

use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;

class ResourceLinkedEntityDataMapper
{
	private static function getDataClassByType(ResourceLinkedEntityType $resourceLinkedEntityType) : string|null
	{
		return match ($resourceLinkedEntityType)
		{
			ResourceLinkedEntityType::Calendar => CalendarData::class,
			default => null,
		};
	}

	public static function mapFromArray(
		ResourceLinkedEntityType $resourceLinkedEntityType,
		array $params,
	): ResourceLinkedEntityDataInterface
	{
		/** @var ResourceLinkedEntityDataInterface $className */
		$className = self::getDataClassByType($resourceLinkedEntityType);

		return $className::mapFromArray($params);
	}
}
