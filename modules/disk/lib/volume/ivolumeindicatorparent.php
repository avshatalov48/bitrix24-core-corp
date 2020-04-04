<?php

namespace Bitrix\Disk\Volume;
use \Bitrix\Disk\Volume;

interface IVolumeIndicatorParent
{
	/**
	 * @param Volume\Fragment $fragment Entity object.
	 * @return string[]
	 */
	public static function getParents(Volume\Fragment $fragment);
}
