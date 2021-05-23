<?php

namespace Bitrix\SalesCenter\Delivery\Handlers;

use Bitrix\SalesCenter\Delivery\Wizard\WizardContract;

/**
 * Interface HandlerContract
 * @package Bitrix\SalesCenter\Delivery\Handlers
 */
interface HandlerContract
{
	/**
	 * @return bool
	 */
	public function isAvailable(): bool;

	/**
	 * @return string
	 */
	public function getHandlerClass(): string;

	/**
	 * @return string
	 */
	public function getCode(): string;

	/**
	 * @return string|null
	 */
	public function getName();

	/**
	 * @return string|null
	 */
	public function getShortDescription();

	/**
	 * @return string|null
	 */
	public function getImagePath();

	/**
	 * @return bool
	 */
	public function doesImageContainName(): bool;

	/**
	 * @return string|null
	 */
	public function getInstalledImagePath();

	/**
	 * @return string|null
	 */
	public function getWorkingImagePath();

	/**
	 * @return string|null
	 */
	public function getInstalledColor();

	/**
	 * @return bool
	 */
	public function isInstalled(): bool;

	/**
	 * @return bool
	 */
	public function isInstallable(): bool;

	/**
	 * @return string
	 */
	public function getInstallationLink(): string;

	/**
	 * @param int $serviceId
	 * @return string
	 */
	public function getEditLink(int $serviceId): string;

	/**
	 * @return WizardContract|null
	 */
	public function getWizard();
}
