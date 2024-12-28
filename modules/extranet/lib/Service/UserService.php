<?php

namespace Bitrix\Extranet\Service;

use Bitrix\Extranet\Enum\User\ExtranetRole;
use Bitrix\Extranet\Entity;
use Bitrix\Extranet\Contract;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Result;

class UserService implements Contract\Service\UserService
{
	private Contract\Repository\ExtranetUserRepository $extranetUserRepository;
	private Cache $cache;
	private const BASE_CACHE_DIR = 'extranet/user/';
	private const CACHE_TTL = 86400;

	public function __construct(?Contract\Repository\ExtranetUserRepository $userRepository = null)
	{
		$this->extranetUserRepository = $userRepository ?? ServiceContainer::getInstance()->getExtranetUserRepository();
		$this->cache = Application::getInstance()->getCache();
	}

	public function setRoleById(int $id, ExtranetRole $role): Result
	{
		if ($role === ExtranetRole::Collaber)
		{
			return ServiceContainer::getInstance()->getCollaberService()->setCollaberRoleByUserId($id);
		}

		return $this->extranetUserRepository->upsert(
			new Entity\ExtranetUser(userId: $id, role: $role->value),
		);
	}

	public function isCurrentExtranetUserById(int $userId): bool
	{
		return in_array($userId, $this->getCurrentExtranetUserIds(), true);
	}

	public function isFormerExtranetUserById(int $userId): bool
	{
		return in_array($userId, $this->getFormerExtranetUserIds(), true);
	}

	public function getCurrentExtranetUserIds(): array
	{
		if ($this->cache->initCache(self::CACHE_TTL, 'current_extranet_user_ids', self::BASE_CACHE_DIR))
		{
			$ids = $this->cache->getVars();
		}
		else
		{
			$ids = $this->extranetUserRepository->getAllUserIdsByRoles(ExtranetRole::getCurrentExtranetRole());

			if ($this->cache->startDataCache())
			{
				$this->cache->endDataCache($ids);
			}
		}

		return $ids;
	}

	public function getUserIdsByRole(ExtranetRole $role): array
	{
		if ($this->cache->initCache(self::CACHE_TTL, 'extranet_user_ids_' . $role->value, self::BASE_CACHE_DIR))
		{
			$ids = $this->cache->getVars();
		}
		else
		{
			$ids = $this->extranetUserRepository->getAllUserIdsByRole($role);

			if ($this->cache->startDataCache())
			{
				$this->cache->endDataCache($ids);
			}
		}

		return $ids;
	}

	public function getFormerExtranetUserIds(): array
	{
		if ($this->cache->initCache(self::CACHE_TTL, 'former_extranet_user_ids', self::BASE_CACHE_DIR))
		{
			$ids = $this->cache->getVars();
		}
		else
		{
			$ids = $this->extranetUserRepository->getAllUserIdsByRoles(ExtranetRole::getFormerExtranetRole());

			if ($this->cache->startDataCache())
			{
				$this->cache->endDataCache($ids);
			}
		}

		return $ids;
	}

	public function deleteById(int $userId): Result
	{
		return $this->extranetUserRepository->deleteByUserId($userId);
	}

	public function clearCache(): void
	{
		$this->cache->cleanDir(self::BASE_CACHE_DIR);
	}
}
