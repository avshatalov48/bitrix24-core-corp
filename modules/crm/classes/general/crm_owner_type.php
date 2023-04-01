<?php

use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Service;

class CCrmOwnerType
{
	public const Undefined = 0;
	public const Lead = 1;
	public const Deal = 2;
	public const Contact = 3;
	public const Company = 4;
	public const Invoice = 5;
	public const Activity = 6;
	public const Quote = 7;
	public const Requisite = 8;
	public const DealCategory = 9;
	public const CustomActivityType = 10;
	public const Wait = 11;
	public const CallList = 12;
	public const DealRecurring = 13;
	public const Order = 14;
	public const OrderCheck = 15;
	public const OrderShipment = 16;
	public const OrderPayment = 17;

	//Types for suspended state (moved to recycle bin)
	public const SuspendedLead = 18;
	public const SuspendedDeal = 19;
	public const SuspendedContact = 20;
	public const SuspendedCompany = 21;
	public const SuspendedQuote = 22;
	public const SuspendedInvoice = 23;
	public const SuspendedOrder = 24;
	public const SuspendedActivity = 25;
	public const SuspendedRequisite = 26;

	public const InvoiceRecurring = 27;
	public const Scoring = 28;
	public const CheckCorrection = 29;
	public const DeliveryRequest = 30;
	public const SmartInvoice = 31;
	public const SuspendedSmartInvoice = 32;

	public const StoreDocument = 33;
	public const ShipmentDocument = 34;
	public const BankDetail = 35;
	public const SmartDocument = 36;
	public const SuspendedSmartDocument = 37;

	public const FirstOwnerType = 1;
	public const LastOwnerType = 37;

	public const DynamicTypeStart = 128;
	public const DynamicTypeEnd = 192;
	public const SuspendedDynamicTypeStart = 192;
	public const SuspendedDynamicTypeEnd = 256;

	//Special quasi-types
	public const System = 1024;

	public const LeadName = 'LEAD';
	public const DealName = 'DEAL';
	public const ContactName = 'CONTACT';
	public const CompanyName = 'COMPANY';
	public const InvoiceName = 'INVOICE';
	public const ActivityName = 'ACTIVITY';
	public const QuoteName = 'QUOTE';
	public const RequisiteName = 'REQUISITE';
	public const DealCategoryName = 'DEAL_CATEGORY';
	public const CustomActivityTypeName = 'CUSTOM_ACTIVITY_TYPE';
	public const WaitTypeName = 'WAIT';
	public const CallListTypeName = 'CALL_LIST';
	public const SystemName = 'SYSTEM';
	public const DealRecurringName = 'DEAL_RECURRING';
	public const InvoiceRecurringName = 'INVOICE_RECURRING';
	public const OrderName = 'ORDER';
	public const OrderCheckName = 'ORDER_CHECK';
	public const CheckCorrectionName = 'CHECK_CORRECTION';
	public const OrderShipmentName = 'ORDER_SHIPMENT';
	public const OrderPaymentName = 'ORDER_PAYMENT';
	public const SmartInvoiceName = 'SMART_INVOICE';
	public const SmartDocumentName = 'SMART_DOCUMENT';
	public const CommonDynamicName = 'DYNAMIC';

	public const SuspendedLeadName = 'SUS_LEAD';
	public const SuspendedDealName = 'SUS_DEAL';
	public const SuspendedContactName = 'SUS_CONTACT';
	public const SuspendedCompanyName = 'SUS_COMPANY';
	public const SuspendedQuoteName = 'SUS_QUOTE';
	public const SuspendedInvoiceName = 'SUS_INVOICE';
	public const SuspendedOrderName = 'SUS_ORDER';
	public const SuspendedActivityName = 'SUS_ACTIVITY';
	public const SuspendedRequisiteName = 'SUS_REQUISITE';
	public const SuspendedSmartInvoiceName = 'SUS_SMART_INVOICE';
	public const SuspendedSmartDocumentName = 'SUS_SMART_DOCUMENT';

	public const StoreDocumentName = 'STORE_DOCUMENT';
	public const ShipmentDocumentName = 'SHIPMENT_DOCUMENT';

	public const BankDetailName = 'BANK_DETAIL';

	public const ScoringName = 'SCORING';

	public const DynamicTypePrefixName = 'DYNAMIC_';
	public const SuspendedDynamicTypePrefixName = 'SUS_DYNAMIC_';

	private static $ALL_DESCRIPTIONS = array();
	private static $ALL_CATEGORY_CAPTION = array();
	private static $CAPTIONS = array();
	private static $RESPONSIBLES = array();
	private static $INFOS = array();
	private static $INFO_STUB = null;
	private static $COMPANY_TYPE = null;
	private static $COMPANY_INDUSTRY = null;

