<?php

namespace Bitrix\Crm\FileUploader;

use Bitrix\Crm\Activity\Entity;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Crm\Integration\Disk\HiddenStorage;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\UI\FileUploader\Configuration;
use Bitrix\UI\FileUploader\FileOwnershipCollection;

class TodoActivityUploaderController extends ActivityUploaderController
{
	private const MAX_UPLOAD_FILE_SIZE = 1024 * 1024 * 50; // 50M;

	private ?int $activityId = null;

	public function __construct(array $options)
	{
		if (isset($options['activityId']))
		{
			$this->activityId = (int)$options['activityId'];
		}

		parent::__construct($options);
	}

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

	public function verifyFileOwner(FileOwnershipCollection $files): void
	{
		$entityFiles = $this->fetchToDoActivityFiles();
		foreach ($files as $file)
		{
			if (in_array($file->getId(), $entityFiles, true))
			{
				$file->markAsOwn();
			}
		}
	}

	private function fetchToDoActivityFiles(): array
	{
		['entityTypeId' => $entityTypeId, 'entityId' => $entityId] = $this->getOptions();

		if (isset($entityId, $entityTypeId, $this->activityId))
		{
			$todo = (new Entity\ToDo(new ItemIdentifier($entityTypeId, $entityId), new Provider\ToDo\ToDo()))
				->load($this->activityId);
			if ($todo)
			{
				$storageElementIds = $todo->getStorageElementIds() ?? [];

				return array_map('intval', (new HiddenStorage())->fetchFileIdsByStorageFileIds($storageElementIds));
			}
		}

		return [];
	}
}
