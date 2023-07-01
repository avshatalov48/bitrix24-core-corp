<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Disk;

interface IClearFolderConstraint
{
	/**
	 * Check ability to clear folder.
	 *
	 * @param Disk\Folder $folder Folder to clear.
	 *
	 * @return boolean
	 */
	public function isAllowClearFolder(Disk\Folder $folder): bool;
}
