<?php
namespace Bitrix\ImConnector\Provider\Network;

use Bitrix\ImConnector\DeliveryMark;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\DI\ServiceLocator;

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
	 * Adds delayed execution of the session action.
	 *
	 * @param $eventName Action type: sessionStart or sessionFinish.
	 * @param array $data Action data.
	 * @param bool $immediately Run action immediately.
	 *
	 * @return Result
	 */
	protected function addEventSession($eventName, array $data, bool $immediately = false): Result
	{
		$result = clone $this->result;

		if($result->isSuccess())
		{
			foreach ($data as $message)
			{
				$args = [
					'LINE_ID' => $this->line,
					'GUID' => $message['chat']['id'],
					'USER' => $message['user']['id'],
					'SESSION_ID' => $message['session']['id'],
					'PARENT_ID' => $message['session']['parent_id'],
					'CLOSE_TERM' => $message['session']['close_term'],
				];

				if ($immediately)
				{
					\Bitrix\ImBot\Service\Openlines::$eventName($args);
				}
				else
				{
					Application::getInstance()->addBackgroundJob(
						[\Bitrix\ImBot\Service\Openlines::class, $eventName],
						[$args],
						Application::JOB_PRIORITY_LOW
					);
				}
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
		$result = clone $this->result;

		if($result->isSuccess())
		{
			$resultRegister = Bot\Network::registerConnector($this->line, $data);
			if (!$resultRegister)
			{
				$error = Bot\Network::getError();

				$result->addError(new Error(
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
		$result = clone $this->result;

		if($result->isSuccess())
		{
			$resultUpdate = Bot\Network::updateConnector($this->line, $data);
			if (!$resultUpdate)
			{
				$error = Bot\Network::getError();

				$result->addError(new Error(
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
		$result = clone $this->result;

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

				$result->addError(new Error(
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
		$result = clone $this->result;

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
		$result = clone $this->result;

		if($result->isSuccess())
		{
			foreach ($data as $messageData)
			{
				if (
					isset($messageData['im'])
					&& isset($messageData['im']['message_id'])
					&& isset($messageData['im']['chat_id'])
				)
				{
					DeliveryMark::setDeliveryMark((int)$messageData['im']['message_id'], (int)$messageData['im']['chat_id']);
				}
			}

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
		$result = clone $this->result;

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
		$result = clone $this->result;

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
	 * @param bool $immediately
	 *
	 * @return Result
	 */
	protected function sessionStart(array $data, bool $immediately = false): Result
	{
		return $this->addEventSession('sessionStart', $data, $immediately);
	}

	/**
	 * @param array $data
	 * @param bool $immediately
	 *
	 * @return Result
	 */
	protected function sessionFinish(array $data, bool $immediately = false): Result
	{
		return $this->addEventSession('sessionFinish', $data, $immediately);
	}

	/**
	 * The removal of the open line of this website from the remote server connectors.
	 *
	 * @param $lineId
	 * @return Result
	 */
	protected function deleteLine($lineId): Result
	{
		$result = clone $this->result;

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
		$result = clone $this->result;
		$resultNetwork = [];

		if(
			$result->isSuccess() &&
			Loader::includeModule(Library::MODULE_ID_OPEN_LINES)
		)
		{
			$statusNetwork = Status::getInstance(Library::ID_NETWORK_CONNECTOR, (int)$lineId);

			if($statusNetwork->isStatus())
			{
				$dataNetwork = $statusNetwork->getData();

				if(!empty($dataNetwork['CODE']))
				{
					$linkNetwork = '';
					$serviceLocator = ServiceLocator::getInstance();
					if($serviceLocator->has('ImConnector.toolsNetwork'))
					{
						/** @var \Bitrix\ImConnector\Tools\Connectors\Network $toolsNetwork */
						$toolsNetwork = $serviceLocator->get('ImConnector.toolsNetwork');
						$linkNetwork = $toolsNetwork->getPublicLink($dataNetwork['CODE']);
					}

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

	/**
	 * Start new multidialog by operator.
	 *
	 * @param array $data
	 * @return Result
	 */
	protected function operatorOpenNewDialog(array $data): Result
	{
		$result = clone $this->result;

		if($result->isSuccess())
		{
			\Bitrix\ImBot\Service\Openlines::operatorOpenNewDialog([
				'LINE_ID' => $data['LINE_ID'],
				'GUID' => $data['GUID'],
				'CHAT_ID' => $data['CHAT_ID'],
				'OPERATOR_ID' => $data['OPERATOR_ID'],
				'MESSAGE_ID' => $data['MESSAGE_ID'],
				'QUOTED_MESSAGE' => $data['QUOTED_MESSAGE'],
				'MESSAGE_TEXT' => $data['MESSAGE_TEXT'] ?? $data['QUOTED_MESSAGE'],
				'MESSAGE_AUTHOR' => $data['MESSAGE_AUTHOR'] ?? 0,
				'SESSION_ID' => $data['SESSION_ID'],
			]);
		}

		return $result;
	}
}