<?php

namespace Bitrix\SalesCenter\Delivery\Handlers;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Services;
use Bitrix\Sale\Delivery\Services\Table;

Loc::loadMessages(__FILE__);

/**
 * Class RestDelivery
 * @package Bitrix\SalesCenter\Delivery\Handlers
 */
class RestDelivery extends Base implements IRestHandler
{
	/** @var string */
	private $restHandlerCode;

	/** @var array */
	private $restHandler;

	/**
	 * RestDelivery constructor.
	 * @param string $code
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function __construct(string $code)
	{
		$this->restHandlerCode = $code;
		$this->restHandler = Services\Manager::getRestHandlerList()[$code];
	}

	/**
	 * @inheritDoc
	 */
	public function getRestHandlerCode(): ?string
	{
		return $this->restHandlerCode;
	}

	/**
	 * @inheritDoc
	 */
	public function getHandlerClass(): string
	{
		return '\\' . \Sale\Handlers\Delivery\RestHandler::class;
	}

	/**
	 * @inheritDoc
	 */
	public function getName()
	{
		return $this->restHandler['NAME'];
	}

	/**
	 * @inheritDoc
	 */
	public function getCode(): string
	{
		return 'REST_DELIVERY';
	}

	/**
	 * @inheritDoc
	 */
	protected function getImageName(): string
	{
		return 'rest_delivery.svg';
	}

	/**
	 * @inheritDoc
	 */
	public function doesImageContainName(): bool
	{
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getWorkingImagePath(): string
	{
		return $this->getImagesPath() . 'delivery_logo.png';
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
		return '#2870a3';
	}

	/**
	 * @inheritDoc
	 */
	public function getWizard()
	{
		return new \Bitrix\SalesCenter\Delivery\Wizard\RestDelivery();
	}

	/**
	 * @inheritDoc
	 */
	protected function getInstallationLinkParams(): array
	{
		return array_merge(
			parent::getInstallationLinkParams(),
			[
				'restHandlerCode' => $this->restHandlerCode,
			]
		);
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
					'restHandlerCode' => $this->restHandlerCode,
					'service_id' => $serviceId,
				]
			)
		);
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
					'%CONFIG' => $this->getRestHandlerCode(),
					'=ACTIVE' => 'Y',
				]
			]
		)->fetch();

		return $active ? true : false;
	}

	/**
	 * @inheritDoc
	 */
	public function getShortDescription()
	{
		return $this->restHandler['DESCRIPTION'];
	}
}
