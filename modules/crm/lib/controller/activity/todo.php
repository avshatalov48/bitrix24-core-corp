<?php

namespace Bitrix\Crm\Controller\Activity;

use Bitrix\Crm\Activity\Entity;
use Bitrix\Crm\Activity\TodoCreateNotification;
use Bitrix\Crm\Controller\Base;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Integration\Disk\HiddenStorage;
use Bitrix\Crm\Integration\UI\FileUploader;
use Bitrix\Crm\FileUploader\TodoActivityUploaderController;
use Bitrix\Disk\File;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

class ToDo extends Base
{
	public function getNearestAction(int $ownerTypeId, int $ownerId): ?array
	{
		$itemIdentifier = new ItemIdentifier($ownerTypeId, $ownerId);

		$todo = Entity\ToDo::loadNearest($itemIdentifier);
		if (!$todo)
		{
			return null;
		}

		return [
			'id' => $todo->getId(),
			'parentActivityId' => $todo->getParentActivityId(),
			'description' => $todo->getDescription(),
			'deadline' => $todo->getDeadline()->toString(),
			'storageElementIds' => array_map(
				'intval',
				(new HiddenStorage())->fetchFileIdsByStorageFileIds($todo->getStorageElementIds())
			),
		];
   }

	public function addAction(
		int $ownerTypeId,
		int $ownerId,
		string $deadline,
		string $description = '',
		?int $responsibleId = null,
		?int $parentActivityId = null,
		array $fileTokens = []
	): ?array
	{
		$todo = new Entity\ToDo(
			new ItemIdentifier($ownerTypeId, $ownerId)
		);

		$todo = $this->getPreparedEntity($todo, $description, $deadline, $parentActivityId, $responsibleId);
		if (!$todo)
		{
			return null;
		}

		$result = $this->saveTodo($todo);
		if (is_array($result) && !empty($fileTokens))
		{
			// if success save - add files
			$storageElementIds = $this->saveFilesToStorage($ownerTypeId, $ownerId, $fileTokens);
			if (!empty($storageElementIds))
			{
				$todo->setStorageElementIds($storageElementIds);
			}

			return $this->saveTodo($todo);
		}

		return $result;
	}

