<?php
abstract class CCrmTemplateAdapterBase
{
	abstract public function GetSupportedTypes();
	abstract public function IsTypeSupported($typeID);
	abstract public function GetTypeMap($typeID);
	abstract public function CreateMapper($typeID, $ID);
}

class CCrmTemplateAdapter extends CCrmTemplateAdapterBase
{
	private static $MAP_BY_LANG = array();

	protected $supportedTypes;

	public function GetSupportedTypes()
	{
		if ($this->supportedTypes === null)
		{
			$this->supportedTypes = [
				CCrmOwnerType::Lead,
				CCrmOwnerType::Contact,
				CCrmOwnerType::Company,
				CCrmOwnerType::Deal,
				CCrmOwnerType::Invoice,
				CCrmOwnerType::Quote,
				CCrmOwnerType::System,
				CCrmOwnerType::SmartInvoice,
			];

			$typesMap = \Bitrix\Crm\Service\Container::getInstance()->getDynamicTypesMap()->load([
				'isLoadStages' => false,
				'isLoadCategories' => false,
			]);
			foreach ($typesMap->getTypes() as $type)
			{
				if ($type->getIsClientEnabled())
				{
					$this->supportedTypes[] = $type->getEntityTypeId();
				}
			}
		}

		return $this->supportedTypes;
	}
	public function IsTypeSupported($typeID)
	{
		return CCrmOwnerType::IsDefined($typeID);
	}
	public function GetTypeMap($typeID)
	{
		global $USER_FIELD_MANAGER;

		$typeID = intval($typeID);
		if(!CCrmOwnerType::IsDefined($typeID))
		{
			return null;
		}

		if(!isset(self::$MAP_BY_LANG[LANGUAGE_ID]))
		{
			self::$MAP_BY_LANG[LANGUAGE_ID] = array();
		}

		if(isset(self::$MAP_BY_LANG[LANGUAGE_ID][$typeID]))
		{
			return self::$MAP_BY_LANG[LANGUAGE_ID][$typeID];
		}

		IncludeModuleLangFile(__FILE__);

		$ufEntityId = null;

		if($typeID === CCrmOwnerType::Lead)
		{
			$ufEntityId = \CCrmLead::$sUFEntityID;
			self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::Lead] = array(
				'typeId' => CCrmOwnerType::Lead,
				'typeName' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
				'fields' => array(
					array('id' => 'ID', 'name' => 'ID'),
					array('id' => 'TITLE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TITLE')),
					array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_LAST_NAME')),
					array('id' => 'NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_NAME')),
					array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_SECOND_NAME')),
					array('id' => 'FORMATTED_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_FORMATTED_NAME')),
					array('id' => 'COMPANY_TITLE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMPANY_TITLE')),
					array('id' => 'SOURCE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_SOURCE')),
					array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_SOURCE_DESCRIPTION')),
					array('id' => 'STATUS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_STATUS')),
					array('id' => 'STATUS_DESCRIPTION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_STATUS_DESCRIPTION')),
					array('id' => 'POST', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_POST')),
					array('id' => 'ADDRESS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ADDRESS')),
					array('id' => 'CURRENCY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CURRENCY')),
					array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_OPPORTUNITY')),
					array('id' => 'OPPORTUNITY_FORMATTED', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_OPPORTUNITY_FORMATTED')),
					array('id' => 'ASSIGNED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME')),
					array('id' => 'ASSIGNED_BY_WORK_POSITION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_POST')),
					array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME')),
					array('id' => 'MODIFY_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_MODIFY_BY_FULL_NAME')),
					array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_CREATE')),
					array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_MODIFY')),
					array('id' => 'COMMENTS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMMENTS')),
					array('id' => 'FM_EMAIL', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_FM_EMAIL')),
					array('id' => 'FM_PHONE', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_FM_PHONE')),
				)
			);
		}
		elseif($typeID === CCrmOwnerType::Deal)
		{
			$ufEntityId = \CCrmDeal::$sUFEntityID;
			self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::Deal] = array(
				'typeId' => CCrmOwnerType::Deal,
				'typeName' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
				'fields' => array(
					array('id' => 'ID', 'name' => 'ID'),
					array('id' => 'TITLE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TITLE')),
					array('id' => 'TYPE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TYPE')),
					array('id' => 'STAGE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_STAGE')),
					array('id' => 'PROBABILITY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_PROBABILITY')),
					array('id' => 'CURRENCY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CURRENCY')),
					array('id' => 'OPPORTUNITY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_OPPORTUNITY')),
					array('id' => 'OPPORTUNITY_FORMATTED', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_OPPORTUNITY_FORMATTED')),
					array('id' => 'COMPANY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMPANY'), 'typeId' => CCrmOwnerType::Company),
					array('id' => 'CONTACT', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CONTACT'), 'typeId' => CCrmOwnerType::Contact),
					array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_BEGINDATE')),
					array('id' => 'CLOSEDATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CLOSEDATE')),
					array('id' => 'ASSIGNED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME')),
					array('id' => 'ASSIGNED_BY_WORK_POSITION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_POST')),
					array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME')),
					array('id' => 'MODIFY_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_MODIFY_BY_FULL_NAME')),
					array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_CREATE')),
					array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_MODIFY')),
					array('id' => 'COMMENTS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMMENTS'))
				)
			);
		}
		elseif($typeID === CCrmOwnerType::Contact)
		{
			$ufEntityId = \CCrmContact::$sUFEntityID;
			self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::Contact] = array(
				'typeId' => CCrmOwnerType::Contact,
				'typeName' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
				'fields' => array(
					array('id' => 'ID', 'name' => 'ID'),
					array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_LAST_NAME')),
					array('id' => 'NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_NAME')),
					array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_SECOND_NAME')),
					array('id' => 'FORMATTED_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_FORMATTED_NAME')),
					array('id' => 'HONORIFIC', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_HONORIFIC')),
					array('id' => 'POST', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_POST')),
					array('id' => 'ADDRESS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ADDRESS')),
					array('id' => 'TYPE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TYPE')),
					array('id' => 'SOURCE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_SOURCE')),
					array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_STATUS_DESCRIPTION')),
					array('id' => 'COMPANY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMPANY'), 'typeId' => CCrmOwnerType::Company),
					array('id' => 'ASSIGNED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME')),
					array('id' => 'ASSIGNED_BY_WORK_POSITION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_POST')),
					array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME')),
					array('id' => 'MODIFY_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_MODIFY_BY_FULL_NAME')),
					array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_CREATE')),
					array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_MODIFY')),
					array('id' => 'COMMENTS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMMENTS')),
					array('id' => 'FM_EMAIL', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_FM_EMAIL')),
					array('id' => 'FM_PHONE', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_FM_PHONE')),
				)
			);
		}
		elseif($typeID === CCrmOwnerType::Company)
		{
			$ufEntityId = \CCrmCompany::$sUFEntityID;
			self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::Company] = array(
				'typeId' => CCrmOwnerType::Company,
				'typeName' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
				'fields' => array(
					array('id' => 'ID', 'name' => 'ID'),
					array('id' => 'TITLE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TITLE')),
					array('id' => 'TYPE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMPANY_TYPE')),
					array('id' => 'ADDRESS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ACTUAL_ADDRESS')),
					array('id' => 'ADDRESS_LEGAL', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ADDRESS_LEGAL')),
					array('id' => 'BANKING_DETAILS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_BANKING_DETAILS')),
					array('id' => 'INDUSTRY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_INDUSTRY')),
					array('id' => 'EMPLOYEES', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_EMPLOYEES')),
					array('id' => 'CURRENCY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CURRENCY')),
					array('id' => 'REVENUE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_REVENUE')),
					array('id' => 'REVENUE_FORMATTED', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_REVENUE_FORMATTED')),
					array('id' => 'ASSIGNED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME')),
					array('id' => 'ASSIGNED_BY_WORK_POSITION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_POST')),
					array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME')),
					array('id' => 'MODIFY_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_MODIFY_BY_FULL_NAME')),
					array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_CREATE')),
					array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_MODIFY')),
					array('id' => 'COMMENTS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMMENTS')),
					array('id' => 'FM_EMAIL', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_FM_EMAIL')),
					array('id' => 'FM_PHONE', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_FM_PHONE')),
				)
			);
		}
		elseif ($typeID === CCrmOwnerType::Invoice)
		{
			$ufEntityId = \CCrmInvoice::$sUFEntityID;
			self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::Invoice] = array(
				'typeId' => CCrmOwnerType::Invoice,
				'typeName' => CCrmOwnerType::ResolveName(CCrmOwnerType::Invoice),
				'fields' => array(
					array('id' => 'ACCOUNT_NUMBER', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ACCOUNT_NUMBER')),
					array('id' => 'TITLE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_TITLE')),
					array('id' => 'PRICE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_PRICE')),
					array('id' => 'PRICE_FORMATED', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_PRICE_FORMATED')),
					array('id' => 'CURRENCY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CURRENCY')),
					array('id' => 'COMPANY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMPANY'), 'typeId' => CCrmOwnerType::Company),
					array('id' => 'CONTACT', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CONTACT'), 'typeId' => CCrmOwnerType::Contact),
					array('id' => 'DATE_BILL', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_BILL')),
					array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_CREATE')),
					array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_MODIFY')),
					array('id' => 'RESPONSIBLE_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME')),
					array('id' => 'RESPONSIBLE_WORK_POSITION', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_POST')),
					array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME')),
					array('id' => 'COMMENTS', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_COMMENTS'))
				)
			);
		}
		elseif ($typeID === CCrmOwnerType::Quote)
		{
			$ufEntityId = \CCrmQuote::$sUFEntityID;
			self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::Quote] = array(
				'typeId' => CCrmOwnerType::Quote,
				'typeName' => CCrmOwnerType::QuoteName,
				'fields' => array(
					array('id' => 'QUOTE_NUMBER', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_QUOTE_NUMBER')),
					array('id' => 'TITLE', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_TITLE')),
					array('id' => 'STATUS', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_STATUS')),
					array('id' => 'CURRENCY', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_CURRENCY')),
					array('id' => 'OPPORTUNITY', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_OPPORTUNITY')),
					array('id' => 'OPPORTUNITY_FORMATTED', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_OPPORTUNITY_FORMATTED')),
					array('id' => 'ASSIGNED_BY_FULL_NAME', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME')),
					array('id' => 'ASSIGNED_BY_WORK_POSITION', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_POST')),
					array('id' => 'CREATED_BY_FULL_NAME', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME')),
					array('id' => 'MODIFY_BY_FULL_NAME', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_MODIFY_BY_FULL_NAME')),
					array('id' => 'BEGINDATE', 'name' => GetMessage('CRM_TEMPLATE_ADAPTER_DATE_BILL')),
					array('id' => 'DATE_CREATE', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_DATE_CREATE')),
					array('id' => 'DATE_MODIFY', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_DATE_MODIFY')),
					array('id' => 'COMPANY', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_COMPANY'), 'typeId' => CCrmOwnerType::Company),
					array('id' => 'CONTACT', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_CONTACT'), 'typeId' => CCrmOwnerType::Contact),
					array('id' => 'LEAD', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_LEAD'), 'typeId' => CCrmOwnerType::Lead),
					array('id' => 'DEAL', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_DEAL'), 'typeId' => CCrmOwnerType::Deal),
					array('id' => 'CONTENT', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_CONTENT')),
					array('id' => 'TERMS', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_TERMS')),
					array('id' => 'COMMENTS', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_COMMENTS')),
				)
			);
		}
		else if (\CCrmOwnerType::System == $typeID)
		{
			self::$MAP_BY_LANG[LANGUAGE_ID][CCrmOwnerType::System] = array(
				'typeId' => CCrmOwnerType::System,
				'typeName' => 'SENDER',
				'fields' => array(
					array('id' => 'NAME', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_SENDER_NAME')),
					array('id' => 'LAST_NAME', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_SENDER_LAST_NAME')),
					array('id' => 'SECOND_NAME', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_SENDER_SECOND_NAME')),
					array('id' => 'FULL_NAME', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_SENDER_FULL_NAME')),
					array('id' => 'EMAIL', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_SENDER_EMAIL')),
					array('id' => 'WORK_POSITION', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_SENDER_WORK_POSITION')),
					array('id' => 'WORK_PHONE', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_SENDER_WORK_PHONE')),
					array('id' => 'UF_PHONE_INNER', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_SENDER_PHONE_INNER')),
					array('id' => 'PERSONAL_MOBILE', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_SENDER_PERSONAL_MOBILE')),
					//array('id' => 'PERSONAL_PHONE', 'name' => getMessage('CRM_TEMPLATE_ADAPTER_SENDER_PERSONAL_PHONE')),
				)
			);
		}
		elseif (\CCrmOwnerType::isUseDynamicTypeBasedApproach($typeID))
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($typeID);
			if (!$factory)
			{
				return null;
			}
			$hiddenFields = [
				\Bitrix\Crm\Item::FIELD_NAME_LOCATION_ID,
				\Bitrix\Crm\Item::FIELD_NAME_OPENED,
				\Bitrix\Crm\Item::FIELD_NAME_UPDATED_BY,
				\Bitrix\Crm\Item::FIELD_NAME_MOVED_BY,
				\Bitrix\Crm\Item::FIELD_NAME_OBSERVERS,
				\Bitrix\Crm\Item::FIELD_NAME_MYCOMPANY_ID,
			];
			$sorting = [
				\Bitrix\Crm\Item\SmartInvoice::FIELD_NAME_ACCOUNT_NUMBER => 10,
				\Bitrix\Crm\Item::FIELD_NAME_TITLE => 20,
				'PRICE' => 30,
				'PRICE_FORMATED' => 40,
				\Bitrix\Crm\Item::FIELD_NAME_CURRENCY_ID => 50,
				'COMPANY' => 60,
				'CONTACT' => 70,
				\Bitrix\Crm\Item::FIELD_NAME_BEGIN_DATE => 80,
				\Bitrix\Crm\Item::FIELD_NAME_CREATED_TIME => 90,
				\Bitrix\Crm\Item::FIELD_NAME_UPDATED_TIME => 100,
				'ASSIGNED_BY_FULL_NAME' => 110,
				'ASSIGNED_BY_WORK_POSITION' => 120,
				'CREATED_BY_FULL_NAME' => 130,
				\Bitrix\Crm\Item\SmartInvoice::FIELD_NAME_COMMENTS => 140,
			];
			$fields = [];
			foreach ($factory->getFieldsCollection() as $field)
			{
				if ($field->isUserField()
					|| $field->isHidden()
					|| !$field->isDisplayed()
					|| $field->isProgress()
					|| in_array($field->getName(), $hiddenFields, true)
				)
				{
					continue;
				}
				if (\Bitrix\Crm\Service\ParentFieldManager::isParentFieldName($field->getName()))
				{
					$fields[] = [
						'id' => $field->getName(),
						'name' => $field->getTitle(),
						'typeId' => \Bitrix\Crm\Service\ParentFieldManager::getEntityTypeIdFromFieldName($field->getName()),
						'sort' => $sorting[$field->getName()] ?? 500,
					];
				}
				else
				{
					$fields[] = [
						'id' => $field->getName(),
						'name' => $field->getTitle(),
						'sort' => $sorting[$field->getName()] ?? 500,
					];
				}
			}
			if ($factory->isClientEnabled())
			{
				$fields[] = [
					'id' => 'CONTACT',
					'name' => $factory->getFieldCaption(\Bitrix\Crm\Item::FIELD_NAME_CONTACT_ID),
					'typeId' => \CCrmOwnerType::Contact,
					'sort' => $sorting['CONTACT'] ?? 500,
				];
				$fields[] = [
					'id' => 'COMPANY',
					'name' => $factory->getFieldCaption(\Bitrix\Crm\Item::FIELD_NAME_COMPANY_ID),
					'typeId' => \CCrmOwnerType::Company,
					'sort' => $sorting['COMPANY'] ?? 500,
				];
			}
			$fields[] = [
				'id' => 'ASSIGNED_BY_FULL_NAME',
				'name' => getMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_FULL_NAME'),
				'sort' => $sorting['ASSIGNED_BY_FULL_NAME'] ?? 500,
			];
			$fields[] = [
				'id' => 'ASSIGNED_BY_WORK_POSITION',
				'name' => getMessage('CRM_TEMPLATE_ADAPTER_ASSIGNED_BY_POST'),
				'sort' => $sorting['ASSIGNED_BY_WORK_POSITION'] ?? 500,
			];
			$fields[] = [
				'id' => 'CREATED_BY_FULL_NAME',
				'name' => getMessage('CRM_TEMPLATE_ADAPTER_CREATED_BY_FULL_NAME'),
				'sort' => $sorting['CREATED_BY_FULL_NAME'] ?? 500,
			];
			$fields[] = [
				'id' => 'PRICE',
				'name' => GetMessage('CRM_TEMPLATE_ADAPTER_PRICE'),
				'sort' => $sorting['PRICE'] ?? 500,
			];
			$fields[] = [
				'id' => 'PRICE_FORMATED',
				'name' => GetMessage('CRM_TEMPLATE_ADAPTER_PRICE_FORMATED'),
				'sort' => $sorting['PRICE_FORMATED'] ?? 500,
			];
			$fields[] = [
				'id' => 'MY_COMPANY',
				'typeId' => \CCrmOwnerType::Company,
				'name' => $factory->getFieldCaption(Bitrix\Crm\Item::FIELD_NAME_MYCOMPANY_ID),
				'sort' => $sorting['MY_COMPANY'] ?? 500,
			];

			\Bitrix\Main\Type\Collection::sortByColumn($fields, 'sort');

			$ufEntityId = $factory->getUserFieldEntityId();
			self::$MAP_BY_LANG[LANGUAGE_ID][$typeID] = [
				'typeId' => $typeID,
				'typeName' => $factory->getEntityName(),
				'fields' => $fields,
			];
		}
		else
		{
			return null;
		}

		if (!empty($ufEntityId))
		{
			$userTypes = array('string', 'integer', 'double', 'date', 'datetime', 'url', 'enumeration', 'boolean', 'money', 'address');

			foreach ($USER_FIELD_MANAGER->getUserFields($ufEntityId, 0, LANGUAGE_ID) as $item)
			{
				if (!in_array($item['USER_TYPE_ID'], $userTypes))
					continue;

				self::$MAP_BY_LANG[LANGUAGE_ID][$typeID]['fields'][] = array(
					'id'   => $item['FIELD_NAME'],
					'name' => $item['EDIT_FORM_LABEL'],
				);
			}
		}

		return self::$MAP_BY_LANG[LANGUAGE_ID][$typeID];
	}
	public function CreateMapper($typeID, $ID)
	{
		return self::IsTypeSupported($typeID) ? new CCrmTemplateMapper($typeID, $ID) : null;
	}
}
