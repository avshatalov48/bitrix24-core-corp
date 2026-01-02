<?php

declare(strict_types=1);

namespace Bitrix\Booking\Provider\Trait;

use Bitrix\Booking\Entity\BaseEntityCollection;
use Bitrix\Booking\Entity\EntityWithExternalDataRelationInterface;
use Bitrix\Booking\Internals\Service\ExternalDataService;

trait ExternalDataTrait
{
	abstract protected function getExternalDataService(): ExternalDataService;

	public function withExternalData(BaseEntityCollection $entityCollection): self
	{
		$externalDataCollections = [];

		/** @var EntityWithExternalDataRelationInterface $entity */
		foreach ($entityCollection as $entity)
		{
			$externalDataCollections[] = $entity->getExternalDataCollection();
		}

		$this->getExternalDataService()->loadExternalData(...$externalDataCollections);

		return $this;
	}
}
