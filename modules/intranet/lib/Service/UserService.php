<?php

namespace Bitrix\Intranet\Service;

use Bitrix\Intranet\Contract;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;

class UserService
{
	private Contract\Repository\UserRepository $intranetUserRepository;
	private Cache $cache;
	private const BASE_CACHE_DIR = 'intranet/user/';
	private const CACHE_TTL = 86400;

	public function __construct(?Contract\Repository\UserRepository $userRepository = null)
	{
		$this->intranetUserRepository = $userRepository ?? ServiceContainer::getInstance()->userRepository();
		$this->cache = Application::getInstance()->getCache();
	}

	public function getAdminUserIds(): array
	{
		if ($this->cache->initCache(self::CACHE_TTL, 'admin_id_list', self::BASE_CACHE_DIR))
		{
			$ids = $this->cache->getVars();
		}
		else
		{
			$ids = $this->intranetUserRepository
				->findUsersByUserGroup(1)
				->getIds();

			if ($this->cache->startDataCache())
			{
				$this->cache->endDataCache($ids);
			}
		}

		return $ids;
	}

	public function getIntegratorUserIds(): array
	{
		if ($this->cache->initCache(self::CACHE_TTL, 'integrator_id_list', self::BASE_CACHE_DIR))
		{
			$ids = $this->cache->getVars();
		}
		else
		{
			$ids = [];

			if (Loader::includeModule('bitrix24'))
			{
				$ids = $this->intranetUserRepository
					->findUsersByUserGroup(\CBitrix24::getIntegratorGroupId())
					->getIds();
			}

			if ($this->cache->startDataCache())
			{
				$this->cache->endDataCache($ids);
			}
		}

		return $ids;
	}

	public function clearCache(): void
	{
		$this->cache->cleanDir(self::BASE_CACHE_DIR);
	}
}