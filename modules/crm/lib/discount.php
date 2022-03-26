<?php
namespace Bitrix\Crm;
class Discount
{
	public const UNDEFINED = 0;
	public const MONETARY = 1;
	public const PERCENTAGE = 2;

	public const MONETARY_NAME = 'MONETARY';
	public const PERCENTAGE_NAME = 'PERCENTAGE';

	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = intval($typeID);
		return $typeID >= self::MONETARY && $typeID <= self::PERCENTAGE;
	}

	public static function resolveName($typeID)
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = intval($typeID);
		if($typeID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::MONETARY:
				return self::MONETARY_NAME;
			case self::PERCENTAGE:
				return self::PERCENTAGE_NAME;
			case self::UNDEFINED:
			default:
				return '';
		}
	}

	public static function calculateDiscountRate($originalPrice, $price)
	{
		$originalPrice = round(doubleval($originalPrice), 2);
		$price = round(doubleval($price), 2);

		if($originalPrice === 0.0)
		{
			return 0.0;
		}

		if($price === 0.0)
		{
			return $originalPrice > 0 ? 100.0 : -100.0;
		}

		return round(((100 * ($originalPrice - $price)) / $originalPrice), 2);
	}

	public static function calculateDiscountSum($price, $discountRate)
	{
		return (self::calculateOriginalPrice($price, $discountRate) - doubleval($price));
	}

	/**
	 * @deprecated Please use calculateDiscountSum instead
	 *
	 * @param $discountPrice
	 * @param $discountRate
	 *
	 * @return float|int
	 */
	public static function calculateDiscountByDiscountPrice($discountPrice, $discountRate)
	{
		return self::calculateDiscountSum($discountPrice, $discountRate);
	}

	public static function calculateOriginalPrice($price, $discountRate)
	{
		$price = doubleval($price);
		$discountRate = doubleval($discountRate);
		if ($discountRate === 100.0)
		{
			return 0.0;
		}

		return (100 * $price) / (100 - $discountRate);
	}

	public static function calculatePrice($originalPrice, $discountRate)
	{
		$originalPrice = doubleval($originalPrice);
		$discountRate = doubleval($discountRate);

		return $originalPrice - (($originalPrice * $discountRate) / 100);
	}
}