<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\Binding\LeadContactTable;
use Bitrix\Crm\LeadAddress;
use Bitrix\Crm\LeadTable;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Nameable;

class Lead extends ProductsDataProvider implements Nameable
{
	protected $contacts;

	/**
	 * @return array
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			parent::getFields();
			$this->fields['STATUS'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_LEAD_STATUS_TITLE'),];
			$this->fields['SOURCE'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_LEAD_SOURCE_TITLE'),];
			$this->fields['IMOL'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_LEAD_IMOL_TITLE'),
				'VALUE' => [$this, 'getClientIm'],
				'FORMAT' => [
					'mfirst' => true,
				],
			];
			$this->fields['PHONE_ANOTHER'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PHONE_ANOTHER_TITLE'),
				'VALUE' => [$this, 'getAnotherPhone'],
				'TYPE' => 'PHONE',
				'FORMAT' => [
					'mfirst' => true,
				],
			];
			$this->fields['EMAIL_ANOTHER'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_EMAIL_ANOTHER_TITLE'),
				'VALUE' => [$this, 'getAnotherEmail'],
				'FORMAT' => [
					'mfirst' => true,
				],
			];
			$this->fields['COMPANY_NAME'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_LEAD_COMPANY_NAME_TITLE'),
				'VALUE' => 'COMPANY_TITLE',
			];

			$this->fields['PHONE_MOBILE']['TYPE'] = 'PHONE';
			$this->fields['PHONE_MOBILE']['FORMAT'] = ['mfirst' => true,];
			$this->fields['PHONE_MOBILE']['VALUE'] = [$this, 'getMobilePhone'];
			$this->fields['PHONE_WORK']['TYPE'] = 'PHONE';
			$this->fields['PHONE_WORK']['FORMAT'] = ['mfirst' => true,];
			$this->fields['PHONE_WORK']['VALUE'] = [$this, 'getWorkPhone'];
			$this->fields['PHONE_HOME']['TITLE'] = GetMessage('CRM_DOCGEN_DATAPROVIDER_LEAD_PHONE_HOME_TITLE');
			$this->fields['PHONE_HOME']['TYPE'] = 'PHONE';
			$this->fields['PHONE_HOME']['FORMAT'] = ['mfirst' => true,];
			$this->fields['PHONE_HOME']['VALUE'] = [$this, 'getHomePhone'];
			$this->fields['PHONE']['TYPE'] = 'PHONE';
			$this->fields['PHONE']['FORMAT'] = ['mfirst' => true,];
			$this->fields['PHONE']['VALUE'] = [$this, 'getClientPhone'];

			$this->fields['EMAIL_HOME']['FORMAT'] = ['mfirst' => true,];
			$this->fields['EMAIL_HOME']['VALUE'] = [$this, 'getHomeEmail'];
			$this->fields['EMAIL_WORK']['FORMAT'] = ['mfirst' => true,];
			$this->fields['EMAIL_WORK']['VALUE'] = [$this, 'getWorkEmail'];
			$this->fields['EMAIL']['FORMAT'] = ['mfirst' => true,];
			$this->fields['EMAIL']['VALUE'] = [$this, 'getClientEmail'];
			$this->fields['BIRTHDATE']['TITLE'] = GetMessage('CRM_DOCGEN_DATAPROVIDER_BIRTHDATE_TITLE');
			$this->fields['CONTACTS'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_LEAD_CONTACTS_TITLE'),
				'PROVIDER' => ArrayDataProvider::class,
				'OPTIONS' => [
					'ITEM_PROVIDER' => Contact::class,
					'ITEM_NAME' => 'CONTACT',
					'ITEM_TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_LEAD_CONTACT_TITLE'),
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
	 * @return string
	 */
	public static function getLangName()
	{
		return GetMessage('CRM_DOCGEN_DATAPROVIDER_LEAD_TITLE');
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
			return \CCrmLead::CheckReadPermission($this->source, $userPermissions);
		}

