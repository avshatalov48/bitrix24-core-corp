<?php

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper\Trait;

use Bitrix\Booking\Entity\Client\ClientCollection;
use Bitrix\Booking\Entity\EntityInterface;
use Bitrix\Booking\Internals\Repository\ORM\Mapper\ClientMapper;

trait ClientTrait
{
	private readonly ClientMapper $clientMapper;

	abstract protected function getClientMapper(): ClientMapper;

	private function setClientCollection(EntityInterface $entity, mixed $ormEntity): void
	{
		$clients = [];

		$ormClients = $ormEntity->getClients() ?? [];
		foreach ($ormClients as $ormClient)
		{
			$clients[] = $this->getClientMapper()->convertFromOrm($ormClient);
		}

		$entity->setClientCollection(new ClientCollection(...$clients));
	}
}
