<?php

namespace Bitrix\Crm\Order;

use Bitrix\Main\EventResult;
use Bitrix\Sale;
use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Crm\Requisite;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

/**
 * Class Order
 * @package Bitrix\Crm\Order
 */
class Order extends Sale\Order
{
	protected $contactCompanyCollection = null;

	private $requisiteList = [];

	/**
	 * @param $siteId
	 * @param null $userId
	 * @param null $currency
	 * @return Sale\Order
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Bitrix\Main\ObjectException
	 */
	public static function create($siteId, $userId = null, $currency = null)
	{
		$order = parent::create($siteId, $userId, $currency);

		$order->setFieldNoDemand(
			'RESPONSIBLE_ID',
			Crm\Settings\OrderSettings::getCurrent()->getDefaultResponsibleId()
		);

		return $order;
	}

	/**
	 * @return Sale\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Exception
	 */
	protected function add()
	{
		$result = parent::add();
		if (!$result->isSuccess())
		{
			return $result;
		}

		$contactCompanyCollection = $this->getContactCompanyCollection();
		if ($contactCompanyCollection->isEmpty())
		{
			$r = $this->addContactCompany();
			if (!$r->isSuccess())
			{
				return $r;
			}

			$this->setContactCompanyRequisites();
		}

		$this->addTimelineEntryOnCreate();

		return $result;
	}

	/**
	 * @param string $name
	 * @param float|int|mixed|string $oldValue
	 * @param float|int|mixed|string $value
	 * @return Main\Entity\Result|Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectNotFoundException
	 */
	protected function onFieldModify($name, $oldValue, $value)
	{
		$result = parent::onFieldModify($name, $oldValue, $value);
		if (!$result->isSuccess())
		{
			return $result;
		}

		if ($name === 'STATUS_ID')
		{
			$canceled = (OrderStatus::getSemanticID($value) === Crm\PhaseSemantics::FAILURE) ? 'Y' : 'N';

			$r = $this->setField('CANCELED', $canceled);
			if (!$r->isSuccess())
			{
				return $result->addErrors($r->getErrors());
			}
		}

		return $result;
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * @return void
	 */
	private function setContactCompanyRequisites()
	{
		$collection = $this->getContactCompanyCollection();

		$entity = $collection->getPrimaryCompany();
		if ($entity === null)
		{
			$entity = $collection->getPrimaryContact();
		}

		if ($entity === null)
		{
			return;
		}

		$result = [
			'MC_REQUISITE_ID' => 0,
			'MC_BANK_DETAIL_ID' => 0
		];

		$requisiteList = $entity->getRequisiteList();
		if ($requisiteList)
		{
			$result['REQUISITE_ID'] = current($requisiteList)['ID'];
		}

		$bankRequisiteList = $entity->getBankRequisiteList();
		if ($bankRequisiteList)
		{
			$result['BANK_DETAIL_ID'] = current($bankRequisiteList)['ID'];
		}

		$this->setRequisiteLink($result);
	}

	/**
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\LoaderException
	 * @throws Main\NotSupportedException
	 */
	protected function onAfterSave()
	{
		$result = parent::onAfterSave();

		if ($this->fields->isChanged('CANCELED') && $this->isCanceled())
		{
			Crm\Automation\Trigger\OrderCanceledTrigger::execute(
				[['OWNER_TYPE_ID' => \CCrmOwnerType::Order, 'OWNER_ID' => $this->getId()]],
				['ORDER' => $this]
			);
		}

		if ($this->isNew())
		{
			$this->runAutomationOnAdd();
		}
		else
		{
			if ($this->fields->isChanged('STATUS_ID'))
			{
				$this->runAutomationOnStatusChanged();
				$this->addTimelineEntryOnStatusModify();
			}

			if ($this->fields->isChanged('PRICE') || $this->fields->isChanged('CURRENCY') )
			{
				$this->updateTimelineCreationEntity();
			}
		}

		if ($this->fields->isChanged('CANCELED'))
		{
			if (!($this->isNew() && $this->getField('CANCELED') === 'N'))
			{
				$this->addTimelineEntryOnCancel();
			}
		}

		$this->saveRequisiteLink();

		if ($this->fields->isChanged('RESPONSIBLE_ID') || $this->fields->isChanged('STATUS_ID'))
		{
			if($this->fields->isChanged('RESPONSIBLE_ID'))
			{
				Permissions\Order::updatePermission($this->getId(), $this->getField('RESPONSIBLE_ID'));
			}

			static::resetCounters($this->getField('RESPONSIBLE_ID'));
		}

		$this->appendBuyerGroups();

		if(Main\Loader::includeModule('pull'))
		{
			\CPullWatch::AddToStack(
				'CRM_ENTITY_ORDER',
				array(
					'module_id' => 'crm',
					'command' => 'onOrderSave',
					'params' => array(
						'FIELDS' => $this->getFieldValues()
					)
				)
			);
		}

		return $result;
	}

	/**
	 * return void
	 */
	protected function appendBuyerGroups()
	{
		$userId = $this->getField('USER_ID');

		if (!empty($userId))
		{
			\CUser::AppendUserGroup($userId, BuyerGroup::getDefaultGroups());
		}
	}

	/**
	 * @return void;
	 */
	private function runAutomationOnAdd()
	{
		Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Order, $this->getId());
	}

