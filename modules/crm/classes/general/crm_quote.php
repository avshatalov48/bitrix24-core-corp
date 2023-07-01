<?php
IncludeModuleLangFile(__FILE__);

use Bitrix\Crm;
use Bitrix\Crm\Binding\QuoteContactTable;
use Bitrix\Crm\CompanyAddress;
use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\Entity\Traits\EntityFieldsNormalizer;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Integration\StorageManager;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UtmTable;

class CAllCrmQuote
{
	use EntityFieldsNormalizer;

	public static $sUFEntityID = 'CRM_QUOTE';

	const USER_FIELD_ENTITY_ID = 'CRM_QUOTE';
	const SUSPENDED_USER_FIELD_ENTITY_ID = 'CRM_QUOTE_SPD';
	const TOTAL_COUNT_CACHE_ID = 'crm_quote_total_count';
	const CACHE_TTL = 3600;

	protected const TABLE_NAME = 'b_crm_quote';

	protected static $TYPE_NAME = 'QUOTE';
	private static $QUOTE_STATUSES = null;
	private static $STORAGE_TYPE_ID = CCrmQuoteStorageType::Undefined;
	private static $clientFields = array(
		'CLIENT_TITLE', 'CLIENT_ADDR', 'CLIENT_TP_ID', 'CLIENT_TPA_ID', 'CLIENT_CONTACT', 'CLIENT_EMAIL', 'CLIENT_PHONE'
	);

	public $LAST_ERROR = '';
	public $cPerms = null;
	protected $bCheckPermission = true;
	protected $lastErrors;

	private static ?Crm\Entity\Compatibility\Adapter $lastActivityAdapter = null;
	private static ?Crm\Entity\Compatibility\Adapter $contentTypeIdAdapter = null;

	/** @var \Bitrix\Crm\Entity\Compatibility\Adapter */
	private $compatibiltyAdapter;

	const TABLE_ALIAS = 'Q';
	const OWNER_TYPE = self::TABLE_ALIAS;
	private static $FIELD_INFOS = null;

	/**
	 * Returns true if this class should invoke Service\Operation instead old API.
	 * For a start it will return false by default. Please use this period to test your customization on compatibility with new API.
	 * Later it will return true by default.
	 * In several months this class will be declared as deprecated and old code will be deleted completely.
	 *
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function isUseOperation(): bool
	{
		return static::isFactoryEnabled();
	}

	private static function isFactoryEnabled(): bool
	{
		return Crm\Settings\QuoteSettings::getCurrent()->isFactoryEnabled();
	}

	private function getCompatibilityAdapter(): Crm\Entity\Compatibility\Adapter
	{
		if (!$this->compatibiltyAdapter)
		{
			$this->compatibiltyAdapter = static::createCompatibilityAdapter();

			if ($this->compatibiltyAdapter instanceof Crm\Entity\Compatibility\Adapter\Operation)
			{
				$this->compatibiltyAdapter
					///bind newly created adapter to this instance
					->setCheckPermissions((bool)$this->bCheckPermission)
					->setErrorMessageContainer($this->LAST_ERROR)
					->setErrorCollectionContainer($this->lastErrors)
				;
			}
		}

		return $this->compatibiltyAdapter;
	}

	private static function createCompatibilityAdapter(): Crm\Entity\Compatibility\Adapter
	{
		$factory = Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Quote);
		if (!$factory)
		{
			throw new Error('No factory for quote');
		}

		return
			(new Crm\Entity\Compatibility\Adapter\Operation($factory))
				->setAlwaysExposedFields([
					'MODIFY_BY_ID',
					'PERSON_TYPE_ID',
					'STORAGE_TYPE_ID',
					'STORAGE_ELEMENT_IDS',
					'ID',
				])
				->setExposedOnlyAfterAddFields([
					'CREATED_BY_ID',
					'ASSIGNED_BY_ID',
					'STATUS_ID',
					'CLOSED',
					'BEGINDATE',
				])
				->setExposedOnlyAfterUpdateFields([
					'QUOTE_NUMBER',
				])
		;
	}

	private static function getLastActivityAdapter(): Crm\Entity\Compatibility\Adapter
	{
		if (!self::$lastActivityAdapter)
		{
			$factory = Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::Quote);
			self::$lastActivityAdapter = new Crm\Entity\Compatibility\Adapter\LastActivity($factory);
			self::$lastActivityAdapter->setTableAlias(self::TABLE_ALIAS);
		}

		return self::$lastActivityAdapter;
	}

	private static function getContentTypeIdAdapter(): Crm\Entity\Compatibility\Adapter\ContentTypeId
	{
		if (!self::$contentTypeIdAdapter)
		{
			self::$contentTypeIdAdapter = new Crm\Entity\Compatibility\Adapter\ContentTypeId(\CCrmOwnerType::Quote);
		}

		return self::$contentTypeIdAdapter;
	}

	public function __construct($bCheckPermission = true)
	{
		$this->bCheckPermission = $bCheckPermission;
		$this->cPerms = CCrmPerms::GetCurrentUserPermissions();
	}

	public function Add(&$arFields, $bUpdateSearch = true, $options = array())
	{
		$this->lastErrors = null;

		if (!is_array($arFields))
		{
			$arFields = (array)$arFields;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		if($this->isUseOperation())
		{
			return $this->getCompatibilityAdapter()->performAdd($arFields, $options);
		}

		global $DB;

		$this->LAST_ERROR = '';
		$iUserId = isset($options['CURRENT_USER'])
			? (int)$options['CURRENT_USER'] : CCrmSecurityHelper::GetCurrentUserID();

		if (isset($arFields['ID']))
			unset($arFields['ID']);

		if (isset($arFields['DATE_CREATE']))
			unset($arFields['DATE_CREATE']);
		$arFields['~DATE_CREATE'] = $DB->CurrentTimeFunction();
		$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();

		if (!isset($arFields['CREATED_BY_ID']) || (int)$arFields['CREATED_BY_ID'] <= 0)
			$arFields['CREATED_BY_ID'] = $iUserId;
		if (!isset($arFields['MODIFY_BY_ID']) || (int)$arFields['MODIFY_BY_ID'] <= 0)
			$arFields['MODIFY_BY_ID'] = $iUserId;

		if(isset($arFields['ASSIGNED_BY_ID']) && is_array($arFields['ASSIGNED_BY_ID']))
		{
			$arFields['ASSIGNED_BY_ID'] = count($arFields['ASSIGNED_BY_ID']) > 0 ? intval($arFields['ASSIGNED_BY_ID'][0]) : $iUserId;
		}

		if (!isset($arFields['ASSIGNED_BY_ID']) || (int)$arFields['ASSIGNED_BY_ID'] <= 0)
			$arFields['ASSIGNED_BY_ID'] = $iUserId;

		// person type
		if (!isset($arFields['PERSON_TYPE_ID']) || intval($arFields['PERSON_TYPE_ID']) <= 0)
		{
			$arFields['PERSON_TYPE_ID'] = 0;
			$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
			if (isset($arPersonTypes['CONTACT']) && (!isset($arFields['COMPANY_ID']) || intval($arFields['COMPANY_ID']) <= 0))
				$arFields['PERSON_TYPE_ID'] = intval($arPersonTypes['CONTACT']);
			else if (isset($arPersonTypes['COMPANY']) && isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
				$arFields['PERSON_TYPE_ID'] = intval($arPersonTypes['COMPANY']);
		}

		// storage type
		$storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
			? intval($arFields['STORAGE_TYPE_ID']) : CCrmQuoteStorageType::Undefined;
		if($storageTypeID === CCrmQuoteStorageType::Undefined
			|| !CCrmQuoteStorageType::IsDefined($storageTypeID))
		{
			$storageTypeID = self::GetDefaultStorageTypeID();
		}
		$arFields['STORAGE_TYPE_ID'] = $storageTypeID;


		// storage elements
		$storageElementIDs = (isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS']))
			? $arFields['STORAGE_ELEMENT_IDS'] : null;
		$arFields['STORAGE_ELEMENT_IDS'] = null;
		if ($storageElementIDs !== null)
		{
			$storageElementIDs = self::NormalizeStorageElementIDs($storageElementIDs);
			$arFields['STORAGE_ELEMENT_IDS'] = serialize($storageElementIDs);
		}

		if (!$this->CheckFields($arFields, false, $options))
		{
			$result = false;
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		}
		else
		{
			if (!isset($arFields['STATUS_ID']))
				$arFields['STATUS_ID'] = 'DRAFT';
			$arAttr = array();
			if (!empty($arFields['STATUS_ID']))
				$arAttr['STATUS_ID'] = $arFields['STATUS_ID'];
			if (!empty($arFields['OPENED']))
				$arAttr['OPENED'] = $arFields['OPENED'];

			$sPermission = 'ADD';
			if (isset($arFields['PERMISSION']))
			{
				if ($arFields['PERMISSION'] == 'IMPORT')
					$sPermission = 'IMPORT';
				unset($arFields['PERMISSION']);
			}

			if($this->bCheckPermission)
			{
				$arEntityAttr = self::BuildEntityAttr($iUserId, $arAttr);
				$userPerms =  $iUserId == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($iUserId);
				$sEntityPerm = $userPerms->GetPermType(self::$TYPE_NAME, $sPermission, $arEntityAttr);
				if ($sEntityPerm == BX_CRM_PERM_NONE)
				{
					$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
					$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					return false;
				}

				$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
				if ($sEntityPerm == BX_CRM_PERM_SELF && $assignedByID != $iUserId)
				{
					$arFields['ASSIGNED_BY_ID'] = $iUserId;
				}
				if ($sEntityPerm == BX_CRM_PERM_OPEN && $iUserId == $assignedByID)
				{
					$arFields['OPENED'] = 'Y';
				}
			}

			$assignedByID = intval($arFields['ASSIGNED_BY_ID']);
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			$userPerms =  $assignedByID == CCrmPerms::GetCurrentUserID() ? $this->cPerms : CCrmPerms::GetUserPermissions($assignedByID);
			$sEntityPerm = $userPerms->GetPermType(self::$TYPE_NAME, $sPermission, $arEntityAttr);
			$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);

			$arFields = array_merge($arFields, \CCrmAccountingHelper::calculateAccountingData($arFields, [], true));

			$arFields['CLOSED'] = self::GetStatusSemantics($arFields['STATUS_ID']) === 'process' ? 'N' : 'Y';

			$now = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', SITE_ID);
			if (!isset($arFields['BEGINDATE'][0]))
			{
				$arFields['BEGINDATE'] = $now;
			}

			if($arFields['CLOSED'] === 'Y'
				&& (!isset($arFields['CLOSEDATE']) || $arFields['CLOSEDATE'] === ''))
			{
				$arFields['CLOSEDATE'] = $now;
			}

			//region Preparation of contacts
			$contactBindings = isset($arFields['CONTACT_BINDINGS']) && is_array($arFields['CONTACT_BINDINGS'])
				? $arFields['CONTACT_BINDINGS'] : null;
			$contactIDs = isset($arFields['CONTACT_IDS']) && is_array($arFields['CONTACT_IDS'])
				? $arFields['CONTACT_IDS'] : null;
			unset($arFields['CONTACT_IDS']);
			//For backward compatibility only
			$contactID = isset($arFields['CONTACT_ID']) ? max((int)$arFields['CONTACT_ID'], 0) : null;
			if($contactID !== null && $contactIDs === null && $contactBindings === null)
			{
				$contactIDs = array();
				if($contactID > 0)
				{
					$contactIDs[] = $contactID;
				}
			}

			if(is_array($contactBindings))
			{
				$contactIDs = \Bitrix\Crm\Binding\EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Contact,
					$contactBindings
				);
			}
			elseif(is_array($contactIDs))
			{
				$contactBindings = \Bitrix\Crm\Binding\EntityBinding::prepareEntityBindings(
					\CCrmOwnerType::Contact,
					$contactIDs
				);

				\Bitrix\Crm\Binding\EntityBinding::markFirstAsPrimary($contactBindings);
			}
			//endregion

			self::getLastActivityAdapter()->performAdd($arFields, $options);

			//region Rise BeforeAdd event
			foreach (GetModuleEvents('crm', 'OnBeforeCrmQuoteAdd', true) as $arEvent)
			{
				if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				{
					if(isset($arFields['RESULT_MESSAGE']))
					{
						$this->LAST_ERROR = $arFields['RESULT_MESSAGE'];
					}
					else
					{
						$this->LAST_ERROR = GetMessage('CRM_QUOTE_CREATION_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}
			//endregion

			$clobFieldNames = array('COMMENTS', 'CONTENT', 'STORAGE_ELEMENT_IDS');
			$clobFields = array();
			foreach ($clobFieldNames as $fieldName)
			{
				if (array_key_exists($fieldName, $arFields))
					$clobFields[] = $fieldName;
			}

			$this->normalizeEntityFields($arFields);
			$ID = (int) $DB->Add(self::TABLE_NAME, $arFields, $clobFields, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);

			if(defined('BX_COMP_MANAGED_CACHE'))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_quote');
			}

			if (!self::SetQuoteNumber($ID))
			{
				$this->LAST_ERROR = GetMessage('CRM_ERROR_QUOTE_NUMBER_IS_NOT_SET');
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => true));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

			$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
				->setEntityAttributes($arEntityAttr)
			;
			Crm\Security\Manager::getEntityController(CCrmOwnerType::Quote)
				->register(self::$TYPE_NAME, $ID, $securityRegisterOptions)
			;

			if(is_array($storageElementIDs))
			{
				CCrmQuote::DoSaveElementIDs($ID, $storageTypeID, $storageElementIDs);
			}
			unset($storageTypeID, $storageElementIDs);

			// tracking of entity
			Tracking\Entity::onAfterAdd(CCrmOwnerType::Quote, $ID, $arFields);

			if($bUpdateSearch)
			{
				$arFilterTmp = Array('ID' => $ID);
				if (!$this->bCheckPermission)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";
				CCrmSearch::UpdateSearch($arFilterTmp, 'QUOTE', true);
			}

			$result = $arFields['ID'] = $ID;

			if (isset($GLOBALS["USER"]) && isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
			{
				CUserOptions::SetOption('crm', 'crm_company_search', array('last_selected' => $arFields['COMPANY_ID']));
			}

			//region Save contacts
			if(!empty($contactBindings))
			{
				QuoteContactTable::bindContacts($ID, $contactBindings);
				if (isset($GLOBALS['USER']))
				{
					CUserOptions::SetOption(
						'crm',
						'crm_contact_search',
						array('last_selected' => $contactIDs[count($contactIDs) - 1])
					);
				}
			}
			//endregion

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Quote)->build($ID);
			//endregion

			self::getContentTypeIdAdapter()->performAdd($arFields, $options);

			//region Rise AfterAdd event
			foreach (GetModuleEvents('crm', 'OnAfterCrmQuoteAdd', true) as $arEvent)
				ExecuteModuleEventEx($arEvent, array(&$arFields));
			//endregion
		}

		return $result;
	}

	public function CheckFields(&$arFields, $ID = false, $options = array())
	{
		global $APPLICATION, $USER_FIELD_MANAGER, $DB;
		$this->LAST_ERROR = '';

		/*if (($ID == false || isset($arFields['TITLE'])) && empty($arFields['TITLE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_TITLE')))."<br />\n";*/
		if (isset($arFields['TITLE']) && mb_strlen($arFields['TITLE']) > 255)
		{
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_TITLE')))."<br />\n";
		}

		if ($ID !== false && isset($arFields['QUOTE_NUMBER']))
		{
			/*if (strlen($arFields['QUOTE_NUMBER']) <= 0)
			{
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_IS_MISSING', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_QUOTE_NUMBER')))."<br />\n";
			}
			else*/ if (mb_strlen($arFields['QUOTE_NUMBER']) > 100)
			{
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_QUOTE_NUMBER')))."<br />\n";
			}
			else
			{
				$dbres = $DB->Query("SELECT ID, QUOTE_NUMBER FROM b_crm_quote WHERE QUOTE_NUMBER = '".$DB->ForSql($arFields["QUOTE_NUMBER"])."'", true);
				if ($arRes = $dbres->GetNext())
				{
					if (is_array($arRes) && $arRes["ID"] != $ID)
					{
						$this->LAST_ERROR .= GetMessage('CRM_ERROR_QUOTE_NUMBER_EXISTS')."<br />\n";
					}
				}
				unset($arRes, $dbres);
			}
		}

		if (!empty($arFields['BEGINDATE']) && !CheckDateTime($arFields['BEGINDATE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_BEGINDATE')))."<br />\n";

		if (!empty($arFields['CLOSEDATE']) && !CheckDateTime($arFields['CLOSEDATE']))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_CLOSEDATE')))."<br />\n";

		if(is_string($arFields['OPPORTUNITY']) && $arFields['OPPORTUNITY'] !== '')
		{
			$arFields['OPPORTUNITY'] = str_replace(array(',', ' '), array('.', ''), $arFields['OPPORTUNITY']);
			//HACK: MSSQL returns '.00' for zero value
			if(mb_strpos($arFields['OPPORTUNITY'], '.') === 0)
			{
				$arFields['OPPORTUNITY'] = '0'.$arFields['OPPORTUNITY'];
			}

			if (!preg_match('/^\d{1,}(\.\d{1,})?$/', $arFields['OPPORTUNITY']))
			{
				$this->LAST_ERROR .= GetMessage('CRM_QUOTE_FIELD_OPPORTUNITY_INVALID')."<br />\n";
			}
		}

		// storage type id
		if(!isset($arFields['STORAGE_TYPE_ID'])
			|| $arFields['STORAGE_TYPE_ID'] === CCrmQuoteStorageType::Undefined
			|| !CCrmQuoteStorageType::IsDefined($arFields['STORAGE_TYPE_ID']))
		{
			$arFields['STORAGE_TYPE_ID'] = self::GetDefaultStorageTypeID();
		}

		foreach (self::$clientFields as $fieldName)
		{
			if (isset($arFields[$fieldName]) && mb_strlen($arFields[$fieldName]) > 255)
				$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_'.$fieldName.($fieldName === 'MYCOMPANY_ID' ? '1' : ''))))."<br />\n";
		}
		unset($fieldName);

		// check person type
		$personTypeId = 0;
		if (isset($arFields['PERSON_TYPE_ID']))
			$personTypeId = intval($arFields['PERSON_TYPE_ID']);
		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		$arPersonTypeEnum = array();
		if (isset($arPersonTypes['CONTACT']))
			$arPersonTypeEnum[] = intval($arPersonTypes['CONTACT']);
		if (isset($arPersonTypes['COMPANY']))
			$arPersonTypeEnum[] = intval($arPersonTypes['COMPANY']);
		if ($personTypeId <= 0 || !in_array($personTypeId, $arPersonTypeEnum, true))
			$this->LAST_ERROR .= GetMessage('CRM_ERROR_FIELD_INCORRECT', array('%FIELD_NAME%' => GetMessage('CRM_QUOTE_FIELD_PERSON_TYPE_ID')))."<br />\n";
		unset($personTypeId, $arPersonTypes, $arPersonTypeEnum);

		if(!is_array($options))
		{
			$options = array();
		}

		$enableUserFieldCheck = !(isset($options['DISABLE_USER_FIELD_CHECK'])
			&& $options['DISABLE_USER_FIELD_CHECK'] === true);

		if ($enableUserFieldCheck)
		{
			// We have to prepare field data before check (issue #22966)
			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $USER_FIELD_MANAGER, array('IS_NEW' => ($ID == false)));

			$enableRequiredUserFieldCheck = !(isset($options['DISABLE_REQUIRED_USER_FIELD_CHECK'])
				&& $options['DISABLE_REQUIRED_USER_FIELD_CHECK'] === true);

