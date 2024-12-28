<?php

declare(strict_types=1);


namespace Bitrix\Intranet\Integration\Socialnetwork\Url;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Collab\Url\UrlManager;

class GroupUrl
{
	public static function get(int $groupId, string $groupType, ?int $chatId = null): string
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return '';
		}

		return \Bitrix\Socialnetwork\Site\GroupUrl::get(
			$groupId,
			$groupType,
			['chatId' => $chatId]
		);
	}

	public static function getCollabTemplate(): string
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return '';
		}

		return UrlManager::getCollabUrlTemplateDialogId();
	}

	public static function getDialogId(int $chatId): string
	{
		return 'chat' . $chatId;
	}
}