<?php

namespace Bitrix\Tasks\Flow\Notification\Config;

use Bitrix\Tasks\Flow\Notification\Exception\InvalidPayload;
use Bitrix\Tasks\Integration\Bizproc\Robot\ChannelDictionary;
use Bitrix\Tasks\ValueObjectInterface;

class Where implements ValueObjectInterface
{
	public const NOTIFICATION_CENTER = 'notificationCenter';
	public const CHAT_DEPARTMENT = 'chatDepartment';
	public const TASK_COMMENT = 'taskComment';
	public const CHAT_DIRECT = 'chatDirect';
	public const EMAIL = 'email';

	private string $type;

	public function __construct(string $type)
	{
		if (!$this->isTypeAllowed($type))
		{
			throw new InvalidPayload('Where:type must be one of the: ' . json_encode($this->getAllowedTypes()));
		}

		$this->type = $type;
	}

	public function getValue(): string
	{
		return $this->type;
	}

	private function isTypeAllowed(string $when): bool
	{
		return in_array($when, $this->getAllowedTypes(), true);
	}

	private function getAllowedTypes(): array
	{
		return [
			self::CHAT_DEPARTMENT,
			self::CHAT_DIRECT,
			self::TASK_COMMENT,
			self::NOTIFICATION_CENTER,
			self::EMAIL,
		];
	}
}