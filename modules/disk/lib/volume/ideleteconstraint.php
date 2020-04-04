<?php

namespace Bitrix\Disk\Volume;

interface IDeleteConstraint
{
	/**
	 * Check ability to drop folder.
	 * @param \Bitrix\Disk\Folder $folder Folder to drop.
	 * @return boolean
	 */
	public function isAllowDeleteFolder(\Bitrix\Disk\Folder $folder);
}
