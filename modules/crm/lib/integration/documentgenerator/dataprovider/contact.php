<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\ContactTable;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Value\Name;

class Contact extends CrmEntityDataProvider
{
	protected $bankDetailIds;
	protected $nameData = [];

	protected $companies;

	/**
	 * @return array
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			parent::getFields();
			$this->fields['HONORIFIC_NAME'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_CONTACT_HONORIFIC_TITLE'),
				'VALUE' => [$this, 'getHonorificName'],
			];
			$this->fields['FORMATTED_NAME'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_FORMATTED_NAME_TITLE'),
				'VALUE' => [$this, 'getFormattedName'],
			];
			$this->fields['ADDRESS']['VALUE'] = [$this, 'getAddress'];
			$this->fields['ADDRESS']['TYPE'] = \Bitrix\Crm\Integration\DocumentGenerator\Value\Address::class;
			$this->fields['TYPE'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_CONTACT_TYPE_TITLE'),];
			$this->fields['SOURCE'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_CONTACT_SOURCE_TITLE'),];
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
			$this->fields['EMAIL_ANOTHER'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_EMAIL_ANOTHER_TITLE'),
				'VALUE' => [$this, 'getAnotherEmail'],
				'FORMAT' => [
					'mfirst' => true,
				],
			];
			$this->fields['IMOL'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_IMOL_TITLE'),
				'VALUE' => [$this, 'getClientIm'],
				'FORMAT' => [
					'mfirst' => true,
				],
			];
			$this->fields['NAME']['VALUE'] = [$this, 'getNameData'];
			$this->fields['NAME']['TYPE'] = static::FIELD_TYPE_NAME;
			$this->fields['NAME']['FORMAT'] = ['format' => '#NAME#'];

			$this->fields['SECOND_NAME']['VALUE'] = [$this, 'getNameData'];
			$this->fields['SECOND_NAME']['TYPE'] = static::FIELD_TYPE_NAME;
			$this->fields['SECOND_NAME']['FORMAT'] = ['format' => '#SECOND_NAME#'];

			$this->fields['LAST_NAME']['VALUE'] = [$this, 'getNameData'];
			$this->fields['LAST_NAME']['TYPE'] = static::FIELD_TYPE_NAME;
			$this->fields['LAST_NAME']['FORMAT'] = ['format' => '#LAST_NAME#'];

			$this->fields['PHOTO']['TITLE'] = GetMessage('CRM_DOCGEN_DATAPROVIDER_PHOTO_TITLE');
			$this->fields['PHOTO']['TYPE'] = static::FIELD_TYPE_IMAGE;

			$this->fields['PHONE']['TYPE'] = 'PHONE';
			$this->fields['PHONE']['FORMAT'] = ['mfirst' => true,];
			$this->fields['PHONE']['VALUE'] = [$this, 'getClientPhone'];
			$this->fields['EMAIL']['FORMAT'] = ['mfirst' => true,];
			$this->fields['EMAIL']['VALUE'] = [$this, 'getClientEmail'];

			if (!$this->isLightMode())
			{
				$this->fields['COMPANIES'] = [
					'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_COMPANIES_TITLE'),
					'PROVIDER' => ArrayDataProvider::class,
					'OPTIONS' => [
						'ITEM_PROVIDER' => Company::class,
						'ITEM_NAME' => 'COMPANY',
						'ITEM_TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_COMPANY_TITLE'),
						'ITEM_OPTIONS' => [
							'DISABLE_MY_COMPANY' => true,
							'isLightMode' => true,
						],
					],
					'VALUE' => [$this, 'getCompanies'],
				];
			}
			$this->fields['BIRTHDATE']['TITLE'] = GetMessage('CRM_DOCGEN_DATAPROVIDER_BIRTHDATE_TITLE');

			if(isset($this->getOptions()['DISABLE_MY_COMPANY']))
			{
				unset($this->fields['MY_COMPANY']);
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
		if(empty($this->data['ADDRESS']))
		{
			unset($this->data['ADDRESS']);
		}
		else
		{
			$address = ContactAddress::getByOwner(ContactAddress::Primary, $this->getCrmOwnerType(), $this->source);
			if($address)
			{
				$this->data['ADDRESS'] = new \Bitrix\Crm\Integration\DocumentGenerator\Value\Address($address);
			}
		}
		$this->nameData = [
			'TITLE' => $this->getHonorificName(),
			'NAME' => $this->data['NAME'] ?? '',
			'SECOND_NAME' => $this->data['SECOND_NAME'] ?? '',
			'LAST_NAME' => $this->data['LAST_NAME'] ?? '',
		];
		unset($this->data['NAME']);
		unset($this->data['SECOND_NAME']);
		unset($this->data['LAST_NAME']);
	}

	/**
	 * @return string
	 */
	public static function getLangName()
	{
		return GetMessage('CRM_DOCGEN_DATAPROVIDER_CONTACT_TITLE');
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
			return \CCrmContact::CheckReadPermission($this->source, $userPermissions);
		}

