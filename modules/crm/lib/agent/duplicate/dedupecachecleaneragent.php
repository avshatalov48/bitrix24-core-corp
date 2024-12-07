<?php

namespace Bitrix\Crm\Agent\Duplicate;

use Bitrix\Crm\Agent\AgentBase;
use Bitrix\Crm\Badge\Model\BadgeTable;
use Bitrix\Crm\Integrity\MatchHashDedupeCache;
use Bitrix\Crm\Settings\ActivitySettings;
use Bitrix\Main\Type\DateTime;

class DedupeCacheCleanerAgent extends AgentBase
{
	public static function doRun(): bool
	{
		MatchHashDedupeCache::dropExpired();

		return true;
	}
}