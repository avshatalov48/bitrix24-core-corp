<?php

namespace Bitrix\Crm\Controller\Integration\SocialNetwork;

use Bitrix\Crm\Integration\Socialnetwork\Livefeed\AvailabilityHelper;

class LiveFeed extends \Bitrix\Crm\Controller\Base
{
	public function getDisablingInfoAction(): array
	{
		return [
			'isShowAlert' => AvailabilityHelper::isShowAlert(),
			'daysUntilDisable' => AvailabilityHelper::getDaysUntilDisable(),
			'alertContainerSelector' => AvailabilityHelper::ALERT_SELECTOR_CLASS,
			'showAlertUserOption' => AvailabilityHelper::SHOW_ALERT_OPTION,
		];
	}
}
