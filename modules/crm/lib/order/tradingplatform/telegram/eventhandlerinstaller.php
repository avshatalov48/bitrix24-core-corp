<?php

namespace Bitrix\Crm\Order\TradingPlatform\Telegram;

use Bitrix\Main;

class EventHandlerInstaller
{
	private Main\EventManager $eventManager;

	private const FROM_MODULE = 'fromModule';
	private const EVENT_TYPE = 'eventType';
	private const TO_MODULE = 'toModule';
	private const TO_CLASS = 'toClass';
	private const TO_METHOD = 'toMethod';

	private const EVENTS = [
		[
			self::FROM_MODULE => 'sale',
			self::EVENT_TYPE => 'OnSaleOrderBeforeSaved',
			self::TO_MODULE => 'crm',
			self::TO_CLASS => EventHandler::class,
			self::TO_METHOD => 'setTelegramTradingPlatform',
		],
		[
			self::FROM_MODULE => 'sale',
			self::EVENT_TYPE => 'OnSaleOrderSaved',
			self::TO_MODULE => 'crm',
			self::TO_CLASS => EventHandler::class,
			self::TO_METHOD => 'sendOrderNotification',
		],
		[
			self::FROM_MODULE => 'sale',
			self::EVENT_TYPE => 'OnPaymentPaid',
			self::TO_MODULE => 'crm',
			self::TO_CLASS => EventHandler::class,
			self::TO_METHOD => 'sendPaymentPaidNotification',
		],
		[
			self::FROM_MODULE => 'sale',
			self::EVENT_TYPE => 'OnShipmentDeducted',
			self::TO_MODULE => 'crm',
			self::TO_CLASS => EventHandler::class,
			self::TO_METHOD => 'sendShipmentDeductedNotification',
		],
		[
			self::FROM_MODULE => 'sale',
			self::EVENT_TYPE => 'OnSaleShipmentEntitySaved',
			self::TO_MODULE => 'crm',
			self::TO_CLASS => EventHandler::class,
			self::TO_METHOD => 'sendShipmentReadyNotification',
		],
		[
			self::FROM_MODULE => 'main',
			self::EVENT_TYPE => 'OnEpilog',
			self::TO_MODULE => 'crm',
			self::TO_CLASS => EventHandler::class,
			self::TO_METHOD => 'saveTelegramUserCodeToSession',
		],
	];

	public function __construct()
	{
		$this->eventManager = Main\EventManager::getInstance();
	}

	public function onInstall(): void
	{
		foreach (self::EVENTS as $event)
		{
			$this->eventManager->registerEventHandler(
				$event[self::FROM_MODULE],
				$event[self::EVENT_TYPE],
				$event[self::TO_MODULE],
				$event[self::TO_CLASS],
				$event[self::TO_METHOD]
			);
		}
	}

	public function onUninstall(): void
	{
		foreach (self::EVENTS as $event)
		{
			$this->eventManager->unRegisterEventHandler(
				$event[self::FROM_MODULE],
				$event[self::EVENT_TYPE],
				$event[self::TO_MODULE],
				$event[self::TO_CLASS],
				$event[self::TO_METHOD]
			);
		}
	}
}
