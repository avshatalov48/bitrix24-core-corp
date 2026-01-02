<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

class InMemoryUserRepository implements UserRepositoryInterface
{
	private UserRepositoryInterface $userRepository;

	private Entity\UserCollection $cache;
	private ?Entity\UserCollection $adminCache = null;

	public function __construct(UserRepository $userRepository)
	{
		$this->userRepository = $userRepository;
		$this->cache = new Entity\UserCollection();
	}

	public function getByIds(array $userIds): Entity\UserCollection
	{
		$users = Entity\UserCollection::mapFromIds($userIds);
		$stored = $this->cache->findAllByIds($userIds);

		$notStoredIds = $users->diff($stored)->getIdList();

		if (empty($notStoredIds))
		{
			return $stored;
		}

		$users = $this->userRepository->getByIds($notStoredIds);

		$this->cache->merge($users);

		return $this->cache->findAllByIds($userIds);
	}

	public function getAdmins(): Entity\UserCollection
	{
		if ($this->adminCache === null)
		{
			$this->adminCache = $this->userRepository->getAdmins();
		}

		return $this->adminCache;
	}
}