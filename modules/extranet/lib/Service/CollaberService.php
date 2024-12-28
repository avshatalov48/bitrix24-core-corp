<?php

namespace Bitrix\Extranet\Service;

use Bitrix\Extranet\Contract;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Extranet\Enum;
use Bitrix\Extranet\Entity;
use Bitrix\Main;

class CollaberService implements Contract\Service\CollaberService
{
	private Contract\Repository\ExtranetUserRepository $extranetUserRepository;
	private Main\Data\Cache $cache;
	private const BASE_CACHE_DIR = 'extranet/collaber/';
	private const CACHE_TTL = 86400;

	public function __construct(?Contract\Repository\ExtranetUserRepository $userRepository = null)
	{
		$this->extranetUserRepository = $userRepository ?? ServiceContainer::getInstance()->getExtranetUserRepository();
		$this->cache = Application::getInstance()->getCache();
	}

	public function isCollaberById(int $id): bool
	{
		return in_array($id, $this->getCollaberIds(), true);
	}

	public function setCollaberRoleByUserId(int $id): Result
	{
		$extranetUserEntity = $this->extranetUserRepository->getByUserId($id);

		if ($extranetUserEntity === null)
		{
			return $this->extranetUserRepository->add(
				new Entity\ExtranetUser(userId: $id, role: Enum\User\ExtranetRole::Collaber->value),
			);
		}

		if ($extranetUserEntity->getRole() === Enum\User\ExtranetRole::Collaber)
		{
			return (new Result())->setData([$extranetUserEntity]);
		}

		if (
			$extranetUserEntity->getRole() !== Enum\User\ExtranetRole::FormerCollaber
			&& $extranetUserEntity->getRole() !== Enum\User\ExtranetRole::Extranet
		)
		{
			return (new Result())->addError(new Error('User doesn`t have valid type'));
		}

		return $this->extranetUserRepository->update(
			new Entity\ExtranetUser(userId: $id, role: Enum\User\ExtranetRole::Collaber->value),
		);
	}

	public function removeCollaberRoleByUserId(int $id): Result
	{
		$extranetUserEntity = $this->extranetUserRepository->getByUserId($id);

		if ($extranetUserEntity === null)
		{
			return (new Result())->addError(new Error('User not found'));
		}

		if ($extranetUserEntity->getRole() !== Enum\User\ExtranetRole::Collaber)
		{
			return (new Result())->setData([$extranetUserEntity]);
		}

		return $this->extranetUserRepository
			->update(new Entity\ExtranetUser(userId: $id, role: Enum\User\ExtranetRole::FormerCollaber->value)
		);
	}

	public function getCollaberCollection(int $limit = 100): Entity\Collection\ExtranetUserCollection
	{
		return $this->extranetUserRepository->getAllByRole(Enum\User\ExtranetRole::Collaber, $limit);
	}

	public function getCollaberIds(): array
	{
		if ($this->cache->initCache(self::CACHE_TTL, 'collaber_ids', self::BASE_CACHE_DIR))
		{
			$collaberIds = $this->cache->getVars();
		}
		else
		{
			$collaberIds = $this->extranetUserRepository->getAllUserIdsByRole(Enum\User\ExtranetRole::Collaber);

			if ($this->cache->startDataCache())
			{
				$this->cache->endDataCache($collaberIds);
			}
		}

		return $collaberIds;
	}

	public function clearCache(): void
	{
		$this->cache->cleanDir(self::BASE_CACHE_DIR);
	}
}
