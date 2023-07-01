<?php

namespace Bitrix\Crm\Order\Import;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Result;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

/**
 * Class for retrieving data from the store connector server.
 *
 * @final
 * @internal
 */
final class Provider
{
	const ERROR_EMPTY_SERVER_RESPONSE = 'EMPTY_SERVER_RESPONSE';

	const SERVER_URI = 'store-connect.bitrix.info';

	const TYPE_BITRIX24 = 'B24';
	const TYPE_CP = 'CP';

	private $connectorName;
	private $controllerUrl;
	private $licenceCode;
	private $domain;
	private $type;

	/**
	 * Provider constructor.
	 * @param $connectorName
	 */
	public function __construct($connectorName)
	{
		if (defined('BX24_HOST_NAME'))
		{
			$this->licenceCode = BX24_HOST_NAME;
		}
		elseif (method_exists(\Bitrix\Main\License::class, 'getPublicHashKey'))
		{

			$this->licenceCode = Application::getInstance()->getLicense()->getPublicHashKey();
		}
		else
		{
			require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/general/update_client.php');
			$this->licenceCode = md5('BITRIX'.\CUpdateClient::GetLicenseKey().'LICENCE');
		}

		$this->type = self::getPortalType();
		$this->domain = self::getDomainDefault();

		$this->connectorName = $connectorName;
		$this->controllerUrl = 'https://'.self::SERVER_URI.'/imwebhook/portal.php';
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
		return $this->query($name, $arguments);
	}

	/**
	 * Query execution on a remote server.
	 *
	 * @param $command
	 * @param $data
	 * @return mixed
	 */
	private function query($command, $data)
	{
		$command = (string)$command;

		if (!is_array($data))
		{
			$data = [];
		}

		$result = new Result();

		if ($command !== '')
		{
			$params['BX_COMMAND'] = $command;
			$params['BX_LICENCE'] = $this->licenceCode;
			$params['BX_DOMAIN'] = $this->domain;
			$params['BX_TYPE'] = $this->type;
			$params['BX_VERSION'] = ModuleManager::getVersion('crm');
			$params['CONNECTOR'] = $this->connectorName;
			$params['LINE'] = $this->connectorName;
			$params['DATA'] = $data;

			//$params = Converter::convertStubInEmpty($params);
			$params = Encoding::convertEncoding($params, SITE_CHARSET, 'UTF-8');

			$params['DATA'] = base64_encode(serialize($params['DATA']));
			$params['BX_HASH'] = self::requestSign($this->type, md5(implode('|', $params)));

			$httpClient = new HttpClient([
				'socketTimeout' => 20,
				'streamTimeout' => 60,
				'waitResponse' => true,
				'disableSslVerification' => true,
			]);

			$httpClient->setHeader('User-Agent', 'Bitrix Store Connector Client');
			$httpClient->setHeader('x-bitrix-licence', $this->licenceCode);

			$request = $httpClient->post($this->controllerUrl, $params);

			try
			{
				$request = Json::decode($request);
				$result = self::convertArrayObject($request);
			}
			catch (\Exception $e)
			{
				$result->addError(new Error($e->getMessage(), $e->getCode(), __METHOD__));
			}
		}

		return $result;
	}

	/**
	 * Returns the type of the portal.
	 *
	 * @return string
	 */
	private static function getDomainDefault()
	{
		if (defined('BX24_HOST_NAME'))
		{
			$uri = (Context::getCurrent()->getRequest()->isHttps() ? 'https://' : 'http://').BX24_HOST_NAME;
		}
		else
		{
			$uri = self::getCurrentServerUrl();
		}

		return $uri;
	}

	/**
	 * Gets the current server address with protocol and port.
	 *
	 * @return string
	 */
	private static function getCurrentServerUrl()
	{
		$server = Context::getCurrent()->getServer();
		$request = Context::getCurrent()->getRequest();

		$url = $request->isHttps() ? 'https://' : 'http://';
		$url .= $server->getServerName();
		$url .= (
			(int)$server->getServerPort() === 80
			|| ($server->get('HTTPS') && (int)$server->getServerPort() === 443)
		)
			? ''
			: ':'.$server->getServerPort();

		return $url;
	}

	/**
	 * Returns the type of the portal.
	 *
	 * @return string
	 */
	public static function getPortalType()
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
	 * Returns the query hash of the license key.
	 *
	 * @param string $type The type of portal.
	 * @param string $str
	 * @return string
	 */
	public static function requestSign($type, $str)
	{
		if ($type === self::TYPE_BITRIX24 && function_exists('bx_sign'))
		{
			return bx_sign($str);
		}

		/** @var string $LICENSE_KEY */
		include($_SERVER['DOCUMENT_ROOT'].'/bitrix/license_key.php');

		return md5($str.md5($LICENSE_KEY));
	}

	/**
	 * Converts an array into Bitrix\Main\Result object.
	 *
	 * @param array $array
	 * @return Result
	 */
	private static function convertArrayObject(array $array)
	{
		$result = new Result();

		if (!empty($array['DATA']) && is_array($array['DATA']))
		{
			$result->setData($array['DATA']);
		}

		if (empty($array['OK']))
		{
			if (is_array($array['ERROR']))
			{
				foreach ($array['ERROR'] as $error)
				{
					$result->addError(new Error(
						$error['MESSAGE'],
						$error['CODE'],
						[$error['METHOD'], $error['PARAMS']]
					));
				}
			}
			else
			{
				$result->addError(new Error(
					'Empty server response',
					self::ERROR_EMPTY_SERVER_RESPONSE,
					[__METHOD__, $array['ERROR']]
				));
			}
		}

		return $result;
	}
}