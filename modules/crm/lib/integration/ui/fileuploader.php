<?php

namespace Bitrix\Crm\Integration\UI;

use Bitrix\Main\Loader;
use Bitrix\Main\NotSupportedException;
use Bitrix\UI\FileUploader\Uploader;
use Bitrix\UI\FileUploader\UploaderController;

class FileUploader
{
	private Uploader $uploader;

	public function __construct(UploaderController $controller)
	{
		if (!Loader::includeModule('ui'))
		{
			throw new NotSupportedException('"ui" module is required');
		}

		$this->uploader = new Uploader($controller);
	}

	public function getUploader(): Uploader
	{
		return $this->uploader;
	}

	public function setUploader(Uploader $uploader): self
	{
		$this->uploader = $uploader;

		return $this;
	}

	public function getPendingFiles(array $tempFileIds): array
	{
		$pendingFiles = $this->uploader->getPendingFiles($tempFileIds);

		return $pendingFiles->getFileIds();
	}

	public function makePersistentFiles(array $tempFileIds): void
	{
		$this->uploader->getPendingFiles($tempFileIds)->makePersistent();
	}
}
