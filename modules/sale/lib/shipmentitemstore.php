<?php


namespace Bitrix\Sale;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main;
use Bitrix\Sale;

Loc::loadMessages(__FILE__);

class ShipmentItemStore
	extends Internals\CollectableEntity
	implements \IEntityMarker
{
	/** @var  BasketItem */
	protected $basketItem;

	/** @var null|array  */
	protected $barcodeList = null;

	private static $eventClassName = null;


	/**
	 * @return array
	 */
	public static function getAvailableFields()
	{
		return array("ORDER_DELIVERY_BASKET_ID", "STORE_ID", "QUANTITY", "BARCODE", 'BASKET_ID');
	}

	/**
	 * @return array
	 */
	protected static function getMeaningfulFields()
	{
		return array();
	}


	/**
	 * @param $itemData
	 * @return ShipmentItemStore
	 */
	private static function createShipmentItemStoreObject(array $itemData = array())
	{
		$registry = Registry::getInstance(static::getRegistryType());
		$shipmentItemStoreClassName = $registry->getShipmentItemStoreClassName();

		return new $shipmentItemStoreClassName($itemData);
	}

	/**
	 * @return string
	 */
	public static function getRegistryType()
	{
		return Registry::REGISTRY_TYPE_ORDER;
	}

	public static function create(ShipmentItemStoreCollection $collection, BasketItem $basketItem)
	{
		$fields = array(
			'BASKET_ID' => $basketItem->getId(),
		);

		$shipmentItemStore = static::createShipmentItemStoreObject($fields);
		$shipmentItemStore->setCollection($collection);

		$shipmentItemStore->basketItem = $basketItem;

		return $shipmentItemStore;

	}

	/**
	 * Deletes shipment item
	 *
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotSupportedException
	 * @throws \Exception
	 */
	public function delete()
	{
		$result = new Result();

		if (self::$eventClassName === null)
		{
			self::$eventClassName = static::getEntityEventName();
		}

		/** @var array $oldEntityValues */
		$oldEntityValues = $this->fields->getOriginalValues();

		/** @var Main\Event $event */
		$event = new Main\Event('sale', "OnBefore".self::$eventClassName."EntityDeleted", array(
				'ENTITY' => $this,
				'VALUES' => $oldEntityValues,
		));
		$event->send();

		if ($event->getResults())
		{
			/** @var Main\EventResult $eventResult */
			foreach($event->getResults() as $eventResult)
			{
				if($eventResult->getType() == Main\EventResult::ERROR)
				{
					$errorMsg = new ResultError(Loc::getMessage('SALE_EVENT_ON_BEFORE_'.ToUpper(self::$eventClassName).'_ENTITY_DELETED_ERROR'), 'SALE_EVENT_ON_BEFORE_'.ToUpper(self::$eventClassName).'_ENTITY_DELETED_ERROR');
					if ($eventResultData = $eventResult->getParameters())
					{
						if (isset($eventResultData) && $eventResultData instanceof ResultError)
						{
							/** @var ResultError $errorMsg */
							$errorMsg = $eventResultData;
						}
					}

					$result->addError($errorMsg);
				}
			}

			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		$r = parent::delete();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		/** @var array $oldEntityValues */
		$oldEntityValues = $this->fields->getOriginalValues();

		/** @var Main\Event $event */
		$event = new Main\Event('sale', "On".self::$eventClassName."EntityDeleted", array(
				'ENTITY' => $this,
				'VALUES' => $oldEntityValues,
		));
		$event->send();

		if ($event->getResults())
		{
			/** @var Main\EventResult $eventResult */
			foreach($event->getResults() as $eventResult)
			{
				if($eventResult->getType() == Main\EventResult::ERROR)
				{
					$errorMsg = new ResultError(Loc::getMessage('SALE_EVENT_ON_'.ToUpper(self::$eventClassName).'_ENTITY_DELETED_ERROR'), 'SALE_EVENT_ON_'.ToUpper(self::$eventClassName).'_ENTITY_DELETED_ERROR');
					if ($eventResultData = $eventResult->getParameters())
					{
						if (isset($eventResultData) && $eventResultData instanceof ResultError)
						{
							/** @var ResultError $errorMsg */
							$errorMsg = $eventResultData;
						}
					}

					$result->addError($errorMsg);
				}
			}

			if (!$result->isSuccess())
			{
				return $result;
			}
		}

		return $result;
	}


	/**
	 * @return int
	 */
	public function getBasketId()
	{
		return $this->getField('BASKET_ID');
	}

	/**
	 * @return float
	 */
	public function getQuantity()
	{
		return floatval($this->getField('QUANTITY'));
	}

	/**
	 * @return integer
	 */
	public function getStoreId()
	{
		return $this->getField('STORE_ID');
	}

	/**
	 * @return string
	 */
	public function getBasketCode()
	{
		$basket = $this->getBasketItem();
		return $basket->getBasketCode();
	}

	/**
	 * @return string
	 */
	public function getBarcode()
	{
		return $this->getField('BARCODE');
	}

	/**
	 * @return string
	 */
	public function getItemCode()
	{
		$basketCode = $this->getBasketCode();
		$storeId = $this->getStoreId();
		$deliveryBasketId = $this->getField('ORDER_DELIVERY_BASKET_ID');
		$id = $this->getField('id');

		return $basketCode."_".$storeId."_".$deliveryBasketId."_".$id;
	}


	/**
	 * @return bool
	 */
	protected function loadBasketItem()
	{
		/** @var ShipmentItemStoreCollection $collection */
		$collection = $this->getCollection();
		$shipmentItem = $collection->getShipmentItem();
		$basketItem = $shipmentItem->getBasketItem();

		return $basketItem;
	}

	/**
	 * @return BasketItem
	 */
	public function getBasketItem()
	{
		if ($this->basketItem == null)
		{
			$this->basketItem = $this->loadBasketItem();
		}

		return $this->basketItem;
	}

	/**
	 * @param $id
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 */
	public static function loadForShipmentItem($id)
	{
		if (intval($id) <= 0)
			throw new Main\ArgumentNullException("id");

		$items = array();

		$itemDataList = static::getList(
			array(
				'filter' => array('ORDER_DELIVERY_BASKET_ID' => $id),
				'order' => array('DATE_CREATE' => 'ASC', 'ID' => 'ASC')
			)
		);
		while ($itemData = $itemDataList->fetch())
			$items[] = static::createShipmentItemStoreObject($itemData);

		return $items;
	}

	/**
	 * @return Main\Entity\AddResult|Main\Entity\UpdateResult
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws \Exception
	 */
	public function save()
	{
		global $USER;

		$result = new Result();

		$id = $this->getId();
		$fields = $this->fields->getValues();

		if (self::$eventClassName === null)
		{
			self::$eventClassName = static::getEntityEventName();
		}

		/** @var ShipmentItemStoreCollection $shipmentItemStoreCollection */
		if (!$shipmentItemStoreCollection = $this->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentItemStoreCollection" not found');
		}

		/** @var Result $r */
		$r = $shipmentItemStoreCollection->checkAvailableQuantity($this);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
			return $result;
		}

		/** @var ShipmentItem $shipmentItem */
		if (!$shipmentItem = $shipmentItemStoreCollection->getShipmentItem())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentItem" not found');
		}

		/** @var ShipmentItemCollection $shipmentItemCollection */
		if (!$shipmentItemCollection = $shipmentItem->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
		}

		/** @var Shipment $shipment */
		if (!$shipment = $shipmentItemCollection->getShipment())
		{
			throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
		}

		/** @var ShipmentCollection $shipmentCollection */
		if (!$shipmentCollection = $shipment->getCollection())
		{
			throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
		}

		/** @var Order $order */
		if (!$order = $shipmentCollection->getOrder())
		{
			throw new Main\ObjectNotFoundException('Entity "Order" not found');
		}


		if ($this->isChanged() && self::$eventClassName)
		{
			/** @var Main\Entity\Event $event */
			$event = new Main\Event('sale', 'OnBefore'.self::$eventClassName.'EntitySaved', array(
					'ENTITY' => $this,
					'VALUES' => $this->fields->getOriginalValues()
			));
			$event->send();
		}

		/** @var BasketItem $basketItem */
		$basketItem = $this->getBasketItem();

		if ($id > 0)
		{
			$fields = $this->fields->getChangedValues();

			if (!empty($fields) && is_array($fields))
			{
				if (isset($fields["QUANTITY"]) && (floatval($fields["QUANTITY"]) < 0))
					throw new Main\ArgumentNullException('quantity');

				$fields['DATE_MODIFY'] = new Main\Type\DateTime();
				$this->setFieldNoDemand('DATE_MODIFY', $fields['DATE_MODIFY']);
				
				$fields['MODIFIED_BY'] = $USER->GetID();
				$this->setFieldNoDemand('MODIFIED_BY', $fields['MODIFIED_BY']);

				$r = $this->updateInternal($id, $fields);
				if (!$r->isSuccess())
				{
					$registry = Registry::getInstance(static::getRegistryType());

					/** @var OrderHistory $orderHistory */
					$orderHistory = $registry->getOrderHistoryClassName();
					$orderHistory::addAction(
						'SHIPMENT',
						$order->getId(),
						'SHIPMENT_ITEM_STORE_UPDATE_ERROR',
						null,
						$this,
						array("ERROR" => $r->getErrorMessages())
					);
					$result->addErrors($r->getErrors());
					return $result;
				}

				if ($resultData = $r->getData())
					$result->setData($resultData);
			}

		}
		else
		{

			if (!isset($fields["ORDER_DELIVERY_BASKET_ID"]))
			{
				$fields['ORDER_DELIVERY_BASKET_ID'] = $this->getParentShipmentItemId();
				$this->setFieldNoDemand('ORDER_DELIVERY_BASKET_ID', $fields['ORDER_DELIVERY_BASKET_ID']);
			}

			if (!isset($fields["BASKET_ID"]))
			{
				$fields['BASKET_ID'] = $basketItem->getId();
				$this->setFieldNoDemand('BASKET_ID', $fields['BASKET_ID']);
			}

			$fields['DATE_CREATE'] = new Main\Type\DateTime();
			$this->setFieldNoDemand('DATE_CREATE', $fields['DATE_CREATE']);

			if (!isset($fields["QUANTITY"]))
				return $result;

			if ($basketItem->isBarcodeMulti() && isset($fields['BARCODE']) && strval(trim($fields['BARCODE'])) == "")
			{
				$result->addError(new ResultError(Loc::getMessage('SHIPMENT_ITEM_STORE_BARCODE_MULTI_EMPTY', array(
					'#PRODUCT_NAME#' => $basketItem->getField('NAME'),
					'#STORE_ID#' => $fields['STORE_ID'],
				)), 'SHIPMENT_ITEM_STORE_BARCODE_MULTI_EMPTY'));
				return $result;
			}

			$r = $this->addInternal($fields);
			if (!$r->isSuccess())
			{
				$registry = Registry::getInstance(static::getRegistryType());

				/** @var OrderHistory $orderHistory */
				$orderHistory = $registry->getOrderHistoryClassName();
				$orderHistory::addAction(
					'SHIPMENT',
					$order->getId(),
					'SHIPMENT_ITEM_STORE_ADD_ERROR',
					$id,
					$this,
					array("ERROR" => $r->getErrorMessages())
				);

				$result->addErrors($r->getErrors());
				return $result;
			}

			if ($resultData = $r->getData())
				$result->setData($resultData);

			$id = $r->getId();
			$this->setFieldNoDemand('ID', $id);

		}

		if ($id > 0)
		{
			$result->setId($id);
		}

		if ($this->isChanged() && self::$eventClassName)
		{
			/** @var Main\Event $event */
			$event = new Main\Event('sale', 'On'.self::$eventClassName.'EntitySaved', array(
					'ENTITY' => $this,
					'VALUES' => $this->fields->getOriginalValues(),
			));
			$event->send();
		}

		return $result;
	}

	/**
	 * @return int
	 */
	private function getParentShipmentItemId()
	{
		/** @var ShipmentItemStoreCollection $collection */
		$collection = $this->getCollection();
		$shipmentItem = $collection->getShipmentItem();
		return $shipmentItem->getId();
	}

	/**
	 * @param string $name
	 * @param null|string $oldValue
	 * @param null|string $value
	 * @throws Main\ObjectNotFoundException
	 */
	protected function addChangesToHistory($name, $oldValue = null, $value = null)
	{
		if ($this->getId() > 0)
		{
			/** @var ShipmentItemStoreCollection $shipmentItemStoreCollection */
			if (!$shipmentItemStoreCollection = $this->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentItemStoreCollection" not found');
			}

			/** @var ShipmentItem $shipmentItem */
			if (!$shipmentItem = $shipmentItemStoreCollection->getShipmentItem())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentItem" not found');
			}

			/** @var ShipmentItemCollection $shipmentItemCollection */
			if (!$shipmentItemCollection = $shipmentItem->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentItemCollection" not found');
			}

			/** @var Shipment $shipment */
			if (!$shipment = $shipmentItemCollection->getShipment())
			{
				throw new Main\ObjectNotFoundException('Entity "Shipment" not found');
			}

			if ($shipment->isSystem())
				return;

			/** @var ShipmentCollection $shipmentCollection */
			if (!$shipmentCollection = $shipment->getCollection())
			{
				throw new Main\ObjectNotFoundException('Entity "ShipmentCollection" not found');
			}

			/** @var Order $order */
			if (($order = $shipmentCollection->getOrder()) && $order->getId() > 0)
			{
				$historyFields = array();

				/** @var BasketItem $basketItem */
				if ($basketItem = $shipmentItem->getBasketItem())
				{
					$historyFields = array(
						'NAME' => $basketItem->getField('NAME'),
						'PRODUCT_ID' => $basketItem->getField('PRODUCT_ID'),
					);
				}

				$registry = Registry::getInstance(static::getRegistryType());

				/** @var OrderHistory $orderHistory */
				$orderHistory = $registry->getOrderHistoryClassName();
				$orderHistory::addField(
					'SHIPMENT_ITEM_STORE',
					$order->getId(),
					$name,
					$oldValue,
					$value,
					$this->getId(),
					$this,
					$historyFields
					);
			}


		}
	}

	/**
	 * @param array $parameters
	 *
	 * @return Main\DB\Result
	 * @throws Main\ArgumentException
	 */
	public static function getList(array $parameters = array())
	{
		return Sale\Internals\ShipmentItemStoreTable::getList($parameters);
	}

	/**
	 * @internal
	 *
	 * @param \SplObjectStorage $cloneEntity
	 * @return Internals\CollectableEntity|ShipmentItemStore|object
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 */
	public function createClone(\SplObjectStorage $cloneEntity)
	{
		if ($this->isClone() && $cloneEntity->contains($this))
		{
			return $cloneEntity[$this];
		}

		/** @var ShipmentItemStore $shipmentItemStoreClone */
		$shipmentItemStoreClone = parent::createClone($cloneEntity);

		/** @var BasketItem $basketItem */
		if ($basketItem = $this->getBasketItem())
		{
			if (!$cloneEntity->contains($basketItem))
			{
				$cloneEntity[$basketItem] = $basketItem->createClone($cloneEntity);
			}

			if ($cloneEntity->contains($basketItem))
			{
				$shipmentItemStoreClone->basketItem = $cloneEntity[$basketItem];
			}
		}

		return $shipmentItemStoreClone;
	}


	/**
	 * @param $value
	 *
	 * @return string
	 */
	public function getErrorEntity($value)
	{
		static $className = null;
		$errorsList = static::getAutoFixErrorsList();
		if (is_array($errorsList) && in_array($value, $errorsList))
		{
			if ($className === null)
				$className = static::getClassName();
		}
		return $className;
	}

	/**
	 * @param $value
	 *
	 * @return bool
	 */
	public function canAutoFixError($value)
	{
		$errorsList = static::getAutoFixErrorsList();
		return (is_array($errorsList) && in_array($value, $errorsList));
	}

	/**
	 * @return array
	 */
	public function getAutoFixErrorsList()
	{
		return array();
	}

	/**
	 * @param $code
	 *
	 * @return Result
	 */
	public function tryFixError($code)
	{
		return new Result();
	}

	public function canMarked()
	{
		return false;
	}

	public function getMarkField()
	{
		return null;
	}

	/**
	 * @param array $data
	 * @return Main\Entity\AddResult
	 */
	protected function addInternal(array $data)
	{
		return Internals\ShipmentItemStoreTable::add($data);
	}

	/**
	 * @param $primary
	 * @param array $data
	 * @return Main\Entity\UpdateResult
	 */
	protected function updateInternal($primary, array $data)
	{
		return Internals\ShipmentItemStoreTable::update($primary, $data);
	}

	/**
	 * @return array
	 */
	protected static function getFieldsMap()
	{
		return Internals\ShipmentItemStoreTable::getMap();
	}

}