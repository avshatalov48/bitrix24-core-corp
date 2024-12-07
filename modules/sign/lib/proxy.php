<?php
namespace Bitrix\Sign;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;
use Bitrix\Sign;
use Bitrix\Sign\Debug\Logger;

Loc::loadMessages(__FILE__);

class Proxy
{
	private const SAFE_CLIENT_ID_OPTION = '~sign_safe_client_id';
	private const SAFE_CLIENT_TOKEN_OPTION = '~sign_safe_client_token_id';
	private const CLIENT_ID_HEADER = 'X-Sign-Client-Id';
	private const CLIENT_TOKEN_HEADER = 'X-Sign-Client-Token';
	/**
	 * Returns Proxy's backend URL.
	 * @return string
	 */
	private static function getBackendUrl(): string
	{
		static $backendUrl = null;

		if ($backendUrl === null)
		{
			$address = Sign\Config\Storage::instance()->getServiceAddress();
			$backendUrl = $address . '/bitrix/services/main/ajax.php';
		}

		return $backendUrl;
	}

	/**
	 * Returns Proxy's frontend URL.
	 * @return string
	 */
	public static function getFrontendUrl(): string
	{
		static $frontendUrl = null;

		if ($frontendUrl === null)
		{
			$frontendUrl = Sign\Config\Storage::instance()->getServiceSignLink();
		}

		return $frontendUrl;
	}

	/**
	 * Processes answer from proxy.
	 * @param HttpClient $http HttpClient instance.
	 * @return mixed
	 */
	private static function processAnswer(HttpClient $http)
	{
		try
		{
			$result = $http->getResult();

			if (!$result)
			{
				Logger::getInstance()->error('proxy no result');
				return null;
			}

			$result = Json::decode($result);

			if ($result['status'] === 'success')
			{
				return $result['data'];
			}

			if ($result['status'] === 'error')
			{
				Logger::getInstance()->dump($result['errors'], 'proxy result errors');
				foreach ($result['errors'] as $error)
				{
					Error::getInstance()->addError(
						$error['code'],
						$error['message'] ?? $error['code']
					);
				}
			}

			return null;
		}
		catch (\Exception $e)
		{
			Logger::getInstance()->trace('PROXY_SEND_ERROR: ' . $e->getMessage());
			Error::getInstance()->addError(
				'PROXY_SEND_ERROR',
				Loc::getMessage('SIGN_CORE_PROXY_SEND_ERROR')
			);

			return null;
		}
	}

	/**
	 * Returns public url for interaction proxy and portal.
	 * @return null|string
	 */
	private static function getHost(): ?string
	{
		return Sign\Config\Storage::instance()->getSelfHost();
	}

	/**
	 * Executes command to the proxy and returns result.
	 * @param string $commandName Command name.
	 * @param array $data Data to send.
	 * @param array $params Additional params.
	 * @return mixed
	 */
	public static function sendCommand(string $commandName, array $data, array $params = [])
	{
		$http = new HttpClient;
		// $http->setLogger(Logger::getInstance());

		if ($params['timeout'] ?? null)
		{
			$http->setTimeout($params['timeout']);
		}

		if (!self::prepareSafeClientIdentifier($http))
		{
			return null;
		}

		Logger::getInstance()->dump($data, 'proxy command: ' . $commandName);

		$http->post(self::getBackendUrl() . '?action=signproxy.api.safe.command', [
			'commandName' => $commandName,
			'data' => $data,
			'portal' => self::getHost()
		]);

		$result = self::processAnswer($http);
		Logger::getInstance()->dump($result, 'proxy command result');
		return $result;
	}

	private static function prepareSafeClientIdentifier(HttpClient $httpClient): bool
	{
		$http = new HttpClient;
		// $http->setLogger(Logger::getInstance());
		$clientId = Option::get('sign', self::SAFE_CLIENT_ID_OPTION, false);
		$clientToken = Option::get('sign', self::SAFE_CLIENT_TOKEN_OPTION, false);

		if (!$clientId || !$clientToken)
		{
			$domain = self::getHost();
			$callbackUri = '';

			if(!Sign\Main\Application::isHttps())
			{
				$callbackUri =
					"http://$domain"
					. "/bitrix/services/main/ajax.php"
					. "?action=sign.callback.handle"
				;
			}

			$http->post(self::getBackendUrl() . '?action=signproxy.api.safe.command', [
				'commandName' => 'client.register',
				'data' => [
					'domain' => self::getHost(),
					'region' => \Bitrix\Main\Application::getInstance()->getLicense()->getRegion(),
					'callbackUri' => $callbackUri,
					'licenseHash' => self::getLicenseHash(),
				],
			]);
			Error::getInstance()->clear();
			$result = self::processAnswer($http);
			if (Error::getInstance()->getErrors() || !$result['id'])
			{
				return false;
			}

			$clientId = $result['id'];
			$clientToken = $result['token'];
			Option::set('sign', self::SAFE_CLIENT_ID_OPTION, $clientId);
			Option::set('sign', self::SAFE_CLIENT_TOKEN_OPTION, $clientToken);
		}

		$httpClient->setHeader(self::CLIENT_ID_HEADER, $clientId);
		$httpClient->setHeader(self::CLIENT_TOKEN_HEADER, $clientToken);

		return true;
	}
	/**
	 * Sends file to the proxy and returns result.
	 * @param string $commandName Command name.
	 * @param File $file File instance to send.
	 * @param array $data Data to send.
	 * @param array $params Additional params.
	 * @return mixed
	 */
	public static function sendFile(string $commandName, File $file, array $data = [], array $params = [])
	{
		$http = new HttpClient;
		// $http->setLogger(Logger::getInstance());

		if (!$file->isExist())
		{
			Logger::getInstance()->trace('FILE_NOT_FOUND');
			Error::getInstance()->addError(
				'FILE_NOT_FOUND',
				Loc::getMessage('SIGN_CORE_PROXY_FILE_NOT_FOUND')
			);

			return null;
		}

		if ($params['timeout'] ?? null)
		{
			$http->setStreamTimeout($params['timeout']);
		}

		if (!self::prepareSafeClientIdentifier($http))
		{
			return null;
		}

		$data = [
			'data' => $data,
			'portal' => self::getHost()
		];
		$data['commandName'] = $commandName;
		$data['action'] = 'signproxy.api.safe.file';
		$url = self::getBackendUrl() . '?' . http_build_query($data);
		Logger::getInstance()->dump($url, 'proxy file');

		$http->post($url, [
			'file' => [
				'content' => $file->getContent(),
				'filename' => $file->getName(),
				'contentType' => $file->getType(),
			]
		], true);

		$result = self::processAnswer($http);
		Logger::getInstance()->dump($result, 'proxy file result');
		return $result;
	}

	/**
	 * Returns only command URL to the proxy, without any requests.
	 * @param string $commandName Command name.
	 * @param array $data Data to send.
	 * @return string
	 */
	public static function getCommandUrl(string $commandName, array $data = []): string
	{
		$data['commandName'] = $commandName;
		$data['action'] = 'signproxy.api.safe.command';

		return self::getBackendUrl() . '?' . http_build_query($data);
	}

	protected static function getLicenseHash()
	{
		return md5(LICENSE_KEY);
	}
}
