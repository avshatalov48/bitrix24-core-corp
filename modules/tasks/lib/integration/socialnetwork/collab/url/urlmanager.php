<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Integration\SocialNetwork\Collab\Url;

use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\Site\GroupUrl;

class UrlManager
{
	public static function getCollabUrlById(int $id): string
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return '';
		}

		return \Bitrix\Socialnetwork\Collab\Url\UrlManager::getCollabUrlById($id);
	}

	public static function getCollabUrlTemplateDialogId(): string
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return '';
		}

		return \Bitrix\Socialnetwork\Collab\Url\UrlManager::getCollabUrlTemplateDialogId();
	}

	public static function getUrlByType(int $groupId, ?string $type = null, array $parameters = []): string
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return '';
		}

		return GroupUrl::get($groupId, $type, $parameters);
	}
}