<?php
namespace Bitrix\Location\Infrastructure;

use Bitrix\Location\Entity\Location;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\Service\GeoIp;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Web\Json;

/**
 * Class UserPoint
 * @package Bitrix\Location\Infrastructure
 * @internal
 */
final class UserLocation
{
	/**
	 * Try to find user's point as precise as possible
	 * @return Location
	 */
	public static function findUserLocation(): Location
	{
		$location = self::findLocationByOption();

		if(!$location)
		{
			$location = self::findLocationByIp();
		}

		if(!$location)
		{
			$location = self::findLocationByPortalRegion();
		}

		return $location;
	}

	private static function findLocationByIp(string $ipAddress = ''): ?Location
	{
		$coordinates = GeoIp\Manager::getGeoPosition($ipAddress);
		if (
			!is_array($coordinates)
			|| !isset($coordinates['latitude'])
			|| !isset($coordinates['longitude'])
		)
		{
			return null;
		}

		return (new Location())
			->setLatitude($coordinates['latitude'])
			->setLongitude($coordinates['longitude'])
			->setType(Location\Type::LOCALITY);
	}

	private static function findLocationByPortalRegion(): Location
	{
		$region = self::getCurrentRegion();

		$map = [
			'ru' => [55.751244, 37.618423],
			'eu' => [50.85045, 4.34878],
			'de' => [52.520008, 13.404954],
			'fr' => [48.864716, 2.349014],
			'it' => [41.902782, 12.496366],
			'pl' => [52.237049, 21.017532],
			'ua' => [50.431759, 30.517023],
			'by' => [53.893009, 27.567444],
			'kz' => [43.238949, 76.889709],
			'in' => [28.644800, 77.216721],
			'tr' => [39.925533, 32.866287],
			'id' => [-6.200000, 106.816666],
			'cn' => [39.916668, 116.383331],
			'vn' => [21.028511, 105.804817],
			'jp' => [35.652832, 139.839478],
			'com' => [47.751076, -120.740135],
			'es' => [19.432608, -99.133209],
			'br' => [-15.793889, -47.882778],
		];

		$coordinates = $map[$region] ?? [51.509865, -0.118092];

		return (new Location())
			->setLatitude($coordinates[0])
			->setLongitude($coordinates[1])
			->setType(Location\Type::LOCALITY);
	}

	private static function getCurrentRegion(): string
	{
		$result = null;

		if (Loader::includeModule('bitrix24'))
		{
			$licensePrefix = \CBitrix24::getLicensePrefix();
			if ($licensePrefix !== false)
			{
				$result = (string)$licensePrefix;
			}
		}
		elseif (Loader::includeModule('intranet'))
		{
			$result = (string)\CIntranetUtils::getPortalZone();
		}
		elseif (defined('LANGUAGE_ID'))
		{
			$result = LANGUAGE_ID;
		}

		if (!$result)
		{
			$result = 'en';
		}

		return $result;
	}

	/**
	 * @return Location|null
	 */
	private static function findLocationByOption(): ?Location
	{
		$lastLocationOptionValue = \CUserOptions::GetOption('location', 'last_selected_location');
		if ($lastLocationOptionValue)
		{
			try
			{
				return Location::fromArray(
					Json::decode(
						Encoding::convertEncoding($lastLocationOptionValue, SITE_CHARSET, 'UTF-8')
					)
				);
			}
			catch (ArgumentException $exception) {}
		}

		return null;
	}
}
