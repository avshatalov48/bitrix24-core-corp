<?php

namespace Bitrix\ImConnector\Provider\Notifications;

use Bitrix\ImConnector\Provider\Base;
use Bitrix\ImConnector\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Notifications\Integration\ImConnector;

class Output extends Base\Output
{

	/**
	 * Output constructor.
	 * @param string $connector ID connector.
	 * @param string|bool $line ID open line.
	 */
	public function __construct(string $connector, $line = false)
	{
		parent::__construct($connector, $line);

		if (!Loader::includeModule('notifications'))
		{
			$this->result->addError(new \Bitrix\ImConnector\Error(
				'Module notifications is not installed',
				'NO_NOTIFICATIONS_MODULE',
				__METHOD__,
				$connector
			));
		}
	}

	/**
	 *
	 *
	 * @see \Bitrix\ImConnector\Output::register()
	 * @param array $data
	 * @return Result
	 */
	public function register(array $data = []) : Result
	{
		$result = new Result();

		if (!Loader::includeModule("notifications"))
		{
			return $result->addError(new Error(Loc::getMessage("IMCONNECTOR_PROVIDER_NOTIFICATIONS_ERROR_MODULE_NOT_INSTALLED")));
		}

		$lineId = (int)$data['LINE_ID'];
		if ($lineId <= 0)
		{
			return $result->addError(new Error('LINE_ID should be positive integer'));
		}

		$registerResult = ImConnector::registerConnector($lineId);
		if (!$registerResult->isSuccess())
		{
			$result->addErrors($registerResult->getErrors());
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function update(array $data = []): Result
	{
		return new Result();
	}

	/**
	 * @param int $lineId
	 * @return Result
	 */
	protected function delete(int $lineId = 0): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			if(empty($lineId))
			{
				$lineId = (int)$this->line;
			}

			if (!Loader::includeModule("notifications"))
			{
				return $result->addError(new Error(Loc::getMessage("IMCONNECTOR_PROVIDER_NOTIFICATIONS_ERROR_MODULE_NOT_INSTALLED")));
			}

			if ($lineId <= 0)
			{
				return $result->addError(new Error('LINE_ID should be positive integer'));
			}

			$resultDelete = ImConnector::unRegisterConnector($lineId);
			if (!$resultDelete->isSuccess())
			{
				$this->result->addErrors($resultDelete->getErrors());
			}
			else
			{
				$result->setResult($resultDelete->getData());
			}
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function sendStatusWriting(array $data): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			foreach ($data as $message)
			{
				ImConnector::sendOperatorStartWriting([
					"LINE_ID" => $this->line,
					"GUID" => $message['chat']['id'],
					"USER" => [
						'ID' => $message['user']['ID'],
						'NAME' => $message['user']['FIRST_NAME'],
						'LAST_NAME' => $message['user']['LAST_NAME'],
						'PERSONAL_GENDER' => $message['user']['GENDER'],
						'PERSONAL_PHOTO' => $message['user']['AVATAR']
					]
				]);
			}

		}

		return $result;
	}

	/**
	 * Sending a message.
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 */
	protected function sendMessage(array $data): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			$data = $this->sendMessagesProcessing($data);

			foreach ($data as $message)
			{
				$sendResult = ImConnector::sendOutgoingMessage($message);
				if (!$sendResult->isSuccess())
				{
					$result->addErrors($sendResult->getErrors());
				}
			}
		}

		return $result;
	}

	/**
	 * Update a message.
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 */
	protected function updateMessage(array $data): Result
	{
		$result = $this->result;

		return $result->addError(new Error('Message update is not supported', 'NOT_SUPPORTED'));
	}

	/**
	 * Delete a message.
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 */
	protected function deleteMessage(array $data): Result
	{
		$result = $this->result;

		return $result->addError(new Error('Message delete is not supported', 'NOT_SUPPORTED'));
	}
}
