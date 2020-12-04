<?php

namespace Bitrix\Ml;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use CUpdateClient;

/**
 * Class Client
 * @package Bitrix\Ml
 *
 * @method Result test(array $params)
 * @method Result createModel(array $params)
 * @method Result deleteModel(array $params)
 * @method Result appendLearningData(array $params)
 * @method Result startTraining(array $params)
 * @method Result predictRecord(array $params)
 * @method Result predictBatch(array $params)
 */
class Client
{
	const DEFAULT_SERVER = "https://ml.bitrix.info";

	const TYPE_BITRIX24 = "B24";
	const TYPE_CP = "CP";

	protected $serverAddress;

	public function __construct()
	{
		$this->serverAddress = defined("ML_SERVER") ? ML_SERVER : static::DEFAULT_SERVER;
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @return Result
	 * @throws ArgumentException
	 */
	public function __call($name, array $arguments)
	{
		$parameters = $arguments[0];
		if(!is_array($parameters))
		{
			throw new ArgumentException("Call parameters should be an array");
		}

		$action = "mlserver.api." . $name;

		return $this->performRequest($action, $parameters);
	}

	/**
	 * @param string $action
	 * @param array $parameters
	 * @return Result
	 * @throws ArgumentException
	 */
	protected function performRequest($action, array $parameters)
	{
		$result = new Result();

		$httpClient = new HttpClient([
			"socketTimeout" => 10,
			"streamTimeout" => 30,
			"disableSslVerification" => true
		]);

		$url = $this->serverAddress . "/api/?action=".$action;

		$request = [
			"action" => $action,
			"serializedParameters" => base64_encode(gzencode(Json::encode($parameters))),
		];

		//$request += $parameters;

		$request["BX_TYPE"] = static::getPortalType();
		$request["BX_LICENCE"] = static::getLicenseCode();
		$request["SERVER_NAME"] = static::getServerName();
		$request["BX_HASH"] = static::signRequest($request);

		$queryResult = $httpClient->query(HttpClient::HTTP_POST, $url, $request);

		if(!$queryResult)
		{
			$errors = $httpClient->getError();
			foreach ($errors as $code => $message)
			{
				$result->addError(new Error($message, $code));
			}
			return $result;
		}
		$returnCode = $httpClient->getStatus();
		if($returnCode != 200)
		{
			$result->addError(new Error("Server returned " . $returnCode . " code", "WRONG_SERVER_RESPONSE"));
			return $result;
		}

		$response = $httpClient->getResult();
		if($response == "")
		{
			$result->addError(new Error("Empty server response", "EMPTY_SERVER_RESPONSE"));
			return $result;
		}

		try
		{
			$parsedResponse = Json::decode($response);
		}
		catch (\Exception $e)
		{
			$result->addError(new Error("Could not parse server response. Raw response: " . $response));
			return $result;
		}

		if($parsedResponse["status"] === "error")
		{
			foreach ($parsedResponse["errors"] as $error)
			{
				$result->addError(new Error($error["message"], $error["code"], $error["customData"]));
			}
		}
		else if(is_array($parsedResponse["data"]))
		{
			$result->setData($parsedResponse["data"]);
		}

		return $result;
	}

	public static function getPortalType()
	{
		if(Loader::includeModule("bitrix24") && defined("BX24_HOST_NAME"))
		{
			return static::TYPE_BITRIX24;
		}
		else
		{
			return static::TYPE_CP;
		}
	}

	/**
	 * Return license code of the portal (to be used as a part of request verification scheme).
	 *
	 * @return string
	 */
	public static function getLicenseCode()
	{
		if(defined('BX24_HOST_NAME'))
		{
			return BX24_HOST_NAME;
		}
		else
		{
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client.php");
			return md5("BITRIX".\CUpdateClient::GetLicenseKey()."LICENCE");
		}
	}

	public static function getServerName()
	{
		if(defined('BX24_HOST_NAME'))
		{
			return "https://" . BX24_HOST_NAME;
		}
		else
		{
			return (\CMain::isHTTPS() ? "https" : "http")."://".((defined("SITE_SERVER_NAME") && SITE_SERVER_NAME <> '') ? SITE_SERVER_NAME : Option::get("main", "server_name"));
		}
	}

	/**
	 * Returns request authorization hash string.
	 *
	 * @param array $parameters Array or request parameters to be signed.
	 * @param string $suffix Suffix to append to signed string
	 * @return string
	 */
	public static function signRequest(array $parameters, $suffix = "")
	{
		$paramStr = md5(implode("|", $parameters) . ($suffix ? "|" . $suffix : ""));

		$portalType = static::getPortalType();
		if ($portalType == self::TYPE_BITRIX24 && function_exists('bx_sign'))
		{
			return bx_sign($paramStr);
		}
		else
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php");
			return md5($paramStr.md5($LICENSE_KEY));
		}

	}
}