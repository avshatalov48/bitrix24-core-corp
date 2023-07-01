<?php


namespace Bitrix\Sale\Compatible;

use Bitrix\Main;
use Bitrix\Sale;
use Bitrix\Sale\Compatible\Internals;
use Bitrix\Sale\Internals\BasketTable;

Main\Localization\Loc::loadMessages(__FILE__);

class BasketCompatibility extends Internals\EntityCompatibility
{

	private static $proxyBasket = array();

	/** @var null|OrderCompatibility */
	protected $orderCompatibility = null;

	/**
	 * @return string
	 */
	protected static function getRegistryType()
	{
		return Sale\Registry::REGISTRY_TYPE_ORDER;
	}

	/**
	 * @return Main\Entity\Base
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
	 */
	protected static function getEntity()
	{
		return BasketTable::getEntity();
	}

	/**
	 * @return string
	 */
	protected static function getOrderCompatibilityClassName()
	{
		return OrderCompatibility::class;
	}

	/**
	 * @param array $fields - field basket
	 */
	protected function __construct(array $fields = array())
	{
		/** @var OrderQuery query */
		$this->query = new OrderQuery(static::getEntity());
		$this->fields = new Sale\Internals\Fields($fields);
	}

	/**
	 * @param OrderCompatibility $orderCompatibility
	 *
	 * @return BasketCompatibility
	 */
	public static function create(OrderCompatibility $orderCompatibility)
	{
		/** @var BasketCompatibility $basketCompatibility */
		$basketCompatibility = new static();

		$basketCompatibility->orderCompatibility = $orderCompatibility;

		return $basketCompatibility;
	}

	/**
	 * @internal
	 *
	 * @param Sale\Basket $basket
	 * @param array $requestFields
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 */
	public function fillBasket(Sale\Basket $basket, array $requestFields)
	{
		$orderCompatibility = $this->getOrderCompatibility();

		/** @var Sale\Order $order */
		$order = $orderCompatibility->getOrder();

		$result = new Sale\Result();

		if (empty($requestFields['BASKET_ITEMS']))
			return $result;

		$isStartField = $order->isStartField();

		$basketCodeList = array();

		$r = $this->parseBasketItems($basket, $requestFields['BASKET_ITEMS']);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		$resultData = $r->getData();

		if (isset($resultData['BASKET']))
		{
			$basket = $resultData['BASKET'];
		}

		if (isset($resultData['BASKET_CODE_LIST']))
		{
			$basketCodeList = $resultData['BASKET_CODE_LIST'];
		}

		if (isset($resultData['BASKET_CHANGED']) && $resultData['BASKET_CHANGED'] === true)
		{
			$order->refreshVat();
		}

		if (!empty($basketCodeList) && is_array($basketCodeList))
		{
			foreach ($basketCodeList as $index => $basketCode)
			{
				DiscountCompatibility::setBasketCode($index, $basketCode);
			}
		}

		if ($isStartField)
		{
			/** @var Sale\Result $r */
			$r = $order->doFinalAction(true);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
			else
			{
				if (($data = $r->getData())
					&& !empty($data) && is_array($data))
				{
					$result->setData($result->getData() + $data);
				}
			}
		}

		return $result;
	}

