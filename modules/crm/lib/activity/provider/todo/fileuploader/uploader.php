<?php

namespace Bitrix\Crm\Activity\Provider\ToDo\FileUploader;

use Bitrix\Crm\FileUploader\TodoActivityUploaderController;
use Bitrix\Crm\Integration\Disk\HiddenStorage;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Disk\File;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;

final class Uploader
{
	private ?int $activityId = null;
	private array $currentStorageElementIds = [];
	private Mode $mode = Mode::ADD;

	public function __construct(
		private readonly array $fileUploaderIds,
		private readonly int $ownerTypeId,
		private readonly int $ownerId
	)
	{

	}

	public function saveFilesToStorage(): Result
	{
		$result = new Result();

		if (!$this->hasDisk())
		{
			$result->addError(new Error('The Disk module is required.'));

			return $result;
		}

		$idsOfNewFiles = $this->getPreparedIdsOfNewFiles();
		$idsNotChanged = [];

		$itemIdentifier = new ItemIdentifier($this->ownerTypeId, $this->ownerId);
		$currentFileIds = $this->getCurrentFileIds($itemIdentifier, $this->currentStorageElementIds);
		$hiddenStorage = $this->getHiddenStorage($itemIdentifier);

		if (!empty($currentFileIds))
		{
			$idsOfNewFiles = array_values(array_diff($this->fileUploaderIds, $currentFileIds));
			$idsToRemove = array_diff($currentFileIds, $this->fileUploaderIds);
			$idsNotChanged = array_keys(array_diff($currentFileIds, $idsToRemove));

			$hiddenStorage->deleteFiles(array_keys($idsToRemove));
		}

		$fileUploader = new \Bitrix\Crm\Integration\UI\FileUploader(
			new TodoActivityUploaderController([
				'entityTypeId' => $this->ownerTypeId,
				'entityId' => $this->ownerId,
				'activityId' => $this->activityId,
			])
		);

		if ($this->mode === Mode::ADD)
		{
			$fileIds = $fileUploader->getPendingFiles($idsOfNewFiles);
			$hiddenStorageFiles = $hiddenStorage->addFilesToFolder(
				$fileIds,
				HiddenStorage::FOLDER_CODE_ACTIVITY
			);
		}
		else
		{
			$hiddenStorageFiles = $hiddenStorage->copyFilesToFolder(
				$idsOfNewFiles,
				HiddenStorage::FOLDER_CODE_ACTIVITY
			);
		}

		$hiddenStorageFileIds = array_map(static fn(File $file) => $file->getId(), $hiddenStorageFiles);
		$fileUploader->makePersistentFiles($idsOfNewFiles);

		return $result->setData([
			'ids' => array_values(array_merge($idsNotChanged, $hiddenStorageFileIds)),
		]);
	}

	public function setMode(Mode $mode): self
	{
		$this->mode = $mode;

		return $this;
	}

	private function hasDisk(): bool
	{
		return Loader::includeModule('disk');
	}

	private function getPreparedIdsOfNewFiles(): array
	{
		return array_values(
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
					}, $this->fileUploaderIds)
				)
			)
		);
	}

	private function getCurrentFileIds(ItemIdentifier $item, array $storageFileIds): array
	{
		return $this->getHiddenStorage($item)->fetchFileIdsByStorageFileIds(
			$storageFileIds,
			HiddenStorage::USE_DISK_OBJ_ID_AS_KEY
		);
	}

	private function getHiddenStorage(ItemIdentifier $item): HiddenStorage
	{
		return (new HiddenStorage())->setSecurityContextOptions([
			'entityTypeId' => $item->getEntityTypeId(),
			'entityId' => $item->getEntityId(),
		]);
	}

	public function getActivityId(): ?int
	{
		return $this->activityId;
	}

	public function setActivityId(?int $activityId): self
	{
		$this->activityId = $activityId;

		return $this;
	}

	public function getCurrentStorageElementIds(): array
	{
		return $this->currentStorageElementIds;
	}

	public function setCurrentStorageElementIds(array $currentStorageElementIds): self
	{
		$this->currentStorageElementIds = $currentStorageElementIds;

		return $this;
	}
}
