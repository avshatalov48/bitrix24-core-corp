<?php

namespace Bitrix\Location\Common;

/**
 * Calculate distance between two points
 * Class DistanceCalculator
 * @package Bitrix\Location\Common;
 */
final class DistanceCalculator
{
	private const EARTH_RADIUS = 6378137;

	/**
	 * @param IPoint $point1
	 * @param IPoint $point2
	 * @return bool|float
	 */
	public static function calculate(IPoint $point1, IPoint $point2)
	{
		if(
			empty($point1->getLatitude())
			|| empty($point1->getLongitude())
			|| empty($point2->getLatitude())
			|| empty($point2->getLongitude())
		)
		{
			return false;
		}

		$lat1 = self::rad((float)$point1->getLatitude());
		$lat2 = self::rad((float)$point2->getLatitude());
		$lon1 = self::rad((float)$point1->getLongitude());
		$lon2 = self::rad((float)$point2->getLongitude());

		$dLat = $lat2 - $lat1;
		$dLon = $lon2 - $lon1;

		$a = sin($dLat / 2) **  2
			+ cos($lat1)*cos($lat2)
			* sin($dLon / 2) ** 2;

		$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

		return self::EARTH_RADIUS * $c;
	}

	private static function rad(float $x)
	{
		return $x * M_PI / 180;
	}
}