	/**
	 * @param Sale\Basket $basket
	 * @param array $requestBasketItems
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 */
	protected function parseBasketItems(Sale\Basket $basket, array $requestBasketItems)
	{
		$result = new Sale\Result();

		$basketCodeList = array();
		$basketItemList = array();
		$basketParentList = array();
		$basketChildList = array();

		$basketChanged = false;

		$registry = Sale\Registry::getInstance(static::getRegistryType());
		$basketItemEntity = $registry->getBasketItemClassName();

		$publicMode = DiscountCompatibility::usedByClient();
		foreach ($requestBasketItems as $basketIndex => $basketItemData)
		{
			if (isset($basketItemData['SET_PARENT_ID']) && strval($basketItemData['SET_PARENT_ID']) != '')
			{
				$parentId = intval($basketItemData['SET_PARENT_ID']);
				if ($basketItemData['TYPE'] != $basketItemEntity::TYPE_SET && !array_key_exists($parentId, $basketParentList))
				{
					$basketChildList[intval($basketItemData['SET_PARENT_ID'])] = $basketItemData['SET_PARENT_ID'];
				}
			}
		}

		$orderCompatibility = $this->getOrderCompatibility();

		/** @var Sale\Order $order */
		$order = $orderCompatibility->getOrder();
		$basketItemsIndexList = array();

		foreach ($basket as $basketItem)
		{
			$basketItemsIndexList[$basketItem->getId()] = true;
		}

		$sort = 100;
		foreach ($requestBasketItems as $basketIndex => $basketItemData)
		{
			if (!isset($basketItemData['BASE_PRICE']) && isset($basketItemData['PRICE']))
			{
				$basketItemData['BASE_PRICE'] = $basketItemData['PRICE'];
			}

			$basketItem = null;
			if (isset($basketItemData['ID']) && intval($basketItemData['ID']) > 0)
			{
				/** @var Sale\BasketItem $basketItem */
				if ($basketItem = $basket->getItemById($basketItemData['ID']))
				{
					if (isset($basketItemsIndexList[$basketItem->getId()]))
						unset($basketItemsIndexList[$basketItem->getId()]);
				}
			}


			if (!$basketItem)
			{
				/** @var Sale\BasketItem $basketItem */
				$basketItem = $basketItemEntity::create($basket, $basketItemData['MODULE'], $basketItemData['PRODUCT_ID']);
				$basketChanged = true;
			}

			$itemDuplicate = (isset($basketItemData['DUPLICATE']) && $basketItemData['DUPLICATE'] == "Y");

			$basketItemData['SORT'] = $sort;
			$sort += 100;

			$basketFields = static::clearFields($basketItemData);

			if ($order->getId() > 0)
			{
				/** @var Sale\ShipmentCollection $shipmentCollection */
				if ($shipmentCollection = $order->getShipmentCollection())
				{
					if (count($shipmentCollection) == 2
						&& (isset($basketItemData['QUANTITY']) && floatval($basketItemData['QUANTITY']) <= $basketItem->getQuantity()))
					{

						/** @var Sale\Shipment $shipment */
						foreach ($shipmentCollection as $shipment)
						{
							if ($shipment->isSystem())
								continue;


							$basketQuantity = $shipment->getBasketItemQuantity($basketItem);
							if ($basketQuantity <= floatval($basketItemData['QUANTITY']))
								continue;


							/** @var Sale\ShipmentItemCollection $shipmentItemCollection */
							if ($shipmentItemCollection = $shipment->getShipmentItemCollection())
							{
								/** @var Sale\ShipmentItem $shipmentItem */
								if (!$shipmentItem = $shipmentItemCollection->getItemByBasketCode($basketItem->getBasketCode()))
									continue;

								$shipmentItem->setQuantity(floatval($basketItemData['QUANTITY']));
							}
						}
					}
				}
			}


			$isBasketItemCanBeAdded = true;
			if ($publicMode)
			{
				if (\CSaleBasketHelper::isSetParent($basketItemData))
				{
					$parentId = (int)$basketItemData['ID'];
					$parentCode = $basketItemData['ID'];
				}
				else
				{
					$parentId = (int)$basketItemData['SET_PARENT_ID'];
					$parentCode = $basketItemData['SET_PARENT_ID'];
				}
			}
			else
			{
				$parentId = (int)$basketItemData['SET_PARENT_ID'];
				$parentCode = $basketItemData['SET_PARENT_ID'];
			}

			if ($parentId > 0)
			{
				if ($basketItem->isBundleParent())
				{
					$basketParentList[$parentCode] = $basketItem->getBasketCode();
				}
				else
				{
					$isBasketItemCanBeAdded = false;
					$basketItemList[$parentCode][$basketIndex] = $basketItem;
				}
			}

			if ($isBasketItemCanBeAdded)
			{
				$propList = array();
				/** @var Sale\BasketPropertiesCollection $propertyCollection */
				if ($propertyCollection = $basketItem->getPropertyCollection())
				{
					$propList = $propertyCollection->getPropertyValues();
				}

				/** @var null|Sale\BasketItem $foundedBasketItem */
				$foundedBasketItem = null;

				if ($basketItem->getId() > 0 && ($foundedBasketItem = $basket->getItemById($basketItem->getId())))
				{
					$basketCodeList[($publicMode ? $foundedBasketItem->getId() : $basketIndex)] = $foundedBasketItem->getBasketCode();
				}
				else
				{
					if (!$itemDuplicate && ($foundedBasketItem = $basket->getExistsItem($basketItem->getField('MODULE'), $basketItem->getProductId(), $propList)))
					{
						$basketCodeList[($publicMode ? $foundedBasketItem->getId() : $basketIndex)] = $foundedBasketItem->getBasketCode();
					}
				}

				if ($foundedBasketItem === null)
				{
					$basket->addItem($basketItem);
					$basketCodeList[($publicMode ? $basketItem->getId() : $basketIndex)] = $basketItem->getBasketCode();

					$basketChanged = true;
				}
			}


			/** @var Sale\Result $r */
			$r = $basketItem->setFields($basketFields);
			if ($r->isSuccess())
			{
				if (array_key_exists('PROPS', $basketItemData) && is_array($basketItemData))
				{
					/** @var Sale\BasketPropertiesCollection $propertyCollection */
					if ($propertyCollection = $basketItem->getPropertyCollection())
					{
						$propertyCollection->setProperty($basketItemData['PROPS']);
					}
				}
			}
			else
			{
				$result->addErrors($r->getErrors());
			}

		}

		if (!empty($basketChildList))
		{
			foreach($basketItemList as $parentBasketCode => $childBasketItemList)
			{
				$parentCode = null;
				if (!empty($basketParentList[$parentBasketCode]))
					$parentCode = $basketParentList[$parentBasketCode];

				if (strval($parentCode) == '')
					continue;

				/** @var Sale\BasketItem $parentBasketItem */
				if (!$parentBasketItem = $basket->getItemByBasketCode($parentCode))
				{
					throw new Main\ObjectNotFoundException('Entity parent "BasketItem" not found');
				}

				if (!empty($childBasketItemList) && is_array($childBasketItemList))
				{
					/** @var Sale\BasketItem $childBasketItem */
					foreach ($childBasketItemList as $indexChildBasketItem => $childBasketItem)
					{
						$basketCodeIndex = ($publicMode ? $childBasketItem->getId() : $indexChildBasketItem);
						$childBasketCode = $childBasketItem->getBasketCode();

						$propList = array();
						/** @var Sale\BasketPropertiesCollection $propertyCollection */
						if ($propertyCollection = $childBasketItem->getPropertyCollection())
						{
							$propList = $propertyCollection->getPropertyValues();
						}

						$bundleCollection = $parentBasketItem->getBundleCollection();
						/** @var Sale\BasketItem $foundedBasketItem */
						if ($foundedBasketItem = $bundleCollection->getExistsItem($childBasketItem->getField('MODULE'), $childBasketItem->getProductId(), $propList))
						{
							$childBasketCode = $foundedBasketItem->getBasketCode();
							unset($childBasketItemList[$indexChildBasketItem]);
							$basketCodeIndex = ($publicMode ? $foundedBasketItem->getId() : $indexChildBasketItem);
						}

						if (strval($childBasketCode) != '')
							$basketCodeList[$basketCodeIndex] = $childBasketCode;
					}

					if (!empty($childBasketItemList))
					{
						$this->setChildBundleCollection($basket, $childBasketItemList, $parentBasketItem);
					}
				}
			}
		}

		if (!empty($basketItemsIndexList) && is_array($basketItemsIndexList))
		{
			foreach ($basketItemsIndexList as $basketIndexId => $basketIndexValue)
			{
				if ($foundedBasketItem = $basket->getItemById($basketIndexId))
				{
					$foundedBasketItem->delete();
					$basketChanged = true;
				}
			}
		}

		$result->setData(array(
			'BASKET' => $basket,
			'BASKET_CODE_LIST' => $basketCodeList,
			'BASKET_CHANGED' => $basketChanged,
		));

		return $result;
	}

