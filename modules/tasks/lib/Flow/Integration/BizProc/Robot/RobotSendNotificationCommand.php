<?php

namespace Bitrix\Tasks\Flow\Integration\BizProc\Robot;

use Bitrix\Tasks\Internals\Attribute\NotEmpty;
use Bitrix\Tasks\Internals\Attribute\PositiveNumber;

class RobotSendNotificationCommand extends AbstractRobotCommand
{
	public function __construct(
		#[NotEmpty]
		public readonly string $title,
		#[PositiveNumber]
		public readonly int $recipient,
		#[NotEmpty]
		public readonly string $message,
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
			'Type' => 'SocNetMessageActivity',
			'Name' => $this->getName(),
			'Properties' => [
				'MessageText' => $this->message,
				'MessageFormat'  => 'robot',
				'MessageUserFrom' => [
					'{=Document:RESPONSIBLE_ID}',
				],
				'MessageUserTo' => [
					'user_' . $this->recipient,
				],
				'Title' => $this->title,
			],
			'Activated' => 'Y',
		];
	}
}