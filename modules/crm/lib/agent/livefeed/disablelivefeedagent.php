<?php

namespace Bitrix\Crm\Agent\LiveFeed;

use Bitrix\Crm\Settings\Crm;
use Bitrix\Crm\Integration\Socialnetwork\Livefeed;
use Bitrix\Crm\Settings\LiveFeedSettings;

class DisableLiveFeedAgent
{
	public static function run(): void
	{
		Crm::setLiveFeedRecordsGenerationEnabled(false);
		LiveFeedSettings::getCurrent()->enableLiveFeedMerge(false);
		Livefeed\AvailabilityHelper::setAvailable(false);
		Livefeed\AvailabilityHelper::deleteUnusedOptions();
	}
}