	/**
	 * Return true if entity with $typeId exists.
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public static function IsDefined($entityTypeId): bool
	{
		$entityTypeId = (int)$entityTypeId;

		$isStatic = $entityTypeId === self::System
			|| ($entityTypeId >= self::FirstOwnerType && $entityTypeId <= self::LastOwnerType);

		if ($isStatic)
		{
			return true;
		}

		if (!static::isPossibleDynamicTypeId($entityTypeId) && !static::isPossibleSuspendedDynamicTypeId($entityTypeId))
		{
			return false;
		}

		return (
			Container::getInstance()->getTypeByEntityTypeId(static::getRealDynamicTypeId($entityTypeId)) !== null
		);
	}

	/**
	 * Return true if $entityTypeId is a correct identifier (entity with such entityTypeId can exist).
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public static function isCorrectEntityTypeId(int $entityTypeId): bool
	{
		return (
			$entityTypeId === self::System
			|| (
				$entityTypeId >= self::FirstOwnerType
				&& $entityTypeId <= self::LastOwnerType
			)
			|| static::isPossibleDynamicTypeId($entityTypeId)
			|| static::isPossibleSuspendedDynamicTypeId($entityTypeId)
		);
	}

	public static function IsEntity($typeID): bool
	{
		$typeID = (int)$typeID;

		$isStatic = (
			$typeID === self::Lead
			|| $typeID === self::Deal
			|| $typeID === self::Contact
			|| $typeID === self::Company
			|| $typeID === self::Invoice
			|| $typeID === self::Quote
			|| $typeID === self::Activity
			|| $typeID === self::Order
			|| $typeID === self::OrderPayment
			|| $typeID === self::OrderShipment
			|| $typeID === self::OrderCheck
			|| $typeID === self::CheckCorrection
			|| $typeID === self::DeliveryRequest
			|| $typeID === self::ShipmentDocument
		);

		if($isStatic)
		{
			return true;
		}

		if(static::isUseDynamicTypeBasedApproach($typeID))
		{
			return static::IsDefined($typeID);
		}

		return false;
	}

	public static function isPossibleDynamicTypeId(int $typeId): bool
	{
		return ($typeId >= static::DynamicTypeStart && $typeId < static::DynamicTypeEnd);
	}

	public static function isPossibleSuspendedDynamicTypeId(int $typeId): bool
	{
		return ($typeId >= static::SuspendedDynamicTypeStart && $typeId < static::SuspendedDynamicTypeEnd);
	}

	protected static function getRealDynamicTypeId(int $typeId): int
	{
		if (static::isPossibleSuspendedDynamicTypeId($typeId))
		{
			$typeId -= (static::SuspendedDynamicTypeEnd - static::SuspendedDynamicTypeStart);
		}

		return $typeId;
	}

	public static function getSuspendedDynamicTypeId(int $typeId): int
	{
		if(static::isPossibleDynamicTypeId($typeId))
		{
			$typeId += (static::DynamicTypeEnd - static::DynamicTypeStart);
		}

		return $typeId;
	}

	public static function ResolveID($name): int
	{
		$name = mb_strtoupper(trim((string)$name));
		if($name === '')
		{
			return self::Undefined;
		}

		switch($name)
		{
			case CCrmOwnerTypeAbbr::Lead:
			case self::LeadName:
				return self::Lead;

			case CCrmOwnerTypeAbbr::Deal:
			case self::DealName:
				return self::Deal;

			case CCrmOwnerTypeAbbr::Contact:
			case self::ContactName:
				return self::Contact;

			case CCrmOwnerTypeAbbr::Company:
			case self::CompanyName:
				return self::Company;

			case CCrmOwnerTypeAbbr::Invoice:
			case self::InvoiceName:
				return self::Invoice;

			case self::ActivityName:
				return self::Activity;

			case CCrmOwnerTypeAbbr::Quote:
			case self::QuoteName:
				return self::Quote;

			case CCrmOwnerTypeAbbr::Order:
			case self::OrderName:
				return self::Order;

			case CCrmOwnerTypeAbbr::OrderPayment:
			case self::OrderPaymentName:
				return self::OrderPayment;

			case CCrmOwnerTypeAbbr::OrderShipment:
			case self::OrderShipmentName:
				return self::OrderShipment;

			case CCrmOwnerTypeAbbr::Requisite:
			case self::RequisiteName:
				return self::Requisite;

			case CCrmOwnerTypeAbbr::DealCategory:
			case self::DealCategoryName:
				return self::DealCategory;

			case self::DealRecurringName:
				return self::DealRecurring;

			case self::InvoiceRecurringName:
				return self::InvoiceRecurring;

			case CCrmOwnerTypeAbbr::CustomActivityType:
			case self::CustomActivityTypeName:
				return self::CustomActivityType;

			case self::CallListTypeName:
				return self::CallList;

			case CCrmOwnerTypeAbbr::SuspendedLead:
			case self::SuspendedLeadName:
				return self::SuspendedLead;

			case CCrmOwnerTypeAbbr::SuspendedDeal:
			case self::SuspendedDealName:
				return self::SuspendedDeal;

			case self::SuspendedContactName:
				return self::SuspendedContact;

			case self::SuspendedCompanyName:
				return self::SuspendedCompany;

			case self::SuspendedQuoteName:
				return self::SuspendedQuote;

			case self::SuspendedInvoiceName:
				return self::SuspendedInvoice;

			case self::SuspendedOrderName:
				return self::SuspendedOrder;

			case self::SuspendedActivityName:
				return self::SuspendedActivity;

			case self::ScoringName:
				return self::Scoring;

			case CCrmOwnerTypeAbbr::SmartInvoice:
			case self::SmartInvoiceName:
				return self::SmartInvoice;

			case CCrmOwnerTypeAbbr::SuspendedSmartInvoice:
			case self::SuspendedSmartInvoiceName:
				return self::SuspendedSmartInvoice;

			case CCrmOwnerTypeAbbr::SmartDocument:
			case self::SmartDocumentName:
				return self::SmartDocument;

			case CCrmOwnerTypeAbbr::SuspendedSmartDocument:
			case self::SuspendedSmartDocumentName:
				return self::SuspendedSmartDocument;

			case self::StoreDocumentName:
				return self::StoreDocument;

			case self::ShipmentDocumentName:
				return self::ShipmentDocument;

			case CCrmOwnerTypeAbbr::System:
			case self::SystemName:
				return self::System;

			default:
				if (CCrmOwnerTypeAbbr::isDynamicTypeAbbreviation($name) || CCrmOwnerTypeAbbr::isSuspendedDynamicTypeAbbreviation($name))
				{
					$name = CCrmOwnerTypeAbbr::ResolveName($name);
				}

				$isSuspendedDynamicType = false;
				$isDynamicType = preg_match('/^'.static::DynamicTypePrefixName.'(\d+)$/', $name, $matches);
				if(!$isDynamicType)
				{
					$isSuspendedDynamicType = preg_match('/^'.static::SuspendedDynamicTypePrefixName.'(\d+)$/', $name, $matches);
				}
				if($isDynamicType || $isSuspendedDynamicType)
				{
					return $matches[1];
				}

				return self::Undefined;
		}
	}

	public static function ResolveName($typeID): string
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = (int)$typeID;
		if($typeID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::Lead:
				return self::LeadName;

			case self::Deal:
				return self::DealName;

			case self::Contact:
				return self::ContactName;

			case self::Company:
				return self::CompanyName;

			case self::Invoice:
				return self::InvoiceName;

			case self::Activity:
				return self::ActivityName;

			case self::Quote:
				return self::QuoteName;

			case self::Order:
				return self::OrderName;

			case self::OrderCheck:
				return self::OrderCheckName;

			case self::OrderShipment:
				return self::OrderShipmentName;

			case self::OrderPayment:
				return self::OrderPaymentName;

			case self::Requisite:
				return self::RequisiteName;

			case self::DealCategory:
				return self::DealCategoryName;

			case self::DealRecurring:
				return self::DealRecurringName;

			case self::InvoiceRecurring:
				return self::InvoiceRecurringName;

			case self::CustomActivityType:
				return self::CustomActivityTypeName;

			case self::CallList:
				return self::CallListTypeName;

			case self::SuspendedLead:
				return self::SuspendedLeadName;

			case self::SuspendedDeal:
				return self::SuspendedDealName;

			case self::SuspendedContact:
				return self::SuspendedContactName;

			case self::SuspendedCompany:
				return self::SuspendedCompanyName;

			case self::SuspendedQuote:
				return self::SuspendedQuoteName;

			case self::SuspendedInvoice:
				return self::SuspendedInvoiceName;

			case self::SuspendedOrder:
				return self::SuspendedOrderName;

			case self::SuspendedActivity:
				return self::SuspendedActivityName;

			case self::Scoring:
				return self::ScoringName;

			case self::SmartInvoice:
				return self::SmartInvoiceName;

			case self::SuspendedSmartInvoice:
				return self::SuspendedSmartInvoiceName;

			case self::StoreDocument:
				return self::StoreDocumentName;

			case self::ShipmentDocument:
				return self::ShipmentDocumentName;

			case self::SmartDocument:
				return self::SmartDocumentName;

			case self::SuspendedSmartDocument:
				return self::SuspendedSmartDocumentName;

			case self::System:
				return self::SystemName;

			case self::Undefined:
				return '';

			default:
				$isPossibleDynamicTypeId = static::isPossibleDynamicTypeId($typeID);
				$isPossibleSuspendedDynamicTypeId = static::isPossibleSuspendedDynamicTypeId($typeID);
				if($isPossibleDynamicTypeId || $isPossibleSuspendedDynamicTypeId)
				{
					return (
						$isPossibleDynamicTypeId ? static::DynamicTypePrefixName : static::SuspendedDynamicTypePrefixName
					) . $typeID;
				}

				return '';
		}
	}

	public static function ResolveSuspended($typeID): int
	{
		$typeID = (int)$typeID;

		if($typeID <= 0)
		{
			return self::Undefined;
		}

		switch($typeID)
		{
			case self::Lead:
			case self::SuspendedLead:
				return self::SuspendedLead;

			case self::Deal:
			case self::SuspendedDeal:
				return self::SuspendedDeal;

			case self::Contact:
			case self::SuspendedContact:
				return self::SuspendedContact;

			case self::Company:
			case self::SuspendedCompany:
				return self::SuspendedCompany;

			case self::Invoice:
			case self::SuspendedInvoice:
				return self::SuspendedInvoice;

			case self::Activity:
			case self::SuspendedActivity:
				return self::SuspendedActivity;

			case self::Quote:
			case self::SuspendedQuote:
				return self::SuspendedQuote;

			case self::Order:
			case self::SuspendedOrder:
				return self::SuspendedOrder;

			case self::Requisite:
			case self::SuspendedRequisite:
				return self::SuspendedRequisite;

			case self::SmartInvoice:
			case self::SuspendedSmartInvoice:
				return self::SuspendedSmartInvoice;

			case self::SmartDocument:
			case self::SuspendedSmartDocument:
				return self::SuspendedSmartDocument;

			default:
				$isPossibleDynamicTypeId = static::isPossibleDynamicTypeId($typeID);
				$isPossibleSuspendedDynamicTypeId = static::isPossibleSuspendedDynamicTypeId($typeID);
				if(
					(
						$isPossibleDynamicTypeId
						|| $isPossibleSuspendedDynamicTypeId
					)
					&& static::IsDefined($typeID)
				)
				{
					return static::getSuspendedDynamicTypeId($typeID);
				}

				return self::Undefined;
		}
	}

	public static function GetAllNames(): array
	{
		return [
			self::ContactName, self::CompanyName,
			self::LeadName, self::DealName,
			self::InvoiceName, self::ActivityName,
			self::QuoteName, self::Requisite,
			self::DealCategoryName, self::CustomActivityTypeName,
			self::SmartInvoiceName, self::SmartDocumentName,
		];
	}

	public static function GetNames(array $types): array
	{
		$result = [];

		foreach($types as $typeID)
		{
			$typeID = (int)$typeID;
			$name = self::ResolveName($typeID);
			if($name !== '')
			{
				$result[] = $name;
			}
		}

		return $result;
	}

	public static function GetDescriptions(array $types): array
	{
		$result = [];
		foreach($types as $typeID)
		{
			$typeID = (int)$typeID;
			$description = self::GetDescription($typeID);
			if($description !== '')
			{
				$result[$typeID] = $description;
			}
		}
		return $result;
	}

	public static function GetAll(): array
	{
		return [
			self::Contact, self::Company,
			self::Lead, self::Deal,
			self::Invoice, self::Activity,
			self::Quote, self::Requisite,
			self::DealCategory, self::CustomActivityType,
			self::SmartInvoice, self::SmartDocument,
		];
	}

	public static function  getAllSuspended(): array
	{
		$suspended = [
			self::SuspendedLead,
			self::SuspendedDeal,
			self::SuspendedContact,
			self::SuspendedCompany,
			self::SuspendedQuote,
			self::SuspendedInvoice,
			self::SuspendedOrder,
			self::SuspendedActivity,
			self::SuspendedRequisite,
			self::SuspendedSmartInvoice,
		];

		$map = Container::getInstance()->getDynamicTypesMap()->load([
			'isLoadStages' => false,
			'isLoadCategories' => false,
		]);
		foreach ($map->getTypes() as $type)
		{
			$suspended[] = self::getSuspendedDynamicTypeId($type->getEntityTypeId());
		}

		return array_unique($suspended);
	}

	public static function GetAllDescriptions(): array
	{
		if (!isset(self::$ALL_DESCRIPTIONS[LANGUAGE_ID]))
		{
			IncludeModuleLangFile(__FILE__);
			self::$ALL_DESCRIPTIONS[LANGUAGE_ID] = [
				self::Lead => GetMessage('CRM_OWNER_TYPE_LEAD'),
				self::Deal => GetMessage('CRM_OWNER_TYPE_DEAL'),
				self::Contact => GetMessage('CRM_OWNER_TYPE_CONTACT'),
				self::Company => GetMessage('CRM_OWNER_TYPE_COMPANY'),
				self::Invoice => Container::getInstance()->getLocalization()->appendOldVersionSuffix(GetMessage('CRM_OWNER_TYPE_INVOICE')),
				self::SmartInvoice => GetMessage('CRM_OWNER_TYPE_INVOICE'),
				self::Quote => GetMessage('CRM_OWNER_TYPE_QUOTE'),
				self::Requisite => GetMessage('CRM_OWNER_TYPE_REQUISITE'),
				self::DealCategory => GetMessage('CRM_OWNER_TYPE_DEAL_CATEGORY'),
				self::DealRecurring => GetMessage('CRM_OWNER_TYPE_RECURRING_DEAL'),
				self::Activity => GetMessage('CRM_OWNER_TYPE_ACTIVITY'),
				self::CustomActivityType => GetMessage('CRM_OWNER_TYPE_CUSTOM_ACTIVITY_TYPE'),
				self::System => GetMessage('CRM_OWNER_TYPE_SYSTEM'),
				self::Order => GetMessage('CRM_OWNER_TYPE_ORDER'),
				self::OrderShipment => GetMessage('CRM_OWNER_TYPE_ORDER_SHIPMENT'),
				self::OrderPayment => GetMessage('CRM_OWNER_TYPE_ORDER_PAYMENT'),
				self::SmartDocument => GetMessage('CRM_OWNER_TYPE_SMART_DOCUMENT'),
			];

			$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap();
			$dynamicTypesMap->load([
				'isLoadCategories' => false,
				'isLoadStages' => false,
			]);
			foreach ($dynamicTypesMap->getTypes() as $type)
			{
				self::$ALL_DESCRIPTIONS[LANGUAGE_ID][$type->getEntityTypeId()] = $type->getTitle();
			}
		}

		return self::$ALL_DESCRIPTIONS[LANGUAGE_ID];
	}

	public static function GetAllCategoryCaptions(bool $useNames = false): array
	{
		if (!isset(self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID]))
		{
			IncludeModuleLangFile(__FILE__);
			self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID] = [
				self::Lead => GetMessage('CRM_OWNER_TYPE_LEAD_CATEGORY'),
				self::Deal => GetMessage('CRM_OWNER_TYPE_DEAL_CATEGORY'),
				self::Contact => GetMessage('CRM_OWNER_TYPE_CONTACT_CATEGORY'),
				self::Company => GetMessage('CRM_OWNER_TYPE_COMPANY_CATEGORY'),
				self::Invoice => Container::getInstance()->getLocalization()->appendOldVersionSuffix(GetMessage('CRM_OWNER_TYPE_INVOICE_CATEGORY')),
				self::SmartInvoice => GetMessage('CRM_OWNER_TYPE_INVOICE_CATEGORY'),
				self::Quote => GetMessage('CRM_OWNER_TYPE_QUOTE_CATEGORY'),
				self::Requisite => GetMessage('CRM_OWNER_TYPE_REQUISITE_CATEGORY'),
				self::DealCategory => GetMessage('CRM_OWNER_TYPE_DEAL_CATEGORY_CATEGORY'),
				self::CustomActivityType => GetMessage('CRM_OWNER_TYPE_CUSTOM_ACTIVITY_TYPE_CATEGORY'),
				self::Order => GetMessage('CRM_OWNER_TYPE_ORDER_CATEGORY'),
				self::SmartInvoice => GetMessage('CRM_OWNER_TYPE_INVOICE_CATEGORY'),
				self::SmartDocument => GetMessage('CRM_OWNER_TYPE_DOCUMENT_CATEGORY'),
			];

			$dynamicTypesMap = Container::getInstance()->getDynamicTypesMap();
			$dynamicTypesMap->load([
				'isLoadCategories' => false,
				'isLoadStages' => false,
			]);
			foreach ($dynamicTypesMap->getTypes() as $type)
			{
				self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID][$type->getEntityTypeId()] = $type->getTitle();
			}
		}


		if(!$useNames)
		{
			return self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID];
		}

		$results = array();
		foreach(self::$ALL_CATEGORY_CAPTION[LANGUAGE_ID] as $typeID => $caption)
		{
			$results[self::ResolveName($typeID)] = $caption;
		}
		return $results;
	}

	public static function GetDescription($typeID)
	{
		$typeID = intval($typeID);
		$all = self::GetAllDescriptions();
		return isset($all[$typeID]) ? $all[$typeID] : '';
	}

	public static function GetCategoryCaption($typeID)
	{
		$typeID = intval($typeID);
		$all = self::GetAllCategoryCaptions();
		return isset($all[$typeID]) ? $all[$typeID] : '';
	}

	public static function GetListUrl($typeID, $bCheckPermissions = false)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		switch($typeID)
		{
			case self::Lead:
			{
				if ($bCheckPermissions && !CCrmLead::CheckReadPermission())
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					Bitrix\Main\Config\Option::get('crm', 'path_to_lead_list', '/crm/lead/list/', false),
					array()
				);
			}
			case self::Contact:
			{
				if ($bCheckPermissions && !CCrmContact::CheckReadPermission())
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					Bitrix\Main\Config\Option::get('crm', 'path_to_contact_list', '/crm/contact/list/', false),
					array()
				);
			}
			case self::Company:
			{
				if ($bCheckPermissions && !CCrmCompany::CheckReadPermission())
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					Bitrix\Main\Config\Option::get('crm', 'path_to_company_list', '/crm/company/list/', false),
					array()
				);
			}
			case self::Deal:
			{
				if ($bCheckPermissions && !CCrmDeal::CheckReadPermission())
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					Bitrix\Main\Config\Option::get('crm', 'path_to_deal_list', '/crm/deal/list/', false),
					array()
				);
			}
			case self::Activity:
			{
				return CComponentEngine::MakePathFromTemplate(
					Bitrix\Main\Config\Option::get('crm', 'path_to_activity_list', '/crm/activity/', false),
					array()
				);
			}
			case self::Invoice:
			{
				if ($bCheckPermissions && !CCrmInvoice::CheckReadPermission())
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					Bitrix\Main\Config\Option::get('crm', 'path_to_invoice_list', '/crm/invoice/list/', false),
					array()
				);
			}
			case self::Quote:
			{
				if ($bCheckPermissions && !CCrmQuote::CheckReadPermission())
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					Bitrix\Main\Config\Option::get('crm', 'path_to_quote_list', '/crm/quote/list/', false),
					array()
				);
			}
			case self::Order:
			{
				if ($bCheckPermissions && !\Bitrix\Crm\Order\Permissions\Order::checkReadPermission())
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					Bitrix\Main\Config\Option::get('crm', 'path_to_order_list', '/shop/orders/list/', false),
					array()
				);
			}

		}
		return '';
	}
	public static function GetShowUrl($typeID, $ID, $bCheckPermissions = false)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if($ID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::Lead:
			{
				if ($bCheckPermissions && !CCrmLead::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_lead_show'),
					array('lead_id' => $ID)
				);
			}
			case self::Contact:
			{
				if ($bCheckPermissions && !CCrmContact::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_contact_show'),
					array('contact_id' => $ID)
				);
			}
			case self::Company:
			{
				if ($bCheckPermissions && !CCrmCompany::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_company_show'),
					array('company_id' => $ID)
				);
			}
			case self::Deal:
			{
				if ($bCheckPermissions && !CCrmDeal::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_deal_show'),
					array('deal_id' => $ID)
				);
			}
			case self::Activity:
			{
				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_activity_show'),
					array('activity_id' => $ID)
				);
			}
			case self::Invoice:
			{
				if ($bCheckPermissions && !CCrmInvoice::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_invoice_show'),
					array('invoice_id' => $ID)
				);
			}
			case self::Quote:
			{
				if ($bCheckPermissions && !CCrmQuote::CheckReadPermission($ID))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_quote_show'),
					array('quote_id' => $ID)
				);
			}
			case self::Order:
			{
				if ($bCheckPermissions && !\Bitrix\Crm\Order\Permissions\Order::checkReadPermission($ID))
				{
					return '';
				}

				return self::GetDetailsUrl($typeID, $ID, $bCheckPermissions = false);
			}
			case self::OrderShipment:
			{
				if ($bCheckPermissions && !\Bitrix\Crm\Order\Permissions\Shipment::checkReadPermission($ID))
				{
					return '';
				}

				return self::GetDetailsUrl($typeID, $ID, $bCheckPermissions = false);
			}
			case self::OrderPayment:
			{
				if ($bCheckPermissions && !\Bitrix\Crm\Order\Permissions\Payment::checkReadPermission($ID))
				{
					return '';
				}

				return self::GetDetailsUrl($typeID, $ID, $bCheckPermissions = false);
			}

			case self::StoreDocument:
			{
				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_store_document_details'),
					[
						'store_document_id' => $ID,
					]
				);
			}

			case self::ShipmentDocument:
			{
				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_shipment_document_details'),
					[
						'shipment_document_id' => $ID,
					]
				);
			}

			default:
				return '';
		}
	}
	public static function GetDetailsUrl($typeID, $ID, $bCheckPermissions = false, $options = null)
	{
		if(!is_array($options))
		{
			$options = [];
		}

		$typeID = (int)$typeID;
		$ID = (int)$ID;

		if (!static::IsSliderEnabled($typeID))
		{
			return '';
		}

		if ($bCheckPermissions && !static::checkReadPermission($typeID, $ID))
		{
			return '';
		}

		$url = Container::getInstance()->getRouter()->getItemDetailUrl($typeID, $ID);

		if (is_null($url))
		{
			return '';
		}

		if($ID > 0 && !empty($options['INIT_MODE']))
		{
			$url->addParams(['init_mode' => mb_strtolower($options['INIT_MODE'])]);
		}

		if(isset($options['ENABLE_SLIDER']) && $options['ENABLE_SLIDER'] === true)
		{
			$url->addParams(['IFRAME' => 'Y', 'IFRAME_TYPE' => 'SIDE_SLIDER']);
		}

		return $url->getUri();
	}

	protected static function checkReadPermission(int $typeId, int $id): bool
	{
		return EntityAuthorization::checkReadPermission($typeId, $id);
	}

	public static function GetEditUrl($typeID, $ID, $bCheckPermissions = false, array $options = null)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if($ID <= 0)
		{
			$ID = 0;
		}

		switch($typeID)
		{
			case self::Lead:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmLead::CheckUpdatePermission($ID) : CCrmLead::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_lead_edit'),
					array('lead_id' => $ID)
				);
			}
			case self::Contact:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmContact::CheckUpdatePermission($ID) : CCrmContact::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_contact_edit'),
					array('contact_id' => $ID)
				);
			}
			case self::Company:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmCompany::CheckUpdatePermission($ID) : CCrmCompany::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_company_edit'),
					array('company_id' => $ID)
				);
			}
			case self::Deal:
			{
				$settings = is_array($options) && isset($options['ENTITY_SETTINGS']) ? $options['ENTITY_SETTINGS'] : array();
				$categoryId = isset($settings['categoryId']) ? (int)$settings['categoryId'] : -1;

				if ($bCheckPermissions && !($ID > 0 ? CCrmDeal::CheckUpdatePermission($ID) : CCrmDeal::CheckCreatePermission(null, $categoryId)))
				{
					return '';
				}

				$url = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_deal_edit'),
					array('deal_id' => $ID)
				);

				if($ID <= 0 && $categoryId >= 0)
				{
					$url = \CCrmUrlUtil::AddUrlParams($url, array('category_id' => $categoryId));
				}
				return $url;
			}
			case self::Invoice:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmInvoice::CheckUpdatePermission($ID) : CCrmInvoice::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_invoice_edit'),
					array('invoice_id' => $ID)
				);
			}
			case self::Quote:
			{
				if ($bCheckPermissions && !($ID > 0 ? CCrmQuote::CheckUpdatePermission($ID) : CCrmQuote::CheckCreatePermission()))
				{
					return '';
				}

				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_quote_edit'),
					array('quote_id' => $ID)
				);
			}
			case self::Order:
			{
				if ($bCheckPermissions && !($ID > 0 ? \Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($ID) : \Bitrix\Crm\Order\Permissions\Order::checkCreatePermission()))
				{
					return '';
				}

				return self::GetDetailsUrl($typeID, $ID, $bCheckPermissions, $options);
			}
			case self::Activity:
			{
				return CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', 'path_to_activity_edit'),
					array('activity_id' => $ID)
				);
			}
			default:
				return '';
		}
	}

	/**
	 *
	 * @param int $entityID
	 * @param int $fieldID
	 *
	 * @return string
	 *
	 * @deprecated Use Bitrix\Crm\UserField\Router::getEditUrl($entityID, $fieldID)
	 * @see Bitrix\Crm\UserField\Router::getEditUrl
	 */
	public static function GetUserFieldEditUrl($entityID, $fieldID)
	{
		$entityID = (string) $entityID;
		$fieldID = (int) $fieldID;

		return (new Bitrix\Crm\UserField\Router($entityID))->getEditUrl($fieldID);
	}