	/**
	 * @param Sale\Basket $basket
	 * @param array $basketItemList
	 * @param Sale\BasketItem|null $externalParentBasketItem
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 */
	private function setChildBundleCollection(Sale\Basket $basket, array $basketItemList, Sale\BasketItem $externalParentBasketItem = null)
	{
		$order = null;

		$isExternalBasketParent = false;

		if ($externalParentBasketItem !== null)
		{
			if (!$externalParentBasketItem->isBundleParent())
			{
				throw new Main\ObjectException('basketItem not parent');
			}
			$isExternalBasketParent = true;
		}

		/** @var Sale\BasketItem $item */
		foreach ($basketItemList as $item)
		{
			if ($item->isBundleChild() || (!$item->isBundleParent() && $isExternalBasketParent && $externalParentBasketItem !== null))
			{
				/** @var Sale\BasketItem $parentBasketItem */
				$parentBasketItem = $item->getParentBasketItem();

				if (!$parentBasketItem && $externalParentBasketItem !== null)
				{
					$parentBasketItem = $externalParentBasketItem;
				}

				if ($parentBasketItem)
				{
					/** @var Sale\Basket $bundleCollection */
					$bundleCollection = $parentBasketItem->createBundleCollection();

					if ($basket->getItemByBasketCode($parentBasketItem->getBasketCode()))
					{
						$bundleCollection->addItem($item);
					}
					else
					{
						$basket->addItem($item);
					}

					if ($order === null)
					{
						/** @var Sale\Basket $basket */
						if (!$basket = $parentBasketItem->getCollection())
						{
							throw new Main\ObjectNotFoundException('Entity "Basket" not found');
						}

						/** @var Sale\Order $order */
						$order = $basket->getOrder();
					}

					if ($bundleCollection->getOrder() === null && $order instanceof Sale\OrderBase)
						$bundleCollection->setOrder($order);
				}
			}
		}
	}

