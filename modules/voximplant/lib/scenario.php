<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\Web\Json;

class Scenario
{
	protected $call;

	const COMMAND_WAIT = 'wait';
	const COMMAND_ANSWER = 'answer';
	const COMMAND_CONNECTION_ERROR = 'connectionError';
	const COMMAND_HOLD = 'hold';
	const COMMAND_UNHOLD = 'unhold';
	const COMMAND_DEQUEUE = 'dequeue';
	const COMMAND_START_TRANSFER = 'startTransfer';
	const COMMAND_CANCEL_TRANSFER = 'cancelTransfer';
	const COMMAND_COMPLETE_TRANSFER = 'completeTransfer';
	const COMMAND_CANCEL_EXTERNAL_CALL = 'cancelExternalCall';


	public function __construct(Call $call)
	{
		$this->call = $call;
	}

	public function sendWait($userId, $waitResponse = false)
	{
		return $this->send([
			'COMMAND' => static::COMMAND_WAIT,
			'USER_ID' => $userId
		], $waitResponse);
	}

	public function sendAnswer($userId, $waitResponse = false)
	{
		return $this->send([
			'COMMAND' => static::COMMAND_ANSWER,
			'USER_ID' => $userId
		], $waitResponse);
	}

	public function sendConnectionError($userId, $error, $waitResponse = false)
	{
		return $this->send([
			'COMMAND' => static::COMMAND_CONNECTION_ERROR,
			'USER_ID' => $userId,
			'ERROR' => $error
		], $waitResponse);
	}

	public function sendDequeue($userId, $waitResponse = false)
	{
		return $this->send([
			'COMMAND' => static::COMMAND_DEQUEUE,
			'OPERATOR_ID' => $userId,
			'OPERATOR' => \CVoxImplantIncoming::getUserInfo($userId)
		], $waitResponse);
	}

	public function sendHold($userId, $waitResponse = false)
	{
		return $this->send([
			'COMMAND' => static::COMMAND_HOLD,
			'OPERATOR_ID' => $userId,
		], $waitResponse);
	}

	public function sendUnHold($userId, $waitResponse = false)
	{
		return $this->send([
			'COMMAND' => static::COMMAND_UNHOLD,
			'OPERATOR_ID' => $userId,
		], $waitResponse);
	}

	public function sendStartTransfer($userId, $transferCallId, $forwardConfig, $waitResponse = false)
	{
		return $this->send([
			'COMMAND' => static::COMMAND_START_TRANSFER,
			'TRANSFER_CALL_ID' => $transferCallId,
			'FORWARD_CONFIG' => $forwardConfig,
			'OPERATOR_ID' => $userId,
		], $waitResponse);
	}

	public function sendCancelTransfer($userId, $waitResponse = false)
	{
		return $this->send([
			'COMMAND' => static::COMMAND_CANCEL_TRANSFER,
			'OPERATOR_ID' => $userId,
		], $waitResponse);
	}

	public function sendCompleteTransfer($userId, $waitResponse = false)
	{
		return $this->send([
			'COMMAND' => static::COMMAND_COMPLETE_TRANSFER,
			'OPERATOR_ID' => $userId,
		], $waitResponse);
	}

	public function sendCancelExternalCall($userId, $waitResponse = false)
	{
		return $this->send([
			'COMMAND' => static::COMMAND_CANCEL_EXTERNAL_CALL,
			'OPERATOR_ID' => $userId,
		], $waitResponse);
	}

	/**
	 * Sends command to the scenario instance.
	 *
	 * @param array $command
	 * @param bool $waitResponse
	 * @return Result
	 */
	protected function send(array $command, $waitResponse): Result
	{
		$result = new Result();

		if(!isset($command['CALL_ID']))
		{
			$command['CALL_ID'] = $this->call->getCallId();
		}

		$httpClient = HttpClientFactory::create(array(
			'waitResponse' => $waitResponse
		));
		$queryResult = $httpClient->query('POST', $this->call->getAccessUrl(), Json::encode($command));

		if($waitResponse)
		{
			if ($queryResult === false)
			{
				$httpClientErrors = $httpClient->getError();
				if(!empty($httpClientErrors))
				{
					foreach ($httpClientErrors as $code => $message)
					{
						$result->addError(new \Bitrix\Main\Error($message, $code));
					}
				}
			}

			$responseStatus = $httpClient->getStatus();
			if ($responseStatus == 200)
			{
				// nothing here
			}
			else if ($httpClient->getStatus() == 404)
			{
				$result->addError(new \Bitrix\Main\Error('Call scenario is not running', 'NOT_FOUND'));
			}
			else
			{
				$result->addError(new \Bitrix\Main\Error("Scenario server returns code " . $httpClient->getStatus()));

			}
		}

		return $result;
	}
}