<?php

declare(strict_types=1);

namespace Bitrix\Disk\Integration\Collab;

use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\ProxyType\User;
use Bitrix\Disk\RightsManager;
use Bitrix\Disk\Sharing;
use Bitrix\Disk\SpecificFolder;
use Bitrix\Disk\Storage;
use Bitrix\Im\Disk\ProxyType\Im;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Socialnetwork\Collab\Provider\CollabProvider;

class CollabHandlers
{

	private CollabService $collabService;
	private CollabProvider $collabProvider;
	private int $userId;
	private bool $isCollaber;

	private function __construct(private readonly Event $event)
	{
		$this->userId = (int)$event->getParameter('userId');
		$this->collabService = new CollabService();
		$this->collabProvider = CollabProvider::getInstance();
		$this->isCollaber = $this->collabService->isCollaberUserById($this->userId);
	}

	public static function onRetrievingUserRights(Event $event): void
	{
		(new static($event))->handleRetrievingUserRightsEvent();
	}

	private function handleRetrievingUserRightsEvent(): void
	{
		if ($this->isCollaber)
		{
			$objectId = (int)$this->event->getParameter('objectId');
			$rights = $this->event->getParameter('rights');

			$object = ObjectTable::getList([
				'select' => ['ID', 'STORAGE_ID', 'CODE'],
				'filter' => [
					'=ID' => $objectId,
				]
			])->fetch();

			if ($object !== false)
			{
				$storage = Storage::loadById($object['STORAGE_ID']);
				if ($storage !== null)
				{
					$rights = $this->getNewRightsForCollaber((string)$object['CODE'], $storage, $rights);
					$this->event->addResult(new EventResult(EventResult::SUCCESS, $rights));
				}
			}
		}
	}

	public static function onPreloadUserRights(Event $event): void
	{
		(new static($event))->handlePreloadUserRightsEvent();
	}

	private function handlePreloadUserRightsEvent(): void
	{
		if ($this->isCollaber)
		{
			$rightsByObjectId = $this->event->getParameter('rightsByObjectId');

			$objectIds = array_keys($rightsByObjectId);
			$objects = ObjectTable::getList([
				'select' => ['ID', 'STORAGE_ID', 'CODE'],
				'filter' => [
					'=ID' => $objectIds,
				],
			])->fetchAll();

			if (!empty($objects))
			{
				$storageIds = array_column($objects, 'STORAGE_ID');
				$loadedStorages = Storage::loadBatchById($storageIds);
				$storages = [];
				foreach ($loadedStorages as $storage)
				{
					$storages[$storage->getId()] = $storage;
				}

				foreach ($objects as ['ID' => $objectId, 'STORAGE_ID' => $storageId, 'CODE' => $objectCode])
				{
					if (!isset($storages[$storageId]))
					{
						continue;
					}

					$storage = $storages[$storageId];

					$rights = $this->getNewRightsForCollaber((string)$objectCode, $storage, $rightsByObjectId[$objectId]);

					$rightsByObjectId[$objectId] = $rights;
				}

				$this->event->addResult(new EventResult(EventResult::SUCCESS, $rightsByObjectId));
			}
		}
	}

	private function getNewRightsForCollaber(string $objectCode, Storage $storage, array $rights): array
	{
		$isStorageForIm = $storage->getEntityType() === Im::class;

		if (!$isStorageForIm && !$this->isSpecialFolder($objectCode, $storage) && !$this->collabService->isCollabStorage($storage))
		{
			$rights = array_filter($rights, function (array $operation) {
				[$sharingType, $entityId] = Sharing::parseEntityValue($operation['ACCESS_CODE']);
				if ($sharingType === Sharing::TYPE_TO_GROUP && $this->collabProvider->isCollab((int)$entityId))
				{
					return true;
				}

				return $operation['NAME'] !== RightsManager::OP_ADD;
			});
		}

		return $rights;
	}

	private function isSpecialFolder(string $objectCode, Storage $storage): bool
	{
		if ($storage->getEntityType() === User::class && (int)$storage->getEntityId() === $this->userId)
		{
			$specialFolderCodes = [
				SpecificFolder::CODE_FOR_UPLOADED_FILES,
				SpecificFolder::CODE_FOR_CREATED_FILES,
				SpecificFolder::CODE_FOR_SAVED_FILES,
				SpecificFolder::CODE_FOR_RECORDED_FILES,
			];

			return in_array($objectCode, $specialFolderCodes, true);
		}

		return false;
	}
}