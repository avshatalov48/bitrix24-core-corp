<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\Binding\QuoteContactTable;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\Item;
use Bitrix\Crm\QuoteTable;
use Bitrix\Crm\Service\Container;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Value\DateTime;

class Quote extends ProductsDataProvider
{
	protected $contacts;

	/**
	 * Returns list of value names for this Provider.
	 *
	 * @return array
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			parent::getFields();
			$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Quote);
			$factoryFieldsInfo = $factory ? $factory->getFieldsInfo() : [];
			$this->fields['ID'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_ID_TITLE'),];
			$this->fields['TITLE'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_TITLE_TITLE'),];
			$this->fields['OPPORTUNITY'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_OPPORTUNITY_TITLE'),
				'TYPE' => Money::class,
				'FORMAT' => ['CURRENCY_ID' => $this->getCurrencyId()],
			];
			$this->fields['CURRENCY_ID'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_CURRENCY_ID_TITLE'),];
			$this->fields['LOCATION_ID'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_LOCATION_ID_TITLE'),];
			$this->fields['COMMENTS'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_COMMENTS_TITLE'), 'TYPE' => static::FIELD_TYPE_TEXT];
			$this->fields['BEGINDATE'] = [
				'TITLE' => $factoryFieldsInfo['BEGINDATE']['TITLE'] ?? GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_BEGINDATE_TITLE'),
				'TYPE' => DateTime::class,
			];
			$this->fields['CLOSEDATE'] = [
				'TITLE' => $factoryFieldsInfo['CLOSEDATE']['TITLE'] ?? GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_CLOSEDATE_TITLE'),
				'TYPE' => DateTime::class,
			];
			$this->fields['DATE_CREATE'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_DATE_CREATE_TITLE'), 'TYPE' => DateTime::class];
			$this->fields['DATE_MODIFY'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_DATE_MODIFY_TITLE'), 'TYPE' => DateTime::class];
			$this->fields['CONTENT'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_CONTENT_TITLE'), 'TYPE' => static::FIELD_TYPE_TEXT];
			$this->fields['TERMS'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_TERM_TITLE'), 'TYPE' => static::FIELD_TYPE_TEXT];
			$this->fields['DEAL'] = [
				'PROVIDER' => Deal::class,
				'VALUE' => 'DEAL_ID',
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_DEAL_TITLE'),
			];
			if (!$this->isLightMode())
			{
				$this->fields['CONTACTS'] = [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_CONTACTS_TITLE'),
					'PROVIDER' => ArrayDataProvider::class,
					'OPTIONS' => [
						'ITEM_PROVIDER' => Contact::class,
						'ITEM_NAME' => 'CONTACT',
						'ITEM_TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_CONTACT_TITLE'),
						'ITEM_OPTIONS' => [
							'DISABLE_MY_COMPANY' => true,
							'isLightMode' => true,
						],
					],
					'VALUE' => [$this, 'getContacts'],
				];
			}
		}

		return $this->fields;
	}

	/**
	 * Fill $this->data.
	 */
	protected function fetchData()
	{
		if($this->data === null)
		{
			$this->data = [];
			$data = \CCrmQuote::GetByID($this->source);
			if($data)
			{
				$this->data = $data;
			}
		}
		if(!$this->isLightMode())
		{
			$this->loadProducts();
			$this->calculateTotalFields();
		}
	}

	/**
	 * @param int $userId
	 * @return boolean
	 */
	public function hasAccess($userId)
	{
		if($this->isLoaded())
		{
			$userPermissions = new \CCrmPerms($userId);

			return \CCrmQuote::CheckReadPermission(
				$this->source,
				$userPermissions
			);
		}

		return false;
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		return GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_TITLE');
	}

	/**
	 * @return string
	 */
	protected function getTableClass()
	{
		return QuoteTable::class;
	}

	/**
	 * @return int
	 */
	public function getCrmOwnerType()
	{
		return \CCrmOwnerType::Quote;
	}

	/**
	 * @param null $defaultMyCompanyId
	 * @return array|int
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getMyCompanyId($defaultMyCompanyId = null)
	{
		return parent::getMyCompanyId($this->data['MYCOMPANY_ID']);
	}

	/**
	 * @return string
	 */
	protected function getUserFieldEntityID()
	{
		return \CCrmQuote::GetUserFieldEntityID();
	}

	/**
	 * @return array
	 */
	protected function getHiddenFields()
	{
		return array_merge(parent::getHiddenFields(), [
			'STATUS_ID',
			'BEGINDATE_SHORT',
			'CLOSEDATE_SHORT',
			'MYCOMPANY_ID',
			'DATE_CREATE_SHORT',
			'DATE_MODIFY_SHORT',
			'CREATED_BY',
			'MODIFY_BY',
			'ASSIGNED_BY',
			'LEAD_BY',
		]);
	}

	/**
	 * @return bool
	 */
	protected function hasLeadField()
	{
		return true;
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
				$contactBindings = QuoteContactTable::getQuoteBindings($this->source);
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

	protected function getStatusEntityId(): ?string
	{
		return 'QUOTE_STATUS';
	}
}