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
	protected const TYPE_BITRIX24 = 'B24';
	protected const TYPE_CP = 'CP';
	protected const STATIC_FUNCTIONS = [
		'deleteline',
		'infoconnectorsline'
	];
	protected const DYNAMIC_FUNCTIONS = [
		'sendmessage',
		'updatemessage',
		'deletemessage',
	];

	/** @var Result */
	protected $result;

	protected $connector;
	protected $line;

	protected $licenceCode = '';
	protected $domain = '';
	protected $type = '';

	/**
	 * Output constructor.
	 * @param string $connector ID connector.
	 * @param string|bool $line ID open line.
	 */
	public function __construct(string $connector, $line = false)
	{
		$this->result = new Result();
		Library::loadMessages();

		$this->licenceCode = $this->getLicenceCode();
		$this->type = $this->getPortalType();
		$this->domain = Connector::getDomainDefault();

		$this->connector = $connector;
		$this->line = $line;
	}

	/**
	 * Returns the type of the portal.
	 *
	 * @return string
	 */
	protected function getPortalType(): string
	{
		if(defined('BX24_HOST_NAME'))
		{
			$type = self::TYPE_BITRIX24;
		}
		else
		{
			$type = self::TYPE_CP;
		}
		return $type;
	}

	/**
	 * The query hash of the license key.
	 *
	 * @param $type. The type of portal.
	 * @param $str.
	 * @return string
	 */
	protected function requestSign($type, $str): string
	{
		$result = '';

		if (
			$type == self::TYPE_BITRIX24 &&
			function_exists('bx_sign')
		)
		{
			$result = bx_sign($str);
		}
		else
		{
			include($_SERVER['DOCUMENT_ROOT'] . '/bitrix/license_key.php');

			if(!empty($LICENSE_KEY))
			{
				$result = md5($str.md5($LICENSE_KEY));
			}
		}

		return $result;
	}

	/**
	 * @return string
	 */
	protected function getLicenceCode(): string
	{
		$result = '';

		if(defined('BX24_HOST_NAME'))
		{
			$result = BX24_HOST_NAME;
		}
		else
		{
			$licenceCode = false;
			require_once($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/classes/general/update_client.php');

			if(method_exists('CUpdateClient','GetLicenseKey'))
			{
				$licenceCode = \CUpdateClient::GetLicenseKey();
			}

			if(!empty($licenceCode))
			{
				$result = md5('BITRIX' . \CUpdateClient::GetLicenseKey() . 'LICENCE');
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

		if($result->isSuccess())
		{
			if($this->connector === 'all')
			{
				if(in_array(mb_strtolower($name), self::DYNAMIC_FUNCTIONS, false))
				{
					$result->addError(new Error(
						Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_GENERAL_REQUEST_DYNAMIC_METHOD'),
						Library::ERROR_IMCONNECTOR_PROVIDER_GENERAL_REQUEST_DYNAMIC_METHOD,
						__METHOD__,
						$name
					));
				}
			}
			elseif(in_array(mb_strtolower($name), self::STATIC_FUNCTIONS, false))
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
	 * Magic method for handling dynamic methods.
	 *
	 * @param string $name The name of the called method.
	 * @param array $arguments The set of parameters passed to the method.
	 * @return Result
	 */
	public function call($name, $arguments): Result
	{
		$result = clone $this->result;

		if($result->isSuccess())
		{
			if(method_exists($this, $name))
			{
				$result = $this->validationMethodCall($name);
				if($result->isSuccess())
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
	 * Sending a message.
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function sendMessage(array $data): Result
	{
		$result = clone $this->result;

		if($result->isSuccess())
		{
			$data = $this->sendMessagesProcessing($data);

			$event = new Event(Library::MODULE_ID, Library::EVENT_SEND_MESSAGE_CUSTOM_CONNECTOR, ['CONNECTOR' => $this->connector, 'LINE' => $this->line, 'DATA' => $data]);
			$event->send();
		}

		return $result;
	}

	/**
	 * Update a message.
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function updateMessage(array $data): Result
	{
		$result = clone $this->result;

		if($result->isSuccess())
		{
			$data = $this->updateMessagesProcessing($data);

			$event = new Event(Library::MODULE_ID, Library::EVENT_UPDATE_MESSAGE_CUSTOM_CONNECTOR, ['CONNECTOR' => $this->connector, 'LINE' => $this->line,'DATA' => $data]);
			$event->send();
		}

		return $result;
	}

	/**
	 * Delete a message.
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 * @param array $data
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function deleteMessage(array $data): Result
	{
		$result = clone $this->result;

		if($result->isSuccess())
		{
			$data = $this->deleteMessagesProcessing($data);

			$event = new Event(Library::MODULE_ID, Library::EVENT_DELETE_MESSAGE_CUSTOM_CONNECTOR, ['CONNECTOR' => $this->connector, 'LINE' => $this->line,'DATA' => $data]);
			$event->send();
		}

		return $result;
	}

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function sessionStart(array $data): Result
	{
		return $this->result;
	}

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function sessionFinish(array $data): Result
	{
		return $this->result;
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

	/**
	 * @param $command
	 * @param array $data
	 * @return Result
	 */
	protected function query($command, array $data): Result
	{
		$result = clone $this->result;

		if($result->isSuccess())
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_NOT_CALL'), Library::ERROR_IMCONNECTOR_PROVIDER_NOT_CALL, __METHOD__, $this->connector));
		}

		return $result;
	}
}