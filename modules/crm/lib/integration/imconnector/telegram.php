<?php

namespace Bitrix\Crm\Integration\ImConnector;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\ImConnector;
use Bitrix\Catalog;

Main\Localization\Loc::loadMessages(__FILE__);

class Telegram
{
	public function __construct()
	{
		Main\Loader::includeModule('imconnector');
		Main\Loader::includeModule('catalog');
	}

	/**
	 * Send message to telegram about order
	 *
	 * @param Crm\Order\Order $order
	 * @return void
	 */
	public function sendOrderNotification(Crm\Order\Order $order): void
	{
		$message = Main\Localization\Loc::getMessage(
			'CRM_IMCONNECTOR_TELEGRAM_ORDER',
			[
				'#ORDER_ID#' => $order->getField('ACCOUNT_NUMBER'),
				'#DATE#' => $this->getFormattedDate($order->getDateInsert()),
				'#SUM_WITH_CURRENCY#' => $this->getSumWithCurrency($order->getPrice(), $order->getCurrency()),
			]
		);

		$contact = $this->getPrimaryContact($order);
		if ($contact)
		{
			$this->sendMessage($message, $contact);
		}
	}

	/**
	 * Send message to telegram about payment is paid
	 *
	 * @param Crm\Order\Payment $payment
	 * @return void
	 */
	public function sendPaymentPaidNotification(Crm\Order\Payment $payment): void
	{
		if (!$payment->isPaid())
		{
			return;
		}

		$order = $payment->getOrder();

		$message = Main\Localization\Loc::getMessage(
			'CRM_IMCONNECTOR_TELEGRAM_PAYMENT_PAID',
			[
				'#ORDER_ID#' => $order->getField('ACCOUNT_NUMBER'),
				'#DATE#' => $this->getFormattedDate($order->getDateInsert()),
				'#SUM_WITH_CURRENCY#' => $this->getSumWithCurrency($order->getPrice(), $order->getCurrency()),
			]
		);

		$contact = $this->getPrimaryContact($order);
		if ($contact)
		{
			$this->sendMessage($message, $contact);
		}
	}

	/**
	 * Send message to telegram about shipment is shipped
	 *
	 * @param Crm\Order\Shipment $shipment
	 * @return void
	 */
	public function sendShipmentDeductedNotification(Crm\Order\Shipment $shipment): void
	{
		if (!$shipment->isShipped())
		{
			return;
		}

		$order = $shipment->getOrder();

		$message = Main\Localization\Loc::getMessage(
			'CRM_IMCONNECTOR_TELEGRAM_SHIPMENT_DEDUCTED',
			[
				'#ORDER_ID#' => $order->getField('ACCOUNT_NUMBER'),
				'#DATE#' => $this->getFormattedDate($order->getDateInsert()),
				'#SUM_WITH_CURRENCY#' => $this->getSumWithCurrency($order->getPrice(), $order->getCurrency()),
			]
		);

		$contact = $this->getPrimaryContact($order);
		if ($contact)
		{
			$this->sendMessage($message, $contact);
		}
	}

	/**
	 * Send message to telegram about shipment is ready
	 *
	 * @param Crm\Order\Shipment $shipment
	 * @return void
	 */
	public function sendShipmentReadyNotification(Crm\Order\Shipment $shipment): void
	{
		$order = $shipment->getOrder();

		$store = Catalog\StoreTable::getById($shipment->getStoreId())->fetch();
		if ($store)
		{
			$message = Main\Localization\Loc::getMessage(
				'CRM_IMCONNECTOR_TELEGRAM_SHIPMENT_READY',
				[
					'#ORDER_ID#' => $order->getField('ACCOUNT_NUMBER'),
					'#DATE#' => $this->getFormattedDate($order->getDateInsert()),
					'#SUM_WITH_CURRENCY#' => $this->getSumWithCurrency($order->getPrice(), $order->getCurrency()),
					'#STORE_ADDRESS#' => $store['ADDRESS'],
				]
			);

			$contact = $this->getPrimaryContact($order);
			if ($contact)
			{
				$this->sendMessage($message, $contact);
			}
		}
	}

	private function getFormattedDate(Main\Type\DateTime $dateTime): string
	{
		return (string)FormatDate('d.m.Y', $dateTime);
	}

	private function getSumWithCurrency(float $sum, string $currency): string
	{
		$search = [
			'&nbsp;',
		];

		return str_replace($search, ' ', (string)SaleFormatCurrency($sum, $currency));
	}

	private function getPrimaryContact(Crm\Order\Order $order): ?Crm\Order\Contact
	{
		$contactCompanyCollection = $order->getContactCompanyCollection();
		if ($contactCompanyCollection)
		{
			$contacts = $contactCompanyCollection->getContacts();
			/** @var Crm\Order\Contact $contact */
			foreach ($contacts as $contact)
			{
				if ($contact->isPrimary())
				{
					return $contact;
				}
			}
		}

		return null;
	}

	private function sendMessage(string $message, Crm\Order\Contact $contact): void
	{
		$crmEntityType = Crm\Order\Contact::getEntityTypeName();
		$crmEntityId = $contact->getField('ENTITY_ID');

		(new ImConnector\Connectors\TelegramBot())
			->sendAutomaticMessage($message, $crmEntityType, $crmEntityId)
		;
	}
}