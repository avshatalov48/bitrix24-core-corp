<?php

namespace Bitrix\Crm\Service\Timeline\Item\Compatible;

use Bitrix\Crm\Timeline\WaitController;

class Wait extends Compatible
{
	protected function initializeData(array $data): array
	{
		return WaitController::prepareScheduleDataModel(
			$data,
			['ENABLE_USER_INFO' => true]
		);
	}
}