		return false;
	}

	/**
	 * @return string
	 */
	protected function getTableClass()
	{
		return ContactTable::class;
	}

	/**
	 * @return array
	 */
	protected function getHiddenFields()
	{
		return array_merge(parent::getHiddenFields(), [
			'LOGIN',
			'TYPE_ID',
			'TYPE_BY',
			'SOURCE_ID',
			'SOURCE_BY',
			'ASSIGNED_BY_ID',
			'ASSIGNED_BY',
			'CREATED_BY_ID',
			'CREATED_BY',
			'MODIFY_BY_ID',
			'MODIFY_BY',
			'EVENT_RELATION',
			'FACE_ID',
			'HAS_EMAIL',
			'HAS_PHONE',
			'HAS_IMOL',
			'SEARCH_CONTENT',
			'HONORIFIC',
			'COMPANY_ID',
			'FULL_NAME',
		]);
	}

	/**
	 * @return array
	 */
	protected function getGetListParameters()
	{
		return array_merge_recursive(parent::getGetListParameters(), [
			'select' => [
				'SHORT_NAME',
				'TYPE' => 'TYPE_BY.NAME',
				'SOURCE' => 'SOURCE_BY.NAME',
			],
		]);
	}

	/**
	 * @return int
	 */
	public function getCrmOwnerType()
	{
		return \CCrmOwnerType::Contact;
	}

	/**
	 * @return int|null
	 */
	public function getCompanyId()
	{
		return null;
	}

	/**
	 * @return int|null
	 */
	public function getContactId()
	{
		return $this->source;
	}

	/**
	 * @return string
	 */
	protected function getUserFieldEntityID()
	{
		return \CCrmContact::GetUserFieldEntityID();
	}

	/**
	 * @return string
	 */
	public function getHonorificName()
	{
		$value = null;

		$honorific = ($this->data['HONORIFIC'] ?? null);

		if ($honorific)
		{
			$all = \CCrmStatus::GetStatusList('HONORIFIC');
			$value = $all[$honorific];
		}

		return (string)$value;
	}

	/**
	 * @return string
	 */
	public function getFormattedName()
	{
		return new Name($this->getNameData(), ['format' => $this->getNameFormat()]);
	}

	/**
	 * @return array
	 */
	public function getNameData()
	{
		return $this->nameData;
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getCompanies()
	{
		if($this->companies === null)
		{
			$this->companies = [];
			if(intval($this->source) > 0)
			{
				$contactBindings = \Bitrix\Crm\Binding\ContactCompanyTable::getContactBindings($this->source);
				foreach($contactBindings as $binding)
				{
					$company = DataProviderManager::getInstance()->getDataProvider(Company::class, $binding['COMPANY_ID'], [
						'isLightMode' => true,
						'DISABLE_MY_COMPANY' => true,
					], $this);
					$this->companies[] = $company;
				}
			}
		}

		return $this->companies;
	}

	/**
	 * @return bool
	 */
	protected function hasLeadField()
	{
		return true;
	}
}