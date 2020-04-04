<?php
namespace Bitrix\Crm\Statistics;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

abstract class StatisticEntryBase
{
	/** @var StatisticFieldBindingMap[]|null */
	protected static $bindingMaps = null;
	/**
	* Ensure binding map created.
	* @param string $typeName Source string.
	* @return StatisticFieldBindingMap
	*/
	protected static function ensureSlotBindingMapCreated($typeName)
	{
		if(self::$bindingMaps === null)
		{
			self::$bindingMaps = array();
		}

		if(!isset(self::$bindingMaps[$typeName]))
		{
			self::$bindingMaps[$typeName] = new StatisticFieldBindingMap($typeName);
		}
		return self::$bindingMaps[$typeName];
	}
	/**
	* Setup binding map.
	* @param string $typeName Source string.
	* @param StatisticFieldBindingMap $source Source binding map.
	* @return void
	*/
	protected static function setupSlotBindingMap($typeName, StatisticFieldBindingMap $source)
	{
		$map = new StatisticFieldBindingMap($typeName);
		$map->copyFrom($source);
		$map->save();

		if(self::$bindingMaps === null)
		{
			self::$bindingMaps = array();
		}

		self::$bindingMaps[$typeName] = $map;
	}
	/**
	* Calculate busy slot quantity.
	* @param string $typeName Source string.
	* @return integer
	*/
	protected static function calculateBusySlots($typeName)
	{
		$names = array_keys(self::ensureSlotBindingMapCreated($typeName)->getAll());
		$result = count($names);
		//Fixed slot is ignored
		if(in_array('SUM_TOTAL', $names))
		{
			$result--;
		}
		return $result;
	}
	/**
	 * Parse date from string.
	 * @param string $str Source string.
	 * @return string|null
	 */
	public static function parseDateString($str)
	{
		if($str === '')
		{
			return null;
		}

		try
		{
			$date = new Date($str, Date::convertFormatToPhp(FORMAT_DATE));
		}
		catch(Main\ObjectException $e)
		{
			try
			{
				$date = new DateTime($str, Date::convertFormatToPhp(FORMAT_DATETIME));
				$date->setTime(0, 0, 0);
			}
			catch(Main\ObjectException $e)
			{
				return null;
			}
		}
		return $date;
	}

	/**
	 * Compare two dates by Unix timestamp in a null safe way. A NULL is treated as zero timestamp.
	 * Return a value less than 0 if this first date is before second.
	 * Return 0 if they are equal.
	 * Return a value greater than 0 if this first date is after second.
	 * @param Date|null $first First date.
	 * @param Date|null $second Second date.
	 * @return int
	 */
	public static function nullSafeCompareDates(Date $first = null, Date $second = null)
	{
		$firstStamp = $first !== null ? $first->getTimestamp() : 0;
		$secondStamp = $second !== null ? $second->getTimestamp() : 0;
		return ($firstStamp - $secondStamp);
	}
}