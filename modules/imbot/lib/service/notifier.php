<?php declare(strict_types=1);

namespace Bitrix\ImBot\Service;

use Bitrix\Im;
use Bitrix\ImBot;
use Bitrix\ImBot\Log;
use Bitrix\Main;
use Bitrix\Main\Result;
use Bitrix\Main\Localization\Loc;

/**
 * Class Notifier service.
 *
 * @package Bitrix\ImBot\Service
 */
class Notifier
{
	public const
		COMMAND_FORWARD_MESSAGE = 'forwardMessage',
		CHAT_ENTITY_TYPE = 'SUPPORT24_NOTIFIER'
	;

	/**
	 * @see \Bitrix\ImBot\Controller::sendToService
	 * @param string $command
	 * @param array $params
	 *
	 * @return bool|Result
	 */
	public static function onReceiveCommand(string $command, array $params)
	{
		unset(
			$params['BX_BOT_NAME'],
			$params['BX_SERVICE_NAME'],
			$params['BX_COMMAND']
		);

		if (
			!\Bitrix\Main\Loader::includeModule('im')
			|| !\Bitrix\Main\Loader::includeModule('imbot')
		)
		{
			return false;
		}

		$params['BX_COMMAND'] = $command;

		Log::write($params, 'NOTIFIER SERVICE');

		$result = new Result;

		if ($command === self::COMMAND_FORWARD_MESSAGE)
		{
			try
			{
				$resultReception = self::forwardMessage($params);

				if ($resultReception->isSuccess())
				{
					$result->setData([
						'RESULT' => 'OK'
					]);
				}
				else
				{
					$result->addErrors($resultReception->getErrors());
				}
			}
			catch (\Bitrix\Main\SystemException $exception)
			{
				$result->addError(new \Bitrix\Main\Error(
					$exception->getMessage(),
					$exception->getCode(),
					$params
				));
			}
		}
		else
		{
			$result->addError(new \Bitrix\Main\Error(
				'Command "'.$command.'" is not found.',
				'UNKNOWN_COMMAND',
				$params
			));
		}

		if (!$result->isSuccess() && $result->getErrorCollection()->isEmpty())
		{
			$result->addError(new \Bitrix\Main\Error(
				'Command "'.$command.'" execute with errors.',
				'ERROR_COMMAND',
				$params
			));
		}

		return $result;
	}

	/**
	 * @param array $params
	 *
	 * @return Result
	 */
	protected static function forwardMessage(array $params): Result
	{
		$classSupport = self::detectSupportBot();
		if (!$classSupport)
		{
			$result = new Result;
			$result->addError(new Main\Error('Support bot is not installed'));

			return $result;
		}

		$adminGroupUsers = self::getAdminGroupUsers();

		if (empty($params['DIALOG_ID']))
		{
			// to portal creator
			if (
				isset($params['RECIPIENT'])
				&& $params['RECIPIENT'] === 'CREATOR'
				&& \Bitrix\Main\Loader::includeModule('bitrix24')
			)
			{
				$params['DIALOG_ID'] = \CBitrix24::getPortalCreatorId();
			}
			// to single admin
			elseif (count($adminGroupUsers) == 1)
			{
				$params['DIALOG_ID'] = $adminGroupUsers[0];
			}
		}

		if (empty($params['DIALOG_ID']))
		{
			$result = self::notifyChannel($params);

			if (!$result->isSuccess())
			{
				// fallback
				$params['DIALOG_ID'] = $adminGroupUsers[0];
				$result = self::notifyCertainUser($params);
			}
		}
		else
		{
			// send to certain user
			$result = self::notifyCertainUser($params);
		}

		return $result;
	}

