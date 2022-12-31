<?php

namespace Bitrix\Crm\Order\TradingPlatform\Telegram;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Catalog;
use Bitrix\ImConnector;

class EventHandler
{
	private const TELEGRAM_WEB_APP = 'tgWebApp';
	private const TELEGRAM_USER_CODE = 'tgUserCode';

	// region Event handlers
	/**
	 * Change trading platform on telegram before save order
	 *
	 * @param Main\Event $event
	 * @return Main\EventResult
	 */
	public static function setTelegramTradingPlatform(Main\Event $event): Main\EventResult
	{
		/** @var Crm\Order\Order $order */
		$order = $event->getParameter('ENTITY');
		if (!$order instanceof Crm\Order\Order || !self::isTelegramOrder())
		{
			return new Main\EventResult( Main\EventResult::SUCCESS);
		}

		self::setTradeBinding($order);

		$clientInfo = self::getClientInfo();

		if ($clientInfo['DEAL_ID'] > 0 && self::needDealBinding($clientInfo['DEAL_ID']))
		{
			self::setDealBinding($order, $clientInfo['DEAL_ID']);
		}

		if ($clientInfo['CONTACT_ID'] > 0)
		{
			self::setContact($order, $clientInfo['CONTACT_ID']);
		}

		return new Main\EventResult( Main\EventResult::SUCCESS, $order);
	}

	/**
	 * Send message to telegram about order
	 *
	 * @param Main\Event $event
	 * @return void
	 */
	public static function sendOrderNotification(Main\Event $event): void
	{
		/** @var Crm\Order\Order $order */
		$order = $event->getParameter('ENTITY');
		if (!$order instanceof Crm\Order\Order)
		{
			return;
		}

		$isNew = $event->getParameter('IS_NEW');
		if (!$isNew)
		{
			return;
		}

		if (self::isSetTelegramTradeBinding($order))
		{
			self::syncOrderProducts($order);
			self::saveTelegramUserCodeToContact($order);

			$telegram = new Crm\Integration\ImConnector\Telegram();
			$telegram->sendOrderNotification($order);
		}
	}

	/**
	 * Send message to telegram about payment is paid
	 *
	 * @param Main\Event $event
	 * @return void
	 */
	public static function sendPaymentPaidNotification(Main\Event $event): void
	{
		/** @var Crm\Order\Payment $payment */
		$payment = $event->getParameter('ENTITY');
		if (!$payment instanceof Crm\Order\Payment)
		{
			return;
		}

		$order = $payment->getOrder();
		if (self::isSetTelegramTradeBinding($order))
		{
			$telegram = new Crm\Integration\ImConnector\Telegram();
			$telegram->sendPaymentPaidNotification($payment);
		}
	}

	/**
	 * Send message to telegram about shipment is shipped
	 *
	 * @param Main\Event $event
	 * @return void
	 */
	public static function sendShipmentDeductedNotification(Main\Event $event): void
	{
		/** @var Crm\Order\Shipment $shipment */
		$shipment = $event->getParameter('ENTITY');
		if (!$shipment instanceof Crm\Order\Shipment || $shipment->isSystem())
		{
			return;
		}

		$order = $shipment->getOrder();
		if (self::isSetTelegramTradeBinding($order))
		{
			$telegram = new Crm\Integration\ImConnector\Telegram();
			$telegram->sendShipmentDeductedNotification($shipment);
		}
	}

	/**
	 * Send message to telegram about shipment is ready
	 *
	 * @param Main\Event $event
	 * @return void
	 */
	public static function sendShipmentReadyNotification(Main\Event $event): void
	{
		/** @var Crm\Order\Shipment $shipment */
		$shipment = $event->getParameter('ENTITY');
		if (!$shipment instanceof Crm\Order\Shipment || $shipment->isSystem())
		{
			return;
		}

		$isNew = $event->getParameter('IS_NEW');
		if (!$isNew)
		{
			return;
		}

		$order = $shipment->getOrder();
		if (self::isSetTelegramTradeBinding($order))
		{
			$storeId = $shipment->getStoreId(); 
			if ($storeId)
			{
				$telegram = new Crm\Integration\ImConnector\Telegram();
				$telegram->sendShipmentReadyNotification($shipment);
			}
		}
	}

