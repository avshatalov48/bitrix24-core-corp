<?php
namespace Bitrix\ImOpenLines\User;

use Bitrix\ImOpenLines\Model\UserOptionTable;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;

class Option
{
	private const CACHE_DIR = '/bx/imol/user_option';
	private const CACHE_TTL = 2592000;

	private int $userId;

	public function __construct(int $userId = 0)
	{
		if (!$userId)
		{
			$userId = \Bitrix\Im\User::getInstance()->getId();
		}

		$this->userId = $userId;
	}

	public function setPause(bool $value = true): self
	{
		static::cleanCache('PAUSE_' . $this->userId);
		UserOptionTable::merge([
			'USER_ID' => $this->userId,
			'PAUSE' => $value ? 'Y' : 'N',
		],
		[
			'PAUSE' => $value ? 'Y' : 'N',
		]);

		return $this;
	}

	public function getPause(): bool
	{
		return $this->getOption('PAUSE') === 'Y';
	}

	private function getOption(string $name): ?string
	{
		$cache = static::getCache($name . '_' . $this->userId);
		$cachedOption = $cache->getVars();
		if ($cachedOption !== false)
		{
			return $cachedOption ? (string)$cachedOption : null;
		}

		$option = UserOptionTable::getRow([
			'select' => [
				$name
			],
			'filter' => [
				'=USER_ID' => $this->userId,
			]
		]);

		if (!$option)
		{
			return null;
		}

		$cache->startDataCache();
		$cache->endDataCache($option[$name]);

		return $option[$name];
	}

	private static function getCache(string $cacheId): Cache
	{
		$cache = Application::getInstance()->getCache();
		$cache->initCache(self::CACHE_TTL, $cacheId, self::CACHE_DIR);

		return $cache;
	}

	private static function cleanCache(string $cacheId): void
	{
		$cache = Application::getInstance()->getCache();
		$cache->clean($cacheId, self::CACHE_DIR);
	}
}
