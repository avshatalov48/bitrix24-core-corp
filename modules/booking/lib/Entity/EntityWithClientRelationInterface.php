<?php

namespace Bitrix\Booking\Entity;

use Bitrix\Booking\Entity\Client\ClientCollection;

interface EntityWithClientRelationInterface
{
	public function setClientCollection(ClientCollection $clientCollection): self;
	public function getClientCollection(): ClientCollection;
}
