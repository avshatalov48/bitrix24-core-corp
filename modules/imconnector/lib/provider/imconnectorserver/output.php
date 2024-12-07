<?php
namespace Bitrix\ImConnector\Provider\ImConnectorServer;

use Bitrix\ImConnector\Connector;
use Bitrix\ImConnector\DeliveryMark;
use Bitrix\ImConnector\Error;
use Bitrix\ImConnector\Result;
use Bitrix\ImConnector\Library;
use Bitrix\ImConnector\Converter;
use Bitrix\ImConnector\Provider\Base;

use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\Json;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Localization\Loc;

class Output extends Base\Output
{
	/** @var string */
	protected $controllerUrl = '';

	/** @var string */
	protected $domain = '';

	/** @var string */
	protected $type = '';

	/** @var string */
	protected $licenceCode = '';

	/** @var string */
	protected $region = '';

	/** @var array The list of methods that close the connection without waiting for a response from the server.*/
	protected const LIST_COMMAND_NOT_WAIT_RESPONSE = [
		'sendmessage',
		'updatemessage',
		'deletemessage',
		'setstatusdelivered',
		'setstatusreading',
		'initializereceivemessages'
	];

	private const SERVICE_MAP = [
		'ru' => 'https://im-ru.bitrix.info',
		'eu' => 'https://im.bitrix.info',
	];

	public const
		ERROR_NETWORK = 'NETWORK_ERROR',
		ERROR_ANSWER = 'ANSWER_MALFORMED';

