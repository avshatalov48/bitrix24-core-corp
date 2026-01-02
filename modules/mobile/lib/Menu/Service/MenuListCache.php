<?php

namespace Bitrix\Mobile\Menu\Service;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\TaggedCache;
use Bitrix\MobileApp\Mobile;

class MenuListCache implements MenuListCacheInterface
{
	private const CACHE_DIR = '/mobile/menu/';
	private const CACHE_KEY_PREFIX = 'mobile_menu_';
	private int $userId;
	private int $defaultTtl;
	private int $apiVersion;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
		$this->defaultTtl = defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600;
		$this->apiVersion = Mobile::getInstance()::getApiVersion();
	}

	public function get(): ?array
	{
		$cache = Cache::createInstance();

		$cacheKey = $this->generateCacheKey();
		if ($cache->initCache($this->defaultTtl, $cacheKey, self::CACHE_DIR))
		{
			return $cache->getVars();
		}

		return null;
	}

	public function set(array $data): void
	{
		$cache = Cache::createInstance();
		$taggedCache = \Bitrix\Main\Application::getInstance()->getTaggedCache();
		$cacheKey = $this->generateCacheKey();

		$taggedCache->startTagCache(self::CACHE_DIR);
		$this->registerTags($taggedCache);
		$taggedCache->endTagCache();

		if ($cache->startDataCache($this->defaultTtl, $cacheKey, self::CACHE_DIR))
		{
			$cache->endDataCache($data);
		}
	}

	public function clear(): void
	{
		$cache = Cache::createInstance();
		$cacheKey = $this->generateCacheKey();
		$cache->clean($cacheKey, self::CACHE_DIR);
	}

	private function generateCacheKey(): string
	{
		$keyParts = [
			$this->userId,
			IsModuleInstalled('extranet') ? 'extranet' : 'intranet',
			LANGUAGE_ID,
			$this->apiVersion,
		];

		return self::CACHE_KEY_PREFIX . md5(implode('_', $keyParts));
	}

	private function registerTags(TaggedCache $taggedCache): void
	{
		$taggedCache->registerTag('sonet_group');
		$taggedCache->registerTag('crm_initiated');
		$taggedCache->registerTag('USER_CARD_' . (int)($this->userId / TAGGED_user_card_size));
		$taggedCache->registerTag('sonet_user2group_U' . $this->userId);
		$taggedCache->registerTag('mobile_custom_menu' . $this->userId);
		$taggedCache->registerTag('mobile_custom_menu');
		$taggedCache->registerTag('crm_change_role');
		$taggedCache->registerTag('bitrix24_left_menu');
	}
}
