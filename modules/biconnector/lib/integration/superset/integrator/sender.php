<?php

namespace Bitrix\BIConnector\Integration\Superset\Integrator;

use Bitrix\BIConnector\Superset\Config\ConfigContainer;
use Bitrix\Main\Error;
use Bitrix\Main\IO\File;
use Bitrix\Main\IO\FileOpenException;
use Bitrix\Main\Result;
use Bitrix\Main\Service\MicroService;
use Bitrix\Main\Service\MicroService\Client;
use Bitrix\Main\Web\Http\MultipartStream;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

class Sender extends MicroService\BaseSender
{
	private const API_VERSION = 2;

	protected function getServiceUrl(): string
	{
		return ServiceLocation::getCurrentServiceUrl();
	}

	protected function getProxyPath(): string
	{
		return "/api/v1";
	}

	/**
	 * @inheritDoc
	 */
	public function performRequest($action, array $parameters = [], Dto\User $user = null): Result
	{
		$url = $this->getServiceUrl() . $this->getProxyPath() . $action;

		/*
		 * `trim($val, '=')` is used to bypass the proactive filter of the proxy server.
		 * 	The '=' sign is used for padding Base64 strings. Proactive filter might detect a string
		 * 	in the format `/[^a-z]on.+=/` and adding spaces to it to protect against potential XSS attacks,
		 * 	which causes signature mismatches.
		 */
		$serializedParameters = trim(base64_encode(gzencode(Json::encode($parameters))), '=');
		$request = [
			'action' => $action,
			'serializedParameters' => $serializedParameters,
		];

		$request['BX_TYPE'] = Client::getPortalType();
		$request['BX_LICENCE'] = Client::getLicenseCode();
		$request['SERVER_NAME'] = self::getServerName();
		if ($user && $user->clientId)
		{
			$request['BX_CLIENT_ID'] = $user->clientId;
		}
		$request['BX_VERSION'] = self::API_VERSION;

		$portalId = ConfigContainer::getConfigContainer()->getPortalId();
		if (!empty($portalId))
		{
			$request['BX_PORTAL_ID'] = $portalId;
		}
		$request['BX_HASH'] = Client::signRequest($request);

		$httpClient = $this->buildHttpClient();
		$result = $httpClient->query(HttpClient::HTTP_POST, $url, $request);

		return $this->buildResult($httpClient, $result);
	}

	public function performMultipartRequest($action, array $parameters = [], Dto\User $user = null): Result
	{
		$url = $this->getServiceUrl() . $this->getProxyPath() . $action;
		$filePath = $parameters['filePath'] ?? '';
		if (!$filePath)
		{
			$result = new Result();
			$result->addError(new Error('File path is empty.'));

			return $result;
		}
		$file = new File($filePath);
		try
		{
			$fileData = $file->open('r');
		}
		catch (FileOpenException $e)
		{
			$result = new Result();
			$result->addError(new Error('Error performing multipart request: ' . $e->getMessage() . '. File path: ' . $filePath));

			return $result;
		}

		unset($parameters['filePath']);

		$data = $parameters + [
				'BX_TYPE' => Client::getPortalType(),
				'BX_LICENCE' => Client::getLicenseCode(),
				'SERVER_NAME' => self::getServerName(),
			];

		if ($user && $user->clientId)
		{
			$data['BX_CLIENT_ID'] = $user->clientId;
		}

		$portalId = ConfigContainer::getConfigContainer()->getPortalId();
		if (!empty($portalId))
		{
			$data['BX_PORTAL_ID'] = $portalId;
		}

		$data['BX_VERSION'] = self::API_VERSION;
		$data['BX_HASH'] = Client::signRequest($data);
		$data[] = [
			'resource' => $fileData,
			'filename' => $file->getName(),
		];
		$body = new MultipartStream($data);

		$httpClient = $this->buildHttpClient();
		$httpClient->setHeader('Content-Type', 'multipart/form-data; boundary=' . $body->getBoundary());

		$result = $httpClient->query(HttpClient::HTTP_POST, $url, $body);
		$file->close();

		return $this->buildResult($httpClient, $result);
	}

	protected function createAnswerForJsonResponse($queryResult, $response, $errors, $status): Result
	{
		$result = new Result();

		if(!$queryResult)
		{
			foreach ($errors as $code => $message)
			{
				$result->addError(new Error($message, $code));
			}

			return $result;
		}


		$resultBody = [
			'status' => $status,
			'data' => null,
		];

		if ($response !==  "")
		{
			$parseResult = self::parseJsonResponse($response);
			$result->addErrors($parseResult->getErrors());

			$data = $parseResult->getData();
			$resultBody['data'] = $data;
		}

		$result->setData($resultBody);
		return $result;
	}

	protected static function parseJsonResponse(string $jsonResponse): Result
	{
		$result = new Result();
		try
		{
			$parsedResponse = Json::decode($jsonResponse);
		}
		catch (\Exception $e)
		{
			$result->addError(new Error("Could not parse server response. Raw response: " . $jsonResponse));
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

	private static function getServerName(): string
	{
		if (defined('BX24_HOST_NAME'))
		{
			return "https://" . BX24_HOST_NAME;
		}

		if (\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps())
		{
			$scheme = "https://";
		}
		else
		{
			$scheme = "http://";
		}

		return $scheme . \Bitrix\Main\Config\Option::get("main", "server_name");
	}
}
