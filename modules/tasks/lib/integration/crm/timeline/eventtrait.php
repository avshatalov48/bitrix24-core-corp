<?php

namespace Bitrix\Tasks\Integration\CRM\Timeline;

use Bitrix\Disk\Internals\AttachedObjectTable;
use Bitrix\Main\Loader;

trait EventTrait
{
	public function getFiles(int $entityId, string $connector): array
	{
		if (!Loader::includeModule('disk'))
		{
			return [];
		}

		$query = AttachedObjectTable::query();
		$query
			->setSelect(['ID', 'OBJECT_ID'])
			->where('ENTITY_TYPE', $connector)
			->where('ENTITY_ID', $entityId)
		;

		return $query->exec()->fetchCollection()->getObjectIdList();
	}

	public function getPriority(): int
	{
		return 0;
	}
}