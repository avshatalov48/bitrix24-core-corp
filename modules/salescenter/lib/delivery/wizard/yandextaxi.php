<?php

namespace Bitrix\SalesCenter\Delivery\Wizard;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Sale\Handlers\Delivery\YandexTaxi\Api\Api;
use Sale\Handlers\Delivery\YandexTaxi\Api\RequestEntity\TariffsOptions;
use Sale\Handlers\Delivery\YandexTaxi\Common\RegionCoordinatesMapper;
use Sale\Handlers\Delivery\YandexTaxi\Common\RegionFinder;

Loc::loadMessages(__FILE__);

/**
 * Class YandexTaxi
 * @package Bitrix\SalesCenter\DeliveryServiceInstallator
 */
class YandexTaxi extends Base
{
	/** @var Api */
	private $api;

	/** @var RegionFinder */
	private $regionFinder;

	/** @var RegionCoordinatesMapper */
	private $regionCoordinatesMapper;

	/**
	 * YandexTaxi constructor.
	 * @param Api $api
	 * @param RegionFinder $regionFinder
	 * @param RegionCoordinatesMapper $regionCoordinatesMapper
	 */
	public function __construct(Api $api, RegionFinder $regionFinder, RegionCoordinatesMapper $regionCoordinatesMapper)
	{
		$this->api = $api;
		$this->regionFinder = $regionFinder;
		$this->regionCoordinatesMapper = $regionCoordinatesMapper;
	}

	/**
	 * @inheritdoc
	 */
	protected function buildFieldsFromSettings(array $settings): Result
	{
		$buildResult = parent::buildFieldsFromSettings($settings);
		if (!$buildResult->isSuccess())
		{
			return $buildResult;
		}

		$fields = $buildResult->getData()['FIELDS'];

		return $buildResult->setData(
			[
				'FIELDS' => array_merge(
					$fields,
					[
						'CODE' => $this->handler->getCode(),
						'CONFIG' => [
							'MAIN' => [
								'OAUTH_TOKEN' => $settings['OAUTH_TOKEN']
							]
						]
					]
				)
			]
		);
	}

	/**
	 * @inheritdoc
	 */
	protected function validateSettings(array $settings): Result
	{
		$validationResult = parent::validateSettings($settings);
		if (!$validationResult->isSuccess())
		{
			return $validationResult;
		}

		if (!isset($settings['OAUTH_TOKEN']) || empty($settings['OAUTH_TOKEN']))
		{
			return $validationResult->addError(
				new Error(
					Loc::getMessage('SALESCENTER_CONTROLLER_DELIVERY_INSTALLATION_YANDEX_ERROR_MISSING_TOKEN')
				)
			);
		}
		else
		{
			$this->api->getTransport()->getOauthTokenProvider()->setToken($settings['OAUTH_TOKEN']);

			$currentRegion = $this->regionFinder->getCurrentRegion();
			if (!$currentRegion)
			{
				return $validationResult->addError(new Error('Unexpected region'));
			}

			$tariffsResult = $this->api->getTariffs(
				(new TariffsOptions)->setStartPoint(
					$this->regionCoordinatesMapper->getRegionCoordinates($currentRegion)
				)
			);

			if (!$tariffsResult->isSuccess())
			{
				return $validationResult->addError(
					new Error(
						Loc::getMessage('SALESCENTER_CONTROLLER_DELIVERY_INSTALLATION_YANDEX_ERROR_INVALID_TOKEN')
					)
				);
			}

			if (!in_array('express', $tariffsResult->getTariffs()))
			{
				return $validationResult->addError(
					new Error(
						Loc::getMessage('SALESCENTER_CONTROLLER_DELIVERY_INSTALLATION_YANDEX_ERROR_TARIFF_NOT_SUPPORTED')
					)
				);
			}
		}

		return $validationResult;
	}

	/**
	 * @return RegionFinder
	 */
	public function getYandexTaxiRegionFinder(): RegionFinder
	{
		return $this->regionFinder;
	}
}