	public static function IsSliderEnabled($typeID)
	{
		$typeID = (int)$typeID;

		if ($typeID === CCrmOwnerType::Order
			|| $typeID === CCrmOwnerType::OrderCheck
			|| $typeID === CCrmOwnerType::OrderShipment
			|| $typeID === CCrmOwnerType::OrderPayment
			|| $typeID === CCrmOwnerType::StoreDocument
			|| $typeID === CCrmOwnerType::SmartInvoice
			|| $typeID === CCrmOwnerType::SmartDocument
		)
		{
			return true;
		}

		if (!\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled())
		{
			return false;
		}

		if ($typeID === static::Quote)
		{
			return \Bitrix\Crm\Settings\QuoteSettings::getCurrent()->isFactoryEnabled();
		}

		return $typeID === CCrmOwnerType::Lead
			|| $typeID === CCrmOwnerType::Deal
			|| $typeID === CCrmOwnerType::Quote
			|| $typeID === CCrmOwnerType::Contact
			|| $typeID === CCrmOwnerType::Company
			|| static::isPossibleDynamicTypeId($typeID);
	}

	public static function GetEntityShowPath($typeID, $ID, $bCheckPermissions = false, array $options = null)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		$enableSlider = self::IsSliderEnabled($typeID);
		if($enableSlider && $options !== null && isset($options['ENABLE_SLIDER']) && !$options['ENABLE_SLIDER'])
		{
			$enableSlider = false;
		}

