<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Field;
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

	public function saveFilePersistently(Field $field, array $fileData): ?int
	{
		return $this->saveFile($field, $fileData);
	}

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
			$controlId = $this->fileInputUtility->getUserFieldCid($field->getUserField());

			$this->fileInputUtility->registerControl($controlId, $controlId);
			$this->fileInputUtility->registerFile($controlId, $fileId);

			return (int)$fileId;
		}

		return null;
	}

	public function markFileAsPersistent(int $fileId): self
	{
		unset($this->files[$fileId]);

		return $this;
	}

	public function deleteTemporaryFiles(): self
	{
		foreach ($this->files as $fileId)
		{
			if ($fileId > 0)
			{
				$this->cfile::Delete($fileId);
			}
		}

		$this->files = [];

		return $this;
	}
}