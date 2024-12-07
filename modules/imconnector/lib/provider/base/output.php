<?php
namespace Bitrix\ImConnector\Provider\Base;

use Bitrix\ImConnector\Error,
	Bitrix\ImConnector\Result,
	Bitrix\ImConnector\Library,
	Bitrix\ImConnector\Connector;

use \Bitrix\Main\Event,
	\Bitrix\Main\Localization\Loc;

class Output
{
	protected const
		TYPE_BITRIX24 = 'B24',
		TYPE_CP = 'CP',

		STATIC_FUNCTIONS = [
			'deleteline',
			'infoconnectorsline'
		],

		DYNAMIC_FUNCTIONS = [
			'sendmessage',
			'updatemessage',
			'deletemessage',
		];

	/** @var Result */
	protected $result;

	/** @var string */
	protected $connector;

	/** @var string */
	protected $line;

	/**
	 * Output constructor.
	 * @param string $connector ID connector.
	 * @param string|bool $line ID open line.
	 */
	public function __construct(string $connector, $line = false)
	{
		$this->result = new Result();
		Library::loadMessages();

		$this->connector = $connector;
		$this->line = $line;
	}

	//region Method call

	/**
	 * Magic method for handling dynamic methods.
	 *
	 * @param string $name The name of the called method.
	 * @param array $arguments The set of parameters passed to the method.
	 * @return Result
	 */
	public function call($name, $arguments): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			if (method_exists($this, $name))
			{
				$result = $this->validationMethodCall($name);
				if ($result->isSuccess())
				{
					$result = call_user_func_array([$this, $name], $arguments);
				}
			}
			else
			{
				$result = $this->query($name, $arguments);
			}
		}

		return $result;
	}

	/**
	 * @param $name
	 * @return Result
	 */
	protected function validationMethodCall($name): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			if ($this->connector === 'all')
			{
				if (in_array(mb_strtolower($name), self::DYNAMIC_FUNCTIONS, false))
				{
					$result->addError(new Error(
						Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_GENERAL_REQUEST_DYNAMIC_METHOD'),
						Library::ERROR_IMCONNECTOR_PROVIDER_GENERAL_REQUEST_DYNAMIC_METHOD,
						__METHOD__,
						$name
					));
				}
			}
			elseif (in_array(mb_strtolower($name), self::STATIC_FUNCTIONS, false))
			{
				$result->addError(new Error(
					Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_GENERAL_REQUEST_NOT_DYNAMIC_METHOD'),
					Library::ERROR_IMCONNECTOR_PROVIDER_GENERAL_REQUEST_NOT_DYNAMIC_METHOD,
					__METHOD__,
					$name
				));
			}
		}

		return $result;
	}

	/**
	 * @param $command
	 * @param array $data
	 * @return Result
	 */
	protected function query($command, array $data): Result
	{
		return $this->result;
	}

	//endregion

	//region Commands

	/**
	 * @param array $messages
	 * @return mixed
	 */
	protected function sendMessagesProcessing(array $messages): array
	{
		foreach ($messages as $cell=>$message)
		{
			$messages[$cell] = Connector::initConnectorHandler($this->connector)->sendMessageProcessing($message, $this->line);
		}

		return $messages;
	}

	/**
	 * @param array $messages
	 * @return array
	 */
	protected function updateMessagesProcessing(array $messages): array
	{
		foreach ($messages as $cell=>$message)
		{
			$messages[$cell] = Connector::initConnectorHandler($this->connector)->updateMessageProcessing($message, $this->line);
		}

		return $messages;
	}

	/**
	 * @param array $messages
	 * @return array
	 */
	protected function deleteMessagesProcessing(array $messages): array
	{
		foreach ($messages as $cell=>$message)
		{
			$messages[$cell] = Connector::initConnectorHandler($this->connector)->deleteMessageProcessing($message, $this->line);
		}

		return $messages;
	}

	/**
	 * Sending a message.
	 * Call from @see \Bitrix\ImOpenLines\Connector::sendMessage
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 */
	protected function sendMessage(array $data): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			$data = $this->sendMessagesProcessing($data);

			$event = new Event(Library::MODULE_ID, Library::EVENT_SEND_MESSAGE_CUSTOM_CONNECTOR, ['CONNECTOR' => $this->connector, 'LINE' => $this->line, 'DATA' => $data]);
			$event->send();
		}

		return $result;
	}

	/**
	 * Update a message.
	 * @see \Bitrix\ImOpenLines\Connector::updateMessage
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 */
	protected function updateMessage(array $data): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			$data = $this->updateMessagesProcessing($data);

			$event = new Event(Library::MODULE_ID, Library::EVENT_UPDATE_MESSAGE_CUSTOM_CONNECTOR, ['CONNECTOR' => $this->connector, 'LINE' => $this->line,'DATA' => $data]);
			$event->send();
		}

		return $result;
	}

	/**
	 * Delete a message.
	 * @see \Bitrix\ImOpenLines\Connector::deleteMessage
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 */
	protected function deleteMessage(array $data): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			$data = $this->deleteMessagesProcessing($data);

			$event = new Event(Library::MODULE_ID, Library::EVENT_DELETE_MESSAGE_CUSTOM_CONNECTOR, ['CONNECTOR' => $this->connector, 'LINE' => $this->line,'DATA' => $data]);
			$event->send();
		}

		return $result;
	}

	/**
	 * @see \Bitrix\ImOpenLines\Connector::onSessionStart
	 * @param array $data
	 * @return Result
	 */
	protected function sessionStart(array $data): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			$event = new Event(
				Library::MODULE_ID,
				Library::EVENT_DIALOG_START,
				[
					'CONNECTOR' => $this->connector,
					'LINE' => $this->line,
					'DATA' => $data
				]
			);
			$event->send();
		}

		return $result;
	}

	/**
	 * @see \Bitrix\ImOpenLines\Connector::onSessionFinish
	 * @param array $data
	 * @return Result
	 */
	protected function sessionFinish(array $data): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			$event = new Event(
				Library::MODULE_ID,
				Library::EVENT_DIALOG_FINISH,
				[
					'CONNECTOR' => $this->connector,
					'LINE' => $this->line,
					'DATA' => $data
				]
			);
			$event->send();
		}

		return $result;
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

		if ($result->isSuccess())
		{
			$event = new Event(Library::MODULE_ID, Library::EVENT_DELETE_LINE, ['LINE_ID' => $lineId]);
			$event->send();
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
		return $this->result;
	}

	//endregion
}
