<?php

namespace Bitrix\SalesCenter\Delivery\Handlers;

use Bitrix\Sale\Delivery\Services\Table;

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
		return true;
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
		return $this->getImagesPath() . $this->getImageName();
	}

	/**
	 * @inheritDoc
	 */
	public function getInstalledImagePath()
	{
		return $this->getImagesPath() . sprintf('installed_%s', $this->getImageName());
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
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getWizard()
	{
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function isRestHandler(): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getRestHandlerCode(): string
	{
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function getProfileClass(): ?string
	{
		return null;
	}
}
