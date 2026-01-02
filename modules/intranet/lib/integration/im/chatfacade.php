<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Integration\Im;

use Bitrix\Main;

final class ChatFacade
{
	public function hasAccess(int $userId, int $targetUserId): bool
	{
		if (Main\Loader::includeModule('im'))
		{
			return \Bitrix\Im\V2\Entity\User\User::getInstance($userId)->checkAccess($targetUserId)->isSuccess();
		}

		return false;
	}
}
