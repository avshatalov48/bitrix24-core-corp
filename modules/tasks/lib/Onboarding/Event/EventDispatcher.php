<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Event;

use Bitrix\Main\EventResult;

class EventDispatcher
{
	public static function OnUserInitialize(array $data): EventResult
	{
		return OnFirstUserAuthorizeListener::getInstance()->onUserInitialize($data);
	}

	public static function OnAfterUserUpdate(array $data): EventResult
	{
		return OnUserFiredListener::getInstance()->onAfterUserFired($data);
	}

	public static function OnAfterUserDelete(int $userId): EventResult
	{
		return OnUserFiredListener::getInstance()->onAfterUserDelete($userId);
	}
}