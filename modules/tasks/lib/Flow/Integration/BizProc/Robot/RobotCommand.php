<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc\Robot;

use Bitrix\Bizproc\Automation\Engine\Robot;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Flow\Control\AbstractCommand;

class RobotCommand extends AbstractCommand
{
	public function __construct(
		public readonly string $title,
		public readonly int $status,
		public ?Condition $condition = null,
	)
	{

	}
	public function toArray(bool $withDefault = true): array
	{
		$robot = [
			'Type' => 'TasksChangeStatusActivity',
			'Name' => Robot::generateName(),
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