	/**
	 * If request from telegram app save telegram user code to session
	 *
	 * @return void
	 */
	public static function saveTelegramUserCodeToSession(): void
	{
		$request = Main\Application::getInstance()->getContext()->getRequest();

		$isTelegramRequest =
			$request->get(self::TELEGRAM_WEB_APP) !== null
			&& !empty($request->get(self::TELEGRAM_USER_CODE))
		;

		if ($isTelegramRequest)
		{
			$session = Main\Application::getInstance()->getSession();
			if ($session->isAccessible())
			{
				$session->set(self::TELEGRAM_USER_CODE, $request->get(self::TELEGRAM_USER_CODE));
			}
		}
	}
	// endregion

	private static function isTelegramOrder(): bool
	{
		$telegramUserCode = self::getTelegramUserCode();
		if ($telegramUserCode)
		{
			return self::isValidTelegramUserUserCode($telegramUserCode);
		}

		return false;
	}

	private static function getTelegramUserCode(): ?string
	{
		$telegramUserCode = null;

		$session = Main\Application::getInstance()->getSession();
		if ($session->isAccessible() && $session->has(self::TELEGRAM_USER_CODE))
		{
			$telegramUserCode = (string)$session->get(self::TELEGRAM_USER_CODE);
		}

		return $telegramUserCode;
	}

	private static function isValidTelegramUserUserCode(string $telegramUserCode): bool
	{
		if (
			Main\Loader::includeModule('crm')
			&& Main\Loader::includeModule('imconnector')
		)
		{
			return ImConnector\User::validateUserCode($telegramUserCode);
		}

		return false;
	}

	private static function isSetTelegramTradeBinding(Crm\Order\Order $order): bool
	{
		$collection = $order->getTradeBindingCollection();

		/** @var Crm\Order\TradeBindingEntity $binding */
		foreach ($collection as $binding)
		{
			$platform = $binding->getTradePlatform();
			if (
				$platform
				&& $platform->getCode() === Telegram::TRADING_PLATFORM_CODE
			)
			{
				return true;
			}
		}

		return false;
	}

	private static function setTradeBinding(Crm\Order\Order $order): void
	{
		$platformCode = Crm\Order\TradingPlatform\Telegram\Telegram::TRADING_PLATFORM_CODE;
		$telegram = Crm\Order\TradingPlatform\Telegram\Telegram::getInstanceByCode($platformCode);
		if ($telegram->isInstalled() && $telegram->isActive())
		{
			$collection = $order->getTradeBindingCollection();
			/** @var Crm\Order\TradeBindingEntity $binding */
			foreach ($collection as $binding)
			{
				$platform = $binding->getTradePlatform();
				if ($platform && $platform->getCode() !== $telegram->getCode())
				{
					$binding->setFieldNoDemand('TRADING_PLATFORM_ID', $telegram->getId());
					break;
				}
			}
		}
	}

	private static function setContact(Crm\Order\Order $order, int $contactId): void
	{
		$contactCompanyCollection = $order->getContactCompanyCollection();
		if (!$contactCompanyCollection->isEmpty())
		{
			return;
		}

		$contactCompanyCollection = $order->getContactCompanyCollection();
		/** @var Crm\Order\Contact $contact */
		$contact = $contactCompanyCollection->createContact();
		$contact->setField('ENTITY_ID', $contactId);
		$contact->setField('IS_PRIMARY', 'Y');
	}

	private static function setDealBinding(Crm\Order\Order $order, int $dealId): void
	{
		$entityBinding = $order->getEntityBinding();
		if (!$entityBinding)
		{
			$entityBinding = $order->createEntityBinding();
			$entityBinding->setFields([
				'OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
				'OWNER_ID' => $dealId,
			]);
		}
	}

	private static function getClientInfo(): array
	{
		$telegramUserCode = self::getTelegramUserCode();

		$selector = new Crm\Integrity\ActualEntitySelector();
		$selector->appendCommunicationCriterion(
			Crm\Communication\Type::TELEGRAM_NAME,
			'imol|' . $telegramUserCode
		)->search();

		return [
			'DEAL_ID' => (int)$selector->getDealId(),
			'CONTACT_ID' => (int)$selector->getContactId(),
		];
	}

