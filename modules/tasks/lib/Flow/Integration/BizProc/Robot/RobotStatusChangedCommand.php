<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc\Robot;

class RobotStatusChangedCommand extends AbstractRobotCommand
{
	public function __construct(
		public readonly string $title,
		public readonly int $status,
		public ?Condition $condition = null,
	)
	{

	}

	public function isUserSensitive(): bool
	{
		return false;
	}

	public function toArray(bool $withDefault = true): array
	{
		$robot = [
			'Type' => 'TasksChangeStatusActivity',
			'Name' => $this->getName(),
			'Properties' => [
				'TargetStatus' => $this->status,
				'Title' => $this->title,
			],
			'Activated' => 'Y',
		];

		if (null !== $this->condition)
		{
			$robot['Condition'] = $this->condition->toArray();
		}

		return $robot;
	}
}