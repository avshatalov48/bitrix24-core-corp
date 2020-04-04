<?php
namespace Bitrix\ImOpenLines\Tools;

use \Bitrix\Main\Type\DateTime;

/**
 * Class Time
 * @package Bitrix\ImOpenLines\Tools
 */
class Time
{
	private $time = 0;

	/**
	 * Time constructor.
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function __construct()
	{
		$this->time = self::getCurrentTime();
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\ObjectException
	 */
	public function getElapsedTime()
	{
		return self::getCurrentTime() - $this->time;
	}

	/**
	 * @return int
	 * @throws \Bitrix\Main\ObjectException
	 */
	public static function getCurrentTime()
	{
		return (new DateTime)->getTimestamp();
	}
}