	/**
	 * Output constructor.
	 *
	 * @param string $connectorId Mnemonic connector name.
	 * @param string|bool $line Open line ID.
	 */
	public function __construct(string $connectorId, $line = false)
	{
		parent::__construct($connectorId, $line);

		$this->region = Connector::getPortalRegion();
		$this->controllerUrl = $this->getControllerConnectorUrl($this->region);
		$this->licenceCode = $this->getLicenceCode();
		$this->type = $this->getPortalType();
		$this->domain = Connector::getDomainDefault();

		if (empty($this->controllerUrl))
		{
			$this->result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_CONTROLLER_CONNECTOR_URL'),
				Library::ERROR_IMCONNECTOR_PROVIDER_CONTROLLER_CONNECTOR_URL,
				__METHOD__,
				$connectorId
			));
		}
		if (empty($this->licenceCode))
		{
			$this->result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_LICENCE_CODE_PORTAL'),
				Library::ERROR_IMCONNECTOR_PROVIDER_LICENCE_CODE_PORTAL,
				__METHOD__,
				$connectorId
			));
		}
		if (empty($this->type))
		{
			$this->result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_TYPE_PORTAL'),
				Library::ERROR_IMCONNECTOR_PROVIDER_TYPE_PORTAL,
				__METHOD__,
				$connectorId
			));
		}
		if (empty($this->connector))
		{
			$this->result->addError(new Error(
				Loc::getMessage('IMCONNECTOR_ERROR_PROVIDER_CONNECTOR'),
				Library::ERROR_IMCONNECTOR_PROVIDER_CONNECTOR,
				__METHOD__,
				$connectorId
			));
		}
	}

	/**
	 * Returns controller service endpoint url.
	 *
	 * @return string
	 */
	protected function getControllerConnectorUrl(string $region): string
	{
		if (defined('CONTROLLER_CONNECTOR_URL'))
		{
			$serviceEndpoint = \CONTROLLER_CONNECTOR_URL;
		}
		elseif ($uriServer = Option::get(Library::MODULE_ID, 'uri_server', ''))
		{
			$serviceEndpoint = $uriServer;
		}
		else
		{
			if (in_array($region, ['ru', 'by', 'kz'], true))
			{
				$serviceEndpoint = self::SERVICE_MAP['ru'];
			}
			else
			{
				$serviceEndpoint = self::SERVICE_MAP['eu'];
			}
		}

		if (!(mb_strpos($serviceEndpoint, 'https://') === 0 || mb_strpos($serviceEndpoint, 'http://') === 0))
		{
			$serviceEndpoint = 'https://' . $serviceEndpoint;
		}

		$serviceEndpoint .= '/imwebhook/portal.php';

		return $serviceEndpoint;
	}

	//region Commands

	/**
	 * @param array $data
	 * @return Result
	 */
	protected function setStatusReading(array $data): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			if (in_array($this->connector, Library::ENABLE_SETSTATUSREADING))
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
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			foreach ($data as $id => $message)
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
		$result = clone $this->result;

		if ($result->isSuccess())
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
		$result = clone $this->result;

		if ($result->isSuccess())
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
		$result = clone $this->result;

		if ($result->isSuccess())
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
		$result = clone $this->result;

		if ($result->isSuccess())
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
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			$result = $this->query('infoConnectorsLine', [$lineId]);
		}

		return $result;
	}

	//endregion

	/**
	 * Query execution on a remote server.
	 *
	 * @param $command
	 * @param array $data
	 * @return Result
	 */
	protected function query($command, array $data): Result
	{
		$result = clone $this->result;

		if ($result->isSuccess())
		{
			if (empty($command))
			{
				$result->addError(new Error(
					Loc::getMessage('IMCONNECTOR_ERROR_INCORRECT_INCOMING_DATA'),
					Library::ERROR_IMCONNECTOR_PROVIDER_INCORRECT_INCOMING_DATA,
					__METHOD__,
					[$command, $data]
				));
			}
			else
			{
				$params = [];
				$params['BX_COMMAND'] = $command;
				$params['BX_LICENCE'] = $this->licenceCode;
				$params['BX_DOMAIN'] = $this->domain;
				$params['BX_TYPE'] = $this->type;
				$params['BX_REGION'] = $this->region;
				$params['BX_VERSION'] = ModuleManager::getVersion(Library::MODULE_ID);
				$params['CONNECTOR'] = $this->connector;
				$params['LINE'] = $this->line;
				$params['DATA'] = $data;

				$params = Converter::convertStubInEmpty($params);

				$params['DATA'] = \base64_encode(\serialize($params['DATA']));
				$params['BX_HASH'] = self::requestSign($this->type, \md5(implode('|', $params)));

				$waitResponse = true;
				if (in_array(mb_strtolower($params['BX_COMMAND']), self::LIST_COMMAND_NOT_WAIT_RESPONSE))
				{
					$waitResponse = false;
				}

				$httpClient = $this->instanceHttpClient($waitResponse);
				$httpClient
					->setHeader('Connection', 'close')
					->setHeader('Expect', ''); // to disable "100 Continue" behavior

				$url = $this->controllerUrl
					. '?connector='. $this->connector
					. '&command='. $command
				;

				$response = $httpClient->post($url, $params);

				// Header 'x-bitrix-error' workaround.
				$errorCode = $httpClient->getHeaders()->get('x-bitrix-error');

				// Network errors workaround.
				if ($response === false)
				{
					// check for network errors
					$errors = $httpClient->getError();
					if (!empty($errors))
					{
						$result->addError(new Error(
							'Network connection error',
							self::ERROR_NETWORK,
							__METHOD__,
							$errors
						));

						$errors[] = 'url:'.$this->controllerUrl;
						$errors[] = 'connector:'.$this->connector;
						$systemException = new \Bitrix\Main\SystemException('Network connection error: '.implode('; ', $errors));
						\Bitrix\Main\Application::getInstance()->getExceptionHandler()->writeToLog($systemException);
					}
				}
				elseif ($waitResponse)
				{
					// try to parse result
					if (is_string($response))
					{
						try
						{
							$response = Json::decode($response);
							$result = Converter::convertArrayObject($response);
						}
						catch (ArgumentException $exception)
						{
							$result->addError(new Error(
								'Server answer is malformed',
								self::ERROR_ANSWER,
								__METHOD__,
								[$exception->getCode(), $exception->getMessage()]
							));
						}
					}
				}
				// don't wait for response body
				else
				{
					$response = ($response !== false);

					if ($errorCode)
					{
						$result->addError(new Error(
							'Something went wrong',
							$errorCode,
							__METHOD__
						));
					}
				}
			}
		}

		return $result;
	}

	protected function sessionFinish(array $data): Result
	{
		$result = clone $this->result;

		foreach ($data as $value)
		{
			if ($value['connector']['connector_id'] === Library::ID_TELEGRAMBOT_CONNECTOR)
			{
				$messageData = [
					'lineId' => $value['connector']['line_id'],
					'chatId' => $value['connector']['chat_id'],
					'userId' => $value['connector']['user_id'],
				];
				$result = $this->query('finishSession', [$messageData]);
				break;
			}
		}

		return $result;
	}

	protected function sessionStart(array $data): Result
	{
		$result = clone $this->result;

		foreach ($data as $value)
		{
			if ($value['connector']['connector_id'] === Library::ID_TELEGRAMBOT_CONNECTOR)
			{
				$messageData = [
					'lineId' => $value['connector']['line_id'],
					'chatId' => $value['connector']['chat_id'],
					'userId' => $value['connector']['user_id'],
				];
				$result = $this->query('startSession', [$messageData]);
				break;
			}
		}

		return $result;
	}

	/**
	 * @return HttpClient
	 */
	public function instanceHttpClient(bool $waitResponse = false): HttpClient
	{
		$httpClient = new HttpClient();
		$httpClient
			->waitResponse($waitResponse)
			->setTimeout(20)
			->setStreamTimeout(60)
			->disableSslVerification() //TODO: Enable if you have not signed the certificate
			->setHeader('User-Agent', 'Bitrix Connector Client '.$this->getPortalType())
			->setHeader('x-bitrix-licence', $this->licenceCode)
			->setHeader('Referer', $this->domain)
		;

		return $httpClient;
	}

	/**
	 * Returns the type of the portal.
	 *
	 * @return string
	 */
	protected function getPortalType(): string
	{
		if (defined('BX24_HOST_NAME'))
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
		if (
			$type == self::TYPE_BITRIX24 &&
			function_exists('bx_sign')
		)
		{
			$result = \bx_sign($str);
		}
		else
		{
			$result = \md5($str . Application::getInstance()->getLicense()->getHashLicenseKey());
		}

		return $result;
	}

	/**
	 * @return string
	 */
	protected function getLicenceCode(): string
	{
		if (defined('BX24_HOST_NAME'))
		{
			$result = \BX24_HOST_NAME;
		}
		else
		{
			$result = Application::getInstance()->getLicense()->getPublicHashKey();
		}

		return $result;
	}
}
