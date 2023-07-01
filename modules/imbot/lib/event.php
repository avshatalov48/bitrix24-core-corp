<?php
namespace Bitrix\ImBot;

/**
 * Bot event dispatcher.
 * @package \Bitrix\ImBot
 */
class Event
{
	/**
	 * Handler for "im:OnAfterUserRead" event.
	 * @see \CIMMessage::SetReadMessage
	 *
	 * @param array $params
	 * @return bool
	 */
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

	/**
	 * Handler for "im:OnAfterChatRead" event.
	 * @see \CIMChat::SetReadMessage
	 *
	 * @param array $params
	 * @return bool
	 */
	public static function onChatRead($params)
	{
		$botList = [];
		$relations = \CIMChat::GetRelationById($params['CHAT_ID'], false, false, false);
		foreach ($relations as $relation)
		{
			if ($relation['EXTERNAL_AUTH_ID'] === \Bitrix\Im\Bot::EXTERNAL_AUTH_ID)
			{
				$botList[(int)$relation['USER_ID']] = (int)$relation['USER_ID'];
			}
		}

		$result = true;
		foreach ($botList as $botId)
		{
			$botData = \Bitrix\Im\Bot::getCache($botId);
			if (!$botData)
			{
				continue;
			}

			if (class_exists($botData['CLASS']) && method_exists($botData['CLASS'], 'onChatRead'))
			{
				$result = call_user_func_array([$botData['CLASS'], 'onChatRead'], [$params]);
			}
		}

		return $result;
	}

	/**
	 * Handler for "im:OnAfterMessagesLike" event.
	 * @see \CIMMessenger::Like
	 *
	 * @param array $params
	 * @return bool
	 */
	public static function onMessageLike($params)
	{
		if ($params['CHAT']['TYPE'] == \IM_MESSAGE_PRIVATE)
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

	/**
	 * Handler for "im:OnStartWriting" event.
	 * @see \CIMMessenger::StartWriting
	 *
	 * @param array $params
	 * @return bool
	 */
	public static function onStartWriting($params)
	{
		$botList = [];
		if (empty($params['CHAT']))
		{
			$botList[] = (int)$params['DIALOG_ID'];
		}
		elseif (!empty($params['RELATION']))
		{
			foreach ($params['RELATION'] as $relation)
			{
				if ($relation['EXTERNAL_AUTH_ID'] === \Bitrix\Im\Bot::EXTERNAL_AUTH_ID)
				{
					$botList[(int)$relation['USER_ID']] = (int)$relation['USER_ID'];
				}
			}
		}

		$result = true;
		foreach ($botList as $botId)
		{
			$botData = \Bitrix\Im\Bot::getCache($botId);
			if (!$botData)
			{
				continue;
			}

			if (
				class_exists($botData['CLASS'], true)
				&& method_exists($botData['CLASS'], 'onStartWriting')
			)
			{
				$params['BOT_ID'] = $botId;

				Log::write($params, 'START WRITING');

				$result = call_user_func([$botData['CLASS'], 'onStartWriting'], $params);
			}
		}

		return $result;
	}

	/**
	 * Handler for "im:OnSessionVote" event.
	 * @see \CIMMessenger::LinesSessionVote
	 * @see \Bitrix\Imbot\Bot\NetworkBot::onSessionVote
	 *
	 * @param array $params
	 * @return bool
	 */
	public static function onSessionVote($params)
	{
		$botList = [];
		if (empty($params['CHAT']))
		{
			$botList[] = (int)$params['DIALOG_ID'];
		}
		elseif (!empty($params['RELATION']))
		{
			foreach ($params['RELATION'] as $relation)
			{
				if ($relation['EXTERNAL_AUTH_ID'] === \Bitrix\Im\Bot::EXTERNAL_AUTH_ID)
				{
					$botList[(int)$relation['USER_ID']] = (int)$relation['USER_ID'];
				}
			}
		}

		$result = true;
		foreach ($botList as $botId)
		{
			$botData = \Bitrix\Im\Bot::getCache($botId);
			if (!$botData)
			{
				continue;
			}

			if (
				class_exists($botData['CLASS'])
				&& method_exists($botData['CLASS'], 'onSessionVote')
			)
			{
				$params['BOT_ID'] = $botId;

				Log::write($params, 'SESSION VOTE');

				$result = call_user_func([$botData['CLASS'], 'onSessionVote'], $params);
			}
		}

		return $result;
	}
}