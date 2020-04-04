<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\LeadTable;
use Bitrix\DocumentGenerator\Nameable;

class Lead extends ProductsDataProvider implements Nameable
{
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
			$this->fields['IMOL'] = ['TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_LEAD_IMOL_TITLE'),];
			$this->fields['PHONE_ANOTHER'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_PHONE_ANOTHER_TITLE'),
				'VALUE' => [$this, 'getAnotherPhone'],
				'TYPE' => 'PHONE',
			];
			$this->fields['EMAIL_ANOTHER'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_EMAIL_ANOTHER_TITLE'),
				'VALUE' => [$this, 'getAnotherEmail'],
			];
			$this->fields['COMPANY_NAME'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_DATAPROVIDER_LEAD_COMPANY_NAME_TITLE'),
				'VALUE' => 'COMPANY_TITLE',
			];

			$this->fields['PHONE_MOBILE']['TYPE'] = 'PHONE';
			$this->fields['PHONE_WORK']['TYPE'] = 'PHONE';
			$this->fields['PHONE']['TYPE'] = 'PHONE';
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
				'PHONE',
				'PHONE_WORK',
				'PHONE_MOBILE',
				'EMAIL_HOME',
				'EMAIL_WORK',
				'SKYPE',
				'ICQ',
				'IMOL',
				'EMAIL',
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
	 * @return array|null
	 */
	protected function getMultiFields()
	{
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
		}
		else
		{
			return [];
		}

		return $this->multiFields;
	}
}