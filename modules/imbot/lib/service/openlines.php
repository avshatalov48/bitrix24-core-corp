<?php
namespace Bitrix\ImBot\Service;

use Bitrix\ImBot\Log;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Openlines
{
	const BOT_CODE = "network";
	const SERVICE_CODE = "openlines";

	public static function onReceiveCommand($command, $params)
	{
		unset($params['BX_BOT_NAME']);
		unset($params['BX_SERVICE_NAME']);
		unset($params['BX_COMMAND']);
		unset($params['BX_TYPE']);

		if (!\Bitrix\Main\Loader::includeModule('imopenlines'))
			return false;

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

	public static function operatorMessageAdd($params)
	{
		$params['MESSAGE_TEXT'] = $params['MESSAGE_TEXT'] === '0'? '#ZERO#': $params['MESSAGE_TEXT'];
		$params['MESSAGE_TEXT'] = preg_replace("/\\[CHAT=[0-9]+\\](.*?)\\[\\/CHAT\\]/", "\\1",  $params['MESSAGE_TEXT']);
		$params['MESSAGE_TEXT'] = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1",  $params['MESSAGE_TEXT']);

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

	public static function operatorMessageUpdate($params)
	{
		$params['MESSAGE_TEXT'] = $params['MESSAGE_TEXT'] === '0'? '#ZERO#': $params['MESSAGE_TEXT'];
		$params['MESSAGE_TEXT'] = preg_replace("/\\[CHAT=[0-9]+\\](.*?)\\[\\/CHAT\\]/", "\\1",  $params['MESSAGE_TEXT']);
		$params['MESSAGE_TEXT'] = preg_replace("/\\[USER=[0-9]+\\](.*?)\\[\\/USER\\]/", "\\1",  $params['MESSAGE_TEXT']);

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