<?php

namespace Bitrix\Crm\Activity\Provider\Tasks;

use Bitrix\Crm\Service\Timeline\Layout\Header\Tag;
use Bitrix\Main\Localization\Loc;
class TaskActivityStatus
{
	public const STATUS_CREATED = 'CREATED';
	public const STATUS_VIEWED = 'VIEWED';
	public const STATUS_UPDATED = 'UPDATED';
	public const STATUS_IN_PROGRESS = 'INPROGRESS';
	public const STATUS_WAITING = 'WAITING';
	public const STATUS_DEADLINE_CHANGED = 'DEADLINECHANGED';
	public const STATUS_RESULT_ADDED = 'RESULTADDED';
	public const STATUS_EXPIRED = 'EXPIRED';
	public const STATUS_CONTROL_WAITING = 'CONTROLWAITING';
	public const STATUS_FINISHED = 'FINISHED';

	public const STATUSES_MANAGER_CAN_UPDATE = [
		self::STATUS_EXPIRED,
		self::STATUS_FINISHED,
		self::STATUS_WAITING,
		self::STATUS_IN_PROGRESS,
		self::STATUS_DEADLINE_CHANGED
	];


	// STATUSES FROM c_tasks
	const TASKS_STATE_PENDING = 2;    // Pending === Accepted
	const TASKS_STATE_IN_PROGRESS = 3;
	const TASKS_STATE_SUPPOSEDLY_COMPLETED = 4;
	const TASKS_STATE_COMPLETED = 5;

	public function onStatusChange(int $currStatus, bool $expired = false): string
	{
		switch ($currStatus)
		{
			case self::TASKS_STATE_PENDING:
				return ($expired === true) ? self::STATUS_EXPIRED : self::STATUS_WAITING;
			case self::TASKS_STATE_COMPLETED:
				return self::STATUS_FINISHED;
			case self::TASKS_STATE_IN_PROGRESS:
				return self::STATUS_IN_PROGRESS;
			case self::TASKS_STATE_SUPPOSEDLY_COMPLETED:
				return self::STATUS_CONTROL_WAITING;
		}

		return '';
	}

	public function getStatusLocMessage(string $status): string
	{
		return $this->getSettings()[$status]['loc'] ?? '';
	}

	public function getIcon(string $status): string
	{
		return $this->getSettings()[$status]['icon'] ?? '';
	}

	public function isAllowedStatusChange(string $desiredStatus, string $currentStatus): bool
	{
		if (!$this->isStatusValid($desiredStatus) || !$this->isStatusValid($currentStatus))
		{
			return false;
		}

		if ($desiredStatus === $currentStatus)
		{
			return false;
		}

		$allowedNextSteps = $this->getSettings()[$currentStatus]['next'];
		return in_array($desiredStatus, $allowedNextSteps, true);
	}

	public function isStatusValid(string $status): bool
	{
		return isset($this->getSettings()[$status]);
	}