		return $enableSlider
			? self::GetDetailsUrl($typeID, $ID, $bCheckPermissions, $options)
			: self::GetShowUrl($typeID, $ID, $bCheckPermissions);
	}

	public static function GetEntityEditPath($typeID, $ID, $bCheckPermissions = false, array $options = null)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		$enableSlider = self::IsSliderEnabled($typeID);
		if($enableSlider && $options !== null && isset($options['ENABLE_SLIDER']) && !$options['ENABLE_SLIDER'])
		{
			$enableSlider = false;
		}

		if($enableSlider)
		{
			if($ID > 0)
			{
				if(!is_array($options))
				{
					$options = array('INIT_MODE' => 'edit');
				}
				else
				{
					$options = array_merge($options, array('INIT_MODE' => 'edit'));
				}
			}
			return self::GetDetailsUrl($typeID, $ID, $bCheckPermissions, $options);
		}

		return self::GetEditUrl($typeID, $ID, $bCheckPermissions, $options);
	}

	public static function GetCaption($typeID, $ID, $checkRights = true, array $options = null)
	{
		$typeID = (int)$typeID;
		$ID = (int)$ID;

		if($ID <= 0)
		{
			return '';
		}

		$key = "{$typeID}_{$ID}";

		if(isset(self::$CAPTIONS[$key]))
		{
			return self::$CAPTIONS[$key];
		}

		if($options === null)
		{
			$options = array();
		}

		switch($typeID)
		{
			case self::Lead:
			{
				$arRes = isset($options['FIELDS']) ? $options['FIELDS'] : null;
				if(!is_array($arRes))
				{
					$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('TITLE', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME'));
					$arRes = $dbRes ? $dbRes->Fetch() : null;
				}

				if(!$arRes)
				{
					return (self::$CAPTIONS[$key] = '');
				}
				else
				{
					$caption = isset($arRes['TITLE']) ? $arRes['TITLE'] : '';
					if($caption === '')
					{
						$caption = CCrmLead::PrepareFormattedName(
							array(
								'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
								'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
								'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
								'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
							)
						);
					}
					return (self::$CAPTIONS[$key] = $caption);
				}
			}
			case self::Contact:
			{
				$arRes = isset($options['FIELDS']) ? $options['FIELDS'] : null;
				if(!is_array($arRes))
				{
					$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME'));
					$arRes = $dbRes ? $dbRes->Fetch() : null;
				}

				if(!$arRes)
				{
					return (self::$CAPTIONS[$key] = '');
				}
				else
				{
					return (self::$CAPTIONS[$key] =
						CCrmContact::PrepareFormattedName(
							array(
								'HONORIFIC' => isset($arRes['HONORIFIC']) ? $arRes['HONORIFIC'] : '',
								'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
								'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
								'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
							)
						)
					);
				}
			}
			case self::Company:
			{
				$arRes = isset($options['FIELDS']) ? $options['FIELDS'] : null;
				if(!is_array($arRes))
				{
					$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('TITLE'));
					$arRes = $dbRes ? $dbRes->Fetch() : null;
				}
				return (self::$CAPTIONS[$key] = $arRes ? $arRes['TITLE'] : '');
			}
			case self::Deal:
			{
				$arRes = isset($options['FIELDS']) ? $options['FIELDS'] : null;
				if(!is_array($arRes))
				{
					$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('TITLE'));
					$arRes = $dbRes ? $dbRes->Fetch() : null;
				}
				return (self::$CAPTIONS[$key] = $arRes ? $arRes['TITLE'] : '');
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(array(), array('ID' => $ID), false, false, array('ORDER_TOPIC'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return (self::$CAPTIONS[$key] = $arRes ? $arRes['ORDER_TOPIC'] : '');
			}
			case self::Quote:
			{
				$arRes = isset($options['FIELDS']) ? $options['FIELDS'] : null;
				if(!is_array($arRes))
				{
					$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('QUOTE_NUMBER', 'TITLE'));
					$arRes = $dbRes ? $dbRes->Fetch() : null;
				}
				$quoteTitle = empty($arRes['QUOTE_NUMBER']) ? '' : $arRes['QUOTE_NUMBER'];
				$quoteTitle = empty($arRes['TITLE']) ?
					$quoteTitle : (empty($quoteTitle) ? $arRes['TITLE'] : $quoteTitle.' - '.$arRes['TITLE']);
				$quoteTitle = empty($quoteTitle) ? '' : str_replace(array(';', ','), ' ', $quoteTitle);
				return $quoteTitle;
			}
			case self::Order:
			{
				$arRes = isset($options['FIELDS']) ? $options['FIELDS'] : null;

				if(!is_array($arRes))
				{
					// todo: 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')
					$dbRes = Bitrix\Crm\Order\Order::getList(array(
						'filter' => array('=ID' => $ID),
						'select' => array('ACCOUNT_NUMBER', 'ORDER_TOPIC')
					));

					$arRes = $dbRes->fetch();
				}

				$orderTitle = empty($arRes['ACCOUNT_NUMBER']) ? '' : $arRes['ACCOUNT_NUMBER'];
				$orderTitle = empty($arRes['ORDER_TOPIC']) ?
					$orderTitle : (empty($orderTitle) ? $arRes['ORDER_TOPIC'] : $orderTitle.' - '.$arRes['ORDER_TOPIC']);
				$orderTitle = empty($orderTitle) ? '' : str_replace(array(';', ','), ' ', $orderTitle);
				return $orderTitle;
			}

			case self::StoreDocument:
			{
				if (\Bitrix\Main\Loader::includeModule('catalog'))
				{
					$documentData = StoreDocumentTable::getList(
						[
							'select' => ['ID', 'DOC_TYPE', 'TITLE'],
							'filter' => ['ID' => $ID],
						]
					)->fetch();

					if ($documentData)
					{
						if (!empty($documentData['TITLE']))
						{
							return $documentData['TITLE'];
						}

						if (!empty($documentData['DOC_TYPE']))
						{
							$documentTitle = StoreDocumentTable::getTypeList(true)[$documentData['DOC_TYPE']];
							return $documentTitle;
						}
					}
					return '';
				}
				break;
			}

			case self::ShipmentDocument:
			{
				if
				(
					\Bitrix\Main\Loader::includeModule('catalog')
					&& \Bitrix\Main\Loader::includeModule('sale')
				)
				{
					$documentData = \Bitrix\Sale\Internals\ShipmentTable::getList(
						[
							'select' => ['ID', 'ORDER_ID', 'ACCOUNT_NUMBER'],
							'filter' => ['ID' => $ID],
						]
					)->fetch();

					if ($documentData)
					{
						if (!empty($documentData['ACCOUNT_NUMBER']))
						{
							$accountNumber = $documentData['ACCOUNT_NUMBER'];
						}
						else
						{
							$accountNumber = '';
						}

						return GetMessage('CRM_OWNER_TYPE_SHIPMENT_DOCUMENT', [
							"#ACCOUNT_NUMBER#" => $accountNumber
						]);
					}

					return '';
				}
				break;
			}

			case self::DealCategory:
			{
				$arRes = isset($options['FIELDS']) ? $options['FIELDS'] : null;
				if(!is_array($arRes) && (!$checkRights || Bitrix\Crm\Category\DealCategory::checkReadPermission()))
				{
					$arRes = Bitrix\Crm\Category\DealCategory::get($ID);
				}

				if(!$arRes)
				{
					return (self::$CAPTIONS[$key] = '');
				}
				else
				{
					$caption = isset($arRes['NAME']) ? $arRes['NAME'] : '';
					return (self::$CAPTIONS[$key] = $caption);
				}
			}
			case self::CustomActivityType:
			{
				$arRes = isset($options['FIELDS']) ? $options['FIELDS'] : null;
				if(!is_array($arRes))
				{
					$arRes = Bitrix\Crm\Activity\CustomType::get($ID);
				}

				if(!$arRes)
				{
					return (self::$CAPTIONS[$key] = '');
				}
				else
				{
					$caption = isset($arRes['NAME']) ? $arRes['NAME'] : '';
					return (self::$CAPTIONS[$key] = $caption);
				}
			}
		}

		if (static::isUseDynamicTypeBasedApproach($typeID))
		{
			$factory = Container::getInstance()->getFactory($typeID);
			if ($factory)
			{
				$item = $factory->getItem($ID);
				if (!$item)
				{
					self::$CAPTIONS[$key] = '';
					return '';
				}
				if ($checkRights && !Container::getInstance()->getUserPermissions()->canReadItem($item))
				{
					self::$CAPTIONS[$key] = '';
					return '';
				}
				self::$CAPTIONS[$key] = $item->getHeading();

				return self::$CAPTIONS[$key];
			}
		}

		return '';
	}
	public static function TryGetEntityInfo($typeID, $ID, &$info, $checkPermissions = true)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if(self::$INFO_STUB === null)
		{
			self::$INFO_STUB = [
				'TITLE' => '',
				'LEGEND' => '',
				'IMAGE_FILE_ID' => 0,
				'RESPONSIBLE_ID' => 0,
				'SHOW_URL' => '',
				'ENTITY_TYPE_CAPTION' => '',
			];
		}

		if($ID <= 0)
		{
			$info = self::$INFO_STUB;
			return false;
		}

		$key = "{$typeID}_{$ID}";

		if($checkPermissions && !CCrmAuthorizationHelper::CheckReadPermission($typeID, $ID))
		{
			$info = self::$INFO_STUB;
			return false;
		}

		if(isset(self::$INFOS[$key]))
		{
			if(is_array(self::$INFOS[$key]))
			{
				$info = self::$INFOS[$key];
				return true;
			}
			else
			{
				$info = self::$INFO_STUB;
				return false;
			}
		}

		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(
					array(),
					array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'HONORIFIC', 'TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'ASSIGNED_BY_ID')
				);
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				self::$INFOS[$key] = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => CCrmLead::PrepareFormattedName($arRes),
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' => self::GetEntityShowPath(self::Lead, $ID),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Lead),
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(
					array(),
					array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE', 'PHOTO', 'ASSIGNED_BY_ID')
				);

				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				self::$INFOS[$key] = array(
					'TITLE' => CCrmContact::PrepareFormattedName($arRes),
					'LEGEND' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => isset($arRes['PHOTO']) ? intval($arRes['PHOTO']) : 0,
					'SHOW_URL' => self::GetEntityShowPath(self::Contact, $ID),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Contact),
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(
					array(),
					array(
						'=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
						false,
						false,
						array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO', 'ASSIGNED_BY_ID')
				);

				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				if(self::$COMPANY_TYPE === null)
				{
					self::$COMPANY_TYPE = CCrmStatus::GetStatusList('COMPANY_TYPE');
				}
				if(self::$COMPANY_INDUSTRY === null)
				{
					self::$COMPANY_INDUSTRY = CCrmStatus::GetStatusList('INDUSTRY');
				}

				$legendParts = array();

				$typeID = isset($arRes['COMPANY_TYPE']) ? $arRes['COMPANY_TYPE'] : '';
				if($typeID !== '' && isset(self::$COMPANY_TYPE[$typeID]))
				{
					$legendParts[] = self::$COMPANY_TYPE[$typeID];
				}

				$industryID = isset($arRes['INDUSTRY']) ? $arRes['INDUSTRY'] : '';
				if($industryID !== '' && isset(self::$COMPANY_INDUSTRY[$industryID]))
				{
					$legendParts[] = self::$COMPANY_INDUSTRY[$industryID];
				}

				self::$INFOS[$key] = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => !empty($legendParts) ? implode(', ', $legendParts) : '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => isset($arRes['LOGO']) ? intval($arRes['LOGO']) : 0,
					'SHOW_URL' => self::GetEntityShowPath(self::Company, $ID),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Company),
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::DealRecurring:
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(
					array(),
					array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('TITLE', 'ASSIGNED_BY_ID', 'DATE_CREATE', 'OPPORTUNITY', 'CURRENCY_ID')
				);

				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				$date = new Bitrix\Main\Type\Date($arRes['DATE_CREATE']);

				self::$INFOS[$key] = array(
					'TITLE' => $arRes['TITLE'],
					'LEGEND' => GetMessage('CRM_OWNER_TYPE_DEAL_LEGEND', [
						"#DATE_CREATE#" =>  FormatDate(
							Bitrix\Main\Context::getCurrent()->getCulture()->getLongDateFormat(),
							$date->getTimestamp()
						),
						"#SUM_WITH_CURRENCY#" => \CCrmCurrency::MoneyToString(
							$arRes['OPPORTUNITY'],
							$arRes['CURRENCY_ID']
						),
					]),
					'OPPORTUNITY' => $arRes['OPPORTUNITY'],
					'CURRENCY_ID' => $arRes['CURRENCY_ID'],
					'DATE_CREATE' => $arRes['DATE_CREATE'],
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' => self::GetEntityShowPath(self::Deal, $ID),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Deal),
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(
					array(),
					array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ORDER_TOPIC', 'RESPONSIBLE_ID')
				);

				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				self::$INFOS[$key] = array(
					'TITLE' => isset($arRes['ORDER_TOPIC']) ? $arRes['ORDER_TOPIC'] : '',
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($arRes['RESPONSIBLE_ID']) ? intval($arRes['RESPONSIBLE_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' => static::GetEntityShowPath(static::Invoice, $ID),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Invoice),
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(
					array(),
					array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('TITLE', 'ASSIGNED_BY_ID')
				);

				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(!is_array($arRes))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				self::$INFOS[$key] = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' => static::GetEntityShowPath(self::Quote, $ID),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Quote),
				);

				$info = self::$INFOS[$key];
				return true;
			}
			case self::Order:
			{
				$result = \Bitrix\Crm\Order\Order::getList(
					array(
						'filter' => array('=ID' => $ID),
						'select' => array('ACCOUNT_NUMBER', 'CREATED_BY', 'RESPONSIBLE_ID'),
						'limit' => 1
					)
				);

				$result = $result ? $result->fetch() : null;
				if(!is_array($result))
				{
					self::$INFOS[$key] = false;
					$info = self::$INFO_STUB;
					return false;
				}

				self::$INFOS[$key] = array(
					'TITLE' => isset($result['ACCOUNT_NUMBER']) ? $result['ACCOUNT_NUMBER'] : '',
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($result['RESPONSIBLE_ID']) ? (int)($result['CREATED_BY']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' => Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
						->getOrderDetailsLink($ID),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Order),
				);

				$info = self::$INFOS[$key];
				return true;
			}

			default:
				$factory = Container::getInstance()->getFactory($typeID);
				if ($factory)
				{
					$item = $factory->getItems([
						'select' => [
							\Bitrix\Crm\Item::FIELD_NAME_ID,
							\Bitrix\Crm\Item::FIELD_NAME_TITLE,
							\Bitrix\Crm\Item::FIELD_NAME_ASSIGNED,
						],
						'filter' => [
							'=' . \Bitrix\Crm\Item::FIELD_NAME_ID => $ID,
						]
					])[0] ?? null;

					if ($item)
					{
						$info = [
							'TITLE' => $item->getHeading(),
							'LEGEND' => '',
							'RESPONSIBLE_ID' => $item->getAssignedById(),
							'IMAGE_FILE_ID' => 0,
							'SHOW_URL' => Container::getInstance()->getRouter()->getItemDetailUrl(
								$factory->getEntityTypeId(),
								$item->getId()
							),
							'ENTITY_TYPE_CAPTION' => $factory->getEntityDescription(),
						];

						self::$INFOS[$key] = $info;

						return true;
					}
				}
		}

		$info = self::$INFO_STUB;
		return false;
	}
	public static function PrepareEntityInfoBatch($typeID, array &$entityInfos, $checkPermissions = true, $options = null)
	{
		if(!is_array($options))
		{
			$options = array();
		}

		$IDs = array_keys($entityInfos);
		$dbRes = null;
		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('ID', 'HONORIFIC', 'TITLE', 'COMPANY_TITLE', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'ASSIGNED_BY_ID')
				);
				break;
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('ID', 'HONORIFIC', 'NAME', 'POST', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_ID', 'COMPANY_TITLE', 'PHOTO', 'ASSIGNED_BY_ID')
				);
				break;
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY',  'LOGO', 'ASSIGNED_BY_ID', 'IS_MY_COMPANY')
				);
				break;
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('TITLE', 'ASSIGNED_BY_ID', 'DATE_CREATE', 'CURRENCY_ID', 'OPPORTUNITY')
				);
				break;
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('ID', 'ORDER_TOPIC', 'RESPONSIBLE_ID')
				);
				break;
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(
					array(),
					array('@ID' => $IDs, 'CHECK_PERMISSIONS' => $checkPermissions ? 'Y' : 'N'),
					false,
					false,
					array('ID', 'TITLE', 'ASSIGNED_BY_ID', 'BEGINDATE', 'QUOTE_NUMBER')
				);
				break;
			}
			case self::Order:
			{
				$orderDB = \Bitrix\Crm\Order\Order::getList(
					array(
						'filter' => array('=ID' => $IDs),
						'select' => [
							'ID', 'ACCOUNT_NUMBER', 'CREATED_BY', 'DATE_INSERT',
							'RESPONSIBLE_ID', 'PRICE', 'CURRENCY', 'ORDER_TOPIC'
						]
					)
				);
				$dbRes = new \CDBResult($orderDB);
				break;
			}
			case self::OrderPayment:
			{
				$orderDB = \Bitrix\Crm\Order\Payment::getList(
					array(
						'filter' => array('=ID' => $IDs),
						'select' => [
							'ID', 'ACCOUNT_NUMBER', 'ORDER_ID', 'PAY_SYSTEM_NAME',
							'DATE_BILL', 'RESPONSIBLE_ID', 'SUM', 'CURRENCY', 'PAY_SYSTEM_ID'
						]
					)
				);
				$dbRes = new \CDBResult($orderDB);
				break;
			}
			case self::OrderShipment:
			{
				$orderDB = \Bitrix\Crm\Order\Shipment::getList(
					array(
						'filter' => array('=ID' => $IDs),
						'select' => [
							'ID', 'ACCOUNT_NUMBER', 'ORDER_ID', 'DATE_INSERT',
							'PRICE_DELIVERY', 'CURRENCY', 'RESPONSIBLE_ID', 'DELIVERY_NAME'
						]
					)
				);
				$dbRes = new \CDBResult($orderDB);
				break;
			}
			case self::StoreDocument:
			{
				if (\Bitrix\Main\Loader::includeModule('catalog'))
				{
					$documentDB = StoreDocumentTable::getList(
						[
							'select' => ['ID', 'DOC_TYPE', 'TITLE', 'DATE_CREATE', 'TOTAL', 'CURRENCY'],
							'filter' => ['ID' => $IDs],
						]
					);
					$dbRes = new \CDBResult($documentDB);
				}
				break;
			}
			case self::SuspendedLead:
			{
				$dbRes = new \CDBResult();
				$dbRes->InitFromArray(
					\Bitrix\Crm\Recycling\LeadController::getInstance()->getEntityInfos($IDs)
				);
				break;
			}
			case self::SuspendedContact:
			{
				$dbRes = new \CDBResult();
				$dbRes->InitFromArray(
					\Bitrix\Crm\Recycling\ContactController::getInstance()->getEntityInfos($IDs)
				);
				break;
			}
			case self::SuspendedCompany:
			{
				$dbRes = new \CDBResult();
				$dbRes->InitFromArray(
					\Bitrix\Crm\Recycling\CompanyController::getInstance()->getEntityInfos($IDs)
				);
				break;
			}
			case self::SuspendedDeal:
			{
				$dbRes = new \CDBResult();
				$dbRes->InitFromArray(
					\Bitrix\Crm\Recycling\DealController::getInstance()->getEntityInfos($IDs)
				);
				break;
			}
		}

		if(!is_object($dbRes))
		{
			$factory = Container::getInstance()->getFactory($typeID);
			if (!$factory)
			{
				return;
			}

			$params = [
				'select' => [
					\Bitrix\Crm\Item::FIELD_NAME_ID,
					\Bitrix\Crm\Item::FIELD_NAME_TITLE,
					\Bitrix\Crm\Item::FIELD_NAME_ASSIGNED,
				],
				'filter' => [
					'@ID' => $IDs,
				],
			];

			if ($checkPermissions)
			{
				$items = $factory->getItemsFilteredByPermissions($params);
			}
			else
			{
				$items = $factory->getItems($params);
			}
			$data = [];
			foreach ($items as $item)
			{
				$itemData = $item->getCompatibleData();
				$itemData['TITLE'] = $item->getHeading();
				$data[] = $itemData;
			}
			$dbRes = new \CDBResult();
			$dbRes->InitFromArray($data);
		}

		$enableResponsible = isset($options['ENABLE_RESPONSIBLE']) && $options['ENABLE_RESPONSIBLE'] === true;
		$userIDs = null;
		while($arRes = $dbRes->Fetch())
		{
			$ID = intval($arRes['ID']);
			if(!isset($entityInfos[$ID]))
			{
				continue;
			}

			$info = self::PrepareEntityInfo($typeID, $ID, $arRes, $options);
			if(!is_array($info) || empty($info))
			{
				continue;
			}

			if($enableResponsible)
			{
				$responsibleID = $info['RESPONSIBLE_ID'];
				if($responsibleID > 0)
				{
					if($userIDs === null)
					{
						$userIDs = array($responsibleID);
					}
					elseif(!in_array($responsibleID, $userIDs, true))
					{
						$userIDs[] = $responsibleID;
					}
				}
			}

			$entityInfos[$ID] = array_merge($entityInfos[$ID], $info);
		}

		if($enableResponsible && is_array($userIDs) && !empty($userIDs))
		{
			$enablePhoto = isset($options['ENABLE_RESPONSIBLE_PHOTO']) ? $options['ENABLE_RESPONSIBLE_PHOTO'] : true;
			$userSelect = array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'TITLE', 'EMAIL', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE');
			if($enablePhoto)
			{
				$userSelect[] = 'PERSONAL_PHOTO';
			}

			$dbUsers = CUser::GetList(
				'id', 'asc',
				array('ID' => implode('|', $userIDs)),
				array('FIELDS' => $userSelect)
			);

			$photoSize = null;
			if($enablePhoto)
			{
				$photoSize = isset($options['PHOTO_SIZE']) ? $options['PHOTO_SIZE'] : array();
				if(!isset($photoSize['WIDTH']) || !isset($photoSize['HEIGHT']))
				{
					if(isset($photoSize['WIDTH']))
					{
						$photoSize['HEIGHT'] = $photoSize['WIDTH'];
					}
					elseif(isset($photoSize['HEIGHT']))
					{
						$photoSize['WIDTH'] = $photoSize['HEIGHT'];
					}
					else
					{
						$photoSize['WIDTH'] = $photoSize['HEIGHT'] = 50;
					}
				}
			}

			$userProfilePath = isset($options['USER_PROFILE_PATH']) ? $options['USER_PROFILE_PATH'] : '';
			$userInfos = array();
			while($user = $dbUsers->Fetch())
			{
				$userID = intval($user['ID']);
				$personalPhone =  isset($user['PERSONAL_PHONE']) ? $user['PERSONAL_PHONE'] : '';
				$personalMobile =  isset($user['PERSONAL_MOBILE']) ? $user['PERSONAL_MOBILE'] : '';
				$workPhone =  isset($user['WORK_PHONE']) ? $user['WORK_PHONE'] : '';
				$userPhone = $workPhone !== '' ? $workPhone : ($personalMobile !== '' ? $personalMobile : $personalPhone);

				$userInfo = array(
					'FORMATTED_NAME' => CUser::FormatName(
						CSite::GetNameFormat(false),
						$user,
						true,
						false
					),
					'EMAIL' => isset($user['EMAIL']) ? $user['EMAIL'] : '',
					'PHONE' => $userPhone
				);

				if($enablePhoto)
				{
					$photoID = isset($user['PERSONAL_PHOTO']) ? intval($user['PERSONAL_PHOTO']) : 0;
					if($photoID > 0)
					{
						$photoUrl = CFile::ResizeImageGet(
							$photoID,
							array('width' => $photoSize['WIDTH'], 'height' => $photoSize['HEIGHT']),
							BX_RESIZE_IMAGE_EXACT
						);
						$userInfo['PHOTO_URL'] = $photoUrl['src'];
					}
				}

				if($userProfilePath !== '')
				{
					$userInfo['URL'] = CComponentEngine::MakePathFromTemplate(
						$userProfilePath,
						array('user_id' => $userID)
					);
				}

				$userInfos[$userID] = &$userInfo;
				unset($userInfo);
			}

			if(!empty($userInfos))
			{
				foreach($entityInfos as &$info)
				{
					$responsibleID = $info['RESPONSIBLE_ID'];
					if($responsibleID > 0 && isset($userInfos[$responsibleID]))
					{
						$userInfo = $userInfos[$responsibleID];
						$info['RESPONSIBLE_FULL_NAME'] = $userInfo['FORMATTED_NAME'];

						if(isset($userInfo['PHOTO_URL']))
						{
							$info['RESPONSIBLE_PHOTO_URL'] = $userInfo['PHOTO_URL'];
						}

						if(isset($userInfo['URL']))
						{
							$info['RESPONSIBLE_URL'] = $userInfo['URL'];
						}

						if(isset($userInfo['EMAIL']))
						{
							$info['RESPONSIBLE_EMAIL'] = $userInfo['EMAIL'];
						}

						if(isset($userInfo['PHONE']))
						{
							$info['RESPONSIBLE_PHONE'] = $userInfo['PHONE'];
						}
					}
				}
				unset($info);
			}
		}
	}
	private static function PrepareEntityInfo($typeID, $ID, &$arRes, $options = null)
	{
		$enableSlider = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();
		$enableEditUrl = is_array($options) && isset($options['ENABLE_EDIT_URL']) && $options['ENABLE_EDIT_URL'] === true;

		switch($typeID)
		{
			case self::Lead:
			{
				$treatAsContact = false;
				$treatAsCompany = false;

				if(is_array($options))
				{
					$treatAsContact = isset($options['TREAT_AS_CONTACT']) && $options['TREAT_AS_CONTACT'];
					$treatAsCompany = isset($options['TREAT_AS_COMPANY']) && $options['TREAT_AS_COMPANY'];
				}

				if($treatAsContact)
				{
					$result = array(
						'TITLE' => CCrmLead::PrepareFormattedName($arRes),
						'LEGEND' => isset($arRes['TITLE']) ? $arRes['TITLE'] : ''
					);
				}
				elseif($treatAsCompany)
				{
					$result = array(
						'TITLE' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
						'LEGEND' => isset($arRes['TITLE']) ? $arRes['TITLE'] : ''
					);
				}
				else
				{
					$result = array(
						'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'LEGEND' => CCrmLead::PrepareFormattedName($arRes)
					);
				}

				$result['RESPONSIBLE_ID'] = isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				$result['IMAGE_FILE_ID'] = 0;
				$result['SHOW_URL'] = CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('crm', $enableSlider ? 'path_to_lead_details' : 'path_to_lead_show'),
					array('lead_id' => $ID)
				);
				$result['ENTITY_TYPE_CAPTION'] = static::GetDescription(static::Lead);

				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', $enableSlider ? 'path_to_lead_details' :'path_to_lead_edit'),
							array('lead_id' => $ID)
						);
				}
				return $result;
			}
			case self::Contact:
			{
				$result = array(
					'TITLE' => CCrmContact::PrepareFormattedName($arRes),
					'LEGEND' => isset($arRes['COMPANY_TITLE']) ? $arRes['COMPANY_TITLE'] : '',
					'POST' => isset($arRes['POST']) ? $arRes['POST'] : '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => isset($arRes['PHOTO']) ? intval($arRes['PHOTO']) : 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', $enableSlider ? 'path_to_contact_details' : 'path_to_contact_show'),
							array('contact_id' => $ID)
						),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Contact),
				);
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', $enableSlider ? 'path_to_contact_details' : 'path_to_contact_edit'),
							array('contact_id' => $ID)
						);
				}
				return $result;
			}
			case self::Company:
			{
				if(self::$COMPANY_TYPE === null)
				{
					self::$COMPANY_TYPE = CCrmStatus::GetStatusList('COMPANY_TYPE');
				}
				if(self::$COMPANY_INDUSTRY === null)
				{
					self::$COMPANY_INDUSTRY = CCrmStatus::GetStatusList('INDUSTRY');
				}

				$legendParts = array();

				$typeID = isset($arRes['COMPANY_TYPE']) ? $arRes['COMPANY_TYPE'] : '';
				if($typeID !== '' && isset(self::$COMPANY_TYPE[$typeID]))
				{
					$legendParts[] = self::$COMPANY_TYPE[$typeID];
				}

				$industryID = isset($arRes['INDUSTRY']) ? $arRes['INDUSTRY'] : '';
				if($industryID !== '' && isset(self::$COMPANY_INDUSTRY[$industryID]))
				{
					$legendParts[] = self::$COMPANY_INDUSTRY[$industryID];
				}

				$result = array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => !empty($legendParts) ? implode(', ', $legendParts) : '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => isset($arRes['LOGO']) ? intval($arRes['LOGO']) : 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', $enableSlider ? 'path_to_company_details' : 'path_to_company_show'),
							array('company_id' => $ID)
						),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Company),
					'IS_MY_COMPANY' => isset($arRes['IS_MY_COMPANY']) ? ($arRes['IS_MY_COMPANY'] === 'Y') : false,
				);
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', $enableSlider ? 'path_to_company_details' : 'path_to_company_edit'),
							array('company_id' => $ID)
						);
				}
				return $result;
			}
			case self::Deal:
			{
				$date = new Bitrix\Main\Type\Date($arRes['DATE_CREATE']);

				$result = [
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'LEGEND' => GetMessage('CRM_OWNER_TYPE_DEAL_LEGEND', [
						"#DATE_CREATE#" =>  FormatDate(
							Bitrix\Main\Context::getCurrent()->getCulture()->getLongDateFormat(),
							$date->getTimestamp()
						),
						"#SUM_WITH_CURRENCY#" => \CCrmCurrency::MoneyToString(
							$arRes['OPPORTUNITY'],
							$arRes['CURRENCY_ID']
						),
					]),
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', $enableSlider ? 'path_to_deal_details' : 'path_to_deal_show'),
							['deal_id' => $ID]
						),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Deal),
				];
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', $enableSlider ? 'path_to_deal_details' : 'path_to_deal_edit'),
							array('deal_id' => $ID)
						);
				}
				return $result;
			}
			case self::Invoice:
			{
				$result = array(
					'TITLE' => isset($arRes['ORDER_TOPIC']) ? $arRes['ORDER_TOPIC'] : '',
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($arRes['RESPONSIBLE_ID']) ? intval($arRes['RESPONSIBLE_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_invoice_show'),
							array('invoice_id' => $ID)
						),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Invoice),
				);
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_invoice_edit'),
							array('invoice_id' => $ID)
						);
				}
				return $result;
			}
			case self::Quote:
			{
				$title = $arRes['TITLE'] ?? '';
				if (empty($title))
				{
					$title = Bitrix\Crm\Item\Quote::getTitlePlaceholderFromData($arRes);
				}
				$result = array(
					'TITLE' => $title,
					'LEGEND' => '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' => Container::getInstance()->getRouter()->getItemDetailUrl(
						static::Quote,
						$arRes['ID']
					),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Quote),
				);
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_quote_edit'),
							array('quote_id' => $ID)
						);
				}
				return $result;
			}
			case self::Order:
			{
				$title = $arRes['ORDER_TOPIC'];
				if ($title == '')
				{
					$number = $arRes['ACCOUNT_NUMBER'] ? $arRes['ACCOUNT_NUMBER'] : $arRes['ID'];
					$title = GetMessage('CRM_OWNER_TYPE_ORDER_TITLE', [
						"#ACCOUNT_NUMBER#" => $number
					]);
				}

				$culture = Bitrix\Main\Context::getCurrent()->getCulture();
				$dateInsert = '';
				if ($arRes['DATE_INSERT'] instanceof \Bitrix\Main\Type\Date && $culture)
				{
					$dateInsert = FormatDate($culture->getLongDateFormat(), $arRes['DATE_INSERT']->getTimestamp());
				}
				$result = array(
					'ID' => $arRes['ID'],
					'TITLE' => $title,
					'ACCOUNT_NUMBER' => $arRes['ACCOUNT_NUMBER'],
					'ORDER_TOPIC' => $arRes['ORDER_TOPIC'],
					'LEGEND' => GetMessage('CRM_OWNER_TYPE_ORDER_LEGEND', [
						"#SUM_WITH_CURRENCY#" => \CCrmCurrency::MoneyToString($arRes['PRICE'], $arRes['CURRENCY']),
						"#DATE_INSERT#" => $dateInsert,
					]),
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' =>
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_order_details'),
							array('order_id' => $ID)
						),
					'DATE' => $dateInsert,
					'SUM_WITH_CURRENCY' => \CCrmCurrency::MoneyToString($arRes['PRICE'], $arRes['CURRENCY']),
					'PRICE' => $arRes['PRICE'],
					'CURRENCY' => $arRes['CURRENCY'],
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::Order),
				);
				if($enableEditUrl)
				{
					$result['EDIT_URL'] =
						CComponentEngine::MakePathFromTemplate(
							COption::GetOptionString('crm', 'path_to_order_edit'),
							array('order_id' => $ID)
						);
				}
				return $result;
			}
			case self::OrderPayment:
			{
				$handler = Bitrix\Sale\PaySystem\Manager::getById($arRes['PAY_SYSTEM_ID']);
				$logotip = null;
				if ($handler['LOGOTIP']
					&& $arFile = \CFile::GetFileArray($handler['LOGOTIP'])
				)
				{
					$logotip = $arFile['SRC'];
				}

				$dateInsert = '';
				$culture = Bitrix\Main\Context::getCurrent()->getCulture();
				if ($arRes['DATE_BILL'] instanceof \Bitrix\Main\Type\Date && $culture)
				{
					$dateInsert = FormatDate($culture->getLongDateFormat(), $arRes['DATE_BILL']->getTimestamp());
				}
				$result = array(
					'ID' => $arRes['ID'],
					'ORDER_ID' => $arRes['ORDER_ID'],
					'TITLE' => isset($arRes['ACCOUNT_NUMBER']) ?  $arRes['ACCOUNT_NUMBER'] : '',
					'ACCOUNT_NUMBER' => isset($arRes['ACCOUNT_NUMBER']) ?  $arRes['ACCOUNT_NUMBER'] : '',
					'LEGEND' => GetMessage('CRM_OWNER_TYPE_ORDER_PAYMENT_LEGEND', [
						"#SUM_WITH_CURRENCY#" => \CCrmCurrency::MoneyToString($arRes['SUM'], $arRes['CURRENCY']),
						"#DATE_BILL#" => $dateInsert
					]),
					'SUBLEGEND' => GetMessage('CRM_OWNER_TYPE_ORDER_PAYMENT_SUBLEGEND', [
						"#PAY_SYSTEM_NAME#" => $arRes['PAY_SYSTEM_NAME']
					]),
					'RESPONSIBLE_ID' => isset($arRes['RESPONSIBLE_ID']) ? intval($arRes['RESPONSIBLE_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' => Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
						->getPaymentDetailsLink($ID),
					'LOGOTIP' => $logotip,
					'DATE' => $dateInsert,
					'PAY_SYSTEM_NAME' => $arRes['PAY_SYSTEM_NAME'],
					'SUM' => \CCrmCurrency::MoneyToString($arRes['SUM'], $arRes['CURRENCY'], '#'),
					'CURRENCY' => \CCrmCurrency::GetCurrencyText($arRes['CURRENCY']),
					'RAW_SUM' => $arRes['SUM'],
					'RAW_CURRENCY' => $arRes['CURRENCY'],
					'SUM_WITH_CURRENCY' => \CCrmCurrency::MoneyToString($arRes['SUM'], $arRes['CURRENCY']),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::OrderPayment),
				);
				if ($enableEditUrl)
				{
					$result['EDIT_URL'] = Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
						->getPaymentDetailsLink($ID);
				}
				return $result;
			}
			case self::OrderShipment:
			{
				$dateInsert = '';
				$culture = Bitrix\Main\Context::getCurrent()->getCulture();
				if ($arRes['DATE_INSERT'] instanceof \Bitrix\Main\Type\Date && $culture)
				{
					$dateInsert = FormatDate($culture->getLongDateFormat(), $arRes['DATE_INSERT']->getTimestamp());
				}

				$result = [
					'ID' => $arRes['ID'],
					'DATE_INSERT' => $arRes['DATE_INSERT'],
					'DATE_INSERT_FORMATTED' => $dateInsert,
					'TITLE' => isset($arRes['ACCOUNT_NUMBER']) ? $arRes['ACCOUNT_NUMBER'] : '',
					'ACCOUNT_NUMBER' => isset($arRes['ACCOUNT_NUMBER']) ?  $arRes['ACCOUNT_NUMBER'] : '',
					'LEGEND' => GetMessage('CRM_OWNER_TYPE_ORDER_SHIPMENT_LEGEND_2', [
						"#DATE_INSERT#" => $dateInsert,
						"#PRICE_DELIVERY_WITH_CURRENCY#" => \CCrmCurrency::MoneyToString($arRes['PRICE_DELIVERY'], $arRes['CURRENCY']),
					]),
					'SUBLEGEND' => GetMessage('CRM_OWNER_TYPE_ORDER_SHIPMENT_SUBLEGEND', [
						"#DELIVERY_NAME#" => $arRes['DELIVERY_NAME']
					]),
					'PRICE_DELIVERY' => $arRes['PRICE_DELIVERY'],
					'CURRENCY' => $arRes['CURRENCY'],
					'DELIVERY_NAME' => $arRes['DELIVERY_NAME'],
					'RESPONSIBLE_ID' => isset($arRes['RESPONSIBLE_ID']) ? intval($arRes['RESPONSIBLE_ID']) : 0,
					'IMAGE_FILE_ID' => 0,
					'SHOW_URL' => Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
						->getShipmentDetailsLink($ID),
					'ENTITY_TYPE_CAPTION' => static::GetDescription(static::OrderShipment),
				];
				if ($enableEditUrl)
				{
					$result['EDIT_URL'] = Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
						->getShipmentDetailsLink($ID);
				}
				return $result;
			}
			case self::StoreDocument:
				$culture = Bitrix\Main\Context::getCurrent()->getCulture();
				$dateCreate = '';
				if ($arRes['DATE_CREATE'] instanceof \Bitrix\Main\Type\Date && $culture)
				{
					$dateCreate = FormatDate($culture->getShortDateFormat(), $arRes['DATE_CREATE']->getTimestamp());
				}
				return [
					'ID' => $arRes['ID'] ?? 0,
					'TITLE' => $arRes['TITLE'] ?? '',
					'DOC_TYPE' => $arRes['DOC_TYPE'] ?? '',
					'DATE_CREATE' => $dateCreate,
					'TOTAL' => $arRes['TOTAL'] ?? 0,
					'CURRENCY' => $arRes['CURRENCY'] ?? CCrmCurrency::GetDefaultCurrencyID(),
				];
			case self::SuspendedLead:
			case self::SuspendedContact:
			case self::SuspendedCompany:
			case self::SuspendedDeal:
			{
				return array(
					'TITLE' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
					'RESPONSIBLE_ID' => isset($arRes['ASSIGNED_BY_ID']) ? intval($arRes['ASSIGNED_BY_ID']) : 0,
					'ENTITY_TYPE_CAPTION' => static::GetDescription($typeID),
				);
			}
		}

		if (static::isUseDynamicTypeBasedApproach($typeID))
		{
			$factory = Container::getInstance()->getFactory((int)$typeID);

			return [
				'TITLE' => $arRes[\Bitrix\Crm\Item::FIELD_NAME_TITLE] ?? '',
				'RESPONSIBLE_ID' => $arRes[\Bitrix\Crm\Item::FIELD_NAME_ASSIGNED] ?? 0,
				'SHOW_URL' => Container::getInstance()->getRouter()->getItemDetailUrl(
					$typeID,
					$arRes['ID'] ?? 0
				)->getUri(),
				'ENTITY_TYPE_CAPTION' => $factory ? $factory->getEntityDescription() : '',
			];
		}

		return null;
	}

	public static function ResolveUserFieldEntityID($typeID): string
	{
		$typeID = (int)$typeID;
		if($typeID <= 0)
		{
			return '';
		}

		switch($typeID)
		{
			case self::Activity:
				return CAllCrmActivity::UF_ENTITY_TYPE;
			case self::SuspendedActivity:
				return CAllCrmActivity::UF_SUSPENDED_ENTITY_TYPE;
			case self::Lead:
				return CAllCrmLead::USER_FIELD_ENTITY_ID;
			case self::SuspendedLead:
				return CAllCrmLead::SUSPENDED_USER_FIELD_ENTITY_ID;
			case self::Deal:
				return CAllCrmDeal::USER_FIELD_ENTITY_ID;
			case self::SuspendedDeal:
				return CAllCrmDeal::SUSPENDED_USER_FIELD_ENTITY_ID;
			case self::Contact:
				return CAllCrmContact::USER_FIELD_ENTITY_ID;
			case self::SuspendedContact:
				return CAllCrmContact::SUSPENDED_USER_FIELD_ENTITY_ID;
			case self::Company:
				return CAllCrmCompany::USER_FIELD_ENTITY_ID;
			case self::SuspendedCompany:
				return CAllCrmCompany::SUSPENDED_USER_FIELD_ENTITY_ID;
			case self::Quote:
				return CAllCrmQuote::USER_FIELD_ENTITY_ID;
			case self::SuspendedQuote:
				return CAllCrmQuote::SUSPENDED_USER_FIELD_ENTITY_ID;
			case self::Invoice:
				return CAllCrmInvoice::USER_FIELD_ENTITY_ID;
			case self::SuspendedInvoice:
				return CAllCrmInvoice::SUSPENDED_USER_FIELD_ENTITY_ID;
			case self::Order:
				return \Bitrix\Crm\Order\Manager::getUfId();
			case self::Requisite:
				$requisite = new \Bitrix\Crm\EntityRequisite();
				$ufId = $requisite->getUfId();
				unset($requisite);
				return $ufId;
			case self::SmartInvoice:
				return \Bitrix\Crm\Service\Factory\SmartInvoice::USER_FIELD_ENTITY_ID;
			case self::SuspendedSmartInvoice:
				return \Bitrix\Crm\Service\Factory\SmartInvoice::SUSPENDED_USER_FIELD_ENTITY_ID;
			case self::SmartDocument:
				return \Bitrix\Crm\Service\Factory\SmartDocument::USER_FIELD_ENTITY_ID;
			case self::SuspendedSmartDocument:
				return \Bitrix\Crm\Service\Factory\SmartDocument::SUSPENDED_USER_FIELD_ENTITY_ID;
			case self::Undefined:
				return '';
			default:
				if(static::isPossibleSuspendedDynamicTypeId($typeID))
				{
					$typeID = \CCrmOwnerType::getRealDynamicTypeId($typeID);
					$type = Container::getInstance()->getTypeByEntityTypeId($typeID);
					if($type)
					{
						return ServiceLocator::getInstance()->get('crm.type.factory')->getUserFieldSuspendedEntityId($type->getId());
					}
				}
				if(static::isPossibleDynamicTypeId($typeID))
				{
					$type = Container::getInstance()->getTypeByEntityTypeId($typeID);
					if($type)
					{
						return ServiceLocator::getInstance()->get('crm.type.factory')->getUserFieldEntityId($type->getId());
					}
				}

				return '';
		}
	}

	/**
	 * @param string $userFieldEntityId
	 * @return int|string|null
	 */
	public static function ResolveIDByUFEntityID($userFieldEntityId)
	{
		if($userFieldEntityId === '')
		{
			return '';
		}

		$requisite = new \Bitrix\Crm\EntityRequisite();
		$requisiteUfId = $requisite->getUfId();
		unset($requisite);

		switch($userFieldEntityId)
		{
			case CAllCrmLead::$sUFEntityID:
				return self::Lead;
			case CAllCrmDeal::$sUFEntityID:
				return self::Deal;
			case CAllCrmContact::$sUFEntityID:
				return self::Contact;
			case CAllCrmCompany::$sUFEntityID:
				return self::Company;
			case CAllCrmInvoice::$sUFEntityID:
				return self::Invoice;
			case CAllCrmQuote::$sUFEntityID:
				return self::Quote;
			case $requisiteUfId:
				return self::Requisite;
			case \Bitrix\Crm\Order\Manager::getUfId():
				return self::Order;
			case \Bitrix\Crm\Service\Factory\SmartInvoice::USER_FIELD_ENTITY_ID:
				return self::SmartInvoice;
			case \Bitrix\Crm\Service\Factory\SmartDocument::USER_FIELD_ENTITY_ID:
				return self::SmartDocument;
		}

		if (preg_match('/CRM_(\d+)/', $userFieldEntityId, $matches))
		{
			$type = Container::getInstance()->getType($matches[1]);
			if ($type)
			{
				return $type->getEntityTypeId();
			}
		}

		return self::Undefined;
	}

	private static function GetFields($typeID, $ID, $options = array())
	{
		$typeID = intval($typeID);
		$ID = intval($ID);
		$options = is_array($options) ? $options : array();

		$select = isset($options['SELECT']) ? $options['SELECT'] : array();
		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(array(), array('ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID), false, false, $select);
				return $dbRes ? $dbRes->Fetch() : null;
			}
			case self::Requisite:
			{
				$requisite = new \Bitrix\Crm\EntityRequisite();
				$dbRes = $requisite->getList(array(
					'filter' => array('=ID' => $ID),
					'select' => $select
				));
				return $dbRes ? $dbRes->fetch() : null;
			}
		}

		return null;
	}

	public static function GetFieldsInfo($typeID)
	{
		$typeID = intval($typeID);

		switch($typeID)
		{
			case self::Lead:
			{
				return CCrmLead::GetFieldsInfo();
			}
			case self::Contact:
			{
				return CCrmContact::GetFieldsInfo();
			}
			case self::Company:
			{
				return CCrmCompany::GetFieldsInfo();
			}
			case self::Deal:
			{
				return CCrmDeal::GetFieldsInfo();
			}
			case self::Quote:
			{
				return CCrmQuote::GetFieldsInfo();
			}
		}

		return null;
	}

	public static function GetFieldIntValue($typeID, $ID, $fieldName)
	{
		$fields = self::GetFields($typeID, $ID, array('SELECT' => array($fieldName)));
		return is_array($fields) && isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
	}

	public static function loadResponsibleId(int $entityTypeId, int $entityId, bool $checkRights = true): int
	{
		$result = 0;

		switch($entityTypeId)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $entityId, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				break;
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $entityId, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				break;
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $entityId, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				break;
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $entityId, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				break;
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(array(), array('ID' => $entityId), false, false, array('RESPONSIBLE_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['RESPONSIBLE_ID']) : 0;
				break;
			}
			case self::Activity:
			{
				$dbRes = CCrmActivity::GetList(array(), array('=ID' => $entityId, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('RESPONSIBLE_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['RESPONSIBLE_ID']) : 0;
				break;
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $entityId, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('ASSIGNED_BY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				$result = $arRes ? intval($arRes['ASSIGNED_BY_ID']) : 0;
				break;
			}
			case self::Order:
			{
				if($checkRights && !\Bitrix\Crm\Order\Permissions\Order::checkReadPermission($entityId))
				{
					break;
				}

				$dbRes = Bitrix\Crm\Order\Order::getList(array('filter' => array('=ID' => $entityId), 'select' => array('RESPONSIBLE_ID')));
				$arRes = $dbRes ? $dbRes->fetch() : null;
				$result = $arRes ? intval($arRes['RESPONSIBLE_ID']) : 0;
				break;
			}
		}

		if ($result === 0 && static::isUseDynamicTypeBasedApproach($entityTypeId))
		{
			$factory = Container::getInstance()->getFactory($entityTypeId);
			if ($factory)
			{
				$parameters = [
					'select' => [
						\Bitrix\Crm\Item::FIELD_NAME_ASSIGNED,
					],
					'filter' => [
						'=ID' => $entityId,
					],
					'limit' => 1,
				];
				if ($checkRights)
				{
					$items = $factory->getItemsFilteredByPermissions($parameters);
				}
				else
				{
					$items = $factory->getItems($parameters);
				}
				if (count($items) > 0 && $items[0] && $items[0] instanceof \Bitrix\Crm\Item)
				{
					$result = (int)$items[0]->getAssignedById();
				}
			}
		}

		return $result;
	}

	public static function GetResponsibleID($typeID, $ID, $checkRights = true)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);
		$checkRights = $checkRights === true;

		if(!(self::IsDefined($typeID) && $ID > 0))
		{
			return 0;
		}

		$key = "{$typeID}_{$ID}_" . ($checkRights ? 'Y' : 'N');
		if(isset(self::$RESPONSIBLES[$key]))
		{
			return self::$RESPONSIBLES[$key];
		}

		$result = static::loadResponsibleId($typeID, $ID, $checkRights);

		self::$RESPONSIBLES[$key] = $result;
		return $result;
	}

	public static function IsOpened($typeID, $ID, $checkRights = true)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('OPENED'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return ($arRes && $arRes['OPENED'] == 'Y');
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('OPENED'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return ($arRes && $arRes['OPENED'] == 'Y');
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('OPENED'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return ($arRes && $arRes['OPENED'] == 'Y');
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('OPENED'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return ($arRes && $arRes['OPENED'] == 'Y');
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($checkRights ? 'Y' : 'N')), false, false, array('OPENED'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				return ($arRes && $arRes['OPENED'] == 'Y');
			}
		}

		if (static::isUseFactoryBasedApproach($typeID))
		{
			$factory = Container::getInstance()->getFactory($typeID);
			if ($factory)
			{
				$item = $factory->getItem($ID);
				if ($item && $item->hasField(\Bitrix\Crm\Item::FIELD_NAME_OPENED))
				{
					return $item->getOpened();
				}
			}
		}

		return false;
	}

	public static function TryGetOwnerInfos($typeID, $ID, &$owners, $options = array())
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if(!is_array($options))
		{
			$options = array();
		}

		$entityTypeIDKey = isset($options['ENTITY_TYPE_ID_KEY']) ? $options['ENTITY_TYPE_ID_KEY'] : '';
		if($entityTypeIDKey === '')
		{
			$entityTypeIDKey = 'ENTITY_TYPE_ID';
		}

		$entityIDKey = isset($options['ENTITY_ID_KEY']) ? $options['ENTITY_ID_KEY'] : '';
		if($entityIDKey === '')
		{
			$entityIDKey = 'ENTITY_ID';
		}

		$additionalData = isset($options['ADDITIONAL_DATA']) && is_array($options['ADDITIONAL_DATA']) ? $options['ADDITIONAL_DATA'] : null;
		$enableMapping = isset($options['ENABLE_MAPPING']) ? (bool)$options['ENABLE_MAPPING'] : false;

		$bindingThreshold = 500;
		switch($typeID)
		{
			case self::Contact:
			{
				$companyIDs = \Bitrix\Crm\Binding\ContactCompanyTable::getContactCompanyIDs($ID);
				for($i = 0, $length = min(count($companyIDs), $bindingThreshold); $i < $length; $i++)
				{
					$companyID = $companyIDs[$i];
					$info = array(
						$entityTypeIDKey => self::Company,
						$entityIDKey => $companyID
					);

					if($additionalData !== null)
					{
						$info = array_merge($info, $additionalData);
					}

					if($enableMapping)
					{
						$owners[self::Company.'_'.$companyID] = $info;
					}
					else
					{
						$owners[] = $info;
					}
				}
				return true;
			}
			//break;
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID), false, false, array('CONTACT_ID', 'COMPANY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;

				if(!is_array($arRes))
				{
					return false;
				}

				$contactID = isset($arRes['CONTACT_ID']) ? intval($arRes['CONTACT_ID']) : 0;
				$companyID = isset($arRes['COMPANY_ID']) ? intval($arRes['COMPANY_ID']) : 0;
				if($contactID <= 0 && $companyID <= 0)
				{
					return false;
				}

				if($contactID > 0)
				{
					$info = array(
						$entityTypeIDKey => self::Contact,
						$entityIDKey => $contactID
					);

					if($additionalData !== null)
					{
						$info = array_merge($info, $additionalData);
					}

					if($enableMapping)
					{
						$owners[self::Contact.'_'.$contactID] = &$info;
					}
					else
					{
						$owners[] = &$info;
					}
					unset($info);
				}
				if($companyID > 0)
				{
					$info =  array(
						$entityTypeIDKey => self::Company,
						$entityIDKey => $companyID
					);

					if($additionalData !== null)
					{
						$info = array_merge($info, $additionalData);
					}

					if($enableMapping)
					{
						$owners[self::Company.'_'.$companyID] = &$info;
					}
					else
					{
						$owners[] = &$info;
					}
					unset($info);
				}
				return true;
			}
			//break;
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID), false, false, array('CONTACT_ID', 'COMPANY_ID'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;

				if(!is_array($arRes))
				{
					return false;
				}

				$contactID = isset($arRes['CONTACT_ID']) ? intval($arRes['CONTACT_ID']) : 0;
				$companyID = isset($arRes['COMPANY_ID']) ? intval($arRes['COMPANY_ID']) : 0;
				if($contactID <= 0 && $companyID <= 0)
				{
					return false;
				}

				if($contactID > 0)
				{
					$info = array(
						$entityTypeIDKey => self::Contact,
						$entityIDKey => $contactID
					);

					if($additionalData !== null)
					{
						$info = array_merge($info, $additionalData);
					}

					if($enableMapping)
					{
						$owners[self::Contact.'_'.$contactID] = &$info;
					}
					else
					{
						$owners[] = &$info;
					}
					unset($info);
				}
				if($companyID > 0)
				{
					$info =  array(
						$entityTypeIDKey => self::Company,
						$entityIDKey => $companyID
					);

					if($additionalData !== null)
					{
						$info = array_merge($info, $additionalData);
					}

					if($enableMapping)
					{
						$owners[self::Company.'_'.$companyID] = &$info;
					}
					else
					{
						$owners[] = &$info;
					}
					unset($info);
				}
				return true;
			}
			//break;
		}
		return false;
	}

	public static function TryGetInfo($typeID, $ID, &$info, $bCheckPermissions = false)
	{
		$typeID = intval($typeID);
		$ID = intval($ID);

		if($ID <= 0)
		{
			return array();
		}

		$result = null;
		switch($typeID)
		{
			case self::Lead:
			{
				$dbRes = CCrmLead::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('TITLE'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'IMAGE_ID' => 0
					);
					return true;
				}
				break;
			}
			case self::Contact:
			{
				$dbRes = CCrmContact::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'PHOTO'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => CCrmContact::PrepareFormattedName($arRes),
						'IMAGE_ID' => isset($arRes['PHOTO']) ? intval($arRes['PHOTO']) : 0
					);
					return true;
				}
				break;
			}
			case self::Company:
			{
				$dbRes = CCrmCompany::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('TITLE', 'LOGO'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'IMAGE_ID' => isset($arRes['LOGO']) ? intval($arRes['LOGO']) : 0
					);
					return true;
				}
				break;
			}
			case self::Deal:
			{
				$dbRes = CCrmDeal::GetListEx(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('TITLE'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'IMAGE_ID' => 0
					);
					return true;
				}
				break;
			}
			case self::Invoice:
			{
				$dbRes = CCrmInvoice::GetList(array(), array('ID' => $ID), false, false, array('ORDER_TOPIC'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => isset($arRes['ORDER_TOPIC']) ? $arRes['ORDER_TOPIC'] : '',
						'IMAGE_ID' => 0
					);
					return true;
				}
				break;
			}
			case self::Quote:
			{
				$dbRes = CCrmQuote::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => ($bCheckPermissions ? 'Y' : 'N')), false, false, array('TITLE'));
				$arRes = $dbRes ? $dbRes->Fetch() : null;
				if(is_array($arRes))
				{
					$info = array(
						'CAPTION' => isset($arRes['TITLE']) ? $arRes['TITLE'] : '',
						'IMAGE_ID' => 0
					);
					return true;
				}
				break;
			}
			case self::Order:
			{
				$caption = self::GetCaption($typeID, $ID);
				if (!empty($caption))
				{
					$info = array(
						'CAPTION' => $caption,
						'IMAGE_ID' => 0
					);
					return true;
				}
				break;
			}
			case self::StoreDocument:
			case self::ShipmentDocument:
			{
				$caption = self::GetCaption($typeID, $ID);
				if (!empty($caption))
				{
					$info = [
						'CAPTION' => $caption,
						'IMAGE_ID' => 0,
					];
					return true;
				}
				break;
			}
		}

		if (static::isUseDynamicTypeBasedApproach($typeID))
		{
			$caption = static::GetCaption($typeID, $ID);
			if (!empty($caption))
			{
				$info = [
					'CAPTION' => $caption,
					'IMAGE_ID' => 0,
				];
				return true;
			}
		}

		return false;
	}

	public static function ParseEntitySlug($slug)
	{
		$ary = explode('_', $slug);
		if(count($ary) !== 2)
		{
			return null;
		}

		return array(
			'ENTITY_TYPE_ID' => \CCrmOwnerTypeAbbr::ResolveTypeID($ary[0]),
			'ENTITY_ID' => (int)$ary[1]
		);
	}

	public static function GetJavascriptDescriptions()
	{
		return [
			self::LeadName => self::GetDescription(self::Lead),
			self::ContactName => self::GetDescription(self::Contact),
			self::CompanyName => self::GetDescription(self::Company),
			self::DealName => self::GetDescription(self::Deal),
			self::DealRecurringName => self::GetDescription(self::DealRecurring),
			self::InvoiceName => self::GetDescription(self::Invoice),
			self::QuoteName => self::GetDescription(self::Quote),
			self::OrderName => self::GetDescription(self::Order),
			self::SmartInvoice => self::GetDescription(self::SmartInvoice),
			self::SmartDocument => self::GetDescription(self::SmartDocument),
		];
	}

	public static function GetNotFoundMessages()
	{
		return [
			self::LeadName => GetMessage('CRM_OWNER_TYPE_LEAD_NOT_FOUND'),
			self::ContactName => GetMessage('CRM_OWNER_TYPE_CONTACT_NOT_FOUND'),
			self::CompanyName => GetMessage('CRM_OWNER_TYPE_COMPANY_NOT_FOUND'),
			self::DealName => GetMessage('CRM_OWNER_TYPE_DEAL_NOT_FOUND'),
			self::DealRecurringName => GetMessage('CRM_OWNER_TYPE_DEAL_NOT_FOUND'),
			self::InvoiceName => GetMessage('CRM_OWNER_TYPE_INVOICE_NOT_FOUND'),
			self::QuoteName => GetMessage('CRM_OWNER_TYPE_QUOTE_NOT_FOUND'),
			self::SmartInvoiceName => GetMessage('CRM_OWNER_TYPE_INVOICE_NOT_FOUND'),
			self::CommonDynamicName => GetMessage('CRM_TYPE_ITEM_NOT_FOUND'),
		];
	}

	public static function IsClient($ownerTypeID)
	{
		$ownerTypeID = (int)$ownerTypeID;
		return ($ownerTypeID === static::Lead || $ownerTypeID === static::Contact || $ownerTypeID === static::Company);
	}

	/**
	 * Return true if $entityTypeId fully supports new factory-based API.
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public static function isUseFactoryBasedApproach(int $entityTypeId): bool
	{
		return (
			self::isUseDynamicTypeBasedApproach($entityTypeId)
			|| $entityTypeId === self::Lead
			|| $entityTypeId === self::Deal
			|| $entityTypeId === self::Contact
			|| $entityTypeId === self::Company
			|| $entityTypeId === self::Quote
		);
	}

	/**
	 * Return true if $entityTypeId refers to the type which based on dynamic types API.
	 * Use this method if you want to use dynamic types API.
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public static function isUseDynamicTypeBasedApproach(int $entityTypeId): bool
	{
		return (
			self::isDynamicTypeBasedStaticEntity($entityTypeId)
			|| self::isPossibleDynamicTypeId($entityTypeId)
		);
	}

	/**
	 * Return true if $entityTypeId refers to the type which fully based on dynamic types API, but not a dynamic type itself.
	 * @internal
	 *
	 * @param int $entityTypeId
	 * @return bool
	 */
	public static function isDynamicTypeBasedStaticEntity(int $entityTypeId): bool
	{
		return in_array($entityTypeId, static::getDynamicTypeBasedStaticEntityTypeIds(), true);
	}

	/**
	 * Return types identifiers that fully based on dynamic types API, but not a dynamic types themselves.
	 *
	 * @return int[]
	 */
	public static function getDynamicTypeBasedStaticEntityTypeIds(): array
	{
		return [self::SmartInvoice, self::SmartDocument];
	}
}

