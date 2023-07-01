<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Service\Container;

class CCrmSipHelper
{
	private static $ENABLE_VOX_IMPLANT = null;

	public static function isEnabled()
	{
		if (self::$ENABLE_VOX_IMPLANT === null)
		{
			self::$ENABLE_VOX_IMPLANT = IsModuleInstalled('voximplant') && CModule::IncludeModule('voximplant');
		}

		return self::$ENABLE_VOX_IMPLANT;
	}

	public static function checkPhoneNumber($number)
	{
		if (!self::isEnabled())
		{
			return false;
		}

		return CVoxImplantMain::Enable($number);
	}

	public static function findByPhoneNumber($number, $params = array())
	{
		if (!is_string($number))
		{
			throw new \Bitrix\Main\ArgumentTypeException('number', 'string');
		}

		if ($number === '')
		{
			throw new \Bitrix\Main\ArgumentException('Is empty', 'number');
		}

		if (!is_array($params))
		{
			$params = array();
		}

		$dups = array();
		$criterion = new \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion('PHONE', $number);
		$entityTypes = array(CCrmOwnerType::Contact, CCrmOwnerType::Company, CCrmOwnerType::Lead);
		foreach ($entityTypes as $entityType)
		{
			$duplicate = $criterion->find($entityType, 1);
			if ($duplicate !== null)
			{
				$dups[] = $duplicate;
			}
		}

		$entityByType = array();
		foreach ($dups as &$dup)
		{
			/** @var \Bitrix\Crm\Integrity\Duplicate $dup */
			$entities = $dup->getEntities();
			if (!(is_array($entities) && !empty($entities)))
			{
				continue;
			}

			//Each entity type limited by 50 items
			foreach ($entities as &$entity)
			{
				/** @var \Bitrix\Crm\Integrity\DuplicateEntity $entity */
				$entityTypeID = $entity->getEntityTypeID();
				$entityID = $entity->getEntityID();

				$fields = self::getEntityFields($entityTypeID, $entityID, $params);
				if(!is_array($fields))
					continue;

				$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
				if (!isset($entityByType[$entityTypeName]))
				{
					$entityByType[$entityTypeName] = array($fields);
				}
				elseif (!in_array($entityID, $entityByType[$entityTypeName], true))
				{
					$entityByType[$entityTypeName][] = $fields;
				}
			}
		}
		unset($dup);
		return $entityByType;
	}

	public static function getEntityFields($entityTypeID, $entityID, $params = array())
	{
		$fields = null;

		$userID = isset($params['USER_ID']) ? intval($params['USER_ID']) : 0;
		if ($userID <= 0)
		{
			$userID = CCrmPerms::GetCurrentUserID();
		}

		$isAdmin = CCrmPerms::IsAdmin($userID);

		$userPermissions = CCrmPerms::GetUserPermissions($userID);
		$enableExtendedMode = isset($params['ENABLE_EXTENDED_MODE']) ? (bool)$params['ENABLE_EXTENDED_MODE'] : true;

		if ($entityTypeID === CCrmOwnerType::Contact)
		{
			$contactFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Contact);
			if (!$contactFactory)
			{
				return null;
			}

			$contactItem = $contactFactory->getItem($entityID);
			if (!$contactItem)
			{
				return null;
			}

			$companyId = $contactItem->getCompanyId();

			$fields = [
				'ID' => $contactItem->getId(),
				'FORMATTED_NAME' => $contactItem->getHeading() ?? '',
				'PHOTO' => $contactItem->get(Item\Contact::FIELD_NAME_PHOTO) ?? 0,
				'COMPANY_ID' => $companyId ?? 0,
				'COMPANY_TITLE' => static::getCompanyTitle($companyId) ?? '',
				'POST' => $contactItem->getPost() ?? '',
				'ASSIGNED_BY_ID' => $contactItem->getAssignedById() ?? 0,
				'CAN_READ' => CCrmContact::CheckReadPermission($entityID, $userPermissions)
			];

			if ($fields['CAN_READ'] && $enableExtendedMode)
			{
				self::setFieldsEntities($fields, $entityTypeID, $entityID, $userID, $isAdmin);

				$invoiceIds = \Bitrix\Crm\Binding\EntityContactTable::getEntityIds(\CCrmOwnerType::SmartInvoice, $entityID);
				if (!empty($invoiceIds))
				{
					$fields['SMART_INVOICES'] = static::loadSmartInvoices([
						'@ID' => $invoiceIds,
					]);
				}
			}
		}
		elseif ($entityTypeID === CCrmOwnerType::Company)
		{
			$companyFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Company);
			if (!$companyFactory)
			{
				return null;
			}

