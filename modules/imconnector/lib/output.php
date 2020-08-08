<?php
namespace Bitrix\ImConnector;

use \Bitrix\Main\Event,
	\Bitrix\Main\Loader,
	\Bitrix\Main\Web\Json,
	\Bitrix\Main\Data\Cache,
	\Bitrix\Main\EventResult,
	\Bitrix\Main\Config\Option,
	\Bitrix\Main\ModuleManager,
	\Bitrix\Main\Text\Encoding,
	\Bitrix\Main\Web\HttpClient,
	\Bitrix\Main\Localization\Loc;
use \Bitrix\ImOpenLines\Network,
	\Bitrix\ImOpenLines\LiveChatManager;
use	\Bitrix\ImConnector,
	\Bitrix\ImConnector\Connectors\Viber,
	\Bitrix\ImConnector\Connectors\Yandex,
	\Bitrix\ImConnector\Connectors\FbInstagram,
	\Bitrix\ImConnector\Connectors\BotFramework,
	\Bitrix\ImConnector\Connectors\FacebookComments;

Loc::loadMessages(__FILE__);
Library::loadMessages();

/**
 * Class for sending messages for the server of connectors.
 * @package Bitrix\ImConnector
 * @final
 * @internal
 */
final class Output
{
	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'CP';

	const ERROR_IMCONNECTOR_NO_ACTIVE_CONNECTOR = "IMCONNECTOR_NO_ACTIVE_CONNECTOR";
	const ERROR_IMCONNECTOR_GENERAL_REQUEST_NOT_DYNAMIC_METHOD = "IMCONNECTOR_GENERAL_REQUEST_NOT_DYNAMIC_METHOD";
	const ERROR_IMCONNECTOR_INCORRECT_INCOMING_DATA = "IMCONNECTOR_INCORRECT_INCOMING_DATA";

	const CACHE_DIR = "/imconnector/output/";
	const CACHE_TIME = "86400";

	/** @var array The list of methods that close the connection without waiting for a response from the server.*/
	private $listCommandNotWaitResponse = array(
		'sendmessage',
		'updatemessage',
		'deletemessage',
		'setstatusdelivered',
		'setstatusreading',
		'initializereceivemessages'
	);

	private $controllerUrl = '';
	private $licenceCode = '';
	private $domain = '';
	private $type = '';

	private $connector;
	private $line;
	private $result;