	/**
	 * Sends notification to the certain user.
	 * @param array $params
	 *
	 * @return Result
	 */
	private static function notifyCertainUser(array $params): Result
	{
		$result = new Result;

		$classSupport = self::detectSupportBot();

		/** @see \Bitrix\ImBot\Bot\Network::onReceiveCommand */
		$commandResult = $classSupport::onReceiveCommand(
			ImBot\Bot\Network::COMMAND_OPERATOR_MESSAGE_ADD,
			[
				'MESSAGE_ID' => (int)$params['MESSAGE_ID'],
				'BOT_ID' => $classSupport::getBotId(),
				'BOT_CODE' => $classSupport::getBotCode(),
				'DIALOG_ID' => $params['DIALOG_ID'],
				'MESSAGE' => $params['MESSAGE'],
				'URL_PREVIEW' => $params['URL_PREVIEW'] === 'N' ? 'N': 'Y',
				'ATTACH' => $params['ATTACH'] ?? '',
				'KEYBOARD' => $params['KEYBOARD'] ?? '',
				'PARAMS' => [
					ImBot\Bot\Network::MESSAGE_PARAM_ALLOW_QUOTE => 'Y',
					'IMB_MENU_ACTION' => 'SKIP:MENU', /** @see \Bitrix\Imbot\Bot\Mixin\MESSAGE_PARAM_MENU_ACTION */
				],
			]
		);
		if ($commandResult instanceof ImBot\Error)
		{
			$result->addError(new Main\Error($commandResult->msg, $commandResult->code));
		}

		return $result;
	}

	/**
	 * Sends notification to the group chat.
	 * @param array $params
	 *
	 * @return Result
	 */
	private static function notifyChannel(array $params): Result
	{
		$result = new Result;

		/**
		 * @global \CMain $APPLICATION
		 */
		global $APPLICATION;

		$classSupport = self::detectSupportBot();

		$chatId = self::getChannel();
		if (!$chatId)
		{
			$result = self::createChannel();
			if ($result->isSuccess())
			{
				$chatId = $result->getData()['chatId'];
			}
		}
		else
		{
			self::checkChannelMembers($chatId);
		}

		if ($result->isSuccess())
		{
			$messageFields = [
				'MESSAGE_TYPE' => \IM_MESSAGE_CHAT,
				'TO_CHAT_ID' => $chatId,
				'FROM_USER_ID' => $classSupport::getBotId(),
				'PARAMS' => [
					ImBot\Bot\Network::MESSAGE_PARAM_ALLOW_QUOTE => 'Y',
				],
				'MESSAGE' => $params['MESSAGE'],
				'URL_PREVIEW' => $params['URL_PREVIEW'] === 'N' ? 'N': 'Y',
			];

			if (!empty($params['ATTACH']))
			{
				$messageFields['ATTACH'] = \CIMMessageParamAttach::getAttachByJson($params['ATTACH']);
			}

			// feedback button
			if (!empty($params['KEYBOARD']))
			{
				if (!isset($params['KEYBOARD']['BUTTONS']))
				{
					$keyboard['BUTTONS'] = $params['KEYBOARD'];
				}
				else
				{
					$keyboard = $params['KEYBOARD'];
				}
				$keyboard['BOT_ID'] = $classSupport::getBotId();
				$messageFields['KEYBOARD'] =
					Im\Bot\Keyboard::getKeyboardByJson($keyboard);
			}
			else
			{
				$feedback = $classSupport::getMessage('NOTIFIER_FEEDBACK');
				if (!$feedback)
				{
					$feedback = Loc::getMessage('IMBOT_NOTIFIER_FEEDBACK_SUPPORT');
				}
				$keyboard = new Im\Bot\Keyboard($classSupport::getBotId());
				$keyboard->addButton([
					'DISPLAY' => "LINE",
					'TEXT' => $feedback,
					'BG_COLOR' => "#29619b",
					'TEXT_COLOR' => "#fff",
					'BLOCK' => "Y",
					'COMMAND' => $classSupport::COMMAND_START_DIALOG,
				]);
				$messageFields['KEYBOARD'] = $keyboard;
			}

			$messageId = \CIMMessenger::add($messageFields);
			if (!$messageId)
			{
				/**
				 * @var \CApplicationException $error
				 */
				$error = $APPLICATION->getException();
				if ($error)
				{
					$result->addError(new Main\Error($error->getString(), $error->getId()));
				}
				else
				{
					$result->addError(new Main\Error(
						'Cannot send message to admin group',
						'ERROR_DELIVER_NOTIFICATION'
					));
				}
			}
		}

		return $result;
	}

	/**
	 * Returns group chat id.
	 * @return int|null
	 */
	private static function getChannel(): ?int
	{
		$classSupport = self::detectSupportBot();
		$res = \Bitrix\Im\Model\ChatTable::getList([
			'select' => ['ID', 'ENTITY_ID'],
			'filter' => [
				'=ENTITY_TYPE' => self::CHAT_ENTITY_TYPE,
				'=ENTITY_ID' => $classSupport::getBotId(),
			]
		]);
		if ($chat = $res->fetch())
		{
			return (int)$chat['ID'];
		}

		return null;
	}

