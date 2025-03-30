<?php

namespace Bitrix\Call;

use Bitrix\Main\Event;
use Bitrix\Main\Entity\EventResult;
use Bitrix\Im\Call\Call;
use Bitrix\Im\V2\Call\CallFactory;
use Bitrix\Im\Call\Integration\EntityType;


class EventHandler
{
	/**
	 * @event 'im:OnChatUserDelete'
	 * @see \Bitrix\Im\V2\Chat::sendEventUserDelete
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onChatUserLeave(Event $event): EventResult
	{
		$result = new EventResult;

		/** @var array{chatId: int, userIds: int[]} $eventData */
		$eventData = $event->getParameters();
		if (!empty($eventData['chatId']) && !empty($eventData['userIds']))
		{
			['chatId' => $chatId, 'userIds' => $userIds] = $eventData;

			$type = Call::TYPE_INSTANT;
			$chat = \Bitrix\Im\V2\Chat::getInstance($chatId);
			if ($chat->getEntityType() == \Bitrix\Im\V2\Chat\Type::Videoconference->value)
			{
				$type = Call::TYPE_PERMANENT;
			}

			$call = CallFactory::searchActive(
				type: $type,
				provider: Call::PROVIDER_BITRIX,
				entityType: EntityType::CHAT,
				entityId: 'chat'.$chatId
			);
			if ($call)
			{
				foreach ($userIds as $userId)
				{
					$call->removeUser($userId);
					$call->getSignaling()->sendHangup($userId, $call->getUsers(), null);
				}
			}
		}

		return $result;
	}
}
