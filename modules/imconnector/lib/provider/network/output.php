<?php
namespace Bitrix\ImConnector\Provider\Network;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;

use Bitrix\ImBot\Bot;
use Bitrix\ImBot\Service;

use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Status;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Provider\Base;
use Bitrix\ImConnector\Connectors\Network;

class Output extends Base\Output
{
	/**
	 * @param $eventName
	 * @param array $data
	 * @return Result
	 */
	protected function addEventSession($eventName, array $data): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			foreach ($data as $message)
			{
				$args = [
					[
						'LINE_ID' => $this->line,
						'GUID' => $message['chat']['id'],
						'USER' => $message['user']['id'],
						'SESSION_ID' => $message['session']['id'],
						'PARENT_ID' => $message['session']['parent_id'],
					]
				];

				Application::getInstance()->addBackgroundJob(
					['\Bitrix\ImBot\Service\Openlines', $eventName],
					$args,
					Application::JOB_PRIORITY_LOW
				);
			}
		}

		return $result;
	}

	/**
	 * Output constructor.
	 * @param string $connector ID connector.
	 * @param string|bool $line ID open line.
	 */
	public function __construct(string $connector, $line = false)
	{
		parent::__construct($connector, $line);

		if (!Loader::includeModule('imbot'))
		{
			$this->result->addError(new Error(
				'Unable to load the imbot module',
				'IMBOT_ERROR',
				__METHOD__,
				$connector
			));
		}
	}

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function register(array $data = []): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			$resultRegister = Bot\Network::registerConnector($this->line, $data);
			if (!$resultRegister)
			{
				$error = Bot\Network::getError();

				$this->result->addError(new Error(
					$error->msg,
					$error->code,
					__METHOD__,
					$data
				));
			}
			else
			{
				$result->setResult($resultRegister);
			}
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function update(array $data = []): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			$resultUpdate = Bot\Network::updateConnector($this->line, $data);
			if (!$resultUpdate)
			{
				$error = Bot\Network::getError();

				$this->result->addError(new Error(
					$error->msg,
					$error->code,
					__METHOD__,
					$data
				));
			}
			else
			{
				$result->setResult($resultUpdate);
			}
		}

		return $result;
	}


	/**
	 * @param int $lineId
	 * @return Result
	 */
	protected function delete($lineId = 0): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			if(empty($lineId))
			{
				$lineId = (int)$this->line;
			}

			$resultDelete = Bot\Network::unRegisterConnector($lineId);
			if (!$resultDelete)
			{
				$error = Bot\Network::getError();

				$this->result->addError(new Error(
					$error->msg,
					$error->code,
					__METHOD__
				));
			}
			else
			{
				$result->setResult($resultDelete);
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
				Service\Openlines::operatorStartWriting([
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
				Service\Openlines::operatorMessageAdd($message);
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

		if($result->isSuccess())
		{
			$data = $this->updateMessagesProcessing($data);

			foreach ($data as $message)
			{
				Service\Openlines::operatorMessageUpdate($message);
			}
		}

		return $result;
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

		if($result->isSuccess())
		{
			$data = $this->deleteMessagesProcessing($data);

			foreach ($data as $message)
			{
				Service\Openlines::operatorMessageDelete($message);
			}
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function sessionStart(array $data): Result
	{
		return $this->addEventSession('sessionStart', $data);
	}

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function sessionFinish(array $data): Result
	{
		return $this->addEventSession('sessionFinish', $data);
	}

	/**
	 * The removal of the open line of this website from the remote server connectors.
	 *
	 * @param $lineId
	 * @return Result
	 */
	protected function deleteLine($lineId): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			$result = $this->delete($lineId);
		}

		return $result;
	}

	/**
	 * Receive information about all the connected connectors.
	 *
	 * @param $lineId
	 * @return Result
	 */
	protected function infoConnectorsLine($lineId): Result
	{
		$result = $this->result;
		$resultNetwork = [];

		if(
			$result->isSuccess() &&
			Loader::includeModule(Library::MODULE_ID_OPEN_LINES)
		)
		{
			$statusNetwork = Status::getInstance(Library::ID_NETWORK_CONNECTOR, $lineId);

			if($statusNetwork->isStatus())
			{
				$dataNetwork = $statusNetwork->getData();

				if(!empty($dataNetwork['CODE']))
				{
					$linkNetwork = Network::getPublicLink($dataNetwork['CODE']);

					if(!empty($linkNetwork))
					{
						$resultNetwork['id'] = $dataNetwork['CODE'];
						$resultNetwork['url'] = $linkNetwork;
						$resultNetwork['url_im'] = $linkNetwork;

						if(!Library::isEmpty($dataNetwork['NAME']))
						{
							$resultNetwork['name'] = $dataNetwork['NAME'];
						}

						if(!empty($dataNetwork['AVATAR']))
						{
							$resultNetwork['picture']['url'] = \CFile::GetPath($dataNetwork['AVATAR']);
						}
					}
				}
			}
		}
		$result->setData([
			Library::ID_NETWORK_CONNECTOR => $resultNetwork
		]);

		return $result;
	}
}