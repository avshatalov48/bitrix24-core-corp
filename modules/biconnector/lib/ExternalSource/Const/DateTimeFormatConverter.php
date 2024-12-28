<?php

namespace Bitrix\BIConnector\ExternalSource\Const;

final class DateTimeFormatConverter
{
	private const iso8601ToPhpMap = [
		// Date components
		'YYYY' => 'Y', // Full year
		'YY' => 'y', // 2-digit year
		'MM' => 'm', // Month with leading zero
		'M' => 'n', // Month without leading zero
		'DD' => 'd', // Day of the month with leading zero
		'D' => 'j', // Day of the month without leading zero

		// Time components
		'hh' => 'H', // Hour (24-hour format, leading zero)
		'h' => 'G', // Hour (24-hour format, no leading zero)
		'mm' => 'i', // Minutes with leading zero
		'sss' => 'v', // Milliseconds
		'ss' => 's', // Seconds with leading zero
	];

	/**
	 * Convert ISO 8601 date format string to PHP date format string.
	 *
	 * @param string $isoFormat ISO 8601 date format string
	 * @return string PHP date format string
	 */
	public static function iso8601ToPhp(string $isoFormat): string
	{
		$phpFormat = $isoFormat;
		foreach (self::iso8601ToPhpMap as $iso => $php)
		{
			$phpFormat = str_replace($iso, $php, $phpFormat);
		}

		return $phpFormat;
	}

	/**
	 * Convert PHP date format string to ISO 8601 date format string.
	 *
	 * @param string $phpFormat PHP date format string
	 * @return string ISO 8601 date format string
	 */
	public static function phpToIso8601(string $phpFormat): string
	{
		$isoFormat = $phpFormat;
		foreach (array_flip(self::iso8601ToPhpMap) as $php => $iso)
		{
			$isoFormat = str_replace($php, $iso, $isoFormat);
		}

		return $isoFormat;
	}
}