<?php

namespace Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Socialnetwork\Helper\Feature;
use Bitrix\Tasks\Util\Restriction\Bitrix24Restriction\Limit;

class ProjectLimit extends Limit
{
	public static function isFeatureEnabled(int $groupId = 0): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return true;
		}

		if (Feature::isFeatureEnabled(Feature::PROJECTS_GROUPS, $groupId))
		{
			return true;
		}

		return false;
	}

	public static function isFeatureEnabledOrTrial(): bool
	{
		return static::isFeatureEnabled() || static::canTurnOnTrial();
	}

	public static function canTurnOnTrial(): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		return Feature::canTurnOnTrial(Feature::PROJECTS_GROUPS);
	}

	public static function turnOnTrial(): void
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			throw new SystemException('socialnetwork not include');
		}

		Feature::turnOnTrial(Feature::PROJECTS_GROUPS);
	}

	public static function getFeatureId(): string
	{
		return Feature::PROJECTS_GROUPS;
	}

	public static function getLimitCode(): string
	{
		return 'limit_' . Feature::PROJECTS_GROUPS;
	}
}
