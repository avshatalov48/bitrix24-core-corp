<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Link;

use Bitrix\Tasks\V2\Internal\Entity\EntityInterface;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\UserRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Service\UrlService;
use Bitrix\Tasks\V2\Internal\Service\UserService;
use CTaskNotifications;

class LinkService
{
	public function __construct(
		private readonly UserService $userService,
		private readonly UrlService $urlService,
		private readonly UserRepositoryInterface $userRepository,
	)
	{

	}

	public function getPublic(int $taskId): string
	{
		return \Bitrix\Tasks\UI\Task::makeActionUrl('/pub/task.php?task_id=#task_id#', $taskId);
	}

	public function getWithServer(int $taskId, int $userId): string
	{
		$user = $this->userRepository->getByIds([$userId])->findOneById($userId);
		if ($user === null)
		{
			return '';
		}

		if ($this->userService->isEmail($user))
		{
			return $this->urlService->getHostUrl() . $this->getPublic($taskId);
		}

		return (string)CTaskNotifications::GetNotificationPath(['ID' => $userId], $taskId);
	}

	public function getCreateTask(int $userId = 0, int $groupId = 0): string
	{
		return $this->getEditTask(0, $userId, $groupId);
	}

	public function getEditTask(int $taskId = 0, int $userId = 0, int $groupId = 0): string
	{
		$parameters = [
			'entityId' => $taskId,
			'entityType' => 'task',
			'action' => 'edit',
		];

		if ($groupId > 0)
		{
			$parameters['context'] = 'group';
			$parameters['ownerId'] = $groupId;
		}
		else
		{
			$parameters['ownerId'] = $userId;
		}

		return LinkBuilderFactory::getInstance()
			->create(...$parameters)
			?->makeEntityPath()
		;
	}

	public function get(EntityInterface $entity, int $userId = 0): ?string
	{
		$parameters = [
			'entityId' => (int)$entity->getId(),
			'ownerId' => $userId,
		];

		if ($entity instanceof Entity\Task)
		{
			$parameters['entityType'] = 'task';
			if ($entity->group?->id > 0)
			{
				$parameters['context'] = 'group';
				$parameters['ownerId'] = $entity->group->id;
			}
		}
		elseif ($entity instanceof Entity\Template)
		{
			$parameters['entityType'] = 'template';
		}

		return LinkBuilderFactory::getInstance()
			->create(...$parameters)
			?->makeEntityPath();
	}
}
