<?php

namespace Bitrix\Disk\Volume;

interface IVolumeIndicatorStorage
{
	/**
	 * Gets available disk space. Units ara bytes.
	 * @param \Bitrix\Disk\Storage|null $storage Storage entity object.
	 * @return int
	 */
	public static function getAvailableSpace(\Bitrix\Disk\Storage $storage = null);

	/**
	 * Returns entity type list.
	 * @return string[]
	 */
	public static function getEntityType();
}
