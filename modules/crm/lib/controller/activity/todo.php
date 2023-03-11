<?php

namespace Bitrix\Crm\Controller\Activity;

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
	public function addAction(
		int $ownerTypeId,
		int $ownerId,
		string $description = '',
		string $deadline,
		int $responsibleId = null,
		?int $parentActivityId = null,
		array $fileTokens = []
	): ?array
	{
		$todo = new \Bitrix\Crm\Activity\Entity\ToDo(
			new ItemIdentifier($ownerTypeId, $ownerId)
		);
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

	public function updateDeadlineAction(
		int $ownerTypeId,
		int $ownerId,
		int $id,
		string $value
	): ?array
	{
		$todo = \Bitrix\Crm\Activity\Entity\ToDo::load(
			new ItemIdentifier($ownerTypeId, $ownerId),
			$id
		);
		if (!$todo)
		{
			$this->addError(ErrorCode::getNotFoundError());
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
		$todo = \Bitrix\Crm\Activity\Entity\ToDo::load(
			new ItemIdentifier($ownerTypeId, $ownerId),
			$id
		);
		if (!$todo)
		{
			$this->addError(ErrorCode::getNotFoundError());
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
		$todo = \Bitrix\Crm\Activity\Entity\ToDo::load(
			new ItemIdentifier($ownerTypeId, $ownerId),
			$id
		);
		if (!$todo)
		{
			$this->addError(ErrorCode::getNotFoundError());

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

	private function saveTodo(\Bitrix\Crm\Activity\Entity\ToDo $todo): ?array
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
