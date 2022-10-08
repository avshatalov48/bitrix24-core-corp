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
 *
 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorMessageAdd
 * @method bool operatorMessageAdd(array $params)
 *
 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorMessageUpdate
 * @method bool operatorMessageUpdate(array $params)
 *
 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorMessageDelete
 * @method bool operatorMessageDelete(array $params)
 *
 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorStartWriting
 * @method bool operatorStartWriting(array $params)
 *
 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorMessageReceived
 * @method bool operatorMessageReceived(array $params)
 *
 * @see \Bitrix\Botcontroller\Bot\Network\Command\StartDialogSession
 * @method bool sessionStart(array $params)
 *
 * @see \Bitrix\Botcontroller\Bot\Network\Command\FinishDialogSession
 * @method bool sessionFinish(array $params)
 *
 * @see \Bitrix\Botcontroller\Bot\Network\Command\OperatorQueueNumber
 * @method bool operatorQueueNumber(array $params)
 *
 */
class Openlines
{
	public const
		BOT_CODE = 'network',

		COMMAND_OPERATOR_MESSAGE_ADD = 'operatorMessageAdd',
		COMMAND_OPERATOR_MESSAGE_UPDATE = 'operatorMessageUpdate',
		COMMAND_OPERATOR_MESSAGE_DELETE = 'operatorMessageDelete',
		COMMAND_OPERATOR_MESSAGE_RECEIVED = 'operatorMessageReceived',
		COMMAND_OPERATOR_START_WRITING = 'operatorStartWriting',
		COMMAND_START_DIALOG_SESSION = 'startDialogSession',
		COMMAND_FINISH_DIALOG_SESSION = 'finishDialogSession',
		COMMAND_OPERATOR_QUEUE_NUMBER = 'operatorQueueNumber'
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

	//region Outgoing

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
	 * @param string $command
	 * @param array $params
	 *
	 * @return bool
	 */
	public static function __callStatic(string $command, array $params)
	{
		$aliasList = [
			'sessionStart' => self::COMMAND_START_DIALOG_SESSION,
			'sessionFinish' => self::COMMAND_FINISH_DIALOG_SESSION,
		];
		if (isset($aliasList[$command]))
		{
			$command = $aliasList[$command];
		}

		if (
			(
				$command === self::COMMAND_OPERATOR_MESSAGE_ADD
				|| $command === self::COMMAND_OPERATOR_MESSAGE_UPDATE
			)
			&& isset($params[0], $params[0]['MESSAGE_TEXT'])
		)
		{
			$params[0]['MESSAGE_TEXT'] = self::prepareMessage($params[0]['MESSAGE_TEXT']);
		}

		return self::sendCommand($command, $params[0]);
	}

	/**
	 * @param string $command
	 * @param array $params
	 *
	 * @return bool
	 */
	protected static function sendCommand(string $command, array $params): bool
	{
		$constList = (new \ReflectionClass(__CLASS__))->getConstants();
		$whiteList = [];
		foreach ($constList as $const => $value)
		{
			if (strpos($const, 'COMMAND_', 0) === 0)
			{
				$whiteList[] = $value;
			}
		}
		if (in_array($command, $whiteList, true))
		{
			$http = self::instanceHttpClient();
			$query = $http->query($command, $params, true);

			return !isset($query->error);
		}

		return false;
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
	 * @param ImBot\Http $httpClient
	 * @return void
	 */
	public static function initHttpClient(ImBot\Http $httpClient): void
	{
		self::$httpClient = $httpClient;
	}
}