<?php

namespace Bitrix\Mobile\Push;

use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Pull\Event;

Loader::requireModule('pull');

/**
 * Messages, sent through this channel will be delivered when user opens his mobile application,
 * or if it's already opened.
 */
final class ApplicationChannel extends Channel
{
	public function send(int $userId, Message $message): Result
	{
		$result = new Result();

		$sent = Event::add($userId, [
			'module_id' => self::MODULE_ID,
			'command' => self::COMMON_MOBILE_PUSH_EVENT,
			'params' => [
				'message' => $message,
			]
		]);

		if (!$sent)
		{
			$result->addError(new Error('An error occurred while sending a message through the Application Channel'));
		}

		return $result;
	}
}
