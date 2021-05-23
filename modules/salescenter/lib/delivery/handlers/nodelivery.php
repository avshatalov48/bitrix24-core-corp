<?php

namespace Bitrix\SalesCenter\Delivery\Handlers;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Delivery\Services\EmptyDeliveryService;

Loc::loadMessages(__FILE__);

/**
 * Class NoDelivery
 * @package Bitrix\SalesCenter\Delivery\Handlers
 */
class NoDelivery extends Base
{
	/**
	 * @return string
	 */
	public function getHandlerClass(): string
	{
		return '\\' . EmptyDeliveryService::class;
	}

	/**
	 * @inheritDoc
	 */
	public function getName()
	{
		return Loc::getMessage('SALESCENTER_DELIVERY_HANDLERS_NO_DELIVERY_TITLE');
	}

	/**
	 * @inheritDoc
	 */
	public function getCode(): string
	{
		return 'NO_DELIVERY';
	}

	/**
	 * @inheritDoc
	 */
	protected function getImageName(): string
	{
		return 'no_delivery.svg';
	}

	/**
	 * @inheritDoc
	 */
	public function isInstallable(): bool
	{
		return false;
	}
}
