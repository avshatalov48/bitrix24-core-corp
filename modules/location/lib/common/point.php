<?php

namespace Bitrix\Location\Common;

use Bitrix\Main\Web\Json;

/**
 * Class Point
 * @package Bitrix\Location\Common
 */
class Point implements IPoint
{
	protected $latitude = '';
	protected $longitude = '';

	public function __construct(string $latitude, string $longitude)
	{
		$this->latitude = $latitude;
		$this->longitude = $longitude;
	}

	public function getLatitude(): string
	{
		return $this->latitude;
	}

	public function getLongitude(): string
	{
		return $this->longitude;
	}

	public function toJson(): string
	{
		return Json::encode([
			'latitude' => $this->latitude,
			'longitude' => $this->longitude
		]);
	}
}