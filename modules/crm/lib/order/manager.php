<?php

namespace Bitrix\Crm\Order;

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Sale\Result;
use Bitrix\Sale\Delivery;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class Driver
 * @package Bitrix\Crm\Order
 */
final class Manager
{
	const ORDER_PAYMENT_DOCUMENT_TYPE_CANCEL = 0x03;
	const ORDER_PAYMENT_DOCUMENT_TYPE_RETURN = 0x02;
	const ORDER_PAYMENT_DOCUMENT_TYPE_VOUCHER = 0x01;

	/**
	 * @param $id
	 * @param $statusId
	 * @param string $reasonCanceled
	 * @return Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Exception
	 */
	public static function setOrderStatus($id, $statusId, $reasonCanceled = '')
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return new Result();
		}

		$id = (int)$id;
		$result = new Result();

		if($id <= 0)
			return $result;

		if(strlen($statusId) <= 0)
			return $result;

		$order = Order::load($id);

		if(!$order)
		{
			$result->addError(new Error('Order not found'));
			return $result;
		}

		$prevStatus = $order->getField('STATUS_ID');
		$result->setData(['PREVIOUS_STATUS_ID' => $prevStatus]);

		if($prevStatus !== $statusId)
		{
			$res = $order->setField('STATUS_ID', $statusId);
			if(!$res->isSuccess())
			{
				return $result->addErrors($res->getErrors());
			}
		}

		if (OrderStatus::getSemanticID($statusId) == Crm\PhaseSemantics::FAILURE)
		{
			if(strlen($reasonCanceled) > 0)
			{
				$res = $order->setField('REASON_CANCELED', $reasonCanceled);
				if(!$res->isSuccess())
				{
					return $result->addErrors($res->getErrors());
				}
			}
		}
			
		$res = $order->save();
		if(!$res->isSuccess())
		{
			$result->addErrors($res->getErrors());
		}

		return $result;
	}

	/**
	 * @param $id
	 * @return Payment|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getPaymentObject($id)
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return null;
		}

		$res = Payment::getList(array(
			'filter' => array('=ID' => $id)
		));

		$paymentData = $res->fetch();

		if(!$paymentData || intval($paymentData['ORDER_ID']) <= 0)
			return null;

		$order = Order::load($paymentData['ORDER_ID']);

		if(!$order)
			return null;

		$collection = $order->getPaymentCollection();
		/** @var Payment $payment */
		$payment = $collection->getItemById($id);
		return $payment;
	}

	/**
	 * @param $id
	 * @return Shipment | null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function getShipmentObject($id)
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return null;
		}

		$res = Shipment::getList(array(
			'filter' => array('=ID' => $id)
		));

		$shp = $res->fetch();

		if(!$shp || intval($shp['ORDER_ID']) <= 0)
			return null;

		$order = Order::load($shp['ORDER_ID']);

		if(!$order)
			return null;

		$collection = $order->getShipmentCollection();
		return $collection->getItemById($id);
	}

	/**
	 * @param $site
	 * @param null $userId
	 *
	 * @return \Bitrix\Sale\Order|null
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
	 * @throws Main\NotImplementedException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 */
	public static function createEmptyOrder($site, $userId = null)
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return null;
		}

		$order = Order::create($site, $userId);

		$order->setPersonTypeId(
			\Bitrix\Sale\Helpers\Admin\Blocks\OrderBuyer::getDefaultPersonType(
				$order->getSiteId()
			)
		);

		$basket = \Bitrix\Crm\Order\Basket::create($site);
		$order->setBasket($basket);

		$shipments = $order->getShipmentCollection();
		$shipments->createItem();

		$payments = $order->getPaymentCollection();
		$payments->createItem();

		return $order;
	}


	public static function getDeliveryServicesList(Shipment $shipment)
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return null;
		}

		static $result = null;

		if($result === null)
		{
			$result = array(
				array(
					'ID' => 0,
					'NAME' => '('.Loc::getMessage('CRM_ORDER_CHOOSE_DELIVERY').')',
				)
			);

			$deliveryList = \Bitrix\Sale\Delivery\Services\Manager::getRestrictedList(
				$shipment,
				Delivery\Restrictions\Manager::MODE_MANAGER,
				array(
					Delivery\Services\Manager::SKIP_CHILDREN_PARENT_CHECK,
					Delivery\Services\Manager::SKIP_PROFILE_PARENT_CHECK
				)
			);

			foreach ($deliveryList as $delivery)
			{
				if($delivery['CLASS_NAME'] == '\Bitrix\Sale\Delivery\Services\Group')
				{
					continue;
				}

				$service = Delivery\Services\Manager::getObjectById($delivery['ID']);

				if(!$service)
				{
					continue;
				}

				if($shipment && !$service->isCompatible($shipment))
				{
					continue;
				}

				$groupId = 0;

				if((int)$delivery['PARENT_ID'] > 0)
				{
					if($parent = Delivery\Services\Manager::getById($delivery['PARENT_ID']))
					{
						if($parent['CLASS_NAME'] != '\Bitrix\Sale\Delivery\Services\Group')
						{
							continue;
						}
						else
						{
							$groupId = (int)$delivery['PARENT_ID'];
						}
					}
				}

				if($service->canHasProfiles())
				{
					$profiles = $service->getProfilesList();

					if(empty($profiles))
					{
						continue;
					}
				}

				$item = [
					'ID' => $delivery['ID'],
					'NAME' => $delivery['NAME']
				];

				if($groupId > 0)
				{
					if(isset($result[$groupId]))
					{
						$result[$groupId]['ITEMS'][$delivery['ID']] = $item;
					}
					else
					{
						$result[$groupId] = [
							'NAME' => $parent['NAME'],
							'ITEMS' => [ $delivery['ID'] => $item ]
						];
					}
				}
				else
				{
					$result[$delivery['ID']] = $item;
				}
			}
		}

		return $result;
	}

	public static function getDeliveryProfiles($deliveryId, $deliveryList)
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return null;
		}

		if((int)$deliveryId <= 0)
		{
			return [];
		}

		$result = [];
		$parentId = 0;

		//is profile selected or not
		if(isset($deliveryList[$deliveryId]))
		{
			$parentId = $deliveryId;
		}

		if($parentId <= 0)
		{
			foreach($deliveryList as $delivery)
			{
				if(isset($delivery['ITEMS']))
				{
					foreach($delivery['ITEMS'] as $item)
					{
						if($item['ID'] == $deliveryId)
						{
							$parentId = $deliveryId;
							break 2;
						}
					}
				}
			}
		}

		if($parentId <= 0)
		{
			$delivery = Delivery\Services\Manager::getById($deliveryId);

			if(!$delivery)
			{
				return [];
			}

			$parentId = $delivery['PARENT_ID'];
		}

		$profiles = Delivery\Services\Manager::getByParentId($parentId);

		foreach($profiles as $id => $profile)
		{
			$result[$id] = [
				'ID' => $profile['ID'],
				'NAME' => $profile['NAME']
			];
		}

		return $result;
	}

	public static function getAnonymousUserID()
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return null;
		}

		return (int)\CSaleUser::GetAnonymousUserID();
	}

	/**
	 * @param $orderId
	 *
	 * @return Order
	 */
	public static function copy($orderId)
	{
		if (!Main\Loader::includeModule('sale'))
		{
			return null;
		}

		$originalOrder = Order::load((int)$orderId);
		if (!$originalOrder)
		{
			return null;
		}
		/** @var Order $order */
		$order = Order::create($originalOrder->getSiteId(), $originalOrder->getUserId(), $originalOrder->getCurrency());
		$order->setPersonTypeId($originalOrder->getPersonTypeId());
		$originalPropCollection = $originalOrder->getPropertyCollection();
		$properties['PROPERTIES'] = array();
		$files = array();

		/** @var \Bitrix\Sale\PropertyValue $prop */
		foreach ($originalPropCollection as $prop)
		{
			if ($prop->getField('TYPE') == 'FILE')
			{
				$propValue = $prop->getValue();
				if ($propValue)
				{
					$files[] = \CAllFile::MakeFileArray($propValue['ID']);
					$properties['PROPERTIES'][$prop->getPropertyId()] = $propValue['ID'];
				}
			}
			else
			{
				$properties['PROPERTIES'][$prop->getPropertyId()] = $prop->getValue();
			}
		}

		$contactCompanyCollection = $order->getContactCompanyCollection();
		$originalCollection = $originalOrder->getContactCompanyCollection();
		foreach ($originalCollection as $item)
		{
			$fields = $item->getFieldValues();
			unset($fields['ID'], $fields['ORDER_ID'], $fields['ENTITY_TYPE_ID']);
			if ($item instanceof Company)
			{
				$entity = $contactCompanyCollection->createCompany();
			}
			else
			{
				$entity = $contactCompanyCollection->createContact();
			}

			$entity->setFields($fields);
		}

		$propCollection = $order->getPropertyCollection();
		$propCollection->setValuesFromPost($properties, $files);
		$originalBasket = $originalOrder->getBasket();
		$originalBasketItems = $originalBasket->getBasketItems();
		$basket = \Bitrix\Crm\Order\Basket::create($originalOrder->getSiteId());
		$basket->setFUserId($originalBasket->getFUserId());

		/** @var \Bitrix\Sale\BasketItem $originalBasketItem */
		foreach($originalBasketItems as $originalBasketItem)
		{
			$item = $basket->createItem($originalBasketItem->getField("MODULE"), $originalBasketItem->getProductId());
			$item->setField('NAME', $originalBasketItem->getField('NAME'));

			$item->setFields(
				array_intersect_key(
					$originalBasketItem->getFields()->getValues(),
					array_flip(
						$originalBasketItem->getAvailableFields()
					)
				)
			);

			$item->setField('XML_ID', '');

			$item->getPropertyCollection()->setProperty(
				$originalBasketItem->getPropertyCollection()->getPropertyValues()
			);
		}

		$order->setBasket($basket);

		$payment = null;
		$paymentCollection = $originalOrder->getPaymentCollection();
		$originalPayment = $paymentCollection->current();

		if ($originalPayment)
		{
			$payment = $order->getPaymentCollection()->createItem();
			/** @var \Bitrix\Sale\Payment $payment */
			$payment->setField('PAY_SYSTEM_ID', $originalPayment->getPaymentSystemId());
			$payment->setField('CURRENCY', $originalPayment->getField("CURRENCY"));
		}

		$originalShipmentCollection = $originalOrder->getShipmentCollection();
		/** @var \Bitrix\Sale\Shipment $shipment */
		foreach ($originalShipmentCollection as $originalShipment)
		{
			if (!$originalShipment->isSystem())
			{
				$customPriceDelivery = $originalShipment->getField('CUSTOM_PRICE_DELIVERY');
				$basePrice = $originalShipment->getField('BASE_PRICE_DELIVERY');
				$originalStoreId = $originalShipment->getStoreId();

				$shipment = $order->getShipmentCollection()->createItem();
				$shipment->setField('DELIVERY_ID', $originalShipment->getDeliveryId());
				$shipment->setField('CURRENCY', $originalShipment->getField("CURRENCY"));

				if(intval($originalStoreId) > 0)
				{
					$shipment->setStoreId($originalStoreId);
				}

				$shipment->setBasePriceDelivery($basePrice, ($customPriceDelivery == 'Y'));

				break;
			}
		}

		/** @var Result $r */
		$r = $order->getDiscount()->calculate();
		if ($r->isSuccess())
		{
			$discountData = $r->getData();
			$order->applyDiscount($discountData);
		}

		if (!empty($payment))
		{
			$payment->setField('SUM', $order->getPrice());
		}
		return $order;
	}

	public static function getUfId()
	{
		return 'ORDER';
	}

	public static function installDeliveryServices()
	{
		return '';
	}
}