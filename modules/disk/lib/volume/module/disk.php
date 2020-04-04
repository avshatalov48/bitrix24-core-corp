<?php

namespace Bitrix\Disk\Volume\Module;

use Bitrix\Disk\Volume;

/**
 * Disk storage volume measurement class.
 * @package Bitrix\Disk\Volume
 */
class Disk extends Volume\Module\Module
{
	/** @var string */
	protected static $moduleId = 'disk';

	/**
	 * Returns special folder code list.
	 * @return string[]
	 */
	public static function getSpecialFolderCode()
	{
		return array(
			\Bitrix\Disk\Folder::CODE_FOR_CREATED_FILES,
			\Bitrix\Disk\Folder::CODE_FOR_SAVED_FILES,
			\Bitrix\Disk\Folder::CODE_FOR_UPLOADED_FILES,
		);
	}
}

