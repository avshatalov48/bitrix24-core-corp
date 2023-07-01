<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Controller\Action\Terminal;

use Bitrix\CrmMobile\Terminal\LocHelper;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Crm;
use Bitrix\CrmMobile\Controller\Action;
use Bitrix\CrmMobile\Terminal\GetPaymentQuery;

LocHelper::loadMessages();

class CreatePaymentAction extends Action
{
	public function run(
		float $sum,
		string $currency,
		string $phoneNumber = null,
		?array $client = null,
		?string $clientName = null
	)
	{
		$order = $this->createOrder($currency);
		$this->setTerminalPlatform($order);

		$product = $this->prepareBasketProduct($sum, $currency);
		$basket = $this->createBasket($product);
		$setBasketResult = $order->setBasket($basket);
		if (!$setBasketResult->isSuccess())
		{
			$this->addErrors($setBasketResult->getErrors());
			return null;
		}

		if ($phoneNumber)
		{
			$this->setProperties($order, $phoneNumber);
		}

		$payment = $this->createPayment($order);

		$setContactCompanyResult = null;
		if ($client)
		{
			$setContactCompanyResult = $this->setContactCompany($order, $client);
		}
		elseif ($clientName || $phoneNumber)
		{
			$contactCompanyResult = $this->createContactCompany($phoneNumber, $clientName);
			if ($contactCompanyResult->isSuccess())
			{
				$setContactCompanyResult = $this->setContactCompany(
					$order,
					[
						'entityTypeId' => \CCrmOwnerType::Contact,
						'id' => $contactCompanyResult->getId()
					]
				);
			}
			else
			{
				$this->addErrors($contactCompanyResult->getErrors());
				return null;
			}
		}

		if ($setContactCompanyResult && !$setContactCompanyResult->isSuccess())
		{
			$this->addErrors($setContactCompanyResult->getErrors());
			return null;
		}

		$hasMeaningfulFields = $order->hasMeaningfulField();
		$finalActionResult = $order->doFinalAction($hasMeaningfulFields);
		if (!$finalActionResult->isSuccess())
		{
			$this->addErrors($finalActionResult->getErrors());
			return null;
		}

		$result = $order->save();
		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
			return null;
		}

