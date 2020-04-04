<?php

namespace Bitrix\Disk\Internals\Index;

use Bitrix\Disk\Configuration;

class ObjectHeadIndexTable extends BaseIndexTable
{
	public static function getTableName()
	{
		return 'b_disk_object_head_index';
	}

	public static function getMaxIndexSize()
	{
		return Configuration::getMaxHeadIndexSize();
	}
}