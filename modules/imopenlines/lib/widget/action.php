<?php
namespace Bitrix\ImOpenLines\Widget;

use Bitrix\Im\Bot\Keyboard;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\Json;

class Action
{
	public static function execute(int $messageId, string $actionValue)
	{

		if (!\Bitrix\Main\Loader::includeModule('imconnector'))
		{
			return false;
		}

		if (!\Bitrix\Main\Loader::includeModule('pull'))
		{
			return false;
		}

		if (!self::checkAccess($messageId))
		{
			return false;
		}

		$buttonExists = false;

		$keyboardButtons = \CIMMessageParam::Get($messageId, 'KEYBOARD');
		foreach ($keyboardButtons as $button)
		{
			if ($button['ACTION_VALUE'] === $actionValue)
			{
				$buttonExists = true;
				break;
			}
		}

		if (!$buttonExists)
		{
			return false;
		}

		try
		{
			$decodeValue = Json::decode($actionValue);
		}
		catch(ArgumentException $e)
		{
			$decodeValue = false;
		}

		if (!$decodeValue || !isset($decodeValue['ACTION']))
		{
			return false;
		}

		$keyboard = new Keyboard();
		foreach ($keyboardButtons as $button)
		{
			$button['DISABLED'] = 'Y';
			$keyboard->addButton($button);
		}

		\CIMMessageParam::Set($messageId, ['KEYBOARD' => $keyboard]);
		\CIMMessageParam::SendPull($messageId, ['KEYBOARD']);
		\Bitrix\Pull\Event::executeEvents();



		$command = $decodeValue['ACTION'];
		unset($decodeValue['ACTION']);
		$actionValue = Json::encode($decodeValue);


		/** @var \Bitrix\ImConnector\InteractiveMessage\Connectors\Livechat\Input $interactiveMessage */
		$interactiveMessage = \Bitrix\ImConnector\InteractiveMessage\Input::init('livechat');
		$result = $interactiveMessage->processingCommandKeyboard($command, $actionValue);
		if (!$result->isSuccess())
		{
			$keyboard = new Keyboard();
			foreach ($keyboardButtons as $button)
			{
				$keyboard->addButton($button);
			}

			\CIMMessageParam::Set($messageId, ['KEYBOARD' => $keyboard]);
			\CIMMessageParam::SendPull($messageId, ['KEYBOARD']);
		}

		return true;
	}

	private static function checkAccess(int $messageId)
	{
		global $USER;
		$userId = $USER->GetId();
		if ($userId <= 0)
		{
			return false;
		}

		$orm = \Bitrix\Im\Model\MessageTable::getById($messageId);
		$message = $orm->fetch();

		if(!$message)
		{
			return false;
		}

		$orm = \Bitrix\Im\Model\ChatTable::getById($message['CHAT_ID']);
		$chat = $orm->fetch();
		if (!$chat)
		{
			return false;
		}

		$relations = \CIMChat::GetRelationById($message['CHAT_ID'], false, true, false);
		if (!isset($relations[$userId]))
		{
			return false;
		}

		return true;
	}
}