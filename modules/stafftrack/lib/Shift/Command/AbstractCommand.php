<?php

namespace Bitrix\StaffTrack\Shift\Command;

use Bitrix\Main\Result;
use Bitrix\Stafftrack\Integration\Pull;
use Bitrix\StaffTrack\Item\Department;
use Bitrix\StaffTrack\Service\UserService;
use Bitrix\StaffTrack\Shift\Observer\ObserverInterface;
use Bitrix\StaffTrack\Shift\ShiftDto;
use Bitrix\StaffTrack\Shift\ShiftMapper;

abstract class AbstractCommand
{
	protected ShiftMapper $mapper;
	protected ShiftDto $shiftDto;
	protected array $observers = [];

	public function __construct()
	{
		$this->init();
	}

	abstract public function execute(ShiftDto $shiftDto): Result;

	protected function init(): void
	{
		$this->mapper = new ShiftMapper();
	}

	protected function addObserver(ObserverInterface $observer): self
	{
		$this->observers[] = $observer;

		return $this;
	}

	protected function notify(ObserverInterface ...$observers): void
	{
		foreach ($observers as $observer)
		{
			$observer->update($this->shiftDto);
		}
	}

	protected function sendPushToDepartment(Pull\PushCommand $command): void
	{
		$user = UserService::getInstance()->getUser($this->shiftDto->userId);
		if ($user === null)
		{
			return;
		}

		$departmentIds = array_map(static fn (Department $department) => $department->id, $user->departments->getValues());

		foreach ($departmentIds as $departmentId)
		{
			Pull\PushService::sendByTag(
				Pull\Tag::getDepartmentTag($departmentId),
				$command,
				[
					'shift' => $this->shiftDto->toArray(),
					'departmentIds' => $departmentIds,
				],
			);
		}

		Pull\PushService::sendByTag(
			Pull\Tag::getUserTag($this->shiftDto->userId),
			$command,
			[
				'shift' => $this->shiftDto->toArray(),
				'departmentIds' => $departmentIds,
			],
		);
	}
}
