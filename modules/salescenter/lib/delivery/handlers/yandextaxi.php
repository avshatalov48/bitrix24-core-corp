<?php

namespace Bitrix\SalesCenter\Delivery\Handlers;

use Sale\Handlers\Delivery\YandexTaxi\ServiceContainer;

/**
 * Class YandexTaxi
 * @package Bitrix\SalesCenter\Delivery\Handlers
 */
class YandexTaxi extends Base
{
	/**
	 * @inheritDoc
	 */
	public function getHandlerClass(): string
	{
		return '\\' . \Sale\Handlers\Delivery\YandextaxiHandler::class;
	}

	/**
	 * @inheritDoc
	 */
	public function getName()
	{
		return \Sale\Handlers\Delivery\YandextaxiHandler::getClassTitle();
	}

	/**
	 * @inheritDoc
	 */
	public function getCode(): string
	{
		return \Sale\Handlers\Delivery\YandextaxiHandler::SERVICE_CODE;
	}

	/**
	 * @inheritDoc
	 */
	protected function getImageName(): string
	{
		return 'yandex_taxi.png';
	}

	/**
	 * @inheritDoc
	 */
	public function isInstallable(): bool
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getInstalledColor()
	{
		return '#F7A700';
	}

	/**
	 * @inheritDoc
	 */
	public function getWizard()
	{
		return new \Bitrix\SalesCenter\Delivery\Wizard\YandexTaxi(
			ServiceContainer::getApi(),
			ServiceContainer::getRegionFinder(),
			ServiceContainer::getRegionCoordinatesMapper(),
			ServiceContainer::getTariffsChecker()
		);
	}
}