	/**
	 * @return void;
	 */
	private function runAutomationOnStatusChanged()
	{
		Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Order, $this->getId());
	}

	/**
	 * @throws Main\ArgumentException
	 * @return void;
	 */
	private function addTimelineEntryOnCreate()
	{
		Crm\Timeline\OrderController::getInstance()->onCreate(
			$this->getId(),
			[
				'FIELDS' => [
					'ID' => $this->getId(),
					'CREATED_BY' => $this->getField('CREATED_BY'),
					'RESPONSIBLE_ID' => $this->getField('RESPONSIBLE_ID'),
					'DATE_INSERT' => $this->getField('DATE_INSERT'),
					'PRICE' => $this->getField('PRICE'),
					'CURRENCY' => $this->getField('CURRENCY')
				]
			]
		);
	}

	/**
	 * @throws Main\ArgumentException
	 */
	private function addTimelineEntryOnCancel()
	{
		$fields = [
			'ID' => $this->getId(),
			'CANCELED' => $this->getField('CANCELED'),
		];

		if ($this->getField('CANCELED') === 'Y')
		{
			$fields['REASON_CANCELED'] = $this->getField('REASON_CANCELED');
			$fields['EMP_CANCELED_ID'] = $this->getField('EMP_CANCELED_ID');
		}

		Crm\Timeline\OrderController::getInstance()->onCancel($this->getId(), ['FIELDS' => $fields]);
	}

	/**
	 * @throws Main\ArgumentException
	 * @return void;
	 */
	private function addTimelineEntryOnStatusModify()
	{
		$fields = $this->getFields();
		$originalValues  = $fields->getOriginalValues();

		$modifyParams = array(
			'PREVIOUS_FIELDS' => array('STATUS_ID' => $originalValues['STATUS_ID']),
			'CURRENT_FIELDS' => array(
				'STATUS_ID' => $this->getField('STATUS_ID'),
				'EMP_STATUS_ID' => $this->getField('EMP_STATUS_ID')
			),
		);

		Crm\Timeline\OrderController::getInstance()->onModify($this->getId(), $modifyParams);
	}

	/**
	 * @throws Main\ArgumentException
	 * @return void;
	 */
	private function updateTimelineCreationEntity()
	{
		$fields = $this->getFields();
		$selectedFields =[
			'DATE_INSERT_TIMESTAMP' => $fields['DATE_INSERT']->getTimestamp(),
			'PRICE' => $fields['PRICE'],
			'CURRENCY' => $fields['CURRENCY']
		];

		Crm\Timeline\OrderController::getInstance()->updateSettingFields(
			$this->getId(),
			Crm\Timeline\TimelineType::CREATION,
			$selectedFields
		);
	}

	/**
	 * @return Sale\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\NotImplementedException
	 * @throws \Exception
	 */
	private function addContactCompany()
	{
		$result = new Sale\Result();

		$matches = Matcher\EntityMatchManager::getInstance()->match($this);
		if ($matches)
		{
			/** @var ContactCompanyCollection $communication */
			$communication = $this->getContactCompanyCollection();
			if (isset($matches[\CCrmOwnerType::Contact]))
			{
				/** @var Contact $contact */
				$contact = Contact::create($communication);
				$contact->setField('ENTITY_ID', $matches[\CCrmOwnerType::Contact]);
				$contact->setField('IS_PRIMARY', 'Y');

				$communication->addItem($contact);
			}

			if (isset($matches[\CCrmOwnerType::Company]))
			{
				/** @var Company $company */
				$company = Company::create($communication);
				$company->setField('ENTITY_ID', $matches[\CCrmOwnerType::Company]);
				$company->setField('IS_PRIMARY', 'Y');

				$communication->addItem($company);
			}
		}

		return $result;
	}

	/**
	 * @return Sale\Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\ObjectNotFoundException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 * @throws \Exception
	 */
	protected function saveEntities()
	{
		$result = parent::saveEntities();

		$communication = $this->getContactCompanyCollection();
		$r = $communication->save();
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param $oderId
	 * @return Sale\Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected static function deleteEntitiesNoDemand($oderId)
	{
		$result = parent::deleteEntitiesNoDemand($oderId);

		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var ContactCompanyCollection $contactCompanyCollection */
		$contactCompanyCollection = $registry->get(ENTITY_CRM_CONTACT_COMPANY_COLLECTION);

		$r = $contactCompanyCollection::deleteNoDemand($oderId);
		if (!$r->isSuccess())
		{
			$result->addErrors($r->getErrors());
		}

		return $result;
	}

	/**
	 * @param Sale\OrderBase $order
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectNotFoundException
	 * @throws Main\SystemException
	 */
	protected static function deleteEntities(Sale\OrderBase $order)
	{
		parent::deleteEntities($order);

		if ($order instanceof Order)
		{
			/** @var ContactCompanyCollection $contactCompanyCollection */
			if ($contactCompanyCollection = $order->getContactCompanyCollection())
			{
				/** @var ContactCompanyEntity $entity */
				foreach ($contactCompanyCollection as $entity)
				{
					$entity->delete();
				}
			}
		}
	}

	/**
	 * @return ContactCompanyCollection|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getContactCompanyCollection()
	{
		if (!$this->contactCompanyCollection)
		{
			$this->contactCompanyCollection = $this->loadContactCompanyCollection();
		}

		return $this->contactCompanyCollection;
	}

	/**
	 * @return ContactCompanyCollection
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function loadContactCompanyCollection()
	{
		$registry = Sale\Registry::getInstance(static::getRegistryType());

		/** @var ContactCompanyCollection $contactCompanyClassName */
		$contactCompanyClassName = $registry->get(ENTITY_CRM_CONTACT_COMPANY_COLLECTION);
		return $contactCompanyClassName::load($this);
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function isChanged()
	{
		if (parent::isChanged())
		{
			return true;
		}

		/** @var ContactCompanyCollection $contactCompanyCollection */
		if ($contactCompanyCollection = $this->getContactCompanyCollection())
		{
			if ($contactCompanyCollection->isChanged())
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\SystemException
	 */
	public function clearChanged()
	{
		parent::clearChanged();

		if ($contactCompanyCollection = $this->getContactCompanyCollection())
		{
			$contactCompanyCollection->clearChanged();
		}
	}

	/**
	 * Delete order.
	 *
	 * @param int $id Order id.
	 * @return Sale\Result
	 * @throws Main\ArgumentNullException
	 */
	public static function delete($id)
	{
		$result = parent::delete($id);

		if ($result->isSuccess())
		{
			$data = $result->getData();

			/** @var Sale\OrderBase $order */
			$order = $data['ORDER'];
			$responsibleId = $order->getField('RESPONSIBLE_ID');
			if ($responsibleId > 0)
			{
				static::resetCounters($responsibleId);
			}
		}

		return $result;
	}

	/**
	 * @param int $responsibleId
	 */
	private static function resetCounters($responsibleId)
	{
		$respIds = intval($responsibleId) > 0 ? array($responsibleId) : array();

		Crm\Counter\EntityCounterManager::reset(
			Crm\Counter\EntityCounterManager::prepareCodes(
				\CCrmOwnerType::Order,
				array(
					Crm\Counter\EntityCounterType::PENDING,
					Crm\Counter\EntityCounterType::IDLE,
					Crm\Counter\EntityCounterType::ALL
				),
				array('EXTENDED_MODE' => true)
			),
			$respIds
		);
	}

	/**
	 * @param array $requisiteList
	 */
	public function setRequisiteLink(array $requisiteList)
	{
		foreach ($requisiteList as $name => $value)
		{
			$this->requisiteList[$name] = $value;
		}
	}

	/**
	 * @return array|false
	 * @throws Main\ArgumentException
	 */
	public function getRequisiteLink()
	{
		if (!$this->requisiteList)
		{
			if ($this->getId() > 0)
			{
				$this->requisiteList = $this->loadRequisiteLink();
			}
		}

		return $this->requisiteList;
	}

	/**
	 * @return array|false
	 * @throws Main\ArgumentException
	 */
	protected function loadRequisiteLink()
	{
		$dbRes = Requisite\EntityLink::getList([
			'select' => [
				'REQUISITE_ID',
				'BANK_DETAIL_ID',
				'MC_REQUISITE_ID',
				'MC_BANK_DETAIL_ID',
			],
			'filter' => [
				'=ENTITY_ID' => $this->getId(),
				'=ENTITY_TYPE_ID' => \CCrmOwnerType::Order
			]
		]);

		if ($data = $dbRes->fetch())
		{
			 return $data;
		}

		return [];
	}

	/**
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 */
	protected function saveRequisiteLink()
	{
		$requisiteLink = $this->getRequisiteLink();
		if ($requisiteLink)
		{
			Requisite\EntityLink::register(
				\CCrmOwnerType::Order,
				$this->getId(),
				$requisiteLink['REQUISITE_ID'] ?: 0,
				$requisiteLink['BANK_DETAIL_ID'] ?: 0,
				$requisiteLink['MC_REQUISITE_ID'] ?: 0,
				$requisiteLink['MC_BANK_DETAIL_ID'] ?: 0
			);
		}
	}

	/**
	 * @param \SplObjectStorage $cloneEntity
	 * @throws Main\ArgumentException
	 * @throws Main\NotImplementedException
	 * @throws Main\SystemException
	 */
	protected function cloneEntities(\SplObjectStorage $cloneEntity)
	{
		parent::cloneEntities($cloneEntity);

		/** @var Order $orderClone */
		$orderClone = $cloneEntity[$this];

		/** @var ContactCompanyCollection $contactCompanyCollection */
		if ($contactCompanyCollection = $this->getContactCompanyCollection())
		{
			$orderClone->contactCompanyCollection = $contactCompanyCollection->createClone($cloneEntity);
		}
	}

	/**
	 * @return EventResult
	 */
	public static function OnInitRegistryList()
	{
		$registry = array(
			Sale\Registry::REGISTRY_TYPE_ORDER => array(
				Sale\Registry::ENTITY_ORDER => '\Bitrix\Crm\Order\Order',
				Sale\Registry::ENTITY_PROPERTY => '\Bitrix\Crm\Order\Property',
				Sale\Registry::ENTITY_PROPERTY_VALUE => '\Bitrix\Crm\Order\PropertyValue',
				Sale\Registry::ENTITY_PROPERTY_VALUE_COLLECTION => '\Bitrix\Crm\Order\PropertyValueCollection',
				Sale\Registry::ENTITY_TAX => '\Bitrix\Crm\Order\Tax',
				Sale\Registry::ENTITY_DISCOUNT => '\Bitrix\Crm\Order\Discount',
				Sale\Registry::ENTITY_DISCOUNT_COUPON => '\Bitrix\Crm\Order\DiscountCoupon',
				Sale\Registry::ENTITY_ORDER_DISCOUNT => '\Bitrix\Crm\Order\OrderDiscount',
				Sale\Registry::ENTITY_BASKET => '\Bitrix\Crm\Order\Basket',
				Sale\Registry::ENTITY_BASKET_ITEM => '\Bitrix\Crm\Order\BasketItem',
				Sale\Registry::ENTITY_BUNDLE_COLLECTION => '\Bitrix\Crm\Order\BundleCollection',
				Sale\Registry::ENTITY_BASKET_PROPERTIES_COLLECTION => '\Bitrix\Crm\Order\BasketPropertiesCollection',
				Sale\Registry::ENTITY_BASKET_PROPERTY_ITEM => '\Bitrix\Crm\Order\BasketPropertyItem',
				Sale\Registry::ENTITY_PAYMENT => '\Bitrix\Crm\Order\Payment',
				Sale\Registry::ENTITY_PAYMENT_COLLECTION => '\Bitrix\Crm\Order\PaymentCollection',
				Sale\Registry::ENTITY_SHIPMENT => '\Bitrix\Crm\Order\Shipment',
				Sale\Registry::ENTITY_SHIPMENT_COLLECTION => '\Bitrix\Crm\Order\ShipmentCollection',
				Sale\Registry::ENTITY_SHIPMENT_ITEM => '\Bitrix\Crm\Order\ShipmentItem',
				Sale\Registry::ENTITY_SHIPMENT_ITEM_COLLECTION => '\Bitrix\Crm\Order\ShipmentItemCollection',
				Sale\Registry::ENTITY_SHIPMENT_ITEM_STORE => '\Bitrix\Crm\Order\ShipmentItemStore',
				Sale\Registry::ENTITY_SHIPMENT_ITEM_STORE_COLLECTION => '\Bitrix\Crm\Order\ShipmentItemStoreCollection',
				Sale\Registry::ENTITY_OPTIONS => 'Bitrix\Main\Config\Option',
				Sale\Registry::ENTITY_ORDER_STATUS => 'Bitrix\Crm\Order\OrderStatus',
				Sale\Registry::ENTITY_DELIVERY_STATUS => 'Bitrix\Crm\Order\DeliveryStatus',
				Sale\Registry::ENTITY_PERSON_TYPE => 'Bitrix\Crm\Order\PersonType',
				Sale\Registry::ENTITY_ENTITY_MARKER => 'Bitrix\Crm\Order\EntityMarker',
				Sale\Registry::ENTITY_ORDER_HISTORY => 'Bitrix\Crm\Order\OrderHistory',
				Sale\Registry::ENTITY_NOTIFY => 'Bitrix\Crm\Order\Notify',
				ENTITY_CRM_COMPANY => 'Bitrix\Crm\Order\Company',
				ENTITY_CRM_CONTACT => 'Bitrix\Crm\Order\Contact',
				ENTITY_CRM_CONTACT_COMPANY_COLLECTION => 'Bitrix\Crm\Order\ContactCompanyCollection',
				Sale\Registry::ENTITY_TRADE_BINDING_COLLECTION => 'Bitrix\Crm\Order\TradeBindingCollection',
				Sale\Registry::ENTITY_TRADE_BINDING_ENTITY => 'Bitrix\Crm\Order\TradeBindingEntity',
			)
		);

		return new EventResult(EventResult::SUCCESS, $registry);
	}
}