class CCrmOwnerTypeAbbr
{
	public const Undefined = '';
	public const Lead = 'L';
	public const Deal = 'D';
	public const Contact = 'C';
	public const Company = 'CO';
	public const Invoice = 'I';
	public const Quote = 'Q';
	public const Requisite = 'RQ';
	public const DealCategory = 'DC';
	public const CustomActivityType = 'CAT';
	public const System = 'SYS';
	public const Order = 'O';
	public const OrderShipment = 'OS';
	public const OrderPayment = 'OP';
	public const SmartInvoice = 'SI';
	public const SmartDocument = 'DO';

	public const SuspendedLead = 'SL';
	public const SuspendedDeal = 'SD';
	public const SuspendedSmartInvoice = 'SSI';
	public const SuspendedSmartDocument = 'SSD';

	public const DynamicTypeAbbreviationPrefix = 'T';
	public const SuspendedDynamicTypeAbbreviationPrefix = 'S';

	public static function ResolveByTypeID($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		switch($typeID)
		{
			case CCrmOwnerType::Lead:
				return self::Lead;
			case CCrmOwnerType::SuspendedLead:
				return self::SuspendedLead;
			case CCrmOwnerType::Deal:
				return self::Deal;
			case CCrmOwnerType::SuspendedDeal:
				return self::SuspendedDeal;
			case CCrmOwnerType::Order:
				return self::Order;
			case CCrmOwnerType::OrderShipment:
				return self::OrderShipment;
			case CCrmOwnerType::OrderPayment:
				return self::OrderPayment;
			case CCrmOwnerType::Contact:
				return self::Contact;
			case CCrmOwnerType::Company:
				return self::Company;
			case CCrmOwnerType::Invoice:
				return self::Invoice;
			case CCrmOwnerType::Quote:
				return self::Quote;
			case CCrmOwnerType::Requisite:
				return self::Requisite;
			case CCrmOwnerType::DealCategory:
				return self::DealCategory;
			case CCrmOwnerType::CustomActivityType:
				return self::CustomActivityType;
			case CCrmOwnerType::SmartInvoice:
				return self::SmartInvoice;
			case CCrmOwnerType::SuspendedSmartInvoice:
				return self::SuspendedSmartInvoice;
			case CCrmOwnerType::SmartDocument:
				return self::SmartDocument;
			case CCrmOwnerType::SuspendedSmartDocument:
				return self::SuspendedSmartDocument;
			case CCrmOwnerType::System:
				return self::System;
			default:
				if (CCrmOwnerType::isPossibleDynamicTypeId($typeID))
				{
					return self::getDynamicTypeAbbreviation($typeID);
				}
				if (CCrmOwnerType::isPossibleSuspendedDynamicTypeId($typeID))
				{
					return self::getSuspendedTypeAbbreviation($typeID);
				}
		}

		return self::Undefined;
	}

