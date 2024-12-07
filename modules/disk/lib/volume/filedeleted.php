<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Main\DB;
use Bitrix\Disk\Internals\ObjectTable;
use Bitrix\Disk\Volume;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class FileDeleted extends Volume\File
{
	/**
	 * Runs measure test to get volumes of selecting objects.
	 * @param array $collectData List types data to collect: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return static
	 */
	public function measure(array $collectData = [self::DISK_FILE]): self
	{
		$this->addFilter('!=DELETED_TYPE', ObjectTable::DELETED_TYPE_NONE);

		parent::measure($collectData);

		return $this;
	}

	/**
	 * Returns result set of file list corresponding to filter.
	 * @param array $collectedData List types of collected data to return: ATTACHED_OBJECT, SHARING_OBJECT, EXTERNAL_LINK, UNNECESSARY_VERSION.
	 * @return DB\Result
	 */
	public function getMeasurementResult(array $collectedData = [self::DISK_FILE, self::ATTACHED_OBJECT, self::EXTERNAL_LINK, self::UNNECESSARY_VERSION]): DB\Result
	{
		$this->addFilter('!=DELETED_TYPE', ObjectTable::DELETED_TYPE_NONE);
		$this->unsetFilter('PARENT_ID');
		$this->unsetFilter('FILES_LEFT');

		return parent::getMeasurementResult($collectedData);
	}
}


