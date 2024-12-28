<?php

namespace Bitrix\CalendarMobile\Integration\Disk;

use Bitrix\Disk\Driver;
use Bitrix\Disk\TypeFile;
use Bitrix\Main\Loader;

class Attachment
{
	protected int $userId;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	/**
	 * @param array $fileIds
	 *
	 * @return array
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getAttachments(array $fileIds): array
	{
		if (empty($fileIds) || !Loader::includeModule('disk'))
		{
			return [];
		}

		$driver = Driver::getInstance();
		$urlManager = $driver->getUrlManager();
		$userFieldManager = $driver->getUserFieldManager();
		$userFieldManager->loadBatchAttachedObject($fileIds);

		$files = [];
		foreach($fileIds as $fileId)
		{
			$attachedObject = $userFieldManager->getAttachedObjectById($fileId);
			if (!$attachedObject || !$attachedObject->canRead($this->userId))
			{
				continue;
			}

			$file = $attachedObject->getFile();
			if (!$file)
			{
				continue;
			}

			$files[] = [
				'id' => (int)$fileId,
				'objectId' => (int)$attachedObject->getObjectId(),
				'name' => $file->getName(),
				'type' => TypeFile::getMimeTypeByFilename($file->getName()),
				'url' => $urlManager::getUrlUfController('show', ['attachedId' => $fileId]),
				'previewUrl' => $urlManager::getUrlToActionShowUfFile($fileId, ['width' => 640, 'height' => 640]),
				'width' => (int)$attachedObject->getExtra()->get('FILE_WIDTH'),
				'height' => (int)$attachedObject->getExtra()->get('FILE_HEIGHT'),
			];
		}

		return $files;
	}
}
