<?php

declare(strict_types=1);

namespace Bitrix\Disk\Integration\Collab;

use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\ProxyType\User;
use Bitrix\Disk\RightsManager;
use Bitrix\Disk\Storage;
use Bitrix\Im\Disk\ProxyType\Im;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class CollabHandlers
{
	public static function onRetrievingUserRights(Event $event): void
	{
		$userId = (int)$event->getParameter('userId');

		$collabService = new CollabService();

		if ($collabService->isCollaberUserById($userId))
		{
			$objectId = (int)$event->getParameter('objectId');
			$object = ObjectTable::getList([
				'select' => ['ID', 'STORAGE_ID'],
				'filter' => [
					'=ID' => $objectId,
				]
			])->fetch();

			if ($object !== false)
			{
				$storage = Storage::loadById($object['STORAGE_ID']);
				if ($storage !== null)
				{
					$isPersonalFolderForUploadedFiles = false;
					if ($storage->getEntityType() === User::class && (int)$storage->getEntityId() === $userId)
					{
						$uploaded = $storage->getFolderForUploadedFiles();
						$isPersonalFolderForUploadedFiles = (int)$uploaded?->getId() === $objectId;
					}

					$isStorageForIm = $storage->getEntityType() === Im::class;

					if (!$isPersonalFolderForUploadedFiles && !$isStorageForIm && !$collabService->isCollabStorage($storage))
					{
						$rights = $event->getParameter('rights');

						$rights = array_filter($rights, static function (array $operation) {
							return $operation['NAME'] !== RightsManager::OP_ADD;
						});

						$event->addResult(new EventResult(EventResult::SUCCESS, $rights));
					}
				}
			}
		}
	}
}