<?php

namespace Bitrix\Rpa\Integration\Disk;

use Bitrix\Disk\Uf\StubConnector;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\TimelineTable;

class Connector extends StubConnector
{
	public function canRead($userId): bool
	{
		$timeline = TimelineTable::getById((int) $this->entityId)->fetchObject();
		if($timeline)
		{
			$item = $timeline->getItem();
			if($item)
			{
				return Driver::getInstance()->getUserPermissions($userId)->canViewItem($item);
			}
			else
			{
				return ($timeline->getUserId() === (int) $userId);
			}
		}

		return false;
	}

	public function canUpdate($userId)
	{
		$timeline = TimelineTable::getById((int) $this->entityId)->fetchObject();
		if($timeline)
		{
			return Driver::getInstance()->getUserPermissions($userId)->canUpdateComment($timeline);
		}

		return false;
	}
}