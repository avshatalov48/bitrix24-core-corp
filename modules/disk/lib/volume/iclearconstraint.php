<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Disk;

interface IClearConstraint
{
	/**
	 * Check ability to clear storage.
	 * @param Disk\Storage $storage Storage to clear.
	 * @return bool
	 */
	public function isAllowClearStorage(Disk\Storage $storage): bool;
}
