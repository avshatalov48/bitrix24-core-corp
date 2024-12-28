<?php

namespace Bitrix\BIConnector\Integration\UI;

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
}
