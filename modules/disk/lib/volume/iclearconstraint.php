<?php

namespace Bitrix\Disk\Volume;

interface IClearConstraint
{
	/**
	 * Check ability to clear storage.
	 * @param \Bitrix\Disk\Storage $storage Storage to clear.
	 * @return boolean
	 */
	public function isAllowClearStorage(\Bitrix\Disk\Storage $storage);
}