	/**
	 * Returns an entityTypeId of a type that is associated with the provided abbreviation
	 *
	 * @param $abbr
	 *
	 * @return int
	 */
	public static function ResolveTypeID($abbr)
	{
		return CCrmOwnerType::ResolveID(self::ResolveName($abbr));
	}

	/**
	 * @param string $typeName
	 * @return string
	 */
	public static function ResolveByTypeName(string $typeName): string
	{
		$typeName = mb_strtoupper(trim($typeName));

		if ($typeName === '')
		{
			return self::Undefined;
		}

		switch($typeName)
		{
			case CCrmOwnerType::LeadName:
				return self::Lead;
			case CCrmOwnerType::SuspendedLeadName:
				return self::SuspendedLead;
			case CCrmOwnerType::DealName:
				return self::Deal;
			case CCrmOwnerType::SuspendedDealName:
				return self::SuspendedDeal;
			case CCrmOwnerType::ContactName:
				return self::Contact;
			case CCrmOwnerType::CompanyName:
				return self::Company;
			case CCrmOwnerType::InvoiceName:
				return self::Invoice;
			case CCrmOwnerType::QuoteName:
				return self::Quote;
			case CCrmOwnerType::OrderName:
				return self::Order;
			case CCrmOwnerType::OrderShipmentName:
				return self::OrderShipment;
			case CCrmOwnerType::OrderPaymentName:
				return self::OrderPayment;
			case CCrmOwnerType::RequisiteName:
				return self::Requisite;
			case CCrmOwnerType::DealCategoryName:
				return self::DealCategory;
			case CCrmOwnerType::CustomActivityTypeName:
				return self::CustomActivityType;
			case CCrmOwnerType::SmartInvoiceName:
				return self::SmartInvoice;
			case CCrmOwnerType::SuspendedSmartInvoiceName:
				return self::SuspendedSmartInvoice;
			case CCrmOwnerType::SmartDocumentName:
				return self::SmartDocument;
			case CCrmOwnerType::SuspendedSmartDocumentName:
				return self::SuspendedSmartDocument;
			case CCrmOwnerType::SystemName:
				return self::System;
			default:
				$typeId = CCrmOwnerType::ResolveID($typeName);
				if (CCrmOwnerType::isPossibleDynamicTypeId($typeId))
				{
					return self::getDynamicTypeAbbreviation($typeId);
				}
				if (CCrmOwnerType::isPossibleSuspendedDynamicTypeId($typeId))
				{
					return self::getSuspendedTypeAbbreviation($typeId);
				}

				return self::Undefined;
		}
	}