		return [
			'payment' => (new GetPaymentQuery($payment->getId()))->execute(),
		];
	}

	private function createOrder(string $currency): Crm\Order\Order
	{
		$registry = $this->getRegistry();

		/** @var Crm\Order\Order $orderClassName */
		$orderClassName = $registry->getOrderClassName();

		$userId = (int)\CSaleUser::GetAnonymousUserID();
		/** @var Crm\Order\Order $order */
		$order = $orderClassName::create(SITE_ID, $userId, $currency);

		$order->setPersonTypeId(Crm\Order\PersonType::getContactPersonTypeId());

		$responsibleId = (int)$this->getCurrentUser()->getId();
		$order->setField('RESPONSIBLE_ID', $responsibleId);

		$this->disableContactAutoCreationMode($order);

		return $order;
	}

	private function createBasket(array $product): Sale\BasketBase
	{
		$basket = Sale\Basket::create(SITE_ID);

		$item = $basket->createItem('', $product['PRODUCT_ID']);
		unset($product['PRODUCT_ID']);
		$item->setFields($product);

		return $basket;
	}

	private function setTerminalPlatform(Crm\Order\Order $order): Sale\Result
	{
		$tradeBindingCollection = $order->getTradeBindingCollection();
		/** @var Sale\TradeBindingEntity $binding */
		$binding = $tradeBindingCollection->createItem();

		return $binding->setFields([
			'TRADING_PLATFORM_ID' => $this->getTerminalPlatformId(),
		]);
	}

	private function prepareBasketProduct(float $sum, string $currency): array
	{
		$basketProductFields = [
			'PRODUCT_ID' => 0,
			'NAME' => Loc::getMessage('M_CRM_TL_BASKET_ITEM_NAME'),
			'CUSTOM_PRICE' => 'Y',
			'PRICE' => $sum,
			'CURRENCY' => $currency,
			'QUANTITY' => 1,
		];

		if (Main\Loader::includeModule('catalog'))
		{
			$measure = [];
			$measureResult = \CCatalogMeasure::getList(
				[],
				['CODE' => \CCatalogMeasure::DEFAULT_MEASURE_CODE],
				false,
				false,
				['CODE', 'SYMBOL_RUS']
			);
			if ($measureResult->SelectedRowsCount())
			{
				$measure = $measureResult->Fetch();
			}
			else
			{
				$measure = \CCatalogMeasure::getDefaultMeasure(true);
			}

			if ($measure)
			{
				$basketProductFields['MEASURE_CODE'] = $measure['CODE'];
				$basketProductFields['MEASURE_NAME'] = $measure['SYMBOL_RUS'];
			}
		}

		return $basketProductFields;
	}

	private function createPayment(Crm\Order\Order $order): Sale\Payment
	{
		$paymentCollection = $order->getPaymentCollection();
		$payment = $paymentCollection->createItem(
			Sale\PaySystem\Manager::getObjectById(
				Sale\PaySystem\Manager::getInnerPaySystemId()
			)
		);

		$payment->setField('SUM', $order->getPrice());
		$payment->setField('CURRENCY', $order->getCurrency());
		$payment->setField('RESPONSIBLE_ID', $order->getField('RESPONSIBLE_ID'));

		return $payment;
	}

	private function setContactCompany(Crm\Order\Order $order, $existingClient): Sale\Result
	{
		$result = new Sale\Result();

		$clientCollection = $order->getContactCompanyCollection();

		$entityTypeId = (int)$existingClient['entityTypeId'];
		if ($entityTypeId === \CCrmOwnerType::Company)
		{
			/** @var Crm\Order\ContactCompanyEntity $contactCompanyEntity */
			$contactCompanyEntity = $clientCollection->createCompany();
		}
		elseif ($entityTypeId === \CCrmOwnerType::Contact)
		{
			/** @var Crm\Order\ContactCompanyEntity $contactCompanyEntity */
			$contactCompanyEntity = $clientCollection->createContact();
		}

		if (isset($contactCompanyEntity))
		{
			return $contactCompanyEntity->setFields([
				'ENTITY_ID' => $existingClient['id'],
				'IS_PRIMARY' => 'Y'
			]);
		}

		return $result;
	}

	private function createContactCompany(?string $phone, ?string $name): Sale\Result
	{
		$result = new Sale\Result();

		$userId = (int)$this->getCurrentUser()->getId();
		$fields = [
			'NAME' => $name ?? '',
			'ASSIGNED_BY_ID' => $userId,
			'TYPE_ID' => 'CLIENT',
			'SOURCE_ID' => 'STORE',
			'FM' => [
				'PHONE' => [
					'n1' => [
						'VALUE_TYPE' => 'MOBILE',
						'VALUE' => $phone ?? '',
					],
				],
			],
		];

		$options = [
			'DISABLE_REQUIRED_USER_FIELD_CHECK' => true,
			'REGISTER_SONET_EVENT' => true,
		];

		$contact = new \CCrmContact(false);
		$id = (int)$contact->Add($fields, true, $options);

		if ($id > 0)
		{
			$result->setId($id);
		}
		else
		{
			$result->addError(new Main\Error($contact->LAST_ERROR));
		}

		return $result;
	}

	private function setProperties(Crm\Order\Order $order, string $phone)
	{
		$propertyValue = $order->getPropertyCollection()->createItem(
			Crm\Terminal\OrderProperty::getTerminalProperty()
		);
		$propertyValue->setValue($phone);
	}

	private function getTerminalPlatformId(): int
	{
		return (int)Crm\Order\TradingPlatform\Terminal::getInstanceByCode(
			Crm\Order\TradingPlatform\Terminal::TRADING_PLATFORM_CODE
		)->getIdIfInstalled();
	}

	private function getRegistry(): Sale\Registry
	{
		return Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
	}

	private function disableContactAutoCreationMode(Crm\Order\Order $order): void
	{
		$contactCompanyCollection = $order->getContactCompanyCollection();
		if ($contactCompanyCollection)
		{
			$contactCompanyCollection->disableAutoCreationMode();
		}
	}
}