	/**
	 * Add the position of the basket
	 *
	 * @param array $fields - an array of fields with data element baskets
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectNotFoundException
	 */
	public static function add(array $fields)
	{
		$result = new Sale\Result();

		$order = null;
		$basket = null;
		$item = null;

		$registry = Sale\Registry::getInstance(static::getRegistryType());

		if (!array_key_exists('FUSER_ID', $fields) || intval($fields['FUSER_ID']) <= 0)
		{
			$fields['FUSER_ID'] = Sale\Fuser::getId(false);
		}

		if (!empty($fields['ORDER_ID']) && intval($fields['ORDER_ID']) > 0)
		{
			/** @var Sale\Order $orderClassName */
			$orderClassName = $registry->getOrderClassName();
			$order = $orderClassName::load(intval($fields['ORDER_ID']));

			if ($order)
			{
				/** @var Sale\Basket $basket */
				$basket = $order->getBasket();
			}
		}

		if (!$basket)
		{
			/** @var Sale\Basket $orderClassName */
			$basketClassName = $registry->getBasketClassName();
			$basket = $basketClassName::loadItemsForFUser($fields["FUSER_ID"], $fields['LID']);
		}


		/** @var \Bitrix\Sale\BasketItem|bool $item */
		if ($item = $basket->getExistsItem($fields["MODULE"], $fields["PRODUCT_ID"], ((!empty($fields["PROPS"]) && is_array($fields["PROPS"]))? $fields["PROPS"] : array())))
		{
			$item->setField('QUANTITY', $item->getQuantity() + $fields['QUANTITY']);

			unset($fields['QUANTITY']);

			/** @var Sale\Result $r */
			$r = $item->setFields(static::clearFields($fields));
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}
			else
			{
				if (!$order)
					$basket->refreshData(array('PRICE', 'COUPONS', 'QUANTITY'), $item);
			}
		}

		if ($item === null)
		{
			/** @var \Bitrix\Sale\BasketItem $item */
			$item = $basket->createItem($fields["MODULE"], $fields["PRODUCT_ID"]);

			if (isset($fields["PROPS"]) && is_array($fields["PROPS"]))
			{
				/** @var Sale\BasketPropertiesCollection $property */
				$property = $item->getPropertyCollection();
				$property->setProperty($fields["PROPS"]);
			}

			/** @var Sale\Result $r */
			$r = $item->setFields(static::clearFields($fields));
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}

