<?php

declare(strict_types=1);


namespace Bitrix\Intranet\Integration\Socialnetwork\Chat;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Integration\Im\Chat\Workgroup;

class GroupChat
{
	public static function getChatIds(array $groupIds): array
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return [];
		}

		return  Workgroup::getChatData(['group_id' => $groupIds]);
	}
}