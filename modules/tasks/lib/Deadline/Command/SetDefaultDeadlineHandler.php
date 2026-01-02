<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Command;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Deadline\Command\Trait\SendPushTrait;
use Bitrix\Tasks\Deadline\Internals\Repository\Cache\Managed\CacheDeadlineUserOptionRepository;
use Bitrix\Tasks\Deadline\Internals\Repository\DeadlineUserOptionRepositoryInterface;
use Bitrix\Tasks\Integration\Pull\PushCommand;

class SetDefaultDeadlineHandler
{
	use SendPushTrait;

	private DeadlineUserOptionRepositoryInterface $deadlineUserOptionRepository;

	public function __construct()
	{
		$serviceLocator = ServiceLocator::getInstance();

		$this->deadlineUserOptionRepository = $serviceLocator->get(CacheDeadlineUserOptionRepository::class);
	}

	/**
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	public function __invoke(SetDefaultDeadlineCommand $setDefaultDeadlineCommand): void
	{
		$deadlineUserOption = $this->deadlineUserOptionRepository->getByUserId($setDefaultDeadlineCommand->entity->userId);

		$deadlineUserOption->defaultDeadlineInSeconds = $setDefaultDeadlineCommand->entity->defaultDeadlineInSeconds;
		$deadlineUserOption->isExactDeadlineTime = $setDefaultDeadlineCommand->entity->isExactDeadlineTime;

		$deadlineUserOption->canChangeDeadline = $setDefaultDeadlineCommand->entity->canChangeDeadline;
		$deadlineUserOption->maxDeadlineChangeDate = $setDefaultDeadlineCommand->entity->maxDeadlineChangeDate;
		$deadlineUserOption->maxDeadlineChanges = $setDefaultDeadlineCommand->entity->maxDeadlineChanges;
		$deadlineUserOption->requireDeadlineChangeReason = $setDefaultDeadlineCommand->entity->requireDeadlineChangeReason;

		$this->deadlineUserOptionRepository->save($deadlineUserOption);

		$this->sendPush(
			$setDefaultDeadlineCommand->entity->userId,
			PushCommand::DEFAULT_DEADLINE_UPDATED,
			[
				'deadline' => $setDefaultDeadlineCommand->entity->defaultDeadlineInSeconds,
				'defaultDeadlineDate' => $deadlineUserOption->toArray()['defaultDeadlineDate'],
				'canChangeDeadline' => $setDefaultDeadlineCommand->entity->canChangeDeadline,
				'maxDeadlineChangeDate' => $setDefaultDeadlineCommand->entity->maxDeadlineChangeDate ?: null,
				'maxDeadlineChanges' => $setDefaultDeadlineCommand->entity->maxDeadlineChanges,
				'requireDeadlineChangeReason' => $setDefaultDeadlineCommand->entity->requireDeadlineChangeReason,
			],
		);
	}
}
