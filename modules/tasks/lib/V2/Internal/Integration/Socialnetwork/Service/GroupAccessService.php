<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\Socialnetwork\Service;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\Collection;
use Bitrix\SocialNetwork\Collab\Access\CollabDictionary;
use Bitrix\Socialnetwork\Permission\GroupDictionary;
use Bitrix\Tasks\V2\Internal\Access\Factory\ControllerFactoryInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Entity\Group;
use Bitrix\Tasks\V2\Internal\Entity\GroupCollection;
use Bitrix\Tasks\V2\Internal\Repository\GroupRepositoryInterface;

class GroupAccessService
{
	public function __construct(
		private readonly ControllerFactoryInterface $controllerFactory,
		private readonly GroupRepositoryInterface $groupRepository,
		private readonly RegistryPreloadService $registryPreloadService,
	)
	{

	}
	public function getWithViewAccess(array $groupIds, int $userId): GroupCollection
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return new GroupCollection();
		}

		Collection::normalizeArrayValuesByInt($groupIds, false);
		if (empty($groupIds))
		{
			return new GroupCollection();
		}

		$this->registryPreloadService->preload($groupIds);

		return $this->groupRepository->getByIds($groupIds)->filter(
			fn (Group $group): bool => $this->canViewGroup($userId, $group)
		);
	}

	public function canViewGroup(int $userId, Group $group): bool
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return false;
		}

		$type = $group->type === 'collab' ? Type::Collab : Type::Group;
		$rule = $group->type === 'collab' ? CollabDictionary::VIEW : GroupDictionary::VIEW;

		$accessController = $this->controllerFactory->create($type, $userId);

		return (bool)$accessController?->checkByItemId($rule, $group->id);
	}
}
