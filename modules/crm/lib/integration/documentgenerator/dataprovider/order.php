<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\Discount;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\OrderStatus;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\Crm\Service;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProvider\User;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Dictionary;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Internals;
use Bitrix\Sale\PropertyBase;
use Bitrix\Sale\Registry;

class Order extends ProductsDataProvider
{
	protected static $properties;

	protected $order;
	protected $contacts;
	protected $payments;
	protected $shipments;

	public function getFields()
	{
		if($this->fields === null)
		{
			parent::getFields();

			$orderProperties = static::getProperties();
			foreach($orderProperties as &$property)
			{
				$property['VALUE'] = [$this, 'getPropertyValue'];
			}
			unset($property);
			$this->fields = array_merge($this->fields, $orderProperties);

			$this->fields['DATE_INSERT']['TYPE'] = static::FIELD_TYPE_DATE;
			$this->fields['DATE_UPDATE']['TYPE'] = static::FIELD_TYPE_DATE;
			$this->fields['DATE_PAYED']['TYPE'] = static::FIELD_TYPE_DATE;
			$this->fields['DATE_DEDUCTED']['TYPE'] = static::FIELD_TYPE_DATE;
			$this->fields['DATE_STATUS']['TYPE'] = static::FIELD_TYPE_DATE;
			$this->fields['PAY_VOUCHER_DATE']['TYPE'] = static::FIELD_TYPE_DATE;
			$this->fields['DATE_CANCELED']['TYPE'] = static::FIELD_TYPE_DATE;
			$this->fields['DELIVERY_DOC_DATE']['TYPE'] = static::FIELD_TYPE_DATE;
			$this->fields['ACCOUNT_NUMBER']['TITLE'] = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_ACCOUNT_NUMBER_TITLE');
			$this->fields['TRACKING_NUMBER']['TITLE'] = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_TRACKING_NUMBER_TITLE');
			$this->fields['USER'] = [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_USER_TITLE'),
				'PROVIDER' => User::class,
				'VALUE' => 'USER_ID',
			];
			$this->fields['DATE_PAYED']['TITLE'] = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_DATE_PAYED_TITLE');
			$this->fields['DATE_DEDUCTED']['TITLE'] = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_DATE_DEDUCTED_TITLE');
			$this->fields['REASON_UNDO_DEDUCTED']['TITLE'] = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_REASON_UNDO_DEDUCTED_TITLE');
			$this->fields['STATUS']['TITLE'] = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_STATUS_TITLE');
			$this->fields['STATUS']['VALUE'] = [$this, 'getStatus'];
			$this->fields['USER_DESCRIPTION']['TITLE'] = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_USER_DESCRIPTION_TITLE');
			$this->fields['COMMENTS']['TITLE'] = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_COMMENTS_TITLE');
			$this->fields['COMPANY']['OPTIONS'] = [
				'DISABLE_MY_COMPANY' => true,
				'VALUES' => [
					'REQUISITE' => $this->getBuyerRequisiteId(),
					'BANK_DETAIL' => $this->getBuyerBankDetailId(),
				]
			];
			if (!$this->isLightMode())
			{
				$this->fields['CONTACTS'] = [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_CONTACTS_TITLE'),
					'PROVIDER' => ArrayDataProvider::class,
					'OPTIONS' => [
						'ITEM_PROVIDER' => Contact::class,
						'ITEM_NAME' => 'CONTACT',
						'ITEM_TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_CONTACT_TITLE'),
						'ITEM_OPTIONS' => [
							'DISABLE_MY_COMPANY' => true,
							'isLightMode' => true,
						],
					],
					'VALUE' => [$this, 'getContacts'],
				];

				$this->fields['PAYMENTS'] = [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_PAYMENTS_TITLE'),
					'PROVIDER' => ArrayDataProvider::class,
					'OPTIONS' => [
						'ITEM_PROVIDER' => Payment::class,
						'ITEM_NAME' => 'PAYMENT',
						'ITEM_TITLE' => Payment::getLangName(),
						'ITEM_OPTIONS' => [
							'isLightMode' => true,
						],
					],
					'VALUE' => [$this, 'getPayments'],
				];

				$this->fields['SHIPMENTS'] = [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_SHIPMENTS_TITLE'),
					'PROVIDER' => ArrayDataProvider::class,
					'OPTIONS' => [
						'ITEM_PROVIDER' => Shipment::class,
						'ITEM_NAME' => 'SHIPMENT',
						'ITEM_TITLE' => Shipment::getLangName(),
						'ITEM_OPTIONS' => [
							'isLightMode' => true,
						],
					],
					'VALUE' => [$this, 'getShipments'],
				];
			}
		}

