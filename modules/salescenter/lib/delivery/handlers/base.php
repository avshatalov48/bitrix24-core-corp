<?php

namespace Bitrix\SalesCenter\Delivery\Handlers;

use Bitrix\Sale\Delivery\Services\Table;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Base
 * @package Bitrix\SalesCenter\Delivery\Handlers
 */
abstract class Base implements HandlerContract
{
	/**
	 * @inheritDoc
	 */
	public function isAvailable(): bool
	{
		/** @var \Bitrix\Sale\Delivery\Services\Base $handlerClass */
		$handlerClass = $this->getHandlerClass();

		return $handlerClass::isHandlerCompatible();
	}

	/**
	 * @inheritDoc
	 */
	public function isInstalled(): bool
	{
		$active = Table::getList(
			[
				'filter' => [
					'=CLASS_NAME' => $this->getHandlerClass(),
					'=ACTIVE' => 'Y'
				]
			]
		)->fetch();

		return $active ? true : false;
	}

	/**
	 * @inheritDoc
	 */
	public function getInstallationLink(): string
	{
		return sprintf(
			'%s?%s',
			$this->getInstallationComponentPath(),
			http_build_query($this->getInstallationLinkParams())
		);
	}

	/**
	 * @return array
	 */
	protected function getInstallationLinkParams(): array
	{
		return [
			'code' => $this->getCode(),
			'analyticsLabel' => 'salescenterClickDeliveryInstall',

		];
	}

	/**
	 * @inheritDoc
	 */
	public function getEditLink(int $serviceId): string
	{
		return sprintf(
			'%s?%s',
			$this->getInstallationComponentPath(),
			http_build_query(
				[
					'code' => $this->getCode(),
					'service_id' => $serviceId,
				]
			)
		);
	}

	/**
	 * @return string
	 */
	protected function getInstallationComponentPath(): string
	{
		return getLocalPath(
			sprintf(
				'components%s/slider.php',
				\CComponentEngine::makeComponentPath('bitrix:salescenter.delivery.wizard')
			)
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getImagePath()
	{
		return $this->getImagesPath() . $this->getImageName() . '?v=2';
	}

	/**
	 * @inheritDoc
	 */
	public function doesImageContainName(): bool
	{
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function getInstalledImagePath()
	{
		return $this->getImagesPath() . sprintf('installed_%s', $this->getImageName()) . '?v=2';
	}

	/**
	 * @inheritDoc
	 */
	public function getWorkingImagePath()
	{
		return $this->getImagesPath() . sprintf('working_%s', $this->getImageName());
	}

	/**
	 * @inheritDoc
	 */
	public function getInstalledColor()
	{
		return null;
	}

	/**
	 * @return bool|string
	 */
	protected function getImagesPath()
	{
		$templatePath = '/templates/.default/images/';

		$componentPath = \CComponentEngine::makeComponentPath('bitrix:salescenter.delivery.panel');

		return getLocalPath('components' . $componentPath . $templatePath);
	}

	/**
	 * @return string
	 */
	abstract protected function getImageName(): string;

	/**
	 * @inheritDoc
	 */
	public function getShortDescription()
	{
		/** @var \Bitrix\Sale\Delivery\Services\Base $handlerClass */
		$handlerClass = static::getHandlerClass();

		return Loc::getMessage(
			'SALESCENTER_DELIVERY_HANDLERS_SHORT_DESCRIPTION',
			[
				'#SERVICE_NAME#' => $handlerClass::getClassTitle()
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getWizard()
	{
		return null;
	}
}
