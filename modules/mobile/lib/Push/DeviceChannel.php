<?php

namespace Bitrix\Mobile\Push;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Web\Json;

Loader::requireModule('pull');

/**
 * Messages, sent through this channel will be delivered as system push-notification, even mobile app is closed.
 * Keep in mind, that if mobile app is already opened, you need to use the Application push object API 54.
 */
final class DeviceChannel extends Channel
{
	/**
	 * @param int $userId
	 * @param Message $message
	 * @return Result
	 */
	public function send(int $userId, Message $message): Result
	{
		$result = new Result();

		if (empty($message->getBody()))
		{
			return $result->addError(new Error('Message body cannot be empty'));
		}

		$sent = (new \CPushManager())->sendMessage([
			[
				'USER_ID' => $userId,
				'APP_ID' => self::APP_ID,
				'EXPIRY' => 0,
				'PARAMS'=> [
					'command' => self::COMMON_MOBILE_PUSH_EVENT,
					'message'=> Json::encode($message),
				],
				'ADVANCED_PARAMS' => [
					'senderName' => $message->getTitle() ?: self::APP_ID,
					'senderMessage' => $message->getBody()
				]
			]
		]);

		if (!$sent)
		{
			$result->addError(new Error('An error occurred while sending a message through the Device Channel'));
		}

		return $result;
	}
}
