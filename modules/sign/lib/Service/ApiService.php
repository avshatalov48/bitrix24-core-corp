<?php

namespace Bitrix\Sign\Service;

use Bitrix\Main;
use Bitrix\Sign\Config;
use Bitrix\Sign\Debug;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ApiService
{
	private const LICENSE_TOKEN_HEADER = 'X-Sign-License-Token';
	private const CLIENT_ID_HEADER = 'X-Sign-Client-Id';
	private const CLIENT_TOKEN_HEADER = 'X-Sign-Client-Token';

	private string $apiEndpoint;
	private int $timeout;

	private array $headers = [];

	public function __construct(
		string $apiEndpoint,
		int $timeout = 30,
		private ?LoggerInterface $logger = null
	)
	{
		$this->apiEndpoint = $apiEndpoint;
		$this->timeout = $timeout;
		$this->logger ??= Debug\Logger::getInstance();
	}

	/**
	 * @param string $endpoint
	 * @param array<string|int, string|int> $requestParams
	 *
	 * @return Main\Result
	 */
	public function get(string $endpoint, ?array $requestParams = null): Main\Result
	{
		if ($requestParams !== null)
		{
			$endpoint .= '?' . http_build_query($requestParams);
		}

		return $this->requestWithClient($endpoint);
	}

	public function post(string $endpoint, array $data = []): Main\Result
	{
		return $this->requestWithClient($endpoint, 'POST', $data);
	}

	private function request(
		string $endpoint,
		string $method = 'GET',
		array $data = []
	): Main\Result
	{
		$url = $this->apiEndpoint . $endpoint;

		$http = (new Main\Web\HttpClient())
			->setTimeout($this->timeout)
			->setHeader('Content-Type', 'application/json')
		;
		foreach ($this->headers as $name => $value)
		{
			$http->setHeader($name, $value);
		}

		$data = !empty($data) ? Main\Web\Json::encode($data) : "";
		$http->query($method, $url, $data);

		$result = new Main\Result;
		$errorHandler = new ErrorHandler();

		if ($this->isClientConnectionError($http))
		{
			return $result->addError($this->getDefaultErrorWithCode('SIGN_CLIENT_CONNECTION_ERROR'));
		}

		$data = $http->getResult();

		try
		{
			$data = Main\Web\Json::decode($data);
		}
		catch (Main\ArgumentException $exception)
		{
			return $result->addError($this->getDefaultErrorWithCode('INCORRECT_JSON'));
		}

		$this->getLogger()->debug('api raw response: ', [Debug\LogFormatter::SIGN_PLACEHOLDER_DUMP => $data]);

		$data = is_array($data) ? $data : [];
		foreach ((array)($data['errors'] ?? []) as $error)
		{
			$result->addError($errorHandler->handleParsedError((array)$error));
		}
		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($http->getStatus() !== 200)
		{
			return $result->addError($this->getDefaultErrorWithCode('INCORRECT_HTTP_STATUS'));
		}

		$data = $data['data'] ?? null;
		if (!is_array($data))
		{
			return $result->addError($this->getDefaultErrorWithCode('INCORRECT_DATA'));
		}

		return $result->setData($data);
	}

	private function requestWithClient(string $endpoint, string $method = 'GET', array $data = []): Main\Result
	{
		if (!$this->isRegistered())
		{
			$result = $this->register();
			if (!$result->isSuccess())
			{
				return $result;
			}
			$clientData = $result->getData();
			if (empty($clientData['token']) || empty($clientData['id']))
			{
				return $result->addError($this->getDefaultErrorWithCode('INCORRECT_REGISTER_RESPONSE'));
			}

			Config\Storage::instance()
				->setClientToken($clientData['token'])
				->setClientId($clientData['id'])
			;
		}

		$this->setHeader(self::CLIENT_ID_HEADER, Config\Storage::instance()->getClientId());
		$this->setHeader(self::CLIENT_TOKEN_HEADER, Config\Storage::instance()->getClientToken());

		return $this->request($endpoint, $method, $data);
	}

	private function requestWithLicense(string $endpoint, string $method = 'GET', array $data = []): Main\Result
	{
		$this->setHeader(self::LICENSE_TOKEN_HEADER, Config\Storage::instance()->getLicenseToken());
		return $this->request($endpoint, $method, $data);
	}

	private function isRegistered(): bool
	{
		$clientId = Config\Storage::instance()->getClientId();
		$clientToken = Config\Storage::instance()->getClientToken();

		return !empty($clientId) && !empty($clientToken);
	}

	private function register(): Main\Result
	{
		$data = [];
		if (!\Bitrix\Main\Context::getCurrent()->getRequest()->isHttps())
		{
			$data['callbackUri'] = Config\Storage::instance()->makeCallbackUri();
		}

		return $this->requestWithLicense(
			'v1/client.register',
			'POST',
			$data
		);
	}

	private function setHeader(string $name, string $value): self
	{
		$this->headers[$name] = $value;
		return $this;
	}

	/**
	 * Get error with default localized error message with code
	 *
	 * @param string $code Error code
	 *
	 * @return Main\Error
	 */
	private function getDefaultErrorWithCode(string $code): Main\Error
	{
		$message = (new ErrorHandler())->getDefaultError($code);
		return new Main\Error($message, $code);
	}

	/**
	 * @return LoggerInterface
	 */
	public function getLogger(): LoggerInterface
	{
		return $this->logger ?: new NullLogger();
	}

	private function isClientConnectionError(Main\Web\HttpClient $httpClient): bool
	{
		if (in_array($httpClient->getStatus(), [0, 407]))
		{
			return true;
		}

		if (!$httpClient->getResult())
		{
			return true;
		}

		$response = $httpClient->getResponse();

		if ($response === null)
		{
			return true;
		}

		if (
			$response->hasHeader('Content-Length')
			&& (int)$response->getHeader('Content-Length') === 0
		)
		{
			return true;
		}

		return false;
	}
}
