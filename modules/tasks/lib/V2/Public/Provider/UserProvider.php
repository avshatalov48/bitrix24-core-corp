<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Provider;

use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\V2\Internal\Access\Service\TaskRightService;
use Bitrix\Tasks\V2\Internal\Entity\User;
use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Integration\Socialnetwork\Service\OperationAccessService;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Public\Provider\Params\UserParams;

class UserProvider
{
	public function __construct(
		private readonly OperationAccessService $operationAccessService,
		private readonly TaskRightService $taskRightService,
		private readonly UserRepositoryInterface $userRepository,
	)
	{

	}

	public function getByIds(UserParams $userParams): UserCollection
	{
		$userIds = $userParams->targetUserIds;

		Collection::normalizeArrayValuesByInt($userIds, false);

		if ($userParams->checkAccess)
		{
			$userIds = $this->operationAccessService->filterUsersWhoCanViewProfile(
				$userParams->userId,
				$userIds,
			);
		}

		$users = $this->userRepository->getByIds($userIds);

		return $this->prepareUserRights($userParams, $users);
	}

	private function prepareUserRights(UserParams $userParams, UserCollection $users): UserCollection
	{
		if (!$userParams->withRights)
		{
			return $users;
		}

		$current = $users->findOneById($userParams->userId);
		if ($current === null)
		{
			return $users;
		}

		$rights = $this->taskRightService->getUserRights($current->id);

		$current = $current->cloneWith(['rights' => ['tasks' => $rights]]);

		$users = $users->filter(static fn(User $user): bool => $user->getId() !== $current->getId());

		$users->add($current);

		return $users;
	}
}
