<?php
namespace Bitrix\ImBot;

class Event
{
	public static function onUserRead($params)
	{
		$botData = \Bitrix\Im\Bot::getCache($params['DIALOG_ID']);
		if (!$botData)
		{
			return true;
		}
		
		if (class_exists($botData['CLASS']) && method_exists($botData['CLASS'], 'onUserRead'))
		{
			return call_user_func_array(array($botData['CLASS'], 'onUserRead'), Array($params));
		}
		
		return true;
	}

	public static function onMessageLike($params)
	{
		if ($params['CHAT']['TYPE'] == IM_MESSAGE_PRIVATE)
		{
			$botId = $params['DIALOG_ID'];
		}
		else
		{
			$botId = $params['MESSAGE']['AUTHOR_ID'];
		}
		
		$botData = \Bitrix\Im\Bot::getCache($botId);
		if (!$botData)
		{
			return true;
		}
		
		Log::write($params, 'MESSAGE LIKE');
		
		if (class_exists($botData['CLASS']) && method_exists($botData['CLASS'], 'onMessageLike'))
		{
			return call_user_func_array(array($botData['CLASS'], 'onMessageLike'), Array($params));
		}
		
		return true;
	}
	
	public static function onStartWriting($params)
	{
		if (empty($params['CHAT']))
		{
			$botId = $params['DIALOG_ID'];
		}
		else
		{
			return true;
		}
		
		$botData = \Bitrix\Im\Bot::getCache($botId);
		if (!$botData)
		{
			return true;
		}
		
		$params['BOT_ID'] = $botId;
		
		Log::write($params, 'START WRITING');
		
		if (class_exists($botData['CLASS']) && method_exists($botData['CLASS'], 'onStartWriting'))
		{
			return call_user_func_array(array($botData['CLASS'], 'onStartWriting'), Array($params));
		}
		
		return true;
	}
	
	public static function onSessionVote($params)
	{
		if (empty($params['CHAT']))
		{
			$botId = $params['DIALOG_ID'];
		}
		else
		{
			return true;
		}
		
		$botData = \Bitrix\Im\Bot::getCache($botId);
		if (!$botData)
		{
			return true;
		}
		
		$params['BOT_ID'] = $botId;
		
		Log::write($params, 'SESSION VOTE');
		
		if (class_exists($botData['CLASS']) && method_exists($botData['CLASS'], 'onSessionVote'))
		{
			return call_user_func_array(array($botData['CLASS'], 'onSessionVote'), Array($params));
		}
		
		return true;
	}
}