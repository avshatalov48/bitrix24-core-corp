<?php

namespace Bitrix\BIConnector\Integration\UI\FileUploaderController;

use Bitrix\UI\FileUploader\Configuration;
use Bitrix\UI\FileUploader\FileOwnershipCollection;
use Bitrix\UI\FileUploader\UploaderController;

class DatasetUploaderController extends UploaderController
{
	public function __construct(array $options = [])
	{
		parent::__construct($options);
	}

	public function isAvailable(): bool
	{
		return true;
	}

	public function getConfiguration(): Configuration
	{
		$configuration = new Configuration();
		$configuration->setAcceptedFileTypes(['.csv']);

		return $configuration;
	}

	public function canUpload()
	{
		return true;
	}

	public function canView(): bool
	{
		return true;
	}

	public function verifyFileOwner(FileOwnershipCollection $files): void
	{
	}

	public function canRemove(): bool
	{
		return true;
	}
}
