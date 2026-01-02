<?php

namespace Bitrix\Call;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Im\Call\Call;
use Bitrix\Im\V2\Call\CallFactory;
use Bitrix\Im\Call\Integration\EntityType;
use Bitrix\Call\Cache\ActiveCallsCache;


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
		$result = new EventResult(EventResult::SUCCESS);

		/** @var array{chatId: int, userIds: int[]} $eventData */
		$eventData = $event->getParameters();
		if (!empty($eventData['chatId']) && !empty($eventData['userIds']))
		{
			['chatId' => $chatId, 'userIds' => $userIds] = $eventData;

			$type = Call::TYPE_INSTANT;
			$chat = \Bitrix\Im\V2\Chat::getInstance($chatId);
			if ($chat->getEntityType() == \Bitrix\Im\V2\Chat\ExtendedType::Videoconference->value)
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
				if (Settings::isNewCallsEnabled())
				{
					$chat->getCallToken()->update();
					$call->getSignaling()->sendPushTokenUpdate($chat->getCallToken()->getToken(), $call->getUsers());
				}

				$ids = array_unique(array_merge($userIds, array_keys($call->getCallUsers())));
				foreach ($ids as $userId)
				{
					\Bitrix\Call\Call::updateUserActiveCallsCache($userId);
				}
			}
		}

		return $result;
	}

	/**
	 * Handles call finished event: update active calls cache for all participants
	 *
	 * @event 'call:onCallFinished'
	 * @param Event $event
	 * @return void
	 */
	public static function onCallFinished(Event $event): void
	{
		$call = $event->getParameter('call');
		if (!$call instanceof \Bitrix\Im\Call\Call)
		{
			return;
		}

		\Bitrix\Call\Call::updateCallCache($call->getId());
		\Bitrix\Call\Call::terminateAllCallsInChat($call->getChatId(), $call->getId());
	}

	/**
	 * @event 'im:OnChatUserAdd'
	 * @see \Bitrix\Im\V2\Chat::sendEventUsersAdd
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onChatUserAdd(Event $event): EventResult
	{
		$result = new EventResult(EventResult::SUCCESS);

		/** @var array{CHAT_ID: int, NEW_USERS: int[], CHAT: \Bitrix\Im\V2\Chat, ENTITY_TYPE: string, ENTITY_ID: string} $eventData */
		$eventData = $event->getParameters();

		if (!empty($eventData['CHAT_ID']) && !empty($eventData['NEW_USERS']))
		{
			$chatId = (int)$eventData['CHAT_ID'];
			$newUsers = $eventData['NEW_USERS'];
			$chat = $eventData['CHAT'] ?? null;

			// Determine call type based on chat entity type
			$type = Call::TYPE_INSTANT;
			if ($chat && $chat->getEntityType() == \Bitrix\Im\V2\Chat\ExtendedType::Videoconference->value)
			{
				$type = Call::TYPE_PERMANENT;
			}

			// Search for active call in this chat
			$call = CallFactory::searchActive(
				type: $type,
				provider: Call::PROVIDER_BITRIX,
				entityType: EntityType::CHAT,
				entityId: 'chat'.$chatId
			);

			// If active call exists, invite new users to it
			if ($call && $call->getState() !== Call::STATE_FINISHED && $call->hasActiveUsers())
			{
				self::inviteUsersToCall($call, $newUsers);
			}
		}

		return $result;
	}

	/**
	 * Invite users to call using Controller logic
	 * @param Call $call
	 * @param array $userIds
	 */
	private static function inviteUsersToCall(Call $call, array $userIds): void
	{
		$usersToInvite = [];

		foreach ($userIds as $userId)
		{
			$userId = (int)$userId;

			if (!$call->checkAccess($userId))
			{
				continue;
			}

			if (!$call->hasUser($userId))
			{
				if ($call->addUser($userId))
				{
					$usersToInvite[] = $userId;
				}
			}
		}

		if (!empty($usersToInvite))
		{
			$signaling = $call->getSignaling();
			$initiatorId = $call->getInitiatorId();

			foreach ($usersToInvite as $userId)
			{
				$signaling->sendInviteToUser(
					$initiatorId,
					$userId,
					$usersToInvite,
					false,
					true,
					true
				);
				\Bitrix\Call\Call::updateUserActiveCallsCache($userId);
			}

			if (count($usersToInvite) > 0)
			{
				$signaling->sendUsersInvited($initiatorId, $usersToInvite, $call->getUsers(), true);
			}

			\Bitrix\Call\Call::updateCallCache($call->getId());
		}
	}
}
