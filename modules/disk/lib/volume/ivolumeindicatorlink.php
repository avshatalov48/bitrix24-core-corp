<?php

namespace Bitrix\Disk\Volume;
use \Bitrix\Disk\Volume;

interface IVolumeIndicatorLink
{
	/**
	 * @param Volume\Fragment $fragment Entity object.
	 * @return string
	 */
	public static function getUrl(Volume\Fragment $fragment);
}
