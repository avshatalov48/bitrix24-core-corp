<?php

namespace Bitrix\Sign\Service\Integration\Im;

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Sign\Item;

class NotificationService
{
	public function isAvailable(): bool
	{
		return Loader::includeModule('im');
	}

	public function sendNotification(Item\Integration\Im\Notification\Message $message): Result
	{
		if (!$this->isAvailable())
		{
			return (new Result())->addError(new Main\Error('Notification not available'));
		}

		$addResult = \CIMNotify::Add(
			[
				'TO_USER_ID' => $message->toUserId,
				'FROM_USER_ID' => $message->fromUserId,
				'NOTIFY_TYPE' => $message->type->value,
				'NOTIFY_MODULE' => 'sign',
				'NOTIFY_TITLE' =>  $message->title,
				'NOTIFY_MESSAGE' => $message->message,
			]
		);
		if ($addResult === false)
		{
			return (new Result())->addError(new Main\Error("Can't send notification"));
		}

		return new Result();
	}
}