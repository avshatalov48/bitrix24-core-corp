<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Field;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\UI\FileInputUtility;

class FileUploader
{
	protected $files = [];
	/** @var \CFile */
	protected $cfile = \CFile::class;
	/** @var FileInputUtility */
	protected $fileInputUtility;

	public function __construct()
	{
		$this->registerShutdownFunction();
		$this->fileInputUtility = FileInputUtility::instance();
	}

	protected function registerShutdownFunction(): void
	{
		register_shutdown_function([$this, 'deleteTemporaryFiles']);
	}

	/**
	 * Check whether is $fileData is a valid file description for $field
	 *
	 * @param Field $field
	 * @param array $fileData
	 * @return Result
	 */
	final public function checkFile(Field $field, array $fileData): Result
	{
		$result = new Result();

		if ($field->getValueType() === Field::VALUE_TYPE_IMAGE)
		{
			$errors = $this->cfile::CheckImageFile($fileData);

			if (!empty($errors))
			{
				$result->addError(new Error($errors));
			}
		}

		return $result;
	}

	/**
	 * Saves new file with $fileData for $field permanently.
	 *
	 * @param Field $field
	 * @param array $fileData
	 * @return int|null
	 */
	public function saveFilePersistently(Field $field, array $fileData): ?int
	{
		return $this->saveFile($field, $fileData);
	}

	/**
	 * Saves new file with $fileData for $field.
	 * If during the hit this file is not bind to any \Bitrix\Crm\Item it will be deleted.
	 *
	 * @param Field $field
	 * @param array $fileData
	 * @return int|null
	 */
	public function saveFileTemporary(Field $field, array $fileData): ?int
	{
		$fileId = $this->saveFile($field, $fileData);
		if ($fileId > 0)
		{
			$this->files[$fileId] = $fileId;
		}

		return $fileId;
	}

	protected function saveFile(Field $field, array $fileData): ?int
	{
		$fileData['MODULE_ID'] = 'crm';

		$fileId = $this->cfile::SaveFile($fileData, 'crm');

		if ($fileId > 0)
		{
			$this->registerFileId($field, $fileId);

			return (int)$fileId;
		}

		return null;
	}

	/**
	 * Register $fileId for $field.
	 *
	 * @param Field $field
	 * @param int $fileId
	 * @return void
	 */
	public function registerFileId(Field $field, int $fileId): void
	{
		$controlId = $this->fileInputUtility->getUserFieldCid($field->getUserField());

		$this->fileInputUtility->registerControl($controlId, $controlId);
		$this->fileInputUtility->registerFile($controlId, $fileId);
	}

	/**
	 * Mark $fileId as successfully bind to some \Bitrix\Crm\Item
	 *
	 * @param int $fileId
	 * @return $this
	 */
	public function markFileAsPersistent(int $fileId): self
	{
		unset($this->files[$fileId]);

		return $this;
	}

	/**
	 * Delete all not bound files.
	 *
	 * @return $this
	 */
	public function deleteTemporaryFiles(): self
	{
		foreach ($this->files as $fileId)
		{
			$this->deleteFilePersistently($fileId);
		}

		$this->files = [];

		return $this;
	}

	final public function deleteFilePersistently(int $fileId): self
	{
		$this->cfile::Delete($fileId);

		return $this;
	}

	/**
	 * Returns site-relative path to a file with id $fileId. If file not found, returns null.
	 *
	 * @param int $fileId
	 * @return string|null
	 */
	final public function getFilePath(int $fileId): ?string
	{
		$path = $this->cfile::GetPath($fileId);

		return is_string($path) ? $path : null;
	}
}
