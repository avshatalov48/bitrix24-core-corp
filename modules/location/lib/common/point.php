<?php

namespace Bitrix\Location\Common;

/**
 * Class Point
 * @package Bitrix\Location\Common
 */
class Point implements IPoint
{
	protected $latitude = "";
	protected $longitude = "";

	/**
	 * Point constructor.
	 * @param string $latitude
	 * @param string $longitude
	 */
	public function __construct(string $latitude, string $longitude)
	{
		$this->latitude = $latitude;
		$this->longitude = $longitude;
	}

	/**
	 * @return string
	 */
	public function getLatitude(): string
	{
		return $this->latitude;
	}

	/**
	 * @return string
	 */
	public function getLongitude(): string
	{
		return $this->longitude;
	}
}