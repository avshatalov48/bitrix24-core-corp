<?php

namespace Bitrix\Crm\Security\Controller;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Crm;

class ControllerQueries
{
	use Singleton;

	public function getDealProgressSteps($permissionEntityType): array
	{
		return array_keys(
			Crm\Category\DealCategory::getStageList(
				Crm\Category\DealCategory::convertFromPermissionEntityType($permissionEntityType)
			)
		);
	}

	public function getLeadProgressSteps($permissionEntityType): array
	{
		return array_keys(\CCrmStatus::GetStatusList('STATUS'));
	}
}