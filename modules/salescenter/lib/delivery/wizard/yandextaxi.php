<?php

namespace Bitrix\SalesCenter\Delivery\Wizard;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Sale\Handlers\Delivery\YandexTaxi\Api\Api;
use Sale\Handlers\Delivery\YandexTaxi\Common\RegionCoordinatesMapper;
use Sale\Handlers\Delivery\YandexTaxi\Common\RegionFinder;
use Sale\Handlers\Delivery\YandexTaxi\TariffsChecker;

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

	/** @var TariffsChecker */
	private $tariffsChecker;

	/**
	 * YandexTaxi constructor.
	 * @param Api $api
	 * @param RegionFinder $regionFinder
	 * @param RegionCoordinatesMapper $regionCoordinatesMapper
	 * @param TariffsChecker $tariffsChecker
	 */
	public function __construct(Api $api, RegionFinder $regionFinder, RegionCoordinatesMapper $regionCoordinatesMapper, TariffsChecker $tariffsChecker)
	{
		$this->api = $api;
		$this->regionFinder = $regionFinder;
		$this->regionCoordinatesMapper = $regionCoordinatesMapper;
		$this->tariffsChecker = $tariffsChecker;
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

			$availableTariffs = $this->tariffsChecker->getAvailableTariffs(
				$this->regionCoordinatesMapper->getRegionCoordinates($currentRegion)
			);

			if (is_null($availableTariffs))
			{
				return $validationResult->addError(
					new Error(
						Loc::getMessage('SALESCENTER_CONTROLLER_DELIVERY_INSTALLATION_YANDEX_ERROR_INVALID_TOKEN')
					)
				);
			}

			if (empty($availableTariffs))
			{
				/** @var \Bitrix\Sale\Delivery\Services\Base $handlerClass */
				$handlerClass = $this->handler->getHandlerClass();

				return $validationResult->addError(
					new Error(
						Loc::getMessage(
							'SALESCENTER_CONTROLLER_DELIVERY_INSTALLATION_YANDEX_ERROR_TARIFF_NOT_SUPPORTED',
							['#SERVICE_NAME#' => $handlerClass::getClassTitle()]
						)
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
