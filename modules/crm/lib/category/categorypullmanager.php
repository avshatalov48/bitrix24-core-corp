<?php

namespace Bitrix\Crm\Category;

use Bitrix\Crm\Traits\Singleton;
use Bitrix\Main\Loader;
use CPullWatch;

final class CategoryPullManager
{
	use Singleton;

	public const MODULE_ID = 'crm';
	public const EVENT_CATEGORIES_UPDATED = 'CRM_CATEGORIES_UPDATED';

	public function sendEventCategoriesUpdated(int $entityTypeId): bool
	{
		if (!Loader::includeModule('pull'))
		{
			return false;
		}

		$params = [
			'entityTypeId' => $entityTypeId,
		];

		return $this->addToStack(self::EVENT_CATEGORIES_UPDATED, $params);
	}

	public function subscribe(int $userId, string $eventId): bool
	{
		if (!Loader::includeModule('pull'))
		{
			return false;
		}

		return CPullWatch::Add($userId, $eventId);
	}

	private function addToStack(string $tag, array $params): bool
	{
		return CPullWatch::AddToStack($tag, [
			'module_id' => self::MODULE_ID,
			'command' => $tag,
			'params' => $params,
		]);
	}
}
