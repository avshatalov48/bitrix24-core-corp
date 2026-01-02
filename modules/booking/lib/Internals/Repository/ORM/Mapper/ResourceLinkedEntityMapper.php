<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Entity\Resource\ResourceLinkedEntity;
use Bitrix\Booking\Internals\Model\Enum\ResourceLinkedEntityType;
use Bitrix\Booking\Internals\Model\EO_ResourceLinkedEntity;
use Bitrix\Booking\Internals\Model\ResourceLinkedEntityData\ResourceLinkedEntityDataMapper;
use Bitrix\Main\Web\Json;

class ResourceLinkedEntityMapper
{
	public function convertFromOrm(EO_ResourceLinkedEntity $ormResourceLinkedEntity): ResourceLinkedEntity
	{
		$linkedEntity = (new ResourceLinkedEntity())
			->setId($ormResourceLinkedEntity->getId())
			->setEntityId($ormResourceLinkedEntity->getEntityId())
			->setCreatedAt($ormResourceLinkedEntity->getCreatedAt()->getTimestamp())
		;

		$type = ResourceLinkedEntityType::from($ormResourceLinkedEntity->getEntityType());
		$linkedEntity->setEntityType($type);

		$data = $ormResourceLinkedEntity->getData();
		if (!$data)
		{
			return $linkedEntity;
		}

		return $linkedEntity->setData(ResourceLinkedEntityDataMapper::mapFromArray($type, Json::decode($data)));
	}
}
