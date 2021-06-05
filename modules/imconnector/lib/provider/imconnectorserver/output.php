<?php
namespace Bitrix\ImConnector\Provider\ImConnectorServer;

use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Converter;
use Bitrix\ImConnector\Provider\Base;

use Bitrix\Main\Web\Json;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Localization\Loc;

class Output extends Base\Output
{
	protected $controllerUrl = '';

	/** @var array The list of methods that close the connection without waiting for a response from the server.*/
	protected const LIST_COMMAND_NOT_WAIT_RESPONSE = [
		'sendmessage',
		'updatemessage',
		'deletemessage',
		'setstatusdelivered',
		'setstatusreading',
		'initializereceivemessages'
	];

	/**
	 * @return string
	 */
	protected function getControllerConnectorUrl(): string
	{
		$serverUri = Library::SERVER_URI;

		if (defined('CONTROLLER_CONNECTOR_URL'))
		{
			$serverUri = CONTROLLER_CONNECTOR_URL;
		}
		elseif ($uriServer = Option::get(Library::MODULE_ID, 'uri_server'))
		{
			$serverUri = $uriServer;
		}

		$serverUri = 'https://' . $serverUri . '/imwebhook/portal.php';

		return $serverUri;
	}

	/**
	 * Output constructor.
	 * @param string $connector ID connector.
	 * @param string|bool $line ID open line.
	 */
	public function __construct(string $connector, $line = false)
	{
		parent::__construct($connector, $line);

		$this->controllerUrl = $this->getControllerConnectorUrl();

		if(empty($this->controllerUrl))
		{
			$this->result->addError(new Error(Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_CONTROLLER_CONNECTOR_URL'), Library::ERROR_IMCONNECTOR_PROVIDER_CONTROLLER_CONNECTOR_URL, __METHOD__, $connector));
		}
		if(empty($this->licenceCode))
		{
			$this->result->addError(new Error(Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_LICENCE_CODE_PORTAL'), Library::ERROR_IMCONNECTOR_PROVIDER_LICENCE_CODE_PORTAL, __METHOD__, $connector));
		}
		if(empty($this->type))
		{
			$this->result->addError(new Error(Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_TYPE_PORTAL'), Library::ERROR_IMCONNECTOR_PROVIDER_TYPE_PORTAL, __METHOD__, $connector));
		}
		if(empty($this->connector))
		{
			$this->result->addError(new Error(Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_CONNECTOR'), Library::ERROR_IMCONNECTOR_PROVIDER_CONNECTOR, __METHOD__, $connector));
		}
	}

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function setStatusReading(array $data): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			if(in_array($this->connector, Library::ENABLE_SETSTATUSREADING))
			{
				$result = $this->query('setStatusReading', [$data]);
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
			foreach ($data as $id=>$message)
			{
				$data[$id]['message']['type'] = 'typing_start';
				unset($data[$id]['user']);
			}

			$result = $this->query('sendMessage', [$data]);
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

			$result = $this->query('sendMessage', [$data]);
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

			$result = $this->query('updateMessage', [$data]);
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

			$result = $this->query('deleteMessage', [$data]);
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
		$result = $this->result;

		if($result->isSuccess())
		{
			$result = $this->query('deleteLine', [$lineId]);
		}

		return $result;
	}

	/**
	 * @param $lineId
	 * @return Result
	 */
	protected function infoConnectorsLine($lineId): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			$result = $this->query('infoConnectorsLine', [$lineId]);
		}

		return $result;
	}

	/**
	 * Query execution on a remote server.
	 *
	 * @param $command
	 * @param array $data
	 * @return Result
	 */
	protected function query($command, array $data): Result
	{
		$result = $this->result;

		if($result->isSuccess())
		{
			if (empty($command))
			{
				$result->addError(new Error(Loc::getMessage('IMCONNECTOR_ERROR_INCORRECT_INCOMING_DATA'), Library::ERROR_IMCONNECTOR_PROVIDER_INCORRECT_INCOMING_DATA, __METHOD__, [$command, $data]));
			}
			else
			{
				$params['BX_COMMAND'] = $command;
				$params['BX_LICENCE'] = $this->licenceCode;
				$params['BX_DOMAIN'] = $this->domain;
				$params['BX_TYPE'] = $this->type;
				$params['BX_VERSION'] = ModuleManager::getVersion(Library::MODULE_ID);
				$params['CONNECTOR'] = $this->connector;
				$params['LINE'] = $this->line;
				$params['DATA'] = $data;

				$params = Converter::convertStubInEmpty($params);
				$params = Encoding::convertEncoding($params, SITE_CHARSET, 'UTF-8');

				$params['DATA'] = base64_encode(serialize($params['DATA']));
				$params['BX_HASH'] = self::requestSign($this->type, md5(implode('|', $params)));

				$waitResponse = true;
				if(in_array(mb_strtolower($params['BX_COMMAND']), self::LIST_COMMAND_NOT_WAIT_RESPONSE))
				{
					$waitResponse = false;
				}

				$httpClient = new HttpClient([
					'socketTimeout' => 20,
					'streamTimeout' => 60,
					'waitResponse' => $waitResponse,
					'disableSslVerification' => true //TODO: Enable if you have not signed the certificate
				]);

				$httpClient->setHeader('User-Agent', 'Bitrix Connector Client');
				$httpClient->setHeader('x-bitrix-licence', $this->licenceCode);

				$request = $httpClient->post($this->controllerUrl, $params);

				if($waitResponse && $result->isSuccess())
				{
					try
					{
						$request = Json::decode($request);
						$result = Converter::convertArrayObject($request);
					}
					catch (\Exception $e)
					{
						$result->addError(new Error($e->getMessage(), $e->getCode(), __METHOD__));
					}
				}
			}
		}

		return $result;
	}
}