	private function getSettings(): array
	{
		return [
			self::STATUS_CREATED => [
				'next' => [
					self::STATUS_VIEWED,
					self::STATUS_UPDATED,
					self::STATUS_WAITING,
					self::STATUS_IN_PROGRESS,
					self::STATUS_DEADLINE_CHANGED,
					self::STATUS_RESULT_ADDED,
					self::STATUS_EXPIRED,
					self::STATUS_CONTROL_WAITING,
					self::STATUS_FINISHED,
				],
				'loc' => Loc::getMessage('TASKS_TASK_INTEGRATION_STATUS_' . self::STATUS_CREATED),
				'icon' => Tag::TYPE_SECONDARY,
			],
			self::STATUS_VIEWED => [
				'next' => [
					self::STATUS_UPDATED,
					self::STATUS_WAITING,
					self::STATUS_IN_PROGRESS,
					self::STATUS_DEADLINE_CHANGED,
					self::STATUS_RESULT_ADDED,
					self::STATUS_EXPIRED,
					self::STATUS_CONTROL_WAITING,
					self::STATUS_FINISHED,
				],
				'loc' => Loc::getMessage('TASKS_TASK_INTEGRATION_STATUS_' . self::STATUS_VIEWED),
				'icon' => Tag::TYPE_PRIMARY,
			],
			self::STATUS_UPDATED => [
				'next' => [
					self::STATUS_WAITING,
					self::STATUS_IN_PROGRESS,
					self::STATUS_DEADLINE_CHANGED,
					self::STATUS_RESULT_ADDED,
					self::STATUS_EXPIRED,
					self::STATUS_CONTROL_WAITING,
					self::STATUS_FINISHED,
				],
				'loc' => Loc::getMessage('TASKS_TASK_INTEGRATION_STATUS_' . self::STATUS_UPDATED),
				'icon' => Tag::TYPE_SECONDARY,
			],
			self::STATUS_IN_PROGRESS => [
				'next' => [
					self::STATUS_UPDATED,
					self::STATUS_IN_PROGRESS,
					self::STATUS_WAITING,
					self::STATUS_DEADLINE_CHANGED,
					self::STATUS_RESULT_ADDED,
					self::STATUS_EXPIRED,
					self::STATUS_CONTROL_WAITING,
					self::STATUS_FINISHED,
				],
				'loc' => Loc::getMessage('TASKS_TASK_INTEGRATION_STATUS_' . self::STATUS_IN_PROGRESS),
				'icon' => Tag::TYPE_PRIMARY,
			],
			self::STATUS_WAITING => [
				'next' => [
					self::STATUS_UPDATED,
					self::STATUS_IN_PROGRESS,
					self::STATUS_WAITING,
					self::STATUS_DEADLINE_CHANGED,
					self::STATUS_RESULT_ADDED,
					self::STATUS_EXPIRED,
					self::STATUS_CONTROL_WAITING,
					self::STATUS_FINISHED,
				],
				'loc' => Loc::getMessage('TASKS_TASK_INTEGRATION_STATUS_' . self::STATUS_WAITING),
				'icon' => Tag::TYPE_PRIMARY,
			],
			self::STATUS_DEADLINE_CHANGED => [
				'next' => [
					self::STATUS_IN_PROGRESS,
					self::STATUS_WAITING,
					self::STATUS_DEADLINE_CHANGED,
					self::STATUS_RESULT_ADDED,
					self::STATUS_EXPIRED,
					self::STATUS_CONTROL_WAITING,
					self::STATUS_FINISHED,
				],
				'loc' => Loc::getMessage('TASKS_TASK_INTEGRATION_STATUS_' . self::STATUS_DEADLINE_CHANGED),
				'icon' => Tag::TYPE_PRIMARY,
			],
			self::STATUS_RESULT_ADDED => [
				'next' => [
					self::STATUS_IN_PROGRESS,
					self::STATUS_WAITING,
					self::STATUS_DEADLINE_CHANGED,
					self::STATUS_RESULT_ADDED,
					self::STATUS_EXPIRED,
					self::STATUS_CONTROL_WAITING,
					self::STATUS_FINISHED,
				],
				'loc' => Loc::getMessage('TASKS_TASK_INTEGRATION_STATUS_' . self::STATUS_RESULT_ADDED),
				'icon' => Tag::TYPE_PRIMARY,
			],
			self::STATUS_EXPIRED => [
				'next' => [
					self::STATUS_CONTROL_WAITING,
					self::STATUS_FINISHED,
					self::STATUS_DEADLINE_CHANGED,
				],
				'loc' => Loc::getMessage('TASKS_TASK_INTEGRATION_STATUS_' . self::STATUS_EXPIRED),
				'icon' => Tag::TYPE_FAILURE,
			],
			self::STATUS_CONTROL_WAITING => [
				'next' => [
					self::STATUS_WAITING,
					self::STATUS_IN_PROGRESS,
					self::STATUS_EXPIRED,
					self::STATUS_FINISHED,
				],
				'loc' => Loc::getMessage('TASKS_TASK_INTEGRATION_STATUS_' . self::STATUS_CONTROL_WAITING),
				'icon' => Tag::TYPE_PRIMARY,
			],
			self::STATUS_FINISHED => [
				'next' => [
					self::STATUS_WAITING,
				],
				'loc' => Loc::getMessage('TASKS_TASK_INTEGRATION_STATUS_' . self::STATUS_FINISHED),
				'icon' => Tag::TYPE_SUCCESS,
			],
		];
	}
}