	public static function ResolveName($abbr)
	{
		if(!is_string($abbr))
		{
			$abbr = (string)$abbr;
		}

		$abbr = mb_strtoupper(trim($abbr));
		if($abbr === '')
		{
			return '';
		}

		switch($abbr)
		{
			case self::Lead:
				return CCrmOwnerType::LeadName;
			case self::SuspendedLead:
				return CCrmOwnerType::SuspendedLead;
			case self::Deal:
				return CCrmOwnerType::DealName;
			case self::SuspendedDeal:
				return CCrmOwnerType::SuspendedDealName;
			case self::Contact:
				return CCrmOwnerType::ContactName;
			case self::Company:
				return CCrmOwnerType::CompanyName;
			case self::Invoice:
				return CCrmOwnerType::InvoiceName;
			case self::Quote:
				return CCrmOwnerType::QuoteName;
			case self::Order:
				return CCrmOwnerType::OrderName;
			case self::OrderShipment:
				return CCrmOwnerType::OrderShipmentName;
			case self::OrderPayment:
				return CCrmOwnerType::OrderPaymentName;
			case self::Requisite:
				return CCrmOwnerType::RequisiteName;
			case self::DealCategory:
				return CCrmOwnerType::DealCategoryName;
			case self::CustomActivityType:
				return CCrmOwnerType::CustomActivityTypeName;
			case self::SmartInvoice:
				return CCrmOwnerType::SmartInvoiceName;
			case self::SuspendedSmartInvoice:
				return CCrmOwnerType::SuspendedSmartInvoiceName;
			case self::SmartDocument:
				return CCrmOwnerType::SmartDocumentName;
			case self::SuspendedSmartDocument:
				return CCrmOwnerType::SuspendedSmartDocumentName;
			case self::System:
				return CCrmOwnerType::SystemName;
			default:
				$typeId = self::extractTypeIdFromDynamicTypeAbbreviation($abbr);
				if (!is_null($typeId))
				{
					return CCrmOwnerType::ResolveName($typeId);
				}

				$suspendedTypeId = self::extractTypeIdFromSuspendedDynamicTypeAbbreviation($abbr);
				if (!is_null($suspendedTypeId))
				{
					return CCrmOwnerType::ResolveName($suspendedTypeId);
				}
		}
		return '';
	}

