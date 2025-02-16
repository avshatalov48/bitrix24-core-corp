<?php

use Bitrix\Crm\Binding\EntityContactTable;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\Counter\EntityCountableActivityTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;

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

	public static function getEntityFields(int $entityTypeId, int $entityId, array $params = []): ?array
	{
		$fields = null;
		$userId = (int)($params['USER_ID'] ?? 0);
		if ($userId <= 0)
		{
			$userId = Container::getInstance()->getContext()->getUserId();
		}

		$userPermissions = Container::getInstance()->getUserPermissions($userId);
		$isAdmin = $userPermissions->isAdmin();
		$enableExtendedMode = !isset($params['ENABLE_EXTENDED_MODE']) || (bool)$params['ENABLE_EXTENDED_MODE'];

		if ($entityTypeId === CCrmOwnerType::Contact)
		{
			$contactItem = Container::getInstance()->getFactory(\CCrmOwnerType::Contact)?->getItem($entityId);
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
				'CAN_READ' => $userPermissions->checkReadPermissions(CCrmOwnerType::Contact, $entityId),
			];

			if ($fields['CAN_READ'] && $enableExtendedMode)
			{
				self::setFieldsEntities(
					$fields,
					$entityTypeId,
					$entityId,
					$userId,
					$isAdmin
				);

				$invoiceIds = EntityContactTable::getEntityIds(\CCrmOwnerType::SmartInvoice, $entityId);
				if (!empty($invoiceIds))
				{
					$fields['SMART_INVOICES'] = static::loadSmartInvoices([
						'@ID' => $invoiceIds,
					]);
				}
			}
		}
		elseif ($entityTypeId === CCrmOwnerType::Company)
		{
			$companyItem = Container::getInstance()->getFactory(\CCrmOwnerType::Company)?->getItem($entityId);
			if (!$companyItem)
			{
				return null;
			}

			$fields = [
				'ID' => $companyItem->getId(),
				'TITLE' => $companyItem->getTitle() ?? '',
				'LOGO' => $companyItem->get(Item\Company::FIELD_NAME_LOGO) ?? 0,
				'ASSIGNED_BY_ID' => $companyItem->getAssignedById() ?? 0,
				'CAN_READ' => $userPermissions->checkReadPermissions(CCrmOwnerType::Company, $entityId),
			];

			if ($fields['CAN_READ'] && $enableExtendedMode)
			{
				self::setFieldsEntities(
					$fields,
					$entityTypeId,
					$entityId,
					$userId,
					$isAdmin
				);

				$fields['SMART_INVOICES'] = static::loadSmartInvoices([
					'=COMPANY_ID' => $entityId,
				]);
			}
		}
		elseif ($entityTypeId === CCrmOwnerType::Lead)
		{
			$leadFactory = Container::getInstance()->getFactory(\CCrmOwnerType::Lead);
			if (!$leadFactory)
			{
				return null;
			}

			$leadItem = $leadFactory->getItem($entityId);
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
				'CAN_READ' => $userPermissions->checkReadPermissions(CCrmOwnerType::Lead, $entityId),
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
			$showUrl = $fields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath($entityTypeId, $entityId);
			$defaultFormId = '';
			switch ($entityTypeId)
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
			
			if ($showUrl !== '' && $defaultFormId !== '')
			{
				$fields['ACTIVITY_LIST_URL'] = CCrmUrlUtil::AddUrlParams(
					$showUrl,
					array("{$defaultFormId}_active_tab" => 'tab_activity')
				);

				$fields['INVOICE_LIST_URL'] = CCrmUrlUtil::AddUrlParams(
					$showUrl,
					array("{$defaultFormId}_active_tab" => 'tab_invoice')
				);

				if (in_array($entityTypeId, [CCrmOwnerType::Contact, CCrmOwnerType::Company], true))
				{
					$fields['DEAL_LIST_URL'] = CCrmUrlUtil::AddUrlParams(
						$showUrl,
						["{$defaultFormId}_active_tab" => 'tab_deal']
					);
				}
			}

			$activitiesLimit = 4;
			$activities = [];
			$data = EntityCountableActivityTable::query()
				->setSelect([
					'ACTIVITY_ID',
				])
				->where('ENTITY_ID', $entityId)
				->where('ENTITY_TYPE_ID', $entityTypeId)
				->setLimit($activitiesLimit)
				->setOrder(['ACTIVITY_DEADLINE' => 'ASC'])
				->fetchAll()
			;
			$activityIds = array_column($data, 'ACTIVITY_ID');

			if ($activitiesLimit - count($activityIds) > 0)
			{
				// need to select activities where the deadline is not set
				$dbActivityIdsObject = CCrmActivity::GetList(
					[],
					[
						'COMPLETED' => 'N',
						'CHECK_PERMISSIONS' => 'N',
						'DEADLINE' => CCrmDateTimeHelper::GetMaxDatabaseDate(false),
						'BINDINGS' => [['OWNER_TYPE_ID' => $entityTypeId, 'OWNER_ID' => $entityId]],
					],
					false,
					['nTopCount' => $activitiesLimit - count($activityIds)],
					['ID']
				);
				if (is_object($dbActivityIdsObject))
				{
					while ($activityIdField = $dbActivityIdsObject->Fetch())
					{
						$activityIds[] = $activityIdField['ID'];
					}
				}
			}

			if (!empty($activityIds))
			{
				// We can skip permissions check, because user should be able to read activities,
				// bound to the entity, that he is able to read (see $fields['CAN_READ'])
				$dbActivityObject = CCrmActivity::GetList(
					['DEADLINE' => 'ASC'],
					[
						'@ID' => $activityIds,
						'COMPLETED' => 'N',
						'CHECK_PERMISSIONS' => 'N',
					],
					false,
					[],
					['ID', 'SUBJECT', 'START_TIME', 'END_TIME', 'DEADLINE']
				);

				if (is_object($dbActivityObject))
				{
					while ($activityFields = $dbActivityObject->Fetch())
					{
						$activityFields['SHOW_URL'] = CCrmOwnerType::GetEntityShowPath(
							CCrmOwnerType::Activity,
							$activityFields['ID']
						);
						if (CCrmDateTimeHelper::IsMaxDatabaseDate($activityFields['DEADLINE']))
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
				'=CLOSED' => 'N',
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
		if ($companyId > 0)
		{
			$item = CompanyTable::query()
				->setSelect(['TITLE'])
				->where('ID', $companyId)
				->setLimit(1)
				->fetch();

			return $item['TITLE'] ?? null;
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