	private static function needDealBinding(int $dealId): bool
	{
		return !self::isDealBindingExists($dealId) && !self::isDealWithProducts($dealId);
	}

	private static function isDealBindingExists(int $dealId): bool
	{
		return (bool)Crm\Binding\OrderEntityTable::getList([
			'filter' => [
				'=OWNER_TYPE_ID' => \CCrmOwnerType::Deal,
				'=OWNER_ID' => $dealId,
			],
			'limit' => 1,
		])->fetch();
	}

	private static function isDealWithProducts(int $dealId): bool
	{
		return count(\CCrmDeal::LoadProductRows($dealId)) > 0;
	}

	private static function syncOrderProducts(Crm\Order\Order $order): void
	{
		$entityBinding = $order->getEntityBinding();
		if ($entityBinding)
		{
			$productManager = new Crm\Order\ProductManager(
				$entityBinding->getOwnerTypeId(),
				$entityBinding->getOwnerId()
			);
			$productManager->setOrder($order);

			$basketItems = self::prepareBasketItemsForSync($order);
			$productManager->syncOrderProducts($basketItems);
		}
	}

	private static function prepareBasketItemsForSync(Crm\Order\Order $order): array
	{
		$basketItems = [];

		if (Main\Loader::includeModule('catalog'))
		{
			$formBuilder = new Catalog\v2\Integration\JS\ProductForm\BasketBuilder();

			/** @var Crm\Order\Shipment $shipment */
			foreach ($order->getShipmentCollection() as $shipment)
			{
				/** @var Crm\Order\ShipmentItem $shipmentItem */
				foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
				{
					$basketItem = $shipmentItem->getBasketItem();
					$product = $basketItem->getFieldValues();

					$item = $formBuilder->loadItemBySkuId($product['PRODUCT_ID']);
					if ($item)
					{
						$item
							->setDetailUrlManagerType(Crm\Product\Url\ProductBuilder::TYPE_ID)
							->addAdditionalField('originProductId', (string)$product['PRODUCT_ID'])
							->addAdditionalField('originBasketId', (string)$product['ID'])
							->setName($product['NAME'])
							->setPrice((float)$product['PRICE'])
							->setCode($product['ID'])
							->setBasePrice((float)$product['BASE_PRICE'])
							->setPriceExclusive((float)$product['PRICE'])
							->setQuantity((float)$product['QUANTITY'])
							->setMeasureCode((int)$product['MEASURE_CODE'])
							->setMeasureName($product['MEASURE_NAME'])
						;

						$basketItems[] = $item->getFields();
					}
				}
			}
		}

		return $basketItems;
	}

	private static function saveTelegramUserCodeToContact(Crm\Order\Order $order): void
	{
		$telegramUserCode = self::getTelegramUserCode();
		if ($telegramUserCode)
		{
			$contactCompanyCollection = $order->getContactCompanyCollection();

			/** @var Crm\Order\Contact $contact */
			$contact = $contactCompanyCollection->getPrimaryContact();
			if ($contact)
			{
				$crmEntityType = Crm\Order\Contact::getEntityTypeName();
				$crmEntityId = $contact->getField('ENTITY_ID');

				$value = 'imol|' . $telegramUserCode;

				$dbRes = \CCrmFieldMulti::GetListEx(
					['ID' => 'asc'],
					[
						'ENTITY_ID' => $crmEntityType,
						'ELEMENT_ID' => $crmEntityId,
						'VALUE_TYPE' => Crm\Communication\Type::TELEGRAM_NAME,
						'VALUE' => $value,
					]
				);
				if (!$dbRes->Fetch())
				{
					$arFields = [
						'ID' => $crmEntityId,
						'FM' => [
							'IM' => [
								'n0' => [
									'VALUE_TYPE' => Crm\Communication\Type::TELEGRAM_NAME,
									'VALUE' => $value,
								],
							],
						]
					];
					$crmContact = new \CCrmContact(false);
					$crmContact->Update($crmEntityId, $arFields);
				}
			}
		}
	}
}
