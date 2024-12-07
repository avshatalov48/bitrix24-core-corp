<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc\Robot;

use Bitrix\Tasks\Internals\Attribute\NotEmpty;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;

class RobotAddAccompliceCommand extends AbstractRobotCommand
{
	public function __construct(
		#[NotEmpty]
		public readonly string $title,
		#[PositiveNumber]
		public readonly int $accomplice,
	)
	{

	}

	public function isUserSensitive(): bool
	{
		return true;
	}

	public function toArray(bool $withDefault = true): array
	{
		return [
			'Type' => 'TasksUpdateTaskActivity',
			'Name' => $this->getName(),
			'Properties' => [
				'FieldValue' => [
					'ACCOMPLICES' => 'user_' . $this->accomplice
				],
				'MergeMultipleFields' => 'Y',
				'Title' => $this->title,
			],
			'Activated' => 'Y',
		];
	}
}