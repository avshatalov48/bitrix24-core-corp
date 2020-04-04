<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\QuoteTable;
use Bitrix\DocumentGenerator\Nameable;
use Bitrix\DocumentGenerator\Value\DateTime;

class Quote extends ProductsDataProvider implements Nameable
{
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
			$this->fields['ID'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_ID_TITLE'),];
			$this->fields['TITLE'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_TITLE_TITLE'),];
			$this->fields['OPPORTUNITY'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_OPPORTUNITY_TITLE'),];
			$this->fields['CURRENCY_ID'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_CURRENCY_ID_TITLE'),];
			$this->fields['LOCATION_ID'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_LOCATION_ID_TITLE'),];
			$this->fields['COMMENTS'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_COMMENTS_TITLE'), 'TYPE' => static::FIELD_TYPE_TEXT];
			$this->fields['BEGINDATE'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_BEGINDATE_TITLE'), 'TYPE' => DateTime::class];
			$this->fields['CLOSEDATE'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_CLOSEDATE_TITLE'), 'TYPE' => DateTime::class];
			$this->fields['DATE_CREATE'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_DATE_CREATE_TITLE'), 'TYPE' => DateTime::class];
			$this->fields['DATE_MODIFY'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_DATE_MODIFY_TITLE'), 'TYPE' => DateTime::class];
			$this->fields['CONTENT'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_CONTENT_TITLE'), 'TYPE' => static::FIELD_TYPE_TEXT];
			$this->fields['TERMS'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_TERM_TITLE'), 'TYPE' => static::FIELD_TYPE_TEXT];
	//		$this->fields['LEAD'] = [
	//			'PROVIDER' => Lead::class,
	//			'VALUE' => 'LEAD_ID',
	//			'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_LEAD_TITLE'),
	//		];
			$this->fields['DEAL'] = [
				'PROVIDER' => Deal::class,
				'VALUE' => 'DEAL_ID',
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_DEAL_TITLE'),
			];
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
	protected function getCrmProductOwnerType()
	{
		return \CCrmQuote::OWNER_TYPE;
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
			'BEGINDATE_SHORT',
			'CLOSEDATE_SHORT',
			'MYCOMPANY_ID',
		]);
	}
}