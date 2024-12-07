<?php

namespace Bitrix\Crm\Component\EntityList\NearestActivity;

use Bitrix\Crm\Traits\Singleton;

class ManagerFactory
{
	use Singleton;

	public function getManager(int $entityTypeId): Manager
	{
		return new Manager($entityTypeId);
	}
}