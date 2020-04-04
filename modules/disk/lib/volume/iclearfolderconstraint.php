<?php

namespace Bitrix\Disk\Volume;

interface IClearFolderConstraint
{
	/**
	 * Check ability to clear folder.
	 *
	 * @param \Bitrix\Disk\Folder $folder Folder to clear.
	 *
	 * @return boolean
	 */
	public function isAllowClearFolder(\Bitrix\Disk\Folder $folder);
}
