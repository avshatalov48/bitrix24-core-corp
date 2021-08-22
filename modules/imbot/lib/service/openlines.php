<?php
namespace Bitrix\ImBot\Service;

use Bitrix\ImConnector\Provider;

use Bitrix\Main\Loader;
use Bitrix\ImBot;
use Bitrix\ImBot\Log;
use Bitrix\ImBot\Error;

/**
 * Class Openlines
 *
 * @package Bitrix\ImBot\Service
 */
class Openlines
{
	public const
		BOT_CODE = 'network',
		SERVICE_CODE = 'openlines',

		COMMAND_OPERATOR_MESSAGE_ADD = 'operatorMessageAdd',
		COMMAND_OPERATOR_MESSAGE_UPDATE = 'operatorMessageUpdate',
		COMMAND_OPERATOR_MESSAGE_DELETE = 'operatorMessageDelete',
		COMMAND_OPERATOR_MESSAGE_RECEIVED = 'operatorMessageReceived',
		COMMAND_OPERATOR_START_WRITING = 'operatorStartWriting',
		COMMAND_START_DIALOG_SESSION = 'startDialogSession',
		COMMAND_FINISH_DIALOG_SESSION = 'finishDialogSession'
	;

	/** @var ImBot\Http */
	protected static $httpClient;

	//region Incoming

	/**
	 * @see \Bitrix\ImBot\Controller::sendToService
	 * @param string $command
	 * @param array $params
	 *
	 * @return Error|bool|array
	 */
	public static function onReceiveCommand($command, $params)
	{
		unset(
			$params['BX_BOT_NAME'],
			$params['BX_SERVICE_NAME'],
			$params['BX_COMMAND'],
			$params['BX_TYPE']
		);

		if (!Loader::includeModule('imconnector'))
		{
			return false;
		}

		$params['BX_COMMAND'] = $command;

		Log::write($params, 'NETWORK SERVICE');

		$providerResult = Provider::getProviderForConnectorInput(self::BOT_CODE, $params);

		if($providerResult->isSuccess())
		{
			/** @var \Bitrix\ImConnector\Provider\Network\Input $provider */
			$provider = $providerResult->getResult();
			$resultReception = $provider->reception();

			if ($resultReception->isSuccess())
			{
				$result = true;
			}
			else
			{
				$result = false;
			}
		}
		else
		{
			return false;
		}

		if($result)
		{
			$result = [
				'RESULT' => 'OK'
			];
		}
		else if (is_null($result))
		{
			$result = new Error(
				__METHOD__,
				'UNKNOWN_COMMAND',
				'Command "'.$command.'" is not found.',
				$params
			);
		}
		elseif (!$result)
		{
			$result = new Error(
				__METHOD__,
				'ERROR_COMMAND',
				'Command "'.$command.'" execute with errors.',
				$params
			);
		}

		return $result;
	}

	//endregion


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

	//region Outgoing

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorMessageAdd
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function operatorMessageAdd($params)
	{
		$params['MESSAGE_TEXT'] = self::prepareMessage($params['MESSAGE_TEXT']);

		$http = self::instanceHttpClient();
		$query = $http->query(
			self::COMMAND_OPERATOR_MESSAGE_ADD,
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

		$http = self::instanceHttpClient();
		$query = $http->query(
			self::COMMAND_OPERATOR_MESSAGE_UPDATE,
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorMessageDelete
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function operatorMessageDelete($params)
	{
		$http = self::instanceHttpClient();
		$query = $http->query(
			self::COMMAND_OPERATOR_MESSAGE_DELETE,
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorStartWriting
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function operatorStartWriting($params)
	{
		$http = self::instanceHttpClient();
		$query = $http->query(
			self::COMMAND_OPERATOR_START_WRITING,
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\StartDialogSession
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function sessionStart($params)
	{
		$http = self::instanceHttpClient();
		$query = $http->query(
			self::COMMAND_START_DIALOG_SESSION,
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\FinishDialogSession
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function sessionFinish($params)
	{
		$http = self::instanceHttpClient();
		$query = $http->query(
			self::COMMAND_FINISH_DIALOG_SESSION,
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	/**
	 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorMessageReceived
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function operatorMessageReceived($params)
	{
		$http = self::instanceHttpClient();
		$query = $http->query(
			self::COMMAND_OPERATOR_MESSAGE_RECEIVED,
			$params
		);
		if (isset($query->error))
		{
			return false;
		}

		return true;
	}

	//endregion


	/**
	 * Returns web client.
	 *
	 * @return ImBot\Http
	 */
	protected static function instanceHttpClient(): ImBot\Http
	{
		if (!(self::$httpClient instanceof ImBot\Http))
		{
			self::$httpClient = new ImBot\Http(self::BOT_CODE);
		}

		return self::$httpClient;
	}

	/**
	 * Replace web client.
	 *
	 * @return ImBot\Http
	 */
	public static function initHttpClient(ImBot\Http $httpClient): void
	{
		self::$httpClient = $httpClient;
	}
}