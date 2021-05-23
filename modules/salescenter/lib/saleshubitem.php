<?php

namespace Bitrix\Salescenter;

use Bitrix\Main;

/**
 * Class SaleshubItem
 * @package Bitrix\Salescenter
 */
final class SaleshubItem
{
	private const QUERY_DEFAULT_URL = 'https://util.bitrixsoft.com/b24/saleshub.php';
	private const QUERY_URL = [
		'ru' => 'https://util.1c-bitrix.ru/b24/saleshub.php',
		'ua' => 'https://util.bitrix.ua/b24/saleshub.php',
		'en' => 'https://util.bitrixsoft.com/b24/saleshub.php',
		'kz' => 'https://util.1c-bitrix.kz/b24/saleshub.php',
		'by' => 'https://util.1c-bitrix.by/b24/saleshub.php'
	];

	/**
	 * @return array
	 * @throws Main\ArgumentException
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
		$domain = null;
		$b24Manager = \Bitrix\SalesCenter\Integration\Bitrix24Manager::getInstance();
		if ($b24Manager->isEnabled())
		{
			$zone = $b24Manager->getPortalZone();
			if (isset(self::QUERY_URL[$zone]))
			{
				$domain = self::QUERY_URL[$zone];
			}
		}

		if (!$domain)
		{
			$domain = self::QUERY_DEFAULT_URL;
		}

		return $domain;
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
		catch (Main\ArgumentException $exception)
		{
			return false;
		}
	}
}