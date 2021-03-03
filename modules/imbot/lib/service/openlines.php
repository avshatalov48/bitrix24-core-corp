<?php
namespace Bitrix\ImBot\Service;

/**
 * Class Openlines
 *
 * @package Bitrix\ImBot\Service
 */
class Openlines
{
	const BOT_CODE = "network";
	const SERVICE_CODE = "openlines";

	/**
	 * @param string $command
	 * @param array $params
	 *
	 * @return \Bitrix\ImBot\Error|bool|array
	 */
	public static function onReceiveCommand($command, $params)
	{
		unset(
			$params['BX_BOT_NAME'],
			$params['BX_SERVICE_NAME'],
			$params['BX_COMMAND'],
			$params['BX_TYPE']
		);

		if (!\Bitrix\Main\Loader::includeModule('imopenlines'))
		{
			return false;
		}

		\Bitrix\ImBot\Log::write(Array($command,$params), 'NETWORK SERVICE');

		$network = new \Bitrix\ImOpenLines\Network();
		$result = $network->onReceiveCommand($command, $params);
		if($result)
		{
			$result = Array('RESULT' => 'OK');
		}
		else if (is_null($result))
		{
			$result = new \Bitrix\ImBot\Error(__METHOD__, 'UNKNOWN_COMMAND', 'Command "'.$command.'" is not found.', [$command, $params]);
		}
		else if (!$result)
		{
			$result = new \Bitrix\ImBot\Error(__METHOD__, 'ERROR_COMMAND', 'Command "'.$command.'" execute with errors.', [$command, $params]);
		}

		return $result;
	}

	/**
	 * Removes mentions from message.
	 *
	 * @param string $messageText
	 *
	 * @return string
	 */
	private static function prepareMessage($messageText)
	{
		$messageText = $messageText === '0' ? '#ZERO#' : $messageText;
		$messageText = preg_replace("/\\[CHAT=[0-9]+\\](.*?)\\[\\/CHAT\\]/", "\\1",  $messageText);
		$messageText = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1",  $messageText);

		return $messageText;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorMessageAdd
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function operatorMessageAdd($params)
	{
		$params['MESSAGE_TEXT'] = self::prepareMessage($params['MESSAGE_TEXT']);

		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'operatorMessageAdd',
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorMessageUpdate
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function operatorMessageUpdate($params)
	{
		$params['MESSAGE_TEXT'] = self::prepareMessage($params['MESSAGE_TEXT']);

		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'operatorMessageUpdate',
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network::operatorMessageDelete
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function operatorMessageDelete($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'operatorMessageDelete',
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network::operatorStartWriting
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function operatorStartWriting($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'operatorStartWriting',
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network::operatorSessionStart
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function sessionStart($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'startDialogSession',
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network::operatorSessionFinish
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function sessionFinish($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'finishDialogSession',
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network::operatorMessageReceived
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function operatorMessageReceived($params)
	{
		$http = new \Bitrix\ImBot\Http(self::BOT_CODE);
		$query = $http->query(
			'operatorMessageReceived',
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}
}