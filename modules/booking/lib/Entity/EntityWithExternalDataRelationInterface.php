<?php

namespace Bitrix\Booking\Entity;

use Bitrix\Booking\Entity\ExternalData\ExternalDataCollection;

interface EntityWithExternalDataRelationInterface
{
	public function setExternalDataCollection(ExternalDataCollection $externalDataCollection): self;
	public function getExternalDataCollection(): ExternalDataCollection;
}
