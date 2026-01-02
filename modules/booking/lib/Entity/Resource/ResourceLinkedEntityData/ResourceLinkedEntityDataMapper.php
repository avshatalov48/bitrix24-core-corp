<?php

declare(strict_types=1);

namespace Bitrix\Booking\Entity\Resource\ResourceLinkedEntityData;

use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;

class ResourceLinkedEntityDataMapper
{
	public function mapFromArray(ResourceLinkedEntityType $type, array $props): ResourceLinkedEntityDataInterface
	{
		$class = '';
//		$class = match ($type)
//		{
//			ResourceLinkedEntityType::User => UserLinkedResourceData::class,
//		};

		return $class::mapFromArray($props);
	}
}
