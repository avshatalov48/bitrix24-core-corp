<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI\Event;

use Bitrix\Main\Event;
use Bitrix\Tasks\Flow\Integration\AI\Agent\PromoRequestsCountUpdatedAgent;
use Bitrix\Tasks\Flow\Integration\AI\Stepper\PromoRequestsCountUpdatedStepper;

class CopilotRequestLimitListener
{
	public static function onBoostActivated(Event $event): void
	{
		PromoRequestsCountUpdatedAgent::removeAgent();
		PromoRequestsCountUpdatedStepper::bind(0);
	}
}
