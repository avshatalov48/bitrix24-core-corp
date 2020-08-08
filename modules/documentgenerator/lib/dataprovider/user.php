<?php

namespace Bitrix\DocumentGenerator\DataProvider;

use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\UserTable;

class User extends EntityDataProvider
{
	protected $nameData = [];
	protected $workDepartment;

	/**
	 * @return array
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			parent::getFields();
			if(isset($this->fields['UF_PHONE_INNER']))
			{
				$this->fields['UF_PHONE_INNER']['TITLE'] = GetMessage('DOCGEN_DATAPROVIDER_USER_INNER_PHONE_TITLE');
			}
			$this->fields['PERSONAL_PHOTO']['TYPE'] = static::FIELD_TYPE_IMAGE;
			$this->fields['WORK_LOGO']['TYPE'] = static::FIELD_TYPE_IMAGE;

			$this->fields['FORMATTED_NAME'] = [
				'TITLE' => GetMessage('DOCGEN_DATAPROVIDER_USER_FORMATTED_NAME_TITLE'),
				'VALUE' => [$this, 'getNameData'],
				'TYPE' => static::FIELD_TYPE_NAME,
			];

			if(isset($this->options['FORMATTED_NAME_FORMAT']))
			{
				$this->fields['FORMATTED_NAME']['FORMAT'] = $this->options['FORMATTED_NAME_FORMAT'];
			}

			$this->fields['NAME']['VALUE'] = [$this, 'getNameData'];
			$this->fields['NAME']['TYPE'] = static::FIELD_TYPE_NAME;
			$this->fields['NAME']['FORMAT'] = ['format' => '#NAME#'];

			$this->fields['SECOND_NAME']['VALUE'] = [$this, 'getNameData'];
			$this->fields['SECOND_NAME']['TYPE'] = static::FIELD_TYPE_NAME;
			$this->fields['SECOND_NAME']['FORMAT'] = ['format' => '#SECOND_NAME#'];

			$this->fields['LAST_NAME']['VALUE'] = [$this, 'getNameData'];
			$this->fields['LAST_NAME']['TYPE'] = static::FIELD_TYPE_NAME;
			$this->fields['LAST_NAME']['FORMAT'] = ['format' => '#LAST_NAME#'];

			$this->fields['PERSONAL_PHONE']['TYPE'] = static::FIELD_TYPE_PHONE;
			$this->fields['WORK_PHONE']['TYPE'] = static::FIELD_TYPE_PHONE;
			$this->fields['PERSONAL_MOBILE']['TYPE'] = static::FIELD_TYPE_PHONE;

			$this->fields['WORK_DEPARTMENT']['VALUE'] = [$this, 'getDepartment'];
			if(isset($this->fields['UF_DEPARTMENT']))
			{
				unset($this->fields['UF_DEPARTMENT']);
			}
		}

		return $this->fields;
	}

	protected function fetchData()
	{
		parent::fetchData();
		if($this->data['PERSONAL_PHOTO'] > 0)
		{
			$this->data['PERSONAL_PHOTO'] = \CFile::GetPath($this->data['PERSONAL_PHOTO']);
		}
		if($this->data['WORK_LOGO'] > 0)
		{
			$this->data['WORK_LOGO'] = \CFile::GetPath($this->data['WORK_LOGO']);
		}
		$this->nameData = [
			'GENDER' => $this->data['PERSONAL_GENDER'],
			'NAME' => $this->data['NAME'],
			'SECOND_NAME' => $this->data['SECOND_NAME'],
			'LAST_NAME' => $this->data['LAST_NAME'],
		];
		unset($this->data['NAME']);
		unset($this->data['SECOND_NAME']);
		unset($this->data['LAST_NAME']);
		$this->workDepartment = $this->data['WORK_DEPARTMENT'];
		unset($this->data['WORK_DEPARTMENT']);
	}

	/**
	 * @return string
	 */
	protected function getTableClass()
	{
		return UserTable::class;
	}

	/**
	 * @return array
	 */
	protected function getHiddenFields()
	{
		return array_diff(array_keys($this->getEntity()->getFields()), $this->getGetListParameters()['select']);
	}

	/**
	 * @return array
	 */
	protected function getGetListParameters()
	{
		static $result = null;
		if($result === null)
		{
			$result = [
				'select' => [
					'EMAIL',
					'NAME',
					'SECOND_NAME',
					'LAST_NAME',
					'PERSONAL_PHONE',
					'PERSONAL_MOBILE',
					'WORK_PHONE',
					'PERSONAL_PROFESSION',
					'PERSONAL_WWW',
					'PERSONAL_STREET',
					'PERSONAL_MAILBOX',
					'PERSONAL_CITY',
					'PERSONAL_STATE',
					'PERSONAL_ZIP',
					'PERSONAL_COUNTRY',
					'PERSONAL_BIRTHDAY',
					'PERSONAL_GENDER',
					'PERSONAL_PHOTO',
					'WORK_COMPANY',
					'WORK_DEPARTMENT',
					'WORK_POSITION',
					'WORK_WWW',
					'WORK_STREET',
					'WORK_MAILBOX',
					'WORK_CITY',
					'WORK_STATE',
					'WORK_ZIP',
					'WORK_COUNTRY',
					'WORK_PROFILE',
					'WORK_LOGO',
				],
			];

			if(ModuleManager::isModuleInstalled('intranet'))
			{
				$result['select'][] = 'UF_PHONE_INNER';
				$result['select'][] = 'UF_DEPARTMENT';
			}
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function getNameData()
	{
		return $this->nameData;
	}

	/**
	 * @return bool
	 */
	public function isRootProvider()
	{
		return false;
	}

	/**
	 * @return array
	 */
	public function getDepartment()
	{
		$department = null;
		if(isset($this->data['UF_DEPARTMENT']) && !empty($this->data['UF_DEPARTMENT']))
		{
			$departmentNames = $this->loadDepartmentNames();
			if(!empty($departmentNames))
			{
				if(is_array($this->data['UF_DEPARTMENT']))
				{
					$department = [];
					foreach($this->data['UF_DEPARTMENT'] as $id)
					{
						$department[] = $departmentNames[$id];
					}
				}
				else
				{
					$department = $departmentNames[$department];
				}
			}
		}

		if(!$department)
		{
			$department = $this->workDepartment;
		}

		return $department;
	}

	/**
	 * @return array|null
	 */
	protected function loadDepartmentNames()
	{
		static $departmentNames;
		if($departmentNames === null)
		{
			$departmentNames = [];

			$departmentIblockIds = $this->getUfDepartmentIblockId();
			if($departmentIblockIds > 0 && Loader::includeModule('iblock'))
			{
				$departments = SectionTable::getList(['select' => ['ID', 'NAME']]);
				while($department = $departments->fetch())
				{
					$departmentNames[$department['ID']] = $department['NAME'];
				}
			}
		}

		return $departmentNames;
	}

	/**
	 * @return int|null
	 */
	protected function getUfDepartmentIblockId()
	{
		$arUserFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('USER', 0, DataProviderManager::getInstance()->getContext()->getRegionLanguageId());
		return $arUserFields["UF_DEPARTMENT"]["SETTINGS"]["IBLOCK_ID"];
	}
}