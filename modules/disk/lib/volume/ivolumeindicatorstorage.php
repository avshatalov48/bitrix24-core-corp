<?php

namespace Bitrix\Disk\Volume;

use Bitrix\Disk;

interface IVolumeIndicatorStorage
{
	/**
	 * Gets available disk space. Units ara bytes.
	 * @param Disk\Storage|null $storage Storage entity object.
	 * @return int
	 */
	public static function getAvailableSpace(Disk\Storage $storage = null);

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType(): array;
}