		return false;
	}

	/**
	 * @return string
	 */
	protected function getTableClass()
	{
		return LeadTable::class;
	}

	public function getContacts()
	{
		if($this->contacts === null)
		{
			$this->contacts = [];
			if(intval($this->source) > 0)
			{
				$contactBindings = LeadContactTable::getLeadBindings($this->source);
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

	protected function fetchData()
	{
		parent::fetchData();
		if(empty($this->data['ADDRESS']))
		{
			unset($this->data['ADDRESS']);
		}
		else
		{
			$address = LeadAddress::getByOwner(LeadAddress::Primary, $this->getCrmOwnerType(), $this->source);
			if($address)
			{
				$this->data['ADDRESS'] = new \Bitrix\Crm\Integration\DocumentGenerator\Value\Address($address);
			}
		}
	}


	/**
	 * @return array
	 */
	protected function getHiddenFields()
	{
		return array_merge(parent::getHiddenFields(), [
			'STATUS_ID',
			'STATUS_BY',
			'IS_CONVERT',
			'SHORT_NAME',
			'LOGIN',
			'SOURCE_ID',
			'SOURCE_BY',
			'ASSIGNED_BY_ID',
			'ASSIGNED_BY',
			'CREATED_BY_ID',
			'CREATED_BY',
			'MODIFY_BY_ID',
			'MODIFY_BY',
			'EVENT_RELATION',
			'HAS_EMAIL',
			'HAS_PHONE',
			'HAS_IMOL',
			'SEARCH_CONTENT',
			'DATE_CREATE_SHORT',
			'DATE_MODIFY_SHORT',
			'FACE_ID',
			'COMPANY_TITLE',
			'OPPORTUNITY_ACCOUNT',
			'ACCOUNT_CURRENCY_ID',
			'IS_RETURN_CUSTOMER',
			'STATUS_SEMANTIC_ID',
			'ORIGIN_ID',
			'ORIGINATOR_ID',
			'ADDRESS_ENTITY',
			'PRODUCT_ROW',
			'COMPANY_ID',
			'CONTACT_ID',
		]);
	}

	/**
	 * @return array
	 */
	protected function getGetListParameters()
	{
		return array_merge_recursive(parent::getGetListParameters(), [
			'select' => [
				'STATUS' => 'STATUS_BY.NAME',
				'SOURCE' => 'SOURCE_BY.NAME',
				'SKYPE',
				'ICQ',
			],
		]);
	}

	/**
	 * @return int
	 */
	public function getCrmOwnerType()
	{
		return \CCrmOwnerType::Lead;
	}

	/**
	 * @return string
	 */
	protected function getCrmProductOwnerType()
	{
		return 'L';
	}

	/**
	 * @return string
	 */
	protected function getUserFieldEntityID()
	{
		return \CCrmLead::GetUserFieldEntityID();
	}

	/**
	 * @return array
	 */
	protected function loadMultiFields()
	{
		$result = [];
		if($this->isLoaded())
		{
			if($this->multiFields === null)
			{
				$this->multiFields = [];

				$entityId = \CCrmOwnerType::CompanyName;
				$elementId = $this->getCompanyId();
				if(!$elementId)
				{
					$elementId = $this->getContactId();
					$entityId = \CCrmOwnerType::ContactName;
				}
				if(!$elementId)
				{
					$elementId = $this->source;
					$entityId = \CCrmOwnerType::LeadName;
				}

				if($elementId > 0)
				{
					$multiFieldDbResult = \CCrmFieldMulti::GetList(
						['ID' => 'asc'],
						[
							'ENTITY_ID' => $entityId,
							'ELEMENT_ID' => $elementId,
						]
					);
					while($multiField = $multiFieldDbResult->Fetch())
					{
						$this->multiFields[$multiField['TYPE_ID']][] = $multiField;
					}
				}
			}
			$result = $this->multiFields;
		}

		return $result;
	}
}