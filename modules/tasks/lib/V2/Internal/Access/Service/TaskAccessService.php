<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Access\Service;

use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\V2\Internal\Access\Factory\ControllerFactoryInterface;
use Bitrix\Tasks\V2\Internal\Access\Factory\Type;
use Bitrix\Tasks\V2\Internal\Integration\Socialnetwork\Service\OperationAccessService;
use Bitrix\Tasks\V2\Internal\Repository\GroupRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\TaskRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Entity;

class TaskAccessService
{
	public function __construct(
		private readonly TaskRepositoryInterface    $taskRepository,
		private readonly GroupRepositoryInterface   $groupRepository,
		private readonly UserRepositoryInterface    $userRepository,
		private readonly OperationAccessService           $operationService,
		private readonly ControllerFactoryInterface $controllerFactory,
	)
	{

	}

	// TODO: Move this code closer to where it's actually used. For chat integration.
	public function filterUsersWithAccess(int $taskId, array $userIds): array
	{
		$task = $this->taskRepository->getById($taskId);
		if ($task === null)
		{
			return [];
		}

		Collection::normalizeArrayValuesByInt($userIds);

		$memberIds = $task->getMemberIds();

		if ($memberIds === $userIds)
		{
			return $userIds;
		}

		$withAccess = $this->getAdminAccess($memberIds, $userIds);
		$checkAccess = array_diff($userIds, $withAccess);

		if (empty($checkAccess))
		{
			return $withAccess;
		}

		if ($task->group !== null)
		{
			$withAccess = $this->getGroupAccess($task, $withAccess);
			$checkAccess = array_diff($userIds, $withAccess);

			if (empty($checkAccess))
			{
				return $withAccess;
			}
		}

		return $this->getDepartmentAccess($taskId, $withAccess, $checkAccess);
	}

	private function getAdminAccess(array $memberIds, array $userIds): array
	{
		$withAccess = $memberIds;
		$checkAccess = array_diff($userIds, $memberIds);

		if (empty($checkAccess))
		{
			return $withAccess;
		}

		$accessViaAdminRights = $this->userRepository->getAdmins()->getIds();

		return array_merge($withAccess, $accessViaAdminRights);
	}

	private function getGroupAccess($task, array $withAccess): array
	{
		$members = $this->groupRepository->getMembers($task->group->id);
		$members = array_map(
			static fn (Entity\User $user): array => [$user->id => $user->role],
			iterator_to_array($members)
		);

		$accessViaGroup = $this->operationService->filterUsersWithAccess(
			$task->group->id,
			$members,
			SONET_ENTITY_GROUP,
			'tasks',
			'view_all'
		);

		return array_merge($withAccess, $accessViaGroup);
	}

	private function getDepartmentAccess(int $taskId, array $withAccess, array $checkAccess): array
	{
		$accessViaDepartment = [];
		foreach ($checkAccess as $userId)
		{
			$controller = $this->controllerFactory->create(Type::Task, $userId);
			if ($controller === null)
			{
				return $withAccess;
			}

			if ($controller->checkByItemId(ActionDictionary::ACTION_TASK_DEPARTMENT, $taskId))
			{
				$accessViaDepartment[] = $userId;
			}
		}

		return array_merge($withAccess, $accessViaDepartment);
	}
}
