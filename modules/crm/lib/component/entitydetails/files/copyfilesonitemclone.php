<?php

namespace Bitrix\Crm\Component\EntityDetails\Files;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\MultiValueStoreService;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\FileUploader;
use Bitrix\Crm\Traits\Singleton;

/**
 * When copying entities, it becomes necessary, in custom fields of the file type, not only to copy the file ID,
 * but also the file itself, so that deletion from the new entity does not affect the old one.
 * This class provides functionality to manage the lifecycle of this process.
 */
class CopyFilesOnItemClone
{
	use Singleton;

	public const CLEAN_FILE_QUEUE_KEY = 'clean_copy_file';

	private FileUploader $fileUploader;

	private MultiValueStoreService $multiValueStoreService;

	public function __construct()
	{
		$this->fileUploader = Container::getInstance()->getFileUploader();
		$this->multiValueStoreService = MultiValueStoreService::getInstance();
	}

	public function execute(Item &$item, Factory $factory): void
	{
		foreach ($this->iterateOverUFFileFields($item, $factory) as [$field, $value])
		{
			if ($field->isMultiple() && is_array($value))
			{
				$this->cloneMultipleFileField($item, $field, $value);
			}
			elseif(is_numeric($value))
			{
				$this->cloneFileField($item, $field, $value);
			}
		}
	}

	private function iterateOverUFFileFields(Item &$item, Factory $factory): \Generator
	{
		foreach ($item->getData() as $fieldName => $value)
		{
			$field = $factory->getFieldsCollection()->getField($fieldName);

			if (!$field)
			{
				continue;
			}

			if (!$field->isUserField() || !$field->isFileUserField())
			{
				continue;
			}

			yield [$field, $value];
		}
	}

	private function cloneMultipleFileField(Item $item, Field $field, ?array $value):void
	{
		if (empty($value))
		{
			return;
		}

		$newFileIds = [];
		foreach ($value as $singleValue)
		{
			$newFileId = \CFile::CloneFile($singleValue);
			if ($newFileId)
			{
				$newFileIds[] = $newFileId;
				$this->fileUploader->registerFileId($field, $newFileId);

				$this->addFileToNotUsedCleanQueue($newFileId);
			}
		}

		$item->set($field->getName(), $newFileIds);
	}

	private function cloneFileField(Item $item, Field $field, ?int $value): void
	{
		if (!$value)
		{
			return;
		}

		$newFileId = \CFile::CloneFile($value);

		if ($newFileId)
		{
			$this->fileUploader->registerFileId($field, $newFileId);

			$item->set($field->getName(), $newFileId);

			$this->addFileToNotUsedCleanQueue($newFileId);
		}
	}

	/**
	 * Add fileId to the cleanup queue. If the entity copy operation is completed successfully, then the file ID must be
	 * removed from this queue, otherwise the copy of the file will be deleted.
	 * @see \Bitrix\Crm\Agent\Files\CleanUnusedFilesAfterCopy
	 * @param int $fileId
	 * @return void
	 */
	private function addFileToNotUsedCleanQueue(int $fileId): void
	{
		$this->multiValueStoreService->add(self::CLEAN_FILE_QUEUE_KEY, $fileId);
	}

	/**
	 * Remove file from cleanup queue.
	 * @param int $fileId
	 * @see self::addFileToNotUsedCleanQueue
	 */
	public static function removeFileFromNotUsedCleanQueue(int $fileId): void
	{
		MultiValueStoreService::getInstance()->delete(self::CLEAN_FILE_QUEUE_KEY, $fileId);
	}
}