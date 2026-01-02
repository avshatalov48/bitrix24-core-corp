<?php

namespace Bitrix\Booking\Internals\Repository\ORM\Mapper\Trait;

use Bitrix\Booking\Entity\EntityInterface;

trait NoteTrait
{
	private function setNote(EntityInterface $entity, mixed $ormEntity): void
	{
		$ormNote = $ormEntity->getNote();

		if ($ormNote === null)
		{
			return;
		}

		$entity->setNote($ormNote->getDescription());
	}
}
