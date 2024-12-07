<?php
namespace Bitrix\ImBot;

use Bitrix\Im\V2\Chat;
use Bitrix\Im\V2\Message;
use Bitrix\Im\V2\Service\Context;
use Bitrix\ImBot\Bot\Network;
use Bitrix\ImBot\Model\NetworkSessionTable;
use Bitrix\Main\Loader;

class Pull
{
	public static function addMultidialog(string $dialogId, ?int $botId = null, ?int $userId = null): bool
	{
		if (!$botId)
		{
			if (!$botId = self::getSupportBotId())
			{
				return false;
			}
		}

		$isSupport = $botId == self::getSupportBotId();
		$supportBotClass = self::detectSupportBot();

		$chatId = $dialogId;
		$matches = [];
		if (preg_match('/^chat([0-9]+)$/i', $chatId, $matches))
		{
			$chatId = (int)$matches[1];
			$chat = \Bitrix\Im\V2\Chat::getInstance($chatId);
			$pullUsers = self::getPullUsers($chat);

			\Bitrix\Im\V2\Chat::fillSelfRelations([$chat], current($pullUsers));

			Bot\Network::cleanQuestionsCountCache($botId);

			$pullParams = [
				'chatId' => $chatId,
				'dialogId' => $chat->getDialogId(),
				'botId' => $botId,
				'isSupport' => $isSupport,
			];
		}
		elseif (is_numeric($dialogId))
		{
			$chat = \Bitrix\Im\V2\Chat::getInstance($chatId);
			$pullParams = Network::getBotAsMultidialog((int)$botId, (int)$dialogId);
		}

		$pullParams['status'] = mb_strtolower(Network::MULTIDIALOG_STATUS_NEW);

		return \Bitrix\Pull\Event::add($pullUsers, [
			'module_id' => 'im',
			'command' => 'addMultidialog',
			'expiry' => 3600,
			'params' => [
				'chat' => $chat->toRestFormat(['CHAT_SHORT_FORMAT' => true]),
				'multidialog' => $pullParams,
				'count' => $supportBotClass
					? $supportBotClass::getQuestionsCount($botId, $userId)
					: Network::getQuestionsCount($botId, $userId),
			]
		]);
	}

	public static function changeMultidialogStatus(string $dialogId, string $status, int $sessionId, ?int $botId = null): bool
	{
		if (!$botId)
		{
			if (!$botId = self::getSupportBotId())
			{
				return false;
			}
		}

		$isSupport = $botId == self::getSupportBotId();

		$chatId = $dialogId;
		$matches = [];
		$bot = null;
		if (preg_match('/^chat([0-9]+)$/i', $chatId, $matches))
		{
			$chatId = (int)$matches[1];
		}
		elseif (is_numeric($dialogId))
		{
			$users = (new \Bitrix\Im\V2\Entity\User\UserCollection([$botId]))->toRestFormat();
			if (count($users))
			{
				$bot = array_shift($users);
			}

			$networkSession = NetworkSessionTable::getRow([
				'filter' => [
					'=SESSION_ID' => $sessionId
				]
			]);

			$chatResult = \Bitrix\Im\V2\Chat\PrivateChat::find([
				'FROM_USER_ID' => $botId,
				'TO_USER_ID' => $networkSession['DIALOG_ID'],
			]);

			if ($chatResult->isSuccess())
			{
				$chatData = $chatResult->getResult();
				$chatId = (int)$chatData['ID'];
			}
		}

		$chat = \Bitrix\Im\V2\Chat::getInstance((int)$chatId);
		$pullUsers = self::getPullUsers($chat);
		$userContext = (new Context())->setUserId(current($pullUsers));
		$chat->setContext($userContext);

		$lastMessage = new Message($chat->getLastMessageId());

		$pullParams = [
			'multidialog' => [
				'chatId' => (int)$chatId,
				'botId' => $botId,
				'isSupport' => $isSupport,
				'status' => mb_strtolower($status),
				'dateMessage' => $lastMessage->getDateCreate()->format('c'),
			],
		];

		if ($bot)
		{
			$pullParams['bot'] = $bot;
			$pullParams['multidialog']['dialogId'] = (string)$botId;
		}
		else
		{
			\Bitrix\Im\V2\Chat::fillSelfRelations([$chat], current($pullUsers));
			$pullParams['chat'] = $chat->toRestFormat(['CHAT_SHORT_FORMAT' => true]);
			$pullParams['multidialog']['dialogId'] = $chat->getDialogId();
		}

		return \Bitrix\Pull\Event::add($pullUsers, [
			'module_id' => 'im',
			'command' => 'changeMultidialogStatus',
			'expiry' => 3600,
			'params' => $pullParams
		]);
	}

	public static function changeActiveSessionsLimit(int $limit, int $userId, ?int $botId = null): bool
	{
		if (!$botId)
		{
			if (!$botId = self::getSupportBotId())
			{
				return false;
			}
		}

		return \Bitrix\Pull\Event::add($userId, [
			'module_id' => 'im',
			'command' => 'changeMultidialogSessionsLimit',
			'expiry' => 3600,
			'params' => [
				'botId' => $botId,
				'limit' => $limit
			]
		]);
	}

	private static function getSupportBotId(): ?int
	{
		$classSupport = self::detectSupportBot();
		if ($classSupport)
		{
			return $classSupport::getBotId();
		}

		return null;
	}

	/**
	 * Detects installed support bot.
	 * @return \Bitrix\ImBot\Bot\SupportBot & \Bitrix\Imbot\Bot\SupportQuestion|string|null
	 */
	private static function detectSupportBot(): ?string
	{
		static $classSupport = null;

		if ($classSupport === null)
		{
			/** @var \Bitrix\Imbot\Bot\SupportBot $classSupport */
			if (
				Loader::includeModule('bitrix24')
				&& \Bitrix\ImBot\Bot\Support24::isEnabled()
			)
			{
				$classSupport = \Bitrix\ImBot\Bot\Support24::class;
			}
			elseif (\Bitrix\ImBot\Bot\SupportBox::isEnabled())
			{
				$classSupport = \Bitrix\ImBot\Bot\SupportBox::class;
			}
		}

		return $classSupport;
	}

	private static function getPullUsers(Chat $chat): array
	{
		$pullUsers = [];
		$userIds = $chat->getRelations()->getUserIds();
		foreach ($userIds as $userId)
		{
			$userId = (int)$userId;
			$user = \Bitrix\Im\V2\Entity\User\User::getInstance($userId);
			if ($user->isExist() && $user->isActive() && !$user->isBot())
			{
				$pullUsers[$userId] = $userId;
			}
		}

		return $pullUsers;
	}
}