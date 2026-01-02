<?php

namespace Bitrix\BIConnector\ExternalSource;

use Bitrix\Main;

final class TypeConverter
{
	/**
	 * Converts value to int
	 *
	 * @param mixed $value
	 * @return int|null
	 */
	public static function convertToInt(mixed $value): ?int
	{
		$parsedValue = (int)$value;

		if ($parsedValue === PHP_INT_MAX)
		{
			return null;
		}

		return $parsedValue;
	}

	/**
	 * Converts value to double
	 *
	 * @example '123 785,4463' => 123785.44, 123785.4463 => 123785.4
	 *
	 * @param mixed $value
	 * @param int|null $precision
	 * @param string $delimiter
	 * @return float
	 */
	public static function convertToDouble(mixed $value, int $precision = null, string $delimiter = '.'): float
	{
		if (is_string($value))
		{
			$value = self::prepareNumberForDouble($value, $delimiter);
		}

		$parsedValue = (double)$value;

		if ($precision)
		{
			$parsedValue = round($parsedValue, $precision);
		}

		return $parsedValue;
	}

	/**
	 * Formats a double string using a given delimiter
	 *
	 * @param string $value
	 * @param int|null $precision
	 * @param string $delimiter
	 * @return string
	 */
	public static function formatDoubleString(?string $value, string $delimiter = '.'): string
	{
		if (is_null($value))
		{
			return '';
		}

		if ($delimiter === '.')
		{
			return $value;
		}

		return str_replace('.', $delimiter, $value);
	}

	/**
	 * Converts value to string
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function convertToString(mixed $value): string
	{
		$parsedValue = (string)$value;

		return $parsedValue;
	}

	/**
	 * Converts value to Date with time
	 *
	 * @example '08/18/1960 15:45:20 (m/d/Y H:i:s)
	 *
	 * @param string|null $value
	 * @param string $format
	 * @return Main\Type\DateTime|bool
	 */
	public static function convertToDateTime(?string $value, string $format): Main\Type\DateTime|bool
	{
		$date = \DateTime::createFromFormat($format, $value);
		if ($date)
		{
			$errors = \DateTime::getLastErrors();

			if ($errors['error_count'] > 0 || $errors['warning_count'] > 0)
			{
				return false;
			}

			return Main\Type\DateTime::createFromPhp($date);
		}

		return $date;
	}

	/**
	 * Formats a DateTime object into a string with a given format
	 *
	 * @param Main\Type\DateTime|null $dateTime
	 * @param string $format
	 * @return string
	 */
	public static function convertDateTimeToString(?Main\Type\DateTime $dateTime, string $format): string
	{
		if (!$dateTime)
		{
			return '';
		}

		return $dateTime->format($format);
	}

	/**
	 * Converts value to Date
	 *
	 * @example '08/18/1960 (m/d/Y)
	 *
	 * @param string|null $value
	 * @param string $format
	 * @return Main\Type\Date|bool
	 */
	public static function convertToDate(?string $value, string $format): Main\Type\Date|bool
	{
		$date = \DateTime::createFromFormat($format, $value);
		if ($date)
		{
			$errors = \DateTime::getLastErrors();

			if ($errors['error_count'] > 0 || $errors['warning_count'] > 0)
			{
				return false;
			}

			$date->setTime(0, 0, 0);
			return Main\Type\DateTime::createFromPhp($date);
		}

		return $date;
	}

	/**
	 * Formats a Date object into a string with a given format
	 *
	 * @param Main\Type\DateTime|null $dateTime
	 * @param string $format
	 * @return string
	 */
	public static function convertDateToString(?Main\Type\Date $date, string $format): string
	{
		if (!$date)
		{
			return '';
		}

		return $date->format($format);
	}

	/**
	 * Converts value to money (double)
	 *
	 * @example $8 816.77 => 8816.77
	 *
	 * @param mixed $value
	 * @param int|null $precision
	 * @param string $delimiter
	 * @return float
	 */
	public static function convertToMoney(mixed $value, int $precision = null, string $delimiter = '.'): float
	{
		if (is_string($value))
		{
			$value = self::prepareNumberForMoney($value, $delimiter);
		}

		return self::convertToDouble($value, $precision, $delimiter);
	}

	private static function prepareNumberForMoney(string $value, string $delimiter = '.'): float
	{
		$decodedValue = html_entity_decode($value);

		$number = preg_replace('/[^-\d' . preg_quote($delimiter, '/') . ']/', '', $decodedValue);
		if ($delimiter !== '.')
		{
			$number = str_replace($delimiter, '.', $number);
		}

		return (double)$number;
	}

	private static function prepareNumberForDouble(string $value, string $delimiter = '.'): string
	{
		if ($delimiter !== '.')
		{
			return str_replace($delimiter, '.', $value);
		}

		return $value;
	}
}
