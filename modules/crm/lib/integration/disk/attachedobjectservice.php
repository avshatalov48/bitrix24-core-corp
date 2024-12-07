<?php

namespace Bitrix\Crm\Integration\Disk;

use Bitrix\Crm\Service\Container;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\TypeFile;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main\Loader;

Loader::requireModule('disk');

class AttachedObjectService
{

	public static function loadFilesData(array $ids, int $ownerId, int $ownerTypeId): array
	{
		$userId = Container::getInstance()->getContext()->getUserId();

		$values = $ids;

		$files = [];
		$driver = Driver::getInstance();
		$urlManager = $driver->getUrlManager();
		$userFieldManager = $driver->getUserFieldManager();

		$userFieldManager->loadBatchAttachedObject($values);
		foreach ($values as $id)
		{
			$attachedModel = null;
			[$type, $realValue] = FileUserType::detectType($id);
			if (empty($realValue) || $realValue <= 0)
			{
				continue;
			}

			if ($type === FileUserType::TYPE_NEW_OBJECT)
			{
				$fileModel = File::loadById($realValue);
				if (!$fileModel || !$fileModel->canRead($fileModel->getStorage()->getCurrentUserSecurityContext()))
				{
					continue;
				}
			}
			else
			{
				$attachedModel = $userFieldManager->getAttachedObjectById($realValue);
				if (!$attachedModel)
				{
					continue;
				}

				$attachedModel->setOperableEntity(array(
					'ENTITY_ID' => $ownerTypeId,
					'ENTITY_VALUE_ID' => $ownerId,
				));

				$fileModel = $attachedModel->getFile();
			}

			$securityContext = $fileModel->getStorage()->getCurrentUserSecurityContext();

			$name = $fileModel->getName();
			$data = [
				'ID' => $id,
				'NAME' => $name,
				'SIZE' => $fileModel->getSize(),
				'FILE_ID' => $fileModel->getFileId(),
				'CAN_READ' => (
				$attachedModel
					? $attachedModel->canRead($userId)
					: $fileModel->canRead($securityContext)
				),
				'VIEW_URL' => $urlManager::getUrlUfController('show', ['attachedId' => $id]),
			];

			if (TypeFile::isImage($fileModel) || TypeFile::isVideo($fileModel))
			{
				$data['VIEW_URL'] = (
				$attachedModel === null
					? $urlManager->getUrlForShowFile($fileModel)
					: $urlManager::getUrlUfController('show', ['attachedId' => $id])
				);
			}

			$files[] = $data;
		}

		return $files;
	}
}