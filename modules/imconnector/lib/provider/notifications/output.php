<?php

namespace Bitrix\ImConnector\Provider\Notifications;

use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Provider\Base;
use Bitrix\ImConnector\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Notifications\Integration\ImConnector;
use Bitrix\Notifications\Settings;

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
		$skipTOS = isset($data['SKIP_TOS']) ? (bool)$data['SKIP_TOS'] : false;
		if ($lineId <= 0)
		{
			return $result->addError(new Error('LINE_ID should be positive integer'));
		}

		$registerResult = ImConnector::registerConnector($lineId, $skipTOS);
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
		$result = clone $this->result;

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
				$result->addErrors($resultDelete->getErrors());
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
		$result = clone $this->result;

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
		$result = clone $this->result;

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
		$result = clone $this->result;

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
		$result = clone $this->result;

		return $result->addError(new Error('Message delete is not supported', 'NOT_SUPPORTED'));
	}

	/**
	 * Used for integration with CRM Widget/Button.
	 * @see \Bitrix\Crm\SiteButton\Channel\ChannelOpenLine::getWidgetsById
	 *
	 * @param int $lineId
	 * @return Result
	 */
	protected function infoConnectorsLine($lineId): Result
	{
		$result = clone $this->result;

		$notificationsData = [];
		if (
			$result->isSuccess()
			&& Settings::isScenarioEnabled(Settings::SCENARIO_VIRTUAL_WHATSAPP)
			&& $lineId == \Bitrix\Notifications\Integration\ImConnector::getLineId()
		)
		{
			$notificationsData['id'] = $lineId;

			/** will be overwritten later, in @see \Bitrix\Crm\SiteButton\Channel\ChannelOpenLine::getWidgetsById */
			$notificationsData['name'] = Loc::getMessage("IMCONNECTOR_PROVIDER_NOTIFICATIONS_CONTACT_US_ON_WHATSAPP");
			$notificationsData['icon'] = Connector::getIconByConnector('notifications_virtual_wa');
			$notificationsData['url'] = '';
		}
		$result->setData([
			Library::ID_NOTIFICATIONS_CONNECTOR => $notificationsData
		]);

		return $result;
	}
}
