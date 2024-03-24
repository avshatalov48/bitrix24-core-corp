<?php
declare(strict_types=1);

namespace Bitrix\ImBot\Service;

use Bitrix\ImBot;
use Bitrix\Main\Result;

class Careteam
{
	public const COMMAND_FORWARD_MESSAGE = 'forwardMessage';
	public const BOT_PROPERTIES = ['NAME', 'WORK_POSITION', 'GENDER', 'COLOR'];

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
			|| !self::registerBot()
		)
		{
			return false;
		}

		$params['BX_COMMAND'] = $command;

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

					if (
						isset($params['BOT_PROPERTIES'])
						&& !empty($params['BOT_PROPERTIES'])
						&& is_string($params['BOT_PROPERTIES'])
					)
					{
						$botProperties = \Bitrix\Main\Web\Json::decode($params['BOT_PROPERTIES']);
						if (is_array($botProperties) && !empty($botProperties))
						{
							self::updateBotProperties($botProperties);
						}
					}
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

	protected static function forwardMessage(array $params): Result
	{
		$result = new Result();

		$messageId = self::sendAnswer($params);

		if (!$messageId)
		{
			$result->addError(new \Bitrix\Main\Error(
				'Message has not been added',
				'ERROR_MESSAGE',
				$params
			));
		}

		return $result;
	}

	private static function sendAnswer($messageFields)
	{
		if (empty($messageFields['DIALOG_ID']))
		{
			$adminGroupUsers = \Bitrix\ImBot\Bot\Network::getAdministrators();
			if (
				isset($messageFields['RECIPIENT'])
				&& $messageFields['RECIPIENT'] === 'CREATOR'
				&& \Bitrix\Main\Loader::includeModule('bitrix24')
			)
			{
				$messageFields['DIALOG_ID'] = \CBitrix24::getPortalCreatorId();
			}
			elseif (count($adminGroupUsers) > 0)
			{
				$messageFields['DIALOG_ID'] = $adminGroupUsers[0];
			}
			else
			{
				return 0;
			}
		}

		$attach = [];
		if (!empty($messageFields['ATTACH']))
		{
			$attach = \CIMMessageParamAttach::GetAttachByJson($messageFields['ATTACH']);
		}

		if (isset($messageFields['KEYBOARD']) && !empty($messageFields['KEYBOARD']))
		{
			$keyboard = [];
			if (is_string($messageFields['KEYBOARD']))
			{
				$messageFields['KEYBOARD'] = \CUtil::JsObjectToPhp($messageFields['KEYBOARD']);
			}
			if (!isset($messageFields['KEYBOARD']['BUTTONS']))
			{
				$keyboard['BUTTONS'] = $messageFields['KEYBOARD'];
			}
			else
			{
				$keyboard = $messageFields['KEYBOARD'];
			}
			$keyboard['BOT_ID'] = \Bitrix\ImBot\Bot\Careteam::getBotId();

			$keyboard = \Bitrix\Im\Bot\Keyboard::getKeyboardByJson($keyboard);
			if ($keyboard)
			{
				$messageFields['KEYBOARD'] = $keyboard;
			}
		}

		if ($messageFields['ANSWER_URL'])
		{
			$messageFields['MESSAGE'] = ' ' . $messageFields['ANSWER_URL'];
		}

		$messageId = \Bitrix\Im\Bot::addMessage(['BOT_ID' => ImBot\Bot\Careteam::getBotId()], [
			'DIALOG_ID' => $messageFields['DIALOG_ID'],
			'MESSAGE' => $messageFields['MESSAGE'],
			'ATTACH' => $attach,
			'KEYBOARD' => $keyboard,
			'PARAMS' => $messageFields['PARAMS'] ?? [],
			'URL_PREVIEW' => $messageFields['RICH'] ?? 'Y'
		]);

		return $messageId;
	}

	private static function registerBot(): bool
	{
		if (ImBot\Bot\Careteam::isEnabled())
		{
			return true;
		}

		return (bool)ImBot\Bot\Careteam::register();
	}

	public static function updateBotProperties(array $botProperties = []): bool
	{
		if (!($botId = \Bitrix\ImBot\Bot\Careteam::getBotId()) || empty($botProperties))
		{
			return false;
		}

		$bot = \Bitrix\Im\User::getInstance($botId);
		$botData = $bot->getArray();

		$botParams = [];
		foreach (self::BOT_PROPERTIES as $propertyName)
		{
			if (
				isset($botProperties[$propertyName])
				&& !empty($botProperties[$propertyName])
				&& $botProperties[$propertyName] !== $botData[$propertyName]
			)
			{
				$botParams[$propertyName] = $botProperties[$propertyName];
			}
		}

		if (isset($botProperties['AVATAR']))
		{
			$botAvatar = \Bitrix\Im\User::uploadAvatar($botProperties['AVATAR'], $botId);
			if ($botAvatar && $bot->getAvatarId() != $botAvatar)
			{
				$botParams['PERSONAL_PHOTO'] = $botAvatar;
			}
		}

		return \Bitrix\Im\Bot::update(
			['BOT_ID' => $botId],
			['PROPERTIES' => $botParams]
		);
	}
}
