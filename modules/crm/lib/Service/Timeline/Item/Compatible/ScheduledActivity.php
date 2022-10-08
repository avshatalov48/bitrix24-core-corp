<?php

namespace Bitrix\Crm\Service\Timeline\Item\Compatible;

use Bitrix\Crm\Timeline\ActivityController;

class ScheduledActivity extends Compatible
{
	protected function initializeData(array $data): array
	{
		$result = ActivityController::prepareScheduleDataModel(
			$data,
			[
				'CURRENT_USER' => $this->context->getUserId(),
				'ENABLE_USER_INFO' => true,
			]
		);
		if (isset($data['COMMUNICATION']) && !isset($result['ASSOCIATED_ENTITY']['COMMUNICATION']))
		{
			$result['ASSOCIATED_ENTITY']['COMMUNICATION'] = $data['COMMUNICATION'];
		}

		return $result;
	}
}
