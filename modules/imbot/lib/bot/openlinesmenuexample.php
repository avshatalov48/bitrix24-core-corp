<?php
namespace Bitrix\ImBot\Bot;

use Bitrix\Main\Localization\Loc;
use Bitrix\ImBot\Itr as Itr;

Loc::loadMessages(__FILE__);

class OpenlinesMenuExample
{
	const MODULE_ID = "imbot";
	const BOT_CODE = "openlinemenu";
	
	public static function register(array $params = Array())
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$agentMode = isset($params['AGENT']) && $params['AGENT'] == 'Y';

		if (self::getBotId())
			return $agentMode? "": self::getBotId();

		$botId = \Bitrix\Im\Bot::register(Array(
			'CODE' => self::BOT_CODE,
			'TYPE' => \Bitrix\Im\Bot::TYPE_OPENLINE,
			'MODULE_ID' => self::MODULE_ID,
			'CLASS' => __CLASS__,
			'METHOD_MESSAGE_ADD' => 'onMessageAdd',
			'METHOD_WELCOME_MESSAGE' => 'onChatStart',
			'METHOD_BOT_DELETE' => 'onBotDelete',
			'PROPERTIES' => Array(
				'NAME' => "ITR Bot for Open Channels (example)",
				'WORK_POSITION' => "Get ITR menu for you open channel",
			)
		));
		if ($botId)
		{
			self::setBotId($botId);
		}

		return $agentMode? "": $botId;
	}

	public static function unRegister()
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
			return false;

		$result = \Bitrix\Im\Bot::unRegister(Array('BOT_ID' => self::getBotId()));
		if ($result)
		{
			self::setBotId(0);
		}

		return $result;
	}

	public static function onChatStart($dialogId, $joinFields)
	{
		if ($joinFields['MESSAGE_TYPE'] == IM_MESSAGE_PRIVATE)
			return false;
		
		self::itrRun($dialogId, $joinFields['USER_ID']);
		
		return true;
	}

	public static function onMessageAdd($messageId, $messageFields)
	{
		if ($messageFields['SYSTEM'] == 'Y')
			return false;
		
		self::itrRun($messageFields['DIALOG_ID'], $messageFields['FROM_USER_ID'], $messageFields['MESSAGE']);

		return true;
	}

	public static function onBotDelete($bodId)
	{
		return self::setBotId(0);
	}

	private static function prepareText($message)
	{
		$message = preg_replace("/\[s\].*?\[\/s\]/i", "-", $message);
		$message = preg_replace("/\[[bui]\](.*?)\[\/[bui]\]/i", "$1", $message);
		$message = preg_replace("/\\[url\\](.*?)\\[\\/url\\]/i".BX_UTF_PCRE_MODIFIER, "$1", $message);
		$message = preg_replace("/\\[url\\s*=\\s*((?:[^\\[\\]]++|\\[ (?: (?>[^\\[\\]]+) | (?:\\1) )* \\])+)\\s*\\](.*?)\\[\\/url\\]/ixs".BX_UTF_PCRE_MODIFIER, "$2", $message);
		$message = preg_replace("/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/i", "$2", $message);
		$message = preg_replace("/\[CHAT=([0-9]{1,})\](.*?)\[\/CHAT\]/i", "$2", $message);
		$message = preg_replace("/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/i", "$2", $message);
		$message = preg_replace('#\-{54}.+?\-{54}#s', "", str_replace(array("#BR#"), Array(" "), $message));
		$message = strip_tags($message);

		return trim($message);
	}

	private static function itrRun($dialogId, $userId, $message = '')
	{
		if ($userId <= 0)
			return false;
		
		$menu0 = new Itr\Menu(0);
		$menu0->setText('Main menu (#0)');
		$menu0->addItem(1, 'Text', Itr\Item::sendText('Text message (for #USER_NAME#)'));
		$menu0->addItem(2, 'Text without menu', Itr\Item::sendText('Text message without menu', true));
		$menu0->addItem(3, 'Open menu #1', Itr\Item::openMenu(1));
		$menu0->addItem(0, 'Wait operator answer', Itr\Item::sendText('Wait operator answer', true));

		$menu1 = new Itr\Menu(1);
		$menu1->setText('Second menu (#1)');
		$menu1->addItem(2, 'Transfer to queue', Itr\Item::transferToQueue('Transfer to queue'));
		$menu1->addItem(3, 'Transfer to user', Itr\Item::transferToUser(1, false, 'Transfer to user #1'));
		$menu1->addItem(4, 'Transfer to bot', Itr\Item::transferToBot('marta', false, 'Transfer to bot Marta', 'Marta not found :('));
		$menu1->addItem(5, 'Finish session', Itr\Item::finishSession('Finish session'));
		$menu1->addItem(6, 'Exec function', Itr\Item::execFunction(function($context){
			\Bitrix\Im\Bot::addMessage(Array('BOT_ID' => $context->botId), Array(
				'DIALOG_ID' => $context->dialogId,
				'MESSAGE' => 'Function executed (action)'
			));
		}, 'Function executed (text)'));
		$menu1->addItem(9, 'Back to main menu', Itr\Item::openMenu(0));

		$itr = new Itr\Designer('box', $dialogId, self::getBotId(), $userId);
		$itr->addMenu($menu0);
		$itr->addMenu($menu1);
		$itr->run(self::prepareText($message));

		return true;
	}

	public static function getBotId()
	{
		return \Bitrix\Main\Config\Option::get(self::MODULE_ID, self::BOT_CODE."_bot_id", 0);
	}

	public static function setBotId($id)
	{
		\Bitrix\Main\Config\Option::set(self::MODULE_ID, self::BOT_CODE."_bot_id", $id);
		return true;
	}
}


