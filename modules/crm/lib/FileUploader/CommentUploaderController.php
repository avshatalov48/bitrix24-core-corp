<?php

namespace Bitrix\Crm\FileUploader;

use Bitrix\UI\FileUploader\Configuration;

class CommentUploaderController extends EntityController
{
	private const MAX_UPLOAD_FILE_SIZE = 1024 * 1024 * 50; // 50M;

	public function getConfiguration(): Configuration
	{
		$configuration = new Configuration();

		$configuration->setMaxFileSize(static::MAX_UPLOAD_FILE_SIZE);

		return $configuration;
	}

	public function canView(): bool
	{
		['entityTypeId' => $entityTypeId, 'entityId' => $entityId] = $this->getOptions();

		return $this->userPermissions->checkReadPermissions($entityTypeId, $entityId);
	}
}
