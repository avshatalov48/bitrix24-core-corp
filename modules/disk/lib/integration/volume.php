<?php
namespace Bitrix\Disk\Integration;

use \Bitrix\Main;
use \Bitrix\Disk;

class Volume
{
	/**
	 * Delete storage event handler. Event disk::onAfterDeleteStorage.
	 * @param int $storageId Storage id.
	 * @param int $deletedBy Dropped by id.
	 * @return void
	 */
	public static function onStorageDelete($storageId, $deletedBy = Disk\SystemUser::SYSTEM_USER_ID)
	{
		Main\Loader::includeModule('disk');
		Disk\Internals\VolumeTable::onStorageDelete($storageId, $deletedBy);
	}

	/**
	 * Delete user event handler. Event main:onUserDelete.
	 * @param int $userId User id.
	 * @return void
	 */
	public static function onUserDelete($userId)
	{
		Main\Loader::includeModule('disk');
		Disk\Internals\VolumeTable::onUserDelete($userId);
	}
}