	private static function getDynamicTypeAbbreviation(int $typeId): string
	{
		return (self::DynamicTypeAbbreviationPrefix . self::normalizeTypeIdForAbbreviation($typeId));
	}

	private static function getSuspendedTypeAbbreviation(int $typeId): string
	{
		return (self::SuspendedDynamicTypeAbbreviationPrefix . self::normalizeTypeIdForAbbreviation($typeId));
	}

	/**
	 * Returns true if the provided abbreviation describes a dynamic entity type
	 *
	 * @param string $abbr
	 *
	 * @return bool
	 */
	public static function isDynamicTypeAbbreviation(string $abbr): bool
	{
		$isMatchesPrefix = (mb_strpos($abbr, self::DynamicTypeAbbreviationPrefix) !== false);

		$typeId = self::extractTypeIdFromDynamicTypeAbbreviation($abbr);
		$isTypeIdValid = !is_null($typeId);

		return ($isMatchesPrefix && $isTypeIdValid);
	}

	/**
	 * Returns true if the provided abbreviation describes a suspended dynamic entity type
	 *
	 * @param string $abbr
	 *
	 * @return bool
	 */
	public static function isSuspendedDynamicTypeAbbreviation(string $abbr): bool
	{
		$isMatchesPrefix = (mb_strpos($abbr, self::SuspendedDynamicTypeAbbreviationPrefix) !== false);

		$typeId = self::extractTypeIdFromSuspendedDynamicTypeAbbreviation($abbr);
		$isTypeIdValid = !is_null($typeId);

		return ($isMatchesPrefix && $isTypeIdValid);
	}

	private static function extractTypeIdFromDynamicTypeAbbreviation(string $abbr): ?int
	{
		$typeId = mb_substr($abbr, mb_strlen(self::DynamicTypeAbbreviationPrefix));
		$typeIdNormalized = self::normalizeTypeIdFromAbbreviation($typeId);
		if (CCrmOwnerType::isPossibleDynamicTypeId($typeIdNormalized))
		{
			return $typeIdNormalized;
		}

		return null;
	}

	private static function extractTypeIdFromSuspendedDynamicTypeAbbreviation(string $abbr): ?int
	{
		$typeId = mb_substr($abbr, mb_strlen(self::SuspendedDynamicTypeAbbreviationPrefix));
		$typeIdNormalized = self::normalizeTypeIdFromAbbreviation($typeId);
		if (CCrmOwnerType::isPossibleSuspendedDynamicTypeId($typeIdNormalized))
		{
			return $typeIdNormalized;
		}

		return null;
	}

	private static function normalizeTypeIdForAbbreviation(int $typeId): string
	{
		// In dynamic type abbreviation typeId is being converted to Hex
		// in order to fit the resulting abbreviation into 3 symbols. It's a limitation of several CRM tables
		return dechex($typeId);
	}

	private static function normalizeTypeIdFromAbbreviation(string $typeIdFromAbbreviation): int
	{
		// In dynamic type abbreviation typeId is being converted to Hex
		// in order to fit the resulting abbreviation into 3 symbols. It's a limitation of several CRM tables
		return (int)hexdec($typeIdFromAbbreviation);
	}
}