			$companyItem = $companyFactory->getItem($entityID);
			if (!$companyItem)
			{
				return null;
			}

			$companyTitle = $companyItem->getTitle();
			$companyLogo = $companyItem->get(Item\Company::FIELD_NAME_LOGO);
			$companyAssignedBy = $companyItem->getAssignedById();
			$fields = [
				'ID' => $companyItem->getId(),
				'TITLE' => $companyTitle ?? '',
				'LOGO' => $companyLogo ?? 0,
				'ASSIGNED_BY_ID' => $companyAssignedBy ?? 0,
				'CAN_READ' => CCrmCompany::CheckReadPermission($entityID, $userPermissions)
			];

			if ($fields['CAN_READ'] && $enableExtendedMode)
			{
				self::setFieldsEntities($fields, $entityTypeID, $entityID, $userID, $isAdmin);

				$fields['SMART_INVOICES'] = static::loadSmartInvoices([
					'=COMPANY_ID' => $entityID,
				]);
			}
		}
		elseif ($entityTypeID === CCrmOwnerType::Lead)
		{
			$leadFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Lead);
			if (!$leadFactory)
			{
				return null;
			}

			$leadItem = $leadFactory->getItem($entityID);
			if (!$leadItem)
			{
				return null;
			}

			$companyId = $leadItem->getCompanyId();

			$fields = [
				'ID' => $leadItem->getId(),
				'TITLE' => $leadItem->getTitle() ?? '',
				'FORMATTED_NAME' => $leadItem->getHeading() ?? '',
				'COMPANY_TITLE' => static::getCompanyTitle($companyId) ?? '',
				'POST' => $leadItem->getPost() ?? '',
				'ASSIGNED_BY_ID' => $leadItem->getAssignedById() ?? 0,
				'CAN_READ' => CCrmLead::CheckReadPermission($entityID, $userPermissions),
				'IS_FINAL' => \Bitrix\Crm\PhaseSemantics::isFinal(CCrmLead::GetSemanticID($leadItem->get(Item\Lead::FIELD_NAME_STATUS_ID))),
				'STATUS_TEXT' => static::getStatusText(\CCrmOwnerType::Lead, $leadItem->getIsReturnCustomer(), false),
				'STATUS_COLOR' => static::getStatusColor(\CCrmOwnerType::Lead),
				'LEAD' => static::getLeadData($leadItem, $leadFactory),
			];
		}

		if (!is_array($fields))
		{
			return null;
		}


		if ($fields['CAN_READ'] && $enableExtendedMode)
		{
			$showUrl = $fields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath($entityTypeID, $entityID);
			$defaultFormId = '';
			switch ($entityTypeID)
			{
				case CCrmOwnerType::Lead:
					$defaultFormId = CCrmLead::DEFAULT_FORM_ID;
					break;
				case CCrmOwnerType::Contact:
					$defaultFormId = CCrmContact::DEFAULT_FORM_ID;
					break;
				case CCrmOwnerType::Company:
					$defaultFormId = CCrmCompany::DEFAULT_FORM_ID;
					break;
			}
			
			if ($showUrl !== '' && $defaultFormId != '')
			{
				$fields['ACTIVITY_LIST_URL'] = CCrmUrlUtil::AddUrlParams(
					$showUrl,
					array("{$defaultFormId}_active_tab" => 'tab_activity')
				);

				$fields['INVOICE_LIST_URL'] = CCrmUrlUtil::AddUrlParams(
					$showUrl,
					array("{$defaultFormId}_active_tab" => 'tab_invoice')
				);

				if ($entityTypeID === CCrmOwnerType::Contact || $entityTypeID === CCrmOwnerType::Company)
				{
					$fields['DEAL_LIST_URL'] = CCrmUrlUtil::AddUrlParams(
						$showUrl,
						array("{$defaultFormId}_active_tab" => 'tab_deal')
					);
				}
			}

			$activities = array();

			// We can skip permissions check, because user should be able to read activities,
			// bound to the entity, that he is able to read (see $fields['CAN_READ'])
			$dbActivity = CCrmActivity::GetList(
				array('DEADLINE' => 'ASC'),
				array(
					'COMPLETED' => 'N',
					'BINDINGS' => array(array('OWNER_TYPE_ID' => $entityTypeID, 'OWNER_ID' => $entityID)),
					'CHECK_PERMISSIONS' => 'N'
				),
				false,
				array('nTopCount' => 4),
				array('ID', 'SUBJECT', 'START_TIME', 'END_TIME', 'DEADLINE'),
				array('PERMS' => $userPermissions)
			);

			if (is_object($dbActivity))
			{
				while ($activityFields = $dbActivity->Fetch())
				{
					$activityFields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Activity, $activityFields['ID']);
					if(CCrmDateTimeHelper::IsMaxDatabaseDate($activityFields['DEADLINE']))
					{
						$activityFields['DEADLINE'] = '';
					}
					$activities[] = &$activityFields;
					unset($activityFields);
				}
			}

			$fields['ACTIVITIES'] = &$activities;
			unset($activities);
		}

		return $fields;
	}

	public static function getDealIds($entityTypeId, $entityId)
	{
		$result = [];
		$filter = ['CLOSED' => 'N', 'CHECK_PERMISSIONS' => 'N'];
		switch ($entityTypeId)
		{
			case CCrmOwnerType::Contact:
				$filter['=ASSOCIATED_CONTACT_ID'] = $entityId;
				break;
			case CCrmOwnerType::Company:
				$filter['=COMPANY_ID'] = $entityId;
				break;
			default:
				throw new ArgumentException("Unsupported entity type");
		}

		$cursor = CCrmDeal::GetListEx(
			[],
			$filter,
			false,
			['nTopCount' => 1000],
			['ID']
		);

		if (is_object($cursor))
		{
			while ($deal = $cursor->Fetch())
			{
				$result[] = $deal['ID'];
			}
		}
		return $result;
	}

	protected static function loadSmartInvoices(array $filter): ?array
	{
		if (!\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isSmartInvoiceEnabled())
		{
			return null;
		}
		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
		if (!$factory)
		{
			return null;
		}
		$invoices = $factory->getItemsFilteredByPermissions([
			'select' => [
				Bitrix\Crm\Item::FIELD_NAME_ID,
				Bitrix\Crm\Item::FIELD_NAME_TITLE,
				Bitrix\Crm\Item::FIELD_NAME_CLOSE_DATE,
				Bitrix\Crm\Item::FIELD_NAME_OPPORTUNITY,
				Bitrix\Crm\Item::FIELD_NAME_CURRENCY_ID,
				Bitrix\Crm\Item::FIELD_NAME_STAGE_ID,
				Bitrix\Crm\Item::FIELD_NAME_CATEGORY_ID,
			],
			'filter' => array_merge(
				[
					// todo stage semantic filter
				],
				$filter
			),
			'order' => [
				'ID' => 'DESC',
			],
			'limit' => 2,
		]);
		$router = \Bitrix\Crm\Service\Container::getInstance()->getRouter();
		$result = [];
		foreach ($invoices as $invoice)
		{
			$data = $invoice->getCompatibleData();
			$data['HEADING'] = $invoice->getHeading();
			$data['SHOW_URL'] = $router->getItemDetailUrl(\CCrmOwnerType::SmartInvoice, $invoice->getId());
			$data['PRICE_FORMATTED'] = CCrmCurrency::MoneyToString($data['OPPORTUNITY'], $data['CURRENCY_ID']);
			$result[] = $data;
		}

		return $result;
	}

	private static function getEntityRepeatedText(Item $entity): ?string
	{
		if ($entity->getEntityTypeId() === \CCrmOwnerType::Deal)
		{
			if ($entity->get(Item\Deal::FIELD_NAME_IS_REPEATED_APPROACH))
			{
				return Loc::getMessage('CRM_SIP_HELPER_REPEATED_APPROACH_DEAL');
			}

			if ($entity->getIsReturnCustomer())
			{
				return Loc::getMessage('CRM_SIP_HELPER_REPEATED_REPEATED_DEAL');
			}
		}

		if ($entity->getEntityTypeId() === \CCrmOwnerType::Lead)
		{
			if ($entity->getIsReturnCustomer())
			{
				return Loc::getMessage('CRM_SIP_HELPER_REPEATED_REPEATED_LEAD');
			}
		}

		return null;
	}

	private static function getDeals($entityTypeID, $entityID, $userID): array
	{
		$dealIds = static::getDealIds($entityTypeID, $entityID);
		if ($dealIds === [])
		{
			return [];
		}

		$dealFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
		if (!$dealFactory)
		{
			return [];
		}

		$parameters = [
			'select' => [
				Item::FIELD_NAME_ID,
				Item::FIELD_NAME_TITLE,
				Item::FIELD_NAME_STAGE_ID,
				Item::FIELD_NAME_OPPORTUNITY,
				Item::FIELD_NAME_CURRENCY_ID,
				Item::FIELD_NAME_CATEGORY_ID,
				Item::FIELD_NAME_IS_RETURN_CUSTOMER,
				Item\Deal::FIELD_NAME_IS_REPEATED_APPROACH,
				Item::FIELD_NAME_CREATED_TIME,
			],
			'filter' => [
				'@ID' => $dealIds,
				'CLOSED' => 'N',
			],
			'order' => [
				Item::FIELD_NAME_BEGIN_DATE => 'ASC',
			],
			'limit' => 2,
		];

		$dealItems = $dealFactory->getItemsFilteredByPermissions($parameters, $userID);
		$deals = [];
		foreach ($dealItems as $item)
		{
			$id = $item->getId();
			$opportunity = $item->getOpportunity();
			$currencyId = $item->getCurrencyId();

			$stageName = null;
			$stageColor = null;

			$stage = $dealFactory->getStage($item->getStageId());
			if ($stage)
			{
				$stageName = $stage->getName();
				$stageColor = $stage->getColor();
			}

			$deals[] = [
				'ID' => $id,
				'TITLE' => $item->getTitle(),
				'STAGE_ID' => $item->getStageId(),
				'CATEGORY_ID' => $item->getCategoryId(),
				'STAGE_NAME' => $stageName,
				'STAGE_COLOR' => $stageColor,
				'OPPORTUNITY' => $opportunity,
				'CURRENCY_ID' => $currencyId,
				'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Deal, $id),
				'FORMATTED_OPPORTUNITY' => CCrmCurrency::MoneyToString($opportunity, $currencyId),
				'REPEATED_TEXT' => self::getEntityRepeatedText($item),
				'CREATED_TIME' => $item->getCreatedTime()->getTimestamp(),
				'IS_RETURN_CUSTOMER' => $item->getIsReturnCustomer(),
				'IS_REPEATED_APPROACH' => $item->get(Item\Deal::FIELD_NAME_IS_REPEATED_APPROACH),
 			];
		}

		return $deals;
	}

	private static function getInvoices($entityID, $isAdmin): array
	{
		$invoices = [];
		$dbInvoice = CCrmInvoice::GetList(
			['DATE_INSERT' => 'ASC'],
			[
				'=UF_CONTACT_ID' => $entityID,
				'PAYED' => 'N',
				'CANCELED' => 'N',
				'CHECK_PERMISSIONS' => $isAdmin ? 'N' : 'Y'
			],
			false,
			['nTopCount' => 2],
			['ID', 'ORDER_TOPIC', 'DATE_BILL', 'PRICE', 'CURRENCY', 'STATUS_ID']
		);
		if(is_object($dbInvoice))
		{
			while($invoiceFields = $dbInvoice->Fetch())
			{
				$invoiceFields['PRICE_FORMATTED'] = CCrmCurrency::MoneyToString($invoiceFields['PRICE'], $invoiceFields['CURRENCY']);
				$invoiceFields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Invoice, $invoiceFields['ID']);
				$invoices[] = $invoiceFields;
			}
		}

		return $invoices;
	}

	private static function getCompanyTitle(int $companyId): ?string
	{
		$companyFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Company);
		if ($companyFactory)
		{
			$companyItem = $companyFactory->getItem($companyId);
			if ($companyItem)
			{
				return $companyItem->getTitle();
			}
		}

		return null;
	}

	public static function getLeadId(int $entityTypeId,int $entityId,bool $isAdmin): array
	{
		$result = [];
		$filter = ['STATUS_SEMANTIC_ID' => 'P', 'CHECK_PERMISSIONS' => $isAdmin ? 'N' : 'Y'];
		switch ($entityTypeId)
		{
			case CCrmOwnerType::Contact:
				$filter['=CONTACT_ID'] = $entityId;
				break;
			case CCrmOwnerType::Company:
				$filter['=COMPANY_ID'] = $entityId;
				break;
			default:
				throw new ArgumentException("Unsupported entity type");
		}

		$cursor = CCrmLead::GetListEx(
			[
				Item\Lead::FIELD_NAME_DATE_CREATE => 'ASC',
			],
			$filter,
			false,
			['nTopCount' => 1],
			['ID']
		);

		if (is_object($cursor))
		{
			while ($deal = $cursor->Fetch())
			{
				$result[] = $deal['ID'];
			}
		}

		return $result;
	}

	private static function getLead(int $entityTypeId,int $entityId,bool $isAdmin): ?array
	{
		$leadId = static::getLeadId($entityTypeId, $entityId, $isAdmin);
		if ($leadId === [])
		{
			return null;
		}

		$leadFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Lead);
		if (!$leadFactory)
		{
			return null;
		}

		$leadItem = $leadFactory->getItem($leadId[0]);
		if (!$leadItem)
		{
			return null;
		}

		return  static::getLeadData($leadItem, $leadFactory);
	}

	private static function getLeadData(Item $leadItem, Factory $factory): array
	{
		$id = $leadItem->getId();
		$opportunity = $leadItem->getOpportunity();
		$currencyId = $leadItem->getCurrencyId();
		$stage = $factory->getStage($leadItem->getStageId());

		return [
			'ID' => $id,
			'TITLE' => $leadItem->getTitle(),
			'STAGE' => $stage ? $stage->getName() : '',
			'STAGE_COLOR' => $stage ? $stage->getColor() : '',
			'OPPORTUNITY_VALUE' => $opportunity,
			'CURRENCY_ID' => $currencyId,
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Lead, $id),
			'FORMATTED_OPPORTUNITY' => CCrmCurrency::MoneyToString($opportunity, $currencyId),
			'REPEATED_TEXT' => self::getEntityRepeatedText($leadItem),
			'CREATED_TIME' => $leadItem->getCreatedTime()->getTimestamp(),
			'IS_RETURN_CUSTOMER' => $leadItem->getIsReturnCustomer(),
		];
	}

	private static function getStatusText(int $entityTypeId,bool $isReturnCustomer,bool $isRepeatedApproach = false): ?string
	{
		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			if ($isRepeatedApproach)
			{
				return Loc::getMessage('CRM_SIP_HELPER_REPEATED_APPROACH_DEAL');
			}

			if ($isReturnCustomer)
			{
				return Loc::getMessage('CRM_SIP_HELPER_REPEATED_REPEATED_DEAL');
			}

			return null;
		}

		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			if ($isReturnCustomer)
			{
				return Loc::getMessage('CRM_SIP_HELPER_REPEATED_REPEATED_LEAD');
			}

			return null;
		}

		return null;
	}

	private static function getStatusColor(int $entityTypeId): ?string
	{
		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			return '#BE93F4';
		}
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			return '#93F4E9';
		}

		return null;
	}

	private static function setFieldsEntities(array &$fields,int  $entityTypeID,int  $entityID,int $userID,bool $isAdmin): void
	{
		$fields['DEALS'] = static::getDeals($entityTypeID, $entityID, $userID);

		if ($fields['DEALS'] === [])
		{
			$lead = static::getLead($entityTypeID, $entityID, $isAdmin);
			if ($lead)
			{
				$fields['LEAD'] = $lead;
				$isReturnCustomer = $fields['LEAD']['IS_RETURN_CUSTOMER'];
				$fields['STATUS_TEXT'] = static::getStatusText(\CCrmOwnerType::Lead, $isReturnCustomer, false);
				$fields['STATUS_COLOR'] = static::getStatusColor(\CCrmOwnerType::Lead);
			}
		}
		else
		{
			$isReturnCustomer = $fields['DEALS'][0]['IS_RETURN_CUSTOMER'];
			$isRepeatedApproach = $fields['DEALS'][0]['IS_REPEATED_APPROACH'];
			$fields['STATUS_TEXT'] = static::getStatusText(\CCrmOwnerType::Deal, $isReturnCustomer, $isRepeatedApproach);
			$fields['STATUS_COLOR'] = static::getStatusColor(\CCrmOwnerType::Deal);
		}

		$fields['INVOICES'] = static::getInvoices($entityID, $isAdmin);
	}
}