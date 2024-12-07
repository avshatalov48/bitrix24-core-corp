<?php

namespace Bitrix\CrmMobile\Controller\ReceivePayment;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\CrmMobile\Controller\Salescenter\Product2BasketItemConverter;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Sale\Label\Label;
use Bitrix\Salescenter\Analytics\Dictionary\SubSectionDictionary;
use Bitrix\SalesCenter\Controller\Order;
use Bitrix\SalesCenter\Integration\CrmManager;

Loader::requireModule('salescenter');

class Payment extends Base
{
	public function createPaymentAction(array $options): bool
	{
		$entity = Container::getInstance()->getFactory((int)$options['ownerTypeId'])->getItem((int)$options['ownerId']);

		if (isset($options['selectedContact']))
		{
			$bindContactResult = $this->bindContactToEntity($options['selectedContact']['id'], $entity);
			if (!$bindContactResult->isSuccess())
			{
				$this->errorCollection->clear();
				$errors = [
					new Error(Loc::getMessage('RECEIVE_PAYMENT_SAVE_CONTACT_ERROR')),
				];
				$errors = $this->markErrorsAsPublic($errors);
				$this->addErrors($errors);
			}
		}

		$products = Product2BasketItemConverter::convert($options['products']);
		unset($options['products']);
		$options['orderId'] = CrmManager::getOrderIdByEntity($entity);
		$options['paymentLabels'] = [
			new Label('subSection', SubSectionDictionary::MOBILE->value)
		];

		$this->forward(
			Order::class,
			'createPayment',
			[
				'basketItems' => $products,
				'options' => $options,
			]
		);

		$errors = $this->getErrors();
		if (!empty($errors))
		{
			$this->errorCollection->clear();
			$errors = $this->markErrorsAsPublic($errors);
			$this->addErrors($errors);
		}

		return true;
	}

	private function bindContactToEntity(int $contactId, Item $entity): Result
	{
		$contactBinding = EntityBinding::prepareEntityBindings(\CCrmOwnerType::Contact, [$contactId]);
		EntityBinding::markFirstAsPrimary($contactBinding);
		$entity->bindContacts($contactBinding);

		return $entity->save();
	}

	public function resendMessageAction(array $options): bool
	{
		$factory = Container::getInstance()->getFactory((int)$options['ownerTypeId']);
		if (!$factory)
		{
			return false;
		}

		$entity = $factory->getItem((int)$options['ownerId']);
		if (!$entity)
		{
			return false;
		}

		if (isset($options['selectedContact']))
		{
			$bindContactResult = $this->bindContactToEntity($options['selectedContact']['id'], $entity);
			if (!$bindContactResult->isSuccess())
			{
				$this->errorCollection->clear();
				$errors = [
					new Error(Loc::getMessage('RECEIVE_PAYMENT_SAVE_CONTACT_ERROR')),
				];
				$errors = $this->markErrorsAsPublic($errors);
				$this->addErrors($errors);
			}
		}

		$orderId = CrmManager::getOrderIdByEntity($entity);

		$this->forward(
			Order::class,
			'resendPayment',
			[
				'orderId' => $orderId,
				'paymentId' => $options['paymentId'],
				'shipmentId' => $options['shipmentId'],
				'options' => $options,
			]
		);

		$errors = $this->getErrors();
		if (!empty($errors))
		{
			$this->errorCollection->clear();
			$errors = $this->markErrorsAsPublic($errors);
			$this->addErrors($errors);
		}

		return true;
	}
}
