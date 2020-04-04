<?php

namespace Bitrix\Landing\Assets;

class Location
{
	const LOCATION_BEFORE_ALL = 0;    // before all (critical)
	const LOCATION_KERNEL = 50;        // first
	const LOCATION_TEMPLATE = 100;    // DEFAULT place
	const LOCATION_AFTER_TEMPLATE = 150;    // last
	
	/**
	 * Return default location
	 * @return string
	 */
	public static function getDefaultLocation()
	{
		return self::LOCATION_TEMPLATE;
	}
	
	/**
	 * Available locations for assets adding
	 *
	 * @return array
	 */
	public static function getAllLocations()
	{
		return [
			self::LOCATION_BEFORE_ALL,
			self::LOCATION_KERNEL,
			self::LOCATION_TEMPLATE,
			self::LOCATION_AFTER_TEMPLATE,
		];
	}
	
	/**
	 * Return locations, when must be loaded before all
	 * @return array
	 */
	public static function getCriticalLocations()
	{
		return [
			self::LOCATION_BEFORE_ALL,
		];
	}
	
	/**
	 * Check correctly location number
	 *
	 * @param $location
	 * @return string
	 */
	public static function verifyLocation($location)
	{
		if (
			!isset($location)
			|| !in_array($location, self::getAllLocations())
		)
		{
			return self::getDefaultLocation();
		}
		else
		{
			return $location;
		}
	}
}