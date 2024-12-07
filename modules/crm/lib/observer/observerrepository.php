<?php

namespace Bitrix\Crm\Observer;

use Bitrix\Crm\Observer\Entity\ObserverTable;
use Bitrix\Crm\Traits\Singleton;

class ObserverRepository
{
	use Singleton;

	public function isUsersPresentAsObservers(int $userId, int $entityTypeID): bool
	{
		$row = ObserverTable::query()
			->setSelect(['USER_ID'])
			->where('USER_ID', $userId)
			->where('ENTITY_TYPE_ID', $entityTypeID)
			->setCacheTtl(60)
			->setLimit(1)
			->fetch();

		return $row !== false;
	}
}