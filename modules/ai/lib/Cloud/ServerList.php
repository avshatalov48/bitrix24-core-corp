<?php
declare(strict_types=1);

namespace Bitrix\AI\Cloud;

use Bitrix\AI\Config;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Json;

/**
 * Class ServerList
 * Helps to get the list of allowed servers for the AI service.
 */
final class ServerList
{
	private const SOCKET_TIMEOUT = 5;
	private const STREAM_TIMEOUT = 5;

	public function __construct()
	{
	}

	/**
	 * Get the list of allowed servers.
	 * @return array
	 * @throws ArgumentException
	 */
	public function getAllowedServers(): array
	{
		$primarySettings = Config::getValue('proxyServers');
		if (!empty($primarySettings))
		{
			return $primarySettings;
		}

		$configuration = new Configuration();
		$serverListUrl = $configuration->getServerListEndpoint();
		if (!$serverListUrl)
		{
			return [];
		}

		$http = new HttpClient([
			'socketTimeout' => self::SOCKET_TIMEOUT,
			'streamTimeout' => self::STREAM_TIMEOUT,
			'version' => HttpClient::HTTP_1_1,
		]);

		if ($http->get($serverListUrl) === false)
		{
			throw new \RuntimeException('Server is not available.');
		}
		if ($http->getStatus() !== 200)
		{
			throw new \RuntimeException('Server is not available. Status ' . $http->getStatus());
		}

		$response = Json::decode($http->getResult());
		if (!$response)
		{
			throw new \RuntimeException('Could not decode response.');
		}

		if (empty($response['servers']))
		{
			throw new \RuntimeException('Empty server list.');
		}

		$servers = [];
		foreach ($response['servers'] as $server)
		{
			$servers[] = [
				'proxy' => $server['proxy'],
				'region' => $server['region'] ?? null,
			];
		}

		return $servers;
	}

	/**
	 * Get the most suitable server based on the current region.
	 * @return string|null
	 * @throws ArgumentException
	 */
	public function getMostSuitableServer(): ?string
	{
		$region = Application::getInstance()->getLicense()->getRegion();
		$allowedServers = $this->getAllowedServers();

		if (empty($allowedServers))
		{
			return null;
		}

		foreach ($allowedServers as $server)
		{
			if (isset($server['region']) && $server['region'] === $region)
			{
				return $server['proxy'] ?? null;
			}
		}

		return $allowedServers[0]['proxy'] ?? null;
	}
}
