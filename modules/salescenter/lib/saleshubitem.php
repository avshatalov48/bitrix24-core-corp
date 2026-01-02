<?php

namespace Bitrix\Salescenter;

use Bitrix\Main;
use Bitrix\Main\Application;

/**
 * Class SaleshubItem
 * @package Bitrix\Salescenter
 */
final class SaleshubItem
{
	private const QUERY_PATH = '/b24/saleshub.php';

	/**
	 * Returns paysystem list from server.
	 *
	 * @return array
	 */
	public static function getPaysystemItems(): array
	{
		$result = [];

		$httpClient = new Main\Web\HttpClient();
		$response = $httpClient->get(self::getDomain() . '?source=paysystem');
		if ($response === false)
		{
			return $result;
		}

		if ($httpClient->getStatus() === 200)
		{
			$response = self::decode($response);
		}

		if (is_array($response) && count($response) > 0)
		{
			foreach ($response as $item)
			{
				$result[] = $item;
			}
		}

		return $result;
	}

	/**
	 * Returns sms providers from server.
	 *
	 * @return array
	 */
	public static function getSmsProviderItems(): array
	{
		$result = [];

		$httpClient = new Main\Web\HttpClient();
		$response = $httpClient->get(self::getDomain() . '?source=smsprovider');
		if ($response === false)
		{
			return $result;
		}

		if ($httpClient->getStatus() === 200)
		{
			$result = self::decode($response);
		}

		if (is_array($response) && count($response) > 0)
		{
			foreach ($response as $item)
			{
				$result[] = $item;
			}
		}

		return $result;
	}

	/**
	 * @return string
	 */
	private static function getDomain(): string
	{
		$license = Application::getInstance()->getLicense();

		return $license->getDomainStoreLicense() . self::QUERY_PATH;
	}

	/**
	 * @param array $data
	 * @return mixed
	 * @throws Main\ArgumentException
	 */
	private static function encode(array $data)
	{
		return Main\Web\Json::encode($data, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * @param string $data
	 * @return mixed
	 */
	private static function decode($data)
	{
		try
		{
			return Main\Web\Json::decode($data);
		}
		catch (Main\ArgumentException)
		{
			return false;
		}
	}
}
