<?php

namespace Bitrix\Im\Call\Integration;

use Bitrix\Im\Call\Call;
use Bitrix\Im\Call\CallUser;
use Bitrix\Im\Common;
use Bitrix\Im\Dialog;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;

class Chat extends AbstractEntity
{
	protected $chatId;
	protected $chatFields;
	protected $chatUsers = [];

	const MUTE_MESSAGE = true;

	public function __construct(Call $call, $entityId)
	{
		parent::__construct($call, $entityId);

		if(Common::isChatId($entityId))
		{
			$chatId = \Bitrix\Im\Dialog::getChatId($entityId, $this->userId);
		}
		else
		{
			$otherUserId = $this->userId == $entityId ? $this->call->getInitiatorId() : $entityId;
			$chatId = \Bitrix\Im\Dialog::getChatId($otherUserId , $this->userId);
		}

		$result = \CIMChat::GetChatData([
			'ID' => $chatId,
			'USER_ID' => $this->userId
		]);

		if ($result['chat'][$chatId])
		{
			$this->chatFields = $result['chat'][$chatId];
		}
		if (is_array($result['userInChat'][$chatId]))
		{
			$users = $result['userInChat'][$chatId];
			$activeRealUsers = UserTable::getList([
				'select' => ['ID'],
				'filter' => [
					'ID' => $users,
					'=ACTIVE' => 'Y',
					'=IS_REAL_USER' => 'Y',
					[
						'LOGIC' => 'OR',
						'!=EXTERNAL_AUTH_ID' => \Bitrix\Im\Call\Auth::AUTH_TYPE,
						'=IS_ONLINE' => 'Y'
					]
				]
			])->fetchAll();
			$this->chatUsers = array_column($activeRealUsers, 'ID');
		}
		$this->chatId = $chatId;
	}

	/**
	 * Returns associated entity type.
	 *
	 * @return string
	 */
	public function getEntityType()
	{
		return EntityType::CHAT;
	}

	public function getEntityId($currentUserId = 0)
	{
		if($this->chatFields['message_type'] != IM_MESSAGE_PRIVATE || $currentUserId == 0)
		{
			return $this->entityId;
		}
		else
		{
			return $this->call->getInitiatorId() == $currentUserId ? $this->entityId : $this->call->getInitiatorId();
		}
	}

	public function getChatId()
	{
		return $this->chatId;
	}

	/**
	 * Returns list of users in the chat
	 *
	 * @return array
	 */
	public function getUsers()
	{
		return $this->chatUsers;
	}

	/**
	 * Returns true is user can call users in the associated chat and false otherwise.
	 *
	 * @param int $userId
	 * @return bool
	 */
	public function checkAccess($userId)
	{
		return Dialog::hasAccess($this->entityId, $userId);
	}

	/**
	 * Returns associated entity name.
	 *
	 * @param int $currentUserId Id of the user.
	 * @return string|false
	 */
	public function getName($currentUserId)
	{
		if(!$this->chatFields)
		{
			return false;
		}

		if($this->chatFields['message_type'] == IM_MESSAGE_PRIVATE && count($this->chatUsers) == 2)
		{
			return \Bitrix\Im\User::getInstance($this->getEntityId($currentUserId))->getFullName();
		}
		else if($this->chatFields['message_type'] != IM_MESSAGE_PRIVATE)
		{
			return $this->chatFields['name'];
		}

		return false;
	}

	public function getAvatar($currentUserId)
	{
		if(!$this->chatFields)
		{
			return false;
		}

		if($this->chatFields['message_type'] == IM_MESSAGE_PRIVATE && count($this->chatUsers) == 2)
		{
			return \Bitrix\Im\User::getInstance($this->getEntityId($currentUserId))->getAvatarHr();
		}
		else if($this->chatFields['message_type'] != IM_MESSAGE_PRIVATE)
		{
			return $this->chatFields['avatar'];
		}
	}

