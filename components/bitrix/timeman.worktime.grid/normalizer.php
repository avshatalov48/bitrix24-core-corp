<?php
namespace Bitrix\Timeman\Component\WorktimeGrid;

use \Bitrix\Main;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class Normalizer
{
	/**
	 * Return normalize object date
	 *
	 * @param string|Main\Type\Date|\DateTime $date
	 * @param string $format
	 *
	 * @return \DateTime
	 */
	public static function getNormalDate($datetime, $format = null)
	{
		if (!($datetime instanceof \DateTime))
		{
			if (!($datetime instanceof Main\Type\Date))
			{
				$datetime = new Main\Type\DateTime($datetime, $format);
			}
			$datetime = \DateTime::createFromFormat('d m Y H i s P', $datetime->format('d m Y H i s P'));
		}
		$resultDatetime = clone $datetime;
		return $resultDatetime;
	}
}