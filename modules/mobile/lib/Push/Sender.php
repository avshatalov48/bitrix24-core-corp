<?php

namespace Bitrix\Mobile\Push;

use Bitrix\Main\Result;

/**
 * Facade to send push-messages.
 */
class Sender
{
	/**
	 * Sends $message to $userId through $channels.
	 *
	 * By default, uses ApplicationChannel only.
	 * It means that message will be delivered when user opens his mobile application.
	 *
	 * @param int $userId
	 * @param Message $message
	 * @param Channel[]|null $channels
	 */
	public static function send(int $userId, Message $message, ?array $channels = null): Result
	{
		$result = new Result();

		if ($channels === null)
		{
			$channels = [
				new ApplicationChannel(),
			];
		}

		foreach ($channels as $channel)
		{
			$r = $channel->send($userId, $message);
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * Sends $message to $userId through Application and Device channels.
	 *
	 * It means that message will be delivered as system push-notification, even mobile app is closed.
	 *
	 * @param int $userId
	 * @param Message $message
	 */
	public static function sendImmediate(int $userId, Message $message): Result
	{
		$channels = [
			new ApplicationChannel(),
			new DeviceChannel(),
		];

		return static::send($userId, $message, $channels);
	}
}