	/**
	 * Returns the type of the portal.
	 *
	 * @return string
	 */
	public static function getPortalType()
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
	 * @param string $type The type of portal.
	 * @param string $str String.
	 * @return string
	 */
	public static function requestSign($type, $str)
	{
		if ($type == self::TYPE_BITRIX24 && function_exists('bx_sign'))
		{
			return bx_sign($str);
		}
		else
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php");
			return md5($str.md5($LICENSE_KEY));
		}
	}

	/**
	 * Output constructor.
	 * @param string $connector ID connector.
	 * @param string|bool $line ID open line.
	 * @param bool $ignoreDeactivatedConnector
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	function __construct($connector, $line = false, $ignoreDeactivatedConnector = false)
	{
		$this->result = new Result();

		if(!empty($ignoreDeactivatedConnector) || Connector::isConnector($connector) || $connector == 'all')
		{
			if (defined('CONTROLLER_CONNECTOR_URL'))
			{
				$serverUri = CONTROLLER_CONNECTOR_URL;
			}
			elseif ($uriServer = Option::get(Library::MODULE_ID, "uri_server"))
			{
				$serverUri = $uriServer;
			}
			else
			{
				$serverUri = Library::SERVER_URI;
			}

			if(defined('BX24_HOST_NAME'))
			{
				$this->licenceCode = BX24_HOST_NAME;
			}
			else
			{
				require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");
				$this->licenceCode = md5("BITRIX".\CUpdateClient::GetLicenseKey()."LICENCE");
			}

			$this->type = self::getPortalType();
			$this->domain = Connector::getDomainDefault();

			$this->connector = $connector;
			$this->line = $line;
			$this->controllerUrl = "https://" . $serverUri . "/imwebhook/portal.php";
		}
		else
		{
			$this->result->addError(new Error(Loc::getMessage('IMCONNECTOR_NO_ACTIVE_CONNECTOR'), self::ERROR_IMCONNECTOR_NO_ACTIVE_CONNECTOR, __METHOD__, $connector));
		}
	}

	/**
	 * Query execution on a remote server.
	 *
	 * @param $command
	 * @param Result $result
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function query($command, $result)
	{
		if($result->getResult())
			$data = $result->getResult();
		else
			$data = array();

		if (strlen($command) <= 0 || !is_array($data))
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_INCORRECT_INCOMING_DATA'), self::ERROR_IMCONNECTOR_INCORRECT_INCOMING_DATA, __METHOD__, array($command, $data)));
		}
		else
		{
			$params['BX_COMMAND'] = $command;
			$params['BX_LICENCE'] = $this->licenceCode;
			$params['BX_DOMAIN'] = $this->domain;
			$params['BX_TYPE'] = $this->type;
			$params['BX_VERSION'] = ModuleManager::getVersion(Library::MODULE_ID);
			$params["CONNECTOR"] = $this->connector;
			$params["LINE"] = $this->line;
			$params["DATA"] = $data;

			$params = Converter::convertStubInEmpty($params);
			$params = Encoding::convertEncoding($params, SITE_CHARSET, 'UTF-8');

			$params["DATA"] = base64_encode(serialize($params["DATA"]));
			$params["BX_HASH"] = self::requestSign($this->type, md5(implode("|", $params)));

			if(in_array(strtolower($params['BX_COMMAND']), $this->listCommandNotWaitResponse))
				$waitResponse = false;
			else
				$waitResponse = true;

			$httpClient = new HttpClient(array(
				"socketTimeout" => 20,
				"streamTimeout" => 60,
				"waitResponse" => $waitResponse,
				"disableSslVerification" => true //TODO: Enable if you have not signed the certificate
			));

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
	public function sendMessage(array $data): Result
	{
		$result = new Result();

		if($this->connector == 'all')
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_GENERAL_REQUEST_NOT_DYNAMIC_METHOD'), self::ERROR_IMCONNECTOR_GENERAL_REQUEST_NOT_DYNAMIC_METHOD, __METHOD__, $this->connector));
		}
		elseif($this->result->isSuccess())
		{
			if(in_array($this->connector, Connector::getListConnectorNoServer()))
			{
				$event = new Event(Library::MODULE_ID, Library::EVENT_SEND_MESSAGE_CUSTOM_CONNECTOR, array('CONNECTOR' => $this->connector, 'LINE' => $this->line,'DATA' => $data));
				$event->send();
			}
			else
			{
				foreach ($data as $cell=>$value)
				{
					//Processing for native messages
					$value = InteractiveMessage\Output::sendMessageProcessing($value, $this->connector);

					//Processing for native messages
					$value = Connector::sendMessageProcessing($value);

					//Hack is designed for the Microsoft Bot Framework
					$value = BotFramework::sendMessageProcessing($value, $this->connector);
					//Hack is designed for the Viber
					$value = Viber::sendMessageProcessing($value, $this->connector, $this->line);
					//Hack is designed for the Instagram Facebook
					$value = FbInstagram::sendMessageProcessing($value, $this->connector);
					//Hack is designed for the Yandex
					$value = Yandex::sendMessageProcessing($value, $this->connector, $this->line);
					//Hack is designed for the FacebookComments
					$value = FacebookComments::sendMessageProcessing($value, $this->connector);

					$data[$cell] = $value;
				}

				$result->setResult(array($data));

				$result = $this->query('sendMessage', $result);
			}
		}
		else
		{
			$result->addErrors($this->result->getErrors());
		}

		return $result;
	}

	/**
	 * Update a message.
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function updateMessage(array $data): Result
	{
		$result = new Result();

		if($this->connector == 'all')
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_GENERAL_REQUEST_NOT_DYNAMIC_METHOD'), self::ERROR_IMCONNECTOR_GENERAL_REQUEST_NOT_DYNAMIC_METHOD, __METHOD__, $this->connector));
		}
		elseif($this->result->isSuccess())
		{
			if(in_array($this->connector, Connector::getListConnectorNoServer()))
			{
				$event = new Event(Library::MODULE_ID, Library::EVENT_UPDATE_MESSAGE_CUSTOM_CONNECTOR, array('CONNECTOR' => $this->connector, 'LINE' => $this->line,'DATA' => $data));
				$event->send();
			}
			else
			{
				foreach ($data as $cell=>$value)
				{
					//Hack is designed for the Microsoft Bot Framework
					$value = BotFramework::sendMessageProcessing($value, $this->connector);

					$data[$cell] = $value;
				}

				$result->setResult(array($data));

				$result = $this->query('updateMessage', $result);
			}
		}
		else
		{
			$result->addErrors($this->result->getErrors());
		}

		return $result;
	}

	/**
	 * Delete a message.
	 *
	 * @param array $data An array of data describing the message.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function deleteMessage(array $data): Result
	{
		$result = new Result();

		if($this->connector == 'all')
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_GENERAL_REQUEST_NOT_DYNAMIC_METHOD'), self::ERROR_IMCONNECTOR_GENERAL_REQUEST_NOT_DYNAMIC_METHOD, __METHOD__, $this->connector));
		}
		elseif($this->result->isSuccess())
		{
			if(in_array($this->connector, Connector::getListConnectorNoServer()))
			{
				$event = new Event(Library::MODULE_ID, Library::EVENT_DELETE_MESSAGE_CUSTOM_CONNECTOR, array('CONNECTOR' => $this->connector, 'LINE' => $this->line,'DATA' => $data));
				$event->send();
			}
			else
			{
				foreach ($data as $cell=>$value)
				{
					//Hack is designed for the Microsoft Bot Framework
					$value = BotFramework::sendMessageProcessing($value, $this->connector);

					$data[$cell] = $value;
				}

				$result->setResult(array($data));

				$result = $this->query('deleteMessage', $result);
			}
		}
		else
		{
			$result->addErrors($this->result->getErrors());
		}

		return $result;
	}

	/**
	 * Magic method for handling dynamic methods.
	 *
	 * @param string $name The name of the called method.
	 * @param array $arguments The set of parameters passed to the method.
	 * @return Result
	 */
	public function __call($name, $arguments)
	{
		$result = new Result();

		if($this->connector == 'all')
		{
			$result->addError(new Error(Loc::getMessage('IMCONNECTOR_GENERAL_REQUEST_NOT_DYNAMIC_METHOD'), self::ERROR_IMCONNECTOR_GENERAL_REQUEST_NOT_DYNAMIC_METHOD, __METHOD__, $this->connector));
		}
		elseif($this->result->isSuccess())
		{
			//TODO: Make an exception to the status of reading left only In the Contact
			if($name != 'setStatusReading' || $this->connector == 'vkgroup')
			{
				$result->setResult($arguments);

				$result = $this->query($name, $result);
			}
		}
		else
		{
			$result->addErrors($this->result->getErrors());
		}

		return $result;
	}

	/**
	 * The removal of the open line of this website from the remote server connectors.
	 *
	 * @param string $lineId ID of the deleted lines.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function deleteLine($lineId)
	{
		$result = new Result();

		Status::deleteAll($lineId);

		$connector = new self('all');

		$result->setResult(array($lineId));
		$result = $connector->query('deleteLine', $result);

		$event = new Event(Library::MODULE_ID, Library::EVENT_DELETE_LINE, array('LINE_ID' => $lineId));
		$event->send();

		return $result;
	}

	/**
	 * Receive information about all the connected connectors.
	 *
	 * @param string $lineId ID Line.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function infoConnectorsLine($lineId)
	{
		$result = new Result();

		$resultLiveChat = array();
		$resultNetwork = array();
		$resultCustom = array();
		$resultRest = array();

		$connector = new self('all');

		$result->setResult(array($lineId));
		$result = $connector->query('infoConnectorsLine', $result);

		if(Loader::includeModule(Library::MODULE_ID_OPEN_LINES))
		{
			//live chat
			$managerLiveChat = new LiveChatManager($lineId);
			$infoLiveChat = $managerLiveChat->getPublicLink();

			if(!empty($infoLiveChat["ID"]))
			{
				$resultLiveChat['id'] = $infoLiveChat["ID"];

				if(!Library::isEmpty($infoLiveChat["NAME"]))
					$resultLiveChat['name'] = $infoLiveChat["NAME"];

				if(!empty($infoLiveChat["PICTURE"]) && is_array($infoLiveChat["PICTURE"]))
					$resultLiveChat['picture'] = $infoLiveChat["PICTURE"];

				if(!empty($infoLiveChat["URL"]))
					$resultLiveChat['url'] = $infoLiveChat["URL"];

				if(!empty($infoLiveChat["URL_IM"]))
					$resultLiveChat['url_im'] = $infoLiveChat["URL_IM"];
			}

			//network
			$statusNetwork = Status::getInstance(Library::ID_NETWORK_CONNECTOR, $lineId);

			if($statusNetwork->isStatus())
			{
				$dataNetwork = $statusNetwork->getData();

				if(!empty($dataNetwork["CODE"]))
				{
					$linkNetwork = Network::getPublicLink($dataNetwork["CODE"]);

					if(!empty($linkNetwork))
					{
						$resultNetwork['id'] = $dataNetwork["CODE"];
						$resultNetwork['url'] = $linkNetwork;
						$resultNetwork['url_im'] = $linkNetwork;

						if(!Library::isEmpty($dataNetwork["NAME"]))
							$resultNetwork['name'] = $dataNetwork["NAME"];

						if(!empty($dataNetwork["AVATAR"]))
							$resultNetwork['picture']["url"] = \CFile::GetPath($dataNetwork["AVATAR"]);
					}
				}
			}

			$event = new Event(Library::MODULE_ID, Library::EVENT_INFO_LINE_CUSTOM_CONNECTOR, array('LINE_ID' => $lineId));
			$event->send();

			foreach ($event->getResults() as $eventResult)
			{
				if ($eventResult != EventResult::ERROR && $params = $eventResult->getParameters())
				{
					if(!empty($params['connector_id']))
					{
						if(!empty($params['id']))
							$resultCustom[$params['connector_id']]['id'] = $params['id'];
						if(!empty($params['url']))
							$resultCustom[$params['connector_id']]['url'] = $params['url'];
						if(!empty($params['url_im']))
							$resultCustom[$params['connector_id']]['url_im'] = $params['url_im'];
						if(!empty($params['name']))
							$resultCustom[$params['connector_id']]['name'] = $params['name'];
						if(!empty($params['picture']["url"]))
							$resultCustom[$params['connector_id']]['picture']['url'] = $params['picture']['url'];
					}
				}
			}

			//rest
			$restConnectors = ImConnector\Rest\Helper::listRestConnector();

			foreach ($restConnectors as $restConnector)
			{
				$restConnectorStatus = Status::getInstance($restConnector['ID'], $lineId);

				if ($restConnectorStatus->isStatus())
				{
					$restConnectorData = $restConnectorStatus->getData();

					if (!empty($restConnectorData) && is_array($restConnectorData))
					{
						if(!empty($restConnectorData['id']))
							$resultRest[$restConnector['ID']]['id'] = $restConnectorData['id'];
						if(!empty($restConnectorData['url']))
							$resultRest[$restConnector['ID']]['url'] = $restConnectorData['url'];
						if(!empty($restConnectorData['url_im']))
							$resultRest[$restConnector['ID']]['url_im'] = $restConnectorData['url_im'];
						if(!empty($restConnectorData['name']))
							$resultRest[$restConnector['ID']]['name'] = $restConnectorData['name'];
					}
				}
			}

			if(!empty($resultLiveChat) || !empty($resultNetwork) || !empty($resultCustom) || !empty($resultRest))
			{
				$infoConnectors = $result->getData();

				if(!empty($resultLiveChat))
					$infoConnectors[Library::ID_LIVE_CHAT_CONNECTOR] = $resultLiveChat;
				if(!empty($resultNetwork))
					$infoConnectors[Library::ID_NETWORK_CONNECTOR] = $resultNetwork;

				if(!empty($resultCustom))
				{
					foreach($resultCustom as $connectorId => $customInfoConnector)
					{
						if(!isset($infoConnectors[$connectorId]))
							$infoConnectors[$connectorId] = $customInfoConnector;
					}
				}

				if(!empty($resultRest))
				{
					foreach($resultRest as $connectorId => $restInfoConnector)
					{
						if(!isset($infoConnectors[$connectorId]))
							$infoConnectors[$connectorId] = $restInfoConnector;
					}
				}

				$result->setData($infoConnectors);
			}
		}

		return $result;
	}

	/**
	 * Returns a list of the existing connectors.
	 *
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function listConnector()
	{
		$result = new Result();

		$cache = Cache::createInstance();
		$cacheId = 'listConnector';

		if ($cache->initCache(self::CACHE_TIME, $cacheId, self::CACHE_DIR))
		{
			$vars = $cache->getVars();
			if(is_array($vars))
				$result->setData($vars);
			else
				$result->setResult($vars);
		}
		elseif ($cache->startDataCache())
		{
			$connector = new self('all');

			$result = $connector->query('listConnector', $result);

			if ($result->isSuccess())
			{
				$cache->endDataCache($result->getData());
			}
			else
			{
				$cache->abortDataCache();
			}
		}

		return $result;
	}

	/**
	 * Checks whether this connector.
	 *
	 * @param string $id ID connector.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function isConnector($id)
	{
		$result = new Result();

		$connectors = self::listConnector()->getData();

		if(empty($connectors[$id]))
		{
			foreach ($connectors as $value)
			{
				$value = Connector::getConnectorRealId($value);

				$realConnectors[$value] = $value;
			}

			if(empty($realConnectors[$id]))
				$result->addError(new Error(Loc::getMessage('IMCONNECTOR_NOT_AVAILABLE_CONNECTOR'), Library::ERROR_NOT_AVAILABLE_CONNECTOR, __METHOD__, array($id)));
		}


		return $result;
	}

	/**
	 * Static magic method.
	 * Caching is used for a number of methods.
	 *
	 * @param string $name The name of the called method.
	 * @param array $arguments The set of parameters passed to the method.
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public static function __callStatic($name, $arguments)
	{
		$result = new Result();

		$connector = new self('all');

		$result->setResult($arguments);
		$result = $connector->query($name, $result);

		return $result;
	}
}