<?php

namespace Bitrix\StaffTrack\Integration\Location;

use Bitrix\Location\Entity\Address\Converter\StringConverter;
use Bitrix\Location\Service\FormatService;
use Bitrix\Location\Service\LocationService;
use Bitrix\Location\Service\StaticMapService;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Result;

class GeoService
{
	private float $latitude;
	private float $longitude;

	private const IMAGE_WIDTH = 420;
	private const IMAGE_HEIGHT = 169;
	private const IMAGE_ZOOM = 15;
	private const ADDRESS_ZOOM = 18;

	/**
	 * @param float $latitude
	 * @param float $longitude
	 */
	public function __construct(
		float $latitude,
		float $longitude,
	)
	{
		$this->latitude = $latitude;
		$this->longitude = $longitude;
	}

	/**
	 * @return Result
	 * @throws LoaderException
	 */
	public function generateStaticMap(): Result
	{
		$result = new Result();

		if (!$this->isAvailable())
		{
			return $result->addError(new Error('Module not found'));
		}

		$staticMapResult = StaticMapService::getInstance()->getStaticMap(
			$this->latitude,
			$this->longitude,
			self::IMAGE_ZOOM,
			self::IMAGE_WIDTH,
			self::IMAGE_HEIGHT
		);

		if (!$staticMapResult->isSuccess())
		{
			return $result->addErrors($staticMapResult->getErrors());
		}

		if (empty($staticMapResult->getPath()))
		{
			return $result->addError(new Error('Empty image path'));
		}

		$result->setData([
			'geoImageUrl' => $staticMapResult->getPath(),
		]);

		return $result;
	}

	/**
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException|LoaderException
	 */
	public function generateAddress(): Result
	{
		$result = new Result();

		if (!$this->isAvailable())
		{
			return $result->addError(new Error('Module not found'));
		}

		/** @var string $language */
		$language = Context::getCurrent()?->getLanguage();

		$location = LocationService::getInstance()->findByCoords(
			$this->latitude,
			$this->longitude,
			self::ADDRESS_ZOOM,
			$language,
		);

		if (!$location || !$location->getAddress())
		{
			return $result->addError(new Error('Location not found'));
		}

		$addressString = StringConverter::convertToString(
			$location->getAddress(),
			FormatService::getInstance()->findDefault($language),
			StringConverter::STRATEGY_TYPE_TEMPLATE_COMMA,
			StringConverter::CONTENT_TYPE_TEXT
		);

		$result->setData([
			'addressString' => $addressString,
		]);

		return $result;
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	private function isAvailable(): bool
	{
		return Loader::includeModule('location');
	}
}
