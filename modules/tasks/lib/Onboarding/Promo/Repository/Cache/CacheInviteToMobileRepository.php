<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Promo\Repository\Cache;

use Bitrix\Main\Data\Cache;
use Bitrix\Tasks\Onboarding\Promo\Repository\InviteToMobileRepository;
use Bitrix\Tasks\Onboarding\Promo\Repository\InviteToMobileRepositoryInterface;

class CacheInviteToMobileRepository implements InviteToMobileRepositoryInterface
{
	private const TTL = 86400 * 30; // 30 days
	private const PATH = 'tasks/onboarding_user_option';
	private const ID_PREFIX = 'need_to_show_invite_to_mobile_';

	private InviteToMobileRepository $repository;

	public function __construct(InviteToMobileRepository $repository)
	{
		$this->repository = $repository;
	}

	public function needToShow(int $userId): ?bool
	{
		$cache = Cache::createInstance();
		$cacheId = $this->getCacheId($userId);

		if ($cache->initCache(self::TTL, $cacheId, self::PATH))
		{
			$needToShowData = $cache->getVars();
			if (!is_array($needToShowData))
			{
				return null;
			}

			return $needToShowData['needToShow'] ?? null;
		}

		$needToShow = $this->repository->needToShow($userId);

		$cache->startDataCache(self::TTL, $cacheId, self::PATH);
		$cache->endDataCache(['needToShow' => $needToShow]);

		return $needToShow;
	}

	public function setNeedToShow(int $userId): void
	{
		$cache = Cache::createInstance();
		$cacheId = $this->getCacheId($userId);

		$cache->clean($cacheId, self::PATH);

		$this->repository->setNeedToShow($userId);

		$cache->startDataCache(self::TTL, $cacheId, self::PATH);
		$cache->endDataCache(['needToShow' => true]);
	}

	public function setShown(int $userId): void
	{
		$cache = Cache::createInstance();
		$cacheId = $this->getCacheId($userId);

		$cache->clean($cacheId, self::PATH);

		$this->repository->setShown($userId);

		$cache->startDataCache(self::TTL, $cacheId, self::PATH);
		$cache->endDataCache(['needToShow' => false]);
	}

	private function getCacheId(int $userId): string
	{
		return self::ID_PREFIX . $userId;
	}
}
