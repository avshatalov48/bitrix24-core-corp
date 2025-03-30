<?php

declare(strict_types=1);

namespace Bitrix\Booking\Command\Counter;

use Bitrix\Booking\Internals\Integration\Pull\PushEvent;
use Bitrix\Booking\Internals\Integration\Pull\PushService;
use Bitrix\Booking\Internals\Container;
use Bitrix\Booking\Internals\Service\CounterDictionary;
use Bitrix\Booking\Internals\Service\Journal\EventProcessor\PushPull\PushPullCommandType;
use Bitrix\Booking\Internals\Repository\CounterRepositoryInterface;

class DropCounterCommandHandler
{
	private CounterRepositoryInterface $counterRepository;

	public function __construct()
	{
		$this->counterRepository = Container::getCounterRepository();
	}

	public function __invoke(DropCounterCommand $command): void
	{
		match ($command->type)
		{
			CounterDictionary::BookingUnConfirmed,
			CounterDictionary::BookingDelayed => $this->handle($command),
			default => '',
		};
	}

	private function handle(DropCounterCommand $command): void
	{
		$affectedUsers = $this->counterRepository->getUsersByCounterType(
			entityId: $command->entityId,
			type: $command->type,
		);

		if (empty($affectedUsers))
		{
			return;
		}

		foreach ($affectedUsers as $row)
		{
			$userId = (int)$row['USER_ID'];
			$this->counterRepository->down(entityId: $command->entityId, type: $command->type, userId: $userId);
			$total = $this->counterRepository->get($userId, CounterDictionary::Total);

			\CUserCounter::Set(
				$userId,
				CounterDictionary::LeftMenu->value,
				$total,
				'**',
			);
		}

		(new PushService())->sendEvent(
			new PushEvent(
				command: PushPullCommandType::CountersUpdated->value,
				tag: PushPullCommandType::CountersUpdated->getTag(),
				params: [],
				entityId: $command->entityId,
			)
		);
	}
}
