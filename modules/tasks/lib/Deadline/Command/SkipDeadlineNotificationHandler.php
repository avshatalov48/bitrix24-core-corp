<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Deadline\Command;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Deadline\Command\Trait\SendPushTrait;
use Bitrix\Tasks\Deadline\Internals\Repository\Cache\Managed\CacheDeadlineUserOptionRepository;
use Bitrix\Tasks\Deadline\Internals\Repository\DeadlineUserOptionRepositoryInterface;
use Bitrix\Tasks\Integration\Pull\PushCommand;

class SkipDeadlineNotificationHandler
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
	public function __invoke(SkipDeadlineNotificationCommand $command): void
	{
		$deadlineUserOption = $this->deadlineUserOptionRepository->getByUserId($command->entity->userId);

		$deadlineUserOption->skipNotificationPeriod = $command->entity->skipNotificationPeriod;
		$deadlineUserOption->skipNotificationStartDate = new DateTime();

		$this->deadlineUserOptionRepository->save($deadlineUserOption);

		$this->sendPush(
			$command->entity->userId,
			PushCommand::SKIP_DEADLINE_NOTIFICATION_PERIOD_UPDATED,
			['period' => $command->entity->skipNotificationPeriod],
		);
	}
}
