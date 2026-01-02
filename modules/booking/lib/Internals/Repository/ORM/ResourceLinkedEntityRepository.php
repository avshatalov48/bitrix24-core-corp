<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Repository\ORM;

use Bitrix\Booking\Entity\Resource\Resource;
use Bitrix\Booking\Entity\Resource\ResourceLinkedEntityCollection;
use Bitrix\Booking\Internals\Model\ResourceLinkedEntityTable;
use Bitrix\Main\Web\Json;

class ResourceLinkedEntityRepository
{
	public function link(Resource $resource, ResourceLinkedEntityCollection $linkedEntityCollection): void
	{
		$data = [];

		foreach ($linkedEntityCollection as $linkedEntity)
		{
			$props = [
				'RESOURCE_ID' => $resource->getId(),
				'ENTITY_ID' => $linkedEntity->getEntityId(),
				'ENTITY_TYPE' => $linkedEntity->getEntityType()->value,
			];

			if ($linkedEntity->getData())
			{
				$props['DATA'] = Json::encode($linkedEntity->getData()->toArray());
			}

			$data[] = $props;
		}

		if (!empty($data))
		{
			ResourceLinkedEntityTable::addMulti($data, true);
		}
	}

	public function unLink(Resource $resource, ResourceLinkedEntityCollection $linkedEntityCollection): void
	{
		foreach ($linkedEntityCollection as $linkedEntity)
		{
			ResourceLinkedEntityTable::deleteByFilter([
				'=RESOURCE_ID' => $resource->getId(),
				'=ENTITY_ID' => $linkedEntity->getEntityId(),
				'=ENTITY_TYPE' => $linkedEntity->getEntityType()->value,
			]);
		}
	}
}