	public function updateAction(
		int $ownerTypeId,
		int $ownerId,
		string $deadline,
		int $id = null,
		string $description = '',
		?int $responsibleId = null,
		?int $parentActivityId = null,
		array $fileTokens = []
	): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id);
		if (!$todo)
		{
			return null;
		}

		if ($todo->isCompleted())
		{
			$this->addError(new Error( Loc::getMessage('CRM_ACTIVITY_TODO_UPDATE_FILES_ERROR')));

			return null;
		}

		$todo = $this->getPreparedEntity($todo, $description, $deadline, $parentActivityId, $responsibleId);
		if (!$todo)
		{
			return null;
		}

		$currentStorageElementIds = $todo->getStorageElementIds() ?? [];
		if (!empty($fileTokens) || !empty($currentStorageElementIds))
		{
			$storageElementIds = $this->saveFilesToStorage(
				$ownerTypeId,
				$ownerId,
				$fileTokens,
				$id,
				$currentStorageElementIds
			);

			$todo->setStorageElementIds($storageElementIds);
		}

		return $this->saveTodo($todo);
	}

	protected function getPreparedEntity(
		Entity\ToDo $todo,
		string $description,
		string $deadline,
		?int $parentActivityId,
		?int $responsibleId = null
	): ?Entity\ToDo
	{
		$todo->setDescription($description);

		$deadline = $this->prepareDatetime($deadline);
		if (!$deadline)
		{
			return null;
		}
		$todo->setDeadline($deadline);

		$todo->setParentActivityId($parentActivityId);

		if ($responsibleId)
		{
			$todo->setResponsibleId($responsibleId);
		}

		return $todo;
	}

	public function updateDeadlineAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		string $value
	): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id, false);
		if (!$todo)
		{
			return null;
		}

		$deadline = $this->prepareDatetime($value);
		if (!$deadline)
		{
			return null;
		}
		$todo->setDeadline($deadline);

		return $this->saveTodo($todo);
	}

	public function updateDescriptionAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		string $value
	): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id, false);
		if (!$todo)
		{
			return null;
		}

		$todo->setDescription($value);

		return $this->saveTodo($todo);
	}

	public function updateFilesAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		array $fileTokens = []
	): ?array
	{
		$todo = $this->loadEntity($ownerTypeId, $ownerId, $id);
		if (!$todo)
		{
			return null;
		}

		if ($todo->isCompleted())
		{
			$this->addError(new Error( Loc::getMessage('CRM_ACTIVITY_TODO_UPDATE_FILES_ERROR')));

			return null;
		}

		$todo->setStorageElementIds(
			$this->saveFilesToStorage(
				$ownerTypeId,
				$ownerId,
				$fileTokens,
				$id,
				$todo->getStorageElementIds() ?? []
			)
		);

		return $this->saveTodo($todo);
	}

	protected function loadEntity(int $ownerTypeId, int $ownerId, int $id): ?Entity\ToDo
	{
		$itemIdentifier = new ItemIdentifier($ownerTypeId, $ownerId);
		$todo = Entity\ToDo::load($itemIdentifier, $id);

		if (!$todo)
		{
			$this->addError(ErrorCode::getNotFoundError());

			return null;
		}

		return $todo;
	}

	public function skipEntityDetailsNotificationAction(int $entityTypeId, string $period): bool
	{
		if (!CCrmOwnerType::ResolveName($entityTypeId))
		{
			$this->addError(ErrorCode::getEntityTypeNotSupportedError($entityTypeId));
		}

		$result = (new TodoCreateNotification($entityTypeId))->skipForPeriod($period);
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result->isSuccess();
	}

	private function saveTodo(Entity\ToDo $todo): ?array
	{
		$saveResult = $todo->save();
		if ($saveResult->isSuccess())
		{
			return [
				'id' => $todo->getId(),
			];
		}

		$this->addErrors($saveResult->getErrors());

		return null;
	}

	private function saveFilesToStorage(
		int $ownerTypeId,
		int $ownerId,
		array $fileUploaderIds,
		?int $activityId = null,
		array $currentStorageElementIds = []
	): array
	{
		if (!Loader::includeModule('disk'))
		{
			$this->addError(new Error('"disk" module is required.'));

			return [];
		}

		$idsOfNewFiles = array_values(
			array_unique(
				array_filter(
					array_map(static function($item) {
						if (is_numeric($item))
						{
							return (int) $item;
						}

						if (is_string($item))
						{
							return $item;
						}
					}, $fileUploaderIds)
				)
			)
		);
		$idsNotChanged = [];
		$hiddenStorage = (new HiddenStorage())->setSecurityContextOptions([
			'entityTypeId' => $ownerTypeId,
			'entityId' => $ownerId,
		]);

		$currentFileIds = $hiddenStorage->fetchFileIdsByStorageFileIds(
			$currentStorageElementIds,
			HiddenStorage::USE_DISK_OBJ_ID_AS_KEY
		);
		if (!empty($currentFileIds))
		{
			$idsOfNewFiles = array_values(array_diff($fileUploaderIds, $currentFileIds));
			$idsToRemove = array_diff($currentFileIds, $fileUploaderIds);
			$idsNotChanged = array_keys(array_diff($currentFileIds, $idsToRemove));

			$hiddenStorage->deleteFiles(array_keys($idsToRemove));
		}

		$fileUploader = new FileUploader(
			new TodoActivityUploaderController([
				'entityTypeId' => $ownerTypeId,
				'entityId' => $ownerId,
				'activityId' => $activityId,
			])
		);
		$fileIds = $fileUploader->getPendingFiles($idsOfNewFiles);
		$hiddenStorageFiles = $hiddenStorage->addFilesToFolder($fileIds, HiddenStorage::FOLDER_CODE_ACTIVITY);
		$hiddenStorageFileIds = array_map(fn(File $file) => $file->getId(), $hiddenStorageFiles);
		$fileUploader->makePersistentFiles($idsOfNewFiles);

		return array_values(array_merge($idsNotChanged, $hiddenStorageFileIds));
	}
}
