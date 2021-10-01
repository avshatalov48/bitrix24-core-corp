<?php

namespace Bitrix\Disk\Internals;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Disk\ProxyType;
use Bitrix\Disk\User;
use Bitrix\Main\Localization\Loc;

final class DeletionNotifyManager
{
	/** @var array */
	private $map = [];

	public function put(BaseObject $object, int $deletedBy): void
	{
		//author can delete object without notification
		if ($object->getCreatedBy() && ($object->getCreatedBy() == $deletedBy))
		{
			return;
		}

		$storage = $object->getStorage();
		//we notify only when file belongs to user (not group and not common disk)
		if (!$storage || !($storage->getProxyType() instanceof ProxyType\User))
		{
			return;
		}

		$this->map[$object->getParentId() . "-{$object->getCreatedBy()}"][] = [
			$deletedBy,
			$object->getCreatedBy(),
			$object->getId(),
			$object->getName(),
			$object instanceof Folder,
		];

		if ($object instanceof Folder)
		{
			unset($this->map[$object->getId() . "-{$object->getCreatedBy()}"]);
		}
	}

	public function send()
	{
		$urlManager = Driver::getInstance()->getUrlManager();
		foreach ($this->map as $deletions)
		{
			foreach ($deletions as $deletionData)
			{
				[$deletedBy, $createdBy, $objectId, $name, $isFolder] = $deletionData;

				$deleteUser = User::getById($deletedBy);
				if (!$deleteUser)
				{
					continue;
				}

				$link = $urlManager::getUrlFocusController('showObjectInTrashCanGrid', [
					'objectId' => $objectId,
				]);

				$type = $isFolder? 'FOLDER' : 'FILE';
				$text = Loc::getMessage("DISK_DELETION_MANAGER_NOTIFY_ABOUT_DELETION_{$type}_M", [
					'#NAME#' => "<a href=\"{$link}\">{$name}</a>",
				]);
				if($deleteUser->getPersonalGender() === 'F')
				{
					$text = Loc::getMessage("DISK_DELETION_MANAGER_NOTIFY_ABOUT_DELETION_{$type}_F");
				}

				Driver::getInstance()->sendNotify($createdBy, [
					'FROM_USER_ID' => $deletedBy,
					'NOTIFY_EVENT' => 'deletion',
					'NOTIFY_TAG' => Driver::INTERNAL_MODULE_ID . "|DEL|{$objectId}",
					'NOTIFY_MESSAGE' => $text,
				]);
			}
		}

		$this->map = [];
	}
}