<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Internals\Model\EO_Resource;
use Bitrix\Booking\Internals\Model\EO_ResourceData;
use Bitrix\Booking\Internals\Model\ResourceDataTable;
use Bitrix\Booking\Internals\Model\ResourceTable;
use Bitrix\Main\Type\DateTime;

class ResourceDataMapper
{
	public function convertToOrm(Resource $resource): EO_ResourceData
	{
		$ormResource = $resource->getId()
			? EO_Resource::wakeUp($resource->getId())
			: ResourceTable::createObject();

		$ormResourceData = $ormResource->fillData() ?? ResourceDataTable::createObject();

		$ormResourceData
			->setResourceId($resource->getId())
			->setName($resource->getName())
			->setDescription($resource->getDescription())
			->setCreatedBy($resource->getCreatedBy())
			->setIsDeleted($resource->isDeleted())
		;

		if ($resource->getDeletedAt())
		{
			$ormResourceData->setDeletedAt(new DateTime($resource->getDeletedAt()));
		}

		return $ormResourceData;
	}
}
