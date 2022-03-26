<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\CompanyAddress;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Nameable;

class Company extends CrmEntityDataProvider
{
	protected $bankDetailIds;
	protected $revenue;
	protected $contacts;

	public function getFields()
	{
		if($this->fields === null)
		{
			parent::getFields();
			$this->fields['TYPE'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_COMPANY_TYPE_TITLE'),
			];
			$this->fields['LOGO']['TYPE'] = static::FIELD_TYPE_IMAGE;
			$this->fields['INDUSTRY_TYPE'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_INDUSTRY_TYPE_TITLE'),
			];
			$this->fields['EMPLOYEES_NUM'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_EMPLOYEES_NUM_TITLE'),
			];
			$this->fields['EMAIL_HOME'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_EMAIL_HOME_TITLE'),
				'VALUE' => [$this, 'getHomeEmail'],
				'FORMAT' => [
					'mfirst' => true,
				],
			];
			$this->fields['EMAIL_WORK'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_EMAIL_WORK_TITLE'),
				'VALUE' => [$this, 'getWorkEmail'],
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
			$this->fields['PHONE_MOBILE'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PHONE_MOBILE_TITLE'),
				'TYPE' => 'PHONE',
				'VALUE' => [$this, 'getMobilePhone'],
				'FORMAT' => [
					'mfirst' => true,
				],
			];
			$this->fields['PHONE_WORK'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PHONE_WORK_TITLE'),
				'TYPE' => 'PHONE',
				'VALUE' => [$this, 'getWorkPhone'],
				'FORMAT' => [
					'mfirst' => true,
				],
			];
			$this->fields['PHONE_HOME'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PHONE_HOME_TITLE'),
				'TYPE' => 'PHONE',
				'VALUE' => [$this, 'getHomePhone'],
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
			$this->fields['PHONE']['TYPE'] = 'PHONE';
			$this->fields['PHONE']['FORMAT'] = ['mfirst' => true,];
			$this->fields['PHONE']['VALUE'] = [$this, 'getClientPhone'];
			$this->fields['EMAIL']['FORMAT'] = ['mfirst' => true,];
			$this->fields['EMAIL']['VALUE'] = [$this, 'getClientEmail'];
			$this->fields['IMOL'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_IMOL_TITLE'),
			];
			$this->fields['WEB'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_COMPANY_WEB_TITLE'),
				'VALUE' => [$this, 'getClientWeb'],
				'FORMAT' => [
					'mfirst' => true,
				],
			];
			$this->fields['REVENUE']['TYPE'] = Money::class;
			$this->fields['REVENUE']['VALUE'] = [$this, 'getRevenue'];
			$this->fields['ADDRESS']['VALUE'] = [$this, 'getAddress'];
			$this->fields['ADDRESS']['TYPE'] = \Bitrix\Crm\Integration\DocumentGenerator\Value\Address::class;
			$this->fields['ADDRESS_LEGAL']['VALUE'] = [$this, 'getRegisteredAddress'];
			$this->fields['ADDRESS_LEGAL']['TYPE'] = \Bitrix\Crm\Integration\DocumentGenerator\Value\Address::class;
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
			}

			if($this->isMyCompany())
			{
				$myCompanyFields = $this->getMyCompanyFields();
				foreach($this->fields as $placeholder => $field)
				{
					if(isset($myCompanyFields[$placeholder]))
					{
						$this->fields[$placeholder] = array_merge($this->fields[$placeholder], $myCompanyFields[$placeholder]);
					}
				}
				$this->fields['REQUISITE']['TITLE'] = GetMessage('CRM_DOCGEN_DATAPROVIDER_MY_COMPANY_REQUISITE_TITLE');
				$this->fields['BANK_DETAIL']['TITLE'] = GetMessage('CRM_DOCGEN_DATAPROVIDER_MY_COMPANY_BANK_DETAIL_TITLE');
			}

			if($this->isMyCompany() || isset($this->getOptions()['DISABLE_MY_COMPANY']))
			{
				unset($this->fields['MY_COMPANY']);
				unset($this->fields['CLIENT_PHONE']);
				unset($this->fields['CLIENT_EMAIL']);
				unset($this->fields['CLIENT_WEB']);
			}

			unset($this->fields['COMPANY']);
			unset($this->fields['CONTACT']);
		}

		return $this->fields;
	}

	protected function fetchData()
	{
		parent::fetchData();
		// a hack to load address from requisites.
		$isOutmodedAddressesEnabled = ((new \Bitrix\Crm\Settings\CompanySettings())->areOutmodedRequisitesEnabled());
		if(empty($this->data['ADDRESS']))
		{
			unset($this->data['ADDRESS']);
			if($isOutmodedAddressesEnabled)
			{
				$address = CompanyAddress::getByOwner(CompanyAddress::Primary, $this->getCrmOwnerType(), $this->source);
				if($address)
				{
					$this->data['ADDRESS'] = new \Bitrix\Crm\Integration\DocumentGenerator\Value\Address($address);
				}
			}
		}
		if(empty($this->data['ADDRESS_LEGAL']))
		{
			unset($this->data['ADDRESS_LEGAL']);
			if($isOutmodedAddressesEnabled)
			{
				$address = CompanyAddress::getByOwner(CompanyAddress::Registered, $this->getCrmOwnerType(), $this->source);
				if($address)
				{
					$this->data['ADDRESS_LEGAL'] = new \Bitrix\Crm\Integration\DocumentGenerator\Value\Address($address);
				}
			}
		}
		if(!$this->revenue)
		{
			$this->revenue = $this->data['REVENUE'];
			unset($this->data['REVENUE']);
		}
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		return GetMessage('CRM_DOCGEN_DATAPROVIDER_COMPANY_TITLE');
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
				$elementId = $this->source;

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

	/**
	 * @param int $userId
	 * @return bool
	 */
	public function hasAccess($userId)
	{
		if($this->isLoaded())
		{
			$userPermissions = new \CCrmPerms($userId);
			return \CCrmCompany::CheckReadPermission($this->source, $userPermissions);
		}

		return false;
	}

	protected function getModuleId()
	{
		return 'crm';
	}

	protected function getTableClass()
	{
		return CompanyTable::class;
	}

	/**
	 * @return array
	 */
	protected function getHiddenFields()
	{
		$fields = array_merge(parent::getHiddenFields(), [
			'COMPANY_TYPE',
			'COMPANY_TYPE_BY',
			'INDUSTRY',
			'INDUSTRY_BY',
			'EMPLOYEES',
			'EMPLOYEES_BY',
			'ASSIGNED_BY_ID',
			'ASSIGNED_BY',
			'CREATED_BY_ID',
			'CREATED_BY',
			'MODIFY_BY_ID',
			'MODIFY_BY',
			'EVENT_RELATION',
			'IS_MY_COMPANY',
			'SEARCH_CONTENT',
			'HAS_EMAIL',
			'HAS_PHONE',
			'HAS_IMOL',
			'LEAD_ID',
			'BANKING_DETAILS',
		]);

		if(!$this->isMyCompany())
		{
			$fields = array_merge($fields, array_keys($this->getMyCompanyFields()));
		}

		return $fields;
	}

	/**
	 * @return array
	 */
	protected function getGetListParameters()
	{
		return array_merge_recursive(parent::getGetListParameters(), [
			'select' => [
				'TYPE' => 'COMPANY_TYPE_BY.NAME',
				'INDUSTRY_TYPE' => 'INDUSTRY_BY.NAME',
				'EMPLOYEES_NUM' => 'EMPLOYEES_BY.NAME',
				'IMOL',
			],
		]);
	}

	/**
	 * @return bool
	 */
	protected function isMyCompany()
	{
		return (isset($this->options['MY_COMPANY']) && $this->options['MY_COMPANY'] === 'Y');
	}

	/**
	 * @return array
	 */
	protected function getMyCompanyFields()
	{
		static $result = null;
		if($result === null)
		{
			$result = [];
			$fields = \CCrmCompany::getMyCompanyAdditionalUserFields();
			foreach($fields as $name => $field)
			{
				if($name == 'UF_LOGO')
				{
					$type = static::FIELD_TYPE_IMAGE;
				}
				else
				{
					$type = static::FIELD_TYPE_STAMP;
				}
				$result[$name] = [
					'TITLE' => $field['EDIT_FORM_LABEL'][LANGUAGE_ID],
					'TYPE' => $type,
				];
			}
		}

		return $result;
	}

	/**
	 * @return int
	 */
	public function getCrmOwnerType()
	{
		return \CCrmOwnerType::Company;
	}

	/**
	 * @return int|null
	 */
	public function getCompanyId()
	{
		return $this->source;
	}

	/**
	 * @return int|null
	 */
	public function getContactId()
	{
		return null;
	}

	/**
	 * @return Money
	 */
	public function getRevenue()
	{
		$this->fetchData();
		return new Money($this->revenue, ['CURRENCY_ID' => $this->data['CURRENCY_ID']]);
	}

	/**
	 * @return string
	 */
	protected function getUserFieldEntityID()
	{
		return \CCrmCompany::GetUserFieldEntityID();
	}

	protected function getCrmUserTypeManager()
	{
		global $USER_FIELD_MANAGER;
		return new \CCrmUserType($USER_FIELD_MANAGER, $this->getUserFieldEntityID(), ['isMyCompany' => $this->isMyCompany()]);
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
				$contactBindings = \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyBindings($this->source);
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

	/**
	 * @return bool
	 */
	protected function hasLeadField()
	{
		return true;
	}
}