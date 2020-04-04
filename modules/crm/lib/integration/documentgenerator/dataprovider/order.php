<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\Discount;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\Requisite\EntityLink;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProvider\User;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals;
use Bitrix\DocumentGenerator\Nameable;

class Order extends ProductsDataProvider implements Nameable
{
	protected $order;
	protected $contacts;

	public function getFields()
	{
		if($this->fields === null)
		{
			parent::getFields();
			Loc::loadMessages(__FILE__);
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
			$this->fields['USER_DESCRIPTION']['TITLE'] = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_USER_DESCRIPTION_TITLE');
			$this->fields['COMMENTS']['TITLE'] = Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_COMMENTS_TITLE');
			$this->fields['COMPANY']['OPTIONS'] = [
				'DISABLE_MY_COMPANY' => true,
				'VALUES' => [
					'REQUISITE' => $this->getBuyerRequisiteId(),
					'BANK_DETAIL' => $this->getBuyerBankDetailId(),
				]
			];
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
		}

		return $this->fields;
	}

	/**
	 * @return int|string
	 */
	public function getAssignedId()
	{
		return $this->data['RESPONSIBLE_ID'];
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
		if($requisiteId > 0)
		{
			return $requisiteId;
		}
		if($this->isLoaded())
		{
			$linkData = $this->getLinkData();
			if($linkData['REQUISITE_ID'] > 0)
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

		if($this->isLoaded())
		{
			$linkData = $this->getLinkData();
			$sellerBankDetailId = $linkData['MC_BANK_DETAIL_ID'];
		}
		return $sellerBankDetailId;
	}

	/**
	 * @return BankDetail|null
	 */
	protected function getBuyerBankDetailId()
	{
		$buyerBankDetailId = 0;

		if($this->isLoaded())
		{
			$linkData = $this->getLinkData();
			$buyerBankDetailId = $linkData['BANK_DETAIL_ID'];
		}
		return $buyerBankDetailId;
	}

	/**
	 * @param int $userId
	 * @return bool
	 */
	public function hasAccess($userId)
	{
		return true;
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

	protected function getCrmProductOwnerType()
	{
		return 'O';
	}

	protected function getPersonTypeID()
	{
		return $this->getValue('PERSON_TYPE_ID');
	}

	public function getCurrencyId()
	{
		return $this->data['CURRENCY'];
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
		}

		$taxes = $this->loadTaxes();
		foreach($taxes as $tax)
		{
			$this->data['TOTAL_TAX'] += $tax->getRawValue('VALUE');
		}

		$this->data['TOTAL_DISCOUNT'] = $order->getDiscountPrice();
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
			$taxes = $order->getTax()->getTaxList();
			foreach($taxes as $taxInfo)
			{
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

			while($product = $dbRes->fetch())
			{
				$result[] = [
					'OWNER_ID' => $this->source,
					'OWNER_TYPE' => $this->getCrmProductOwnerType(),
					'PRODUCT_ID' => isset($product['PRODUCT_ID']) ? $product['PRODUCT_ID'] : 0,
					'NAME' => isset($product['NAME']) ? $product['NAME'] : '',
					'PRICE' => $product['PRICE'],
					'QUANTITY' => isset($product['QUANTITY']) ? $product['QUANTITY'] : 0,
					'DISCOUNT_TYPE_ID' => Discount::MONETARY,
					'DISCOUNT_SUM' => $product['DISCOUNT_PRICE'],
					'TAX_RATE' => $product['VAT_RATE'] * 100,
					'TAX_INCLUDED' => isset($product['VAT_INCLUDED']) ? $product['VAT_INCLUDED'] : 'N',
					'MEASURE_CODE' => isset($product['MEASURE_CODE']) ? $product['MEASURE_CODE'] : '',
					'MEASURE_NAME' => isset($product['MEASURE_NAME']) ? $product['MEASURE_NAME'] : '',
					'CUSTOMIZED' => isset($product['CUSTOM_PRICE']) ? $product['CUSTOM_PRICE'] : 'N',
					'CURRENCY_ID' => $this->getCurrencyId(),
				];
			}
		}

		return $result;
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
		]);
	}
}