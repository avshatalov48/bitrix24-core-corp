<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\DealTable;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProvider\Filterable;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Nameable;

class Deal extends ProductsDataProvider implements Nameable, Filterable
{
	protected $contacts;

	public function getFields()
	{
		if($this->fields === null)
		{
			parent::getFields();
			$this->fields['STAGE'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_STAGE_TITLE'),
			];
			$this->fields['CATEGORY'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_CATEGORY_TITLE'),
				'VALUE' => [$this, 'getCategory'],
			];
			$this->fields['CATEGORY_ID']['TITLE'] = GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_CATEGORY_ID_TITLE');
			$this->fields['TYPE'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_TYPE_TITLE'),
			];
			$this->fields['EVENT'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_EVENT_TITLE'),
			];
			$this->fields['SOURCE'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_SOURCE_TITLE'),
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
			$this->fields['OPPORTUNITY']['TYPE'] = Money::class;
			$this->fields['OPPORTUNITY']['FORMAT'] = ['CURRENCY_ID' => $this->getCurrencyId()];
		}

		return $this->fields;
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		return GetMessage('CRM_DOCGEN_DATAPROVIDER_DEAL_TITLE');
	}

	/**
	 * @param int $userId
	 * @return bool
	 */
	public function hasAccess($userId)
	{
		if($this->isLoaded())
		{
			$userPermissions = new \CCrmPerms($userId);

			return \CCrmDeal::CheckReadPermission(
				$this->source,
				$userPermissions
			);
		}

		return false;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getContacts()
	{
		if($this->contacts === null)
		{
			$this->contacts = [];
			if(intval($this->source) > 0)
			{
				$contactBindings = DealContactTable::getDealBindings($this->source);
				foreach($contactBindings as $binding)
				{
					$contact = DataProviderManager::getInstance()->getDataProvider(Contact::class, $binding['CONTACT_ID'], [
						'isLightMode' => true,
						'DISABLE_MY_COMPANY' => true,
					], $this);
					$this->contacts[] = $contact;
				}
			}
		}

		return $this->contacts;
	}

	public function getCategory()
	{
		return DealCategory::getName($this->getRawValue('CATEGORY_ID'));
	}

	protected function getTableClass()
	{
		return DealTable::class;
	}

	/**
	 * @return array
	 */
	protected function getHiddenFields()
	{
		return array_merge(parent::getHiddenFields(), [
			'EXCH_RATE',
			'STAGE_ID',
			'STAGE_BY',
			'CLOSED',
			'IS_RECURRING',
			'TYPE_ID',
			'TYPE_BY',
			'EVENT_ID',
			'EVENT_BY',
			'BEGINDATE_SHORT',
			'DATE_CREATE_SHORT',
			'CLOSEDATE_SHORT',
			'DATE_MODIFY_SHORT',
			'EVENT_DATE_SHORT',
			'ASSIGNED_BY_ID',
			'ASSIGNED_BY',
			'CREATED_BY_ID',
			'CREATED_BY',
			'MODIFY_BY_ID',
			'MODIFY_BY',
			'EVENT_RELATION',
			'LEAD_BY',
			'CONTACT_ID',
			'CONTACT_BY',
			'COMPANY_ID',
			'COMPANY_BY',
			'IS_WORK',
			'IS_WON',
			'IS_LOSE',
			'HAS_PRODUCTS',
			'SEARCH_CONTENT',
			'ORIGIN_ID',
			'ORIGINATOR_ID',
			'ORIGINATOR_BY',
			'STAGE_SEMANTIC_ID',
		]);
	}

	/**
	 * @return array
	 */
	protected function getGetListParameters()
	{
		return array_merge_recursive(parent::getGetListParameters(), [
			'select' => [
				'STAGE' => 'STAGE_BY.NAME',
				'TYPE' => 'TYPE_BY.NAME',
				'EVENT' => 'EVENT_BY.NAME',
				'SOURCE' => 'SOURCE_BY.NAME',
			],
		]);
	}

	/**
	 * @return int
	 */
	public function getCrmOwnerType()
	{
		return \CCrmOwnerType::Deal;
	}

	protected function getCrmProductOwnerType()
	{
		return 'D';
	}

	/**
	 * @return string
	 */
	protected function getUserFieldEntityID()
	{
		return \CCrmDeal::GetUserFieldEntityID();
	}

	/**
	 * @param array $category
	 * @return array
	 */
	public static function getExtendedProviderByCategory(array $category)
	{
		return [
			'NAME' => static::getLangName().' ('.$category['NAME'].')',
			'PROVIDER' => strtolower(static::class).'_category_'.$category['ID'],
		];
	}

	/**
	 * @return array
	 */
	public static function getExtendedList()
	{
		static $list = false;
		if($list === false)
		{
			$list = [];

			$categories = DealCategory::getAll(true);
			foreach($categories as $category)
			{
				$list[] = static::getExtendedProviderByCategory($category);
			}
		}

		return $list;
	}

	/**
	 * @return string
	 */
	public function getFilterString()
	{
		if($this->isLoaded())
		{
			$categoryId = $this->getValue('CATEGORY_ID');
			return static::class.'_category_'.$categoryId;
		}

		return static::class.'_%';
	}

	/**
	 * @return bool
	 */
	protected function hasLeadField()
	{
		return true;
	}
}