<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Repository;

use Bitrix\Main\Type\Collection;
use Bitrix\Socialnetwork\Item\Workgroup;
use Bitrix\Tasks\Integration\SocialNetwork\GroupProvider;
use Bitrix\Tasks\Integration\Socialnetwork\Internals\Registry\GroupRegistry;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\Mapper\GroupMapper;
use CSocNetGroup;

class GroupRepository implements GroupRepositoryInterface
{
	public function __construct(
		private readonly FileRepositoryInterface $fileRepository,
		private readonly GroupMapper $groupMapper,
	)
	{

	}

	public function getById(int $id): ?Entity\Group
	{
		$workgroup = GroupRegistry::getInstance()?->get($id);
		if ($workgroup === null)
		{
			return null;
		}

		if ($workgroup->getImageId() > 0)
		{
			$image = $this->fileRepository->getById($workgroup->getImageId());
		}
		else
		{
			$image = new Entity\File(
				src: $workgroup->getAvatarUrl(),
			);
		}

		return $this->groupMapper->mapToEntity($workgroup, $image);
	}

	public function getMembers(int $id): Entity\UserCollection
	{
		$workgroup = GroupRegistry::getInstance()?->get($id);
		if ($workgroup === null)
		{
			return new Entity\UserCollection();
		}

		$members = $workgroup->getMemberIdsWithRole();

		return $this->groupMapper->mapToUserCollection($members);
	}

	public function getType(int $id): ?string
	{
		return GroupProvider::getInstance()?->getGroupType($id)?->value;
	}

	public function getByIds(array $ids): Entity\GroupCollection
	{
		if (empty($ids))
		{
			return new Entity\GroupCollection();
		}

		$result = CSocNetGroup::GetList(
			arFilter: ['@ID' => $ids],
			arSelectFields: ['ID', 'NAME', 'IMAGE_ID', 'AVATAR_TYPE', 'TYPE', 'VISIBLE', 'SITE_IDS'],
		);

		$groups = [];
		while ($group = $result->Fetch())
		{
			$groups[] = $group;
		}

		$imageIds = array_column($groups, 'IMAGE_ID');

		Collection::normalizeArrayValuesByInt($imageIds, false);

		$images = null;
		if (!empty($imageIds))
		{
			$images = $this->fileRepository->getByIds($imageIds);
		}

		$entities = [];
		foreach ($groups as $group)
		{
			$workgroup = new Workgroup($group);
			if ($workgroup->getImageId() > 0)
			{
				$image = $images?->findOneById((int)$group['IMAGE_ID']);
			}
			else
			{
				$image = new Entity\File(
					src: $workgroup->getAvatarUrl(),
				);
			}

			$entity = $this->groupMapper->mapToEntity($workgroup, $image);

			$entities[] = $entity;
		}

		return new Entity\GroupCollection(...$entities);
	}
}
