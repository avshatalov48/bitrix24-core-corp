<?php

namespace Bitrix\Intranet\Integration\Main;

use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Mail\Internal;
use Bitrix\Main\Event;
use CIMNotify;

class EventHandler
{
	public static function onSenderSmtpLimitDecrease(Event $event) : EventResult
	{
		if (!Loader::includeModule('im') || !$email = $event->getParameter('EMAIL'))
		{
			return new EventResult(EventResult::SUCCESS);
		}
		$senderEntity = Internal\SenderTable::getList(
			[
				'select' => ['EMAIL','USER_ID'],
				'filter' => ['=EMAIL' => $email],
			]
		);

		$senders = [];
		while ($sender = $senderEntity->fetch())
		{
			 $senders[$sender['EMAIL']][] = $sender['USER_ID'];
		}

		foreach ($senders as $userIds)
		{
			foreach ($userIds as $userId)
			{
				$messageFields = [
					"TO_USER_ID" => $userId,
					"FROM_USER_ID" => 0,
					"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
					"NOTIFY_MODULE" => "im",
					"NOTIFY_TAG" => "IM_CONFIG_NOTICE",
					"NOTIFY_MESSAGE" => Loc::getMessage('MAIN_MAIL_CALLBACK_LIMIT_NOTIFICATION'),
				];
				CIMNotify::Add($messageFields);
			}
		}
		return new EventResult(EventResult::SUCCESS);
	}
}