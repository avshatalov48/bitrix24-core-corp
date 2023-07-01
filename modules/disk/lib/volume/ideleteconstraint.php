<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Disk;

interface IDeleteConstraint
{
	/**
	 * Check ability to drop folder.
	 * @param Disk\Folder $folder Folder to drop.
	 * @return boolean
	 */
	public function isAllowDeleteFolder(Disk\Folder $folder): bool;
}