			if ($order)
			{
				$shipmentCollection = $order->getShipmentCollection();
				$systemShipment = $shipmentCollection->getSystemShipment();
				$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();
				$systemShipmentItemCollection->resetCollection($basket);

				if ($systemShipment->getDeliveryId() > 0)
				{
					/** @var OrderCompatibility $orderCompatibilityClassName */
					$orderCompatibilityClassName = static::getOrderCompatibilityClassName();
					/** @var Sale\Shipment $shipment */
					$shipment = $orderCompatibilityClassName::getShipmentByDeliveryId($shipmentCollection, $systemShipment->getDeliveryId());

					if (!$shipment)
					{
						if ($service = Sale\Delivery\Services\Manager::getObjectById($systemShipment->getDeliveryId()))
						{
							/** @var Sale\Shipment $shipment */
							$shipment = $shipmentCollection->createItem($service);
						}
					}


					if ($shipment)
					{
						$shipmentItemCollection = $shipment->getShipmentItemCollection();

						/** @var Sale\ShipmentItem $shipmentItem */
						$shipmentItem = $shipmentItemCollection->createItem($item);
						if ($shipmentItem)
							$shipmentItem->setQuantity($item->getQuantity());
					}
				}

				/** @var Sale\Result $r */
				$r = $order->refreshData(array('PRICE', 'PRICE_DELIVERY'));
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
					return $result;
				}

			}
		}

		if ($order !== null)
		{
			/** @var Sale\Result $r */
			$r = $order->save();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}
		else
		{
			$isNew = false;
			if ($item->getId() == 0)
			{
				$isNew = true;
			}

			$r = $basket->save();
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		if ($r->isSuccess())
		{
			if (!DiscountCompatibility::isInited())
				DiscountCompatibility::init();
			if (DiscountCompatibility::usedByClient())
			{
				$id = $item->getId();
				DiscountCompatibility::setBasketItemData($id, $fields);
				DiscountCompatibility::setBasketCode($id, $item->getBasketCode());
			}

			$result->setId($item->getId());
			$result->addData(array(
				'QUANTITY' => $item->getQuantity()
			));
		}


		return $result;
	}

	/**
	 * Update data element baskets
	 *
	 * @param $id
	 * @param array $fields
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectNotFoundException
	 */
	public static function update($id, array $fields)
	{
		$result = new Sale\Result();

		$item = null;
		$basket = null;
		/** @var Sale\Order $order */
		$order = null;
		$orderId = null;

		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var Sale\Result $itemResult */
		$itemResult = static::loadEntityFromBasket($id);
		if ($itemResult->isSuccess())
		{
			$itemResultData = $itemResult->getData();
			if (isset($itemResultData['BASKET_ITEM']))
			{
				/** @var Sale\BasketItem $item */
				$item = $itemResultData['BASKET_ITEM'];
				$basket = $item->getBasket();
			}

			if (isset($itemResultData['ORDER']))
			{
				$order = $itemResultData['ORDER'];
			}
		}

		if (!$item)
		{
			$result->addError(new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_BASKET_COMPATIBLE_BASKET_ITEM_NOT_FOUND'), 'BASKET_ITEM_NOT_FOUND'));
			return $result;

		}

		if ($order !== null &&
			$fields['ORDER_ID'] > 0 &&
			(int)$fields['ORDER_ID'] !== (int)$order->getId()
		)
		{
			$result->addError(
				new Sale\ResultError(
					Main\Localization\Loc::getMessage('SALE_BASKET_COMPATIBLE_BASKET_ITEM_ERROR_BIND_TO_ORDER'),
					'BASKET_ITEM_NOT_FOUND'
				)
			);

			return $result;
		}

		if (isset($fields["PROPS"]) && is_array($fields["PROPS"]))
		{
			/** @var Sale\BasketPropertiesCollection $property */
			$property = $item->getPropertyCollection();
			$property->clearCollection();
			$property->setProperty($fields["PROPS"]);
		}

		if ($order !== null && isset($fields['PRICE']))
		{
			if ($fields['PRICE'] != $item->getPrice())
				$fields['CUSTOM_PRICE'] = 'Y';
		}

		if (!empty($fields['FUSER_ID']))
		{
			$item->setFieldNoDemand('FUSER_ID', intval($fields['FUSER_ID']));
			unset($fields['FUSER_ID']);
		}

		$r = $item->setFields(static::clearFields($fields));
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		if(DiscountCompatibility::isUsed())
		{
			if (!DiscountCompatibility::isInited())
				DiscountCompatibility::init();
			if (DiscountCompatibility::usedByClient())
			{
				DiscountCompatibility::setBasketItemData($id, $fields);
				DiscountCompatibility::setBasketCode($id, $item->getBasketCode());
			}
		}


		if ($order === null && !empty($fields['ORDER_ID']) && intval($fields['ORDER_ID']) > 0)
		{
			$orderId = intval($fields['ORDER_ID']);

			$orderClassName = $registry->getOrderClassName();
			if ($order = $orderClassName::load($orderId))
			{
				/** @var Sale\Basket $basket */
				if ($basket = $order->getBasket())
				{
					$basket->addItem($item);

					/** @var Sale\ShipmentCollection $shipmentCollection */
					if (!$shipmentCollection = $order->getShipmentCollection())
					{
						throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
					}

					/** @var OrderCompatibility $orderCompatibilityClassName */
					$orderCompatibilityClassName = static::getOrderCompatibilityClassName();
					$orderCompatibilityClassName::createShipmentFromShipmentSystem($shipmentCollection);

					/** @var Sale\Result $r */
					$r = static::syncShipmentCollectionAndBasket($shipmentCollection, $basket);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
						return $result;
					}

					$r = $order->refreshData(array("PRICE_DELIVERY"));
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
						return $result;
					}

					$r = $order->doFinalAction(true);
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
						return $result;
					}
				}

			}
		}

		if ($order !== null)
		{
			/** @var Sale\ShipmentCollection $shipmentCollection */
			if ($shipmentCollection = $order->getShipmentCollection())
			{
				if (count($shipmentCollection) == 2 && $shipmentCollection->isExistsSystemShipment())
				{
					/** @var Sale\Shipment $shipment */
					foreach ($shipmentCollection as $shipment)
					{
						if ($shipment->isSystem() || $shipment->isShipped())
							continue;

						/** @var Sale\Shipment $systemShipment */
						$systemShipment = $shipmentCollection->getSystemShipment();

						/** @var Sale\ShipmentItemCollection $systemShipmentItemCollection */
						$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();

						/** @var Sale\ShipmentItem $systemShipmentItem */
						if (!$systemShipmentItem = $systemShipmentItemCollection->getItemByBasketCode($item->getBasketCode()))
							continue;

						/** @var Sale\ShipmentItemCollection $shipmentItemCollection */
						if (!$shipmentItemCollection = $shipment->getShipmentItemCollection())
							continue;


						/** @var Sale\ShipmentItem $shipmentItem */
						if (!$shipmentItem = $shipmentItemCollection->getItemByBasketCode($item->getBasketCode()))
							continue;


						if ($systemShipmentItem->getQuantity() > 0)
						{
							$r = $shipmentItem->setQuantity(($shipmentItem->getQuantity() + $systemShipmentItem->getQuantity()));
							if (!$r->isSuccess())
							{
								$result->addErrors($r->getErrors());
							}
						}
					}
				}
			}

			if (!$result->isSuccess())
			{
				return $result;
			}

			$r = $order->save();
		}
		else
		{
			if (!$result->isSuccess())
			{
				return $result;
			}

			$r = $basket->save();
		}

		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param $id
	 * @return Sale\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public static function delete($id)
	{
		$result = new Sale\Result();

		$item = null;
		$basket = null;
		$order = null;

		$registry = Sale\Registry::getInstance(static::getRegistryType());
		/** @var Sale\Basket $basketClassName */
		$basketClassName = $registry->getBasketClassName();
		$res = $basketClassName::getList(
			array(
				'filter' => array(
					'ID' => $id
				),
				'select' => array(
					'ID', 'ORDER_ID', 'SET_PARENT_ID', 'TYPE', 'FUSER_ID', 'LID'
				),
			));
		if (!$itemDat = $res->fetch())
		{
			$result->addError(new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_BASKET_COMPATIBLE_BASKET_ITEM_NOT_FOUND'), 'BASKET_ITEM_NOT_FOUND'));
			return $result;
		}

		if (intval($itemDat['ORDER_ID']) > 0)
		{
			/** @var Sale\Basket $basketClassName */
			$orderClassName = $registry->getOrderClassName();
			/** @var Sale\Order $order */
			if ($order = $orderClassName::load(intval($itemDat['ORDER_ID'])))
			{
				if ($basket = $order->getBasket())
				{
					/** @var Sale\BasketItem $item */
					$item = $basket->getItemById($id);
				}
			}
		}
		else
		{
			if (!array_key_exists('FUSER_ID', $itemDat) || intval($itemDat['FUSER_ID']) <= 0)
			{
				$itemDat['FUSER_ID'] = Sale\Fuser::getId();
			}

			/** @var Sale\Basket $basketClassName */
			$basketClassName = $registry->getBasketClassName();
			if ($basket = $basketClassName::loadItemsForFUser($itemDat["FUSER_ID"], $itemDat['LID']))
			{
				/** @var Sale\BasketItem $item */
				$item = $basket->getItemById($id);
			}
		}

		if ($basket === null)
		{
			$result->addError(new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_BASKET_COMPATIBLE_BASKET_COLLECTION_NOT_FOUND'), 'BASKET_COLLECTION_NOT_FOUND'));
			return $result;
		}


		if ($item === null)
		{
			$result->addError(new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_BASKET_COMPATIBLE_BASKET_ITEM_PROPS_NOT_FOUND'), 'BASKET_ITEM_PROPS_NOT_FOUND'));
			return $result;

		}

		/** @var Sale\Result $r */
		$r = $item->delete();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		if ($order !== null)
		{
			if (!$result->isSuccess())
			{
				return $result;
			}

			/** @var Sale\Result $r */
			$r = $order->save();
		}
		else
		{
			if (!$result->isSuccess())
			{
				return $result;
			}

			/** @var Sale\Result $r */
			$r = $basket->save();

			if ($r->isSuccess())
			{
				Sale\BasketComponentHelper::clearFUserBasketQuantity($itemDat['FUSER_ID'], $itemDat['LID']);
				Sale\BasketComponentHelper::clearFUserBasketPrice($itemDat['FUSER_ID'], $itemDat['LID']);
			}
		}

		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function getAliasFields()
	{
		return array(
			'ORDER_STATUS' => 'ORDER.STATUS_ID',
			'ORDER_CANCELED' => 'ORDER.CANCELED',
			'ORDER_PRICE' => 'ORDER.PRICE',
			'ORDER_DATE' => 'ORDER.DATE_INSERT',

			'USER_ID' => 'USER.ID',

			'SUM_PRICE' => 'SUMMARY_PRICE',

			'ORDER_ALLOW_DELIVERY' => 'SHIPMENT.ALLOW_DELIVERY',
			'ORDER_DATE_ALLOW_DELIVERY' => 'SHIPMENT.DATE_ALLOW_DELIVERY',
			'DEDUCTED' => 'SHIPMENT.DEDUCTED',
			'SHIPMENT_SYSTEM' => 'SHIPMENT.SYSTEM',

			'ORDER_PAYED' => 'PAYMENT.PAID',
			'ORDER_DATE_PAYED' => 'PAYMENT.DATE_PAID',


		);
	}

	/**
	 * @return array
	 */
	protected static function getSelectFields()
	{
		return array_keys(static::getEntity()->getScalarFields());
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function getAvailableFields()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());
		$basketItemClassName = $registry->getBasketItemClassName();
		$fields = $basketItemClassName::getAvailableFields();

		if ($index = array_search('SET_PARENT_ID', $fields))
			unset($fields[$index]);

		return $fields;
	}

	/**
	 * @param $id
	 * @return Sale\BasketItem|Sale\Result|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected static function loadEntityFromBasket($id)
	{
		$result = new Sale\Result();

		$order = null;
		$basket = null;
		$item = null;

		$registry = Sale\Registry::getInstance(static::getRegistryType());
		$basketClassName = $registry->getBasketClassName();
		$res = $basketClassName::getList(array(
				'filter' => array(
					'ID' => $id
				),
				'select' => array(
					'ID', 'ORDER_ID', 'SET_PARENT_ID', 'TYPE', 'FUSER_ID', 'LID'
				),
		));
		if (!$itemDat = $res->fetch())
		{
			$result->addError(new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_BASKET_COMPATIBLE_BASKET_ITEM_NOT_FOUND'), 'BASKET_ITEM_NOT_FOUND'));
			return $result;
		}

		if (intval($itemDat['ORDER_ID']) > 0)
		{
			$orderClassName = $registry->getOrderClassName();
			/** @var Sale\Order $order */
			if ($order = $orderClassName::load(intval($itemDat['ORDER_ID'])))
			{
				if ($basket = $order->getBasket())
				{
					/** @var Sale\BasketItem $item */
					$item = $basket->getItemById($id);
				}
			}
		}
		else
		{
			$basketClassName = $registry->getBasketClassName();
			/** @var Sale\Basket $basket */
			$basket = $basketClassName::loadItemsForFUser($itemDat["FUSER_ID"], $itemDat['LID']);

			if ($basket)
			{
				/** @var Sale\BasketItem $item */
				$item = $basket->getItemById($id);
			}

		}

		$data = array(
			'BASKET_ITEM' => $item
		);

		if ($order !== null)
		{
			$data['ORDER'] = $order;
		}

		$result->setData($data);

		return $result;
	}

	/**
	 * @param Sale\Order $order
	 * @param Sale\Basket $basket
	 *
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\NotSupportedException
	 */
	protected static function appendBasketToOrder(Sale\Order $order, Sale\Basket $basket)
	{
		$result = new Sale\Result();

		$orderBasketCollection = $order->getBasket();

		$shipmentCollection = $order->getShipmentCollection();
		$systemShipment = $shipmentCollection->getSystemShipment();
		$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();

		/** @var Sale\BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			$basketProperty = array();
			if ($basketPropertyCollection = $basketItem->getPropertyCollection())
			{
				$basketProperty = $basketPropertyCollection->getPropertyValues();
			}

			if ($orderBasketItem = $orderBasketCollection->getExistsItem($basketItem->getField('MODULE'), $basketItem->getField('PRODUCT_ID'), $basketProperty))
			{
				$fields = $basketItem->getFieldValues();
				$orderBasketItem->setFields(static::clearFields($fields));
			}
			else
			{
				/** @var Sale\BasketItem $orderBasketItem */
				$orderBasketCollection->addItem($basketItem);
				$basketItem->setCollection($orderBasketCollection);


				$systemShipmentItemCollection->resetCollection($orderBasketCollection);
			}
		}

		/** @var Sale\BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			if ($systemShipment->getDeliveryId() > 0)
			{
				/** @var OrderCompatibility $orderCompatibilityClassName */
				$orderCompatibilityClassName = static::getOrderCompatibilityClassName();

				/** @var Sale\Shipment $shipment */
				$shipment = $orderCompatibilityClassName::getShipmentByDeliveryId($shipmentCollection, $systemShipment->getDeliveryId());

				if (!$shipment)
				{
					if ($service = Sale\Delivery\Services\Manager::getObjectById($systemShipment->getDeliveryId()))
					{
						/** @var Sale\Shipment $shipment */
						$shipment = $shipmentCollection->createItem($service);
					}
				}


				if ($shipment)
				{
					$shipmentItemCollection = $shipment->getShipmentItemCollection();

					if (!$shipmentItem = $shipmentItemCollection->getItemByBasketCode($basketItem->getBasketCode()))
					{
						$shipmentItem = $shipmentItemCollection->createItem($basketItem);
					}

					/** @var Sale\Result $r */
					$r = $shipmentItem->setQuantity($basketItem->getQuantity());
					if (!$r->isSuccess())
					{
						$result->addErrors($r->getErrors());
					}
				}
			}
		}

		return $result;

	}

	/**
	 * @param array $list
	 * @return Sale\Result
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public static function setBasketFields(array $list)
	{
		$result = new Sale\Result();

		$order = null;
		$basket = null;
		$basketItem = null;
		$orderId = null;

		foreach ($list as $basketId => $fields)
		{
			$basketItemResult = static::loadEntityFromBasket($basketId);
			if ($basketItemResult->isSuccess())
			{
				$basketItemResultList = $basketItemResult->getData();
				if (isset($basketItemResultList['BASKET']))
				{
					/** @var Sale\Basket $basket */
					$basket = $basketItemResultList['BASKET'];
				}

				if (isset($basketItemResultList['BASKET_ITEM']))
				{
					/** @var Sale\BasketItem $basketItem */
					$basketItem = $basketItemResultList['BASKET_ITEM'];
				}

				if (isset($basketItemResultList['ORDER']))
				{
					/** @var Sale\Order $order */
					$order = $basketItemResultList['ORDER'];
				}
			}

			if ($basketItem === null)
			{
				$result->addError(new Sale\ResultError(Main\Localization\Loc::getMessage('SALE_BASKET_COMPATIBLE_BASKET_ITEM_NOT_FOUND'), 'BASKET_ITEM_NOT_FOUND'));
				return $result;
			}

			if ($orderId === null && isset($fields['ORDER_ID']) && intval($fields['ORDER_ID']) > 0)
			{
				$orderId = (int)$fields['ORDER_ID'];
			}

			if (isset($fields['ORDER_ID']))
				unset($fields['ORDER_ID']);

			$basketItem->setFields($fields);

			if ($order === null && intval($orderId) > 0)
			{
				$registry = Sale\Registry::getInstance(static::getRegistryType());
				$orderClassName = $registry->getOrderClassName();
				/** @var Sale\Order $order */
				$order = $orderClassName::load($orderId);
			}

		}

		if ($order === null)
		{
			return $result;
		}

		if ($order !== null && $basket !== null)
		{
			$r = static::appendBasketToOrder($order, $basket->getOrderableItems());
			if(!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
			}
		}

		/** @var Sale\Result $r */
		$r = $order->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}
		return $result;
	}


	/**
	 * @return OrderCompatibility|null
	 */
	protected function getOrderCompatibility()
	{
		return $this->orderCompatibility;
	}

	/**
	 * Data synchronization basket and shipment
	 *
	 * @internal
	 *
	 * @param Sale\ShipmentCollection $shipmentCollection		Entity shipment collection.
	 * @param Sale\Basket $basket								Entity basket.
	 *
	 * @return Sale\Result
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 */
	public static function syncShipmentCollectionAndBasket(Sale\ShipmentCollection $shipmentCollection, Sale\Basket $basket)
	{
		$result = new Sale\Result();

		if (count($shipmentCollection) > 2)
		{
			return $result;
		}

		/** @var Sale\Order $order */
		if (!$order = $shipmentCollection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}

		$baseShipment = null;

		$shipmentCollection->setMathActionOnly(true);

		if (count($shipmentCollection) == 1 && $shipmentCollection->isExistsSystemShipment())
		{
			/** @var Sale\Shipment $systemShipment */
			if (!$systemShipment = $shipmentCollection->getSystemShipment())
			{
				throw new Main\ObjectNotFoundException('Entity system "Shipment" not found');
			}

			$shipment = $shipmentCollection->createItem();
			$r = $shipmentCollection->cloneShipment($systemShipment, $shipment);
			if (!$r->isSuccess())
			{
				$result->addErrors($r->getErrors());
				return $result;
			}
		}


		/** @var Sale\Shipment $shipment */
		foreach ($shipmentCollection as $shipment)
		{
			if ($shipment->isSystem() || $shipment->isShipped())
				continue;

			/** @var Sale\BasketItem $basketItem */
			foreach ($basket as $basketItem)
			{
				$shipmentItemCollection = $shipment->getShipmentItemCollection();
				if (!$shipmentItem = $shipmentItemCollection->getItemByBasketCode($basketItem->getBasketCode()))
				{
					$shipmentItem = $shipmentItemCollection->createItem($basketItem);
				}

				if (!$shipmentItem)
					continue;

				/** @var Sale\Result $r */
				$r = $shipmentItem->setQuantity($basketItem->getQuantity());
				if (!$r->isSuccess())
				{
					$result->addErrors($r->getErrors());
				}

			}

			break;
		}

		$shipmentCollection->setMathActionOnly(false);

		return $result;

	}

	/**
	 * @internal
	 * @param Sale\BasketItem $basketItem
	 *
	 * @return array
	 * @throws Main\ObjectNotFoundException
	 */
	public static function convertBasketItemToArray(Sale\BasketItem $basketItem)
	{
		$fields = $basketItem->getFieldValues();

		/** @var Sale\BasketItemCollection $collection */
		$collection = $basketItem->getCollection();

		/** @var Sale\Basket $basket */
		if (!$basket = $collection->getBasket())
		{
			throw new Main\ObjectNotFoundException('Entity "Basket" not found');
		}

		if (empty($fields['LID']))
			$fields['LID'] = $basket->getSiteId();

		if (empty($fields['LID']))
		{
			if ($order = $basket->getOrder())
			{
				$fields['LID'] = $order->getField('LID');
			}
			//$order->getField('LID')

		}

		if (empty($fields['FUSER_ID']))
			$fields['FUSER_ID'] = $basket->getFUserId(true);


		/** @var Sale\BasketPropertiesCollection $propertyCollection */
		if ($propertyCollection = $basketItem->getPropertyCollection())
		{
			$fields['PROPS'] = $propertyCollection->getPropertyValues();
		}

		return $fields;
	}

	protected function getWhiteListFields()
	{
		return array_merge(parent::getWhiteListFields(), array('USER_ID'), array_keys(static::getAliasFields()));
	}
}


class BasketFetchAdapter implements FetchAdapter
{
	public function adapt(array $row)
	{
		if(!empty($row["~DIMENSIONS"]) && is_array($row["~DIMENSIONS"]))
			$row["~DIMENSIONS"] = serialize($row["~DIMENSIONS"]);

		if(!empty($row["DIMENSIONS"]) && is_array($row["DIMENSIONS"]))
			$row["DIMENSIONS"] = serialize($row["DIMENSIONS"]);

		if(!empty($row["QUANTITY"]))
			$row["QUANTITY"] = Sale\BasketItem::formatQuantity($row['QUANTITY']);

		if(!empty($row["~QUANTITY"]))
			$row["~QUANTITY"] = Sale\BasketItem::formatQuantity($row['~QUANTITY']);

		return $row;
	}
}