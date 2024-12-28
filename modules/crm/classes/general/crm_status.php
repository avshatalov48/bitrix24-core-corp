<?php

if(!defined('CACHED_b_crm_status')) define('CACHED_b_crm_status', 360000);

IncludeModuleLangFile(__FILE__);

use Bitrix\Crm\Attribute\FieldAttributeManager;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;

class CCrmStatus
{
	protected const PREFIX_SEPARATOR = ':';

	public const PREFIX_USER_CREATED = 'UC';
	public const DEFAULT_SORT = 10;

	protected $entityId = '';
	private static $FIELD_INFOS;

	private $LAST_ERROR = '';

	public function __construct($entityId)
	{
		$this->entityId = $entityId;

	}
	// Get Fields Metadata

	/**
	 * Returns fields description for rest service.
	 *
	 * @return array
	 */
	public static function GetFieldsInfo(): array
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = [
				'ID' => [
					'TYPE' => 'integer',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly]
				],
				'ENTITY_ID' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					]
				],
				'STATUS_ID' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [
						CCrmFieldInfoAttr::Required,
						CCrmFieldInfoAttr::Immutable
					]
				],
				'SORT' => ['TYPE' => 'integer'],
				'NAME' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::Required]
				],
				'NAME_INIT' => [
					'TYPE' => 'string',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly]
				],
				'SYSTEM' => [
					'TYPE' => 'char',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly]
				],
				'CATEGORY_ID' => [
					'TYPE' => 'integer',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::Immutable],
				],
				'COLOR' => [
					'TYPE' => 'string',
				],
				'SEMANTICS' => [
					'TYPE' => 'char',
					'ATTRIBUTES' => [CCrmFieldInfoAttr::Immutable]
				],
				'EXTRA' => ['TYPE' => 'crm_status_extra'],
			];
		}
		return self::$FIELD_INFOS;
	}

	/**
	 * Returns language-dependent field name.
	 *
	 * @param string $fieldName
	 * @return string
	 */
	public static function GetFieldCaption($fieldName): string
	{
		$result = GetMessage("CRM_STATUS_FIELD_{$fieldName}");

		return is_string($result) ? $result : '';
	}

	/**
	 * Returns description of statuses entity types.
	 *
	 * @return array
	 */
	public static function GetEntityTypes(): array
	{
		// force loading owner_type phrases
		\CCrmOwnerType::GetAllDescriptions();

		$arEntityType = [
			'STATUS' => [
				'ID' =>'STATUS',
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_MSGVER_1'),
				'SEMANTIC_INFO' => self::GetLeadStatusSemanticInfo(),
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Lead,
			],
			'SOURCE' => ['ID' =>'SOURCE', 'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE')],
			'CONTACT_TYPE' => ['ID' =>'CONTACT_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_CONTACT_TYPE')],
			'COMPANY_TYPE' => ['ID' =>'COMPANY_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_COMPANY_TYPE')],
			'EMPLOYEES' => ['ID' =>'EMPLOYEES', 'NAME' => GetMessage('CRM_STATUS_TYPE_EMPLOYEES')],
			'INDUSTRY' => ['ID' =>'INDUSTRY', 'NAME' => GetMessage('CRM_STATUS_TYPE_INDUSTRY')],
			'DEAL_TYPE' => ['ID' =>'DEAL_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_DEAL_TYPE')],
		];

		$invoiceSettings = \Bitrix\Crm\Settings\InvoiceSettings::getCurrent();
		if ($invoiceSettings->isOldInvoicesEnabled())
		{
			$arEntityType['INVOICE_STATUS'] = [
				'ID' =>'INVOICE_STATUS',
				'NAME' => GetMessage('CRM_STATUS_TYPE_INVOICE_STATUS_MSGVER_1'),
				'SEMANTIC_INFO' => self::GetInvoiceStatusSemanticInfo(),
			];
		}
		if ($invoiceSettings->isSmartInvoiceEnabled())
		{
			if ($invoiceSettings->isOldInvoicesEnabled())
			{
				$arEntityType['INVOICE_STATUS']['NAME'] = \Bitrix\Crm\Service\Container::getInstance()->getLocalization()->appendOldVersionSuffix(GetMessage('CRM_STATUS_TYPE_INVOICE_STATUS_MSGVER_1'));
			}
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
			if ($factory)
			{
				if (!$factory->isCategoriesEnabled())
				{
					$category = $factory->getDefaultCategory();
					if ($category)
					{
						$stagesEntityId = $factory->getStagesEntityId($category->getId());
						$arEntityType[$stagesEntityId] = [
							'ID' => $stagesEntityId,
							'NAME' => Loc::getMessage('CRM_STATUS_TYPE_INVOICE_STATUS_MSGVER_1'),
							'SEMANTIC_INFO' => [],
							'PREFIX' => static::getDynamicEntityStatusPrefix(\CCrmOwnerType::SmartInvoice, $category->getId()),
							'FIELD_ATTRIBUTE_SCOPE' => FieldAttributeManager::getEntityScopeByCategory($category->getId()),
							'ENTITY_TYPE_ID' => \CCrmOwnerType::SmartInvoice,
							'IS_ENABLED' => true,
							'CATEGORY_ID' => $category->getId(),
						];
					}
				}
				else
				{
					foreach ($factory->getCategories() as $category)
					{
						$stagesEntityId = $factory->getStagesEntityId($category->getId());
						$arEntityType[$stagesEntityId] = [
							'ID' => $stagesEntityId,
							'NAME' => Loc::getMessage('CRM_STATUS_TYPE_STATUS_WITH_CATEGORY', [
								'#NAME#' => $factory->getEntityDescription(),
								'#CATEGORY#' => $category->getName(),
							]),
							'SEMANTIC_INFO' => [],
							'PREFIX' => static::getDynamicEntityStatusPrefix(\CCrmOwnerType::SmartInvoice, $category->getId()),
							'FIELD_ATTRIBUTE_SCOPE' => FieldAttributeManager::getEntityScopeByCategory($category->getId()),
							'ENTITY_TYPE_ID' => \CCrmOwnerType::SmartInvoice,
							'IS_ENABLED' => true,
							'CATEGORY_ID' => $category->getId(),
						];
					}
				}
			}
		}

		if(DealCategory::isCustomized())
		{
			DealCategory::prepareStatusEntityInfos($arEntityType, true);
		}
		else
		{
			$arEntityType['DEAL_STAGE'] = [
				'ID' =>'DEAL_STAGE',
				'NAME' => GetMessage('CRM_STATUS_TYPE_DEAL_STAGE'),
				'SEMANTIC_INFO' => self::GetDealStageSemanticInfo(),
				'FIELD_ATTRIBUTE_SCOPE' => FieldAttributeManager::getEntityScopeByCategory(),
				'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
				'CATEGORY_ID' => 0,
			];
		}

		$arEntityType = array_merge(
			$arEntityType,
			[
				'QUOTE_STATUS' => [
					'ID' =>'QUOTE_STATUS',
					'NAME' => GetMessage('CRM_STATUS_TYPE_QUOTE_STATUS_MSGVER_2'),
					'SEMANTIC_INFO' => self::GetQuoteStatusSemanticInfo(),
					'ENTITY_TYPE_ID' => \CCrmOwnerType::Quote,
				],
				'HONORIFIC' => ['ID' =>'HONORIFIC', 'NAME' => GetMessage('CRM_STATUS_TYPE_HONORIFIC')],
				'CALL_LIST' => ['ID' => 'CALL_LIST', 'NAME' => GetMessage('CRM_STATUS_TYPE_CALL_LIST')]
			]
		);

		if (\Bitrix\Crm\Settings\Crm::isDocumentSigningEnabled())
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory(\CCrmOwnerType::SmartDocument);
			if ($factory)
			{
				$category = $factory->getDefaultCategory();
				if ($category)
				{
					$stagesEntityId = $factory->getStagesEntityId($category->getId());
					$arEntityType[$stagesEntityId] = [
						'ID' => $stagesEntityId,
						'NAME' => Loc::getMessage('CRM_STATUS_TYPE_SMART_DOCUMENT_STATUS'),
						'SEMANTIC_INFO' => [],
						'PREFIX' => static::getDynamicEntityStatusPrefix(\CCrmOwnerType::SmartDocument, $category->getId()),
						'FIELD_ATTRIBUTE_SCOPE' => FieldAttributeManager::getEntityScopeByCategory($category->getId()),
						'ENTITY_TYPE_ID' => \CCrmOwnerType::SmartDocument,
						'IS_ENABLED' => true,
						'CATEGORY_ID' => $category->getId(),
					];
				}
			}
		}

		if(self::IsDepricatedTypesEnabled())
		{
			$arEntityType['EVENT_TYPE'] = ['ID' =>'EVENT_TYPE', 'NAME' => GetMessage('CRM_STATUS_TYPE_EVENT_TYPE')];
			$arEntityType['PRODUCT'] = ['ID' => 'PRODUCT', 'NAME' => GetMessage('CRM_STATUS_TYPE_PRODUCT')];
		}

		$arEntityType = array_merge(
			$arEntityType,
			static::getDynamicEntities()
		);

		return $arEntityType;
	}

	protected static function getDynamicEntities(): array
	{
		$typesMap = \Bitrix\Crm\Service\Container::getInstance()->getDynamicTypesMap();
		try
		{
			$typesMap->load([
				'isLoadStages' => false,
			]);
		}
		catch (Exception $exception)
		{
		}
		catch (Error $error)
		{
		}

		foreach ($typesMap->getTypes() as $type)
		{
			foreach ($typesMap->getCategories($type->getEntityTypeId()) as $category)
			{
				$statusEntityId = $typesMap->getStagesEntityId($type->getEntityTypeId(), $category->getId());
				$entities[$statusEntityId] = [
					'ID' => $statusEntityId,
					'NAME' => Loc::getMessage('CRM_STATUS_TYPE_STATUS_WITH_CATEGORY', [
						'#NAME#' => $type->getTitle(),
						'#CATEGORY#' => $category->getName(),
					]),
					'SEMANTIC_INFO' => [],
					'PREFIX' => static::getDynamicEntityStatusPrefix($type->getEntityTypeId(), $category->getId()),
					'FIELD_ATTRIBUTE_SCOPE' => FieldAttributeManager::getEntityScopeByCategory($category->getId()),
					'ENTITY_TYPE_ID' => $type->getEntityTypeId(),
					'IS_ENABLED' => $type->getIsStagesEnabled(),
					'CATEGORY_ID' => $category->getId(),
				];
			}
		}

		return $entities ?? [];
	}

	public static function getDynamicEntityStatusPrefix(int $entityTypeId, int $categoryId): string
	{
		return 'DT' . $entityTypeId . '_' . $categoryId;
	}

	/**
	 * Returns types available for setting as a simple list in an entity details.
	 *
	 * @return array
	 */
	public static function getAllowedInnerConfigTypes(): array
	{
		static $result = null;

		if ($result === null)
		{
			$requisite = EntityRequisite::getSingleInstance();
			$result = array_merge(
				[
					'SOURCE',
					'CONTACT_TYPE',
					'COMPANY_TYPE',
					'EMPLOYEES',
					'INDUSTRY',
					'DEAL_TYPE',
					'HONORIFIC',
					'EVENT_TYPE',
				],
				$requisite->getAllowedRqListFieldsStatusEntitities()
			);
		}

		return $result;
	}

	/**
	 * @deprecated
	 * @return array
	 */
	public static function GetFieldExtraTypeInfo()
	{
		return [
			'SEMANTICS' => ['TYPE' => 'string', 'ATTRIBUTES' => [CCrmFieldInfoAttr::ReadOnly]],
			'COLOR' => ['TYPE' => 'string']
		];
	}

	public static function IsDepricatedTypesEnabled(): bool
	{
		return mb_strtoupper(COption::GetOptionString('crm', 'enable_depricated_statuses', 'N')) !== 'N';
	}

	public static function EnableDepricatedTypes($enable)
	{
		return COption::SetOptionString('crm', 'enable_depricated_statuses', $enable ? 'Y' : 'N');
	}

	protected function getStatusPrefix(): string
	{
		$entityInfo = static::GetEntityTypes()[$this->entityId] ?? [];

		return $entityInfo['PREFIX'] ?? '';
	}

	public function addPrefixToStatusId(string $statusId): string
	{
		$statusId = $this->removePrefixFromStatusId($statusId);

		return static::addKnownPrefixToStatusId($statusId, $this->getStatusPrefix());
	}

	public static function addKnownPrefixToStatusId(string $statusId, ?string $prefix): string
	{
		return ($prefix ? $prefix . static::PREFIX_SEPARATOR : '') . $statusId;
	}

	protected function removePrefixFromStatusId(string $statusId): string
	{
		$prefixPos = mb_strpos($statusId, static::PREFIX_SEPARATOR);
		if ($prefixPos === false)
		{
			return $statusId;
		}

		return mb_substr($statusId, $prefixPos + 1);
	}

	/**
	 * Creates new status.
	 * @see StatusTable::add()
	 *
	 * @param array $arFields
	 * @param bool $bCheckStatusId
	 * @return array|bool|int
	 * @throws \Bitrix\Main\NotSupportedException
	 */
	public function Add(array $arFields, bool $bCheckStatusId = true)
	{
		$this->LAST_ERROR = '';

		if (!$this->CheckFields($arFields, $bCheckStatusId))
		{
			return false;
		}

		$arFields['SORT'] = (int)($arFields['SORT'] ?? 0);
		if ($arFields['SORT'] <= 0)
		{
			$arFields['SORT'] = self::DEFAULT_SORT;
		}

		if (!is_set($arFields, 'SYSTEM'))
		{
			$arFields['SYSTEM'] = 'N';
		}

		if (!is_set($arFields, 'STATUS_ID'))
		{
			$arFields['STATUS_ID'] = '';
		}

		if (isset($arFields['CATEGORY_ID']))
		{
			$categoryId = (int)$arFields['CATEGORY_ID'];
		}
		elseif (DealCategory::hasStatusEntity($this->entityId))
		{
			$categoryId = DealCategory::convertFromStatusEntityID($this->entityId);
		}
		else
		{
			$categoryId = 0;
		}

		$statusId = $arFields['STATUS_ID'];
		if (empty($statusId))
		{
			$statusId = $this->getUniqueRandomStatusId();
		}

		if (!empty($arFields['COLOR']) && !str_starts_with($arFields['COLOR'], '#'))
		{
			$arFields['COLOR'] = '#' . $arFields['COLOR'];
		}

		$name = trim($arFields['NAME'] ?? '');
		$semantics = empty($arFields['SEMANTICS']) ? \Bitrix\Crm\PhaseSemantics::PROCESS : $arFields['SEMANTICS'];

		$result = StatusTable::add([
			'ENTITY_ID' => $this->entityId,
			'STATUS_ID' => $this->addPrefixToStatusId($statusId),
			'NAME' => $name,
			'NAME_INIT' => $arFields['SYSTEM'] === 'Y' ? $name : '',
			'SORT' => $arFields['SORT'],
			'SYSTEM' => $arFields['SYSTEM'] === 'Y'? 'Y': 'N',
			'CATEGORY_ID' => $categoryId,
			'COLOR' => $arFields['COLOR'] ?? null,
			'SEMANTICS' => $semantics,
		]);

		if (!$result->isSuccess())
		{
			$this->LAST_ERROR = $result->getErrorMessages()[0];

			return false;
		}

		return $result->getId();
	}

	/**
	 * Updates existing status record.
	 *
	 * @param int $ID
	 * @param array $arFields
	 * @param array $arOptions
	 * @return bool|int
	 * @throws Exception
	 */
	public function Update($ID, array $arFields, array $arOptions = [])
	{
		$ID = (int)$ID;
		$this->LAST_ERROR = '';

		if (!$this->CheckFields($arFields))
		{
			return false;
		}

		$arFields['SORT'] = (int)($arFields['SORT'] ?? 0);
		if ($arFields['SORT'] <= 0)
		{
			$arFields['SORT'] = self::DEFAULT_SORT;
		}

		$arFields_u['SORT'] = $arFields['SORT'];

		$name = trim($arFields['NAME'] ?? '');
		if (!empty($name))
		{
			$arFields_u['NAME'] = $name;
		}

		if (isset($arFields['SYSTEM']))
		{
			$arFields_u['SYSTEM'] = ($arFields['SYSTEM'] === 'Y' ? 'Y' : 'N');
		}

		if (
			isset($arOptions['ENABLE_STATUS_ID'], $arFields['STATUS_ID'])
			&& $arOptions['ENABLE_STATUS_ID']
		)
		{
			$arFields_u['STATUS_ID'] = $arFields['STATUS_ID'];
		}

		if (
			isset($arOptions['ENABLE_NAME_INIT'], $arFields['NAME_INIT'])
			&& $arOptions['ENABLE_NAME_INIT']
		)
		{
			$arFields_u['NAME_INIT'] = $arFields['NAME_INIT'];
		}

		if (isset($arFields['COLOR']))
		{
			if(!str_starts_with($arFields['COLOR'], '#'))
			{
				$arFields['COLOR'] = '#' . $arFields['COLOR'];
			}

			$arFields_u['COLOR'] = $arFields['COLOR'];
		}

		if (isset($arFields['SEMANTICS']))
		{
			$arFields_u['SEMANTICS'] = $arFields['SEMANTICS'];
		}

		$result = StatusTable::update($ID, $arFields_u);
		if (!$result->isSuccess())
		{
			$this->LAST_ERROR = $result->getErrorMessages()[0];
		}

		return $ID;
	}

	/**
	 * Deletes status by id.
	 *
	 * @deprecated
	 * @see StatusTable::delete() instead
	 * @param int $ID
	 * @return bool
	 */
	public function Delete(int $ID): bool
	{
		$this->LAST_ERROR = '';

		$result = StatusTable::delete($ID);

		if(!$result->isSuccess())
		{
			$this->LAST_ERROR = $result->getErrorMessages()[0];
			return false;
		}

		return true;
	}

	/**
	 * @deprecated
	 * @see StatusTable::getList()
	 * @param array $arSort
	 * @param array $arFilter
	 * @return bool|CDBResult
	 */
	public static function GetList($arSort=array(), $arFilter=Array())
	{
		$sqlHelper = \Bitrix\Main\Application::getConnection()->getSqlHelper();

		global $DB;
		$arSqlSearch = Array();
		if (is_array($arFilter))
		{
			$filter_keys = array_keys($arFilter);
			for ($i=0, $ic=count($filter_keys); $i<$ic; $i++)
			{
				$val = $arFilter[$filter_keys[$i]];
				if ((string)$val == '' || (string)$val=='NOT_REF') continue;
				switch(strtoupper($filter_keys[$i]))
				{
					case 'ID':
						$arSqlSearch[] = "CS.ID = '".$DB->ForSql($val)."'";
						break;
					case 'ENTITY_ID':
						$arSqlSearch[] = "CS.ENTITY_ID = '".$DB->ForSql($val)."'";
						break;
					case 'STATUS_ID':
						$arSqlSearch[] = "CS.STATUS_ID = '".$DB->ForSql($val)."'";
						break;
					case 'NAME':
						$arSqlSearch[] = GetFilterQuery('CS.NAME', $val);
						break;
					case 'SORT':
						$arSqlSearch[] = "CS.SORT = '".$DB->ForSql($val)."'";
						break;
					case 'SYSTEM':
						$arSqlSearch[] = "CS.".$sqlHelper->quote('SYSTEM')."='".(($val == 'Y')? 'Y' : 'N')."'";
						break;
					case 'CATEGORY_ID':
						$arSqlSearch[] = "CS.CATEGORY_ID = '".((int) $val)."'";
						break;
					case 'SEMANTICS':
						$arSqlSearch[] = "CS.SEMANTICS = '".$DB->ForSql($val)."'";
						break;
				}
			}
		}

		$sOrder = '';
		foreach($arSort as $key=>$val)
		{
			$ord = (mb_strtoupper($val) <> 'ASC'? 'DESC':'ASC');
			switch(mb_strtoupper($key))
			{
				case 'ID':
					$sOrder .= ', CS.ID '.$ord;
					break;
				case 'ENTITY_ID':
					$sOrder .= ', CS.ENTITY_ID '.$ord;
					break;
				case 'STATUS_ID':
					$sOrder .= ', CS.STATUS_ID '.$ord;
					break;
				case 'NAME':
					$sOrder .= ', CS.NAME '.$ord;
					break;
				case 'SORT':
					$sOrder .= ', CS.SORT '.$ord;
					break;
				case 'SYSTEM':
					$sOrder .= ", CS.".$sqlHelper->quote('SYSTEM')." ".$ord;
					break;
				case 'CATEGORY_ID':
					$sOrder .= ', CS.CATEGORY_ID '.$ord;
					break;
				case 'SEMANTICS':
					$sOrder .= ', CS.SEMANTICS '.$ord;
					break;
			}
		}

		if ($sOrder == '')
			$sOrder = 'CS.ID DESC';

		$strSqlOrder = ' ORDER BY '.trim($sOrder, ', ');
		$strSqlSearch = GetFilterSqlSearch($arSqlSearch);

		$strSql = "SELECT CS.* FROM b_crm_status CS WHERE {$strSqlSearch} {$strSqlOrder}";
		$res = $DB->Query($strSql);

		return $res;
	}

	/**
	 * Return true if there is a record with the same STATUS_ID.
	 *
	 * @param string $statusId
	 * @return bool
	 */
	public function CheckStatusId($statusId): bool
	{
		if(!is_string($statusId))
		{
			return false;
		}
		return StatusTable::getRow([
			'filter' => [
				'=STATUS_ID' => $statusId,
				'=ENTITY_ID' => $this->entityId,
			]
		]) !== null;
	}

	public function validateStatusId(string $statusId): \Bitrix\Main\Result
	{
		$statusId = $this->removePrefixFromStatusId($statusId);
		$statusIdWithPrefix = $this->addPrefixToStatusId($statusId);
		$prefixLength = mb_strlen($statusIdWithPrefix) - mb_strlen($statusId);

		$result = new \Bitrix\Main\Result();

		$maxStatusIdLength = 50 - $prefixLength; // STATUS_ID in database is varchar(50)
		$permissionFieldLength = 30 - $prefixLength; // ATTR column in b_crm_entity_perms is varchar(30)

		$useValidationRegex = false;
		if ($this->entityId === 'STATUS') // lead status
		{
			$maxStatusIdLength = $permissionFieldLength  - mb_strlen('STATUS_ID');
			$useValidationRegex = true;
		}
		if ($this->entityId === 'QUOTE_STATUS') // quote status
		{
			$maxStatusIdLength = $permissionFieldLength - mb_strlen('QUOTE_ID');
			$useValidationRegex = true;
		}
		if (\Bitrix\Crm\Category\DealCategory::convertFromStatusEntityID($this->entityId) >= 0) // deal status
		{
			$maxStatusIdLength = $permissionFieldLength - mb_strlen('STAGE_ID');
			$useValidationRegex = true;
		}

		if(mb_strlen($statusId) > $maxStatusIdLength)
		{
			$result->addError(new \Bitrix\Main\Error(GetMessage(
				'CRM_STATUS_ERR_STATUS_ID_MAX_LENGTH_EXCEEDED',
				array('#MAX_LENGTH#' => (string)$maxStatusIdLength)
			)));
			return $result;
		}

		if ($useValidationRegex && !preg_match('/^[0-9A-Z_-]+$/i', $statusId))
		{
			$result->addError(new \Bitrix\Main\Error(GetMessage('CRM_STATUS_ERR_INCORRECT_SYMBOLS')));
			return $result;
		}

		if ($this->CheckStatusId($statusIdWithPrefix))
		{
			$result->addError(new \Bitrix\Main\Error(GetMessage('CRM_STATUS_ERR_DUPLICATE_STATUS_ID')));
			return $result;
		}
		return $result;
	}

	/**
	 * Checks if there are entities with the specified status.
	 *
	 * @param mixed $statusId Status id.
	 * @return bool
	 */
	public function existsEntityWithStatus($statusId): bool
	{
		if(!is_string($statusId))
		{
			return false;
		}
		$entityTypes = self::GetEntityTypes();

		if (array_key_exists($this->entityId, $entityTypes))
		{
			if ($this->entityId === 'STATUS')
			{
				return CCrmLead::existsEntityWithStatus($statusId);
			}
			if ($this->entityId === 'INVOICE_STATUS')
			{
				return CCrmInvoice::existsEntityWithStatus($statusId);
			}
			if ($this->entityId === 'QUOTE_STATUS')
			{
				return CCrmQuote::existsEntityWithStatus($statusId);
			}
			if (mb_strpos($this->entityId, 'DEAL_STAGE') === 0)
			{
				return CCrmDeal::existsEntityWithStatus($statusId);
			}
		}

		return false;
	}

	/**
	 * Returns next available integer for STATUS_ID.
	 * @deprecated
	 *
	 * @return int
	 */
	public function GetNextStatusId(): int
	{
		global $DB;

		$prefix = $this->getStatusPrefix();
		if(!empty($prefix))
		{
			$offset = mb_strlen($prefix) + 2;
			$castedStatus = $DB->ToNumber("SUBSTRING(STATUS_ID, {$offset})");
			$sql = "SELECT SUBSTRING(STATUS_ID, {$offset}) AS MAX_STATUS_ID FROM b_crm_status WHERE ENTITY_ID = '{$DB->ForSql($this->entityId)}' AND $castedStatus > 0 ORDER BY $castedStatus DESC LIMIT 1";
		}
		else
		{
			$castedStatus = $DB->ToNumber("STATUS_ID");
			$sql = "SELECT STATUS_ID AS MAX_STATUS_ID FROM b_crm_status WHERE ENTITY_ID = '{$DB->ForSql($this->entityId)}' AND $castedStatus > 0 ORDER BY $castedStatus DESC LIMIT 1";
		}

		$res = $DB->Query($sql);
		$fields = is_object($res) ? $res->Fetch() : array();

		return (isset($fields['MAX_STATUS_ID']) ? intval($fields['MAX_STATUS_ID']) : 0) + 1;
	}

	public function getRandomStatusId(): string
	{
		$statusId = static::PREFIX_USER_CREATED . '_' .  mb_strtoupper(\Bitrix\Main\Security\Random::getString(6));
		$prefix = $this->getStatusPrefix();
		if (!empty($prefix))
		{
			$statusId = static::addKnownPrefixToStatusId($statusId, $prefix);
		}

		return $statusId;
	}

	public function getUniqueRandomStatusId(int $triesLeft = 5): string
	{
		if ($triesLeft <= 0)
		{
			throw new SystemException('Could not generate unique random status id');
		}
		$triesLeft--;

		$statusId = $this->getRandomStatusId();

		if (StatusTable::getCount([
			'=STATUS_ID' => $statusId,
		]) > 0)
		{
			return static::getUniqueRandomStatusId($triesLeft);
		}

		return $statusId;
	}

	/**
	 * Load list of records for $entityId from database.
	 *
	 * @param string $entityId
	 * @return array
	 */
	public static function loadStatusesByEntityId(string $entityId): array
	{
		return StatusTable::loadStatusesByEntityId($entityId);
	}

	/**
	 * Retrieve list of records for $entityId from static cache, if exists and allowed.
	 *
	 * @param string $entityId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function GetStatus($entityId): array
	{
		if(!is_string($entityId))
		{
			return [];
		}

		return StatusTable::getStatusesByEntityId($entityId);
	}

	/**
	 * @deprecated
	 * @param $statusId
	 * @return mixed|string
	 */
	public static function GetEntityID($statusId)
	{
		global $DB;
		$res = $DB->Query("SELECT ENTITY_ID FROM b_crm_status WHERE STATUS_ID ='{$DB->ForSql($statusId)}'");
		$fields = is_object($res) ? $res->Fetch() : array();
		return isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
	}

	/**
	 * Returns first status for $entityId.
	 *
	 * @param string $entityId
	 * @return string|null
	 */
	public static function GetFirstStatusID($entityId): ?string
	{
		if(!is_string($entityId))
		{
			return null;
		}
		$arStatusList = self::GetStatusList($entityId);

		return !empty($arStatusList) ? key($arStatusList) : null;
	}

	/**
	 * Returns flat list of statuses for $entityId, where key is STATUS_ID and value is NAME.
	 *
	 * @param string $entityId
	 * @return array
	 */
	public static function GetStatusList($entityId): array
	{
		if(!is_string($entityId))
		{
			return [];
		}

		return StatusTable::getStatusesList($entityId);
	}

	/**
	 * Returns flat list of statuses for $entityId, where key is STATUS_ID and value is html filtered NAME.
	 *
	 * @param string $entityId
	 * @return array
	 */
	public static function GetStatusListEx($entityId, bool $isEscapeName = true): array
	{
		if(!is_string($entityId))
		{
			return [];
		}

		$escapedList = [];
		foreach (static::GetStatusList($entityId) as $statusId => $statusName)
		{
			if ($isEscapeName)
			{
				$statusName = htmlspecialcharsbx($statusName);
			}

			$escapedList[htmlspecialcharsbx($statusId)] = $statusName;
		}

		return $escapedList;
	}

	/**
	 * Returns status by $ID.
	 *
	 * @param int $ID
	 * @return false|array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function GetStatusById($ID)
	{
		$ID = (int) $ID;
		$statuses = self::GetStatus($this->entityId);
		foreach($statuses as $item)
		{
			$currentID = isset($item['ID']) ? (int)$item['ID'] : 0;
			if($currentID === $ID)
			{
				return $item;
			}
		}

		return false;
	}

	/**
	 * Returns status by STATUS_ID.
	 *
	 * @param string $statusId
	 * @return false|array
	 */
	public function GetStatusByStatusId($statusId)
	{
		if(!is_string($statusId))
		{
			return false;
		}
		$arStatus = self::GetStatus($this->entityId);

		return $arStatus[$statusId] ?? false;
	}

	private function CheckFields(array $arFields, bool $bCheckStatusId = true): bool
	{
		$aMsg = [];

		if (
			$bCheckStatusId
			&& is_set($arFields, 'STATUS_ID')
		)
		{
			$validationResult = $this->validateStatusId($arFields['STATUS_ID']);
			if (!$validationResult->isSuccess())
			{
				$aMsg[] = ['id' => 'STATUS_ID', 'text' => implode(', ', $validationResult->getErrorMessages())];
			}
		}

		if(!empty($aMsg))
		{
			$messages = [];
			foreach($aMsg as $msg)
			{
				$messages[] = $msg['text'];
			}
			$this->LAST_ERROR = implode("<br/>", $messages);

			$e = new CAdminException($aMsg);
			$GLOBALS['APPLICATION']->ThrowException($e);

			return false;
		}

		return true;
	}

	public function GetLastError(): ?string
	{
		return $this->LAST_ERROR;
	}

	//region default statuses
	/**
	 * Installs default statuses for $entityId.
	 * If $statusId is specified - only this status will be installed.
	 *
	 * @param string $entityId
	 * @param string|null $statusId
	 */
	public static function InstallDefault($entityId, $statusId = null): void
	{
		if(!is_string($entityId))
		{
			return;
		}

		$items = array();
		$entityId = mb_strtoupper($entityId);
		if($entityId === 'STATUS')
		{
			$items = self::GetDefaultLeadStatuses();
		}
		elseif($entityId === 'DEAL_STAGE')
		{
			$items = self::GetDefaultDealStages();
		}
		elseif($entityId === 'SOURCE')
		{
			$items = self::GetDefaultSources();
		}
		elseif($entityId === 'CONTACT_TYPE')
		{
			$items = self::GetDefaultContactTypes();
		}
		elseif($entityId === 'COMPANY_TYPE')
		{
			$items = self::GetDefaultCompanyTypes();
		}
		elseif($entityId === 'QUOTE_STATUS')
		{
			$items = self::GetDefaultQuoteStatuses();
		}
		elseif($entityId === 'EMPLOYEES')
		{
			$items = self::GetDefaultEmployees();
		}
		elseif($entityId === 'CALL_LIST')
		{
			$items = self::GetDefaultCallListStates();
		}
		elseif($entityId === 'INVOICE_STATUS')
		{
			$items = self::GetDefaultInvoiceStatuses();
		}

		if ($statusId !== null && is_string($statusId))
		{
			$items = array_filter($items, static function($item) use ($statusId) {
				return $statusId === $item['STATUS_ID'];
			});
		}

		self::BulkCreate($entityId, $items);
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function GetDefaultLeadStatuses(): array
	{
		return [
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_NEW'),
				'STATUS_ID' => 'NEW',
				'SORT' => 10,
				'SYSTEM' => 'Y',
				'COLOR' => '#39A8EF',
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_IN_PROCESS'),
				'STATUS_ID' => 'IN_PROCESS',
				'SORT' => 20,
				'SYSTEM' => 'N',
				'COLOR' => '#2FC6F6',
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_PROCESSED'),
				'STATUS_ID' => 'PROCESSED',
				'SORT' => 30,
				'SYSTEM' => 'N',
				'COLOR' => '#55D0E0',
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_CONVERTED'),
				'STATUS_ID' => 'CONVERTED',
				'SORT' => 40,
				'SYSTEM' => 'Y',
				'COLOR' => '#7BD500',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::SUCCESS,
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_STATUS_JUNK'),
				'STATUS_ID' => 'JUNK',
				'SORT' => 50,
				'SYSTEM' => 'Y',
				'COLOR' => '#FF5752',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::FAILURE,
			]
		];
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function GetDefaultSources(): array
	{
		return [
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_CALL'),
				'STATUS_ID' => 'CALL',
				'SORT' => 10,
				'SYSTEM' => 'Y'
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_EMAIL'),
				'STATUS_ID' => 'EMAIL',
				'SORT' => 20,
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_WEB'),
				'STATUS_ID' => 'WEB',
				'SORT' => 30
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_ADVERTISING'), //!NEW
				'STATUS_ID' => 'ADVERTISING',
				'SORT' => 40
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_PARTNER'),
				'STATUS_ID' => 'PARTNER',
				'SORT' => 50,
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_RECOMMENDATION'), //!NEW
				'STATUS_ID' => 'RECOMMENDATION',
				'SORT' => 60,
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_TRADE_SHOW'),
				'STATUS_ID' => 'TRADE_SHOW',
				'SORT' => 70,
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_WEBFORM'),
				'STATUS_ID' => 'WEBFORM',
				'SORT' => 75,
				'SYSTEM' => 'Y'
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_CALLBACK'),
				'STATUS_ID' => 'CALLBACK',
				'SORT' => 77,
				'SYSTEM' => 'Y'
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_RC_GENERATOR'),
				'STATUS_ID' => 'RC_GENERATOR',
				'SORT' => 78,
				'SYSTEM' => 'Y'
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_STORE'),
				'STATUS_ID' => 'STORE',
				'SORT' => 79,
				'SYSTEM' => 'Y'
			],
			[
				'NAME' => GetMessage('CRM_STATUS_TYPE_SOURCE_OTHER'),
				'STATUS_ID' => 'OTHER',
				'SORT' => 80,
			]
		];
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function GetDefaultContactTypes(): array
	{
		return [
			[
				'NAME' => GetMessage('CRM_CONTACT_TYPE_CLIENT'),
				'STATUS_ID' => 'CLIENT',
				'SORT' => 10
			],
			[
				'NAME' => GetMessage('CRM_CONTACT_TYPE_SUPPLIER'),
				'STATUS_ID' => 'SUPPLIER',
				'SORT' => 20
			],
			[
				'NAME' => GetMessage('CRM_CONTACT_TYPE_PARTNER'),
				'STATUS_ID' => 'PARTNER',
				'SORT' => 30
			],
			[
				'NAME' => GetMessage('CRM_CONTACT_TYPE_OTHER'),
				'STATUS_ID' => 'OTHER',
				'SORT' => 40
			]
		];
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function GetDefaultCompanyTypes(): array
	{
		return array(
			array(
				'NAME' => GetMessage('CRM_COMPANY_TYPE_CUSTOMER'),
				'STATUS_ID' => 'CUSTOMER',
				'SORT' => 10
			),
			array(
				'NAME' => GetMessage('CRM_COMPANY_TYPE_SUPPLIER'),
				'STATUS_ID' => 'SUPPLIER',
				'SORT' => 20
			),
			array(
				'NAME' => GetMessage('CRM_COMPANY_TYPE_COMPETITOR'),
				'STATUS_ID' => 'COMPETITOR',
				'SORT' => 30
			),
			array(
				'NAME' => GetMessage('CRM_COMPANY_TYPE_PARTNER'),
				'STATUS_ID' => 'PARTNER',
				'SORT' => 40
			),
			array(
				'NAME' => GetMessage('CRM_COMPANY_TYPE_OTHER'),
				'STATUS_ID' => 'OTHER',
				'SORT' => 50
			)
		);
	}

	/**
	 * @param string $namespace
	 * @return array
	 * @internal
	 */
	public static function GetDefaultDealStages($namespace = ''): array
	{
		$prefix = empty($namespace) ? '' :  "{$namespace}:";

		return [
			[
				'NAME' => GetMessage('CRM_DEAL_STAGE_NEW'),
				'STATUS_ID' => "{$prefix}NEW",
				'SORT' => 10,
				'SYSTEM' => 'Y',
				'COLOR' => '#39A8EF',
			],
			[
				'NAME' => GetMessage('CRM_DEAL_STAGE_PREPARATION'),
				'STATUS_ID' => "{$prefix}PREPARATION",
				'SORT' => 20,
				'SYSTEM' => 'N',
				'COLOR' => '#2FC6F6',
			],
			[
				'NAME' => GetMessage('CRM_DEAL_STAGE_PREPAYMENT_INVOICE'),
				'STATUS_ID' => "{$prefix}PREPAYMENT_INVOICE", //PRELIMINARY_INVOICE
				'SORT' => 30,
				'SYSTEM' => 'N',
				'COLOR' => '#55D0E0',
			],
			[
				'NAME' => GetMessage('CRM_DEAL_STAGE_EXECUTING'),
				'STATUS_ID' => "{$prefix}EXECUTING",
				'SORT' => 40,
				'SYSTEM' => 'N',
				'COLOR' => '#47E4C2',
			],
			[
				'NAME' => GetMessage('CRM_DEAL_STAGE_FINAL_INVOICE'),
				'STATUS_ID' => "{$prefix}FINAL_INVOICE",
				'SORT' => 50,
				'SYSTEM' => 'N',
				'COLOR' => '#FFA900',
			],
			[
				'NAME' => GetMessage('CRM_DEAL_STAGE_WON'),
				'STATUS_ID' => "{$prefix}WON",
				'SORT' => 60,
				'SYSTEM' => 'Y',
				'COLOR' => '#7BD500',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::SUCCESS,
			],
			[
				'NAME' => GetMessage('CRM_DEAL_STAGE_LOSE'),
				'STATUS_ID' => "{$prefix}LOSE",
				'SORT' => 70,
				'SYSTEM' => 'Y',
				'COLOR' => '#FF5752',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::FAILURE,
			],
			[
				'NAME' => GetMessage('CRM_DEAL_STAGE_APOLOGY'),
				'STATUS_ID' => "{$prefix}APOLOGY",
				'SORT' => 80,
				'SYSTEM' => 'N',
				'COLOR' => '#FF5752',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::FAILURE,
			]
		];
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function GetDefaultQuoteStatuses(): array
	{
		return [
			[
				'NAME' => GetMessage('CRM_QUOTE_STATUS_DRAFT'),
				'STATUS_ID' => 'DRAFT',
				'SORT' => 10,
				'SYSTEM' => 'Y',
				'COLOR' => '#39A8EF',
			],
			[
				'NAME' => GetMessage('CRM_QUOTE_STATUS_SENT'),
				'STATUS_ID' => 'SENT',
				'SORT' => 20,
				'SYSTEM' => 'N',
				'COLOR' => '#2FC6F6',
			],
			[
				'NAME' => GetMessage('CRM_QUOTE_STATUS_APPROVED'),
				'STATUS_ID' => 'APPROVED',
				'SORT' => 30,
				'SYSTEM' => 'Y',
				'COLOR' => '#7BD500',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::SUCCESS,
			],
			[
				'NAME' => GetMessage('CRM_QUOTE_STATUS_DECLAINED'),
				'STATUS_ID' => 'DECLAINED',
				'SORT' => 40,
				'SYSTEM' => 'Y',
				'COLOR' => '#FF5752',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::FAILURE,
			],
			[
				'NAME' => GetMessage('CRM_QUOTE_STATUS_APOLOGY'),
				'STATUS_ID' => 'APOLOGY',
				'SORT' => 50,
				'SYSTEM' => 'N',
				'COLOR' => '#FF5752',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::FAILURE,
			]
		];
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function GetDefaultInvoiceStatuses(): array
	{
		return [
			[
				'NAME' => GetMessage('CRM_INVOICE_STATUS_NEW'),
				'STATUS_ID' => 'N',
				'SORT' => 100,
				'SYSTEM' => 'Y',
				'COLOR' => '#39A8EF',
			],
			[
				'NAME' => GetMessage('CRM_INVOICE_STATUS_SENT'),
				'STATUS_ID' => 'S',
				'SORT' => 110,
				'SYSTEM' => 'N',
				'COLOR' => '#2FC6F6',
			],
			[
				'NAME' => GetMessage('CRM_INVOICE_STATUS_PAID'),
				'STATUS_ID' => 'P',
				'SORT' => 130,
				'SYSTEM' => 'Y',
				'COLOR' => '#7BD500',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::SUCCESS,
			],
			[
				'NAME' => GetMessage('CRM_INVOICE_STATUS_REFUSED'),
				'STATUS_ID' => 'D',
				'SORT' => 140,
				'SYSTEM' => 'Y',
				'COLOR' => '#FF5752',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::FAILURE,
			]
		];
	}

	public static function GetDefaultSmartDocumentStatuses(): array
	{
		return [
			[
				'NAME' => GetMessage('CRM_SMART_DOCUMENT_STATUS_DRAFT'),
				'STATUS_ID' => 'DRAFT',
				'SORT' => 10,
				'SYSTEM' => 'Y',
				'COLOR' => '#00A9F4',
			],
			[
				'NAME' => GetMessage('CRM_SMART_DOCUMENT_STATUS_PROCESSING'),
				'STATUS_ID' => 'PROCESSING',
				'SORT' => 20,
				'COLOR' => '#00C9FA',
			],
			[
				'NAME' => GetMessage('CRM_SMART_DOCUMENT_STATUS_SENT'),
				'STATUS_ID' => 'SENT',
				'SORT' => 30,
				'COLOR' => '#00D3E2',
			],
			[
				'NAME' => GetMessage('CRM_SMART_DOCUMENT_STATUS_SEMISIGNED'),
				'STATUS_ID' => 'SEMISIGNED',
				'SORT' => 40,
				'COLOR' => '#FEA300',
			],
			[
				'NAME' => GetMessage('CRM_SMART_DOCUMENT_STATUS_SIGNED'),
				'STATUS_ID' => 'SIGNED',
				'SORT' => 50,
				'COLOR' => '#47E4C2',
			],
			[
				'NAME' => GetMessage('CRM_SMART_DOCUMENT_STATUS_ARCHIVE'),
				'STATUS_ID' => 'ARCHIVE',
				'SORT' => 60,
				'COLOR' => '#7BD500',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::SUCCESS,
			],
			[
				'NAME' => GetMessage('CRM_SMART_DOCUMENT_STATUS_NOTSIGNED'),
				'STATUS_ID' => 'NOTSIGNED',
				'SORT' => 70,
				'SYSTEM' => 'Y',
				'COLOR' => '#FF5752',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::FAILURE,
			]
		];
	}

	public static function GetDefaultSmartB2eDocumentStatuses(): array
	{
		return [
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_DRAFT'),
				'STATUS_ID' => 'DRAFT',
				'SORT' => 10,
				'SYSTEM' => 'Y',
				'COLOR' => '#00A9F4',
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_COORDINATION'),
				'STATUS_ID' => 'COORDINATION',
				'SORT' => 20,
				'COLOR' => '#00C9FA',
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_FILLING'),
				'STATUS_ID' => 'FILLING',
				'SORT' => 30,
				'COLOR' => '#00C4FB',
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_SIGNING'),
				'STATUS_ID' => 'SIGNING',
				'SORT' => 40,
				'COLOR' => '#00D3E2',
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_COMPLETED'),
				'STATUS_ID' => 'COMPLETED',
				'SORT' => 50,
				'COLOR' => '#FEA300',
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_ARCHIVE'),
				'STATUS_ID' => 'ARCHIVE',
				'SORT' => 60,
				'COLOR' => '#7BD500',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::SUCCESS,
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_FAILURE'),
				'STATUS_ID' => 'FAILURE',
				'SORT' => 70,
				'COLOR' => '#FF5752',
				'SYSTEM' => 'Y',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::FAILURE,
			],
		];
	}

	public static function GetDefaultSmartB2eEmployeeDocumentStatuses(): array
	{
		return [
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_EMPLOYEE_DRAFT'),
				'STATUS_ID' => 'EMPLOYEE_DRAFT',
				'SORT' => 10,
				'SYSTEM' => 'Y',
				'COLOR' => '#00A9F4',
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_EMPLOYEE_COORDINATION'),
				'STATUS_ID' => 'EMPLOYEE_COORDINATION',
				'SORT' => 20,
				'COLOR' => '#00C9FA',
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_EMPLOYEE_SIGNING'),
				'STATUS_ID' => 'EMPLOYEE_SIGNING',
				'SORT' => 30,
				'COLOR' => '#00D3E2',
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_EMPLOYEE_COMPLETED'),
				'STATUS_ID' => 'EMPLOYEE_COMPLETED',
				'SORT' => 40,
				'COLOR' => '#FEA300',
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_EMPLOYEE_ARCHIVE'),
				'STATUS_ID' => 'ARCHIVE',
				'SORT' => 50,
				'COLOR' => '#7BD500',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::SUCCESS,
			],
			[
				'NAME' => GetMessage('CRM_SMART_B2E_DOCUMENT_STATUS_EMPLOYEE_FAILURE'),
				'STATUS_ID' => 'FAILURE',
				'SORT' => 60,
				'COLOR' => '#FF5752',
				'SYSTEM' => 'Y',
				'SEMANTICS' => \Bitrix\Crm\PhaseSemantics::FAILURE,
			],
		];
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function GetDefaultCallListStates(): array
	{
		return [
			[
				'NAME' => GetMessage('CRM_CALL_LIST_IN_WORK'),
				'STATUS_ID' => 'IN_WORK',
				'SORT' => 10,
				'SYSTEM' => 'Y'
			],
			[
				'NAME' => GetMessage('CRM_CALL_LIST_SUCCESS'),
				'STATUS_ID' => 'SUCCESS',
				'SORT' => 20,
				'SYSTEM' => 'Y'
			],
			[
				'NAME' => GetMessage('CRM_CALL_LIST_WRONG_NUMBER'),
				'STATUS_ID' => 'WRONG_NUMBER',
				'SORT' => 30,
				'SYSTEM' => 'Y'
			],
			[
				'NAME' => GetMessage('CRM_CALL_LIST_STOP_CALLING'),
				'STATUS_ID' => 'STOP_CALLING',
				'SORT' => 40,
				'SYSTEM' => 'Y'
			],
		];
	}

	/**
	 * @internal
	 * @return array
	 */
	public static function GetDefaultEmployees(): array
	{
		return array(
			array(
				'NAME' => GetMessage('CRM_EMPLOYEES_1'),
				'STATUS_ID' => 'EMPLOYEES_1',
				'SORT' => 10,
				'SYSTEM' => 'Y'
			),
			array(
				'NAME' => GetMessage('CRM_EMPLOYEES_2'),
				'STATUS_ID' => 'EMPLOYEES_2',
				'SORT' => 20,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_EMPLOYEES_3'),
				'STATUS_ID' => 'EMPLOYEES_3',
				'SORT' => 30,
				'SYSTEM' => 'N'
			),
			array(
				'NAME' => GetMessage('CRM_EMPLOYEES_4'),
				'STATUS_ID' => 'EMPLOYEES_4',
				'SORT' => 40,
				'SYSTEM' => 'N'
			)
		);
	}

	public static function GetDefaultLeadStatusName($statusID): string
	{
		return Loc::getMessage("CRM_STATUS_TYPE_STATUS_{$statusID}") ?? "[{$statusID}]";
	}

	public static function GetDefaultDealStageName($stageID): string
	{
		return Loc::getMessage("CRM_DEAL_STAGE_{$stageID}") ?? "[{$stageID}]";
	}

	public static function BulkCreate($entityId, array $items): void
	{
		$entity = new CCrmStatus($entityId);
		foreach($items as $item)
		{
			if(!$entity->CheckStatusId($item['STATUS_ID']))
			{
				$entity->Add($item);
			}
		}
	}
	//endregion

	/**
	 * Delete all statuses by $entityId
	 * @param string $entityId
	 */
	public static function Erase($entityId): void
	{
		if(!is_string($entityId))
		{
			return;
		}
		$entity = new CCrmStatus($entityId);
		$entity->DeleteAll();
	}

	/**
	 * Delete all statuses
	 */
	public function DeleteAll(): void
	{
		$statuses = static::GetStatus($this->entityId);
		foreach($statuses as $status)
		{
			$this->Delete($status['ID']);
		}
	}

	/**
	 * @deprecated
	 * @param $entityId
	 * @param $enabled
	 */
	public static function MarkAsEnabled($entityId, $enabled): void
	{
	}

	/**
	 * @deprecated
	 * @param $entityId
	 */
	public static function CheckIfEnabled($entityId)
	{
	}

	//region semantic info
	public static function GetLeadStatusSemanticInfo(): array
	{
		return [
			'START_FIELD' => 'NEW',
			'FINAL_SUCCESS_FIELD' => 'CONVERTED',
			'FINAL_UNSUCCESS_FIELD' => 'JUNK',
			'FINAL_SORT' => 0
		];
	}

	public static function GetDealStageSemanticInfo($namespace = ''): array
	{
		$prefix = is_string($namespace) && $namespace !== '' ? "{$namespace}:" : '';

		return [
			'START_FIELD' => "{$prefix}NEW",
			'FINAL_SUCCESS_FIELD' => "{$prefix}WON",
			'FINAL_UNSUCCESS_FIELD' => "{$prefix}LOSE",
			'FINAL_SORT' => 0
		];
	}

	public static function GetQuoteStatusSemanticInfo(): array
	{
		return [
			'START_FIELD' => 'DRAFT',
			'FINAL_SUCCESS_FIELD' => 'APPROVED',
			'FINAL_UNSUCCESS_FIELD' => 'DECLAINED',
			'FINAL_SORT' => 0
		];
	}

	public static function GetInvoiceStatusSemanticInfo(): array
	{
		return [
			'START_FIELD' => 'N',
			'FINAL_SUCCESS_FIELD' => 'P',
			'FINAL_UNSUCCESS_FIELD' => 'D',
			'FINAL_SORT' => 0
		];
	}
	//endregion
	//region user permissions
	public static function CheckCreatePermission(): bool
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();

		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckUpdatePermission($ID): bool
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckDeletePermission($ID): bool
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	public static function CheckReadPermission($ID = 0): bool
	{
		$perms = CCrmPerms::GetCurrentUserPermissions();
		return $perms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');
	}
	//endregion
}

?>
