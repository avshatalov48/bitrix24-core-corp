<?php

namespace Bitrix\BIConnector\Superset\UI\SettingsPanel\Field;

use Bitrix\BIConnector\Superset\Cache\CacheManager;

final class ClearCacheField extends EntityEditorField
{
	public const FIELD_NAME = 'CLEAR_CACHE';
	public const FIELD_ENTITY_EDITOR_TYPE = 'clearCache';

	public function getFieldInitialData(): array
	{
		$cacheManager = CacheManager::getInstance();
		$canClearCache = $cacheManager->canClearCache();
		$cacheTimeout = null;
		if (!$canClearCache)
		{
			$cacheTimeout = $cacheManager->getNextClearTimeout();
		}

		return [
			'canClearCache' => $canClearCache,
			'clearCacheTimeout' => $cacheTimeout,
		];
	}

	public function getFieldInfoData(): array
	{
		return [];
	}

	public function getName(): string
	{
		return self::FIELD_NAME;
	}

	public function getType(): string
	{
		return self::FIELD_ENTITY_EDITOR_TYPE;
	}
}
