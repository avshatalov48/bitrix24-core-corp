<?php

namespace Bitrix\Location\Infrastructure\Service\Config;

use Bitrix\Location\Common\CachedPool;
use Bitrix\Location\Common\Pool;
use Bitrix\Location\Infrastructure\Service\LoggerService;
use Bitrix\Location\Repository\AddressRepository;
use	Bitrix\Location\Exception\ErrorCodes;
use Bitrix\Location\Repository\Format\DataCollection;
use Bitrix\Location\Repository\FormatRepository;
use Bitrix\Location\Repository;
use Bitrix\Location\Repository\Location\Database;
use Bitrix\Location\Service\SourceService;
use Bitrix\Location\Source;
use Bitrix\Location\Repository\Location\Strategy\Delete;
use Bitrix\Location\Repository\Location\Strategy\Find;
use Bitrix\Location\Repository\Location\Strategy\Save;
use Bitrix\Location\Repository\LocationRepository;
use Bitrix\Location\Service\AddressService;
use Bitrix\Location\Infrastructure\Service\ErrorService;
use Bitrix\Location\Service\FormatService;
use Bitrix\Location\Service\LocationService;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\HttpClient;

class Factory implements IFactory
{
	/** @var IFactory */
	private static $delegate = null;

	/**
	 * @inheritDoc
	 */
	public static function createConfig(string $serviceType): Container
	{
		$result = null;

		if(self::$delegate !== null && self::$delegate instanceof IFactory)
		{
			if($result = self::$delegate::createConfig($serviceType))
			{
				return $result;
			}
		}

		return new Container(
			static::getServiceConfig($serviceType)
		);
	}

	/**
	 * @param IFactory $factory
	 */
	public static function setDelegate(IFactory $factory): void
	{
		self::$delegate = $factory;
	}

	protected static function getServiceConfig(string $serviceType)
	{
		switch ($serviceType)
		{
			case LoggerService::class:
				$result = [
					'logger' => new LoggerService\CEventLogger(),
					// todo: module option
					'logLevel'=> LoggerService\LogLevel::ERROR,
					'eventsToLog' => []
				];
				break;

			case ErrorService::class:
				$result = [
					'logErrors' => true,
					'throwExceptionOnError' => false
				];
				break;

			case FormatService::class:
				$result = [
					'repository' => new FormatRepository([
						'dataCollection' => DataCollection::class //Format data collection
					]),
					'defaultFormatCode' => \Bitrix\Location\Infrastructure\FormatCode::getCurrent()
				];
				break;

			case AddressService::class:
				$result = [
					'repository' => new AddressRepository()
				];
				break;

			case SourceService::class:
				$result = [
					'source' => self::obtainSource()
				];
				break;

			case LocationService::class:
				$result = [
					'repository' => static::createLocationRepository(
						self::obtainSource()
					)
				];
				break;

			default:
				throw new \LogicException("Unknown service type \"${serviceType}\"", ErrorCodes::SERVICE_CONFIG_FABRIC_WRONG_SERVICE);
		}

		return $result;
	}

	/**
	 * @param Source\BaseSource|null  $source
	 * @return LocationRepository
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private static function createLocationRepository(Source\BaseSource $source = null): LocationRepository
	{
		$cacheTTL = 2592000; //month
		$poolSize = 30;
		$pool = new Repository\Location\Cache\Pool($poolSize);

		$cache = new Repository\Location\Cache(
			$pool,
			$cacheTTL,
			'locationRepositoryCache',
			\Bitrix\Main\Data\Cache::createInstance(),
			\Bitrix\Main\EventManager::getInstance()
		);

		$repositories = [
			$cache,
			new Database()
		];

		if($source)
		{
			$repositories[] = $source->getRepository();
		}

		return new LocationRepository(
			new Find($repositories),
			new Save($repositories),
			new Delete($repositories)
		);
	}

	private static function obtainSource(): ?Source\BaseSource
	{
		if(Option::get('location', 'use_google_api', 'Y') === 'N')
		{
			return null;
		}

		static $result = null;

		if($result === null)
		{
			$httpClient = new HttpClient([
				"version" => "1.1",
				"socketTimeout" => 30,
				"streamTimeout" => 30,
				"redirect" => true,
				"redirectMax" => 5,
			]);

			if(defined('LOCATION_GOOGLE_PROXY_HOST'))
			{
				$proxyHost = LOCATION_GOOGLE_PROXY_HOST;
				$proxyPort = null;

				if(defined('LOCATION_GOOGLE_PROXY_PORT'))
				{
					$proxyPort = LOCATION_GOOGLE_PROXY_PORT;
				}

				$httpClient->setProxy($proxyHost, $proxyPort);
			}

			$cacheTTL = 2592000; //month
			$poolSize = 100;
			$pool = new Pool($poolSize);

			$cachePool = new CachedPool(
				$pool,
				$cacheTTL,
				'locationSourceGoogleRequester',
				\Bitrix\Main\Data\Cache::createInstance(),
				\Bitrix\Main\EventManager::getInstance()
			);

			$result = new Source\Google\GoogleSource($httpClient, $cachePool);
		}

		return $result;
	}
}