	/**
	 * Creates group chat.
	 * @return int|null
	 */
	private static function createChannel(): Result
	{
		$result = new Result;

		/**
		 * @global \CMain $APPLICATION
		 */
		global $APPLICATION;

		$classSupport = self::detectSupportBot();
		$adminGroupUsers = self::getAdminGroupUsers();
		$adminGroupUsers[] = $classSupport::getBotId();

		// create group chat
		$chat = new \CIMChat(0);

		$chatId = $chat->add([
			'AUTHOR_ID' => $classSupport::getBotId(),
			'USERS' => $adminGroupUsers,
			'TITLE' => self::getChannelName(),
			'TYPE' => \IM_MESSAGE_CHAT,
			'ENTITY_TYPE' => self::CHAT_ENTITY_TYPE,
			'ENTITY_ID' => $classSupport::getBotId(),
		]);
		if ($chatId)
		{
			$result->setData(['chatId' => $chatId]);
		}
		else
		{
			/**
			 * @var \CApplicationException $error
			 */
			$error = $APPLICATION->getException();
			if ($error)
			{
				$result->addError(new Main\Error($error->getString(), $error->getId()));
			}
			else
			{
				$result->addError(new Main\Error(
					'Cannot initiate chat with admin group',
					'ERROR_CHAT_CREATION'
				));
			}
		}

		return $result;
	}

	/**
	 * Sets up the new chanel's owner.
	 *
	 * @return void
	 */
	public static function changeChannelOwner(int $chatId, int $ownerId, int $previousOwnerId): void
	{
		$chat = new \CIMChat(0);
		$chat->addUser($chatId, $ownerId, true, true, true);
		$chat->setOwner($chatId, $ownerId, false);

		Im\Model\ChatTable::update($chatId, ['ENTITY_ID' => $ownerId]);

		Main\Application::getConnection()->queryExecute(
			'UPDATE '. Im\Model\MessageTable::getTableName()
			.' SET AUTHOR_ID = '. $ownerId
			.' WHERE AUTHOR_ID = '. $previousOwnerId.' AND CHAT_ID = '.$chatId
		);
	}

	/**
	 * Returns notify's channel name.
	 *
	 * @return string
	 */
	private static function getChannelName(): string
	{
		$classSupport = self::detectSupportBot();
		$name = $classSupport::getMessage('NOTIFIER_CHANNEL');
		if (!$name)
		{
			$name = Loc::getMessage('IMBOT_NOTIFIER_ADMIN_GROUP_CHAT');
		}

		return $name;
	}

	/**
	 * Checks members of the group chat.
	 * @return void
	 */
	private static function checkChannelMembers(int $chatId): void
	{
		$classSupport = self::detectSupportBot();

		$chat = new \CIMChat(0);
		$chat->setOwner($chatId, $classSupport::getBotId(), false);

		$adminGroupUsers = self::getAdminGroupUsers();
		$adminGroupUsers[] = $classSupport::getBotId();
		array_map('intVal', $adminGroupUsers);

		$relations = Im\Chat::getRelation($chatId, ['SELECT' => ['ID', 'USER_ID']]);
		$chatMembers = [];
		foreach($relations as $relation)
		{
			$chatMembers[] = (int)$relation['USER_ID'];
		}

		$addUsers = array_diff($adminGroupUsers, $chatMembers);
		if (count($addUsers))
		{
			foreach ($addUsers as $userId)
			{
				$chat->addUser($chatId, $userId);
			}
		}
	}

	/**
	 * Detects installed support bot.
	 * @return \Bitrix\Imbot\Bot\SupportBot|string|null
	 */
	private static function detectSupportBot(): ?string
	{
		static $classSupport = null;

		if ($classSupport === null)
		{
			/** @var \Bitrix\Imbot\Bot\SupportBot $classSupport */
			if (
				\Bitrix\Main\Loader::includeModule('bitrix24')
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

	/**
	 * Returns portal admins.
	 *
	 * @return int[]
	 */
	private static function getAdminGroupUsers(): array
	{
		return \Bitrix\ImBot\Bot\Network::getAdministrators();
	}
}