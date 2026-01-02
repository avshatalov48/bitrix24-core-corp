<?php

namespace Bitrix\Booking\Internals\Repository\ORM\Trait;

use Bitrix\Booking\Internals\Exception\Note\CreateNoteException;
use Bitrix\Booking\Internals\Exception\Note\RemoveNoteException;
use Bitrix\Booking\Internals\Model\Enum\EntityType;
use Bitrix\Booking\Internals\Model\NotesTable;

trait NoteTrait
{
	private function handleNote(
		mixed $ormEntity,
		string|null $noteDescription,
		int $entityId,
		EntityType $entityType
	): void
	{
		$note = $ormEntity->fillNote() ?? NotesTable::createObject();
		if (empty($noteDescription) && $note->getId())
		{
			$noteDeleteResult = $note->delete();
			if (!$noteDeleteResult->isSuccess())
			{
				throw new RemoveNoteException($noteDeleteResult->getErrors()[0]->getMessage());
			}

			return;
		}

		if ($noteDescription === null)
		{
			return;
		}

		$note->setDescription($noteDescription);
		$note->setEntityType($entityType->value);
		$note->setEntityId($entityId);
		$noteSaveResult = $note->save();

		if (!$noteSaveResult->isSuccess())
		{
			throw new CreateNoteException($noteSaveResult->getErrors()[0]->getMessage());
		}
	}
}
