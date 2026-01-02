<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding;

use Bitrix\Main\Config\Option;

final class OnboardingFeature
{
	public static function isAvailable(): bool
	{
		return Option::get('tasks', 'tasks_onboarding_feature', 'Y') === 'Y';
	}

	public static function isNewPortal(): bool
	{
		return Portal::getInstance()->isNew();
	}
}
