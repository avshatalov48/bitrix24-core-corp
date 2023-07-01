<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Volume;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class FolderDeleted extends Volume\FolderTree
{
	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	public function measure(array $collectData = [self::DISK_FILE, self::UNNECESSARY_VERSION]): self
	{
		$this->addFilter('!DELETED_TYPE', ObjectTable::DELETED_TYPE_NONE);

		parent::measure($collectData);

		return $this;
	}
}
