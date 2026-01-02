<?php

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper\Trait;

use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ExternalDataItemMapper;

trait ExternalDataTrait
{
	private readonly ExternalDataItemMapper $externalDataItemMapper;

	abstract protected function getExternalDataItemMapper(): ExternalDataItemMapper;

	private function setExternalDataCollection(EntityInterface $entity, mixed $ormEntity): void
	{
		$externalDataItems = [];

		$externalData = $ormEntity->getExternalData() ?? [];
		foreach ($externalData as $externalDataItem)
		{
			$externalDataItems[] = $this->getExternalDataItemMapper()->convertFromOrm($externalDataItem);
		}

		$entity->setExternalDataCollection(new ExternalDataCollection(...$externalDataItems));
	}
}
