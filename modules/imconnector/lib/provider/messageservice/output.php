<?php
namespace Bitrix\ImConnector\Provider\Messageservice;

use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Provider\Base;
use Bitrix\ImConnector\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\MessageService\Sender\Sms\Ednaru;
use Bitrix\MessageService\Sender\SmsManager;

class Output extends Base\Output
{
	/* @var Ednaru|null */
	private $sender;

	/**
	 * Output constructor.
	 * @param string $connector ID connector.
	 * @param string|bool $line ID open line.
	 */
	public function __construct(string $connector, $line = false)
	{
		parent::__construct($connector, $line);

		if (Loader::includeModule('messageservice'))
		{
			$this->sender = SmsManager::getSenderById('ednaru');
		}
		else
		{
			$this->result->addError(new \Bitrix\ImConnector\Error(
				'Module messageservice is not installed',
				'NO_MESSAGESERVICE_MODULE',
				__METHOD__,
				$connector
			));
		}
	}

	/**
	 * Returns connector settings.
	 *
	 * @return Result
	 */
	public function readSettings(): Result
	{
		$result = clone $this->result;

		if (
			is_object($this->sender)
			&& $result->isSuccess()
		)
		{
			$connectionOptions = $this->sender->getOwnerInfo();
			$result->setData($connectionOptions);
		}

		return $result;
	}

	/**
	 * Registers connection on messageservice side.
	 * @param array $registerFields
	 *
	 * @return Result
	 */
	public function register(array $registerFields): Result
	{
		$result = clone $this->result;

		if (
			is_object($this->sender)
			&& $result->isSuccess()
		)
		{
			$registerResult = $this->sender->register($registerFields);
			if (!$registerResult->isSuccess())
			{
				$result->addErrors($registerResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * Tests connection.
	 *
	 * @return Result
	 */
	public function testConnect(): Result
	{
		$result = clone $this->result;

		if (
			is_object($this->sender)
			&& $result->isSuccess()
		)
		{
			$testConnectionResult = $this->sender->testConnection();
			if (!$testConnectionResult->isSuccess())
			{
				$result->addErrors($testConnectionResult->getErrors());
			}
		}

		return $result;
	}

	/**
	 * Returns callback url for receiving webhooks.
	 *
	 * @return Result
	 */
	public function getCallbackUrl(): Result
	{
		$result = clone $this->result;

		if (
			is_object($this->sender)
			&& $result->isSuccess()
		)
		{
			$callbackUrl = $this->sender->getCallbackUrl();
			$result->setData(['url' => $callbackUrl]);
		}

		return $result;
	}

	/**
	 * Deletes connection on messageservice side.
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function unregister(): Result
	{
		$result = clone $this->result;

		if (
			is_object($this->sender)
			&& $result->isSuccess()
		)
		{
			if (!$this->sender->clearOptions())
			{
				return $result->addError(
					new Error(Loc::getMessage('IMCONNECTOR_PROVIDER_MESSAGESERVICE_ERROR_DELETE_CONNECTION'))
				);
			}
		}

		return $result;
	}

	public function sendMessage(array $data): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			$data = $this->sendMessagesProcessing($data);

			foreach ($data as $message)
			{
				$messageFields = $this->prepareMessageToSend($message);
				$sendResult = $this->sender->sendMessage($messageFields);
				if (!$sendResult->isSuccess())
				{
					$result->addErrors($sendResult->getErrors());
				}
				else
				{
					$deliveryParams = [
						'im' => $message['im'],
						'message' => [
							'id' => $sendResult->getExternalId()
						],
						'chat' => [
							'id' => $message['chat']['id']
						],
					];
					$deliveryResult = $this->sendDeliveryRequest($deliveryParams);
					if (!$deliveryResult->isSuccess())
					{
						$result->addErrors($deliveryResult->getErrors());
					}
				}
			}
		}

		return $result;
	}

	private function sendDeliveryRequest(array $deliveryRequest): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			$deliveryRequest['CONNECTOR'] = Library::ID_EDNA_WHATSAPP_CONNECTOR;
			$deliveryRequest['BX_COMMAND'] = 'receivingStatusDelivery';

			$portal = new \Bitrix\ImConnector\Input($deliveryRequest);
			$receptionResult = $portal->reception();
			if (!$receptionResult->isSuccess())
			{
				$result->addErrors($receptionResult->getErrors());
			}
		}

		return $result;
	}

	private function prepareMessageToSend(array $message): array
	{
		$chatData = explode('@', $message['chat']['id']);
		[$messageTo, $messageFrom] = $chatData;


		$messageBody =
			!Library::isEmpty($message['message']['text'])
				? $message['message']['text']
				: ''
		;

		if (isset($message['message']['files']) && count($message['message']['files']) > 0)
		{
			foreach ($message['message']['files'] as $file)
			{
				$messageBody .= ' '. $file['link'];
			}
		}

		return [
			'MESSAGE_FROM' => $messageFrom,
			'MESSAGE_TO' => $messageTo,
			'MESSAGE_BODY' => $messageBody,
		];
	}
}