			if(!$USER_FIELD_MANAGER->CheckFields(self::$sUFEntityID, $ID, $arFields, false, $enableRequiredUserFieldCheck))
			{
				$e = $APPLICATION->GetException();
				$this->LAST_ERROR .= $e->GetString();
			}
		}

		if ($this->LAST_ERROR <> '')
			return false;

		return true;
	}

	static public function BuildEntityAttr($userID, $arAttr = array())
	{
		$userID = (int)$userID;
		$arResult = array("U{$userID}");
		if(isset($arAttr['OPENED']) && $arAttr['OPENED'] == 'Y')
		{
			$arResult[] = 'O';
		}

		$statusID = isset($arAttr['STATUS_ID']) ? $arAttr['STATUS_ID'] : '';
		if($statusID !== '')
		{
			$arResult[] = "STATUS_ID{$statusID}";
		}

		$arUserAttr = Bitrix\Crm\Service\Container::getInstance()
			->getUserPermissions($userID)
			->getAttributesProvider()
			->getEntityAttributes()
		;

		return array_merge($arResult, $arUserAttr['INTRANET']);
	}
	static public function RebuildEntityAccessAttrs($IDs)
	{
		if(!is_array($IDs))
		{
			$IDs = array($IDs);
		}

		$dbResult = self::GetList(
			array(),
			array('@ID' => $IDs, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'ASSIGNED_BY_ID', 'OPENED', 'STATUS_ID')
		);

		if(!is_object($dbResult))
		{
			return;
		}

		while($fields = $dbResult->Fetch())
		{
			$ID = intval($fields['ID']);
			$assignedByID = isset($fields['ASSIGNED_BY_ID']) ? intval($fields['ASSIGNED_BY_ID']) : 0;
			if($assignedByID <= 0)
			{
				continue;
			}

			$attrs = [];
			if(isset($fields['OPENED']))
			{
				$attrs['OPENED'] = $fields['OPENED'];
			}

			if(isset($fields['STATUS_ID']))
			{
				$attrs['STATUS_ID'] = $fields['STATUS_ID'];
			}

			$entityAttrs = self::BuildEntityAttr($assignedByID, $attrs);
			$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
				->setEntityAttributes($arEntityAttr)
				->setEntityFields($fields)
			;
			Crm\Security\Manager::getEntityController(CCrmOwnerType::Quote)
				->register(self::$TYPE_NAME, $ID, $securityRegisterOptions)
			;
		}
	}
	private function PrepareEntityAttrs(&$arEntityAttr, $entityPermType)
	{
		// Ensure that entity accessable for user restricted by BX_CRM_PERM_OPEN
		if($entityPermType === BX_CRM_PERM_OPEN && !in_array('O', $arEntityAttr, true))
		{
			$arEntityAttr[] = 'O';
		}
	}

	public function Update($ID, &$arFields, $bCompare = true, $bUpdateSearch = true, $options = array())
	{
		$ID = (int) $ID;
		if (!is_array($arFields))
		{
			$arFields = (array)$arFields;
		}
		if(!is_array($options))
		{
			$options = array();
		}

		$this->lastErrors = null;
		if($this->isUseOperation())
		{
			return $this->getCompatibilityAdapter()->performUpdate($ID, $arFields, $options);
		}

		global $DB;

		$this->LAST_ERROR = '';

		$arFilterTmp = array('ID' => $ID);
		if (!$this->bCheckPermission)
			$arFilterTmp['CHECK_PERMISSIONS'] = 'N';

		$obRes = self::GetList(array(), $arFilterTmp);
		if (!($arRow = $obRes->Fetch()))
			return false;

		$iUserId = CCrmSecurityHelper::GetCurrentUserID();

		if (isset($arFields['DATE_CREATE']))
			unset($arFields['DATE_CREATE']);

		if (isset($arFields['DATE_MODIFY']))
			unset($arFields['DATE_MODIFY']);
		$arFields['~DATE_MODIFY'] = $DB->CurrentTimeFunction();

		$arFields['MODIFY_BY_ID'] = $iUserId;

		if (isset($arFields['ASSIGNED_BY_ID']) && $arFields['ASSIGNED_BY_ID'] <= 0)
			unset($arFields['ASSIGNED_BY_ID']);

		// number
		if (!isset($arFields['QUOTE_NUMBER']) || empty($arFields['QUOTE_NUMBER']))
		{
			$arFields['QUOTE_NUMBER'] = isset($arRow['QUOTE_NUMBER']) ? $arRow['QUOTE_NUMBER'] : '';
			if (empty($arFields['QUOTE_NUMBER']))
				$arFields['QUOTE_NUMBER'] = strval($ID);
		}

		// person type
		if (!isset($arFields['PERSON_TYPE_ID']) || intval($arFields['PERSON_TYPE_ID']) <= 0)
		{
			$companyId = isset($arFields['COMPANY_ID']) ? intval($arFields['COMPANY_ID']) : (isset($arRow['COMPANY_ID']) ? intval($arRow['COMPANY_ID']) : 0);
			$arFields['PERSON_TYPE_ID'] = intval($arRow['PERSON_TYPE_ID']);
			$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
			if (isset($arPersonTypes['CONTACT']) && isset($arPersonTypes['COMPANY']))
			{
				if ($companyId <= 0)
					$arFields['PERSON_TYPE_ID'] = intval($arPersonTypes['CONTACT']);
				else
					$arFields['PERSON_TYPE_ID'] = intval($arPersonTypes['COMPANY']);
			}
			unset($companyId, $arPersonTypes);
		}

		// storage type id
		$storageTypeID = isset($arFields['STORAGE_TYPE_ID'])
			? intval($arFields['STORAGE_TYPE_ID']) : CCrmQuoteStorageType::Undefined;
		if($storageTypeID === CCrmQuoteStorageType::Undefined
			|| !CCrmQuoteStorageType::IsDefined($storageTypeID))
		{
			$storageTypeID = isset($arRow['STORAGE_TYPE_ID'])
				? $arRow['STORAGE_TYPE_ID'] : CCrmQuoteStorageType::Undefined;
			if($storageTypeID === CCrmQuoteStorageType::Undefined
				|| !CCrmQuoteStorageType::IsDefined($storageTypeID))
			{
				$storageTypeID = CCrmQuote::GetDefaultStorageTypeID();
			}
		}
		$arFields['STORAGE_TYPE_ID'] = $storageTypeID;

		// storage elements
		$storageElementIDs = (isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS']))
			? $arFields['STORAGE_ELEMENT_IDS'] : null;
		$arFields['STORAGE_ELEMENT_IDS'] = null;
		if ($storageElementIDs !== null)
		{
			$storageElementIDs = self::NormalizeStorageElementIDs($storageElementIDs);
			$arFields['STORAGE_ELEMENT_IDS'] = serialize($storageElementIDs);
		}

		$assignedByID = (int)(isset($arFields['ASSIGNED_BY_ID']) ? $arFields['ASSIGNED_BY_ID'] : $arRow['ASSIGNED_BY_ID']);

		$bResult = false;
		if (!$this->CheckFields($arFields, $ID, $options))
			$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
		else
		{
			if($this->bCheckPermission && !CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $this->cPerms))
			{
				$this->LAST_ERROR = GetMessage('CRM_PERMISSION_DENIED');
				$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
				return false;
			}

			$arAttr = array();
			$arAttr['STATUS_ID'] = !empty($arFields['STATUS_ID']) ? $arFields['STATUS_ID'] : $arRow['STATUS_ID'];
			$arAttr['OPENED'] = !empty($arFields['OPENED']) ? $arFields['OPENED'] : $arRow['OPENED'];
			$arEntityAttr = self::BuildEntityAttr($assignedByID, $arAttr);
			if($this->bCheckPermission)
			{
				$sEntityPerm = $this->cPerms->GetPermType(self::$TYPE_NAME, 'WRITE', $arEntityAttr);
				//HACK: Ensure that entity accessible for user restricted by BX_CRM_PERM_OPEN
				$this->PrepareEntityAttrs($arEntityAttr, $sEntityPerm);
				//HACK: Prevent 'OPENED' field change by user restricted by BX_CRM_PERM_OPEN permission
				if($sEntityPerm === BX_CRM_PERM_OPEN && isset($arFields['OPENED']) && $arFields['OPENED'] !== 'Y' && $assignedByID !== $iUserId)
				{
					$arFields['OPENED'] = 'Y';
				}
			}

			if (isset($arFields['ASSIGNED_BY_ID']) && $arRow['ASSIGNED_BY_ID'] != $arFields['ASSIGNED_BY_ID'])
				CCrmEvent::SetAssignedByElement($arFields['ASSIGNED_BY_ID'], 'QUOTE', $ID);

			//region Preparation of contacts
			$originalContactBindings = QuoteContactTable::getQuoteBindings($ID);
			$originalContactIDs = \Bitrix\Crm\Binding\EntityBinding::prepareEntityIDs(CCrmOwnerType::Contact, $originalContactBindings);
			$contactBindings = isset($arFields['CONTACT_BINDINGS']) && is_array($arFields['CONTACT_BINDINGS'])
				? $arFields['CONTACT_BINDINGS'] : null;
			$contactIDs = isset($arFields['CONTACT_IDS']) && is_array($arFields['CONTACT_IDS'])
				? $arFields['CONTACT_IDS'] : null;
			unset($arFields['CONTACT_IDS']);

			//region Backward compatibility
			$contactID = isset($arFields['CONTACT_ID']) ? max((int)$arFields['CONTACT_ID'], 0) : null;
			if($contactBindings === null &&
				$contactIDs === null &&
				$contactID !== null &&
				!in_array($contactID, $originalContactIDs, true))
			{
				//Compatibility mode. Trying to simulate single binding mode If contact is not found in bindings.
				$contactIDs = array();
				if($contactID > 0)
				{
					$contactIDs[] = $contactID;
				}
			}
			unset($arFields['CONTACT_ID']);
			//endregion

			$addedContactIDs = null;
			$removedContactIDs = null;

			$addedContactBindings = null;
			$removedContactBindings = null;

			if(is_array($contactIDs) && !is_array($contactBindings))
			{
				$contactBindings = \Bitrix\Crm\Binding\EntityBinding::prepareEntityBindings(
					\CCrmOwnerType::Contact,
					$contactIDs
				);

				\Bitrix\Crm\Binding\EntityBinding::markFirstAsPrimary($contactBindings);
			}
			/* Please uncomment if required
			elseif(is_array($contactBindings) && !is_array($contactIDs))
			{
				$contactIDs = \Bitrix\Crm\Binding\EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Contact,
					$contactBindings
				);
			}
			*/

			if(is_array($contactBindings))
			{
				$removedContactBindings = array();
				$addedContactBindings = array();

				\Bitrix\Crm\Binding\EntityBinding::prepareBindingChanges(
					CCrmOwnerType::Contact,
					QuoteContactTable::getQuoteBindings($ID),
					$contactBindings,
					$addedContactBindings,
					$removedContactBindings
				);

				$addedContactIDs = \Bitrix\Crm\Binding\EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Contact,
					$addedContactBindings
				);

				$removedContactIDs = \Bitrix\Crm\Binding\EntityBinding::prepareEntityIDs(
					CCrmOwnerType::Contact,
					$removedContactBindings
				);
			}
			//endregion

			if ($bCompare)
			{
				$compareOptions = array();
				if(!empty($addedContactIDs) || !empty($removedContactIDs))
				{
					$compareOptions['CONTACTS'] = array('ADDED' => $addedContactIDs, 'REMOVED' => $removedContactIDs);
				}
				$arEvents = self::CompareFields($arRow, $arFields, $this->bCheckPermission, $compareOptions);
				foreach($arEvents as $arEvent)
				{
					$arEvent['ENTITY_TYPE'] = 'QUOTE';
					$arEvent['ENTITY_ID'] = $ID;
					$arEvent['EVENT_TYPE'] = 1;
					if (!isset($arEvent['USER_ID']))
						$arEvent['USER_ID'] = $iUserId;

					$isRelationEvent = in_array(
						$arEvent['ENTITY_FIELD'],
						['DEAL_ID', 'COMPANY_ID', 'CONTACT_ID', 'MYCOMPANY_ID', 'LEAD_ID'],
						true,
					);

					if (!$isRelationEvent)
					{
						$CCrmEvent = new CCrmEvent();
						$CCrmEvent->Add($arEvent, $this->bCheckPermission);
					}
				}
			}

			$arFields = array_merge($arFields, \CCrmAccountingHelper::calculateAccountingData($arFields, $arRow, true));

			if(isset($arFields['STATUS_ID']))
			{
				$arFields['CLOSED'] = self::GetStatusSemantics($arFields['STATUS_ID']) === 'process' ? 'N' : 'Y';
			}

			if (isset($arFields['BEGINDATE']) && !isset($arFields['BEGINDATE'][0]))
			{
				unset($arFields['BEGINDATE']);
			}

			if(isset($arFields['CLOSED'])
				&& $arFields['CLOSED'] === 'Y'
				&& (!isset($arFields['CLOSEDATE'])
					|| $arFields['CLOSEDATE'] === ''))
			{
				$arFields['CLOSEDATE'] = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', SITE_ID);
			}


			if(!isset($arFields['ID']))
			{
				$arFields['ID'] = $ID;
			}

			self::getLastActivityAdapter()->performUpdate((int)$ID, $arFields, $options);

			foreach (GetModuleEvents('crm', 'OnBeforeCrmQuoteUpdate', true) as $arEvent)
			{
				if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
				{
					if(isset($arFields['RESULT_MESSAGE']))
					{
						$this->LAST_ERROR = $arFields['RESULT_MESSAGE'];
					}
					else
					{
						$this->LAST_ERROR = GetMessage('CRM_QUOTE_UPDATE_CANCELED', array('#NAME#' => $arEvent['TO_NAME']));
						$arFields['RESULT_MESSAGE'] = &$this->LAST_ERROR;
					}
					return false;
				}
			}

			unset($arFields['ID']);

			$this->normalizeEntityFields($arFields);
			$sUpdate = $DB->PrepareUpdate(self::TABLE_NAME, $arFields);

			if ($sUpdate <> '')
			{
				$clobFieldNames = array('COMMENTS', 'CONTENT', 'STORAGE_ELEMENT_IDS');
				$arBinds = array();
				foreach ($clobFieldNames as $fieldName)
				{
					if (array_key_exists($fieldName, $arFields))
						$arBinds[$fieldName] = $arFields[$fieldName];
				}
				unset($fieldName);

				$sql = "UPDATE b_crm_quote SET {$sUpdate} WHERE ID = {$ID}";
				if(!empty($arBinds))
				{
					$DB->QueryBind($sql, $arBinds, false);
				}
				else
				{
					$DB->Query($sql, false, 'File: '.__FILE__.'<br>Line: '.__LINE__);
				}
				$bResult = true;
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				static $arNameFields = array("TITLE");
				$bClear = false;
				foreach($arNameFields as $val)
				{
					if(isset($arFields[$val]))
					{
						$bClear = true;
						break;
					}
				}
				if ($bClear)
				{
					$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Quote."_".$ID);
				}
			}

			$securityRegisterOptions = (new \Bitrix\Crm\Security\Controller\RegisterOptions())
				->setEntityAttributes($arEntityAttr)
			;
			Crm\Security\Manager::getEntityController(CCrmOwnerType::Quote)
				->register(self::$TYPE_NAME, $ID, $securityRegisterOptions)
			;

			//region Save contacts
			if(!empty($removedContactBindings))
			{
				QuoteContactTable::unbindContacts($ID, $removedContactBindings);
			}

			if(!empty($addedContactBindings))
			{
				QuoteContactTable::bindContacts($ID, $addedContactBindings);
			}
			//endregion

			CCrmEntityHelper::NormalizeUserFields($arFields, self::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => false));
			$GLOBALS['USER_FIELD_MANAGER']->Update(self::$sUFEntityID, $ID, $arFields);

			if(is_array($storageElementIDs))
			{
				CCrmQuote::DoSaveElementIDs($ID, $storageTypeID, $storageElementIDs);
			}
			unset($storageTypeID, $storageElementIDs);

			// update utm fields
			UtmTable::updateEntityUtmFromFields(CCrmOwnerType::Quote, $ID, $arFields);

			if($bUpdateSearch)
			{
				$arFilterTmp = Array('ID' => $ID);
				if (!$this->bCheckPermission)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";
				CCrmSearch::UpdateSearch($arFilterTmp, 'QUOTE', true);
			}

			//region Search content index
			Bitrix\Crm\Search\SearchContentBuilderFactory::create(CCrmOwnerType::Quote)->build($ID);
			//endregion

			$arFields['ID'] = $ID;

			if (isset($arFields['FM']) && is_array($arFields['FM']))
			{
				$CCrmFieldMulti = new CCrmFieldMulti();
				$CCrmFieldMulti->SetFields('QUOTE', $ID, $arFields['FM']);
			}

			// Responsible user sync
			//CCrmActivity::Synchronize(CCrmOwnerType::Quote, $ID);

			self::getContentTypeIdAdapter()
				->setPreviousFields((int)$ID, $arRow)
				->performUpdate((int)$ID, $arFields, $options)
			;

			if($bResult)
			{
				foreach (GetModuleEvents('crm', 'OnAfterCrmQuoteUpdate', true) as $arEvent)
					ExecuteModuleEventEx($arEvent, array(&$arFields));
			}
		}
		return $bResult;
	}

	public function Delete($ID, $options = array())
	{
		$this->lastErrors = null;

		$ID = (int)$ID;

		if(!is_array($options))
		{
			$options = array();
		}

		if($this->isUseOperation())
		{
			return $this->getCompatibilityAdapter()->performDelete($ID, $options);
		}

		global $DB, $APPLICATION;

		if(isset($options['CURRENT_USER']))
		{
			$iUserId = intval($options['CURRENT_USER']);
		}
		else
		{
			$iUserId = CCrmSecurityHelper::GetCurrentUserID();
		}

		$dbResult = CCrmQuote::GetList(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('QUOTE_NUMBER', 'TITLE')
		);

		$arFields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($arFields))
		{
			return false;
		}

		$sWherePerm = '';
		if ($this->bCheckPermission)
		{
			$arEntityAttr = $this->cPerms->GetEntityAttr(self::$TYPE_NAME, $ID);
			$sEntityPerm = $this->cPerms->GetPermType(self::$TYPE_NAME, 'DELETE', $arEntityAttr[$ID]);
			if ($sEntityPerm == BX_CRM_PERM_NONE)
				return false;
			else if ($sEntityPerm == BX_CRM_PERM_SELF)
				$sWherePerm = " AND ASSIGNED_BY_ID = {$iUserId}";
			else if ($sEntityPerm == BX_CRM_PERM_OPEN)
				$sWherePerm = " AND (OPENED = 'Y' OR ASSIGNED_BY_ID = {$iUserId})";
		}

		$APPLICATION->ResetException();
		foreach (GetModuleEvents('crm', 'OnBeforeCrmQuoteDelete', true) as $arEvent)
		{
			if (ExecuteModuleEventEx($arEvent, array($ID))===false)
			{
				$err = GetMessage("MAIN_BEFORE_DEL_ERR").' '.$arEvent['TO_NAME'];
				if ($ex = $APPLICATION->GetException())
					$err .= ': '.$ex->GetString();
				$APPLICATION->throwException($err);
				return false;
			}
		}

		if (!(isset($options['SKIP_FILES']) && $options['SKIP_FILES']))
		{
			if(!self::DeleteStorageElements($ID))
				return false;

			if(!$DB->Query(
				'DELETE FROM '.CCrmQuote::ELEMENT_TABLE_NAME.' WHERE QUOTE_ID = '.$ID,
				false, 'File: '.__FILE__.'<br/>Line: '.__LINE__))
			{
				$APPLICATION->throwException(GetMessage('CRM_QUOTE_ERR_DELETE_STORAGE_ELEMENTS_QUERY'));
				return false;
			}
		}

		$dbRes = $DB->Query("DELETE FROM b_crm_quote WHERE ID = {$ID}{$sWherePerm}", false, 'FILE: '.__FILE__.'<br /> LINE: '.__LINE__);
		if (is_object($dbRes) && $dbRes->AffectedRowsCount() > 0)
		{
			Bitrix\Crm\Kanban\SortTable::clearEntity($ID, \CCrmOwnerType::QuoteName);

			Crm\Security\Manager::getEntityController(CCrmOwnerType::Quote)
				->unregister(self::$TYPE_NAME, $ID)
			;

			QuoteContactTable::unbindAllContacts($ID);

			$GLOBALS['USER_FIELD_MANAGER']->Delete(self::$sUFEntityID, $ID);
			$CCrmFieldMulti = new CCrmFieldMulti();
			$CCrmFieldMulti->DeleteByElement('QUOTE', $ID);
			$CCrmEvent = new CCrmEvent();
			$CCrmEvent->DeleteByElement('QUOTE', $ID);

			CCrmSearch::DeleteSearch('QUOTE', $ID);

			// Deletion of quote details
			CCrmProductRow::DeleteByOwner(self::OWNER_TYPE, $ID);
			CCrmProductRow::DeleteSettings(self::OWNER_TYPE, $ID);
			/*CCrmActivity::DeleteByOwner(CCrmOwnerType::Quote, $ID);*/
			\Bitrix\Crm\Requisite\EntityLink::unregister(CCrmOwnerType::Quote, $ID);

			self::getContentTypeIdAdapter()->performDelete((int)$ID, $options);

			// delete utm fields
			UtmTable::deleteEntityUtm(CCrmOwnerType::Quote, $ID);
			Tracking\Entity::deleteTrace(CCrmOwnerType::Quote, $ID);

			if(Bitrix\Crm\Settings\HistorySettings::getCurrent()->isQuoteDeletionEventEnabled())
			{
				CCrmEvent::RegisterDeleteEvent(CCrmOwnerType::Quote, $ID, $iUserId, array('FIELDS' => $arFields));
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				$GLOBALS['CACHE_MANAGER']->CleanDir('b_crm_quote');
				$GLOBALS["CACHE_MANAGER"]->ClearByTag("crm_entity_name_".CCrmOwnerType::Quote."_".$ID);
			}

			$afterEvents = GetModuleEvents('crm', 'OnAfterCrmQuoteDelete');
			while ($arEvent = $afterEvents->Fetch())
			{
				ExecuteModuleEventEx($arEvent, array($ID));
			}
		}
		return true;
	}

	/**
	 * Generates next quote number according to the scheme selected in the module options (quote_number_...)
	 *
	 * @param int $ID - quote ID
	 * @param string $templateType - quote number template type code
	 * @param string $param - quote number template param
	 * @return mixed - generated number or false
	 */
	public static function GetNextQuoteNumber($ID, $templateType, $param)
	{
		global $DB;
		$value = false;

		switch ($templateType)
		{
			case 'NUMBER':

				$param = intval($param);
				$maxLastID = 0;
				$strSql = '';

				switch($DB->type)
				{
					case "MYSQL":
						$strSql = "SELECT ID, QUOTE_NUMBER FROM b_crm_quote WHERE QUOTE_NUMBER IS NOT NULL ORDER BY ID DESC LIMIT 1";
						break;
					case "ORACLE":
						$strSql = "SELECT ID, QUOTE_NUMBER FROM b_crm_quote WHERE QUOTE_NUMBER IS NOT NULL AND ROWNUM <= 1 ORDER BY ID DESC";
						break;
					case "MSSQL":
						$strSql = "SELECT TOP 1 ID, QUOTE_NUMBER FROM b_crm_quote WHERE QUOTE_NUMBER IS NOT NULL ORDER BY ID DESC";
						break;
				}

				$dbres = $DB->Query($strSql, true);
				if ($arRes = $dbres->GetNext())
				{
					if (mb_strlen($arRes["QUOTE_NUMBER"]) === mb_strlen(intval($arRes["QUOTE_NUMBER"])))
						$maxLastID = intval($arRes["QUOTE_NUMBER"]);
				}

				$value = ($maxLastID >= $param) ? $maxLastID + 1 : $param;
				break;

			case 'PREFIX':

				$value = $param.$ID;
				break;

			case 'RANDOM':

				$rand = randString(intval($param), array("ABCDEFGHIJKLNMOPQRSTUVWXYZ", "0123456789"));
				$dbres = $DB->Query("SELECT ID, QUOTE_NUMBER FROM b_crm_quote WHERE QUOTE_NUMBER = '".$rand."'", true);
				$value = ($arRes = $dbres->GetNext()) ? false : $rand;
				break;

			case 'USER':

				$dbres = $DB->Query("SELECT ASSIGNED_BY_ID FROM b_crm_quote WHERE ID = '".$ID."'", true);

				if ($arRes = $dbres->GetNext())
				{
					$userID = intval($arRes["ASSIGNED_BY_ID"]);
					$strSql = '';

					switch($DB->type)
					{
						case "MYSQL":
							$strSql = "SELECT MAX(CAST(SUBSTRING(QUOTE_NUMBER, LENGTH('".$userID."_') + 1) as UNSIGNED)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$userID."\_%'";
							break;
						case "ORACLE":
							$strSql = "SELECT MAX(CAST(SUBSTR(QUOTE_NUMBER, LENGTH('".$userID."_') + 1) as NUMBER)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$userID."_%'";
							break;
						case "MSSQL":
							$strSql = "SELECT MAX(CAST(SUBSTRING(QUOTE_NUMBER, LEN('".$userID."_') + 1, LEN(QUOTE_NUMBER)) as INT)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$userID."_%'";
							break;
					}

					$dbres = $DB->Query($strSql, true);
					if ($arRes = $dbres->GetNext())
					{
						$numID = (intval($arRes["NUM_ID"]) > 0) ? $arRes["NUM_ID"] + 1 : 1;
						$value = $userID."_".$numID;
					}
					else
						$value = $userID."_1";
				}
				else
					$value = false;

				break;

			case 'DATE':
				$date = '';
				switch ($param)
				{
					// date in the site format but without delimeters
					case 'day':
						$date = date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), mktime(0, 0, 0, date("m"), date("d"), date("Y")));
						$date = preg_replace("/[^0-9]/", "", $date);
						break;
					case 'month':
						$date = date($DB->DateFormatToPHP(str_replace("DD", "", CSite::GetDateFormat("SHORT"))), mktime(0, 0, 0, date("m"), date("d"), date("Y")));
						$date = preg_replace("/[^0-9]/", "", $date);
						break;
					case 'year':
						$date = date('Y');
						break;
				}

				$strSql = '';
				switch($DB->type)
				{
					case "MYSQL":
						$strSql = "SELECT MAX(CAST(SUBSTRING(QUOTE_NUMBER, LENGTH('".$date." / ') + 1) as UNSIGNED)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$date." / %'";
						break;
					case "ORACLE":
						$strSql = "SELECT MAX(CAST(SUBSTR(QUOTE_NUMBER, LENGTH('".$date." / ') + 1) as NUMBER)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$date." / %'";
						break;
					case "MSSQL":
						$strSql = "SELECT MAX(CAST(SUBSTRING(QUOTE_NUMBER, LEN('".$date." / ') + 1, LEN(QUOTE_NUMBER)) as INT)) as NUM_ID FROM b_crm_quote WHERE QUOTE_NUMBER LIKE '".$date." / %'";
						break;
				}

				$dbres = $DB->Query($strSql, true);
				if ($arRes = $dbres->GetNext())
				{
					$numID = (intval($arRes["NUM_ID"]) > 0) ? $arRes["NUM_ID"] + 1 : 1;
					$value = $date." / ".$numID;
				}
				else
					$value = $date." / 1";

				break;

			default: // if unknown template

				$value = false;
				break;
		}

		return $value;
	}

	/**
	 * Sets quote number
	 * Use OnBeforeQuoteNumberSet event to generate custom quote number.
	 * Quote number value must be unique! By default quote ID is used if generated value is incorrect
	 *
	 * @param int $ID - quote ID
	 * @return bool - true if quote number is set successfully
	 */
	private static function SetQuoteNumber($ID)
	{
		global $DB;

		$ID = intval($ID);
		if ($ID <= 0)
			return false;

		$type = COption::GetOptionString("crm", "quote_number_template", "");
		$numeratorSettings = \Bitrix\Main\Numerator\Numerator::getOneByType(REGISTRY_TYPE_CRM_QUOTE);

		$bCustomAlgorithm = false;
		$value = $ID;
		foreach(GetModuleEvents("crm", "OnBeforeCrmQuoteNumberSet", true) as $arEvent)
		{
			$tmpRes = ExecuteModuleEventEx($arEvent, Array($ID, $type));
			if ($tmpRes !== false)
			{
				$bCustomAlgorithm = true;
				$value = $tmpRes;
			}
		}

		if ($bCustomAlgorithm)
		{
			$arFields = array("QUOTE_NUMBER" => $value);
			$sUpdate = $DB->PrepareUpdate('b_crm_quote', $arFields);
			$sql = "UPDATE b_crm_quote SET $sUpdate WHERE ID = $ID";
			$res = $DB->Query($sql, true);
		}
		else
		{
			$res = false;
			if ($numeratorSettings) // if special template (numerator) is selected
			{
				for ($i = 0; $i < 10; $i++)
				{
					$value = false;
					$numerator = \Bitrix\Main\Numerator\Numerator::load($numeratorSettings['id'], ['QUOTE_ID' => $ID]);
					if ($numerator)
					{
						$value = $numerator->getNext();
					}

					if ($value !== false)
					{
						$arFields = array("QUOTE_NUMBER" => $value);
						$sUpdate = $DB->PrepareUpdate('b_crm_quote', $arFields);
						$sql = "UPDATE b_crm_quote SET $sUpdate WHERE ID = $ID";
						$res = $DB->Query($sql, true);
						if ($res)
						{
							break;
						}
					}
				}
			}
		}

		if (!$numeratorSettings || !$res) // if no special template is used or error occured
		{
			if ($type !== 'NUMBER')
			{
				$arFields = array('QUOTE_NUMBER' => $ID);
				$sUpdate = $DB->PrepareUpdate('b_crm_quote', $arFields);
				$sql = "UPDATE b_crm_quote SET $sUpdate WHERE ID = $ID";
				$res = $DB->Query($sql, true);
			}
			if (!$res && (!$numeratorSettings || $type === 'NUMBER'))    // try set max number + 1
			{
				$maxLastId = $ID;
				$maxLastIdIsSet = false;
				for ($i = 0; $i < 10; $i++)
				{
					$sql = 'SELECT MAX(CAST(QUOTE_NUMBER AS UNSIGNED)) AS LAST_NUMBER FROM b_crm_quote';
					$resLastId = $DB->Query($sql, true);
					if ($row = $resLastId->fetch())
					{
						if (strval($row['LAST_NUMBER']) <> '')
						{
							$maxLastId = $row['LAST_NUMBER'];
							$maxLastIdIsSet = true;
						}
					}
					if ($maxLastIdIsSet)
					{
						$arFields = array('~QUOTE_NUMBER' => "($maxLastId + 1)");
						$sUpdate = $DB->PrepareUpdate('b_crm_quote', $arFields);
						$sql = "UPDATE b_crm_quote SET $sUpdate WHERE ID = $ID";
						$res = $DB->Query($sql, true);
						if ($res)
						{
							COption::SetOptionString("crm", "quote_number_template", "NUMBER");
							COption::SetOptionString("crm", "quote_number_data", $maxLastId);
							if (!$numeratorSettings)
							{
								$numeratorForQuotes = \Bitrix\Main\Numerator\Numerator::create();
								$numeratorForQuotes->setConfig([
									\Bitrix\Main\Numerator\Numerator::getType() =>
										[
											'name'     => 'numerator for quote',
											'template' => '{NUMBER}',
											'type'     => 'QUOTE',
										],
									\Bitrix\Main\Numerator\Generator\SequentNumberGenerator::getType() =>
										[
											'start'              => $maxLastId,
											'isDirectNumeration' => true,
										],
								]);
								$numeratorForQuotes->save();
							}
							break;
						}
					}
				}
			}
		}

		return $res;
	}

	public static function CompareFields($arFieldsOrig, $arFieldsModif, $bCheckPerms = true, $arOptions = null)
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		$arMsg = Array();

		if (array_key_exists('QUOTE_NUMBER', $arFieldsModif))
		{
			$origQuoteNumber = isset($arFieldsOrig['QUOTE_NUMBER']) ? $arFieldsOrig['QUOTE_NUMBER'] : '';
			$modifQuoteNumber = isset($arFieldsModif['QUOTE_NUMBER']) ? $arFieldsModif['QUOTE_NUMBER'] : '';
			if ($origQuoteNumber != $modifQuoteNumber)
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'QUOTE_NUMBER',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_QUOTE_NUMBER'),
					'EVENT_TEXT_1' => !empty($origQuoteNumber) ? $origQuoteNumber : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => !empty($modifQuoteNumber) ? $modifQuoteNumber : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY')
				);
			unset($origQuoteNumber, $modifQuoteNumber);
		}

		if (array_key_exists('TITLE', $arFieldsModif))
		{
			$origTitle = isset($arFieldsOrig['TITLE']) ? $arFieldsOrig['TITLE'] : '';
			$modifTitle = isset($arFieldsModif['TITLE']) ? $arFieldsModif['TITLE'] : '';
			if ($origTitle != $modifTitle)
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'TITLE',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_TITLE'),
					'EVENT_TEXT_1' => !empty($origTitle) ? $origTitle : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => !empty($modifTitle) ? $modifTitle : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY')
				);
			unset($origTitle, $modifTitle);
		}

		if (array_key_exists('LEAD_ID', $arFieldsModif))
		{
			$origLeadId = isset($arFieldsOrig['LEAD_ID']) ? intval($arFieldsOrig['LEAD_ID']) : 0;
			$modifLeadId = isset($arFieldsModif['LEAD_ID']) ? intval($arFieldsModif['LEAD_ID']) : 0;
			if ($origLeadId != $modifLeadId)
			{
				$arLead = Array();

				$arFilterTmp = array('@ID' => array($origLeadId, $modifLeadId));
				if (!$bCheckPerms)
				{
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";
				}

				$dbRes = CCrmLead::GetListEx(array('TITLE'=>'ASC'), $arFilterTmp);
				while ($arRes = $dbRes->Fetch())
				{
					$arLead[$arRes['ID']] = $arRes['TITLE'];
				}

				$arMsg[] = Array(
					'ENTITY_FIELD' => 'LEAD_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_LEAD_ID'),
					'EVENT_TEXT_1' => CrmCompareFieldsList($arLead, $origLeadId),
					'EVENT_TEXT_2' => CrmCompareFieldsList($arLead, $modifLeadId)
				);
			}
			unset($origLeadId, $modifLeadId);
		}

		if (array_key_exists('DEAL_ID', $arFieldsModif))
		{
			$origDealId = isset($arFieldsOrig['DEAL_ID']) ? intval($arFieldsOrig['DEAL_ID']) : 0;
			$modifDealId = isset($arFieldsModif['DEAL_ID']) ? intval($arFieldsModif['DEAL_ID']) : 0;
			if ($origDealId != $modifDealId)
			{
				$arDeal = Array();

				$arFilterTmp = array('ID' => array($origDealId, $modifDealId));
				if (!$bCheckPerms)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";

				$dbRes = CCrmDeal::GetList(Array('TITLE'=>'ASC'), $arFilterTmp);
				while ($arRes = $dbRes->Fetch())
					$arDeal[$arRes['ID']] = $arRes['TITLE'];

				$arMsg[] = Array(
					'ENTITY_FIELD' => 'DEAL_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_DEAL_ID'),
					'EVENT_TEXT_1' => CrmCompareFieldsList($arDeal, $origDealId),
					'EVENT_TEXT_2' => CrmCompareFieldsList($arDeal, $modifDealId)
				);
			}
			unset($origDealId, $modifDealId);
		}

		if (array_key_exists('COMPANY_ID', $arFieldsModif))
		{
			$origCompanyId = isset($arFieldsOrig['COMPANY_ID']) ? intval($arFieldsOrig['COMPANY_ID']) : 0;
			$modifCompanyId = isset($arFieldsModif['COMPANY_ID']) ? intval($arFieldsModif['COMPANY_ID']) : 0;
			if ($origCompanyId != $modifCompanyId)
			{
				$arCompany = Array();

				$arFilterTmp = array('ID' => array($origCompanyId, $modifCompanyId));
				if (!$bCheckPerms)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";

				$dbRes = CCrmCompany::GetList(Array('TITLE'=>'ASC'), $arFilterTmp);
				while ($arRes = $dbRes->Fetch())
					$arCompany[$arRes['ID']] = $arRes['TITLE'];

				$arMsg[] = Array(
					'ENTITY_FIELD' => 'COMPANY_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_COMPANY_ID'),
					'EVENT_TEXT_1' => CrmCompareFieldsList($arCompany, $origCompanyId),
					'EVENT_TEXT_2' => CrmCompareFieldsList($arCompany, $modifCompanyId)
				);
			}
			unset($origCompanyId, $modifCompanyId);
		}

		if(isset($arOptions['CONTACTS']) && is_array($arOptions['CONTACTS']))
		{
			$addedContactIDs = isset($arOptions['CONTACTS']['ADDED']) && is_array($arOptions['CONTACTS']['ADDED'])
				? $arOptions['CONTACTS']['ADDED'] : array();

			$removedContactIDs = isset($arOptions['CONTACTS']['REMOVED']) && is_array($arOptions['CONTACTS']['REMOVED'])
				? $arOptions['CONTACTS']['REMOVED'] : array();

			if(!empty($addedContactIDs) || !empty($removedContactIDs))
			{
				//region Preparation of contact names
				$dbResult = CCrmContact::GetListEx(
					array(),
					array(
						'CHECK_PERMISSIONS' => 'N',
						'@ID' => array_merge($addedContactIDs, $removedContactIDs)
					),
					false,
					false,
					array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME')
				);

				$contactNames = array();
				while ($ary = $dbResult->Fetch())
				{
					$contactNames[$ary['ID']] = CCrmContact::PrepareFormattedName($ary);
				}
				//endregion
				if(count($addedContactIDs) <= 1 && count($removedContactIDs) <= 1)
				{
					//region Single binding mode
					$arMsg[] = Array(
						'ENTITY_FIELD' => 'CONTACT_ID',
						'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_CONTACT_ID'),
						'EVENT_TEXT_1' => CrmCompareFieldsList(
							$contactNames,
							isset($removedContactIDs[0]) ? $removedContactIDs[0] : 0
						),
						'EVENT_TEXT_2' => CrmCompareFieldsList(
							$contactNames,
							isset($addedContactIDs[0]) ? $addedContactIDs[0] : 0
						)
					);
					//endregion
				}
				else
				{
					//region Multiple binding mode
					//region Add contacts event
					$texts = array();
					foreach($addedContactIDs as $contactID)
					{
						if(isset($contactNames[$contactID]))
						{
							$texts[] = $contactNames[$contactID];
						}
					}

					$arMsg[] = Array(
						'ENTITY_FIELD' => 'CONTACT_ID',
						'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_CONTACTS_ADDED'),
						'EVENT_TEXT_1' => implode(', ', $texts),
					);
					//endregion
					//region Remove companies event
					$texts = array();
					foreach($removedContactIDs as $contactID)
					{
						if(isset($contactNames[$contactID]))
						{
							$texts[] = $contactNames[$contactID];
						}
					}

					$arMsg[] = Array(
						'ENTITY_FIELD' => 'CONTACT_ID',
						'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_CONTACTS_REMOVED'),
						'EVENT_TEXT_1' => implode(', ', $texts),
					);
					//endregion
					//endregion
				}
			}
		}

		if (array_key_exists('MYCOMPANY_ID', $arFieldsModif))
		{
			$origMyCompanyId = isset($arFieldsOrig['MYCOMPANY_ID']) ? intval($arFieldsOrig['MYCOMPANY_ID']) : 0;
			$modifMyCompanyId = isset($arFieldsModif['MYCOMPANY_ID']) ? intval($arFieldsModif['MYCOMPANY_ID']) : 0;
			if ($origMyCompanyId != $modifMyCompanyId)
			{
				$arMyCompany = array();

				$arFilterTmp = array('ID' => array($origMyCompanyId, $modifMyCompanyId));
				if (!$bCheckPerms)
					$arFilterTmp["CHECK_PERMISSIONS"] = "N";

				$dbRes = CCrmCompany::GetList(array('TITLE'=>'ASC'), $arFilterTmp);
				while ($arRes = $dbRes->Fetch())
					$arMyCompany[$arRes['ID']] = $arRes['TITLE'];

				$arMsg[] = array(
					'ENTITY_FIELD' => 'MYCOMPANY_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_MYCOMPANY_ID1'),
					'EVENT_TEXT_1' => CrmCompareFieldsList($arMyCompany, $origMyCompanyId),
					'EVENT_TEXT_2' => CrmCompareFieldsList($arMyCompany, $modifMyCompanyId)
				);
				unset($arMyCompany, $arFilterTmp, $dbRes, $arRes);
			}
			unset($origMyCompanyId, $modifMyCompanyId);
		}

		if (array_key_exists('ASSIGNED_BY_ID', $arFieldsModif))
		{
			$origAssignedById = isset($arFieldsOrig['ASSIGNED_BY_ID']) ? intval($arFieldsOrig['ASSIGNED_BY_ID']) : 0;
			$modifAssignedById = isset($arFieldsModif['ASSIGNED_BY_ID']) ? intval($arFieldsModif['ASSIGNED_BY_ID']) : 0;
			if ($origAssignedById != $modifAssignedById)
			{
				$arUser = Array();
				$dbUsers = CUser::GetList(
					'last_name', 'asc',
					array('ID' => implode('|', array(intval($origAssignedById), intval($modifAssignedById)))),
					array('FIELDS' => array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'TITLE', 'EMAIL'))
				);
				while ($arRes = $dbUsers->Fetch())
					$arUser[$arRes['ID']] = CUser::FormatName(CSite::GetNameFormat(false), $arRes);

				$arMsg[] = Array(
					'ENTITY_FIELD' => 'ASSIGNED_BY_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_ASSIGNED_BY_ID'),
					'EVENT_TEXT_1' => CrmCompareFieldsList($arUser, $origAssignedById),
					'EVENT_TEXT_2' => CrmCompareFieldsList($arUser, $modifAssignedById)
				);
			}
			unset($origAssignedById, $modifAssignedById);
		}

		if (array_key_exists('STATUS_ID', $arFieldsModif))
		{
			$origStatusId = isset($arFieldsOrig['STATUS_ID']) ? $arFieldsOrig['STATUS_ID'] : '';
			$modifStatusId = isset($arFieldsModif['STATUS_ID']) ? $arFieldsModif['STATUS_ID'] : '';
			if ($origStatusId != $modifStatusId)
			{
				$arStatus = CCrmStatus::GetStatusList('QUOTE_STATUS');
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'STATUS_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_STATUS_ID'),
					'EVENT_TEXT_1' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $origStatusId)),
					'EVENT_TEXT_2' => htmlspecialcharsbx(CrmCompareFieldsList($arStatus, $modifStatusId))
				);
			}
			unset($origStatusId, $modifStatusId);
		}

		if (array_key_exists('COMMENTS', $arFieldsModif))
		{
			$origComments = isset($arFieldsOrig['COMMENTS']) ? $arFieldsOrig['COMMENTS'] : '';
			$modifComments = isset($arFieldsModif['COMMENTS']) ? $arFieldsModif['COMMENTS'] : '';
			if ($origComments != $modifComments)
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'COMMENTS',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_COMMENTS'),
					'EVENT_TEXT_1' => !empty($origComments) ? $origComments : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => !empty($modifComments) ? $modifComments : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY')
				);
			unset($origComments, $modifComments);
		}

		if (array_key_exists('CONTENT', $arFieldsModif))
		{
			$origContent = isset($arFieldsOrig['CONTENT']) ? $arFieldsOrig['CONTENT'] : '';
			$modifContent = isset($arFieldsModif['CONTENT']) ? $arFieldsModif['CONTENT'] : '';
			if ($origContent != $modifContent)
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'CONTENT',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_CONTENT'),
					'EVENT_TEXT_1' => !empty($origContent)? $origContent : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => !empty($modifContent)? $modifContent : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY')
				);
			unset($origContent, $modifContent);
		}

		if (array_key_exists('TERMS', $arFieldsModif))
		{
			$origTerms = isset($arFieldsOrig['TERMS']) ? $arFieldsOrig['TERMS'] : '';
			$modifTerms = isset($arFieldsModif['TERMS']) ? $arFieldsModif['TERMS'] : '';
			if ($origTerms != $modifTerms)
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'TERMS',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_TERMS'),
					'EVENT_TEXT_1' => !empty($origTerms)? $origTerms : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					'EVENT_TEXT_2' => !empty($modifTerms)? $modifTerms : GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
				);
			unset($origTerms, $modifTerms);
		}

		if (array_key_exists('OPPORTUNITY', $arFieldsModif) || array_key_exists('CURRENCY_ID', $arFieldsModif))
		{
			$origOpportunity = isset($arFieldsOrig['OPPORTUNITY']) ? round(doubleval($arFieldsOrig['OPPORTUNITY']), 2) : 0.0;
			$modifOpportunity = isset($arFieldsModif['OPPORTUNITY']) ? round(doubleval($arFieldsModif['OPPORTUNITY']), 2) : $origOpportunity;
			$origCurrencyId = isset($arFieldsOrig['CURRENCY_ID']) ? $arFieldsOrig['CURRENCY_ID'] : '';
			$modifCurrencyId = isset($arFieldsModif['CURRENCY_ID']) ? $arFieldsModif['CURRENCY_ID'] : $origCurrencyId;
			if ($origOpportunity != $modifOpportunity || $origCurrencyId != $modifCurrencyId)
			{
				$arStatus = CCrmCurrencyHelper::PrepareListItems();
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'OPPORTUNITY',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_OPPORTUNITY'),
					'EVENT_TEXT_1' => floatval($arFieldsOrig['OPPORTUNITY']).(($val = CrmCompareFieldsList($arStatus, $origCurrencyId, '')) != '' ? ' ('.$val.')' : ''),
					'EVENT_TEXT_2' => floatval($arFieldsModif['OPPORTUNITY']).(($val = CrmCompareFieldsList($arStatus, $modifCurrencyId, '')) != '' ? ' ('.$val.')' : '')
				);
			}
			unset($origOpportunity, $modifOpportunity, $origCurrencyId, $modifCurrencyId);
		}

		if (array_key_exists('TAX_VALUE', $arFieldsModif) || array_key_exists('CURRENCY_ID', $arFieldsModif))
		{
			if ((isset($arFieldsOrig['TAX_VALUE']) && isset($arFieldsModif['TAX_VALUE']) && $arFieldsOrig['TAX_VALUE'] != $arFieldsModif['TAX_VALUE'])
				|| (isset($arFieldsOrig['CURRENCY_ID']) && isset($arFieldsModif['CURRENCY_ID']) && $arFieldsOrig['CURRENCY_ID'] != $arFieldsModif['CURRENCY_ID']))
			{
				$arStatus = CCrmCurrencyHelper::PrepareListItems();
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'TAX_VALUE',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_TAX_VALUE'),
					'EVENT_TEXT_1' => floatval($arFieldsOrig['TAX_VALUE']).(($val = CrmCompareFieldsList($arStatus, $arFieldsOrig['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : ''),
					'EVENT_TEXT_2' => floatval($arFieldsModif['TAX_VALUE']).(($val = CrmCompareFieldsList($arStatus, $arFieldsModif['CURRENCY_ID'], '')) != '' ? ' ('.$val.')' : '')
				);
			}
		}

		if (array_key_exists('BEGINDATE', $arFieldsOrig) && array_key_exists('BEGINDATE', $arFieldsModif) &&
			ConvertTimeStamp(strtotime($arFieldsOrig['BEGINDATE'])) != $arFieldsModif['BEGINDATE'] && $arFieldsOrig['BEGINDATE'] != $arFieldsModif['BEGINDATE'])
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'BEGINDATE',
				'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_BEGINDATE'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['BEGINDATE'])? ConvertTimeStamp(strtotime($arFieldsOrig['BEGINDATE'])): GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['BEGINDATE'])? $arFieldsModif['BEGINDATE']: GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
			);
		}
		if (array_key_exists('CLOSEDATE', $arFieldsOrig) && array_key_exists('CLOSEDATE', $arFieldsModif) &&
			ConvertTimeStamp(strtotime($arFieldsOrig['CLOSEDATE'])) != $arFieldsModif['CLOSEDATE'] && $arFieldsOrig['CLOSEDATE'] != $arFieldsModif['CLOSEDATE'])
		{
			$arMsg[] = Array(
				'ENTITY_FIELD' => 'CLOSEDATE',
				'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_CLOSEDATE'),
				'EVENT_TEXT_1' => !empty($arFieldsOrig['CLOSEDATE'])? ConvertTimeStamp(strtotime($arFieldsOrig['CLOSEDATE'])): GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
				'EVENT_TEXT_2' => !empty($arFieldsModif['CLOSEDATE'])? $arFieldsModif['CLOSEDATE']: GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
			);
		}

		if (array_key_exists('OPENED', $arFieldsModif))
		{
			if (isset($arFieldsOrig['OPENED']) && isset($arFieldsModif['OPENED'])
				&& $arFieldsOrig['OPENED'] != $arFieldsModif['OPENED'])
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'OPENED',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_OPENED'),
					'EVENT_TEXT_1' => $arFieldsOrig['OPENED'] == 'Y'? GetMessage('MAIN_YES'): GetMessage('MAIN_NO'),
					'EVENT_TEXT_2' => $arFieldsModif['OPENED'] == 'Y'? GetMessage('MAIN_YES'): GetMessage('MAIN_NO'),
				);
		}

		if (array_key_exists('CLOSED', $arFieldsModif))
		{
			if (isset($arFieldsOrig['CLOSED']) && isset($arFieldsModif['CLOSED'])
				&& $arFieldsOrig['CLOSED'] != $arFieldsModif['CLOSED'])
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'CLOSED',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_CLOSED'),
					'EVENT_TEXT_1' => $arFieldsOrig['CLOSED'] == 'Y'? GetMessage('MAIN_YES'): GetMessage('MAIN_NO'),
					'EVENT_TEXT_2' => $arFieldsModif['CLOSED'] == 'Y'? GetMessage('MAIN_YES'): GetMessage('MAIN_NO'),
				);
		}

		// person type
		if (array_key_exists('PERSON_TYPE_ID', $arFieldsModif))
		{
			$bPersonTypeChanged = (isset($arFieldsOrig['PERSON_TYPE_ID']) && isset($arFieldsModif['PERSON_TYPE_ID'])
				&& intval($arFieldsOrig['PERSON_TYPE_ID']) !== intval($arFieldsModif['PERSON_TYPE_ID']));
			if ($bPersonTypeChanged)
			{
				$arPersonTypes = CCrmPaySystem::getPersonTypesList();

				if ($bPersonTypeChanged)
				{
					$arMsg[] = Array(
						'ENTITY_FIELD' => 'PERSON_TYPE_ID',
						'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_PERSON_TYPE_ID'),
						'EVENT_TEXT_1' => CrmCompareFieldsList($arPersonTypes, $arFieldsOrig['PERSON_TYPE_ID']),
						'EVENT_TEXT_2' => CrmCompareFieldsList($arPersonTypes, $arFieldsModif['PERSON_TYPE_ID'])
					);
				}
			}
		}

		if (array_key_exists('LOCATION_ID', $arFieldsModif))
		{
			$origLocationId = isset($arFieldsOrig['LOCATION_ID']) ? $arFieldsOrig['LOCATION_ID'] : '';
			$modifLocationId = isset($arFieldsModif['LOCATION_ID']) ? $arFieldsModif['LOCATION_ID'] : '';
			if ($origLocationId != $modifLocationId)
			{
				$origLocationString = $modifLocationString = '';
				if (IsModuleInstalled('sale') && CModule::IncludeModule('sale'))
				{
					$location = new CSaleLocation();
					$origLocationString = ($origLocationId > 0) ? $location->GetLocationString($origLocationId) : '';
					$modifLocationString = ($modifLocationId > 0) ? $location->GetLocationString($modifLocationId) : '';
				}
				if (empty($origLocationString) && intval($origLocationId) > 0)
					$origLocationString = '['.$origLocationId.']';
				if (empty($modifLocationString) && intval($modifLocationId) > 0)
					$modifLocationString = '['.$modifLocationId.']';
				$arMsg[] = Array(
					'ENTITY_FIELD' => 'LOCATION_ID',
					'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_LOCATION_ID'),
					'EVENT_TEXT_1' => $origLocationString,
					'EVENT_TEXT_2' => $modifLocationString,
				);
				unset($origLocationString, $modifLocationString);
			}
			unset($origLocationId, $modifLocationId);
		}

		$origClientFieldValue = $modifClientFieldValue = '';
		foreach (self::$clientFields as $fieldName)
		{
			if (array_key_exists($fieldName, $arFieldsModif))
			{
				$origClientFieldValue = isset($arFieldsOrig[$fieldName]) ? $arFieldsOrig[$fieldName] : '';
				$modifClientFieldValue = isset($arFieldsModif[$fieldName]) ? $arFieldsModif[$fieldName] : '';
				if ($origClientFieldValue != $modifClientFieldValue)
					$arMsg[] = Array(
						'ENTITY_FIELD' => $fieldName,
						'EVENT_NAME' => GetMessage('CRM_QUOTE_FIELD_COMPARE_'.$fieldName.($fieldName === 'MYCOMPANY_ID' ? '1' : '')),
						'EVENT_TEXT_1' => !empty($origClientFieldValue)? $origClientFieldValue: GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
						'EVENT_TEXT_2' => !empty($modifClientFieldValue)? $modifClientFieldValue: GetMessage('CRM_QUOTE_FIELD_COMPARE_EMPTY'),
					);
			}
		}
		unset($fieldName, $origClientFieldValue, $modifClientFieldValue);

		// Processing of the files
		if (array_key_exists('STORAGE_TYPE_ID', $arFieldsModif)
			&& array_key_exists('STORAGE_ELEMENT_IDS', $arFieldsModif) && $arFieldsModif['STORAGE_ELEMENT_IDS'] <> '')
		{
			$newStorageTypeID = isset($arFieldsModif['STORAGE_TYPE_ID']) ? intval($arFieldsModif['STORAGE_TYPE_ID']) : CCrmQuoteStorageType::Undefined;
			$oldStorageTypeID = isset($arFieldsOrig['STORAGE_TYPE_ID']) ? intval($arFieldsOrig['STORAGE_TYPE_ID']) : CCrmQuoteStorageType::Undefined;

			self::PrepareStorageElementIDs($arFieldsModif);
			$newElementIDs = $arFieldsModif['STORAGE_ELEMENT_IDS'];

			self::PrepareStorageElementIDs($arFieldsOrig);
			$oldElementIDs = $arFieldsOrig['STORAGE_ELEMENT_IDS'];

			if($newStorageTypeID === $oldStorageTypeID && is_array($newElementIDs) && is_array($oldElementIDs))
			{
				$arRemovedElementIDs = array_values(array_diff($oldElementIDs, $newElementIDs));
				if(!empty($arRemovedElementIDs))
				{
					foreach($arRemovedElementIDs as $elementID)
					{
						self::PrepareFileEvent($oldStorageTypeID, $elementID, 'REMOVE', $arFieldsModif, $arMsg);
					}
					unset($elementID);
				}
				unset($arRemovedElementIDs);

				$arAddedElementIDs = array_values(array_diff($newElementIDs, $oldElementIDs));
				if(!empty($arAddedElementIDs))
				{
					foreach($arAddedElementIDs as $elementID)
					{
						self::PrepareFileEvent($newStorageTypeID, $elementID, 'ADD', $arFieldsModif, $arMsg);
					}
					unset($elementID);
				}
				unset($arAddedElementIDs);
			}
			else if ($newStorageTypeID !== $oldStorageTypeID && is_array($newElementIDs) && is_array($oldElementIDs))
			{
				foreach($oldElementIDs as $elementID)
				{
					self::PrepareFileEvent($oldStorageTypeID, $elementID, 'REMOVE', $arFieldsModif, $arMsg);
				}
				unset($elementID);

				foreach($newElementIDs as $elementID)
				{
					self::PrepareFileEvent($newStorageTypeID, $elementID, 'ADD', $arFieldsModif, $arMsg);
				}
				unset($elementID);
			}
			unset($newStorageTypeID, $oldStorageTypeID, $newElementIDs, $oldElementIDs);
		}

		return $arMsg;
	}

	// Service -->
	public static function GetFieldCaption($fieldName)
	{
		$result = GetMessage("CRM_QUOTE_FIELD_{$fieldName}");

		if (
			!(is_string($result) && $result !== '')
			&& Crm\Service\ParentFieldManager::isParentFieldName($fieldName)
		)
		{
			$entityTypeId = Crm\Service\ParentFieldManager::getEntityTypeIdFromFieldName($fieldName);
			$result = \CCrmOwnerType::GetDescription($entityTypeId);
		}

		return is_string($result) ? $result : '';
	}
	// Get Fields Metadata
	public static function GetFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'QUOTE_NUMBER' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'TITLE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'STATUS_ID' => array(
					'TYPE' => 'crm_status',
					'CRM_STATUS_TYPE' => 'QUOTE_STATUS'
				),
				'CURRENCY_ID' => array(
					'TYPE' => 'crm_currency'
				),
				'OPPORTUNITY' => array(
					'TYPE' => 'double'
				),
				'TAX_VALUE' => array(
					'TYPE' => 'double'
				),
				'EXCH_RATE' => array(
					'TYPE' => 'double',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'ACCOUNT_CURRENCY_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'OPPORTUNITY_ACCOUNT' => array(
					'TYPE' => 'double',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'TAX_VALUE_ACCOUNT' => array(
					'TYPE' => 'double',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Hidden)
				),
				'COMPANY_ID' => array(
					'TYPE' => 'crm_company'
				),
				'MYCOMPANY_ID' => array(
					'TYPE' => 'crm_company'
				),
				'CONTACT_ID' => array(
					'TYPE' => 'crm_contact',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Deprecated)
				),
				'CONTACT_IDS' => array(
					'TYPE' => 'crm_contact',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
				),
				'BEGINDATE' => array(
					'TYPE' => 'date'
				),
				'CLOSEDATE' => array(
					'TYPE' => 'date'
				),
				'ACTUAL_DATE' => array(
					'TYPE' => 'date'
				),
				'OPENED' => array(
					'TYPE' => 'char'
				),
				'CLOSED' => array(
					'TYPE' => 'char'
				),
				'COMMENTS' => array(
					'TYPE' => 'string',
					'VALUE_TYPE' => 'html',
				),
				'CONTENT' => array(
					'TYPE' => 'string'
				),
				'TERMS' => array(
					'TYPE' => 'string'
				),
				'CLIENT_TITLE' => array(
					'TYPE' => 'string'
				),
				'CLIENT_ADDR' => array(
					'TYPE' => 'string'
				),
				'CLIENT_CONTACT' => array(
					'TYPE' => 'string'
				),
				'CLIENT_EMAIL' => array(
					'TYPE' => 'string'
				),
				'CLIENT_PHONE' => array(
					'TYPE' => 'string'
				),
				'CLIENT_TP_ID' => array(
					'TYPE' => 'string'
				),
				'CLIENT_TPA_ID' => array(
					'TYPE' => 'string'
				),
				'ASSIGNED_BY_ID' => array(
					'TYPE' => 'user'
				),
				'CREATED_BY_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'MODIFY_BY_ID' => array(
					'TYPE' => 'user',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_CREATE' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_MODIFY' => array(
					'TYPE' => 'datetime',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'LEAD_ID' => array(
					'TYPE' => 'crm_lead'
				),
				'DEAL_ID' => array(
					'TYPE' => 'crm_deal'
				),
				'PERSON_TYPE_ID' => array(
					'TYPE' => 'integer'
				),
				'LOCATION_ID' => array(
					'TYPE' => 'location'
				)
			);

			// add utm fields
			self::$FIELD_INFOS = self::$FIELD_INFOS + UtmTable::getUtmFieldsInfo();

			self::$FIELD_INFOS += Crm\Service\Container::getInstance()->getParentFieldManager()->getParentFieldsInfo(\CCrmOwnerType::Quote);
			self::$FIELD_INFOS += self::getLastActivityAdapter()->getFieldsInfo();
		}

		return self::$FIELD_INFOS;
	}

	public static function GetFields($arOptions = null)
	{
		$leadJoin = 'LEFT JOIN b_crm_lead L ON '.self::TABLE_ALIAS.'.LEAD_ID = L.ID';
		$dealJoin = 'LEFT JOIN b_crm_deal D ON '.self::TABLE_ALIAS.'.DEAL_ID = D.ID';
		$companyJoin = 'LEFT JOIN b_crm_company CO ON '.self::TABLE_ALIAS.'.COMPANY_ID = CO.ID';
		$contactJoin = 'LEFT JOIN b_crm_contact C ON '.self::TABLE_ALIAS.'.CONTACT_ID = C.ID';
		$assignedByJoin = 'LEFT JOIN b_user U ON '.self::TABLE_ALIAS.'.ASSIGNED_BY_ID = U.ID';
		$createdByJoin = 'LEFT JOIN b_user U2 ON '.self::TABLE_ALIAS.'.CREATED_BY_ID = U2.ID';
		$modifyByJoin = 'LEFT JOIN b_user U3 ON '.self::TABLE_ALIAS.'.MODIFY_BY_ID = U3.ID';
		$myCompanyJoin = 'LEFT JOIN b_crm_company MC ON '.self::TABLE_ALIAS.'.MYCOMPANY_ID = MC.ID';

		$result = array(
			'ID' => array('FIELD' => self::TABLE_ALIAS.'.ID', 'TYPE' => 'int'),
			'TITLE' => array('FIELD' => self::TABLE_ALIAS.'.TITLE', 'TYPE' => 'string'),
			'STATUS_ID' => array('FIELD' => self::TABLE_ALIAS.'.STATUS_ID', 'TYPE' => 'string'),
			'CURRENCY_ID' => array('FIELD' => self::TABLE_ALIAS.'.CURRENCY_ID', 'TYPE' => 'string'),
			'EXCH_RATE' => array('FIELD' => self::TABLE_ALIAS.'.EXCH_RATE', 'TYPE' => 'double'),
			'OPPORTUNITY' => array('FIELD' => self::TABLE_ALIAS.'.OPPORTUNITY', 'TYPE' => 'double'),
			'TAX_VALUE' => array('FIELD' => self::TABLE_ALIAS.'.TAX_VALUE', 'TYPE' => 'double'),
			'ACCOUNT_CURRENCY_ID' => array('FIELD' => self::TABLE_ALIAS.'.ACCOUNT_CURRENCY_ID', 'TYPE' => 'string'),
			'OPPORTUNITY_ACCOUNT' => array('FIELD' => self::TABLE_ALIAS.'.OPPORTUNITY_ACCOUNT', 'TYPE' => 'double'),
			'TAX_VALUE_ACCOUNT' => array('FIELD' => self::TABLE_ALIAS.'.TAX_VALUE_ACCOUNT', 'TYPE' => 'double'),

			'COMPANY_ID' => array('FIELD' => self::TABLE_ALIAS.'.COMPANY_ID', 'TYPE' => 'int'),
			'COMPANY_TITLE' => array('FIELD' => 'CO.TITLE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_INDUSTRY' => array('FIELD' => 'CO.INDUSTRY', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_EMPLOYEES' => array('FIELD' => 'CO.EMPLOYEES', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_REVENUE' => array('FIELD' => 'CO.REVENUE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_CURRENCY_ID' => array('FIELD' => 'CO.CURRENCY_ID', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_TYPE' => array('FIELD' => 'CO.COMPANY_TYPE', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_ADDRESS' => array('FIELD' => 'CO.ADDRESS', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_ADDRESS_LEGAL' => array('FIELD' => 'CO.ADDRESS_LEGAL', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_BANKING_DETAILS' => array('FIELD' => 'CO.BANKING_DETAILS', 'TYPE' => 'string', 'FROM' => $companyJoin),
			'COMPANY_LOGO' => array('FIELD' => 'CO.LOGO', 'TYPE' => 'string', 'FROM' => $companyJoin),

			'CONTACT_ID' => array('FIELD' => self::TABLE_ALIAS.'.CONTACT_ID', 'TYPE' => 'int'),
			'CONTACT_TYPE_ID' => array('FIELD' => 'C.TYPE_ID', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_HONORIFIC' => array('FIELD' => 'C.HONORIFIC', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_NAME' => array('FIELD' => 'C.NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_SECOND_NAME' => array('FIELD' => 'C.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_LAST_NAME' => array('FIELD' => 'C.LAST_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_FULL_NAME' => array('FIELD' => 'C.FULL_NAME', 'TYPE' => 'string', 'FROM' => $contactJoin),

			'CONTACT_POST' => array('FIELD' => 'C.POST', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_ADDRESS' => array('FIELD' => 'C.ADDRESS', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_SOURCE_ID' => array('FIELD' => 'C.SOURCE_ID', 'TYPE' => 'string', 'FROM' => $contactJoin),
			'CONTACT_PHOTO' => array('FIELD' => 'C.PHOTO', 'TYPE' => 'string', 'FROM' => $contactJoin),

			'MYCOMPANY_ID' => array('FIELD' => self::TABLE_ALIAS.'.MYCOMPANY_ID', 'TYPE' => 'int'),
			'MYCOMPANY_TITLE' => array('FIELD' => 'MC.TITLE', 'TYPE' => 'string', 'FROM' => $myCompanyJoin),

			'BEGINDATE' => array('FIELD' => self::TABLE_ALIAS.'.BEGINDATE', 'TYPE' => 'date'),
			'CLOSEDATE' => array('FIELD' => self::TABLE_ALIAS.'.CLOSEDATE', 'TYPE' => 'date'),
			'ACTUAL_DATE' => array('FIELD' => self::TABLE_ALIAS.'.ACTUAL_DATE', 'TYPE' => 'date'),

			'ASSIGNED_BY_ID' => array('FIELD' => self::TABLE_ALIAS.'.ASSIGNED_BY_ID', 'TYPE' => 'int'),
			'ASSIGNED_BY_LOGIN' => array('FIELD' => 'U.LOGIN', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_NAME' => array('FIELD' => 'U.NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_LAST_NAME' => array('FIELD' => 'U.LAST_NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_SECOND_NAME' => array('FIELD' => 'U.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_WORK_POSITION' => array('FIELD' => 'U.WORK_POSITION', 'TYPE' => 'string', 'FROM' => $assignedByJoin),
			'ASSIGNED_BY_PERSONAL_PHOTO' => array('FIELD' => 'U.PERSONAL_PHOTO', 'TYPE' => 'string', 'FROM' => $assignedByJoin),

			'CREATED_BY_ID' => array('FIELD' => self::TABLE_ALIAS.'.CREATED_BY_ID', 'TYPE' => 'int'),
			'CREATED_BY_LOGIN' => array('FIELD' => 'U2.LOGIN', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_NAME' => array('FIELD' => 'U2.NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_LAST_NAME' => array('FIELD' => 'U2.LAST_NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),
			'CREATED_BY_SECOND_NAME' => array('FIELD' => 'U2.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $createdByJoin),

			'MODIFY_BY_ID' => array('FIELD' => self::TABLE_ALIAS.'.MODIFY_BY_ID', 'TYPE' => 'int'),
			'MODIFY_BY_LOGIN' => array('FIELD' => 'U3.LOGIN', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_NAME' => array('FIELD' => 'U3.NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_LAST_NAME' => array('FIELD' => 'U3.LAST_NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),
			'MODIFY_BY_SECOND_NAME' => array('FIELD' => 'U3.SECOND_NAME', 'TYPE' => 'string', 'FROM' => $modifyByJoin),

			'DATE_CREATE' => array('FIELD' => self::TABLE_ALIAS.'.DATE_CREATE', 'TYPE' => 'datetime'),
			'DATE_MODIFY' => array('FIELD' => self::TABLE_ALIAS.'.DATE_MODIFY', 'TYPE' => 'datetime'),

			'OPENED' => array('FIELD' => self::TABLE_ALIAS.'.OPENED', 'TYPE' => 'char'),
			'CLOSED' => array('FIELD' => self::TABLE_ALIAS.'.CLOSED', 'TYPE' => 'char'),
			'COMMENTS' => array('FIELD' => self::TABLE_ALIAS.'.COMMENTS', 'TYPE' => 'string'),
			'COMMENTS_TYPE' => array('FIELD' => self::TABLE_ALIAS.'.COMMENTS_TYPE', 'TYPE' => 'int'),

			'WEBFORM_ID' => array('FIELD' => self::TABLE_ALIAS.'.WEBFORM_ID', 'TYPE' => 'int'),
			'LEAD_ID' => array('FIELD' => self::TABLE_ALIAS.'.LEAD_ID', 'TYPE' => 'int'),
			'LEAD_TITLE' => array('FIELD' => 'L.TITLE', 'TYPE' => 'string', 'FROM' => $leadJoin),
			'DEAL_ID' => array('FIELD' => self::TABLE_ALIAS.'.DEAL_ID', 'TYPE' => 'int'),
			'DEAL_TITLE' => array('FIELD' => 'D.TITLE', 'TYPE' => 'string', 'FROM' => $dealJoin),
			'QUOTE_NUMBER' => array('FIELD' => self::TABLE_ALIAS.'.QUOTE_NUMBER', 'TYPE' => 'string'),
			'CONTENT' => array('FIELD' => self::TABLE_ALIAS.'.CONTENT', 'TYPE' => 'string'),
			'CONTENT_TYPE' => array('FIELD' => self::TABLE_ALIAS.'.CONTENT_TYPE', 'TYPE' => 'int'),
			'TERMS' => array('FIELD' => self::TABLE_ALIAS.'.TERMS', 'TYPE' => 'string'),
			'TERMS_TYPE' => array('FIELD' => self::TABLE_ALIAS.'.TERMS_TYPE', 'TYPE' => 'int'),
			'STORAGE_TYPE_ID' => array('FIELD' => self::TABLE_ALIAS.'.STORAGE_TYPE_ID', 'TYPE' => 'int'),
			'STORAGE_ELEMENT_IDS' => array('FIELD' => self::TABLE_ALIAS.'.STORAGE_ELEMENT_IDS', 'TYPE' => 'string'),
			'PERSON_TYPE_ID' => array('FIELD' => self::TABLE_ALIAS.'.PERSON_TYPE_ID', 'TYPE' => 'int'),
			'LOCATION_ID' => array('FIELD' => self::TABLE_ALIAS.'.LOCATION_ID', 'TYPE' => 'string'),
			'CLIENT_TITLE' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_TITLE', 'TYPE' => 'string'),
			'CLIENT_ADDR' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_ADDR', 'TYPE' => 'string'),
			'CLIENT_CONTACT' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_CONTACT', 'TYPE' => 'string'),
			'CLIENT_EMAIL' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_EMAIL', 'TYPE' => 'string'),
			'CLIENT_PHONE' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_PHONE', 'TYPE' => 'string'),
			'CLIENT_TP_ID' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_TP_ID', 'TYPE' => 'string'),
			'CLIENT_TPA_ID' => array('FIELD' => self::TABLE_ALIAS.'.CLIENT_TPA_ID', 'TYPE' => 'string')
		);

		// Creation of field aliases
		$result['ASSIGNED_BY'] = $result['ASSIGNED_BY_ID'];
		$result['CREATED_BY'] = $result['CREATED_BY_ID'];
		$result['MODIFY_BY'] = $result['MODIFY_BY_ID'];

		$additionalFields = is_array($arOptions) && isset($arOptions['ADDITIONAL_FIELDS'])
			? $arOptions['ADDITIONAL_FIELDS'] : null;

		if(is_array($additionalFields))
		{
			if(in_array('STATUS_SORT', $additionalFields, true))
			{
				$statusJoin = "LEFT JOIN b_crm_status ST ON ST.ENTITY_ID = 'QUOTE_STATUS' AND ".self::TABLE_ALIAS.".STATUS_ID = ST.STATUS_ID";
				$result['STATUS_SORT'] = array('FIELD' => 'ST.SORT', 'TYPE' => 'int', 'FROM' => $statusJoin);
			}
		}

		// add utm fields
		$result = array_merge($result, UtmTable::getFieldsDescriptionByEntityTypeId(CCrmOwnerType::Quote, self::TABLE_ALIAS));

		$result = array_merge(
			$result,
			Crm\Service\Container::getInstance()->getParentFieldManager()->getParentFieldsSqlInfo(
				CCrmOwnerType::Quote,
				static::TABLE_ALIAS
			)
		);

		$result += self::getLastActivityAdapter()->getFields();

		return $result;
	}

	public static function CheckStorageElementExists($quoteID, $storageTypeID, $elementID)
	{
		global $DB;
		$quoteID = (int)$quoteID;
		$storageTypeID = (int)$storageTypeID;
		$elementID = (int)$elementID;

		$dbResult = $DB->Query(
			'SELECT 1 FROM '.CCrmQuote::ELEMENT_TABLE_NAME.' WHERE QUOTE_ID = '.$quoteID.' AND STORAGE_TYPE_ID = '.$storageTypeID.' AND ELEMENT_ID = '.$elementID,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);
		return is_array($dbResult->Fetch());
	}

	public static function __AfterPrepareSql(/*CCrmEntityListBuilder*/ $sender, $arOrder, $arFilter, $arGroupBy, $arSelectFields)
	{
		$sqlData = array('FROM' => array(), 'WHERE' => array());
		if(isset($arFilter['SEARCH_CONTENT']) && $arFilter['SEARCH_CONTENT'] !== '')
		{
			$tableAlias = $sender->GetTableAlias();
			$queryWhere = new CSQLWhere();
			$queryWhere->SetFields(
				array(
					'SEARCH_CONTENT' => array(
						'FIELD_NAME' => "{$tableAlias}.SEARCH_CONTENT",
						'FIELD_TYPE' => 'string',
						'JOIN' => false
					)
				)
			);
			$options = [];
			if (isset($arFilter['__ENABLE_SEARCH_CONTENT_PHONE_DETECTION']))
			{
				$options['ENABLE_PHONE_DETECTION'] = $arFilter['__ENABLE_SEARCH_CONTENT_PHONE_DETECTION'];
				unset($arFilter['__ENABLE_SEARCH_CONTENT_PHONE_DETECTION']);
			}
			$query = $queryWhere->GetQuery(
				Crm\Search\SearchEnvironment::prepareEntityFilter(
					CCrmOwnerType::Quote,
					array(
						'SEARCH_CONTENT' => Crm\Search\SearchEnvironment::prepareSearchContent($arFilter['SEARCH_CONTENT'], $options)
					)
				)
			);
			if($query !== '')
			{
				$sqlData['WHERE'][] = $query;
			}
		}

		// Applying filter by PRODUCT_ID

		// Applying filter by PRODUCT_ID
		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('PRODUCT_ROW_PRODUCT_ID', $arFilter);
		if(is_array($operationInfo))
		{
			$prodID = (int)$operationInfo['CONDITION'];
			if($prodID > 0 && $operationInfo['OPERATION'] === '=')
			{
				$tableAlias = $sender->GetTableAlias();
				$sqlData['WHERE'][] = "{$tableAlias}.ID IN (SELECT DP.OWNER_ID from b_crm_product_row DP where DP.OWNER_TYPE = 'Q' and DP.OWNER_ID = {$tableAlias}.ID and DP.PRODUCT_ID = {$prodID})";
			}
		}

		$operationInfo = Crm\UI\Filter\EntityHandler::findFieldOperation('ASSOCIATED_CONTACT_ID', $arFilter);
		if(is_array($operationInfo))
		{
			if($operationInfo['OPERATION'] === '=')
			{
				$sqlData['FROM'][] = QuoteContactTable::prepareFilterJoinSql(
					CCrmOwnerType::Contact,
					$operationInfo['CONDITION'],
					$sender->GetTableAlias()
				);
			}
		}

		Tracking\UI\Filter::buildFilterAfterPrepareSql(
			$sqlData,
			$arFilter,
			\CCrmOwnerType::Quote,
			$sender->GetTableAlias()
		);

		$result = array();
		if(!empty($sqlData['FROM']))
		{
			$result['FROM'] = implode(' ', $sqlData['FROM']);
		}
		if(!empty($sqlData['WHERE']))
		{
			$result['WHERE'] = implode(' AND ', $sqlData['WHERE']);
		}

		return !empty($result) ? $result : false;
	}
	// <-- Service

	public static function GetUserFieldEntityID()
	{
		return self::$sUFEntityID;
	}

	public static function GetByID($ID, $bCheckPerms = true)
	{
		$arFilter = array('=ID' => intval($ID));
		if (!$bCheckPerms)
		{
			$arFilter['CHECK_PERMISSIONS'] = 'N';
		}

		$dbRes = CCrmQuote::GetList(array(), $arFilter);
		return $dbRes->Fetch();
	}

	public static function Exists($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			return false;
		}

		$dbRes = self::GetList(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID')
		);

		return is_array($dbRes->Fetch());
	}

	public static function GetTopIDs($top, $sortType = 'ASC', $userPermissions = null)
	{
		$top = (int) $top;
		if ($top <= 0)
		{
			return [];
		}

		$sortType = mb_strtoupper($sortType) !== 'DESC' ? 'ASC' : 'DESC';

		$permissionSql = '';
		if (!CCrmPerms::IsAdmin())
		{
			if (!$userPermissions)
			{
				$userPermissions = CCrmPerms::GetCurrentUserPermissions();
			}

			$permissionSql = self::BuildPermSql('L', 'READ', ['PERMS' => $userPermissions]);
		}

		if ($permissionSql === false)
		{
			return [];
		}

		$query = new Bitrix\Main\Entity\Query(Crm\QuoteTable::getEntity());
		$query->addSelect('ID');
		$query->addOrder('ID', $sortType);
		$query->setLimit($top);

		if ($permissionSql !== '')
		{
			$permissionSql = mb_substr($permissionSql, 7);
			$query->where('ID', 'in', new Bitrix\Main\DB\SqlExpression($permissionSql));
		}

		$rs = $query->exec();
		$results = [];
		while ($field = $rs->fetch())
		{
			$results[] = (int) $field['ID'];
		}
		return $results;
	}

	public static function GetTotalCount()
	{
		if(defined('BX_COMP_MANAGED_CACHE') && $GLOBALS['CACHE_MANAGER']->Read(self::CACHE_TTL, self::TOTAL_COUNT_CACHE_ID, 'b_crm_quote'))
		{
			return $GLOBALS['CACHE_MANAGER']->Get(self::TOTAL_COUNT_CACHE_ID);
		}

		$result = (int)self::GetList(
			array(),
			array('CHECK_PERMISSIONS' => 'N'),
			array(),
			false,
			array(),
			array('ENABLE_ROW_COUNT_THRESHOLD' => false)
		);

		if(defined('BX_COMP_MANAGED_CACHE'))
		{
			$GLOBALS['CACHE_MANAGER']->Set(self::TOTAL_COUNT_CACHE_ID, $result);
		}
		return $result;
	}

	// GetList with navigation support
	public static function GetList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array(), $arOptions = array())
	{
		if(!is_array($arOptions))
		{
			$arOptions = array();
		}

		if(!isset($arOptions['PERMISSION_SQL_TYPE']))
		{
			$arOptions['PERMISSION_SQL_TYPE'] = 'FROM';
			$arOptions['PERMISSION_SQL_UNION'] = 'DISTINCT';
		}

		$lb = new CCrmEntityListBuilder(
			CCrmQuote::DB_TYPE,
			CCrmQuote::TABLE_NAME,
			self::TABLE_ALIAS,
			self::GetFields(isset($arOptions['FIELD_OPTIONS']) ? $arOptions['FIELD_OPTIONS'] : null),
			self::$sUFEntityID,
			'QUOTE',
			array('CCrmQuote', 'BuildPermSql'),
			array('CCrmQuote', '__AfterPrepareSql')
		);

		return $lb->Prepare($arOrder, $arFilter, $arGroupBy, $arNavStartParams, $arSelectFields, $arOptions);
	}

	static public function BuildPermSql($sAliasPrefix = self::TABLE_ALIAS, $mPermType = 'READ', $arOptions = [])
	{
		$userId = null;
		if (isset($arOptions['PERMS']) && is_object($arOptions['PERMS']))
		{
			/** @var \CCrmPerms $arOptions['PERMS'] */
			$userId = $arOptions['PERMS']->GetUserID();
		}
		$builderOptions =
			Crm\Security\QueryBuilder\Options::createFromArray((array)$arOptions)
				->setOperations((array)$mPermType)
				->setAliasPrefix((string)$sAliasPrefix)
		;

		$queryBuilder = Crm\Service\Container::getInstance()
			->getUserPermissions($userId)
			->createListQueryBuilder(self::$TYPE_NAME, $builderOptions)
		;

		return $queryBuilder->buildCompatible();
	}

	public static function LocalComponentCausedUpdater()
	{
		global $stackCacheManager;

		$bResult = true;
		$errMsg = array();
		$bError = false;

		// at first, check last update version
		if (COption::GetOptionString('crm', '~CRM_QUOTE_14_1_11', 'N') === 'Y')
			return $bResult;

		try
		{
			// Copy perms from deals to quotes
			$CCrmRole = new CCrmRole();
			$dbRoles = $CCrmRole->GetList();

			while($arRole = $dbRoles->Fetch())
			{
				$arPerms = $CCrmRole->GetRolePerms($arRole['ID']);

				if(!isset($arPerms['QUOTE']) && is_array($arPerms['DEAL']))
				{
					foreach ($arPerms['DEAL'] as $key => $value)
					{
						if(isset($value['-']))
							$arPerms['QUOTE'][$key]['-'] = $value['-'];
						else
							$arPerms['QUOTE'][$key]['-'] = null;
					}
				}

				$arFields = array('RELATION' => $arPerms);
				$CCrmRole->Update($arRole['ID'], $arFields);
			}

			// Create default quote status list (if not exists)
			$arStatus = CCrmStatus::GetStatus('QUOTE_STATUS');
			if (empty($arStatus))
			{
				CCrmStatus::InstallDefault('QUOTE_STATUS');
			}
			unset($arStatus);
		}
		catch (Exception $e)
		{
			$errMsg[] = $e->getMessage();
			$bError = true;
		}

		if (!$bError)
		{
			COption::SetOptionString('crm', '~CRM_QUOTE_14_1_11', 'Y');
		}
		else
		{
			$errString = implode('<br>', $errMsg);
			ShowError($errString);
			$bResult = false;
		}

		return $bResult;
	}

	public static function LoadProductRows($ID)
	{
		return CCrmProductRow::LoadRows(self::OWNER_TYPE, $ID);
	}

	public static function SaveProductRows($ID, $arRows, $checkPerms = true, $regEvent = true, $syncOwner = true)
	{
		$result = CCrmProductRow::SaveRows(self::OWNER_TYPE, $ID, $arRows, null, $checkPerms, $regEvent, $syncOwner);
		if($result)
		{
			$events = GetModuleEvents('crm', 'OnAfterCrmQuoteProductRowsSave');
			while ($event = $events->Fetch())
				ExecuteModuleEventEx($event, array($ID, $arRows));
		}
		return $result;
	}

	public static function SynchronizeProductRows($ID, $checkPerms = true)
	{

		$arTotalInfo = CCrmProductRow::CalculateTotalInfo(CCrmQuote::OWNER_TYPE, $ID, $checkPerms);

		if (is_array($arTotalInfo))
		{
			$arFields = array(
				'OPPORTUNITY' => isset($arTotalInfo['OPPORTUNITY']) ? $arTotalInfo['OPPORTUNITY'] : 0.0,
				'TAX_VALUE' => isset($arTotalInfo['TAX_VALUE']) ? $arTotalInfo['TAX_VALUE'] : 0.0
			);

			$entity = new CCrmQuote($checkPerms);
			$entity->Update($ID, $arFields);
		}
	}

	public static function LoadElementIDs($ID)
	{
		$ID = (int)$ID;
		if($ID <= 0)
		{
			return array();
		}

		global $DB;
		$result = array();
		$table = CCrmQuote::ELEMENT_TABLE_NAME;
		$dbResult = $DB->Query("SELECT ELEMENT_ID FROM {$table} WHERE QUOTE_ID = {$ID}", false, 'File: '.__FILE__.'<br/>Line: '.__LINE__);
		while($arResult = $dbResult->Fetch())
		{
			$elementID = isset($arResult['ELEMENT_ID']) ? (int)$arResult['ELEMENT_ID'] : 0;
			if($elementID > 0)
			{
				$result[] = $elementID;
			}
		}
		return $result;
	}

	public static function IsAccessEnabled(CCrmPerms $userPermissions = null)
	{
		return self::CheckReadPermission(0, $userPermissions);
	}

	public static function CheckStatusPermission($statusID, $permissionTypeID, CCrmPerms $userPermissions = null)
	{
		if($userPermissions === null)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		$permissionName = \Bitrix\Crm\Security\EntityPermissionType::resolveName($permissionTypeID);
		$entityAttrs = array("STATUS_ID{$statusID}");
		return $permissionName !== '' &&
			($userPermissions->GetPermType(self::$TYPE_NAME, $permissionName, $entityAttrs) > BX_CRM_PERM_NONE);
	}

	public static function CheckCreatePermission($userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckCreatePermission(self::$TYPE_NAME, $userPermissions);
	}

	public static function CheckUpdatePermission($ID, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckUpdatePermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function CheckDeletePermission($ID, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckDeletePermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function CheckReadPermission($ID = 0, $userPermissions = null)
	{
		return CCrmAuthorizationHelper::CheckReadPermission(self::$TYPE_NAME, $ID, $userPermissions);
	}

	public static function CheckConvertPermission($ID = 0, $entityTypeID = 0, $userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		if($entityTypeID === CCrmOwnerType::Deal)
		{
			return CCrmDeal::CheckCreatePermission($userPermissions);
		}
		elseif($entityTypeID === CCrmOwnerType::Invoice)
		{
			return CCrmInvoice::CheckCreatePermission($userPermissions);
		}

		return (CCrmDeal::CheckCreatePermission($userPermissions)
			|| CCrmInvoice::CheckCreatePermission($userPermissions));
	}

	public static function PrepareConversionPermissionFlags($ID, array &$params, $userPermissions = null)
	{
		if(!$userPermissions)
		{
			$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		}

		$canCreateDeal = CCrmDeal::CheckCreatePermission($userPermissions);
		$canCreateInvoice = IsModuleInstalled('sale') && CCrmInvoice::CheckCreatePermission($userPermissions);

		$params['CAN_CONVERT_TO_DEAL'] = $canCreateDeal;
		$params['CAN_CONVERT_TO_INVOICE'] = $canCreateInvoice;
		$params['CAN_CONVERT'] = $params['CONVERT'] = ($canCreateInvoice || $canCreateDeal);

		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getConversionRestriction();
		if($restriction->hasPermission())
		{
			$params['CONVERSION_PERMITTED'] = true;
		}
		else
		{
			$params['CONVERSION_PERMITTED'] = false;
			$params['CONVERSION_LOCK_SCRIPT'] = $restriction->prepareInfoHelperScript();
		}
	}

	public static function GetFinalStatusSort()
	{
		return self::GetStatusSort('APPROVED');
	}

	public static function GetStatusSort($statusID)
	{
		$statusID = strval($statusID);

		if($statusID === '')
		{
			return -1;
		}

		$statuses = self::GetStatuses();
		$info = isset($statuses[$statusID]) ? $statuses[$statusID] : null;
		return is_array($info) && isset($info['SORT']) ? intval($info['SORT']) : -1;
	}

	public static function GetStatusSemantics($statusID)
	{
		if($statusID === 'APPROVED')
		{
			return 'success';
		}

		if($statusID === 'DECLAINED')
		{
			return 'failure';
		}

		return (self::GetStatusSort($statusID) > self::GetFinalStatusSort()) ? 'apology' : 'process';
	}

	public static function GetSemanticID($statusID)
	{
		if($statusID === 'APPROVED')
		{
			return Bitrix\Crm\PhaseSemantics::SUCCESS;
		}

		if($statusID === 'DECLAINED')
		{
			return Bitrix\Crm\PhaseSemantics::FAILURE;
		}

		return (self::GetStatusSort($statusID) > self::GetFinalStatusSort())
			? Bitrix\Crm\PhaseSemantics::FAILURE : Bitrix\Crm\PhaseSemantics::PROCESS;
	}

	public static function GetStatuses()
	{
		if(!self::$QUOTE_STATUSES)
		{
			self::$QUOTE_STATUSES = CCrmStatus::GetStatus('QUOTE_STATUS');
		}

		return self::$QUOTE_STATUSES;
	}

	public static function PullChange($type, $arParams)
	{
		if(!CModule::IncludeModule('pull'))
		{
			return;
		}

		$type = strval($type);
		if($type === '')
		{
			$type = 'update';
		}
		else
		{
			$type = mb_strtolower($type);
		}

		CPullWatch::AddToStack(
			'CRM_QUOTE_CHANGE',
			array(
				'module_id'  => 'crm',
				'command'    => "crm_quote_{$type}",
				'params'     => $arParams
			)
		);
	}

	public static function GetCount($arFilter)
	{
		$fields = self::GetFields();
		return CSqlUtil::GetCount(CCrmQuote::TABLE_NAME, self::TABLE_ALIAS, $fields, $arFilter);
	}

	public static function GetClientFields()
	{
		return self::$clientFields;
	}

	public static function ResolvePersonType(array $arFields, array $types = null)
	{
		if($types === null)
		{
			$types = CCrmPaySystem::getPersonTypeIDs();
		}

		if(!(isset($types['COMPANY']) && isset($types['CONTACT'])))
		{
			return 0;
		}

		$companyId = isset($arFields['COMPANY_ID']) ? (int)$arFields['COMPANY_ID'] : 0;
		if($companyId > 0)
		{
			return $types['COMPANY'];
		}

		$contactBindings = isset($arFields['CONTACT_BINDINGS']) && is_array($arFields['CONTACT_BINDINGS'])
			? $arFields['CONTACT_BINDINGS'] : array();
		$contactId = \Bitrix\Crm\Binding\EntityBinding::getPrimaryEntityID(CCrmOwnerType::Contact, $contactBindings);
		if($contactId === 0 && isset($arFields['CONTACT_ID']))
		{
			$contactId = (int)$arFields['CONTACT_ID'];
		}
		return $contactId > 0 ? $types['CONTACT'] : 0;
	}

	public static function RewriteClientFields(&$arFields, $bDualFields = true)
	{
		$arCompany = $companyEMail = $companyPhone = null;
		$arContact = $contactEMail = $contactPhone = null;

		$companyId = isset($arFields['COMPANY_ID']) ? (int)$arFields['COMPANY_ID'] : 0;
		$contactBindings = isset($arFields['CONTACT_BINDINGS']) && is_array($arFields['CONTACT_BINDINGS'])
			? $arFields['CONTACT_BINDINGS'] : array();
		$contactId = \Bitrix\Crm\Binding\EntityBinding::getPrimaryEntityID(CCrmOwnerType::Contact, $contactBindings);
		if($contactId === 0 && isset($arFields['CONTACT_ID']))
		{
			$contactId = (int)$arFields['CONTACT_ID'];
		}

		foreach(self::GetClientFields() as $fieldName)
		{
			$arFields[$fieldName] = '';
			if ($bDualFields)
				$arFields["~$fieldName"] = '';
		}

		if ($companyId > 0)
		{
			$arCompany = CCrmCompany::GetByID($companyId);

			// Get multifields values (EMAIL and PHONE)
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('COMPANY', $companyId, 'EMAIL', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$companyEMail = $arFieldsMulti[0]['VALUE'];
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('COMPANY', $companyId, 'PHONE', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$companyPhone = $arFieldsMulti[0]['VALUE'];
			unset($arFieldsMulti);
		}

		if ($contactId > 0)
		{
			$arContact = CCrmContact::GetByID($contactId);

			// Get multifields values (EMAIL and PHONE)
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('CONTACT', $contactId, 'EMAIL', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$contactEMail = $arFieldsMulti[0]['VALUE'];
			$arFieldsMulti = CCrmFieldMulti::GetEntityFields('CONTACT', $contactId, 'PHONE', true, false);
			if (is_array($arFieldsMulti) && isset($arFieldsMulti[0]['VALUE']))
				$contactPhone = $arFieldsMulti[0]['VALUE'];
			unset($arFieldsMulti);
		}

		if ($companyId > 0)
		{
			if (is_array($arCompany) && count($arCompany) >0)
			{
				foreach (self::$clientFields as $k)
				{
					$v = '';
					if ($k === 'CLIENT_TITLE')
					{
						if (isset($arCompany['TITLE']))
							$v = $arCompany['TITLE'];
					}
					elseif ($k === 'CLIENT_CONTACT' && $contactId > 0)
					{
						if (isset($arContact['FULL_NAME']))
							$v = $arContact['FULL_NAME'];
					}
					elseif ($k === 'CLIENT_ADDR')
					{
						$v = AddressFormatter::getSingleInstance()->formatTextComma(
							CompanyAddress::mapEntityFields(
								$arCompany,
								['TYPE_ID' => EntityAddressType::Registered]
							)
						);
					}
					elseif ($k === 'CLIENT_EMAIL')
					{
						$v = ($contactEMail != '') ? $contactEMail : $companyEMail;
					}
					elseif ($k === 'CLIENT_PHONE')
					{
						$v = ($contactPhone != '') ? $contactPhone : $companyPhone;
					}
					if ($bDualFields)
						$arFields['~'.$k] = $v;
					$arFields[$k] = $bDualFields ? htmlspecialcharsbx($v) : $v;
				}
			}
		}
		elseif ($contactId > 0)
		{
			if (is_array($arContact) && count($arContact) >0)
			{
				foreach (self::$clientFields as $k)
				{
					$v = '';
					if ($k === 'CLIENT_TITLE')
					{
						if (isset($arContact['FULL_NAME']))
							$v = $arContact['FULL_NAME'];
					}
					elseif ($k === 'CLIENT_CONTACT' && $contactId > 0)
					{
						$v = '';
					}
					elseif ($k === 'CLIENT_ADDR')
					{
						$v = AddressFormatter::getSingleInstance()->formatTextComma(
							ContactAddress::mapEntityFields($arContact)
						);
					}
					elseif ($k === 'CLIENT_EMAIL')
					{
						$v = $contactEMail;
					}
					elseif ($k === 'CLIENT_PHONE')
					{
						$v = $contactPhone;
					}
					if ($bDualFields)
						$arFields['~'.$k] = $v;
					$arFields[$k] = $bDualFields ? htmlspecialcharsbx($v) : $v;
				}
			}
		}
	}

	public static function rewriteClientFieldsFromRequisite(&$fields, $requisiteId, $dualFields = true)
	{
		$companyId = isset($fields['COMPANY_ID']) ? intval($fields['COMPANY_ID']) : 0;
		$contactId = isset($fields['CONTACT_ID']) ? intval($fields['CONTACT_ID']) : 0;

		$personTypeContact = 1;
		$personTypeCompany = 2;

		$personTypeId = 0;
		if ($companyId > 0)
			$personTypeId = $personTypeCompany;
		else if ($contactId > 0)
			$personTypeId = $personTypeContact;

		$requisiteId = (int)$requisiteId;

		if ($requisiteId > 0 && ($personTypeId == $personTypeContact || $personTypeId == $personTypeCompany))
		{
			// requisite values
			$requisiteValues = array();
			$requisite = new \Bitrix\Crm\EntityRequisite();
			$preset = new \Bitrix\Crm\EntityPreset();
			$row = $requisite->getById($requisiteId);
			if (is_array($row))
			{
				if (isset($row['PRESET_ID']) && $row['PRESET_ID'] > 0)
				{
					$presetFields = array();
					$res = $preset->getList(array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => \Bitrix\Crm\EntityPreset::Requisite,
							'=ID' => (int)$row['PRESET_ID']
						),
						'select' => array('ID', 'COUNTRY_ID', 'SETTINGS'),
						'limit' => 1
					));
					if ($presetData = $res->fetch())
					{
						if (is_array($presetData['SETTINGS']))
						{
							$presetFieldsInfo = $preset->settingsGetFields($presetData['SETTINGS']);
							foreach ($presetFieldsInfo as $fieldInfo)
							{
								if (isset($fieldInfo['FIELD_NAME']))
									$presetFields[$fieldInfo['FIELD_NAME']] = true;
							}
							unset($presetFieldsInfo, $fieldInfo);
						}
					}
					unset($res, $presetData);

					foreach ($row as $fieldName => $fieldValue)
					{
						if (isset($presetFields[$fieldName]))
						{
							$requisiteValues[$fieldName] = $fieldValue;
						}
					}
					unset($fieldName, $fieldValue, $valueKey);

					// addresses
					foreach ($requisite->getAddresses($requisiteId) as $addrTypeId => $addrFields)
					{
						$valueKey = Bitrix\Crm\EntityRequisite::ADDRESS.'_'.$addrTypeId;
						$requisiteValues[$valueKey] =
							AddressFormatter::getSingleInstance()->formatTextComma($addrFields);
					}
				}
			}

			// full name
			$fullName = isset($requisiteValues['RQ_NAME']) ?
				trim(strval($requisiteValues['RQ_NAME'])) : '';
			if (empty($fullName))
			{
				$firstName = isset($requisiteValues['RQ_FIRST_NAME'])
					? trim(strval($requisiteValues['RQ_FIRST_NAME'])) : '';
				$lastName = isset($requisiteValues['RQ_LAST_NAME']) ?
					trim(strval($requisiteValues['RQ_LAST_NAME'])) : '';
				$secondName = isset($requisiteValues['RQ_SECOND_NAME']) ?
					trim(strval($requisiteValues['RQ_SECOND_NAME'])) : '';
				if (!empty($firstName) || !empty($lastName) || !empty($secondName))
				{
					$fullName = CUser::FormatName(
						\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
						array(
							'LOGIN' => '[]',
							'NAME' => $firstName,
							'LAST_NAME' => $lastName,
							'SECOND_NAME' => $secondName
						),
						true, false
					);
					if (!empty($fullName) && $fullName !== '[]')
					{
						$requisiteValues['RQ_NAME'] = $fullName;
					}
				}
			}

			$requisiteToClientFieldsMap = array(
				$personTypeCompany => array(
					'RQ_COMPANY_NAME' => 'CLIENT_TITLE',
					'RQ_ADDR_'.EntityAddressType::Registered => 'CLIENT_ADDR',
					'RQ_INN' => 'CLIENT_TP_ID',
					'RQ_KPP' => 'CLIENT_TPA_ID',
					'RQ_CONTACT' => 'CLIENT_CONTACT',
					'RQ_EMAIL' => 'CLIENT_EMAIL',
					'RQ_PHONE' => 'CLIENT_PHONE'
				),
				$personTypeContact => array(
					'RQ_NAME' => 'CLIENT_TITLE',
					'RQ_EMAIL' => 'CLIENT_EMAIL',
					'RQ_PHONE' => 'CLIENT_PHONE',
					'RQ_ADDR_'.EntityAddressType::Primary => 'CLIENT_ADDR'
				),
			);

			if (is_array($requisiteValues) && !empty($requisiteValues))
			{
				foreach ($requisiteToClientFieldsMap[$personTypeId] as $rqIndex => $clientFields)
				{
					if (isset($requisiteValues[$rqIndex])
						&& !empty($requisiteValues[$rqIndex])
					)
					{
						if (!is_array($clientFields))
							$clientFields = array($clientFields);
						foreach ($clientFields as $clientFieldName)
						{
							if ($dualFields)
							{
								$fields['~'.$clientFieldName] = $requisiteValues[$rqIndex];
								$fields[$clientFieldName] = htmlspecialcharsbx($requisiteValues[$rqIndex]);
							}
							else
							{
								$fields[$clientFieldName] = $requisiteValues[$rqIndex];
							}
						}
					}
				}
			}
		}
	}

	public static function MakeClientInfoString($arQuote, $bDualFields = true)
	{
		$strClientInfo = '';

		$i = 0;
		foreach (self::$clientFields as $k)
		{
			$index = $bDualFields === true ? '~'.$k : $k;
			if (isset($arQuote[$index]) && trim($arQuote[$index]) <> '')
				$strClientInfo .= (($i++ > 0) ? ', ' : '').$arQuote[$index];
		}

		return $strClientInfo;
	}

	public static function BuildSearchCard($arQuote, $bReindex = false)
	{
		$arStatuses = array();
		$arSite = array();
		$sEntityType = 'QUOTE';
		$sTitle = 'TITLE';
		$sNumber = 'QUOTE_NUMBER';
		$arSearchableFields = array(
			'DATE_CREATE' => GetMessage('CRM_QUOTE_SEARCH_FIELD_DATE_CREATE'),
			'STATUS_ID' => GetMessage('CRM_QUOTE_SEARCH_FIELD_STATUS_ID'),
			'BEGINDATE' => GetMessage('CRM_QUOTE_SEARCH_FIELD_BEGINDATE'),
			'CLOSEDATE' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLOSEDATE'),
			'OPPORTUNITY' => GetMessage('CRM_QUOTE_SEARCH_FIELD_OPPORTUNITY'),
			'COMMENTS' => GetMessage('CRM_QUOTE_SEARCH_FIELD_COMMENTS'),
			'CLIENT_TITLE' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLIENT_TITLE'),
			'CLIENT_ADDR' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLIENT_ADDR'),
			'CLIENT_CONTACT' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLIENT_CONTACT'),
			'CLIENT_EMAIL' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLIENT_EMAIL'),
			'CLIENT_PHONE' => GetMessage('CRM_QUOTE_SEARCH_FIELD_CLIENT_PHONE'),
			'CLIENT_TP_ID' => GetMessage('CRM_QUOTE_SEARCH_FIELD_TP_ID'),
			'CLIENT_TPA_ID' => GetMessage('CRM_QUOTE_SEARCH_FIELD_TPA_ID')
		);

		$sBody = $arQuote[$sNumber].', '.$arQuote[$sTitle]."\n";
		$arField2status = array(
			'STATUS_ID' => 'QUOTE_STATUS'
		);
		$site = new CSite();

		foreach (array_keys($arSearchableFields) as $k)
		{
			if (!isset($arQuote[$k]))
				continue;

			$v = $arQuote[$k];

			if($k === 'COMMENTS')
			{
				$v = CSearch::KillTags($v);
			}

			$v = trim($v);

			if ($k === 'DATE_CREATE' || $k === 'BEGINDATE' || $k === 'CLOSEDATE')
			{
				$dateFormatShort = $site->GetDateFormat('SHORT');
				if (!CheckDateTime($v, $dateFormatShort))
				{
					$v = ConvertTimeStamp(strtotime($v), 'SHORT');
				}
				if (CheckDateTime($v, $dateFormatShort))
				{
					$v = FormatDate('SHORT', MakeTimeStamp($v, $dateFormatShort));
				}
				else
				{
					$v = null;
				}
			}

			if (isset($arField2status[$k]))
			{
				if (!isset($arStatuses[$k]))
					$arStatuses[$k] = CCrmStatus::GetStatusList($arField2status[$k]);
				$v = $arStatuses[$k][$v];
			}

			if ($k === 'OPPORTUNITY')
				$v = number_format(doubleval($v), 2, '.', '');
			if (!empty($v) && (!is_numeric($v) || $k === 'OPPORTUNITY') && $v != 'N' && $v != 'Y')
				$sBody .= $arSearchableFields[$k].": $v\n";
		}

		if ((isset($arQuote['ASSIGNED_BY_NAME']) && !empty($arQuote['ASSIGNED_BY_NAME']))
			|| (isset($arQuote['ASSIGNED_BY_LAST_NAME']) && !empty($arQuote['ASSIGNED_BY_LAST_NAME']))
			|| (isset($arQuote['ASSIGNED_BY_SECOND_NAME']) && !empty($arQuote['ASSIGNED_BY_SECOND_NAME'])))
		{
			$responsibleInfo = CUser::FormatName(
				$site->GetNameFormat(null, $arQuote['LID'] ?? null),
				array(
					'LOGIN' => '',
					'NAME' => isset($arQuote['ASSIGNED_BY_NAME']) ? $arQuote['ASSIGNED_BY_NAME'] : '',
					'LAST_NAME' => isset($arQuote['ASSIGNED_BY_LAST_NAME']) ? $arQuote['ASSIGNED_BY_LAST_NAME'] : '',
					'SECOND_NAME' => isset($arQuote['ASSIGNED_BY_SECOND_NAME']) ? $arQuote['ASSIGNED_BY_SECOND_NAME'] : ''
				),
				false, false
			);
			if (isset($arQuote['ASSIGNED_BY_EMAIL']) && !empty($arQuote['ASSIGNED_BY_EMAIL']))
				$responsibleInfo .= ', '.$arQuote['ASSIGNED_BY_EMAIL'];
			if (isset($arQuote['ASSIGNED_BY_WORK_POSITION']) && !empty($arQuote['ASSIGNED_BY_WORK_POSITION']))
				$responsibleInfo .= ', '.$arQuote['ASSIGNED_BY_WORK_POSITION'];
			if (!empty($responsibleInfo) && !is_numeric($responsibleInfo) && $responsibleInfo != 'N' && $responsibleInfo != 'Y')
				$sBody .= GetMessage('CRM_QUOTE_SEARCH_FIELD_ASSIGNED_BY_INFO').": $responsibleInfo\n";
		}

		$sDetailURL = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_'.mb_strtolower($sEntityType).'_show'),
			array(
				mb_strtolower($sEntityType).'_id' => $arQuote['ID']
			)
		);

		$_arAttr = \Bitrix\Crm\Security\Manager::resolveController($sEntityType)
			->getPermissionAttributes($sEntityType, [$arQuote['ID']])
		;

		if (empty($arSite))
		{
			$rsSite = $site->GetList();
			while ($_arSite = $rsSite->Fetch())
				$arSite[] = $_arSite['ID'];
		}
		unset($site);

		$sattr_d = '';
		$sattr_s = '';
		$sattr_u = '';
		$sattr_o = '';
		$sattr2 = '';
		$arAttr = array();
		if (!isset($_arAttr[$arQuote['ID']]))
			$_arAttr[$arQuote['ID']] = array();

		$arAttr[] = $sEntityType; // for perm X
		foreach ($_arAttr[$arQuote['ID']] as $_s)
		{
			if ($_s[0] == 'U')
				$sattr_u = $_s;
			else if ($_s[0] == 'D')
				$sattr_d = $_s;
			else if ($_s[0] == 'S')
				$sattr_s = $_s;
			else if ($_s[0] == 'O')
				$sattr_o = $_s;
			$arAttr[] = $sEntityType.'_'.$_s;
		}
		$sattr = $sEntityType.'_'.$sattr_u;
		if (!empty($sattr_d))
		{
			$sattr .= '_'.$sattr_d;
			$arAttr[] = $sattr;
		}
		if (!empty($sattr_s))
		{
			$sattr2 = $sattr.'_'.$sattr_s;
			$arAttr[] = $sattr2;
			$arAttr[] = $sEntityType.'_'.$sattr_s;  // for perm X in status
		}
		if (!empty($sattr_o))
		{
			$sattr  .= '_'.$sattr_o;
			$sattr3 = $sattr2.'_'.$sattr_o;
			$arAttr[] = $sattr3;
			$arAttr[] = $sattr;
		}

		$arSitePath = array();
		foreach ($arSite as $sSite)
			$arSitePath[$sSite] = $sDetailURL;

		$arResult = Array(
			'LAST_MODIFIED' => $arQuote['DATE_MODIFY'],
			'DATE_FROM' => $arQuote['DATE_CREATE'],
			'TITLE' => GetMessage('CRM_'.$sEntityType).': '.$arQuote[$sNumber].', '.$arQuote[$sTitle],
			'PARAM1' => $sEntityType,
			'PARAM2' => $arQuote['ID'],
			'SITE_ID' => $arSitePath,
			'PERMISSIONS' => $arAttr,
			'BODY' => $sBody,
			'TAGS' => 'crm,'.mb_strtolower($sEntityType).','.GetMessage('CRM_'.$sEntityType)
		);

		if ($bReindex)
			$arResult['ID'] = $sEntityType.'.'.$arQuote['ID'];

		return $arResult;
	}

	public static function GetDefaultStorageTypeID()
	{
		if(self::$STORAGE_TYPE_ID === StorageType::Undefined)
		{
			self::$STORAGE_TYPE_ID = intval(CUserOptions::GetOption('crm', 'quote_storage_type_id', StorageType::Undefined));
			if(self::$STORAGE_TYPE_ID === StorageType::Undefined
				|| !StorageType::isDefined(self::$STORAGE_TYPE_ID))
			{
				self::$STORAGE_TYPE_ID = StorageType::getDefaultTypeID();
			}
		}

		return self::$STORAGE_TYPE_ID;
	}

	public static function PrepareStorageElementIDs(&$arFields)
	{
		if(isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS']))
		{
			return;
		}

		if(isset($arFields['~STORAGE_ELEMENT_IDS']))
		{
			$field = $arFields['~STORAGE_ELEMENT_IDS'];
		}
		elseif(isset($arFields['STORAGE_ELEMENT_IDS']))
		{
			$field = $arFields['STORAGE_ELEMENT_IDS'];
		}
		else
		{
			$field = '';
		}

		if(is_array($field))
		{
			$result = $field;
		}
		elseif(is_numeric($field))
		{
			$ID = (int)$field;
			if($ID <= 0)
			{
				$ID = isset($arFields['ID']) ? (int)$arFields['ID'] : (isset($arFields['~ID']) ? (int)$arFields['~ID'] : 0);
			}

			if($ID <= 0)
			{
				$result = array();
			}
			else
			{
				$result = self::LoadElementIDs($ID);
				$arUpdateFields = array('STORAGE_ELEMENT_IDS' => serialize($result));
				$table = CCrmActivity::TABLE_NAME;
				global $DB;
				$DB->QueryBind(
					'UPDATE '.$table.' SET '.$DB->PrepareUpdate($table, $arUpdateFields).' WHERE ID = '.$ID,
					$arUpdateFields,
					false
				);
			}
		}
		elseif(is_string($field) && $field !== '')
		{
			$result = unserialize($field, ['allowed_classes' => false]);
		}
		else
		{
			$result = array();
		}

		$arFields['~STORAGE_ELEMENT_IDS'] = $arFields['STORAGE_ELEMENT_IDS'] = &$result;
		unset($result);
	}

	protected static function NormalizeStorageElementIDs(&$arElementIDs)
	{
		$result = array();
		foreach($arElementIDs as $elementID)
		{
			$result[] = intval($elementID);
		}

		return array_unique($result, SORT_NUMERIC);
	}

	private static function PrepareFileEvent($storageTypeID, $elementID, $action, &$arRow, &$arEvents)
	{
		$storageTypeID = intval($storageTypeID);
		$elementID = intval($elementID);
		$action = mb_strtoupper(strval($action));

		$name = isset($arRow['SUBJECT']) ? strval($arRow['SUBJECT']) : '';
		if($name === '')
		{
			$name = "[{$arRow['ID']}]";
		}

		$arEventFiles = array();
		if($action === 'ADD' && $storageTypeID !== CCrmQuoteStorageType::Undefined)
		{
			$arEventFiles = self::MakeRawFiles($storageTypeID, array($elementID));
		}

		$arEvents[] = array(
			'EVENT_NAME' => GetMessage("CRM_QUOTE_FILE_{$action}"),
			'EVENT_TEXT_1' => $action !== 'ADD' ? self::ResolveStorageElementName($storageTypeID, $elementID) : '',
			'EVENT_TEXT_2' => '',
			'FILES' => $arEventFiles
		);
	}

	public static function MakeRawFiles($storageTypeID, array $arElementIDs)
	{
		return \Bitrix\Crm\Integration\StorageManager::makeFileArray($arElementIDs, $storageTypeID);
	}

	private static function ResolveStorageElementName($storageTypeID, $elementID)
	{
		return \Bitrix\Crm\Integration\StorageManager::getFileName($elementID, $storageTypeID);
	}

	public static function isActiveQuotePaymentMethodExists()
	{
		$result = false;

		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		foreach ($arPersonTypes as $personTypeName => $personTypeId)
		{
			if ($personTypeName === 'COMPANY' || $personTypeName === 'CONTACT' && $personTypeId > 0)
			{
				$paySystems = CCrmPaySystem::GetPaySystems($personTypeId);
				if(is_array($paySystems))
				{
					foreach($paySystems as &$paySystem)
					{
						$file = isset($paySystem['~PSA_ACTION_FILE']) ? $paySystem['~PSA_ACTION_FILE'] : '';
						if(preg_match('/quote(_\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $file))
						{
							$result = true;
							break;
						}
					}
				}
			}
			if ($result)
			{
				break;
			}
		}

		return $result;
	}

	public static function isPrintingViaPaymentMethodSupported()
	{
		return self::isActiveQuotePaymentMethodExists();
	}

	public static function HandleStorageElementDeletion($storageTypeID, $elementID)
	{
		global $DB;

		$storageTypeID = (int)$storageTypeID;
		$elementID = (int)$elementID;

		$dbResult = $DB->Query(
			'SELECT QUOTE_ID FROM '.CCrmQuote::ELEMENT_TABLE_NAME.' WHERE STORAGE_TYPE_ID = '.$storageTypeID.' AND ELEMENT_ID = '.$elementID,
			false,
			'File: '.__FILE__.'<br/>Line: '.__LINE__
		);

		while($arResult = $dbResult->Fetch())
		{
			$entityID = isset($arResult['QUOTE_ID']) ? (int)$arResult['QUOTE_ID'] : 0;
			if($entityID <= 0)
			{
				continue;
			}

			$dbEntity = self::GetList(array(), array('ID' => $entityID), false, array('nTopCount' => 1), array('STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS'));

			$arEntity = $dbEntity->Fetch();
			if(!is_array($arEntity))
			{
				continue;
			}

			$arEntity['STORAGE_TYPE_ID'] = isset($arEntity['STORAGE_TYPE_ID'])
				? (int)$arEntity['STORAGE_TYPE_ID'] : $storageTypeID;
			self::PrepareStorageElementIDs($arEntity);
			if(!empty($arEntity['STORAGE_ELEMENT_IDS']))
			{
				$arEntity['STORAGE_ELEMENT_IDS'] = array_diff($arEntity['STORAGE_ELEMENT_IDS'], array($elementID));
			}

			$quote = new CCrmQuote(false);
			$quote->Update($entityID, $arEntity, true, false);
		}
	}
	protected static function DeleteStorageElements($ID)
	{
		global $APPLICATION;

		$ID = intval($ID);
		if($ID <= 0)
		{
			$APPLICATION->throwException(GetMessage('CRM_QUOTE_ERR_INCORRECT_QUOTE_ID'));
			return false;
		}

		$dbRes = self::GetList(array(), array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'), false, array('nTopCount' => 1), array('STORAGE_TYPE_ID', 'STORAGE_ELEMENT_IDS'));

		$arRes = $dbRes->Fetch();
		if(!is_array($arRes))
		{
			$APPLICATION->throwException(GetMessage('CRM_QUOTE_ERR_QUOTE_NOT_FOUND', array('#QUOTE_ID#' => $ID)));
			return false;
		}

		$storageTypeID = isset($arRes['STORAGE_TYPE_ID'])
			? intval($arRes['STORAGE_TYPE_ID']) : CCrmQuoteStorageType::Undefined;

		if($storageTypeID === CCrmQuoteStorageType::File)
		{
			self::PrepareStorageElementIDs($arRes);
			$arFileIDs = isset($arRes['STORAGE_ELEMENT_IDS']) ? $arRes['STORAGE_ELEMENT_IDS'] : array();
			foreach($arFileIDs as $fileID)
			{
				CFile::Delete($fileID);
			}
		}

		return true;
	}
	protected static function GetSaleOrderMap()
	{
		return array(
			'ID' => 'ID',
			'ACCOUNT_NUMBER' => 'QUOTE_NUMBER',
			'ORDER_TOPIC' => 'TITLE',
			'DATE_INSERT' => 'DATE_CREATE',
			'DATE_BILL' => 'BEGINDATE',
			'DATE_PAY_BEFORE' => 'CLOSEDATE',
			'PRICE' => 'OPPORTUNITY',
			'SHOULD_PAY' => 'OPPORTUNITY',
			'CURRENCY' => 'CURRENCY_ID',
			'PAY_SYSTEM_ID' => '',
			'TAX_VALUE' => 'TAX_VALUE',
			'USER_DESCRIPTION' => array('CONTENT', 'TERMS'),
			'PRICE_DELIVERY' => '',
			'DISCOUNT_VALUE' => '',
			'USER_ID' => '',
			'DELIVERY_ID' => ''
		);
	}
	protected static function GetCompanyPersonTypeMap()
	{
		return array(
			'COMPANY' => 'CLIENT_TITLE',
			'COMPANY_NAME' => 'CLIENT_TITLE',
			'COMPANY_ADR' => 'CLIENT_ADDR',
			'CONTACT_PERSON' => 'CLIENT_CONTACT',
			'EMAIL' => 'CLIENT_EMAIL',
			'INN' => 'CLIENT_TP_ID',
			'PHONE' => 'CLIENT_PHONE'
		);
	}
	protected static function GetContactPersonTypeMap()
	{
		return array(
			'FIO' => 'CLIENT_TITLE',
			'EMAIL' => 'CLIENT_EMAIL',
			'PHONE' => 'CLIENT_PHONE'
		);
	}
	public static function PrepareSalePaymentData(array &$arQuote)
	{
		$ID = isset($arQuote['ID']) ? intval($arQuote['ID']) : 0;
		if($ID <= 0)
		{
			return null;
		}

		CCrmQuote::RewriteClientFields($arQuote, false);

		$fieldMap = self::GetSaleOrderMap();
		$order = array();
		foreach($fieldMap as $orderFileldID => $fileldID)
		{
			if(!is_array($fileldID))
			{
				$order[$orderFileldID] = isset($arQuote[$fileldID]) ? $arQuote[$fileldID] : '';
				if ($fileldID === 'CURRENCY_ID' && empty($order[$orderFileldID]))
					$order[$orderFileldID] = CCrmCurrency::GetBaseCurrencyID();
			}
			else
			{
				$v = '';
				foreach($fileldID as $item)
				{
					$s = isset($arQuote[$item]) ? trim($arQuote[$item]) : '';
					if($s === '')
					{
						continue;
					}

					if(preg_match('/<br(\/)?>$/i', $v) !== 1)
					{
						$v .= '<br/>';
					}
					$v .= $s;
				}
				$order[$orderFileldID] = $v;
			}
		}

		$personTypeIDs = CCrmPaySystem::getPersonTypeIDs();
		$personTypeID = isset($arQuote['PERSON_TYPE_ID']) ? intval($arQuote['PERSON_TYPE_ID']) : 0;

		$propertyMap = isset($personTypeIDs['COMPANY']) && intval($personTypeIDs['COMPANY']) === $personTypeID
			? self::GetCompanyPersonTypeMap()
			: self::GetContactPersonTypeMap();

		$properties = array();
		foreach($propertyMap as $propertyFileldID => $fileldID)
		{
			$properties[$propertyFileldID] = isset($arQuote[$fileldID]) ? $arQuote[$fileldID] : '';
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(self::$sUFEntityID, 0, LANGUAGE_ID);
		$supportedUserTypeIDs = array('string', 'double', 'integer', 'boolean', 'datetime');
		foreach($userFields as $name => &$userField)
		{
			$fieldType = $userField['USER_TYPE_ID'];
			if(!isset($arQuote[$name]) || !in_array($fieldType, $supportedUserTypeIDs, true))
			{
				continue;
			}

			$fieldValue = $arQuote[$name];
			if($fieldType === 'boolean')
			{
				$fieldValue = GetMessage(intval($fieldValue) > 0 ? 'MAIN_YES' : 'MAIN_NO');
			}
			$properties[$name] = $fieldValue;
		}
		unset($userField);

		$productRows = self::LoadProductRows($ID);
		$currencyID = isset($arQuote['CURRENCY_ID']) ? $arQuote['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();

		$calculatedOrder = CCrmSaleHelper::Calculate(
			$productRows,
			$currencyID,
			$personTypeID,
			false,
			SITE_ID,
			array('LOCATION_ID' => isset($arQuote['LOCATION_ID']) ? $arQuote['LOCATION_ID'] : '')
		);

		$taxList = isset($calculatedOrder['TAX_LIST']) ? $calculatedOrder['TAX_LIST'] : array();
		foreach($taxList as &$taxInfo)
		{
			$taxInfo['TAX_NAME'] = isset($taxInfo['NAME']) ? $taxInfo['NAME'] : '';
		}
		unset($taxInfo);

		if (CModule::IncludeModule('iblock'))
		{
			if ($calculatedOrder['BASKET_ITEMS'])
			{
				$productProps = array();
				$productIds = array();
				foreach ($calculatedOrder['BASKET_ITEMS'] as $i => $row)
				{
					$productIds[] = $row['PRODUCT_ID'];
					$productProps[$row['PRODUCT_ID']] = array();
				}

				if ($productIds)
				{
					$productIdsByCatalogMap = array();
					$dbRes = \CCrmProduct::GetList(array(), array('ID' => $productIds), array('ID', 'CATALOG_ID'));
					while ($data = $dbRes->Fetch())
					{
						$catalogId = isset($data['CATALOG_ID']) ? intval($data['CATALOG_ID']) : \CCrmCatalog::EnsureDefaultExists();
						if (!isset($productIdsByCatalogMap[$catalogId]))
							$productIdsByCatalogMap[$catalogId] = array();

						$productIdsByCatalogMap[$catalogId][] = $data['ID'];
					}

					if ($productIdsByCatalogMap)
					{
						foreach ($productIdsByCatalogMap as $catalogId => $ids)
							CIBlockElement::GetPropertyValuesArray($productProps, $catalogId, array('ID' => $ids));
					}
				}

				foreach ($calculatedOrder['BASKET_ITEMS'] as $i => $row)
				{
					foreach ($productProps[$row['PRODUCT_ID']] as $property)
						$calculatedOrder['BASKET_ITEMS'][$i]['PROPERTY_'.$property['ID']] = $property['VALUE'];
				}
			}
		}

		$requisiteUserFieldsInfo = null;

		// requisite identifiers
		$requisiteId = 0;
		$bankDetailId = 0;
		$mcRequisiteId = 0;
		$mcBankDetailId = 0;
		if ($row = \Bitrix\Crm\Requisite\EntityLink::getList(
			array(
				'filter' => array(
					'=ENTITY_TYPE_ID' => CCrmOwnerType::Quote,
					'=ENTITY_ID' => $ID
				),
				'select' => array('REQUISITE_ID', 'BANK_DETAIL_ID', 'MC_REQUISITE_ID', 'MC_BANK_DETAIL_ID'),
				'limit' => 1
			)
		)->fetch())
		{
			if (isset($row['REQUISITE_ID']) && $row['REQUISITE_ID'] > 0)
				$requisiteId = (int)$row['REQUISITE_ID'];
			if (isset($row['BANK_DETAIL_ID']) && $row['BANK_DETAIL_ID'] > 0)
				$bankDetailId = (int)$row['BANK_DETAIL_ID'];
			if (isset($row['MC_REQUISITE_ID']) && $row['MC_REQUISITE_ID'] > 0)
				$mcRequisiteId = (int)$row['MC_REQUISITE_ID'];
			if (isset($row['MC_BANK_DETAIL_ID']) && $row['MC_BANK_DETAIL_ID'] > 0)
				$mcBankDetailId = (int)$row['MC_BANK_DETAIL_ID'];
		}

		if (!isset($arQuote['MYCOMPANY_ID']) || $arQuote['MYCOMPANY_ID'] <= 0)
		{
			$defLink = Bitrix\Crm\Requisite\EntityLink::getDefaultMyCompanyRequisiteLink();
			if (is_array($defLink))
			{
				$arQuote['MYCOMPANY_ID'] = isset($defLink['MYCOMPANY_ID']) ? (int)$defLink['MYCOMPANY_ID'] : 0;
				$mcRequisiteId = isset($defLink['MC_REQUISITE_ID']) ? (int)$defLink['MC_REQUISITE_ID'] : 0;
				$mcBankDetailId = isset($defLink['MC_BANK_DETAIL_ID']) ? (int)$defLink['MC_BANK_DETAIL_ID'] : 0;
			}
			unset($defLink);
		}

		// requisite values
		$requisiteValues = array();
		$presetCountryId = 0;
		if ($requisiteId > 0)
		{
			$requisite = new \Bitrix\Crm\EntityRequisite();
			$preset = new \Bitrix\Crm\EntityPreset();
			if ($requisiteUserFieldsInfo === null)
				$requisiteUserFieldsInfo = $requisite->getFormUserFieldsInfo();
			$row = $requisite->getList(
				array('select' => array('*', 'UF_*'), 'filter' => array('=ID' => $requisiteId), 'limit' => 1)
			)->fetch();
			if (is_array($row))
			{
				if (isset($row['PRESET_ID']) && $row['PRESET_ID'] > 0)
				{
					$presetFields = array();
					$res = $preset->getList(array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => \Bitrix\Crm\EntityPreset::Requisite,
							'=ID' => (int)$row['PRESET_ID']
						),
						'select' => array('ID', 'COUNTRY_ID', 'SETTINGS'),
						'limit' => 1
					));
					if ($presetData = $res->fetch())
					{
						if (is_array($presetData['SETTINGS']))
						{
							$presetFieldsInfo = $preset->settingsGetFields($presetData['SETTINGS']);
							foreach ($presetFieldsInfo as $fieldInfo)
							{
								if (isset($fieldInfo['FIELD_NAME']))
									$presetFields[$fieldInfo['FIELD_NAME']] = true;
							}
							unset($presetFieldsInfo, $fieldInfo);
						}

						$presetCountryId = (int)$presetData['COUNTRY_ID'];
					}
					unset($res, $presetData);

					if ($presetCountryId > 0)
					{
						foreach ($row as $fieldName => $fieldValue)
						{
							if (isset($presetFields[$fieldName]))
							{
								if (is_array($requisiteUserFieldsInfo[$fieldName])
									&& $requisiteUserFieldsInfo[$fieldName]['type'] === 'boolean')
								{
									$requisiteValues[$fieldName.'|'.$presetCountryId] = $fieldValue ?
										GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
								}
								elseif ($requisite->isRqListField($fieldName))
								{
									$requisiteValues[$fieldName.'|'.$presetCountryId] =
										$requisite->getRqListFieldValueTitle(
											$fieldName,
											$presetCountryId,
											$fieldValue
										)
									;
								}
								else
								{
									$requisiteValues[$fieldName.'|'.$presetCountryId] = $fieldValue;
								}
							}
						}
						unset($fieldName, $fieldValue, $valueKey);

						// addresses
						foreach ($requisite->getAddresses($requisiteId) as $addrTypeId => $addrFields)
						{
							$valueKey = Bitrix\Crm\EntityRequisite::ADDRESS.'_'.$addrTypeId.'|'.$presetCountryId;
							$addressLines = explode(
								"\n",
								str_replace(
									["\r\n", "\n", "\r"], "\n",
									AddressFormatter::getSingleInstance()->formatTextMultiline($addrFields)
								)
							);
							$requisiteValues[$valueKey] = is_array($addressLines) ? $addressLines : [];
							unset($valueKey, $addressLines);
						}
					}
				}
			}
		}

		// full name
		if ($presetCountryId > 0)
		{
			$fullName = isset($requisiteValues['RQ_NAME|'.$presetCountryId]) ?
				trim(strval($requisiteValues['RQ_NAME|'.$presetCountryId])) : '';
			if (empty($fullName))
			{
				$firstName = isset($requisiteValues['RQ_FIRST_NAME|'.$presetCountryId])
					? trim(strval($requisiteValues['RQ_FIRST_NAME|'.$presetCountryId])) : '';
				$lastName = isset($requisiteValues['RQ_LAST_NAME|'.$presetCountryId]) ?
					trim(strval($requisiteValues['RQ_LAST_NAME|'.$presetCountryId])) : '';
				$secondName = isset($requisiteValues['RQ_SECOND_NAME|'.$presetCountryId]) ?
					trim(strval($requisiteValues['RQ_SECOND_NAME|'.$presetCountryId])) : '';
				if (!empty($firstName) || !empty($lastName) || !empty($secondName))
				{
					$fullName = CUser::FormatName(
						\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
						array(
							'LOGIN' => '[]',
							'NAME' => $firstName,
							'LAST_NAME' => $lastName,
							'SECOND_NAME' => $secondName
						),
						true, false
					);
					if (!empty($fullName) && $fullName !== '[]')
					{
						$requisiteValues['RQ_NAME|'.$presetCountryId] = $fullName;
					}
				}
			}
		}

		// bank detail values
		$bankDetailValues = array();
		if ($bankDetailId > 0)
		{
			$bankDetail = new \Bitrix\Crm\EntityBankDetail();
			$row = $bankDetail->getById($bankDetailId);
			if (is_array($row))
			{
				$countryId = isset($row['COUNTRY_ID']) ? (int)$row['COUNTRY_ID'] : 0;
				if ($countryId > 0)
				{
					foreach ($row as $fieldName => $fieldValue)
					{
						$bankDetailValues[$fieldName.'|'.$countryId] = $fieldValue;
					}
					unset($fieldName, $fieldValue, $valueKey);
				}
			}
		}

		// company values
		$companyValues = array();
		$companyId = isset($arQuote['COMPANY_ID']) ? intval($arQuote['COMPANY_ID']) : 0;
		if ($companyId > 0)
		{
			$res = CCrmCompany::GetListEx(
				array(),
				array('ID' => $companyId),
				false,
				array('nTopCount' => 1),
				array('ID', 'TITLE')
			);
			$row = $res->Fetch();
			if (is_array($row))
			{
				$companyValues = $row;
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $companyId)
				);
				$skip = array();
				while($row = $res->Fetch())
				{
					if (($row['TYPE_ID'] === 'PHONE' || $row['TYPE_ID'] === 'EMAIL')
						&& !isset($skip[$row['COMPLEX_ID']]))
					{
						$companyValues[$row['COMPLEX_ID']] = $row['VALUE'];
						$skip[$row['COMPLEX_ID']] = true;
					}
				}
			}
		}

		// contact values
		$contactValues = array();
		$contactId = isset($arQuote['CONTACT_ID']) ? intval($arQuote['CONTACT_ID']) : 0;
		if ($contactId > 0)
		{
			$res = CCrmContact::GetListEx(
				array(),
				array('ID' => $contactId),
				false,
				array('nTopCount' => 1),
				array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'HONORIFIC')
			);
			$row = $res->Fetch();
			if (is_array($row))
			{
				$contactValues['ID'] = $row['ID'];
				$contactValues['FULL_NAME'] = CCrmContact::PrepareFormattedName($row);
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $contactId)
				);
				$skip = array();
				while($row = $res->Fetch())
				{
					if (($row['TYPE_ID'] === 'PHONE' || $row['TYPE_ID'] === 'EMAIL')
						&& !isset($skip[$row['COMPLEX_ID']]))
					{
						$contactValues[$row['COMPLEX_ID']] = $row['VALUE'];
						$skip[$row['COMPLEX_ID']] = true;
					}
				}
			}
		}

		// backward compatibility
		$countryIds = array();
		if ($presetCountryId > 0)
			$countryIds[] = $presetCountryId;
		else
			$countryIds = \Bitrix\Crm\EntityRequisite::getAllowedRqFieldCountries();
		$personTypeCode = '';
		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		if ($arPersonTypes['COMPANY'] != "" && $arPersonTypes['CONTACT'] != "")
		{
			$personTypeCompany = $arPersonTypes['COMPANY'];
			$personTypeContact = $arPersonTypes['CONTACT'];

			$personTypeId = $personTypeContact;
			$personTypeCode = 'CONTACT';
			if ($companyId > 0)
			{
				$personTypeId = $personTypeCompany;
				$personTypeCode = 'COMPANY';
			}
			$requisiteConverted = (COption::GetOptionString('crm', '~CRM_TRANSFER_REQUISITES_TO_'.$personTypeCode, 'N') !== 'Y');
			if ($requisiteConverted)
			{
				$propsToRequisiteMap = array(
					$personTypeCompany => array(
						'COMPANY_NAME' => 'RQ_COMPANY_NAME',
						'COMPANY' => 'RQ_COMPANY_NAME',
						'COMPANY_ADR' => 'RQ_ADDR_'.EntityAddressType::Registered,
						'INN' => 'RQ_INN',
						'KPP' => 'RQ_KPP',
						'CONTACT_PERSON' => 'RQ_CONTACT',
						'EMAIL' => 'RQ_EMAIL',
						'PHONE' => 'RQ_PHONE'
					),
					$personTypeContact => array(
						'FIO' => 'RQ_NAME',
						'EMAIL' => 'RQ_EMAIL',
						'PHONE' => 'RQ_PHONE',
						'ADDRESS' => 'RQ_ADDR_'.EntityAddressType::Primary,
					),
				);

				if (is_array($properties) && !empty($properties))
				{
					foreach ($countryIds as $countryId)
					{
						foreach ($propsToRequisiteMap[$personTypeId] as $propertyCode => $rqIndex)
						{
							$rqIndex .= '|'.$countryId;
							if (isset($properties[$propertyCode])
								&& !empty($properties[$propertyCode])
								&& !isset($requisiteValues[$rqIndex]))
							{
								$requisiteValues[$rqIndex] = $properties[$propertyCode];
							}
						}
					}
				}
			}
		}

		// company full name
		$companyFullName = '';
		if ($presetCountryId > 0)
		{
			$companyFullName = isset($requisiteValues['RQ_COMPANY_FULL_NAME|'.$presetCountryId]) ?
				trim(strval($requisiteValues['RQ_COMPANY_FULL_NAME|'.$presetCountryId])) : '';

			if (empty($companyFullName))
			{
				$companyShortName = isset($requisiteValues['RQ_COMPANY_NAME|'.$presetCountryId]) ?
					trim(strval($requisiteValues['RQ_COMPANY_NAME|'.$presetCountryId])) : '';

				if (!empty($companyShortName))
				{
					$companyFullName = $companyShortName;
				}
			}
		}
		if (empty($companyFullName))
		{
			$companyName = isset($companyValues['TITLE']) ? trim(strval($companyValues['TITLE'])) : '';

			if (!empty($companyName))
				$companyFullName = $companyName;
		}
		if (!empty($companyFullName))
		{
			foreach ($countryIds as $countryId)
				$requisiteValues['RQ_COMPANY_FULL_NAME|'.$countryId] = $companyFullName;
		}
		unset($companyFullName, $companyShortName, $companyName);

		// my company requisite values
		$mcRequisiteValues = array();
		$mcPresetCountryId = 0;
		if ($mcRequisiteId > 0)
		{
			$requisite = new \Bitrix\Crm\EntityRequisite();
			$preset = new \Bitrix\Crm\EntityPreset();
			if ($requisiteUserFieldsInfo === null)
				$requisiteUserFieldsInfo = $requisite->getFormUserFieldsInfo();
			$row = $requisite->getList(
				array('select' => array('*', 'UF_*'), 'filter' => array('=ID' => $mcRequisiteId), 'limit' => 1)
			)->fetch();
			if (is_array($row))
			{
				if (isset($row['PRESET_ID']) && $row['PRESET_ID'] > 0)
				{
					$presetFields = array();
					$res = $preset->getList(array(
						'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
						'filter' => array(
							'=ENTITY_TYPE_ID' => \Bitrix\Crm\EntityPreset::Requisite,
							'=ID' => (int)$row['PRESET_ID']
						),
						'select' => array('ID', 'COUNTRY_ID', 'SETTINGS'),
						'limit' => 1
					));
					if ($presetData = $res->fetch())
					{
						if (is_array($presetData['SETTINGS']))
						{
							$presetFieldsInfo = $preset->settingsGetFields($presetData['SETTINGS']);
							foreach ($presetFieldsInfo as $fieldInfo)
							{
								if (isset($fieldInfo['FIELD_NAME']))
									$presetFields[$fieldInfo['FIELD_NAME']] = true;
							}
							unset($presetFieldsInfo, $fieldInfo);
						}

						$mcPresetCountryId = (int)$presetData['COUNTRY_ID'];
					}
					unset($res, $presetData);

					if ($mcPresetCountryId > 0)
					{
						foreach ($row as $fieldName => $fieldValue)
						{
							if (isset($presetFields[$fieldName]))
							{
								if (is_array($requisiteUserFieldsInfo[$fieldName])
									&& $requisiteUserFieldsInfo[$fieldName]['type'] === 'boolean')
								{
									$mcRequisiteValues[$fieldName.'|'.$mcPresetCountryId] = $fieldValue ?
										GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
								}
								elseif ($requisite->isRqListField($fieldName))
								{
									$mcRequisiteValues[$fieldName.'|'.$mcPresetCountryId] =
										$requisite->getRqListFieldValueTitle(
											$fieldName,
											$mcPresetCountryId,
											$fieldValue
										)
									;
								}
								else
								{
									$mcRequisiteValues[$fieldName.'|'.$mcPresetCountryId] = $fieldValue;
								}
							}
						}
						unset($fieldName, $fieldValue, $valueKey);

						// addresses
						foreach ($requisite->getAddresses($mcRequisiteId) as $addrTypeId => $addrFields)
						{
							$valueKey = Bitrix\Crm\EntityRequisite::ADDRESS.'_'.$addrTypeId.'|'.$mcPresetCountryId;
							$addressLines = explode(
								"\n",
								str_replace(
									["\r\n", "\n", "\r"], "\n",
									AddressFormatter::getSingleInstance()->formatTextMultiline($addrFields)
								)
							);
							$mcRequisiteValues[$valueKey] = is_array($addressLines) ? $addressLines : [];
							unset($valueKey, $addressLines);
						}
					}
				}
			}
		}

		// full name
		if ($mcPresetCountryId > 0)
		{
			$fullName = isset($mcRequisiteValues['RQ_NAME|'.$mcPresetCountryId]) ?
				trim(strval($mcRequisiteValues['RQ_NAME|'.$mcPresetCountryId])) : '';
			if (empty($fullName))
			{
				$firstName = isset($mcRequisiteValues['RQ_FIRST_NAME|'.$mcPresetCountryId])
					? trim(strval($mcRequisiteValues['RQ_FIRST_NAME|'.$mcPresetCountryId])) : '';
				$lastName = isset($mcRequisiteValues['RQ_LAST_NAME|'.$mcPresetCountryId]) ?
					trim(strval($mcRequisiteValues['RQ_LAST_NAME|'.$mcPresetCountryId])) : '';
				$secondName = isset($mcRequisiteValues['RQ_SECOND_NAME|'.$mcPresetCountryId]) ?
					trim(strval($mcRequisiteValues['RQ_SECOND_NAME|'.$mcPresetCountryId])) : '';
				if (!empty($firstName) || !empty($lastName) || !empty($secondName))
				{
					$fullName = CUser::FormatName(
						\Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
						array(
							'LOGIN' => '[]',
							'NAME' => $firstName,
							'LAST_NAME' => $lastName,
							'SECOND_NAME' => $secondName
						),
						true, false
					);
					if (!empty($fullName) && $fullName !== '[]')
					{
						$mcRequisiteValues['RQ_NAME|'.$mcPresetCountryId] = $fullName;
					}
				}
			}
		}

		// my company bank detail values
		$mcBankDetailValues = array();
		if ($mcBankDetailId > 0)
		{
			$bankDetail = new \Bitrix\Crm\EntityBankDetail();
			$row = $bankDetail->getById($mcBankDetailId);
			if (is_array($row))
			{
				$countryId = isset($row['COUNTRY_ID']) ? (int)$row['COUNTRY_ID'] : 0;
				if ($countryId > 0)
				{
					foreach ($row as $fieldName => $fieldValue)
					{
						$mcBankDetailValues[$fieldName.'|'.$countryId] = $fieldValue;
					}
					unset($fieldName, $fieldValue, $valueKey);
				}
			}
		}

		// my company values
		$myCompanyValues = array();
		$myCompanyId = isset($arQuote['MYCOMPANY_ID']) ? intval($arQuote['MYCOMPANY_ID']) : 0;
		if ($myCompanyId > 0)
		{
			$res = CCrmCompany::GetListEx(
				array(),
				array('ID' => $myCompanyId),
				false,
				array('nTopCount' => 1),
				array('ID', 'TITLE')
			);
			$row = $res->Fetch();
			if (is_array($row))
			{
				$myCompanyValues = $row;
				$res = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $myCompanyId)
				);
				$skip = array();
				while($row = $res->Fetch())
				{
					if (($row['TYPE_ID'] === 'PHONE' || $row['TYPE_ID'] === 'EMAIL')
						&& !isset($skip[$row['COMPLEX_ID']]))
					{
						$myCompanyValues[$row['COMPLEX_ID']] = $row['VALUE'];
						$skip[$row['COMPLEX_ID']] = true;
					}
				}
			}
		}

		// my company full name
		$myCompanyFullName = '';
		if ($mcPresetCountryId > 0)
		{
			$myCompanyFullName = isset($mcRequisiteValues['RQ_COMPANY_FULL_NAME|'.$mcPresetCountryId]) ?
				trim(strval($mcRequisiteValues['RQ_COMPANY_FULL_NAME|'.$mcPresetCountryId])) : '';

			if (empty($myCompanyFullName))
			{
				$myCompanyShortName = isset($mcRequisiteValues['RQ_COMPANY_NAME|'.$mcPresetCountryId]) ?
					trim(strval($mcRequisiteValues['RQ_COMPANY_NAME|'.$mcPresetCountryId])) : '';

				if (!empty($myCompanyShortName))
				{
					$myCompanyFullName = $myCompanyShortName;
				}
			}
		}
		if (empty($myCompanyFullName))
		{
			$myCompanyName = isset($myCompanyValues['TITLE']) ? trim(strval($myCompanyValues['TITLE'])) : '';
			if (!empty($myCompanyName))
				$myCompanyFullName = $myCompanyName;
		}
		if (!empty($myCompanyFullName))
		{
			foreach ($countryIds as $countryId)
				$mcRequisiteValues['RQ_COMPANY_FULL_NAME|'.$countryId] = $myCompanyFullName;
		}
		unset($myCompanyFullName, $myCompanyShortName, $myCompanyName);

		return array(
			'ORDER' => $order,
			'PROPERTIES' => $properties,
			'CART_ITEMS' => $calculatedOrder['BASKET_ITEMS'],
			'TAX_LIST' => $taxList,
			'REQUISITE' => $requisiteValues,
			'BANK_DETAIL' => $bankDetailValues,
			'CRM_COMPANY' => $companyValues,
			'CRM_CONTACT' => $contactValues,
			'MC_REQUISITE' => $mcRequisiteValues,
			'MC_BANK_DETAIL' => $mcBankDetailValues,
			'CRM_MYCOMPANY' => $myCompanyValues,
			'PAYMENT' => array(
				'ID' => 0,
				'PAY_SYSTEM_ID' => 0,
				'SUM' => isset($order['SHOULD_PAY']) ? $order['SHOULD_PAY'] : 0.0,
				'PAID' => '',
				'DATE_BILL' => isset($order['DATE_BILL']) ? $order['DATE_BILL'] : null
			),
			'SHIPMENT' => array('DELIVERY_ID' => 0)
		);
	}
	public static function Rebind($ownerTypeID, $oldID, $newID)
	{
		global $DB;

		$ownerTypeID = intval($ownerTypeID);
		$oldID = intval($oldID);
		$newID = intval($newID);
		$tableName = CCrmQuote::TABLE_NAME;

		if($ownerTypeID === CCrmOwnerType::Lead)
		{
			$DB->Query(
				"UPDATE {$tableName} SET LEAD_ID = {$newID} WHERE LEAD_ID = {$oldID}",
				false,
				'File: '.__FILE__.'<br>Line: '.__LINE__
			);
		}
		elseif($ownerTypeID === CCrmOwnerType::Contact)
		{
			$DB->Query(
				"UPDATE {$tableName} SET CONTACT_ID = {$newID} WHERE CONTACT_ID = {$oldID}",
				false,
				'File: '.__FILE__.'<br>Line: '.__LINE__
			);
		}
		elseif($ownerTypeID === CCrmOwnerType::Company)
		{
			$DB->Query(
				"UPDATE {$tableName} SET COMPANY_ID = {$newID} WHERE COMPANY_ID = {$oldID}",
				false,
				'File: '.__FILE__.'<br>Line: '.__LINE__
			);
		}
	}
	public static function PrepareStorageElementInfo(&$arFields)
	{
		$storageTypeID = isset($arFields['STORAGE_TYPE_ID']) ? (int)$arFields['STORAGE_TYPE_ID'] : StorageType::Undefined;
		if(!StorageType::IsDefined($storageTypeID))
		{
			$storageTypeID = self::GetDefaultStorageTypeID();
		}

		$storageElementIDs = isset($arFields['STORAGE_ELEMENT_IDS']) && is_array($arFields['STORAGE_ELEMENT_IDS'])
			? $arFields['STORAGE_ELEMENT_IDS'] : array();

		if($storageTypeID === StorageType::File)
		{
			$arFields['FILES'] = array();
			foreach($storageElementIDs as $fileID)
			{
				$arData = CFile::GetFileArray($fileID);
				if(is_array($arData))
				{
					$arFields['FILES'][] = array(
						'fileID' => $arData['ID'],
						'fileName' => $arData['FILE_NAME'],
						'fileURL' =>  CCrmUrlUtil::UrnEncode($arData['SRC']),
						'fileSize' => $arData['FILE_SIZE']
					);
				}
			}
		}
		elseif($storageTypeID === StorageType::WebDav || $storageTypeID === StorageType::Disk)
		{
			$infos = array();
			foreach($storageElementIDs as $elementID)
			{
				$id = (isset($arFields['ID']) && $arFields['ID'] > 0) ? (int)$arFields['ID'] : 0;
				$infos[] = StorageManager::getFileInfo(
					$elementID, $storageTypeID, false,
					array('OWNER_TYPE_ID' => \CCrmOwnerType::Quote, 'OWNER_ID' => $id)
				);
			}
			$arFields[$storageTypeID === StorageType::Disk ? 'DISK_FILES' : 'WEBDAV_ELEMENTS'] = &$infos;
			unset($infos);
		}
	}

	public static function PrepareFilter(&$arFilter, $arFilter2Logic = null)
	{
		//shameless copy-paste from the crm.quote.list component
		if(!is_array($arFilter))
			return;

		if(!is_array($arFilter2Logic))
			$arFilter2Logic = array();

		static $arImmutableFilters = array(
			'FM', 'ID', 'ASSIGNED_BY_ID', 'CURRENCY_ID',
			'CONTACT_ID', 'CONTACT_ID_value', 'ASSOCIATED_CONTACT_ID',
			'COMPANY_ID', 'COMPANY_ID_value',
			'LEAD_ID', 'LEAD_ID_value',
			'DEAL_ID', 'DEAL_ID_value',
			'MYCOMPANY_ID', 'MYCOMPANY_ID_value',
			'CREATED_BY_ID', 'MODIFY_BY_ID', 'PRODUCT_ROW_PRODUCT_ID',
			'WEBFORM_ID',
			'SEARCH_CONTENT',
			'FILTER_ID', 'FILTER_APPLIED', 'PRESET_ID'
		);
		foreach ($arFilter as $k => $v)
		{
			if(in_array($k, $arImmutableFilters, true))
			{
				continue;
			}

			$arMatch = array();

			if(
			in_array(
				$k, array(
					'PRODUCT_ID', /*'TYPE_ID', */'STATUS_ID',
					'COMPANY_ID', 'LEAD_ID', 'DEAL_ID', 'CONTACT_ID', 'MYCOMPANY_ID'
				)
			))
			{
				// Bugfix #23121 - to suppress comparison by LIKE
				$arFilter['='.$k] = $v;
				unset($arFilter[$k]);
			}
			elseif ($k === 'ENTITIES_LINKS')
			{
				$ownerData =explode('_', $v);
				if(count($ownerData) > 1)
				{
					$ownerTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::ResolveID($ownerData[0]));
					$ownerID = intval($ownerData[1]);
					if(!empty($ownerTypeName) && $ownerID > 0)
						$arFilter[$ownerTypeName.'_ID'] = $ownerID;
				}
				unset($arFilter[$k]);
			}
			elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
			{
				if($v <> '')
				{
					$arFilter['>='.$arMatch[1]] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
			{
				if($v <> '')
				{
					if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
					{
						$v = CCrmDateTimeHelper::SetMaxDayTime($v);
					}
					$arFilter['<='.$arMatch[1]] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif (in_array($k, $arFilter2Logic))
			{
				// Bugfix #26956 - skip empty values in logical filter
				$v = trim($v);
				if($v !== '')
				{
					$arFilter['?'.$k] = $v;
				}
				unset($arFilter[$k]);
			}
			elseif (mb_strpos($k, 'UF_') !== 0 && $k != 'LOGIC' && preg_match('/^[^\=\%\?\>\<]{1}/', $k) === 1)
			{
				$arFilter['%'.$k] = $v;
				unset($arFilter[$k]);
			}
		}
	}


	public static function savePdf($quote_id, $pay_system_id, &$error = null)
	{
		$error = false;

		if (!\CModule::includeModule('sale'))
		{
			$error = 'MODULE SALE NOT INCLUDED!';
			return false;
		}

		$quote_id = (int) $quote_id;
		if ($quote_id <= 0)
		{
			$error = 'QUOTE_ID IS NOT FOUND!';
			return false;
		}

		if (!\CCrmQuote::checkReadPermission($quote_id))
		{
			$error = 'PERMISSION DENIED!';
			return false;
		}

		$pay_system_id = (int) $pay_system_id;
		if ($pay_system_id <= 0)
		{
			$error = 'PAY_SYSTEM_ID ID NOT FOUND!';
			return false;
		}

		$dbResult = \CCrmQuote::getList(
			array(),
			array(
				'ID' => $quote_id,
				'CHECK_PERMISSIONS' => 'N',
			),
			false,
			false,
			array('*', 'UF_*')
		);
		$quoteFields = is_object($dbResult) ? $dbResult->fetch() : null;
		if (!is_array($quoteFields))
		{
			$error = 'QUOTE IS NOT FOUND!';
			return false;
		}

		$paymentData = \CCrmQuote::prepareSalePaymentData($quoteFields);
		if (!is_array($paymentData))
		{
			$error = 'COULD NOT PREPARE PAYMENT DATA!';
			return false;
		}

		$dbPaySysAction = \CSalePaySystemAction::getList(
			array(),
			array(
				'PAY_SYSTEM_ID'  => $pay_system_id,
				'PERSON_TYPE_ID' => $quoteFields['PERSON_TYPE_ID'],
			),
			false,
			false,
			array('ACTION_FILE', 'PARAMS', 'ENCODING')
		);

		$paySysActionFields = $dbPaySysAction->fetch();
		if (!is_array($paySysActionFields))
		{
			$error = 'COULD NOT FIND PAYMENT SYSTEM ACTION!';
			return false;
		}

		$actionFilePath = isset($paySysActionFields['ACTION_FILE']) ? $paySysActionFields['ACTION_FILE'] : '';
		if (!is_string($actionFilePath) || $actionFilePath === '')
		{
			$error = 'COULD NOT FIND PAYMENT SYSTEM ACTION FILE!';
			return false;
		}

		$actionFilePath = $_SERVER['DOCUMENT_ROOT'].$actionFilePath;
		$actionFilePath = str_replace('\\', '/', $actionFilePath);
		while (mb_substr($actionFilePath, mb_strlen($actionFilePath) - 1, 1) == '/')
			$actionFilePath = mb_substr($actionFilePath, 0, mb_strlen($actionFilePath) - 1);

		if (!file_exists($actionFilePath))
		{
			$error = 'COULD NOT FIND PAYMENT SYSTEM ACTION FILE!';
			return false;
		}
		else if (is_dir($actionFilePath))
		{
			$actionFilePath = $actionFilePath.'/payment.php';
			if (!file_exists($actionFilePath))
			{
				$error = 'COULD NOT FIND PAYMENT SYSTEM ACTION FILE!';
				return false;
			}
		}

		\CSalePaySystemAction::initParamArrays(
			$paymentData['ORDER'],
			0,
			$paySysActionFields['PARAMS'],
			array(
				"PROPERTIES"     => $paymentData['PROPERTIES'],
				"BASKET_ITEMS"   => $paymentData['CART_ITEMS'],
				'TAX_LIST'       => $paymentData['TAX_LIST'],
				'REQUISITE'      => $paymentData['REQUISITE'],
				'BANK_DETAIL'    => $paymentData['BANK_DETAIL'],
				'CRM_COMPANY'    => $paymentData['CRM_COMPANY'],
				'CRM_CONTACT'    => $paymentData['CRM_CONTACT'],
				'MC_REQUISITE'   => $paymentData['MC_REQUISITE'],
				'MC_BANK_DETAIL' => $paymentData['MC_BANK_DETAIL'],
				'CRM_MYCOMPANY'  => $paymentData['CRM_MYCOMPANY']
			),
			$paymentData['PAYMENT'],
			$paymentData['SHIPMENT']
		);

		$origRequest = $_REQUEST;
		$_REQUEST['pdf'] = true;
		$_REQUEST['GET_CONTENT'] = 'Y';

		$pdfContent = include($actionFilePath);

		$_REQUEST['pdf'] = $origRequest['pdf'];
		$_REQUEST['GET_CONTENT'] = $origRequest['GET_CONTENT'];

		$fileName = "quote_{$quote_id}.pdf";
		$fileData = array(
			'name' => $fileName,
			'type' => 'application/pdf',
			'content' => $pdfContent,
			'MODULE_ID' => 'crm'
		);

		$fileID = \CFile::saveFile($fileData, 'crm');
		if ($fileID <= 0)
		{
			$error = 'COULD NOT SAVE FILE!';
			return false;
		}

		return $fileID;
	}

	public static function existsEntityWithStatus($statusId)
	{
		$queryObject = self::getList(
			['ID' => 'DESC'],
			['STATUS_ID' => $statusId, 'CHECK_PERMISSIONS' => 'N'],
			false,
			false,
			['ID']
		);

		return (bool) $queryObject->fetch();
	}

	public function getLastErrors(): ?\Bitrix\Main\ErrorCollection
	{
		return $this->lastErrors;
	}
}

/**
 * @deprecated Please use \Bitrix\Crm\Integration\StorageType
 */
class CCrmQuoteStorageType
{
	const Undefined = 0;
	const File = 1;
	const WebDav = 2;
	const Disk = 3;
	const Last = self::Disk;

	public static function IsDefined($typeID)
	{
		$typeID = intval($typeID);
		return $typeID > self::Undefined && $typeID <= self::Last;
	}
}
