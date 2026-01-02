<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Tasks\V2\Internal\Entity;

class InMemoryGroupRepository implements GroupRepositoryInterface
{
	private GroupRepositoryInterface $groupRepository;

	private Entity\GroupCollection $cache;
	private array $membersCache = [];
	private array $typeCache = [];

	public function __construct(GroupRepository $groupRepository)
	{
		$this->groupRepository = $groupRepository;
		$this->cache = new Entity\GroupCollection();
	}

	public function getById(int $id): ?Entity\Group
	{
		if (!$this->cache->findOneById($id))
		{
			$group = $this->groupRepository->getById($id);
			if ($group !== null)
			{
				$this->cache->add($group);
			}
		}

		return $this->cache->findOneById($id);
	}

	public function getMembers(int $id): Entity\UserCollection
	{
		if (!isset($this->membersCache[$id]))
		{
			$this->membersCache[$id] = $this->groupRepository->getMembers($id);
		}

		return $this->membersCache[$id];
	}

	public function getType(int $id): ?string
	{
		$group = $this->cache->findOneById($id);
		if ($group !== null)
		{
			$this->typeCache[$id] = $group->type;

			return $this->typeCache[$id];
		}

		if (!isset($this->typeCache[$id]))
		{
			$this->typeCache[$id] = $this->groupRepository->getType($id);
		}

		return $this->typeCache[$id];
	}

	public function getByIds(array $ids): Entity\GroupCollection
	{
		$groups = Entity\GroupCollection::mapFromIds($ids);
		$stored = $this->cache->findAllByIds($ids);

		$notStoredIds = $groups->diff($stored)->getIdList();

		if (empty($notStoredIds))
		{
			return $stored;
		}

		$groups = $this->groupRepository->getByIds($ids);

		$this->cache->merge($groups);

		return $groups;
	}
}
