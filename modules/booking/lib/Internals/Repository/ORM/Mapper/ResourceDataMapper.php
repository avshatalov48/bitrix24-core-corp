<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper;

use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Internals\Model\EO_Resource;
use Bitrix\Booking\Internals\Model\EO_ResourceData;
use Bitrix\Booking\Internals\Model\ResourceDataTable;
use Bitrix\Booking\Internals\Model\ResourceTable;

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
		;

		return $ormResourceData;
	}
}