	public function getAvatarColor($currentUserId)
	{
		if(!$this->chatFields)
		{
			return false;
		}

		if($this->chatFields['message_type'] == IM_MESSAGE_PRIVATE && count($this->chatUsers) == 2)
		{
			return \Bitrix\Im\User::getInstance($this->getEntityId($currentUserId))->getColor();
		}
		else if($this->chatFields['message_type'] != IM_MESSAGE_PRIVATE)
		{
			return $this->chatFields['color'];
		}
	}


	public function onUserAdd($userId)
	{
		if($this->chatFields['message_type'] == IM_MESSAGE_PRIVATE)
		{
			$chat = new \CIMChat();

			$users = $this->chatUsers;
			$users[] = $userId;

			$chatId = $chat->add(['USERS' => $users]);
			if (!$chatId)
			{
				return false;
			}

			if($this->call)
			{
				$this->call->setAssociatedEntity(static::getEntityType(), 'chat'.$chatId);
			}
		}
		else
		{
			$chat = new \CIMChat();
			$chatId = \Bitrix\Im\Dialog::getChatId($this->getEntityId());
			$result = $chat->addUser($chatId, $userId);
		}

		return true;
	}

	public function onStateChange($state, $prevState)
	{
		$initiatorId = $this->call->getInitiatorId();
		$initiator = \Bitrix\Im\User::getInstance($initiatorId);
		if($state === Call::STATE_INVITING && $prevState === Call::STATE_NEW)
		{
			static::sendMessage(Loc::getMessage("IM_CALL_INTEGRATION_CHAT_CALL_STARTED", [
				"#ID#" => '[B]'.$this->call->getId().'[/B]'
			]), self::MUTE_MESSAGE);
		}
		else if($state === Call::STATE_FINISHED)
		{
			$message = Loc::getMessage("IM_CALL_INTEGRATION_CHAT_CALL_FINISHED");
			$mute = true;

			$userIds = array_values(array_filter($this->call->getUsers(), function($userId) use ($initiatorId)
			{
				return $userId != $initiatorId;
			}));

			if(count($userIds) == 1)
			{
				$otherUser = \Bitrix\Im\User::getInstance($userIds[0]);
				$otherUserState = $this->call->getUser($userIds[0]) ? $this->call->getUser($userIds[0])->getState() : '';
				if ($otherUserState == CallUser::STATE_DECLINED)
				{
					$message = Loc::getMessage("IM_CALL_INTEGRATION_CHAT_CALL_USER_DECLINED_" . $otherUser->getGender(), [
						'#NAME#' => $otherUser->getFullName(false)
					]);
				}
				else if ($otherUserState == CallUser::STATE_BUSY)
				{
					$message = Loc::getMessage("IM_CALL_INTEGRATION_CHAT_CALL_USER_BUSY_" . $otherUser->getGender(), [
						'#NAME#' => $otherUser->getFullName(false)
					]);
					$mute = false;
				}
				else if ($otherUserState == CallUser::STATE_UNAVAILABLE || $otherUserState == CallUser::STATE_CALLING)
				{
					$message = Loc::getMessage("IM_CALL_INTEGRATION_CHAT_CALL_MISSED_" . $initiator->getGender(), [
						'#NAME#' => $initiator->getFullName(false)
					]);
					$mute = false;
				}
			}

			$this->sendMessage($message, $mute);
		}
	}

	public function sendMessage($message, $muted = false)
	{
		\CIMMessenger::add([
			'DIALOG_ID' => $this->entityId,
			'FROM_USER_ID' => $this->getCall()->getInitiatorId(),
			'MESSAGE' => $message,
			'SYSTEM' => 'Y',
			'INCREMENT_COUNTER' => $muted? 'N': 'Y',
			'PUSH' => 'N'
		]);
	}

	public function toArray($currentUserId = 0)
	{
		if($currentUserId == 0)
		{
			$currentUserId = $this->userId;
		}
		return [
			'type' => $this->getEntityType(),
			'id' => $this->getEntityId($currentUserId),
			'name' => $this->getName($currentUserId),
			'avatar' => $this->getAvatar($currentUserId),
			'avatarColor' => $this->getAvatarColor($currentUserId),
			'advanced' => [
				'chatType' => $this->chatFields['type']
			]
		];
	}
}