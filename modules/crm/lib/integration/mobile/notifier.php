<?php

declare(strict_types = 1);

namespace Bitrix\Crm\Integration\Mobile;

use Bitrix\Crm\Settings\Crm;
use Bitrix\Main\Loader;
use Bitrix\Mobile\Push\Message;
use Bitrix\Mobile\Push\Sender;

final class Notifier
{
	public const PING_CREATED_MESSAGE_TYPE = 'CRM_TIMELINE_PING_CREATED';

	public static function isAvailable(): bool
	{
		return (
			Loader::includeModule('mobile')
			&& Loader::includeModule('crmmobile')
			&& Crm::isUniversalActivityScenarioEnabled()
		);
	}

	public static function send(
		string $messageType,
		int $userId,
		string $title,
		string $body,
		array $payload = []
	): void
	{
		if (!self::isAvailable())
		{
			return;
		}

		$message = new Message($messageType, $title, $body, $payload);
		Sender::send($userId, $message);
	}

	public static function sendImmediate(
		string $messageType,
		int $userId,
		string $title,
		string $body,
		array $payload = []
	): void
	{
		if (!self::isAvailable())
		{
			return;
		}

		$message = new Message($messageType, $title, $body, $payload);
		Sender::sendImmediate($userId, $message);
	}
}