		return $this->fields;
	}

	/**
	 * @return int|string
	 */
	public function getAssignedId()
	{
		return $this->data['RESPONSIBLE_ID'] ?? null;
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		return GetMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_TITLE');
	}

	/**
	 * @return array
	 */
	protected function getTotalFields()
	{
		$currencyId = $this->getCurrencyId();
		return array_merge(parent::getTotalFields(), [
			'PRICE_DELIVERY' => [
				'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_PRICE_DELIVERY_TITLE'),
				'TYPE' => Money::class,
				'FORMAT' => ['CURRENCY_ID' => $currencyId, 'WITH_ZEROS' => true],
			],
		]);
	}

	/**
	 * @return int
	 */
	protected function getBuyerRequisiteId()
	{
		static $requisiteId = 0;

		if ($requisiteId > 0)
		{
			return $requisiteId;
		}

		if ($this->isLoaded())
		{
			$linkData = $this->getLinkData();
			if (
				isset($linkData['REQUISITE_ID'])
				&& $linkData['REQUISITE_ID'] > 0
			)
			{
				$requisiteId = $linkData['REQUISITE_ID'];
			}
		}

		return $requisiteId;
	}

	/**
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 */
	protected function getLinkData()
	{
		if($this->linkData === null)
		{
			$this->linkData = EntityLink::getByEntity(\CCrmOwnerType::Order, $this->getSource());
		}

		return $this->linkData;
	}

	/**
	 * @return int
	 */
	protected function getSellerBankDetailId()
	{
		$sellerBankDetailId = 0;

		if ($this->isLoaded())
		{
			$linkData = $this->getLinkData();
			if (isset($linkData['MC_BANK_DETAIL_ID']))
			{
				$sellerBankDetailId = $linkData['MC_BANK_DETAIL_ID'];
			}
		}

		return $sellerBankDetailId;
	}

	/**
	 * @return BankDetail|null
	 */
	protected function getBuyerBankDetailId()
	{
		$buyerBankDetailId = 0;

		if ($this->isLoaded())
		{
			$linkData = $this->getLinkData();
			if (isset($linkData['BANK_DETAIL_ID']))
			{
				$buyerBankDetailId = $linkData['BANK_DETAIL_ID'];
			}
		}

		return $buyerBankDetailId;
	}

	/**
	 * @param int $userId
	 * @return bool
	 */
	public function hasAccess($userId)
	{
		if($this->isLoaded())
		{
			return Service\Container::getInstance()->getUserPermissions($userId)->checkReadPermissions(
				$this->getCrmOwnerType(),
				(int)$this->source
			);
		}

		return false;
	}

	/**
	 * @return string
	 */
	protected function getTableClass()
	{
		Loader::includeModule('sale');

		return Internals\OrderTable::class;
	}

	public function getCrmOwnerType()
	{
		return \CCrmOwnerType::Order;
	}

	protected function getPersonTypeID()
	{
		return $this->getValue('PERSON_TYPE_ID');
	}

	public function getCurrencyId()
	{
		return $this->data['CURRENCY'] ?? null;
	}

	protected function calculateTotalFields()
	{
		$order = $this->getOrder();
		if(!$order)
		{
			return;
		}

		foreach($this->getTotalFields() as $placeholder => $field)
		{
			if(isset($field['FORMAT']) && (!isset($field['FORMAT']['WORDS']) || $field['FORMAT']['WORDS'] !== true))
			{
				$this->data[$placeholder] = 0;
			}
		}

		foreach($this->products as $product)
		{
			$this->data['TOTAL_RAW'] += $product->getRawValue('PRICE_RAW_SUM');
			$this->data['TOTAL_DISCOUNT'] += $product->getRawValue('QUANTITY') * $product->getRawValue('DISCOUNT_SUM');
		}

		$taxes = $this->loadTaxes();
		foreach($taxes as $tax)
		{
			$this->data['TOTAL_TAX'] += $tax->getRawValue('VALUE');
		}

		$this->data['TOTAL_QUANTITY'] = array_sum($order->getBasket()->getQuantityList());
		$this->data['TOTAL_SUM'] = $order->getPrice();
		$this->data['TOTAL_BEFORE_TAX'] = $this->data['TOTAL_SUM'] - $this->data['TOTAL_TAX'];
		$this->data['TOTAL_BEFORE_DISCOUNT'] = $this->data['TOTAL_BEFORE_TAX'] + $this->data['TOTAL_DISCOUNT'];
		$this->data['PRICE_DELIVERY'] = $order->getDeliveryPrice();
	}

	/**
	 * @return array|Tax[]
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function loadTaxes()
	{
		$order = $this->getOrder();
		if(!$order)
		{
			return [];
		}

		if($this->taxes === null)
		{
			$this->taxes = [];
			$currencyID = $this->getCurrencyId();
			if ($taxes = $order->getTax()->getTaxList())
			{
				foreach($taxes as $taxInfo)
				{
					if($taxInfo['CODE'] === 'VAT')
					{
						continue;
					}
					$tax = new Tax([
						'NAME' => $taxInfo['NAME'],
						'VALUE' => new Money($taxInfo['VALUE_MONEY'], ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]),
						'NETTO' => 0,
						'BRUTTO' => 0,
						'RATE' => $taxInfo['VALUE'],
						'TAX_INCLUDED' => $taxInfo['IS_IN_PRICE'],
					]);
					$tax->setParentProvider($this);
					$this->taxes[] = $tax;
				}
			}

			$taxes = $this->loadVatTaxesInfo();
			foreach($taxes as $taxInfo)
			{
				$tax = new Tax($taxInfo);
				$tax->setParentProvider($this);
				$this->taxes[] = $tax;
			}
		}

		return $this->taxes;
	}

	protected function loadVatTaxesInfo()
	{
		$taxes = parent::loadVatTaxesInfo();
		$taxInfos = \CCrmTax::GetVatRateInfos();

		$order = $this->getOrder();
		if (!$order || empty($taxInfos))
		{
			return $taxes;
		}
		$taxNames = [];
		foreach($taxInfos as $taxInfo)
		{
			$taxNames[$taxInfo['VALUE']] = $taxInfo['NAME'];
		}
		$currencyID = $this->getCurrencyId();
		foreach ($order->getShipmentCollection() as $shipment)
		{
			/** @var \Bitrix\Crm\Order\Shipment $shipment */
			$vatSum = $shipment->getVatSum();
			if ($vatSum <= 0)
			{
				continue;
			}
			$vatRate = $shipment->getVatRate();
			$vatRate = isset($vatRate) ? $vatRate * 100 : null;
			if (!isset($taxNames[$vatRate]))
			{
				continue;
			}
			if (!isset($taxes[$vatRate]))
			{
				$taxes[$vatRate] = [
					'NAME' => $taxNames[$vatRate],
					'VALUE' => new Money(0, ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]),
					'NETTO' => new Money(0, ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]),
					'BRUTTO' => new Money(0, ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]),
					'RATE' => $vatRate,
					'TAX_INCLUDED' => 'Y',
					'MODE' => Tax::MODE_VAT,
				];
			}

			$value = $taxes[$vatRate]['VALUE']->getValue();
			$value += $vatSum;
			$taxes[$vatRate]['VALUE'] = new Money($value, ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]);
			$netto = $taxes[$vatRate]['NETTO']->getValue();
			$netto += ($shipment->getPrice() - $value);
			$taxes[$vatRate]['NETTO'] = new Money($netto, ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]);
			$brutto = $taxes[$vatRate]['BRUTTO']->getValue();
			$brutto += $shipment->getPrice();
			$taxes[$vatRate]['BRUTTO'] = new Money($brutto, ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]);
		}

		return $taxes;
	}

	/**
	 * @return null|string
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected function getLocationId()
	{
		$order = $this->getOrder();

		if($order)
		{
			return $order->getTaxLocation();
		}

		return null;
	}

	/**
	 * @return array
	 */
	protected function loadProductsData()
	{
		$result = [];

		if(Loader::includeModule('sale'))
		{
			$dbRes = Internals\BasketTable::getList([
				'filter' => [
					'=ORDER_ID' => $this->source
				]
			]);

			while($basketItem = $dbRes->fetch())
			{
				$result[] = static::getProductProviderDataByBasketItem(
					$basketItem,
					new ItemIdentifier(
						(int)$this->getCrmOwnerType(),
						(int)$this->source
					),
					$this->getCurrencyId()
				);
			}
		}

		return $result;
	}

	public static function getProductProviderDataByBasketItem(array $basketItem, ItemIdentifier $owner, string $currencyId): array
	{
		return [
			'ID' => $basketItem['ID'],
			'OWNER_ID' => $owner->getEntityId(),
			'OWNER_TYPE' => $owner->getEntityTypeId(),
			'PRODUCT_ID' => $basketItem['PRODUCT_ID'] ?? 0,
			'NAME' => $basketItem['NAME'] ?? '',
			'PRICE' => $basketItem['PRICE'],
			'QUANTITY' => (float) ($basketItem['QUANTITY'] ?? 0),
			'DISCOUNT_TYPE_ID' => Discount::MONETARY,
			'DISCOUNT_SUM' => $basketItem['DISCOUNT_PRICE'],
			'PRICE_BRUTTO' => $basketItem['BASE_PRICE'],
			'DISCOUNT_RATE' => self::getDiscountRateByBasketItem($basketItem),
			'TAX_RATE' => self::getTaxRateByBasketItem($basketItem),
			'TAX_INCLUDED' => $basketItem['VAT_INCLUDED'] ?? 'N',
			'MEASURE_CODE' => $basketItem['MEASURE_CODE'] ?? '',
			'MEASURE_NAME' => $basketItem['MEASURE_NAME'] ?? '',
			'CUSTOMIZED' => $basketItem['CUSTOM_PRICE'] ?? 'N',
			'PRODUCT_VARIANT' => self::getProductVariantByBasketItem($basketItem),
			'CURRENCY_ID' => $currencyId,
		];
	}

	private static function getDiscountRateByBasketItem(array $basketItem) : float
	{
		$discountRate = 0;

		if (isset($basketItem['DISCOUNT_RATE']))
		{
			$discountRate = $basketItem['DISCOUNT_RATE'];
		}
		else if ($basketItem['BASE_PRICE'] > 0)
		{
			$discountRate = $basketItem['DISCOUNT_PRICE'] * 100 / $basketItem['BASE_PRICE'];
		}

		return $discountRate;
	}

	private static function getProductVariantByBasketItem(array $basketItem) : string
	{
		if (isset($basketItem['TYPE']) && (int)$basketItem['TYPE'] === BasketItem::TYPE_SERVICE)
		{
			return Dictionary\ProductVariant::SERVICE;
		}

		return Dictionary\ProductVariant::GOODS;
	}

	private static function getTaxRateByBasketItem(array $basketItem) :? float
	{
		if ($basketItem['VAT_RATE'] === null)
		{
			return null;
		}

		return $basketItem['VAT_RATE'] * 100;
	}

	/**
	 * @return int|null
	 */
	public function getCompanyId()
	{
		$order = $this->getOrder();
		if($order)
		{
			$company = $order->getContactCompanyCollection()->getPrimaryCompany();
			if($company)
			{
				return $company->getField('ENTITY_ID');
			}
		}

		return null;
	}

	/**
	 * @return int|null
	 */
	public function getContactId()
	{
		$order = $this->getOrder();
		if($order)
		{
			$contact = $order->getContactCompanyCollection()->getPrimaryContact();
			if($contact)
			{
				return $contact->getField('ENTITY_ID');
			}
		}

		return null;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentTypeException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getContacts()
	{
		if($this->contacts === null)
		{
			$this->contacts = [];
			$order = $this->getOrder();
			if($order)
			{
				$contacts = $order->getContactCompanyCollection()->getContacts();
				foreach($contacts as $contactInfo)
				{
					$contact = DataProviderManager::getInstance()->getDataProvider(Contact::class, $contactInfo->getField('ENTITY_ID'), [
						'isLightMode' => true,
						'DISABLE_MY_COMPANY' => true,
					], $this);
					$this->contacts[] = $contact;
				}
			}
		}

		return $this->contacts;
	}

	public function getPayments(): array
	{
		if($this->payments === null)
		{
			$this->payments = [];
			$order = $this->getOrder();
			if($order)
			{
				$payments = $order->getPaymentCollection();
				foreach($payments as $payment)
				{
					$this->payments[] = DataProviderManager::getInstance()->getDataProvider(
						Payment::class,
						$payment->getId(),
						[
							'isLightMode' => true,
							'data' => $payment->getFields()->getValues(),
						],
						$this
					);
				}
			}
		}

		return $this->payments;
	}

	public function getShipments(): array
	{
		if($this->shipments === null)
		{
			$this->shipments = [];
			$order = $this->getOrder();
			if($order)
			{
				$shipments = $order->getShipmentCollection()->getNotSystemItems();
				foreach($shipments as $shipment)
				{
					$this->shipments[] = DataProviderManager::getInstance()->getDataProvider(
						Shipment::class,
						$shipment->getId(),
						[
							'isLightMode' => true,
							'data' => $shipment->getFields()->getValues(),
						],
						$this
					);
				}
			}
		}

		return $this->shipments;
	}

	/**
	 * @return null|\Bitrix\Crm\Order\Order
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	protected function getOrder()
	{
		if($this->order === null && $this->isLoaded())
		{
			$this->order = \Bitrix\Crm\Order\Order::load($this->source);
		}

		return $this->order;
	}

	/**
	 * @return mixed
	 */
	protected function getUserFieldEntityID()
	{
		return Internals\OrderTable::getUfId();
	}

	/**
	 * @return array
	 */
	protected function getHiddenFields()
	{
		return array_merge(parent::getHiddenFields(), [
			'PAY_SYSTEM_ID',
			'DELIVERY_ID',
			'PERSON_TYPE_ID',
			'USER_ID',
			'CURRENCY_ID',
			'LOCATION_ID',
			'EMP_PAYED_ID',
			'EMP_DEDUCTED_ID',
			'STATUS_ID',
			'DATE_INSERT_SHORT',
			'DATE_INSERT_FORMAT',
			'DATE_UPDATE_SHORT',
			'DATE_STATUS_SHORT',
			'EMP_STATUS_ID',
			'EMP_STATUS_BY',
			'MARKED',
			'DATE_MARKED',
			'EMP_MARKED_ID',
			'EMP_MARKED_BY',
			'REASON_MARKED',
			'ALLOW_DELIVERY',
			'DATE_ALLOW_DELIVERY',
			'EMP_ALLOW_DELIVERY_ID',
			'RESERVED',
			'PRICE',
			'DISCOUNT_VALUE',
			'DISCOUNT_ALL',
			'TAX_VALUE',
			'SUM_PAID',
			'SUM_PAID_FORREP',
			'CREATED_BY',
			'CREATED_USER',
			'RESPONSIBLE_ID',
			'RESPONSIBLE_BY',
			'STAT_GID',
			'DATE_PAY_BEFORE',
			'DATE_BILL',
			'IS_RECURRING',
			'RECURRING_ID',
			'LOCKED_BY',
			'LOCK_USER',
			'DATE_LOCK',
			'LOCK_USER_NAME',
			'LOCK_STATUS',
			'USER_GROUP',
			'RESPONSIBLE',
			'BASKET',
			'BASKET_PRICE_TOTAL',
			'PAYMENT',
			'SHIPMENT',
			'PROPERTY',
			'RECOUNT_FLAG',
			'CURRENCY',
			'ADDITIONAL_INFO',
			'AFFILIATE_ID',
			'UPDATED_1C',
			'ORDER_TOPIC',
			'XML_ID',
			'ID_1C',
			'VERSION_1C',
			'VERSION',
			'EXTERNAL_ORDER',
			'STORE_ID',
			'EMP_CANCELED_ID',
			'DATE_CANCELED_SHORT',
			'EMP_CANCELED_BY',
			'BX_USER_ID',
			'RUNNING',
			'ORDER_COUPONS',
			'ORDER_DISCOUNT_DATA',
			'BY_RECOMMENDATION',
			'PRODUCTS_QUANT',
			'COMPANY_ID',
			'IS_SYNC_B24',
			'SEARCH_CONTENT',
			'ORDER_DISCOUNT_RULES',
			'TRADING_PLATFORM',
		]);
	}

	protected static function getProperties(): array
	{
		if(static::$properties === null)
		{
			static::$properties = [];

			$registry = Registry::getInstance(Registry::REGISTRY_TYPE_ORDER);
			/** @var PropertyBase $propertyClassName */
			$propertyClassName = $registry->getPropertyClassName();
			$orderProperties = $propertyClassName::getList([
				'filter' => [
					'ACTIVE' => 'Y',
				],
			]);
			while($property = $orderProperties->fetch())
			{
				$field = [
					'TITLE' => $property['NAME'],
				];
				if($property['IS_PHONE'] === 'Y')
				{
					$field['TYPE'] = static::FIELD_TYPE_PHONE;
				}
				static::$properties['PROPERTY_'.$property['ID']] = $field;
			}
		}

		return static::$properties;
	}

	public function getPropertyValue(string $placeholder)
	{
		if(!preg_match('#^PROPERTY_(\d+)$#', $placeholder, $matches))
		{
			return null;
		}
		$propertyId = (int) $matches[1];
		if($propertyId <= 0)
		{
			return null;
		}

		$order = $this->getOrder();
		$value = null;
		if($order)
		{
			$property = $order->getPropertyCollection()->getItemByOrderPropertyId($propertyId);
			if($property)
			{
				$value = $property->getValue();
				if (!empty($value) && $property->getType() === 'LOCATION')
				{
					$value = \CCrmLocations::getLocationStringByCode($value);
				}
			}
		}

		return $value;
	}

	public function getStatus(): ?string
	{
		$statusesList = OrderStatus::getListInCrmFormat();
		$statusId = $this->data['STATUS_ID'] ?? '';
		$status = $statusesList[$statusId] ?? [];

		return $status['NAME'] ?? null;
	}
}
