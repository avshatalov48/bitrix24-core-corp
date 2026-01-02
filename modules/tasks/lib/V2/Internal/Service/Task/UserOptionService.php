<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Tasks\Integration\Pull\PushCommand;
use Bitrix\Tasks\Internals\Counter\Event\EventDictionary;
use Bitrix\Tasks\Internals\UserOption\Option;
use Bitrix\Tasks\V2\Internal\Entity\UserCollection;
use Bitrix\Tasks\V2\Internal\Repository\TaskUserOptionRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Entity\Task\UserOption;
use Bitrix\Tasks\V2\Internal\Service\CounterService;
use Bitrix\Tasks\V2\Internal\Service\EventService;
use Bitrix\Tasks\V2\Internal\Service\PushService;

class UserOptionService
{
	public function __construct(
		private readonly TaskUserOptionRepositoryInterface $userOptionRepository,
		private readonly PushService                       $pushService,
		private readonly CounterService                    $counterService,
		private readonly EventService                      $eventService,
	)
	{

	}

	public function pin(int $taskId, int $userId): void
	{
		$userOption = new UserOption(
			userId: $userId,
			taskId: $taskId,
			code: Option::PINNED,
		);

		$this->add($userOption);
	}

	public function unpin(int $taskId, int $userId): void
	{
		$userOption = new UserOption(
			userId: $userId,
			taskId: $taskId,
			code: Option::PINNED,
		);

		$this->delete($userOption);
	}

	public function pinInGroup(int $taskId, int $userId): void
	{
		$userOption = new UserOption(
			userId: $userId,
			taskId: $taskId,
			code: Option::PINNED_IN_GROUP,
		);

		$this->add($userOption);
	}

	public function unpinInGroup(int $taskId, int $userId): void
	{
		$userOption = new UserOption(
			userId: $userId,
			taskId: $taskId,
			code: Option::PINNED_IN_GROUP,
		);

		$this->delete($userOption);
	}

	public function mute(int $taskId, int $userId): void
	{
		$entity = new UserOption(
			userId: $userId,
			taskId: $taskId,
			code: Option::MUTED,
		);

		$this->counterService->collect($entity->userId);

		$this->add($entity);

		$this->counterService->addEvent(
			type: EventDictionary::EVENT_AFTER_TASK_MUTE,
			parameters: [
				'TASK_ID' => $entity->taskId,
				'USER_ID' => $entity->userId,
				'ADDED' => true,
			]
		);
	}

	public function unmute(int $taskId, int $userId): void
	{
		$entity = new UserOption(
			userId: $userId,
			taskId: $taskId,
			code: Option::MUTED,
		);

		$this->counterService->collect($entity->userId);

		$this->delete($entity);

		$this->counterService->addEvent(
			type: EventDictionary::EVENT_AFTER_TASK_MUTE,
			parameters: [
				'TASK_ID' => $entity->taskId,
				'USER_ID' => $entity->userId,
				'ADDED' => false,
			]
		);
	}

	private function add(UserOption $userOption): void
	{
		// Prevent re-sending events if the option is already set. Moved from \Bitrix\Tasks\Internals\UserOption::add
		if ($this->userOptionRepository->isSet($userOption->code, $userOption->taskId, $userOption->userId))
		{
			return;
		}

		$this->userOptionRepository->add($userOption);

		$this->pushService->addEventByParameters(
			recipients: UserCollection::mapFromIds([$userOption->userId]),
			command: PushCommand::USER_OPTION_UPDATED,
			parameters: [
				'TASK_ID' => $userOption->taskId,
				'USER_ID' => $userOption->userId,
				'OPTION' => $userOption->code,
				'ADDED' => true,
			]
		);

		$this->eventService->send(
			type: 'onTaskUserOptionChanged',
			parameters: [
				'taskId' => $userOption->taskId,
				'userId' => $userOption->userId,
				'option' => $userOption->code,
				'added' => true,
			]
		);
	}

	private function delete(UserOption $userOption): void
	{
		// Prevent re-sending events if the option is already deleted. Moved from \Bitrix\Tasks\Internals\UserOption::delete
		if (!$this->userOptionRepository->isSet($userOption->code, $userOption->taskId, $userOption->userId))
		{
			return;
		}

		$this->userOptionRepository->delete([$userOption->code], $userOption->taskId, $userOption->userId);

		$this->pushService->addEventByParameters(
			recipients: UserCollection::mapFromIds([$userOption->userId]),
			command: PushCommand::USER_OPTION_UPDATED,
			parameters: [
				'TASK_ID' => $userOption->taskId,
				'USER_ID' => $userOption->userId,
				'OPTION' => $userOption->code,
				'ADDED' => true,
			]
		);

		$this->eventService->send(
			type: 'onTaskUserOptionChanged',
			parameters: [
				'taskId' => $userOption->taskId,
				'userId' => $userOption->userId,
				'option' => $userOption->code,
				'added' => false,
			]
		);
	}
}
