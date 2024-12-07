<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Field;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\UI\FileInputUtility;
use Bitrix\Main\UserField\File\ManualUploadRegistry;

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
	 * Check whether is bFileId references is a valid file for $field
	 *
	 * @param Field $field
	 * @param int $bFileId
	 * @return Result
	 */
	final public function checkFileById(Field $field, int $bFileId): Result
	{
		$fileData = null;
		$fileDBRow = null;
		if ($bFileId > 0)
		{
			$fileData = $this->cfile::MakeFileArray($bFileId);
			$fileDBRow = $this->cfile::GetFileArray($bFileId);
		}

		if (empty($fileData) || empty($fileDBRow))
		{
			return (new Result())->addError(new Error('File not found', ErrorCode::FILE_NOT_FOUND));
		}

		$moduleId = $fileDBRow['MODULE_ID'] ?? null;
		if ($moduleId !== 'crm')
		{
			return (new Result())->addError(new Error('File should be bound to CRM module', 'WRONG_FILE_MODULE_ID'));
		}

		return $this->checkFile($field, $fileData);
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

		if (is_numeric($fileId) && (int)$fileId > 0)
		{
			$fileId = (int)$fileId;

			$this->registerFileId($field, $fileId);

			return $fileId;
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
		if (!$field->isUserField())
		{
			return;
		}

		if ($this->isFileInputUtilityAccessible())
		{
			$controlId = $this->fileInputUtility->getUserFieldCid($field->getUserField());

			$this->fileInputUtility->registerControl($controlId, $controlId);
			$this->fileInputUtility->registerFile($controlId, $fileId);
		}

		if (class_exists('\Bitrix\Main\UserField\File\ManualUploadRegistry'))
		{
			ManualUploadRegistry::getInstance()->registerFile($field->getUserField(), $fileId);
		}
	}

	private function isFileInputUtilityAccessible(): bool
	{
		if (method_exists($this->fileInputUtility, 'isAccessible'))
		{
			return $this->fileInputUtility->isAccessible();
		}

		return Application::getInstance()->getSession()->isAccessible();
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
