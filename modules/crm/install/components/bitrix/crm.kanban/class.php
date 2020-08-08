<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Main\Config\Option;
use \Bitrix\Crm\Tracking;
use \Bitrix\Main\Result;
use \Bitrix\Main\Entity\AddResult;
use \Bitrix\Main\Error;
use \Bitrix\Crm\Order;
use \Bitrix\Crm\Category\DealCategory;
use \Bitrix\Crm\PhaseSemantics;
use \Bitrix\Crm\Attribute\FieldAttributeManager;
use \Bitrix\Crm\Color\PhaseColorSchemeManager;
use \Bitrix\Crm\Observer\Entity\ObserverTable;
use \Bitrix\Crm\Entity\EntityEditorConfigScope;
use \Bitrix\Crm\Search\SearchEnvironment;
use \Bitrix\Crm\Tracking\Internals\TraceChannelTable;
use \Bitrix\Crm\Tracking\Channel;
use \Bitrix\Sale;
use \Bitrix\Rest\Marketplace;

class CrmKanbanComponent extends \CBitrixComponent
{
	protected $type = '';
	protected $fieldSum = '';
	protected $currency = '';
	protected $statusKey = 'STATUS_ID';
	protected $categoryId = null;
	protected $formatLang = 'ru';
	protected $uid = 0;
	protected $blockPage = 1;
	protected $blockSize = 20;
	protected $maxSortSize = 1000;
	protected $application = null;
	protected $allowSemantics = array();
	protected $allowStages = array();
	protected $types = array();
	protected $contact = array();
	protected $company = array();
	protected $fmTypes = array();
	protected $usersIds = array();
	protected $additionalSelect = array();
	protected $additionalEdit = array();
	protected $additionalTypes = array();
	protected $items = array();
	protected $requiredFields = array();
	protected $schemeFields = array();
	protected $userFields = array();
	protected $dateFormats = array(
		'short' => array(
			'en' => 'F j',
			'de' => 'j. F',
			'ru' => 'j F'
		),
		'full' => array(
			'en' => 'F j, Y',
			'de' => 'j. F Y',
			'ru' => 'j F Y'
		)
	);
	protected $avatarSize = array('width' => 38, 'height' => 38);
	protected $allowedFMtypes = array('phone', 'email', 'im', 'web');
	protected $pathMarkers = array('#lead_id#', '#contact_id#', '#company_id#', '#deal_id#', '#quote_id#', '#invoice_id#', '#order_id#');
	protected $disableMoreFields = array(
		'ACTIVE_TIME_PERIOD', 'PRODUCT_ROW_PRODUCT_ID', 'COMPANY_ID', 'COMPANY_TITLE',
		'CONTACT_ID', 'CONTACT_FULL_NAME', 'EVENT_ID', 'EVENT_DATE', 'ACTIVITY_COUNTER',
		'IS_RETURN_CUSTOMER', 'IS_NEW', 'IS_REPEATED_APPROACH', 'CURRENCY_ID', 'WEBFORM_ID',
		'COMMUNICATION_TYPE', 'HAS_PHONE', 'HAS_EMAIL', 'STAGE_SEMANTIC_ID', 'CATEGORY_ID',
		'STATUS_ID', 'STATUS_SEMANTIC_ID', 'STATUS_CONVERTED', 'MODIFY_BY_ID', 'TRACKING_CHANNEL_CODE',
		'ADDRESS', 'ADDRESS_2', 'ADDRESS_CITY', 'ADDRESS_REGION', 'ADDRESS_PROVINCE',
		'ADDRESS', 'ADDRESS_POSTAL_CODE', 'ADDRESS_COUNTRY', 'CREATED_BY_ID', 'ORIGINATOR_ID', 'ORIGINATOR_ID',
		'UTM_SOURCE', 'UTM_MEDIUM', 'UTM_CAMPAIGN', 'UTM_CONTENT', 'UTM_TERM',
		'STAGE_ID_FROM_HISTORY', 'STAGE_ID_FROM_SUPPOSED_HISTORY', 'STAGE_SEMANTIC_ID_FROM_HISTORY'
	);
	protected $usersIdFields = array(
		'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID', 'RESPONSIBLE_ID', 'USER'
	);
	protected $selectPresets = array(
		'lead' => array('ID', 'STATUS_ID', 'TITLE', 'DATE_CREATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID', 'IS_RETURN_CUSTOMER', 'ASSIGNED_BY'),
		'deal' => array('ID', 'STAGE_ID', 'TITLE', 'DATE_CREATE', 'BEGINDATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'EXCH_RATE', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'IS_REPEATED_APPROACH', 'IS_RETURN_CUSTOMER', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID', 'ASSIGNED_BY', 'ORDER_STAGE'),
		'quote' => array('ID', 'STATUS_ID', 'TITLE', 'DATE_CREATE', 'BEGINDATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID', 'ASSIGNED_BY'),
		'invoice' => array('ID', 'STATUS_ID', 'DATE_INSERT', 'DATE_INSERT_FORMAT', 'PAY_VOUCHER_DATE', 'DATE_BILL', 'ORDER_TOPIC', 'PRICE', 'CURRENCY', 'UF_CONTACT_ID', 'UF_COMPANY_ID', 'RESPONSIBLE_ID'),
		'order' => array('ID', 'ACCOUNT_NUMBER', 'STATUS_ID', 'DATE_INSERT', 'PAY_VOUCHER_DATE', 'DATE_PAYED', 'ORDER_TOPIC', 'PRICE', 'CURRENCY', 'RESPONSIBLE_ID')
	);
	protected $exclusiveFieldsReturnCustomer = array(
		'HONORIFIC' => true,
		'LAST_NAME' => true,
		'NAME' => true,
		'SECOND_NAME' => true,
		'BIRTHDATE' => true,
		'POST' => true,
		'COMPANY_TITLE' => true,
		'ADDRESS' => true,
		'PHONE' => true,
		'EMAIL' => true,
		'WEB' => true,
		'IM' => true
	);

	/**
	 * Init class' vars.
	 * @return bool
	 */
	protected function init()
	{
		Loc::loadMessages(__FILE__);

		if (!Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_KANBAN_CRM_NOT_INSTALLED'));
			return false;
		}
		if (!\CCrmPerms::IsAccessEnabled())
		{
			return false;
		}

		$this->allowSemantics = array(
			PhaseSemantics::PROCESS
		);

		//type and types
		$this->types = array(
			'lead' => \CCrmOwnerType::LeadName,
			'deal' => \CCrmOwnerType::DealName,
			'quote' => \CCrmOwnerType::QuoteName,
			'invoice' => \CCrmOwnerType::InvoiceName,
			'order' => \CCrmOwnerType::OrderName,
		);
		$this->fmTypes = array(
			'EMAIL_WORK' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_WORK'),
			'EMAIL_HOME' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_HOME'),
			'EMAIL_OTHER' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_OTHER'),
			'PHONE_MOBILE' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_MOBILE'),
			'PHONE_WORK' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_WORK'),
			'PHONE_FAX' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_FAX'),
			'PHONE_HOME' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_HOME'),
			'PHONE_PAGER' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_PAGER'),
			'PHONE_OTHER' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_OTHER'),
		);
		$this->type = mb_strtoupper(isset($this->arParams['ENTITY_TYPE'])? $this->arParams['ENTITY_TYPE'] : '');
		if (!$this->type || !in_array($this->type, $this->types))
		{
			return false;
		}
		if (
			$this->type == $this->types['deal'] &&
			isset($this->arParams['EXTRA']['CATEGORY_ID']) &&
			$this->arParams['EXTRA']['CATEGORY_ID'] > 0
		)
		{
			$this->categoryId = (int)$this->arParams['EXTRA']['CATEGORY_ID'];
		}
		$this->arParams['ENTITY_TYPE_CHR'] = array_flip($this->types);
		$this->arParams['ENTITY_TYPE_CHR'] = mb_strtoupper($this->arParams['ENTITY_TYPE_CHR'][$this->type]);
		$this->arParams['ENTITY_TYPE_INT'] = \CCrmOwnerType::resolveID($this->type);
		$this->arParams['ENTITY_PATH'] = $this->getEntityPath($this->type);
		$this->arParams['EDITOR_CONFIG_ID'] = $this->getEditorConfigId();
		$this->arParams['HIDE_CC'] = \CUserOptions::getOption(
			'crm',
			'kanban_cc_hide',
			false
		);
		if (
			(
				$this->type == $this->types['lead'] ||
				$this->type == $this->types['deal']
			) &&
			Loader::includeModule('rest') &&
			is_callable('\Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl')
		)
		{
			$this->arParams['HIDE_REST'] = \CUserOptions::getOption(
				'crm',
				'kanban_rest_hide',
				false
			);
			$this->arParams['REST_DEMO_URL'] = Marketplace\Url::getConfigurationPlacementUrl(
				'crm_'.mb_strtolower($this->type),
				'crm_kanban'
			);
		}
		else
		{
			$this->arParams['HIDE_REST'] = true;
			$this->arParams['REST_DEMO_URL'] = '';
		}
		//additional select-edit fields
		if (
			$this->type == $this->types['lead'] ||
			$this->type == $this->types['deal'] ||
			$this->type == $this->types['order']
		)
		{
			$this->additionalSelect = \CUserOptions::getOption(
				'crm',
				'kanban_select_more_v4_'.
				mb_strtolower($this->type) . '_' . intval($this->categoryId) .
				($this->isEditorConfigCommon() ? '_common' : ''),
				null
			);
			if ($this->additionalSelect === null)
			{
				if ($this->type !== $this->types['order'])
				{
					$this->additionalSelect = [
						'TITLE' => '',
						'OPPORTUNITY' => '',
						'DATE_CREATE' => '',
						'ORDER_STAGE' => '',
						'CLIENT' => '',
						'PROBLEM_NOTIFICATION' => '',
					];
				}
				else
				{
					$this->additionalSelect = [
						'TITLE' => '',
						'PRICE' => '',
						'DATE_INSERT' => '',
						'CLIENT' => ''
					];
				}

			}
			$this->additionalEdit = (array)\CUserOptions::getOption(
				'crm',
				'kanban_edit_more_v4_'.mb_strtolower($this->type) . '_' . intval($this->categoryId),
				array()
			);
		}
		else
		{
			$this->additionalSelect = [
				'TITLE' => '',
				'OPPORTUNITY' => '',
				'DATE_CREATE' => '',
				'CLIENT' => ''
			];
		}
		//redefine price-field
		if ($this->type != $this->types['quote'] && $this->type != $this->types['order'])
		{
			$slots = \Bitrix\Crm\Statistics\StatisticEntryManager::prepareSlotBingingData($this->type .  '_SUM_STATS');
			if (is_array($slots) && isset($slots['SLOT_BINDINGS']) && is_array($slots['SLOT_BINDINGS']))
			{
				foreach ($slots['SLOT_BINDINGS'] as $slot)
				{
					if ($slot['SLOT'] == 'SUM_TOTAL')
					{
						$res = \CUserTypeEntity::getList(
							[],
							['FIELD_NAME' => $slot['FIELD']]
						);
						if ($row = $res->fetch())
						{
							$this->fieldSum = $slot['FIELD'];
							break;
						}
					}
				}
			}
		}
		//init arParams
		if (!isset($this->arParams['~NAME_TEMPLATE']))
		{
			$this->arParams['~NAME_TEMPLATE'] = \Bitrix\Crm\Format\PersonNameFormatter::getFormat();
		}
		else
		{
			$this->arParams['~NAME_TEMPLATE'] = str_replace(
				array('#NOBR#', '#/NOBR#'),
				array('', ''),
				trim($this->arParams['~NAME_TEMPLATE'])
			);
		}
		if (!isset($this->arParams['ADDITIONAL_FILTER']) || !is_array($this->arParams['ADDITIONAL_FILTER']))
		{
			$this->arParams['ADDITIONAL_FILTER'] = array();
		}
		if (isset($this->arParams['PAGE']) && $this->arParams['PAGE'] > 1)
		{
			$this->blockPage = intval($this->arParams['PAGE']);
		}
		if (!isset($this->arParams['ONLY_COLUMNS']) || $this->arParams['ONLY_COLUMNS'] != 'Y')
		{
			$this->arParams['ONLY_COLUMNS'] = 'N';
		}
		if (!isset($this->arParams['ONLY_ITEMS']) || $this->arParams['ONLY_ITEMS'] != 'Y')
		{
			$this->arParams['ONLY_ITEMS'] = 'N';
		}
		if (!isset($this->arParams['EMPTY_RESULT']) || $this->arParams['EMPTY_RESULT'] != 'Y')
		{
			$this->arParams['EMPTY_RESULT'] = 'N';
		}
		if (!isset($this->arParams['IS_AJAX']) || $this->arParams['IS_AJAX'] != 'Y')
		{
			$this->arParams['IS_AJAX'] = 'N';
		}
		if (!isset($this->arParams['GET_AVATARS']) || $this->arParams['GET_AVATARS'] != 'Y')
		{
			$this->arParams['GET_AVATARS'] = 'N';
		}
		if (!isset($this->arParams['EXTRA']) || !is_array($this->arParams['EXTRA']))
		{
			$this->arParams['EXTRA'] = array();
		}
		if (!isset($this->arParams['FORCE_FILTER']) || $this->arParams['FORCE_FILTER'] != 'Y')
		{
			$this->arParams['FORCE_FILTER'] = 'N';
		}
		if ($this->type == $this->types['deal'])
		{
			$this->statusKey = 'STAGE_ID';
		}
		if (LANGUAGE_ID == 'de' || LANGUAGE_ID == 'en')
		{
			$this->formatLang = LANGUAGE_ID;
		}

		$this->uid = \CCrmSecurityHelper::GetCurrentUserID();
		$this->arParams['USER_ID'] = $this->uid;
		if (isset($this->arParams['PATH_TO_IMPORT']))
		{
			$uriImport = new \Bitrix\Main\Web\Uri(
				$this->arParams['PATH_TO_IMPORT']
			);
			if ($this->categoryId)
			{
				$uriImport->addParams(array(
					'category_id' => $this->categoryId
				));
			}
			$uriImport->addParams(array(
				'from' => 'kanban'
			));
			$this->arParams['PATH_TO_IMPORT'] = $uriImport->getUri();
		}
		else
		{
			$this->arParams['PATH_TO_IMPORT'] = '';
		}
		if (!isset($this->arParams['PATH_TO_USER']))
		{
			$this->arParams['PATH_TO_USER'] = '/company/personal/user/#user_id#/';
		}
		if (!isset($this->arParams['PATH_TO_DEAL_KANBANCATEGORY']))
		{
			$this->arParams['PATH_TO_DEAL_KANBANCATEGORY'] = '';
		}
		// for invoice another base currency
		if ($this->type == $this->types['invoice'])
		{
			$this->currency = $this->arParams['CURRENCY'] = \CCrmCurrency::getInvoiceDefault();
		}
		else
		{
			$this->currency = $this->arParams['CURRENCY'] = \CCrmCurrency::GetAccountCurrencyID();
		}

		$this->application = $GLOBALS['APPLICATION'];

		if ($this->type === $this->types['order'])
		{
			Loader::includeModule('sale');
		}

		return true;
	}

	/**
	 * Set error for template.
	 * @param mixed $error
	 * @return void
	 */
	protected function setError($error)
	{
		$this->arResult['ERROR'] = $error;
	}

	/**
	 * Get status's color
	 * @param string $code Code color.
	 * @return array
	 */
	protected function getStatusColor($code)
	{
		$colorScheme = PhaseColorSchemeManager::resolveSchemeByName(
			$code
		);
		if ($colorScheme)
		{
			return $colorScheme->externalize();
		}
		else
		{
			return (array) unserialize(Option::get('crm', $code));
		}
	}

	/**
	 * Switch entity type to letter and returns it.
	 * @param string $entityType Letter entity type.
	 * @return string|null
	 */
	protected function makeEntityLetter(string $entityType): ?string
	{
		$entityType = mb_strtolower($entityType);

		if ($entityType == 'deal')
		{
			return 'D';
		}
		else if ($entityType == 'lead')
		{
			return 'L';
		}
		else if ($entityType == 'contact')
		{
			return 'C';
		}
		else if ($entityType == 'company')
		{
			return 'CO';
		}
		else if ($entityType == 'order')
		{
			return 'O';
		}

		return null;
	}

	/**
	 * Resolves full entity type from symbol.
	 * @param string $entityType Entity type.
	 * @return string
	 */
	protected function resolveEntityLetter(string $entityType): ?string
	{
		if ($entityType == 'D')
		{
			return 'deal';
		}
		else if ($entityType == 'L')
		{
			return 'lead';
		}
		else if ($entityType == 'C')
		{
			return 'contact';
		}
		else if ($entityType == 'CO')
		{
			return 'company';
		}
		else if ($entityType == 'O')
		{
			return 'order';
		}

		return null;
	}

	/**
	 * Gets CRM statuses from database.
	 * @param string $entityId Entity id.
	 * @return array
	 */
	protected function getStatusesDB($entityId)
	{
		static $db = [];

		if ($db)
		{
			return isset($db[$entityId]) ? $db[$entityId] : null;
		}

		$res = \CCrmStatus::getList(
			['SORT' => 'ASC']
		);
		while ($row = $res->fetch())
		{
			if (!isset($db[$row['ENTITY_ID']]))
			{
				$db[$row['ENTITY_ID']] = [];
			}
			$db[$row['ENTITY_ID']][$row['STATUS_ID']] = $row['NAME'];
		}

		return isset($db[$entityId]) ? $db[$entityId] : null;
	}

	/**
	 * Get all CRM statuses, stages, etc.
	 * @param boolean $clear Clear static cache.
	 * @return array
	 */
	protected function getStatuses($clear = false)
	{
		static $statuses = null;

		if ($clear)
		{
			$statuses = null;
		}

		if ($statuses !== null)
		{
			return $statuses;
		}

		$statuses = array();
		$type = $this->type;
		$types = $this->types;
		$semantic = \CCrmStatus::GetEntityTypes();
		$semantic = array_merge($semantic, [Order\OrderStatus::NAME => Order\OrderStatus::getSemanticInfo()]);
		//colors
		$colors = array(
			'QUOTE_STATUS' => ($type == $types['quote']) ? $this->getStatusColor('CONFIG_STATUS_QUOTE_STATUS') : array(),
			'INVOICE_STATUS' => ($type == $types['invoice']) ? $this->getStatusColor('CONFIG_STATUS_INVOICE_STATUS') : array(),
			'ORDER_STATUS' => ($type == $types['order']) ? $this->getStatusColor('CONFIG_STATUS_ORDER_STATUS') : array(),
			'STATUS' => ($type == $types['lead']) ? $this->getStatusColor('CONFIG_STATUS_STATUS') : array(),
		);
		if ($this->categoryId)
		{
			$categories = DealCategory::getList(array(
				'filter' => array(
					'ID' => $this->categoryId
				)
			))->fetchAll();
			foreach ($categories as $cat)
			{
				$colors['DEAL_STAGE_' . $cat['ID']] = $this->getStatusColor(
					'CONFIG_STATUS_DEAL_STAGE_' . $cat['ID']
				);
			}
		}
		elseif ($type == $types['deal'])
		{
			$colors['DEAL_STAGE'] = $this->getStatusColor(
				'CONFIG_STATUS_DEAL_STAGE'
			);
		}

		//custom statuses
		$custom = array_keys($colors);
		$custom[] = 'DEAL_TYPE';
		$custom[] = 'SOURCE';
		$custom = array_flip($custom);
		foreach ($custom as $code => &$value)
		{
			if (empty($value) || !is_array($value))
			{
				unset($custom[$code]);
			}
		}
		unset($value);

		//common get
		$db = array();
		$res = \CCrmStatus::GetList(array('SORT' => 'ASC'));
		while ($row = $res->fetch())
		{
			if (!isset($custom[$row['ENTITY_ID']]))
			{
				$db[] = $row;
			}
		}
		foreach ($custom as $code => $value)
		{
			$db = array_merge($db, $value);
		}
		$orderStatuses = Order\OrderStatus::getListInCrmFormat($clear);
		$db = array_merge($db, $orderStatuses);

		foreach ($db as $row)
		{
			$row['STATUS_ID'] = htmlspecialcharsbx($row['STATUS_ID']);
			if (in_array($row['ENTITY_ID'], array('DEAL_TYPE', 'SOURCE')))
			{
				if ($row['ENTITY_ID'] == 'DEAL_TYPE')
				{
					$row['ENTITY_ID'] = 'TYPE_ID';
				}
				elseif ($row['ENTITY_ID'] == 'SOURCE')
				{
					$row['ENTITY_ID'] = 'SOURCE_ID';
				}
				if (!isset($this->additionalTypes[$row['ENTITY_ID']]))
				{
					$this->additionalTypes[$row['ENTITY_ID']] = array();
				}
				$this->additionalTypes[$row['ENTITY_ID']][$row['STATUS_ID']] = $row['NAME'];
				continue;
			}
			if (!isset($colors[$row['ENTITY_ID']]) || empty($colors[$row['ENTITY_ID']]))
			{
				continue;
			}
			if (!isset($statuses[$row['ENTITY_ID']]))
			{
				$statuses[$row['ENTITY_ID']] = array();
			}
			$row['COLOR'] = isset($colors[$row['ENTITY_ID']][$row['STATUS_ID']]) &&
							isset($colors[$row['ENTITY_ID']][$row['STATUS_ID']]['COLOR'])
							? htmlspecialcharsbx($colors[$row['ENTITY_ID']][$row['STATUS_ID']]['COLOR'])
							: '';
			if (isset($semantic[$row['ENTITY_ID']]) && isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO'])
				&&
				(
					!isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT']) ||
					$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT'] == 0
				)
				&&
				(
					isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SUCCESS_FIELD']) ||
					isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_UNSUCCESS_FIELD'])
				)
				&&
				(
					$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SUCCESS_FIELD'] == $row['STATUS_ID'] ||
					$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_UNSUCCESS_FIELD'] == $row['STATUS_ID']
				)
			)
			{
				$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT'] = $row['SORT'];
			}
			$statuses[$row['ENTITY_ID']][$row['STATUS_ID']] = $row;
		}

		//range statuses
		foreach ($statuses as $id => &$entity)
		{
			$finalSort = isset($semantic[$id]) && isset($semantic[$id]['SEMANTIC_INFO'])
						&& $semantic[$id]['SEMANTIC_INFO']['FINAL_SORT']
						? $semantic[$id]['SEMANTIC_INFO']['FINAL_SORT']
						: 0;
			foreach ($entity as &$status)
			{
				if ($finalSort == $status['SORT'])
				{
					$status['PROGRESS_TYPE'] = 'WIN';
				}
				elseif ($finalSort < $status['SORT'])
				{
					$status['PROGRESS_TYPE'] = 'LOOSE';
				}
				else
				{
					$status['PROGRESS_TYPE'] = 'PROGRESS';
				}
			}
			unset($status);
		}
		unset($entity);

		foreach ($statuses as $status)
		{
			if (!empty($status))
			{
				$statuses = $status;
			}
		}

		return $statuses;
	}

	/**
	 * Make select from presets.
	 * @return array
	 */
	protected function getSelect()
	{
		global $USER_FIELD_MANAGER;
		static $select = null;

		if ($select === null)
		{
			$types = array_flip($this->types);
			$select = $this->selectPresets[$types[$this->type]];

			if (!empty($this->additionalSelect))
			{
				$additionalFields = array_keys($this->additionalSelect);

				if ($this->type == $this->types['order'])
				{
					$ufSelect = preg_grep( "/^UF_/", $additionalFields);
					$additionalFields = array_intersect($additionalFields,  Order\Order::getAllFields());
					if (!empty($ufSelect))
					{
						$crmUserType = new CCrmUserType($USER_FIELD_MANAGER, Order\Order::getUfId());
						$userOrderFields = $crmUserType->GetFields();
						if (is_array($userOrderFields))
						{
							foreach ($ufSelect as $userFieldName)
							{
								if (isset($userOrderFields[$userFieldName]))
								{
									$additionalFields[] = $userFieldName;
								}
							}
						}
					}

					if (isset($this->additionalSelect['SOURCE_ID']))
					{
						$additionalFields['SOURCE_ID'] = 'TRADING_PLATFORM.TRADING_PLATFORM.NAME';
					}
					if (isset($this->additionalSelect['USER']))
					{
						$additionalFields[] = 'USER_ID';
					}
				}
				$select = array_merge($select, $additionalFields);
			}
			if ($this->fieldSum != '')
			{
				$select[] = $this->fieldSum;
			}
		}

		return $select;
	}

	/**
	 * Make filter from env.
	 * @return array
	 */
	protected function getFilter()
	{
		static $filter = null;

		if ($this->arParams['FORCE_FILTER'] == 'Y')
		{
			return [];
		}

		if ($filter === null)
		{
			if ($this->categoryId !== null)
			{
				\Bitrix\Crm\Kanban\Helper::setCategoryId(
					$this->categoryId
				);
			}
			$filter = array();
			$filterLogic = array(
				'TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'COMMENTS', 'COMPANY_TITLE'
			);
			$filterAddress = array(
				'ADDRESS', 'ADDRESS_2', 'ADDRESS_PROVINCE', 'ADDRESS_REGION', 'ADDRESS_CITY',
				'ADDRESS_COUNTRY', 'ADDRESS_POSTAL_CODE'
			);
			//from main.filter
			$grid = \Bitrix\Crm\Kanban\Helper::getGrid($this->type);
			$gridFilter = \Bitrix\Crm\Kanban\Helper::getFilter($this->type);
			$search = (array)$grid->GetFilter($gridFilter);
			\Bitrix\Crm\UI\Filter\EntityHandler::internalize($gridFilter, $search);
			if (!isset($search['FILTER_APPLIED']))
			{
				$search = array();
			}
			if (!empty($search))
			{
				foreach ($search as $key => $item)
				{
					unset($search[$key]);
					if (in_array(mb_substr($key, 0, 2), array('>=', '<=', '<>')))
					{
						$key = mb_substr($key, 2);
					}
					if (in_array(mb_substr($key, 0, 1), array('=', '<', '>', '@', '!')))
					{
						$key = mb_substr($key, 1);
					}
					$search[$key] = $item;
				}
				foreach ($gridFilter as $key => $item)
				{
					//fill filter by type
					if ($item['type'] == 'date')
					{
						if (isset($search[$key . '_from']) && $search[$key . '_from']!='')
						{
							$filter['>='.$key] = $search[$key . '_from'] . ' 00:00:00';
							if ($this->type !== $this->types['order'])
							{
								$filter['FLT_DATE_EXIST'] = 'Y';
							}
						}
						if (isset($search[$key . '_to']) && $search[$key . '_to']!='')
						{
							$filter['<='.$key] = $search[$key . '_to'] . ' 23:59:00';
							if ($this->type !== $this->types['order'])
							{
								$filter['FLT_DATE_EXIST'] = 'Y';
							}
						}
					}
					elseif ($item['type'] == 'number')
					{
						$fltType = isset($search[$key . '_numsel'])
									? $search[$key . '_numsel']
									: 'exact';
						if ($fltType == 'exact' && isset($search[$key . '_from']))
						{
							$filter[$key] = $search[$key . '_from'];
						}
						else if (
							$fltType == 'range' &&
							isset($search[$key . '_from']) &&
							isset($search[$key . '_to'])
						)
						{
							$filter['>='.$key] = $search[$key . '_from'];
							$filter['<='.$key] = $search[$key . '_to'];
						}
						else if (
							$fltType == 'more' &&
							isset($search[$key . '_from'])
						)
						{
							$filter['>'.$key] = $search[$key . '_from'];
						}
						else if (
							$fltType == 'less' &&
							isset($search[$key . '_to'])
						)
						{
							$filter['<'.$key] = $search[$key . '_to'];
						}
					}
					elseif (isset($search[$key]))
					{
						if (in_array($key, $filterLogic))
						{
							$filter['?' . $key] = $search[$key];
						}
						elseif (in_array($key, $filterAddress))
						{
							$filter['=%' . $key] = $search[$key] . '%';
						}
						elseif ($key == 'STATUS_CONVERTED')
						{
							$filter[$key === 'N' ? 'STATUS_SEMANTIC_ID' : '!STATUS_SEMANTIC_ID'] = 'P';
						}
						elseif ($key == 'ORDER_TOPIC' || $key == 'ACCOUNT_NUMBER')
						{
							$filter['~' . $key] ='%' . $search[$key] . '%';
						}
						elseif (
							$key == 'ENTITIES_LINKS' &&
							(
								$this->type == $this->types['quote'] ||
								$this->type == $this->types['invoice']
							)
						)
						{
							$ownerData = explode('_', $search[$key]);
							if (count($ownerData) > 1)
							{
								$ownerTypeName = \CCrmOwnerType::resolveName(
									\CCrmOwnerType::resolveID($ownerData[0])
								);
								$ownerID = intval($ownerData[1]);
								if (!empty($ownerTypeName) && $ownerID > 0)
								{
									if ($this->type == $this->types['invoice'])
									{
										$filter['UF_' . $ownerTypeName . '_ID'] = $ownerID;
									}
									else
									{
										$filter[$ownerTypeName . '_ID'] = $ownerID;
									}
								}
							}
						}
						elseif (
							($this->type == $this->types['quote']) &&
							in_array(
								$key, array(
									'PRODUCT_ID', 'STATUS_ID', 'COMPANY_ID', 'LEAD_ID',
									'DEAL_ID', 'CONTACT_ID', 'MYCOMPANY_ID'
								)
							)
						)
						{
							$filter['=' . $key] = $search[$key];
						}
						else
						{
							$filter[$key] = $search[$key];
						}
					}
					elseif (
						isset($item['alias']) &&
						isset($search[$item['alias']])
					)
					{
						$filter['=' . $key] = $search[$item['alias']];
					}
				}
				//search index
				if (isset($search['FIND']) && trim($search['FIND']) != '')
				{
					$search['FIND'] = trim($search['FIND']);
					$filter['SEARCH_CONTENT'] = $search['FIND'];
				}
			}
			if (isset($filter['COMMUNICATION_TYPE']))
			{
				if (!is_array($filter['COMMUNICATION_TYPE']))
				{
					$filter['COMMUNICATION_TYPE'] = array($filter['COMMUNICATION_TYPE']);
				}
				if (in_array(\CCrmFieldMulti::PHONE, $filter['COMMUNICATION_TYPE']))
				{
					$filter['HAS_PHONE'] = 'Y';
				}
				if (in_array(\CCrmFieldMulti::EMAIL, $filter['COMMUNICATION_TYPE']))
				{
					$filter['HAS_EMAIL'] = 'Y';
				}
				unset($filter['COMMUNICATION_TYPE']);
			}
			//overdue
			if (
				isset($filter['OVERDUE']) &&
				(
					$this->type == $this->types['quote'] ||
					$this->type == $this->types['invoice']
				)
			)
			{
				$key = $this->type == $this->types['quote'] ? 'CLOSEDATE' : 'DATE_PAY_BEFORE';
				$date = new \Bitrix\Main\Type\Date;
				if ($filter['OVERDUE'] == 'Y')
				{
					$filter['<'.$key] = $date;
					$filter['>'.$key] = \Bitrix\Main\Type\Date::createFromTimestamp(0);
				}
				else
				{
					$filter['>='.$key] = $date;
				}
			}
			// counters
			if (
				isset($filter['ACTIVITY_COUNTER']) &&
				($this->type == $this->types['lead'] || $this->type == $this->types['deal'])
			)
			{
				if (is_array($filter['ACTIVITY_COUNTER']))
				{
					$counterTypeID = Bitrix\Crm\Counter\EntityCounterType::joinType(
						array_filter($filter['ACTIVITY_COUNTER'], 'is_numeric')
					);
				}
				else
				{
					$counterTypeID = (int)$filter['ACTIVITY_COUNTER'];
				}

				$counter = null;
				if ($counterTypeID > 0)
				{
					// get assigned for this counter
					$counterUserIDs = array();
					if (isset($filter['ASSIGNED_BY_ID']))
					{
						if (is_array($filter['ASSIGNED_BY_ID']))
						{
							$counterUserIDs = array_filter($filter['ASSIGNED_BY_ID'], 'is_numeric');
						}
						elseif ($filter['ASSIGNED_BY_ID'] > 0)
						{
							$counterUserIDs[] = $filter['ASSIGNED_BY_ID'];
						}
					}
					// set counter to the filter
					try
					{
						$counter = Bitrix\Crm\Counter\EntityCounterFactory::create(
							$this->type == $this->types['lead']
							? \CCrmOwnerType::Lead
							: \CCrmOwnerType::Deal,
							$counterTypeID,
							0
						);
						$filter += $counter->prepareEntityListFilter(
							array(
								'MASTER_ALIAS' => $this->type == $this->types['lead']
													? \CCrmLead::TABLE_ALIAS
													: \CCrmDeal::TABLE_ALIAS,
								'MASTER_IDENTITY' => 'ID',
								'USER_IDS' => $counterUserIDs
							)
						);
						if (isset($filter['ASSIGNED_BY_ID']))
						{
							unset($filter['ASSIGNED_BY_ID']);
						}
					}
					catch (\Bitrix\Main\NotSupportedException $e)
					{
					}
					catch (\Bitrix\Main\ArgumentException $e)
					{
					}
				}
			}
			//deal
			if ($this->categoryId !== null)
			{
				$filter['CATEGORY_ID'] = $this->categoryId;
			}
			//invoice
			if (
				$this->type == $this->types['deal'] ||
				$this->type == $this->types['invoice']
			)
			{
				$filter['!IS_RECURRING'] = 'Y';
			}
			if (isset($filter['OVERDUE']))
			{
				unset($filter['OVERDUE']);
			}
			//detect success/fail columns
			$semanticIds = array();
			if (isset($filter['STATUS_SEMANTIC_ID']))
			{
				$semanticIds = (array)$filter['STATUS_SEMANTIC_ID'];
			}
			elseif (isset($filter['STAGE_SEMANTIC_ID']))
			{
				$semanticIds = (array)$filter['STAGE_SEMANTIC_ID'];
			}
			elseif (
				$this->type == $this->types['quote'] &&
				isset($filter['=STATUS_ID'])
			)
			{
				// gets semantic for quotes
				$res = \CCrmStatus::GetList(array(
					'SORT' => 'ASC'
				));
				while ($row = $res->fetch())
				{
					if (
						$row['ENTITY_ID'] == 'QUOTE_STATUS' &&
						in_array($row['STATUS_ID'], $filter['=STATUS_ID'])
					)
					{
						$this->allowStages[] = $row['STATUS_ID'];
					}
				}
			}
			if (in_array(PhaseSemantics::SUCCESS, $semanticIds))
			{
				$this->allowSemantics[] = PhaseSemantics::SUCCESS;
			}
			if (in_array(PhaseSemantics::FAILURE, $semanticIds))
			{
				$this->allowSemantics[] = PhaseSemantics::FAILURE;
			}
			if (isset($filter[$this->statusKey]))
			{
				$this->allowStages = $filter[$this->statusKey];
			}

			$entityTypeID = \CCrmOwnerType::resolveID($this->type);
			//region Apply Search Restrictions
			$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
			if(!$searchRestriction->isExceeded($entityTypeID))
			{
				SearchEnvironment::convertEntityFilterValues(
					\CCrmOwnerType::resolveID($this->type),
					$filter
				);
			}
			else
			{
				$arResult['LIVE_SEARCH_LIMIT_INFO'] = $searchRestriction->prepareStubInfo(
					array('ENTITY_TYPE_ID' => $entityTypeID)
				);
			}
			//endregion


			\CCrmEntityHelper::prepareMultiFieldFilter(
				$filter,
				array(),
				'=%',
				false
			);
		}

		return $filter;
	}

	/**
	 * Get filter for orders
	 *
	 * @param array $runtime
	 *
	 * @return array
	 */
	protected function getOrderFilter(array &$runtime) : array
	{
		$grid = \Bitrix\Crm\Kanban\Helper::getGrid($this->type);
		$gridFilter = \Bitrix\Crm\Kanban\Helper::getFilter($this->type);
		$gridFilterFields = (array)$grid->GetFilter($gridFilter);
		\Bitrix\Crm\UI\Filter\EntityHandler::internalize($gridFilter, $gridFilterFields);

		$filterFields = [];
		$componentName = 'bitrix:crm.order.list';
		$className = \CBitrixComponent::includeComponentClass(
			$componentName
		);
		/** @var CCrmOrderListComponent $crmCmp */
		$crmCmp = new $className;
		$crmCmp->initComponent($componentName);
		if ($crmCmp->init())
		{
			$filterFields = $crmCmp->createGlFilter($gridFilterFields, $runtime);
		}

		if (!empty($filterFields['DELIVERY_SERVICE']))
		{
			$services = is_array($filterFields['DELIVERY_SERVICE']) ? $filterFields['DELIVERY_SERVICE'] : [$filterFields['DELIVERY_SERVICE']];
			$whereExpression = '';
			foreach ($services as $serviceId)
			{
				$serviceId = (int)$serviceId;
				if ($serviceId <= 0)
				{
					continue;
				}
				$whereExpression .= empty($whereExpression) ? "(" : " OR ";
				$whereExpression .= "DELIVERY_ID = {$serviceId}";
			}
			if (!empty($whereExpression))
			{
				$whereExpression .= ")";
				$expression = "CASE WHEN EXISTS (SELECT ID FROM b_sale_order_delivery WHERE ORDER_ID = %s AND SYSTEM=\"N\" AND {$whereExpression}) THEN 1 ELSE 0 END";
				$runtime[] = new \Bitrix\Main\Entity\ExpressionField(
					'REQUIRED_DELIVERY_PRESENTED',
					$expression,
					['ID'],
					['date_type' => 'boolean']
				);
				$filterFields['=REQUIRED_DELIVERY_PRESENTED'] = 1;
				unset($filterFields['DELIVERY_SERVICE']);
			}
		}

		if (!empty($filterFields['PAY_SYSTEM']))
		{
			$paySystems = is_array($filterFields['PAY_SYSTEM']) ? $filterFields['PAY_SYSTEM'] : [$filterFields['PAY_SYSTEM']];
			$whereExpression = '';
			foreach ($paySystems as $systemId)
			{
				$systemId = (int)$systemId;
				if ($systemId <= 0)
				{
					continue;
				}
				$whereExpression .= empty($whereExpression) ? "(" : " OR ";
				$whereExpression .= "PAY_SYSTEM_ID = {$systemId}";
			}
			if (!empty($whereExpression))
			{
				$whereExpression .= ")";
				$expression = "CASE WHEN EXISTS (SELECT ID FROM b_sale_order_payment WHERE ORDER_ID = %s AND {$whereExpression}) THEN 1 ELSE 0 END";
				$runtime[] = new \Bitrix\Main\Entity\ExpressionField(
					'REQUIRED_PAY_SYSTEM_PRESENTED',
					$expression,
					['ID'],
					['date_type' => 'boolean']
				);
				$filterFields['=REQUIRED_PAY_SYSTEM_PRESENTED'] = 1;
				unset($filterFields['PAY_SYSTEM']);
			}
		}

		return $filterFields;
	}

	/**
	 * Get path for entity from params or module settings.
	 * @param string $type
	 * @return string
	 */
	protected function getEntityPath($type)
	{
		$params = $this->arParams;
		$pathKey = 'PATH_TO_'.mb_strtoupper($type).'_DETAILS';
		$url = !array_key_exists($pathKey, $params) ? \CrmCheckPath($pathKey, '', '') : $params[$pathKey];
		if(!($url !== '' && CCrmOwnerType::IsSliderEnabled(CCrmOwnerType::ResolveID($type))))
		{
			$pathKey = 'PATH_TO_'.mb_strtoupper($type).'_SHOW';
			$url = !array_key_exists($pathKey, $params) ? \CrmCheckPath($pathKey, '', '') : $params[$pathKey];
		}

		return $url;
	}

	/**
	 * Get multi-fields for entity (phone, email, etc).
	 * @param array $items
	 * @param string $contragent
	 * @return array
	 */
	protected function fillFMfields(array $items, $contragent)
	{
		$isOneElement = false;

		if (!empty($items))
		{
			if (isset($items['ID']))
			{
				$isOneElement = true;
				$items = array($items['ID'] => $items);
			}
			$res = \CCrmFieldMulti::GetListEx(array(), array(
															'ENTITY_ID' => $contragent,
															'ELEMENT_ID' => array_keys($items)));
			while ($row = $res->fetch())
			{
				$row['TYPE_ID'] = mb_strtolower($row['TYPE_ID']);
				if (!in_array($row['TYPE_ID'], $this->allowedFMtypes))
				{
					continue;
				}
				if (!isset($items[$row['ELEMENT_ID']]['FM']))
				{
					$items[$row['ELEMENT_ID']]['FM'] = array();
					$items[$row['ELEMENT_ID']]['FM_VALUES'] = array();
				}
				if (!isset($items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']]))
				{
					$items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']] = array();
					$items[$row['ELEMENT_ID']]['FM_VALUES'][$row['TYPE_ID']] = array();
				}
				$items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']][] = $row;
				$items[$row['ELEMENT_ID']]['FM_VALUES'][$row['TYPE_ID']][] = array(
					'value' => htmlspecialcharsbx($row['VALUE']),
					'title' => $this->fmTypes[$row['COMPLEX_ID']]
				);
			}
		}

		return $isOneElement ? array_pop($items) : $items;
	}

	/**
	 * Companies or contacts.
	 * @param string $contragent
	 * @return array
	 */
	protected function getContragents($contragent)
	{
		$items = array();

		$path = $this->getEntityPath($contragent);

		$contragent = mb_strtolower(trim($contragent));
		$provider = '\CCrm'.$contragent;
		if (
			is_callable(array($provider, 'getListEx')) &&
			isset($this->{$contragent}) && !empty($this->{$contragent})
		)
		{
			$select = array(
				'ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'TITLE', 'HONORIFIC'
			);
			$res = $provider::getListEx(
				array(),
				array(
					'ID' => array_unique($this->{$contragent})
				),
				false,
				false,
				$select
			);
			while ($row = $res->fetch())
			{
				if ($contragent == 'contact')
				{
					$row['TITLE'] = \CCrmContact::prepareFormattedName(
						$row,
						$this->arParams['~NAME_TEMPLATE']
					);
				}
				$row['~TITLE'] = $row['TITLE'];
				$row['TITLE'] = htmlspecialcharsbx($row['TITLE']);
				$row['URL'] = str_replace($this->pathMarkers, $row['ID'], $path);
				$items[$row['ID']] = $row;
			}
		}

		$items = $this->fillFMfields($items, $contragent);

		return $items;
	}

	/**
	 * Get columns.
	 * @param boolean $clear Clear static var.
	 * @param boolean $withoutCache Without static cache.
	 * @param array $params Some additional params.
	 * @return array
	 */
	protected function getColumns($clear = false, $withoutCache = false, array $params = array())
	{
		static $columns = array();

		if ($withoutCache)
		{
			$clear = $withoutCache;
		}

		if ($this->arParams['ONLY_ITEMS'] == 'Y')
		{
			return $columns;
		}

		if ($clear)
		{
			$columns = array();
		}

		if (!isset($params['originalColumns']))
		{
			$params['originalColumns'] = false;
		}

		$getDeleteCol = function($params)
		{
			return array(
				'real_id' => 'DELETED',
				'real_sort' => $params['real_sort'],
				'id' => 'DELETED',
				'name' => Loc::getMessage('CRM_KANBAN_SYS_STATUS_DELETE'),
				'color' => '',
				'type' => '',
				'sort' => $params['sort'],
				'count' => 0,
				'total' => 0,
				'currency' => $this->currency,
				'dropzone' => true
			);
		};

		if (empty($columns))
		{
			$runtime = [];
			$baseCurrency = $this->currency;
			if ($this->type !== $this->types['order'])
			{
				$filter = $this->getFilter();
			}
			else
			{
				$filter = $this->getOrderFilter($runtime);
				if (isset($filter[$this->statusKey]))
				{
					$this->allowStages = $filter[$this->statusKey];
				}
			}
			$types = $this->types;
			$statusCode = $this->statusKey;
			$sort = 0;
			$winColumn = [];
			$commonRequireds = [];
			$userPerms = $this->getCurrentUserPermissions();
			$column = [
				'type' => 'PROGRESS'
			];
			// get all required fields
			if
			(
				$this->type == $types['lead'] ||
				$this->type == $types['deal']
			)
			{
				foreach ($this->userFields as $row)
				{
					if ($row['MANDATORY'] == 'Y')
					{
						$commonRequireds[] = $row['FIELD_NAME'];
					}
				}
				unset($row);
			}
			// prepare each status
			$firstDropzone = false;
			foreach ($this->getStatuses($clear) as $status)
			{
				$sort += 100;
				//set default color
				if ($status['COLOR'] == '')
				{
					if ($status['PROGRESS_TYPE'] == 'WIN')
					{
						$status['COLOR'] = \CCrmViewHelper::SUCCESS_COLOR;
					}
					elseif ($status['PROGRESS_TYPE'] == 'LOOSE')
					{
						$status['COLOR'] = \CCrmViewHelper::FAILURE_COLOR;
					}
					else
					{
						$status['COLOR'] = \CCrmViewHelper::PROCESS_COLOR;
					}
				}
				//detect drop zones
				if (
					!empty($this->allowStages) &&
					!in_array($status['STATUS_ID'], $this->allowStages)
				)
				{
					$dropzone = true;
				}
				else if (
					in_array($status['STATUS_ID'], $this->allowStages)
				)
				{
					$dropzone = false;
				}
				else if (
					(
						$status['PROGRESS_TYPE'] == 'WIN' &&
						!in_array(PhaseSemantics::SUCCESS, $this->allowSemantics)
					)
					||
					(
						$status['PROGRESS_TYPE'] == 'LOOSE' &&
						!in_array(PhaseSemantics::FAILURE, $this->allowSemantics)
					)
					||
					(
						$status['PROGRESS_TYPE'] != 'WIN' &&
						$status['PROGRESS_TYPE'] != 'LOOSE' &&
						!in_array(PhaseSemantics::PROCESS, $this->allowSemantics)
					)
				)
				{
					$dropzone = true;
				}
				else
				{
					$dropzone = false;
				}
				// first drop zone
				if (!$firstDropzone && $dropzone)
				{
					$firstDropzone = true;
				}
				// add 'delete' column
				if (
					$firstDropzone &&
					!$params['originalColumns']
				)
				{
					$firstDropzone = false;
					$columns['DELETED'] = $getDeleteCol([
						'real_sort' => $status['SORT'],
						'sort' => $sort
					]);
				}
				// format column
				$column = array(
					'real_id' => $status['ID'],
					'real_sort' => $status['SORT'],
					'id' => $status['STATUS_ID'],
					'name' => $status['NAME'],
					'color' => mb_strpos($status['COLOR'], '#') === 0? mb_substr($status['COLOR'], 1) : $status['COLOR'],
					'type' => $status['PROGRESS_TYPE'],
					'sort' => $sort,
					'count' => 0,
					'total' => 0,
					'currency' => $baseCurrency,
					'dropzone' => $dropzone,
					'canAddItem' => true
				);
				// check create prermissions
				if (
					$this->type == $types['lead'] ||
					$this->type == $types['deal']
				)
				{
					if ($this->type == $types['lead'])
					{
						$perm = \CCrmLead::getStatusCreatePermissionType(
							$column['id'], $userPerms
						);
					}
					else
					{
						$perm = \CCrmDeal::getStageCreatePermissionType(
							$column['id'], $userPerms, (int)$this->categoryId
						);
					}
					if ($perm == BX_CRM_PERM_NONE)
					{
						$column['canAddItem'] = false;
					}
				}
				// win column
				if (
					!$params['originalColumns'] &&
					$status['PROGRESS_TYPE'] == 'WIN'
				)
				{
					$winColumn[$status['STATUS_ID']] = $column;
				}
				else
				{
					$columns[$status['STATUS_ID']] = $column;
				}
				// gets required fields for each column
				if (
					(
						$this->type == $types['lead'] ||
						$this->type == $types['deal']
					)
					&&
					FieldAttributeManager::isEnabled()
				)
				{
					if ($this->type == $types['lead'])
					{
						$requiredFields = FieldAttributeManager::getRequiredFields(
							\CCrmOwnerType::Lead,
							0,
							array(
								'STATUS_ID' => $status['STATUS_ID']
							)
						);
					}
					else
					{
						$requiredFields = FieldAttributeManager::getRequiredFields(
							\CCrmOwnerType::Deal,
							0,
							array(
								'STAGE_ID' => $status['STATUS_ID']
							)
						);
					}
					foreach ($requiredFields as $requiredFieldsBlock)
					{
						foreach ($requiredFieldsBlock as $key)
						{
							if (!isset($this->requiredFields[$key]))
							{
								$this->requiredFields[$key] = [];
							}
							$this->requiredFields[$key][] = $status['STATUS_ID'];
						}
					}
					foreach ($commonRequireds as $key)
					{
						if (!isset($this->requiredFields[$key]))
						{
							$this->requiredFields[$key] = [];
						}
						$this->requiredFields[$key][] = $status['STATUS_ID'];
					}
				}
			}

			$columns += $winColumn;
			$lastColumn = array_pop(array_values($columns));

			if (!isset($columns['DELETED']))
			{
				$columns['DELETED'] = $getDeleteCol([
					'real_sort' => $lastColumn['real_sort'] + 10,
					'sort' => $lastColumn['sort'] + 10
				]);
			}

			//get sums and counts

			$fieldSum = $this->fieldSum ? $this->fieldSum : 'OPPORTUNITY_ACCOUNT';

			if ($this->type == $types['invoice'])
			{
				$stats = array();
				$res = \CCrmInvoice::GetList(array(), $filter,
											array('STATUS_ID', 'SUM' => 'PRICE'), false,
											array('STATUS_ID', 'PRICE')
						);
				while ($row = $res->fetch())
				{
					$stats[] = $row;
				}
			}
			elseif ($this->type == $types['order'])
			{
				$queryParameters = [
					'filter' => $filter,
					'select' => [
						'STATUS_ID',
						new \Bitrix\Main\Entity\ExpressionField('SUM', 'SUM(%s)', 'PRICE'),
						new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)'),
					]
				];
				if (!empty($runtime))
				{
					$queryParameters['runtime'] = $runtime;
				}
				$stats = array();
				$res = Order\Order::getList($queryParameters);
				while ($row = $res->fetch())
				{
					$stats[] = $row;
				}
			}
			else
			{
				$provider = '\CCrm'.$this->type;
				if (class_exists($provider))
				{
					if (method_exists($provider, 'getListEx'))
					{
						$options = [];
						$select = [$statusCode];
						if (mb_substr($fieldSum, 0, 3) == 'UF_')
						{
							$options['FIELD_OPTIONS'] = [
								'UF_FIELDS' => [
									$fieldSum => [
										'FIELD' => $fieldSum,
										'TYPE' => 'double'
									]
								]
							];
						}
						else
						{
							$select[] = $fieldSum;
						}
						$res = $provider::GetListEx(array(), $filter,
													array($statusCode, 'SUM' => $fieldSum), false,
													$select,
													$options);
					}
					else
					{
						$res = $provider::GetList(array(), $filter,
													array($statusCode, 'SUM' => $fieldSum), false,
													array($statusCode, $fieldSum));
					}
					while ($row = $res->fetch())
					{
						$stats[] = $row;
					}
				}
			}

			if ($stats)
			{
				foreach ($stats as $stat)
				{
					if (isset($columns[$stat[$statusCode]]))
					{
						//fill column
						if ($this->type == $types['invoice'])
						{
							$columns[$stat[$statusCode]]['count'] = $stat['CNT'];
							$columns[$stat[$statusCode]]['total'] = $stat['PRICE'];
							$columns[$stat[$statusCode]]['total_format'] = \CCrmCurrency::MoneyToString(round($stat['PRICE']), $baseCurrency);
						}
						elseif ($this->type == $types['order'])
						{
							$columns[$stat[$statusCode]]['count'] = $stat['CNT'];
							$columns[$stat[$statusCode]]['total'] = $stat['SUM'];
							$columns[$stat[$statusCode]]['total_format'] = \CCrmCurrency::MoneyToString(round($stat['SUM']), $baseCurrency);
						}
						else
						{
							$columns[$stat[$statusCode]]['count'] = $stat['CNT'];
							$columns[$stat[$statusCode]]['total'] = $stat[$fieldSum];
							$columns[$stat[$statusCode]]['total_format'] = \CCrmCurrency::MoneyToString(round($stat[$fieldSum]), $baseCurrency);
						}
					}
				}
			}
		}

		// without static cache
		if ($withoutCache)
		{
			$tmpColumns = $columns;
			$columns = array();
			return $tmpColumns;
		}
		else
		{
			return $columns;
		}
	}

	/**
	 * Insert new key/value in the array after the key.
	 * @param array $array Array for change.
	 * @param mixed $afterKey Insert after this key. If null then the value insert in beginning of the array.
	 * @param mixed $newKey New key.
	 * @param mixed $newValue New value.
	 * @return array
	 */
	protected function arrayInsertAfter(array $array, $afterKey, $newKey, $newValue)
	{
		if (isset($array[$newKey]))
		{
			return $array;
		}
		if (empty($array))
		{
			return array($newKey => $newValue);
		}
		if ($afterKey === null)
		{
			return array($newKey => $newValue) + $array;
		}
		if ($afterKey === null || array_key_exists ($afterKey, $array))
		{
			$newArray = array();
			foreach ($array as $k => $value)
			{
				$newArray[$k] = $value;
				if ($k == $afterKey)
				{
					$newArray[$newKey] = $newValue;
				}
			}
			return $newArray;
		}
		else
		{
			return $array;
		}
	}

	/**
	 * Remember last id of entity for user.
	 * @param int|bool $lastId If set, save in config.
	 * @return void|int
	 */
	protected function rememberLastId($lastId = false)
	{
		$lastIds = \CUserOptions::getOption(
			'crm',
			'kanban_sort_last_id',
			array()
		);
		if ($lastId !== false)
		{
			$lastIds[$this->type] = $lastId;
			\CUserOptions::setOption(
				'crm',
				'kanban_sort_last_id',
				$lastIds
			);
		}
		else
		{
			return isset($lastIds[$this->type]) ? $lastIds[$this->type] : 0;
		}
	}

	/**
	 * Get last ID for current entity.
	 * @return int
	 */
	protected function getLastId()
	{
		static $lastId = null;

		if ($lastId !== null)
		{
			return $lastId;
		}

		$lastId = 0;
		$type = $this->type;
		$provider = '\CCrm'.$type;
		$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';

		if (method_exists($provider, 'getTopIDs'))
		{
			$lastId = $provider::getTopIDs(1, 'DESC');
			$lastId = !empty($lastId) ? array_shift($lastId) : 0;
		}
		else if (is_callable(array($provider, $method)))
		{
			$res = $provider::$method(
				array(
					'ID' => 'DESC'
				),
				array(
					//
				),
				false,
				array(
					'nTopCount' => 1
				),
				array(
					'ID'
				)
			);
			if ($row = $res->fetch())
			{
				$lastId = $row['ID'];
			}
		}

		return $lastId;
	}

	/**
	 * Sort items by user order.
	 * @param array $items Items for sort.
	 * @return array
	 */
	protected function sort(array $items)
	{
		static $lastId = null;
		static $lastIdremember = null;

		if ($lastIdremember === null)
		{
			$lastIdremember = $this->rememberLastId();
		}
		if ($lastId === null)
		{
			$lastId = $this->getLastId();
		}

		$sortedIds = array();
		$sort = \Bitrix\Crm\Kanban\SortTable::getPrevious(array(
			'ENTITY_TYPE_ID' => $this->type,
			'ENTITY_ID' => array_keys($items),
		));
		if (!empty($sort))
		{
			foreach ($sort as $id => $prev)
			{
				if ($prev>0 && !isset($items[$prev]))
				{
					continue;
				}
				if ($id == $prev)
				{
					continue;
				}
				$moveItem = $items[$id];
				unset($items[$id]);
				if ($prev == 0)
				{
					$prev = null;
				}
				$sortedIds[] = $id;
				$items = $this->arrayInsertAfter($items, $prev, $id, $moveItem);
			}

			// set all new items to the begin of array
			$newIds = array_reverse(array_diff(array_keys($items), $sortedIds));
			foreach ($newIds as $id)
			{
				if ($id > $lastIdremember)
				{
					$moveItem = $items[$id];
					unset($items[$id]);
					$items = $this->arrayInsertAfter($items, null, $id, $moveItem);
					\Bitrix\Crm\Kanban\SortTable::setPrevious(array(
						'ENTITY_TYPE_ID' => $this->type,
						'ENTITY_ID' => $id,
						'PREV_ENTITY_ID' => 0
					));
				}
			}
		}

		return $items;
	}

	/**
	 * Returns all traces by id.
	 * @param int|int[] $entityId Entity id, or ids.
	 * @return array
	 */
	protected function getTracesById($entityId)
	{
		static $actualSources = null;
		static $entityTypeId = null;
		static $channelNames = null;

		$entityId = (array) $entityId;
		$traces = [];
		$traceEntities = [];

		if ($entityTypeId === null)
		{
			$entityTypeId = \CCrmOwnerType::ResolveID($this->type);
		}
		if ($actualSources === null)
		{
			$actualSources = Tracking\Provider::getActualSources();
			$actualSources = array_combine(
				array_column($actualSources, 'ID'),
				array_values($actualSources)
			);
		}
		if ($channelNames === null)
		{
			$channelNames = Channel\Factory::getNames();
		}

		// get traces by entity
		$res = Tracking\Internals\TraceEntityTable::getList([
			'select' => [
				'ENTITY_ID', 'TRACE_ID'
			],
			'filter' => [
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $entityId
			]
		]);
		while ($row = $res->fetch())
		{
			$traces[$row['ENTITY_ID']] = $row;
			$traceEntities[$row['TRACE_ID']] = [];
		}

		if (!$traceEntities)
		{
			return [];
		}

		// fill paths for traces
		$res = Tracking\Internals\TraceTable::getList([
			'select' => [
				'ID', 'SOURCE_ID'
			],
			'filter' => [
				'=ID' => array_keys($traceEntities)
			]
		]);
		while ($row = $res->fetch())
		{
			if (
				$row['SOURCE_ID'] &&
				isset($actualSources[$row['SOURCE_ID']])
			)
			{
				$source = $actualSources[$row['SOURCE_ID']];
				$traceEntities[$row['ID']] = [
					'NAME' => $source['NAME'],
					'DESC' => $source['DESCRIPTION'],
					'ICON' => $source['ICON_CLASS'],
					'ICON_COLOR' => $source['ICON_COLOR'],
					'IS_SOURCE' => true
				];
			}
		}

		// additional filling
		$res = TraceChannelTable::getList([
			'select' => [
				'TRACE_ID', 'CODE'
			],
			'filter' => [
				'TRACE_ID' => array_keys($traceEntities)
			]
		]);
		while ($row = $res->fetch())
		{
			$traceEntities[$row['TRACE_ID']] = [
				'NAME' => isset($channelNames[$row['CODE']])
						? $channelNames[$row['CODE']]
						: Channel\Base::getNameByCode($row['CODE']),
				'DESC' => '',
				'ICON' => '',
				'ICON_COLOR' => '',
				'IS_SOURCE' => true
			];
		}

		// fill entities by full path
		foreach ($traces as $id => $trace)
		{
			if (isset($traceEntities[$trace['TRACE_ID']]))
			{
				$traces[$id] = $traceEntities[$trace['TRACE_ID']];
			}
			else
			{
				unset($traces[$id]);
			}
		}

		return $traces;
	}

	/**
	 * Base method for getting data.
	 * @param array $filter Additional filter.
	 * @param boolean $pagen Out by paging.
	 * @return array
	 */
	protected function getItems(array $filter = array(), $pagen = true)
	{
		static $path = null;
		static $currency = null;
		static $columns = null;

		$result = array();
		$type = $this->type;
		$typeId = \CCrmOwnerType::ResolveID($type);
		$types = $this->types;
		$provider = '\CCrm'.$type;
		if ($type == $types['order'])
		{
			$provider = Order\Order::class;
		}
		$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';
		$runtime = [];
		$select = $this->getSelect();
		if ($type !== $types['order'])
		{
			$filterCommon = $this->getFilter();
		}
		else
		{
			$filterCommon = $this->getOrderFilter($runtime);
		}

		$addFields = $this->getAdditionalFields();
		$addSelect = array_keys($this->additionalSelect);
		$addTypes = $this->additionalTypes;
		$navParams = $pagen ? array('iNumPage' => $this->blockPage, 'nPageSize' => $this->blockSize) : false;
		$statusKey = $this->statusKey;
		//$userFieldDispatcher = \Bitrix\Main\UserField\Dispatcher::instance();

		if ($this->requiredFields)
		{
			$select = array_merge(
				$select,
				array_keys($this->requiredFields)
			);
		}

		// remove conflict keys and merge filters
		$statusKeyColumn = null;
		$filter = array_merge($filterCommon, $filter);

		if ($path === null)
		{
			$path = $this->getEntityPath($type);
		}
		if ($currency === null)
		{
			$currency = $this->currency;
		}
		if ($columns === null)
		{
			$columns = $this->getColumns();
		}
		if (class_exists($provider))
		{
			$queryParameters = [
				'filter' => $filter,
				'select' => $select,
				'order' => ['ID' => 'DESC'],
			];

			if (!empty($runtime))
			{
				$queryParameters['runtime'] = $runtime;
			}
			//user sorting
			if (
				isset($filter[$statusKey]) &&
				is_string($filter[$statusKey]) &&
				isset($columns[$filter[$statusKey]]['count']) &&
				$columns[$filter[$statusKey]]['count'] <= $this->maxSortSize
			)
			{
				//get all and sort
				$sorting = true;
				$db = array();
				if ($this->type !== $types['order'])
				{
					$res = $provider::$method(array('ID' => 'DESC'), $filter, false, false, $select);
				}
				else
				{
					$res = $provider::$method($queryParameters);
				}
				while ($row = $res->fetch())
				{
					$db[$row['ID']] = $row;
				}
				if ($this->type === $types['order'] && !empty($db))
				{
					$db = $this->prepareOrderEntitiesFields($db);
				}
				$db = $this->sort($db);
				//init query
				$res = new CDBResult;
				$res->initFromArray($db);
				$res->navStart($this->blockSize, false, $this->blockPage);
			}
			else
			{
				$sorting = false;
				if ($this->type !== $types['order'])
				{
					$res = $provider::$method(
						['ID' => 'DESC'],
						$filter,
						false,
						false,
						$select,
						$pagen
							? [
							'QUERY_OPTIONS' => [
								'LIMIT' => $this->blockSize,
								'OFFSET' => $this->blockSize * ($this->blockPage - 1)
							]
						]
							: [

						]
					);
				}
				else
				{
					$orders = [];
					$queryParameters['limit'] = $this->blockSize;
					$queryParameters['offset'] = ($this->blockPage - 1) * $this->blockSize;
					$res = $provider::$method($queryParameters);
					while ($order = $res->fetch())
					{
						$orders[$order['ID']] = $order;
					}
					if (!empty($orders))
					{
						$orders = $this->prepareOrderEntitiesFields($orders);
					}
					$res = new CDBResult;
					$res->initFromArray($orders);
				}
			}
			$timeOffset = \CTimeZone::GetOffset();
			$timeFull = time() + $timeOffset;
			$pageCount = $res->NavPageCount;
			$specialReqKeys = [
				'OPPORTUNITY' => 'OPPORTUNITY_WITH_CURRENCY',
				'CONTACT_ID' => 'CLIENT'
			];
			$users = [];
			$fileIds = [];
			$crmEntities = [];
			$iblockSects = array();
			$iblockElements = array();
			while ($row = $res->fetch())
			{
				$row['FORMAT_TIME'] = true;
				//base
				if (!isset($row['ASSIGNED_BY']))
				{
					$row['ASSIGNED_BY'] = $row['RESPONSIBLE_ID'];
				}
				if ($type == $types['lead'])
				{
					$row['PRICE'] = $row['OPPORTUNITY'];
					$row['DATE'] = $row['DATE_CREATE'];
				}
				elseif ($type == $types['deal'])
				{
					$row['PRICE'] = $row['OPPORTUNITY'];
					$row['DATE'] = $row['DATE_CREATE'];
				}
				elseif ($type == $types['quote'])
				{
					$row['PRICE'] = $row['OPPORTUNITY'];
					if ($row['BEGINDATE'])
					{
						$row['FORMAT_TIME'] = false;
						$row['DATE'] = $row['BEGINDATE'];
					}
					else
					{
						$row['DATE'] = $row['DATE_CREATE'];
					}
				}
				elseif ($type == $types['invoice'])
				{
					$row['TITLE'] = $row['ORDER_TOPIC'];
					$row['FORMAT_TIME'] = false;
					$row['DATE'] = $row['PAY_VOUCHER_DATE'] ? $row['PAY_VOUCHER_DATE'] : $row['DATE_BILL'];
					$row['CONTACT_ID'] = $row['UF_CONTACT_ID'];
					$row['COMPANY_ID'] = $row['UF_COMPANY_ID'];
					$row['CURRENCY_ID'] = $row['CURRENCY'];
				}
				elseif ($type == $types['order'])
				{
					$row['FORMAT_TIME'] = false;
					$row['DATE'] = $row['DATE_INSERT'];
				}
				//redefine price
				if ($this->fieldSum && array_key_exists($this->fieldSum, $row))
				{
					$row['PRICE'] = $row[$this->fieldSum];
				}
				elseif (isset($row['OPPORTUNITY_ACCOUNT']) && $row['OPPORTUNITY_ACCOUNT']!='')
				{
					$row['PRICE'] = $row['OPPORTUNITY_ACCOUNT'];
				}
				if (isset($row['ACCOUNT_CURRENCY_ID']) && $row['ACCOUNT_CURRENCY_ID']!='')
				{
					$row['CURRENCY_ID'] = $row['ACCOUNT_CURRENCY_ID'];
				}

				//contragent
				if ($row['CONTACT_ID'] > 0)
				{
					$row['CONTACT_TYPE'] = 'CRM_CONTACT';
					$this->contact[$row['ID']] = $row['CONTACT_ID'];
				}

				//company
				if ($row['COMPANY_ID'] > 0)
				{
					$row['CONTACT_TYPE'] = ($row['CONTACT_TYPE'] ?? 'CRM_COMPANY');
					$row['CONTACT_ID'] = ($row['CONTACT_ID'] ?? $row['COMPANY_ID']);
					$this->company[$row['ID']] = $row['COMPANY_ID'];
				}

				$row['CONTACT_TYPE'] = ($row['CONTACT_TYPE'] ?? '');

				//price converted
				if ($row['CURRENCY_ID']=='' || $row['CURRENCY_ID'] == $currency)
				{
					$row['PRICE'] = doubleval($row['PRICE']);
					$row['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($row['PRICE'], $currency);
				}
				else
				{
					$row['PRICE'] = \CCrmCurrency::ConvertMoney($row['PRICE'], $row['CURRENCY_ID'], $currency);
					$row['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($row['PRICE'], $currency);
				}
				if ($row['DATE'] instanceof \Bitrix\Main\Type\Date)
				{
					$row['DATE_UNIX'] = $row['DATE']->getTimestamp();
				}
				else
				{
					$row['DATE_UNIX'] = \MakeTimeStamp($row['DATE']);
				}
				$dateFormat = $this->dateFormats[date('Y') == date('Y', $row['DATE_UNIX']) ? 'short' : 'full'][$this->formatLang];
				//additional fields
				$fields = array();
				foreach ($addSelect as $code)
				{
					if (array_key_exists($code, $row) && array_key_exists($code, $addFields))
					{
						if ($code == 'ASSIGNED_BY_ID' && isset($row['ASSIGNED_BY']))
						{
							$users[] = $row['ASSIGNED_BY'];
						}
						elseif ($code == 'RESPONSIBLE_ID' && isset($row['RESPONSIBLE_ID']))
						{
							$users[] = $row['RESPONSIBLE_ID'];
						}
						elseif ($code == 'USER' && isset($row['USER_ID']))
						{
							$users[] = $row['USER_ID'];
						}
						if (isset($addTypes[$code]) && isset($addTypes[$code][$row[$code]]))
						{
							$row[$code] = $addTypes[$code][$row[$code]];
						}
						if (
							!empty($row[$code]) ||
							($code == 'OPPORTUNITY')
						)
						{
							$isHtml = false;
							$boolFieldNames = ['CLOSED', 'PAYED', 'ALLOY_DELIVERY', 'DEDUCTED', 'CANCELED'];
							if (in_array($code, $boolFieldNames))
							{
								$row[$code] = Loc::getMessage('CRM_KANBAN_CHAR_' . $row[$code]);
							}
							else if ($code == 'OPPORTUNITY')
							{
								$row[$code] = \CCrmCurrency::MoneyToString(
									$row[$code],
									$row['CURRENCY_ID']
								);
							}
							else if ($code == 'PAYMENT' || $code == 'SHIPMENT')
							{
								$fieldValue = "";
								$row[$code] = is_array($row[$code]) ? $row[$code] : [$row[$code]];
								foreach ($row[$code] as $rowCodeItem)
								{
									if ($code == 'PAYMENT')
									{
										$pathSubItem = CComponentEngine::MakePathFromTemplate(
											$this->arParams['PATH_TO_ORDER_PAYMENT_DETAILS'],
											array(
												'payment_id' => $rowCodeItem['ID']
											)
										);
									}
									else
									{
										$pathSubItem = CComponentEngine::MakePathFromTemplate(
											$this->arParams['PATH_TO_ORDER_SHIPMENT_DETAILS'],
											array(
												'shipment_id' => $rowCodeItem['ID']
											)
										);
									}

									$price = ($code == 'PAYMENT') ? $rowCodeItem['SUM'] : $rowCodeItem['PRICE_DELIVERY'];
									$sum = \CCrmCurrency::MoneyToString(
										$price,
										$rowCodeItem['CURRENCY']
									);

									$title = '';

									$paySystemName =  ($code == 'PAYMENT') ? $rowCodeItem['PAY_SYSTEM_NAME'] : $rowCodeItem['DELIVERY_NAME'];
									$paySystemName = htmlspecialcharsbx($paySystemName);
									if (!empty($paySystemName))
									{
										$title .= $paySystemName. " ";
									}

									if (!empty($sum))
									{
										$title .= "({$sum})";
									}

									if (empty($title))
									{
										$title = htmlspecialcharsbx($rowCodeItem['ACCOUNT_NUMBER']);
									}

									$fieldValue .= "<a href='{$pathSubItem}'>{$title}</a></br>";
								}
								$row[$code] = $fieldValue;
							}
							else if ($code == 'ORDER_STAGE')
							{
								$orderStages = Order\OrderStage::getList();
								if (isset($orderStages[$row[$code]]))
								{
									$row[$code] = [
										'title' => $orderStages[$row[$code]],
										'code' => $row[$code]
									];
								}
								else
								{
									$row[$code] = [
										'title' => $row[$code],
										'code' => $row[$code]
									];
									continue;
								}
							}
							else if (
								$addFields[$code]['type'] == 'money'
							)
							{
								$moneyRow = is_array($row[$code])
											? $row[$code]
											: [$row[$code]];
								$row[$code] = [];
								foreach ($moneyRow as $moneyItem)
								{
									[$money, $moneyCurr] = explode('|', $moneyItem);
									$row[$code][] = \CCrmCurrency::MoneyToString(
										$money,
										$moneyCurr
									);
								}
								$row[$code] = implode('; ', $row[$code]);
							}
							else if (
								$addFields[$code]['type'] == 'employee'
							)
							{
								$this->usersIdFields[] = $code;
							}
							else if (
								$addFields[$code]['type'] == 'boolean'
							)
							{
								$row[$code] = Loc::getMessage('CRM_KANBAN_BOOLEAN_' . (int)$row[$code]);
							}
							else if (
								$addFields[$code]['type'] == 'enumeration'
							)
							{
								$row[$code] = implode(',', array_intersect_key(
									$addFields[$code]['enumerations'],
									array_flip((array)$row[$code])
								));
							}
							else if (
								$addFields[$code]['type'] == 'date' ||
								mb_strpos($code, 'DATE') !== false
							)
							{
								$row[$code] = (array) $row[$code];
								foreach ($row[$code] as &$rowDate)
								{
									if ($rowDate instanceof \Bitrix\Main\Type\Date || $rowDate instanceof \DateTime)
									{
										$timestamp = $rowDate->getTimestamp();
									}
									else
									{
										$timestamp = \MakeTimeStamp($rowDate);
									}
									$rowDate = \FormatDate($this->dateFormats['full'], $timestamp, $timeFull);
								}
								unset($rowDate);
								$row[$code] = implode(',', $row[$code]);
							}
							else if ($addFields[$code]['type'] == 'file')
							{
								$row[$code] = (array) $row[$code];
								$fileIds = array_merge(
									$fileIds,
									$row[$code]
								);
							}
							else if ($addFields[$code]['type'] == 'crm_status')
							{
								$entityType = isset($addFields[$code]['settings']['ENTITY_TYPE'])
											? $addFields[$code]['settings']['ENTITY_TYPE']
											: null;
								$statuses = $this->getStatusesDB($entityType);
								if (isset($statuses[$row[$code]]))
								{
									$row[$code] = $statuses[$row[$code]];
								}
								else
								{
									continue;
								}
							}
							else if ($addFields[$code]['type'] == 'crm')
							{
								$row[$code] = (array) $row[$code];
								$settings = isset($addFields[$code]['settings'])
											? (array) $addFields[$code]['settings']
											: [];
								$settingsFlip = array_flip($settings);
								$newRow = [];
								foreach ($row[$code] as $crmEntity)
								{
									if (!mb_strpos($crmEntity, '_'))
									{
										if (isset($settingsFlip['Y']))
										{
											$crmEntity = $this->makeEntityLetter($settingsFlip['Y']) . '_' . $crmEntity;
										}
									}
									$newRow[] = $crmEntity;
									if(mb_strpos($crmEntity, '_'))
									{
										[$crmEntityType, $crmEntity] = explode('_', $crmEntity);
									}
									else
									{
										continue;
									}
									if (!isset($crmEntities[$crmEntityType]))
									{
										$crmEntities[$crmEntityType] = [];
									}
									$crmEntities[$crmEntityType][] = $crmEntity;
								}
								$row[$code] = $newRow;
							}
							elseif ($addFields[$code]['type'] == 'iblock_section')
							{
								$row[$code] = (array) $row[$code];
								$iblockSects = array_merge(
									$iblockSects,
									$row[$code]
								);
							}
							elseif ($addFields[$code]['type'] == 'iblock_element')
							{
								$row[$code] = (array) $row[$code];
								$iblockElements = array_merge(
									$iblockElements,
									$row[$code]
								);
							}
							elseif ($addFields[$code]['type'] == 'address')
							{
								$row[$code] = (array) $row[$code];
								foreach ($row[$code] as &$location)
								{
									if (mb_strpos($location, '|') !== false)
									{
										[$location, ] = explode('|', $location);
									}
								}
								unset($location);
							}
							elseif ($addFields[$code]['type'] == 'url')
							{
								$row[$code] = (array) $row[$code];
								foreach ($row[$code] as &$url)
								{
									$url = \htmlspecialcharsbx($url);
									$url = '<a href="' . $url . '" target="_blank">' . $url . '</a>';
								}
								unset($url);
								$isHtml = true;
							}
							$fields[] = array(
								'code' => $code,
								'title' => $addFields[$code]['title'],
								'type' => $addFields[$code]['type'],
								'value' => $row[$code],
								'html' => $isHtml || in_array($code, ['COMMENTS', 'PAYMENT', 'SHIPMENT'])
							);
							if (in_array($code, $this->usersIdFields))
							{
								$users = array_merge(
									$users,
									(array)$row[$code]
								);
							}
						}
					}
				}
				$returnCustomer = isset($row['IS_RETURN_CUSTOMER']) && $row['IS_RETURN_CUSTOMER'] == 'Y';
				// collect required
				$required = [];
				$requiredFm = [];
				if ($this->requiredFields)
				{
					// fm fields check later
					foreach ($this->allowedFMtypes as $fm)
					{
						$fmUp = mb_strtoupper($fm);
						$requiredFm[$fmUp] = true;
						$row[$fmUp] = '';
					}
					// check each key
					foreach ($row as $key => $val)
					{
						if (
							$returnCustomer &&
							isset($this->exclusiveFieldsReturnCustomer[$key])
						)
						{
							continue;
						}
						if (isset($this->requiredFields[$key]) && !$val)
						{
							foreach ($this->requiredFields[$key] as $status)
							{
								if (!isset($required[$status]))
								{
									$required[$status] = [];
								}
								$required[$status][] = $key;
							}
						}
					}
					// special keys
					foreach ($specialReqKeys as $reqKeyOrig => $reqKey)
					{
						if (
							isset($this->requiredFields[$reqKey]) &&
							(
								!$row[$reqKeyOrig]
								||
								$reqKeyOrig == 'OPPORTUNITY' &&
								$row['OPPORTUNITY_ACCOUNT'] <= 0
							)
						)
						{
							foreach ($this->requiredFields[$reqKey] as $status)
							{
								if (!isset($required[$status]))
								{
									$required[$status] = [];
								}
								$required[$status][] = $reqKey;
							}
						}
					}
				}
				//add
				$result[$row['ID']] = array(
					'id' =>  $row['ID'],
					'name' => htmlspecialcharsbx($row['TITLE'] != '' ? $row['TITLE'] : '#' . $row['ID']),
					'link' => str_replace($this->pathMarkers, $row['ID'], $path),
					'columnId' => $columnId = htmlspecialcharsbx($row[$this->statusKey]),
					'columnColor' => isset($columns[$columnId]) ? $columns[$columnId]['color'] : '',
					'price' => $row['PRICE'],
					'price_formatted' => $row['PRICE_FORMATTED'],
					'date' => (
								!$row['FORMAT_TIME']
								? \FormatDate($dateFormat, $row['DATE_UNIX'], $timeFull)
								: (
									(time() - $row['DATE_UNIX']) / 3600 > 48
									? \FormatDate($dateFormat, $row['DATE_UNIX'], $timeFull)
									: \FormatDate('x', $row['DATE_UNIX'], $timeFull)
								)
							),
					'contactId' => (int)$row['CONTACT_ID'],
					'companyId' => (!empty($row['COMPANY_ID']) ? (int)$row['COMPANY_ID'] : null),
					'contactType' => $row['CONTACT_TYPE'],
					'modifyById' => isset($row['MODIFY_BY_ID']) ? $row['MODIFY_BY_ID'] : 0,
					'modifyByAvatar' => '',
					'activityShow' => 1,
					'activityErrorTotal' => 0,
					'activityProgress' => 0,
					'activityTotal' => 0,
					'page' => $this->blockPage,
					'pageCount' => $pageCount,
					'fields' => $fields,
					'return' => $returnCustomer,
					'returnApproach' => isset($row['IS_REPEATED_APPROACH']) && $row['IS_REPEATED_APPROACH'] == 'Y',
					'assignedBy' => $row['ASSIGNED_BY'],
					'required' => $required,
					'required_fm' => $requiredFm
				);
			}
			// get iblock sections
			if (
				$iblockSects &&
				Loader::includeModule('iblock')
			)
			{
				$res = \Bitrix\Iblock\SectionTable::getList([
					'select' => [
						'ID', 'NAME'
					],
					'filter' => [
						'ID' => $iblockSects
					]
				]);
				$iblockSects = [];
				while ($row = $res->fetch())
				{
					$iblockSects[$row['ID']] = $row['NAME'];
				}
				unset($res, $row);
			}
			// get iblock elements
			if (
				$iblockElements &&
				Loader::includeModule('iblock')
			)
			{
				$res = \Bitrix\Iblock\ElementTable::getList([
					'select' => [
						'ID', 'NAME'
					],
					'filter' => [
						'ID' => $iblockElements
					]
				]);
				$iblockElements = [];
				while ($row = $res->fetch())
				{
					$iblockElements[$row['ID']] = $row['NAME'];
				}
				unset($res, $row);
			}
			// get crm entities
			if ($crmEntities)
			{
				foreach ($crmEntities as $entityType => &$entityIds)
				{
					$entityType = $this->resolveEntityLetter($entityType);
					$class = '\Bitrix\Crm\\' . $entityType . 'Table';
					if (!$entityType || !class_exists($class))
					{
						continue;
					}
					if ($entityType == 'contact')
					{
						$select = ['ID', 'NAME', 'LAST_NAME'];
					}
					else
					{
						$select = ['ID', 'TITLE'];
					}
					$entityPath = $this->getEntityPath($entityType);
					$res = $class::getList([
						'select' => $select,
						'filter' => [
							'ID' => $entityIds
						]
					]);
					$entityIds = [];
					while ($row = $res->fetch())
					{
						$id = $row['ID'];
						unset($row['ID']);
						$entityIds[$id] = \htmlspecialcharsbx(implode(' ', $row));
						$entityPath = str_replace('#' . $entityType . '_id#', $id, $entityPath);
						$entityIds[$id] = '<a href="' . $entityPath . '">' . $entityIds[$id] . '</a>';
					}
					unset($entityPath, $res, $row, $id);
				}
				unset($entityType, $entityIds, $class);
			}
			// get observers
			$observers = [];
			if (in_array('OBSERVER', $select))
			{
				$res = ObserverTable::getList([
					'select' => [
						'USER_ID', 'ENTITY_ID'
					],
					'filter' => [
						'=ENTITY_TYPE_ID' => $typeId,
						'=ENTITY_ID' => array_keys($result)
					],
					'order' => [
						'SORT' => 'ASC'
					]
				]);
				while ($row = $res->fetch())
				{
					$users[] = $row['USER_ID'];
					if (!isset($observers[$row['ENTITY_ID']]))
					{
						$observers[$row['ENTITY_ID']] = [];
					}
					$observers[$row['ENTITY_ID']][] = $row['USER_ID'];
				}
				unset($res, $row);
			}
			// get all users in common
			if ($users)
			{
				$res = \Bitrix\Main\UserTable::getList(array(
					'select' => array(
						'ID', 'NAME', 'LAST_NAME', 'PERSONAL_PHOTO'
					),
					'filter' => array(
						'ID' => array_unique($users)
					)
				));
				$users = [];
				while ($row = $res->fetch())
				{
					if ($row['PERSONAL_PHOTO'])
					{
						$row['PERSONAL_PHOTO'] = \CFile::ResizeImageGet(
							$row['PERSONAL_PHOTO'],
							$this->avatarSize,
							BX_RESIZE_IMAGE_EXACT
						);
						if ($row['PERSONAL_PHOTO'])
						{
							$row['PERSONAL_PHOTO'] = $row['PERSONAL_PHOTO']['src'];
						}
					}
					$buyerLink = '';
					$link = str_replace(
						'#user_id#',
						$row['ID'],
						$this->arParams['PATH_TO_USER']
					);
					if (isset($this->arParams['PATH_TO_BUYER_PROFILE']))
					{
						$buyerLink = str_replace(
							'#user_id#',
							$row['ID'],
							$this->arParams['PATH_TO_BUYER_PROFILE']
						);
					}
					$users[$row['ID']] = [
						'id' => $row['ID'],
						'title' => \htmlspecialcharsbx(\CUser::FormatName(
							$this->arParams['~NAME_TEMPLATE'],
							$row, true, false
						)),
						'picture' => $row['PERSONAL_PHOTO'],
						'link' => $link
					];
					// tmp
					$userTitle = $users[$row['ID']]['title'];
					$users[$row['ID']]['html'] = '<a href="' . $link . '">' . $userTitle . '</a>';
					if (!empty($buyerLink))
					{
						$users[$row['ID']]['buyerHtml'] = '<a href="' . $buyerLink . '">' . $userTitle . '</a>';
					}
				}
				unset($res, $row);
			}
			// get file's infos
			if ($fileIds)
			{
				$fileViewer = new \Bitrix\Crm\UserField\FileViewer($typeId);
				$fileIds = array_unique($fileIds);
				$res = \Bitrix\Main\FileTable::getList([
					'select' => [
						'ID', 'ORIGINAL_NAME'
					],
					'filter' => [
						'ID' => $fileIds
					]
				]);
				$fileIds = [];
				while ($row = $res->fetch())
				{
					$fileIds[$row['ID']] = \htmlspecialcharsbx($row['ORIGINAL_NAME']);
				}
				unset($res, $row);
			}
			// tracking analytic
			$traces = [];
			if ($result && in_array('TRACKING_SOURCE_ID', $select))
			{
				$traces = $this->getTracesById(array_keys($result));
			}
			// refill result array, if we have any new data
			if ($users || $fileIds || $iblockSects || $iblockElements || $crmEntities || $traces)
			{
				foreach ($result as $id => &$item)
				{
					// add observers in each items
					if ($observers)
					{
						if (isset($observers[$id]))
						{
							foreach ($observers[$id] as &$obsId)
							{
								if (isset($users[$obsId]))
								{
									// tmp, @todo: make more universal
									$obsId = $users[$obsId]['html'];
								}
							}
							unset($obsId);
							$item['fields'][] = array(
								'code' => 'OBSERVER',
								'title' => Loc::getMessage('CRM_KANBAN_FIELD'),
								'value' => implode('; ', $observers[$id]),
								'html' => true
							);
						}
					}
					// add other users
					foreach ($item['fields'] as &$field)
					{
						if (in_array($field['code'], $this->usersIdFields))
						{
							$newValue = [];
							foreach ((array)$field['value'] as $uid)
							{
								if (isset($users[$uid]))
								{
									// tmp, @todo: make more universal
									if ($field['code'] == 'ASSIGNED_BY_ID' || $field['code'] == 'RESPONSIBLE_ID')
									{
										$newValue[] = $users[$uid];
									}
									elseif ($type == $types['order'] && $field['code'] == 'USER')
									{
										$newValue[] = $users[$uid]['buyerHtml'];
									}
									else
									{
										$newValue[] = $users[$uid]['html'];
									}
								}
							}
							// tmp, @todo: make more universal
							if ($field['code'] == 'ASSIGNED_BY_ID' || $field['code'] == 'RESPONSIBLE_ID' || $field['code'] == 'USER')
							{
								$field['value'] = array_shift($newValue);
							}
							else
							{
								$field['value'] = implode('; ', $newValue);
							}
							$field['html'] = true;
						}
					}
					unset($field);
					// add files' infos
					if ($fileIds)
					{
						foreach ($item['fields'] as &$field)
						{
							if ($field['type'] == 'file')
							{
								foreach ($field['value'] as &$fid)
								{
									if (isset($fileIds[$fid]))
									{
										$fid = '<a href="' . $fileViewer->getUrl($id, $field['code'], $fid) . '">' .
											   		$fileIds[$fid] .
												'</a>';
										$field['html'] = true;
									}
								}
								unset($fid);
								$field['value'] = implode('; ', $field['value']);
							}
						}
						unset($field);
					}
					// add iblock sections
					if ($iblockSects)
					{
						foreach ($item['fields'] as &$field)
						{
							if ($field['type'] == 'iblock_section')
							{
								foreach ($field['value'] as &$section)
								{
									if (isset($iblockSects[$section]))
									{
										$section = $iblockSects[$section];
									}
								}
								unset($section);
								$field['value'] = implode('; ', $field['value']);
							}
						}
						unset($field);
					}
					// add iblock elements
					if ($iblockElements)
					{
						foreach ($item['fields'] as &$field)
						{
							if ($field['type'] == 'iblock_element')
							{
								foreach ($field['value'] as &$element)
								{
									if (isset($iblockElements[$element]))
									{
										$element = $iblockElements[$element];
									}
								}
								unset($element);
								$field['value'] = implode('; ', $field['value']);
							}
						}
						unset($field);
					}
					// crm entities
					if ($crmEntities)
					{
						foreach ($item['fields'] as &$field)
						{
							if ($field['type'] == 'crm')
							{
								foreach ($field['value'] as &$entity)
								{
									[$entityType, $entityId] = explode('_', $entity);
									if (isset($crmEntities[$entityType][$entityId]))
									{
										$entity = $crmEntities[$entityType][$entityId];
									}
									unset($entityId, $entityType);
								}
								unset($entity);
								$field['value'] = implode('; ', $field['value']);
								$field['html'] = true;
							}
						}
						unset($field);
					}
					// tracking system
					if (isset($traces[$id]))
					{
						$item['fields'][] = array(
							'code' => 'TRACKING_SOURCE_ID',
							'title' => Loc::getMessage('CRM_KANBAN_TRACKING_SOURCE_ID'),
							'value' => $traces[$id]['NAME']
						);
					}
				}
				unset($item);
			}
			unset(
				$assignedIds, $users, $observers, $fileIds,
				$iblockSects, $iblockElements
			);
			if (!$sorting)
			{
				$result = $this->sort($result);
			}
		}

		return $result;
	}

	/**
	 * Get all activities by id's and type of entity.
	 * @param array $activity Id's.
	 * @param array $errors Count of errors (deadline).
	 * @return array
	 */
	protected function getActivityCounters($activity, &$errors = array())
	{
		if (empty($activity))
		{
			return array();
		}

		$return = array();
		$activity = array_unique($activity);

		//make filter
		$filter = array(
			'BINDINGS' => array()
		);
		$typeId = \CCrmOwnerType::ResolveID($this->type);
		foreach ($activity as $id)
		{
			$filter['BINDINGS'][] = array(
				'OWNER_ID' => $id,
				'OWNER_TYPE_ID' => $typeId
			);
		}

		// counters
		$date = new \Bitrix\Main\Type\DateTime;
		$date->add('-'.date('G').' hours')->add('-'.date('i').' minutes')->add('+1 day');//@ #81166
		$res = \CCrmActivity::GetList(
			array(),
			array_merge(
				$filter,
				array(
					'=COMPLETED' => 'N',
					'<=DEADLINE' => $date
				)
			),
			false, false,
			array(
				'ID', 'OWNER_ID'
			)
		);
		$fetched = array();
		while ($row = $res->fetch())
		{
			if (!isset($fetched[$row['ID']]))
			{
				$fetched[$row['ID']] = true;
				if (!isset($errors[$row['OWNER_ID']]))
				{
					$errors[$row['OWNER_ID']] = 0;
				}
				$errors[$row['OWNER_ID']]++;
			}
		}

		// gets multi bindings
		$multi = [];
		$res = \Bitrix\Crm\ActivityBindingTable::getList([
			'filter' => [
				'OWNER_ID' => $activity,
				'OWNER_TYPE_ID' => $typeId
			]
		]);
		while ($row = $res->fetch())
		{
			if (!isset($multi[$row['ACTIVITY_ID']]))
			{
				$multi[$row['ACTIVITY_ID']] = [];
			}
			$multi[$row['ACTIVITY_ID']][] = $row['OWNER_ID'];
		}

		// get base activity
		$res = \CCrmActivity::GetList(
			array(),
			$filter,
			false,
			false,
			array(
				'ID', 'COMPLETED', 'OWNER_ID'
			)
		);
		while ($row = $res->fetch())
		{
			if (!isset($return[$row['OWNER_ID']]))
			{
				$return[$row['OWNER_ID']] = array();
			}
			if (!isset($return[$row['OWNER_ID']][$row['COMPLETED']]))
			{
				$return[$row['OWNER_ID']][$row['COMPLETED']] = 0;
			}
			$return[$row['OWNER_ID']][$row['COMPLETED']]++;
			// multi
			if (isset($multi[$row['ID']]))
			{
				foreach ($multi[$row['ID']] as $ownerId)
				{
					if (!isset($return[$ownerId]))
					{
						$return[$ownerId] = [];
					}
					if (!isset($return[$ownerId][$row['COMPLETED']]))
					{
						$return[$ownerId][$row['COMPLETED']] = 0;
					}
					$return[$ownerId][$row['COMPLETED']]++;
				}
			}
		}

		// get waits
		$waits = \Bitrix\Crm\Pseudoactivity\WaitEntry::getRecentIDsByOwner(
			$typeId, $activity
		);
		if ($waits)
		{
			foreach ($waits as $row)
			{
				if (!isset($return[$row['OWNER_ID']]['N']))
				{
					$return[$row['OWNER_ID']]['N'] = 0;
				}
				$return[$row['OWNER_ID']]['N']++;
			}
		}

		return $return;
	}

	/**
	 * Get additional fields for more-button.
	 * @param bool $clearCache Clear static cache.
	 * @return array
	 */
	protected function getAdditionalFields($clearCache = false)
	{
		static $additional = null;

		if ($clearCache)
		{
			$additional = null;
		}

		if ($additional === null)
		{
			$additional = array();
			$ufExist = false;
			$exist = $this->additionalSelect;

			//base fields
			foreach ($this->additionalSelect as $key => $title)
			{
				if (mb_strpos($key, 'UF_') === 0)
				{
					$ufExist = true;
				}
				else
				{
					$additional[$key] = array(
						'title' => \htmlspecialcharsbx($title),
						'type' => 'string',
						'code' => $key
					);
				}
			}
			unset($key, $title);

			//user fields
			if ($ufExist)
			{
				$enumerations = array();
				foreach ($this->userFields as $row)
				{
					if (isset($this->additionalSelect[$row['FIELD_NAME']]))
					{
						$additional[$row['FIELD_NAME']] = array(
							'title' => htmlspecialcharsbx($row['EDIT_FORM_LABEL']),
							'new' => !in_array($row['FIELD_NAME'], $exist) ? 1 : 0,
							'type' => $row['USER_TYPE_ID'],
							'code' => $row['FIELD_NAME'],
							'settings' => $row['SETTINGS'],
							'enumerations' => [],
						);
						if ($row['USER_TYPE_ID'] == 'enumeration')
						{
							$enumerations[$row['ID']] = $row['FIELD_NAME'];
						}
					}
				}
				unset($row);

				if (!empty($enumerations))
				{
					$enumUF = new CUserFieldEnum;
					$resEnum = $enumUF->getList(
						array(),
						array(
							'USER_FIELD_ID' => array_keys($enumerations)
						)
					);
					while ($rowEnum = $resEnum->fetch())
					{
						$additional[$enumerations[$rowEnum['USER_FIELD_ID']]]['enumerations'][$rowEnum['ID']] = $rowEnum['VALUE'];
					}
					unset($enumerations, $enumUF, $resEnum, $rowEnum);
				}
			}
		}

		return $additional;
	}

	/**
	 * Get additional fields for quick form.
	 * @return array
	 */
	protected function getAdditionalEditFields()
	{
		return $this->additionalEdit;
	}

	/**
	 * Gets sections for fields popup.
	 * @param array $config External config.
	 * @return array
	 */
	protected function getFieldsSections($config)
	{
		$sections = [];
		if (is_array($config) && !empty($config))
		{
			foreach ($config as $configSection)
			{
				if (
					isset($configSection['elements']) &&
					is_array($configSection['elements'])
				)
				{
					$tmpItems = $configSection['elements'];
					$configSection['elements'] = [];
					foreach ($tmpItems as $item)
					{
						if ($item['name'] == 'OPPORTUNITY_WITH_CURRENCY')
						{
							$configSection['elements']['OPPORTUNITY'] = [
								'name' => 'OPPORTUNITY',
								'title' => ''
							];
						}
						$configSection['elements'][$item['name']] = [
							'name' => $item['name'],
							'title' => $item['title']
						];
					}

					if (in_array($configSection['name'], ['main', 'additional', 'properties']))
					{
						if ($configSection['name'] == 'additional')
						{
							$configSection['elements'] = '*';
						}
						$sections[] = $configSection;
					}
				}
			}
		}

		return $sections;
	}

	/**
	 * Gets current user perms.
	 * @return CCrmPerms
	 */
	protected function getCurrentUserPermissions()
	{
		static $userPerms = null;
		if ($userPerms === null)
		{
			$userPerms = \CCrmPerms::getCurrentUserPermissions();
		}
		return $userPerms;
	}

	/**
	 * Make some actions (set, update, etc).
	 * @return void
	 */
	protected function makeAction()
	{
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
		$action = $request->get('action');
		$id = $request->get('entity_id');

		// some actions for editor
		if ($action == 'get' && check_bitrix_sessid())
		{
			$ajaxParams = (array)$request->get('ajaxParams');
			if (isset($ajaxParams['editorReset']))
			{
				$this->resetCardFields();
			}
			if (isset($ajaxParams['editorSetCommon']))
			{
				$this->setCommonCardFields();
			}
		}

		//update fields
		if ($action && $id && check_bitrix_sessid())
		{
			$statuses = $this->getStatuses();
			$status = $request->get('status');
			$ids = is_array($id) ? $id : [$id];
			// skip action delete
			if ($action == 'status' && $status == 'DELETED')
			{
				$ids = [];
			}

			foreach ($ids as $id)
			{
				//delete
				if ($action == 'status' && $status == 'DELETED')
				{
					$this->actionDelete($id);
				}
				//change status / stage
				if ($action == 'status' && isset($statuses[$status]))
				{
					$result = $this->actionUpdateEntityStatus($id, $status, $statuses);
					if (!$result->isSuccess())
					{
						foreach ($result->getErrorMessages() as $errorMessage)
						{
							$this->setError($errorMessage);
						}
					}
				}
			}

			if ($this->arParams['IS_AJAX'] != 'Y')
			{
				$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
				\LocalRedirect($uri->deleteParams(array('action', 'entity_id', 'status'))->getUri());
			}
		}

		//subscribe / unsunbscribe
		$supervisor = $request->get('supervisor');
		if ($request->get('apply_filter') == 'Y')
		{
			if (\Bitrix\Crm\Kanban\SupervisorTable::isSupervisor($this->type))
			{
				$supervisor = 'N';
			}
		}
		if ($supervisor)
		{
			\Bitrix\Crm\Kanban\SupervisorTable::set($this->type, $supervisor=='Y');
			$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
			\LocalRedirect($uri->deleteParams(array('supervisor', 'clear_filter'))->getUri());
		}
	}

	/**
	 * Current user is crm admin?
	 * @return boolean
	 */
	protected function isCrmAdmin()
	{
		$crmPerms = new \CCrmPerms($this->uid);
		return $crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
	}

	/**
	 * Can current user edit settings or not.
	 * @return mixed
	 */
	protected function canEditSettings()
	{
		return $GLOBALS['USER']->canDoOperation('edit_other_settings');
	}

	/**
	 * Get admins and group moderators.
	 * @return array
	 */
	protected function getAdmins()
	{
		$users = array();

		$userQuery = new \Bitrix\Main\Entity\Query(
			\Bitrix\Main\UserTable::getEntity()
		);
		// set select
		$userQuery->setSelect(array(
			'ID', 'LOGIN', 'NAME', 'LAST_NAME',
			'SECOND_NAME', 'PERSONAL_PHOTO'
		));
		// set runtime for inner group ID=1 (admins)
		$userQuery->registerRuntimeField(
			null,
			new Bitrix\Main\Entity\ReferenceField(
				'UG',
				\Bitrix\Main\UserGroupTable::getEntity(),
				array(
					'=this.ID' => 'ref.USER_ID',
					'=ref.GROUP_ID' => new Bitrix\Main\DB\SqlExpression(1)
				),
				array(
					'join_type' => 'INNER'
				)
			)
		);
		// set filter
		$date = new \Bitrix\Main\Type\DateTime;
		$userQuery->setFilter(array(
			'=ACTIVE' => 'Y',
			'!ID' => $this->uid,
			array(
				'LOGIC' => 'OR',
				'<=UG.DATE_ACTIVE_FROM' => $date,
				'UG.DATE_ACTIVE_FROM' => false
			),
			array(
				'LOGIC' => 'OR',
				'>=UG.DATE_ACTIVE_TO' => $date,
				'UG.DATE_ACTIVE_TO' => false
			)
		));
		$res = $userQuery->exec();
		while ($row = $res->fetch())
		{
			if ($row['PERSONAL_PHOTO'])
			{
				$row['PERSONAL_PHOTO'] = \CFile::ResizeImageGet(
					$row['PERSONAL_PHOTO'],
					$this->avatarSize,
					BX_RESIZE_IMAGE_EXACT
				);
				if ($row['PERSONAL_PHOTO'])
				{
					$row['PERSONAL_PHOTO'] = $row['PERSONAL_PHOTO']['src'];
				}
			}
			$users[$row['ID']] = array(
				'id' => $row['ID'],
				'name' => \CUser::FormatName(
					$this->arParams['~NAME_TEMPLATE'],
					$row, true, false
				),
				'img' => $row['PERSONAL_PHOTO']
			);
		}

		return $users;
	}

	/**
	 * Prepare fields for inline edit scheme.
	 * @return array
	 */
	protected function getSchemeForInlineEditor()
	{
		if (
			$this->type == $this->types['lead'] ||
			$this->type == $this->types['deal'] ||
			$this->type == $this->types['order']
		)
		{
			$availableFields = array_keys($this->additionalSelect);

			// include external component class for call public method
			$componentName = 'bitrix:crm.'.mb_strtolower($this->type) . '.details';
			$className = \CBitrixComponent::includeComponentClass(
				$componentName
			);
			/** @var CCrmDealDetailsComponent $crmCmp */
			$crmCmp = new $className;
			$crmCmp->initComponent($componentName);
			$crmCmp->arResult = [
				'READ_ONLY' => false,
				'PATH_TO_USER_PROFILE' => ''
			];
			$crmCmp->setEntityID(0);
			if ($this->type === $this->types['order'])
			{
				/** @var CCrmOrderDetailsComponent $crmCmp */
				$crmCmp->obtainOrder();
			}
			$fieldInfos = $crmCmp->prepareFieldInfos();
			$this->userFields = $crmCmp->prepareEntityUserFields();

			$configuration =  ($this->type === $this->types['order']) ? $crmCmp->prepareKanbanConfiguration() : $crmCmp->prepareConfiguration();
			$this->arResult['FIELDS_SECTIONS'] = $this->getFieldsSections(
				$configuration
			);

			if (in_array('OPPORTUNITY', $availableFields))
			{
				$availableFields[] = 'OPPORTUNITY_WITH_CURRENCY';
			}

			// prepare fields
			foreach ($fieldInfos as $field)
			{
				if (in_array($field['name'], $availableFields))
				{
					$this->schemeFields[$field['name']] = $field;
				}
			}
			if (isset($this->schemeFields['TITLE']['isHeading']))
			{
				unset($this->schemeFields['TITLE']['isHeading']);
			}
			if (isset($this->schemeFields['TITLE']['visibilityPolicy']))
			{
				unset($this->schemeFields['TITLE']['visibilityPolicy']);
			}

			unset($crmCmp, $field, $componentName, $className, $fieldInfos);
		}

		// build scheme
		if ($this->schemeFields)
		{
			$scheme = [
				[
					'name' => 'main',
					'title' => '',
					'type' => 'section',
					'elements' => array_values($this->schemeFields)
				]
			];

			return $scheme;
		}

		return [];
	}

	/**
	 * Gets config ID for editors.
	 * @return string
	 */
	protected function getEditorConfigId()
	{
		static $configId = null;

		if ($configId === null)
		{
			$configId = 'quick_editor_v6_'.
				mb_strtolower($this->type) . '_' .
						(int) $this->categoryId;
		}


		return $configId;
	}

	/**
	 * Common or not editor config.
	 * @return bool
	 */
	protected function isEditorConfigCommon()
	{
		$scope = \CUserOptions::getOption(
			'crm.entity.editor',
			$this->getEditorConfigId() . '_scope'
		);
		if (!$scope)
		{
			return true;
		}
		return ($scope == EntityEditorConfigScope::COMMON);
	}

	/**
	 * Runs automation processes on change entity.
	 * @param int $id Entity id.
	 * @param array|null $fields Updated fields.
	 * @return void
	 */
	protected function runAutomationOnUpdate(int $id, array $fields = null): void
	{
		if (
			$this->type == $this->types['lead'] ||
			$this->type == $this->types['deal']
		)
		{
			$errors = null;
			\CCrmBizProcHelper::autoStartWorkflows(
				\CCrmOwnerType::resolveID($this->type),
				$id,
				\CCrmBizProcEventType::Edit,
				$errors
			);

			if ($fields)
			{
				$starter = new \Bitrix\Crm\Automation\Starter(
					($this->type == $this->types['lead']) ? \CCrmOwnerType::Lead : \CCrmOwnerType::Deal,
					$id
				);
				$starter->setUserIdFromCurrent()->runOnUpdate($fields, []);
			}
		}
	}

	/**
	 * Base executable method.
	 * @return array
	 */
	public function executeComponent()
	{
		if (!$this->init())
		{
			return;
		}

		$schemeInlineEdit = $this->getSchemeForInlineEditor();

		$this->arResult['ITEMS'] = array();
		$this->arResult['ADMINS'] = $this->getAdmins();
		$this->arResult['MORE_FIELDS'] = $this->getAdditionalFields();
		$this->arResult['MORE_EDIT_FIELDS'] = $this->getAdditionalEditFields();
		$this->arResult['FIELDS_DISABLED'] = $this->disableMoreFields;
		$this->arResult['CATEGORIES'] = array();

		$isOLinstalled = IsModuleInstalled('imopenlines');
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
		$action = $request->get('action');
		$type = $this->type;
		$types = $this->types;

		//new actions format
		if ($action && is_callable(array($this, 'action' . $action)))
		{
			if (!check_bitrix_sessid())
			{
				return array(
					'ERROR' => Loc::getMessage('CRM_KANBAN_ERROR_SESSION_EXPIRED'),
					'FATAL' => true
				);
			}
			else
			{
				return $this->{'action' . $action}();
			}
		}
		else
		{
			$this->makeAction();
		}

		if ($this->arParams['EMPTY_RESULT'] != 'Y')
		{
			//check other perms (additional for lead converting)
			$userPermissions = $this->getCurrentUserPermissions();
			$this->arResult['ACCESS_CONFIG_PERMS'] = $this->isCrmAdmin();
			$this->arResult['ACCESS_IMPORT'] = $type == $types['lead']
													? \CCrmLead::CheckImportPermission($userPermissions)
													: \CCrmDeal::CheckImportPermission($userPermissions);
			if ($type == $types['lead'])
			{
				$this->arResult['CAN_CONVERT_TO_CONTACT'] = \CCrmContact::CheckCreatePermission($userPermissions);
				$this->arResult['CAN_CONVERT_TO_COMPANY'] = \CCrmCompany::CheckCreatePermission($userPermissions);
				$this->arResult['CAN_CONVERT_TO_DEAL'] = \CCrmDeal::CheckCreatePermission($userPermissions);
				$this->arResult['CONVERSION_CONFIG'] = \Bitrix\Crm\Conversion\DealConversionConfig::load();
				if ($this->arResult['CONVERSION_CONFIG'] === null)
				{
					$this->arResult['CONVERSION_CONFIG'] = \Bitrix\Crm\Conversion\DealConversionConfig::getDefault();
				}
			}

			//output
			if ($this->arParams['ONLY_COLUMNS'] == 'Y')
			{
				$this->arResult['ITEMS'] = array(
					'items' => array_values($this->items),
					'columns' => array_values($this->getColumns())
				);
			}
			else
			{
				$items = array();
				$columns = $this->getColumns();
				if (!empty($this->arParams['ADDITIONAL_FILTER']))
				{
					$filter = $this->arParams['ADDITIONAL_FILTER'];
					if (isset($filter['COLUMN']))
					{
						$filter[$this->statusKey] = $filter['COLUMN'];
						unset($filter['COLUMN']);
					}
					$items = $this->getItems($filter);
				}
				else
				{
					foreach ($columns as $k => $column)
					{
						if (!$column['dropzone'])
						{
							$filter = array();
							$filter[$this->statusKey] = $column['id'];
							$items += $this->getItems($filter);
						}
					}
				}

				$this->arResult['ITEMS'] = array(
					'columns' => array_values($columns),
					'items' => $items
				);
			}

			if ($this->arParams['ONLY_COLUMNS'] == 'N')
			{
				$contacts = $this->getContragents('contact');
				$companies = $this->getContragents('company');
				if ($type == $types['lead'])
				{
					$this->arResult['ITEMS']['items'] = $this->fillFMfields($this->arResult['ITEMS']['items'], 'lead');
				}

				//set contragents to items
				if (!empty($this->arResult['ITEMS']['items']))
				{
					foreach ($this->arResult['ITEMS']['items'] as &$item)
					{
						$contragent = [];
						$contragents = [];

						if (
							!empty($item['contactId'])
							&& isset($contacts[$item['contactId']])
						)
						{
							$contact = $contacts[$item['contactId']];
							$contragents['contact'] = $contact;
							$item['contactName'] = $contact['TITLE'];
							$item['contactLink'] = $contact['URL'];
							$item['contactTooltip'] = \CCrmViewHelper::PrepareEntityBaloonHtml([
								'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
								'ENTITY_ID' => $item['contactId'],
								'TITLE' => $contact['~TITLE'],
								'PREFIX' => $this->type . '_' . $item['id'],
							]);
						}

						if (
							!empty($item['companyId'])
							&& isset($companies[$item['companyId']])
						)
						{
							$company = $companies[$item['companyId']];
							$contragents['company'] = $company;
							$item['companyName'] = $company['TITLE'];
							$item['companyLink'] = $company['URL'];
							$item['companyTooltip'] = \CCrmViewHelper::PrepareEntityBaloonHtml([
								'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
								'ENTITY_ID' => $item['companyId'],
								'TITLE' => $company['~TITLE'],
								'PREFIX' => $this->type . '_' . $item['id'],
							]);
						}

						//phone, email, chat
						foreach ($contragents as $contragentType => $contragent)
						{
							if (isset($contragent['FM_VALUES']) && !empty($contragent['FM_VALUES']))
							{
								foreach ($contragent['FM_VALUES'] as $code => $values)
								{
									if ($code == 'im') // we need only chat for im
									{
										if ($isOLinstalled)
										{
											foreach ($values as $val)
											{
												$val = isset($val['VALUE'])
													? $val['VALUE']
													: $val['value'];
												if ((mb_strpos($val, 'imol|') === 0))
												{
													$item[$code][$contragentType] = $val;
													break;
												}
											}
										}
									}
									else
									{
										$item[$code][$contragentType] = $values;
									}
									$item['required_fm'][mb_strtoupper($code)] = false;
								}
							}
						}
						//same from leads
						if (isset($item['FM_VALUES']))
						{
							foreach ($item['FM_VALUES'] as $code => $values)
							{
								if ($code == 'im') // we need only chat for im
								{
									if ($isOLinstalled)
									{
										foreach ($values as $val)
										{
											$val = isset($val['VALUE'])
													? $val['VALUE']
													: $val['value'];
											if (mb_strpos($val, 'imol|') === 0)
											{
												$item[$code][] = $val;
												break;
											}
										}
									}
								}
								else
								{
									$item[$code] = $values;
								}
								$item['required_fm'][mb_strtoupper($code)] = false;
							}
							unset($item['FM_VALUES'], $item['FM']);
						}
					}
					unset($item);
				}

				//get activity
				if (!empty($this->arResult['ITEMS']['items']))
				{
					if ($type == $types['deal'] || $type == $types['lead'] || $type == $types['order'])
					{
						$activityCounters = $this->getActivityCounters(
							array_keys($this->arResult['ITEMS']['items']),
							$errors
						);
						foreach ($activityCounters as $id => $actCC)
						{
							$this->arResult['ITEMS']['items'][$id]['activityProgress'] = isset($actCC['N']) ? $actCC['N'] : 0;
							$this->arResult['ITEMS']['items'][$id]['activityTotal'] = isset($actCC['Y']) ? $actCC['Y'] : 0;
							if (isset($errors[$id]))
							{
								$this->arResult['ITEMS']['items'][$id]['activityErrorTotal'] = $errors[$id];
							}
						}
					}

					$this->arResult['ITEMS']['items'] = array_values($this->arResult['ITEMS']['items']);
				}
			}

			// get categories for deals
			if (
				$type == $types['deal']
			)
			{
				$catPerm = array_fill_keys(
					\CCrmDeal::GetPermittedToReadCategoryIDs($userPermissions),
					true
				);
				foreach (DealCategory::getAll(true) as $k => $catItem)
				{;
					if (isset($catPerm[$catItem['ID']]))
					{
						$this->arResult['CATEGORIES'][$k] = $catItem;
					}
				}
			}
		}

		$this->arResult['ITEMS']['last_id'] = $this->getLastId();
		$this->arResult['ITEMS']['scheme_inline'] = $schemeInlineEdit;
		$this->arResult['ITEMS']['customFields'] = array_keys($this->arResult['MORE_FIELDS']);

		// items for demo import
		if (
			isset($this->arResult['ITEMS']['columns'][0]['id']) &&
			isset($this->arResult['ITEMS']['columns'][0]['count']) &&
			(
				$this->blockPage * $this->blockSize
				>=
				$this->arResult['ITEMS']['columns'][0]['count']
			)
		)
		{
			if (
				$type == $types['lead'] ||
				$type == $types['deal']
			)
			{
				$this->arResult['ITEMS']['items'] = array_merge(
					$this->arResult['ITEMS']['items'],
					[[
						 'id' => -1,
						 'name' => null,
						 'countable' => false,
						 'droppable' => true,
						 'draggable' => true,
						 'columnId' => $this->arResult['ITEMS']['columns'][0]['id'],
						 'special_type' => 'import'
					 ]]
				);
			}
			$this->arResult['ITEMS']['items'] = array_merge(
				$this->arResult['ITEMS']['items'],
				[[
					 'id' => -2,
					 'name' => null,
					 'countable' => false,
					 'droppable' => true,
					 'draggable' => true,
					 'columnId' => $this->arResult['ITEMS']['columns'][0]['id'],
					 'special_type' => 'rest'
				 ]]
			);
		}

		if ($this->arParams['IS_AJAX'] == 'Y')
		{
			return $this->arResult;
		}
		else
		{
			$GLOBALS['APPLICATION']->setTitle(Loc::getMessage('CRM_KANBAN_TITLE2_' . $this->type));
			$this->IncludeComponentTemplate();
		}
	}

	/**
	 * Get request-var for http-hit.
	 * @param string $var Request-var code.
	 * @return mixed
	 */
	protected function request($var)
	{
		static $request = null;

		if ($request === null)
		{
			$context = Application::getInstance()->getContext();
			$request = $context->getRequest();
		}

		return $request->get($var);
	}

	/**
	 * Convert charset from utf-8 to site.
	 * @param mixed $data
	 * @param bool $fromUtf Direction - from (true) or to (false).
	 * @return mixed
	 */
	protected function convertUtf($data, $fromUtf)
	{
		if (SITE_CHARSET != 'UTF-8')
		{
			$from = $fromUtf ? 'UTF-8' : SITE_CHARSET;
			$to = !$fromUtf ? 'UTF-8' : SITE_CHARSET;
			if (is_array($data))
			{
				$data = $this->application->ConvertCharsetArray($data, $from, $to);
			}
			else
			{
				$data = $this->application->ConvertCharset($data, $from, $to);
			}
		}
		return $data;
	}

	/**
	 * Notify admin for get access.
	 * @return array
	 */
	protected function actionNotifyAdmin()
	{
		if (
			($userId = $this->request('userId')) &&
			Loader::includeModule('im')
		)
		{
			$admins = $this->getAdmins();
			if (isset($admins[$userId]))
			{
				$params = $this->arParams;
				//settings page
				if ($params['ENTITY_TYPE_CHR'] == 'DEAL')
				{
					$pathColumnEdit = '/crm/configs/status/?ACTIVE_TAB=status_tab_DEAL_STAGE';
				}
				elseif ($params['ENTITY_TYPE_CHR'] == 'LEAD')
				{
					$pathColumnEdit = '/crm/configs/status/?ACTIVE_TAB=status_tab_STATUS';
				}
				else
				{
					$pathColumnEdit = '/crm/configs/status/?ACTIVE_TAB=status_tab_'. $params['ENTITY_TYPE_CHR'] .'_STATUS';
				}
				\CIMNotify::Add(array(
					'TO_USER_ID' => $userId,
					'FROM_USER_ID' => $this->uid,
					'NOTIFY_TYPE' => IM_NOTIFY_FROM,
					'NOTIFY_MODULE' => 'crm',
					'NOTIFY_TAG' => 'CRM|NOTIFY_ADMIN|'.$userId.'|'.$this->userId,
					'NOTIFY_MESSAGE' => Loc::getMessage('CRM_ACCESS_NOTIFY_MESSAGE', array(
						'#URL#' => $pathColumnEdit
					))
				));
			}
		}
		return array(
			'status' => 'success'
		);
	}

	/**
	 * Add new stage, update stage, move stage.
	 * @return array
	 */
	protected function actionModifyStage()
	{
		if ($this->isCrmAdmin())
		{
			// vars
			$stages = $this->getColumns(
				true,
				true,
				array(
					'originalColumns' => true
				)
			);
			$delete = $this->request('delete');
			$columnId = $this->request('columnId');
			$columnName = $this->request('columnName');
			$columnColor = $this->request('columnColor');
			$afterColumnId = $this->request('afterColumnId');

			$sort = 0;
			if (
				$afterColumnId !== null &&
				$afterColumnId == '0'
			)
			{
				$sort = 0;
			}
			elseif (isset($stages[$afterColumnId]))
			{
				$sort = $stages[$afterColumnId]['real_sort'];
			}
			elseif (isset($stages[$columnId]))
			{
				$sort = $stages[$columnId]['real_sort'];
			}
			if ($columnName)
			{
				$columnName = $this->convertUtf($this->request('columnName'), true);
			}

			$fields = array(
				'ENTITY_ID' => $this->getStatusEntityId(),
				'SORT' => ++$sort
			);
			if ($columnName)
			{
				$fields['NAME'] = $columnName;
				$fields['NAME_INIT'] = $columnName;
			}
			if ($columnColor)
			{
				$fields['COLOR'] = $columnColor;
			}
			if ($columnId != '' && isset($stages[$columnId]))
			{
				if ($delete)
				{
					$deleteResult = [];
					$r = $this->deleteStage($columnId, $stages);
					if (!$r->isSuccess())
					{
						$errors = $r->getErrorMessages();
						$deleteResult = [
							'ERROR' => current($errors)
						];
					}

					return $deleteResult;
				}
				else
				{
					$internalId = ($this->type == $this->types['order']) ? $columnId : $stages[$columnId]['real_id'];
					$r = $this->updateStage($internalId, $fields);
				}
			}
			else
			{
				$r = $this->addStage($fields);
				$internalId = $r->getId();
			}
			if ($r->isSuccess())
			{
				if ($this->type == $this->types['order'])
				{
					$statusId = $internalId;
				}
				else
				{
					$statusEntityId = $this->getStatusEntityId();
					$status = new \CCrmStatus($statusEntityId);
					$newStatus = $status->GetStatusById($internalId);
					$statusId = $newStatus['STATUS_ID'];
				}

				$stages = $this->getColumns(
					true,
					true,
					array(
						'originalColumns' => true
					)
				);
				if (isset($stages[$statusId]))
				{
					// range sorts
					$sort = 10;
					foreach ($stages as $stage)
					{
						$internalId = ($this->type == $this->types['order']) ? $stage['id'] : $stage['real_id'];
						$this->updateStage($internalId, ['SORT' => $sort]);
						$sort += 10;
					}
					if (!empty($columnColor))
					{
						$stages[$statusId]['color'] = $columnColor;
					}
					return \htmlspecialcharsback($stages[$statusId]);
				}
				else
				{
					return array(
						'ERROR' => 'Unknown error'
					);
				}
			}
			else
			{
				$errors = $r->getErrorMessages();
				return ['ERROR' => current($errors)];
			}
		}
		return array();
	}

	private function resortColumns()
	{

	}

	/**
	 * Delete status stage for 'actionModifyStage' method
	 *
	 * @param $columnId
	 * @param $stages
	 */
	private function deleteStage($columnId, $stages)
	{
		$result = new Result();

		if ($stages[$columnId]['type'] == 'WIN')
		{
			$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_WIN')));
			return $result;
		}

		if (!$this->isEmptyStage($columnId))
		{
			$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_NOT_EMPTY')));
			return $result;
		}

		$statusEntityId = $this->getStatusEntityId();
		if (empty($statusEntityId))
		{
			return $result;
		}

		if ($this->type == $this->types['order'])
		{
			$defaultStatuses = Order\OrderStatus::getDefaultStatuses();
			if (isset($defaultStatuses[$columnId]))
			{
				$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_SYSTEM')));
				return $result;
			}
			$statusCode = $columnId;

			if (!Loader::includeModule('sale'))
			{
				return $result;
			}

			$result = Sale\Internals\StatusTable::delete($columnId);
			if ($result->isSuccess())
			{
				$statusLanguageRaw = Sale\Internals\StatusLangTable::getList([
					'filter' => ['STATUS_ID' => $columnId],
					'select' => ['STATUS_ID', 'LID']
				]);

				while ($langPrimary = $statusLanguageRaw->fetch())
				{
					Sale\Internals\StatusLangTable::delete($langPrimary);
				}
			}
		}
		else
		{
			$internalId = $stages[$columnId]['real_id'];
			$statusObject = new \CCrmStatus($statusEntityId);
			$statusInfo = $statusObject->GetStatusById($internalId);
			if ($statusInfo['SYSTEM'] == 'Y')
			{
				$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_SYSTEM')));
				return $result;
			}

			$statusCode = $statusInfo['STATUS_ID'];

			$statusObject->delete($internalId);
			if (!empty($statusObject->GetLastError()))
			{
				$result->addError(new Error($statusObject->GetLastError()));
			}
		}

		if ($result->isSuccess())
		{
			$this->unsetColorSetting($statusCode);
		}

		return $result;
	}

	/**
	 * @param $stageId
	 *
	 * @return bool
	 */
	private function isEmptyStage($columnId)
	{
		if ($this->type == $this->types['order'])
		{
			$orderData = Order\Order::getList([
				'filter' => [$this->statusKey => $columnId]
			]);

			return !$orderData->fetch();
		}
		else
		{
			$provider = '\CCrm'.$this->type;
			$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';
			if (is_callable(array($provider, $method)))
			{
				$checkFilter = array(
					$this->statusKey => $columnId,
					'CHECK_PERMISSIONS' => 'N'
				);
				if ($this->type == $this->types['deal'])
				{
					$checkFilter['CATEGORY_ID'] = intval($this->categoryId);
				}
				$entities = $provider::$method(
					array('ID' => 'DESC'),
					$checkFilter,
					false,
					false,
					array('ID')
				);

				return !$entities->fetch();
			}
		}

		return true;
	}

	/**
	 * Save changes into status stage for 'actionModifyStage' method
	 *
	 * @param $id
	 * @param array $fields
	 */
	private function updateStage($id, array $fields = [])
	{
		$result = new Result();

		if (isset($fields['COLOR']))
		{
			$columnColor = $fields['COLOR'];
		}

		if ($this->type !== $this->types['order'])
		{
			unset($fields['COLOR']);
			$statusEntityId = $this->getStatusEntityId();
			$status = new \CCrmStatus($statusEntityId);
			$status->update($id, $fields);
			if (!empty($status->GetLastError()))
			{
				$result->addError(new Error($status->GetLastError()));
			}

			$newStatus = $status->GetStatusById($id);
			$statusId = $newStatus['STATUS_ID'];
		}
		else
		{
			if (!Loader::includeModule('sale'))
			{
				return $result;
			}

			$updateFields = array_intersect_key($fields, array_flip(['SORT', 'COLOR']));
			if (!empty($updateFields['COLOR']))
			{
				$updateFields['COLOR'] = "#".$updateFields['COLOR'];
			}
			if (!empty($updateFields))
			{
				$result = Sale\Internals\StatusTable::update($id, $updateFields);
			}
			if ($result->isSuccess() && !empty($fields['NAME']))
			{
				Sale\Internals\StatusLangTable::update(
					[
						'STATUS_ID' => $id,
						'LID' => static::getLanguageId(),
					],
					['NAME' => $fields['NAME']]
				);
			}
			$statusId = $id;
		}

		if (!empty($columnColor) && $result->isSuccess())
		{
			$this->setColorSetting($columnColor, $statusId);
		}

		return $result;
	}

	/**
	 * Add new status stage
	 *
	 * @param array $fields
	 * @return AddResult
	 */
	private function addStage(array $fields = [])
	{
		$result = new AddResult();

		if (isset($fields['COLOR']))
		{
			$columnColor = $fields['COLOR'];
		}

		if ($this->type !== $this->types['order'])
		{
			unset($fields['COLOR']);
			$statusEntityId = $this->getStatusEntityId();
			$status = new \CCrmStatus($statusEntityId);
			$id = $status->add($fields);
			if (!empty($status->GetLastError()))
			{
				$result->addError(new Error($status->GetLastError()));
			}
			else
			{
				$result->setId($id);
			}

			$newStatus = $status->GetStatusById($id);
			$statusId = $newStatus['STATUS_ID'];
		}
		else
		{
			if (!Loader::includeModule('sale'))
			{
				return $result;
			}

			$createFields = array_intersect_key($fields, array_flip(['SORT', 'COLOR']));

			$orderStatusIds = [];
			$statusRaw = Order\OrderStatus::getList([
				'select' => ['ID']
			]);
			while ($data = $statusRaw->fetch())
			{
				$orderStatusIds[$data['ID']] = $data;
			}

			do
			{
				$newId = chr(rand(65, 90)); //A-Z
				if (is_array($result) && count($result) >= 27)
				{
					$newId .= chr(rand(65, 90));
				}
			}
			while (isset($orderStatusIds[$newId]));
			$createFields['ID'] = $newId;
			$createFields['TYPE'] = Order\OrderStatus::TYPE;
			$result = Sale\Internals\StatusTable::add($createFields);
			if ($result->isSuccess())
			{
				Sale\Internals\StatusLangTable::add([
					'STATUS_ID' => $result->getId(),
					'LID' => static::getLanguageId(),
					'NAME' => $fields['NAME']
				]);
			}
			$statusId = $result->getId();
		}

		if (!empty($columnColor) && $result->isSuccess())
		{
			$this->setColorSetting($columnColor, $statusId);
		}

		return $result;
	}

	/**
	 * @return string
	 */
	private function getStatusEntityId()
	{
		switch ($this->type)
		{
			case $this->types['quote']:
				return 'QUOTE_STATUS';
			case $this->types['invoice']:
				return 'INVOICE_STATUS';
			case $this->types['lead']:
				return 'STATUS';
			case $this->types['order']:
				return  Order\OrderStatus::NAME;
			case $this->types['deal']:
			{
				$filter = $this->getFilter();
				if (isset($filter['CATEGORY_ID']) && $filter['CATEGORY_ID'] > 0)
				{
					return 'DEAL_STAGE_' . $filter['CATEGORY_ID'];
				}
				else
				{
					return 'DEAL_STAGE';
				}
			}
		}

		return '';
	}

	/**
	 * @return string
	 */
	private function getStatusColorCode()
	{
		switch ($this->type)
		{
			case $this->types['quote']:
				return 'CONFIG_STATUS_QUOTE_STATUS';
			case $this->types['invoice']:
				return 'CONFIG_STATUS_INVOICE_STATUS';
			case $this->types['lead']:
				return 'CONFIG_STATUS_STATUS';
			case $this->types['order']:
				return  \Bitrix\Crm\Color\OrderStatusColorScheme::getName();
			case $this->types['deal']:
			{
				$filter = $this->getFilter();
				if (isset($filter['CATEGORY_ID']) && $filter['CATEGORY_ID'] > 0)
				{
					return 'CONFIG_STATUS_DEAL_STAGE_' . $filter['CATEGORY_ID'];
				}
				else
				{
					return 'CONFIG_STATUS_DEAL_STAGE';
				}
			}
		}

		return '';
	}

	/**
	 * Update CRM color status option.
	 *
	 * @param $colorValue
	 * @param $statusId
	 */
	private function setColorSetting($colorValue, $statusId)
	{
		$codeColor = $this->getStatusColorCode();
		$colors = $this->getStatusColor($codeColor);
		if (!isset($colors[$statusId]))
		{
			$colors[$statusId] = array();
		}
		$colors[$statusId]['COLOR'] = '#' . $colorValue;
		Option::set('crm', $codeColor, serialize($colors));
	}

	/**
	 * Remove CRM color status value from color option.
	 *
	 * @param $colorValue
	 * @param $statusId
	 */
	private function unsetColorSetting($statusId)
	{
		$codeColor = $this->getStatusColorCode();
		$colors = $this->getStatusColor($codeColor);
		if (isset($colors[$statusId]))
		{
			unset($colors[$statusId]);
		}
		Option::set('crm', $codeColor, serialize($colors));
	}

	private function resortStages()
	{

	}

	/**
	 * Remove all private cards, for setting common.
	 * @return void
	 */
	protected function setCommonCardFields()
	{
		if ($this->canEditSettings())
		{
			$name = 'kanban_select_more_v4_'.
				mb_strtolower($this->type) .
					'_' . intval($this->categoryId);
			\CUserOptions::deleteOptionsByName('crm', $name);
		}
	}

	/**
	 * Reset fields in card.
	 * @return void
	 */
	protected function resetCardFields()
	{
		$name = 'kanban_select_more_v4_'.
			mb_strtolower($this->type) .
				'_' . intval($this->categoryId);
		$fields = [
			'TITLE' => '',
			'OPPORTUNITY' => '',
			'DATE_CREATE' => '',
			'ORDER_STAGE' => '',
			'CLIENT' => ''
		];
		$this->additionalSelect = $fields;
		$this->arResult['MORE_FIELDS'] = $this->getAdditionalFields(true);

		if (
			$this->canEditSettings() &&
			$this->isEditorConfigCommon()
		)
		{
			\CUserOptions::setOption('crm', $name . '_common', $fields, true);
		}
		else
		{
			\CUserOptions::setOption('crm', $name, $fields);
		}
	}

	/**
	 * Save additional fields for card.
	 * @return array
	 */
	protected function actionSaveFields()
	{
		$name = (
					($this->request('type') == 'view')
					? 'kanban_select_more_v4_'.mb_strtolower($this->type)
					: 'kanban_edit_more_v4_'.mb_strtolower($this->type)
				) . '_' . intval($this->categoryId);
		$fields = $this->convertUtf($this->request('fields'), true);
		$fieldsKeys = $fields ? array_keys($fields) : [];

		$current = \CUserOptions::getOption('crm', $name);
		$current = $current ? array_keys($current) : [];
		$diffDel = array_diff($current, $fieldsKeys);
		$diffAdd = array_diff($fieldsKeys, $current);

		// reset common settings
		if (
			$this->canEditSettings() &&
			$this->isEditorConfigCommon()
		)
		{
			\CUserOptions::setOption('crm', $name . '_common', $fields, true);
		}
		else
		{
			\CUserOptions::setOption('crm', $name, $fields);
		}

		unset($name, $fields, $fieldsKeys, $current);

		return [
			'delete' => array_values($diffDel),
			'add' => array_values($diffAdd)
		];
	}

	/**
	 * Delete entity.
	 * @param int|array $ids Optionally id for delete.
	 * @return array
	 */
	protected function actionDelete($ids = null)
	{
		$ids = $ids ? $ids : $this->request('id');
		$ignore = $this->request('ignore');
		$type = $this->type;
		$types = $this->types;

		if ($type === $types['order'])
		{
			if (!$ids)
			{
				return [];
			}

			$ids = is_array($ids) ? $ids : [$ids];
			$userPerms = $this->getCurrentUserPermissions();
			foreach ($ids as $id)
			{
				$id = (int)$id;
				$checkPermission = Order\Permissions\Order::checkDeletePermission($id, $userPerms);
				if (!$checkPermission)
				{
					continue;
				}
				Order\Order::delete($id);
			}
			return [];
		}

		$provider = '\CCrm' . $type;

		if ($ids && method_exists($provider, 'delete'))
		{
			$entity = new $provider();
			if (!is_array($ids))
			{
				$ids = array($ids);
			}
			// set to ignore
			if (
				$ignore == 'Y' &&
				(
					!\Bitrix\Crm\Exclusion\Access::current()->canWrite() ||
					($type != $types['lead'] && $type != $types['deal'])
				)
			)
			{
				$ignore = 'N';
			}
			// delete
			foreach ($ids  as $id)
			{
				if ($ignore == 'Y')
				{
					\Bitrix\Crm\Exclusion\Manager::excludeEntity(
						($type == $types['lead'])
							? \CCrmOwnerType::Lead
							: \CCrmOwnerType::Deal,
						$id
					);
					if ($type == $types['lead'])
					{
						$entity->delete($id);
					}
				}
				else
				{
					$entity->delete($id);
				}
			}
		}
		return array();
	}

	/**
	 * Update statuses
	 *
	 * @param $id
	 * @param $status
	 * @param array $statuses
	 *
	 * @return Result
	 */
	private function actionUpdateEntityStatus($id, $status, array $statuses = [])
	{
		$result = new Result();
		$type = $this->type;
		$userPerms = $this->getCurrentUserPermissions();
		$request = Application::getInstance()->getContext()->getRequest();
		$provider = '\CCrm'.$this->type;

		if (!\CCrmPerms::IsAuthorized())
		{
			$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED')));
			return $result;
		}

		if ($this->type == $this->types['order'])
		{
			$order = Order\Order::load($id);
			if (!$order)
			{
				$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED')));
				return $result;
			}
			$row = $order->getFieldValues();
		}
		else
		{
			$entity = new $provider(false);
			$row = $entity->getById($id);
			if (!$row)
			{
				$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED')));
				return $result;
			}
		}

		if ($this->type == $this->types['order'])
		{
			$checkPermission = Order\Permissions\Order::checkUpdatePermission($id, $userPerms);
		}
		else
		{
			$checkPermission = $provider::CheckUpdatePermission($id, $userPerms);
		}

		if (!$checkPermission)
		{
			$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED')));
			return $result;
		}

		$statusKey = $this->statusKey;
		$ajaxParamsName = ($request->get('version') == 2) ? 'ajaxParams' : 'status_params';
		$newStateParams = (array)$request->get($ajaxParamsName);
		//add one more item for old column
		if ($row[$statusKey] != $status && isset($newStateParams['old_status_lastid']))
		{
			$oneMore = $this->getItems(array(
				$statusKey => $row[$statusKey],
				'<ID' => $newStateParams['old_status_lastid'],
				'!ID' => $id
			));
			if (count($oneMore) > 1)
			{
				$oneMore = array_shift($oneMore);
				$oneMore = array(
					$oneMore['id'] => $oneMore
				);
			}
			$this->items += $oneMore;
		}
		//change state

		if ($type == $this->types['invoice'])
		{
			$statusParams = array();
			$statusParams['REASON_MARKED'] = $this->convertUtf(
				isset($newStateParams['comment'])
					? $newStateParams['comment']
					: '',
				true
			);
			$statusParams[$status == 'P' ? 'PAY_VOUCHER_DATE' : 'DATE_MARKED'] = $this->convertUtf(
				isset($newStateParams['date'])
					? $newStateParams['date']
					: '',
				true
			);
			$statusParams['PAY_VOUCHER_NUM'] = $this->convertUtf(
				isset($newStateParams['docnum'])
					? $newStateParams['docnum']
					: '',
				true
			);
			$entity->SetStatus(
				$id,
				$status,
				$statusParams,
				array(
					'SYNCHRONIZE_LIVE_FEED' => true
				)
			);
		}
		elseif ($type == $this->types['order'])
		{
			$order->setField('STATUS_ID', $status);
			$order->save();
		}
		else
		{
			//if lead, check status
			if ($type == $this->types['lead'] && $row[$statusKey] != $status)
			{
				if ($statuses[$row[$statusKey]]['PROGRESS_TYPE'] == 'WIN')
				{
					$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_LEAD_ALREADY_CONVERTED')));
					return $result;
				}
				elseif ($statuses[$status]['PROGRESS_TYPE'] == 'WIN')
				{
					return $result;
				}
			}
			//if deal, check status
			if ($type == $this->types['deal'] && $row[$statusKey] != $status)
			{
				$stageCategoryID = DealCategory::resolveFromStageID($status);
				$dealCategoryID = (int)$row['CATEGORY_ID'];
				if($dealCategoryID !== $stageCategoryID)
				{
					$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_DEAL_STAGE_MISMATCH')));
					return $result;
				}
			}

			//update
			if ($row[$statusKey] != $status)
			{
				$fields = array($statusKey => $status);
				$entity->Update(
					$id,
					$fields,
					true,
					true,
					array(
						//'DISABLE_USER_FIELD_CHECK' => $isMulti,
						'REGISTER_SONET_EVENT' => true
					)
				);
				if (empty($entity->LAST_ERROR) && ($type == $this->types['lead'] || $type == $this->types['deal']))
				{
					$errors = array();
					\CCrmBizProcHelper::AutoStartWorkflows(
						($type == $this->types['lead']) ? \CCrmOwnerType::Lead : \CCrmOwnerType::Deal,
						$id, \CCrmBizProcEventType::Edit, $errors
					);
					$starter = new \Bitrix\Crm\Automation\Starter(
						($type == $this->types['lead']) ? \CCrmOwnerType::Lead : \CCrmOwnerType::Deal,
						$id
					);
					$starter->setUserIdFromCurrent()->runOnUpdate($fields, []);
				}
				elseif (!empty($entity->LAST_ERROR))
				{
					$result->addError(new Error($entity->LAST_ERROR));
				}
			}
		}

		\Bitrix\Crm\Kanban\SortTable::setPrevious(array(
			'ENTITY_TYPE_ID' => $type,
			'ENTITY_ID' => $id,
			'PREV_ENTITY_ID' => $request->get('prev_entity_id')
		));

		// remember last id
		$this->rememberLastId($this->getLastId());
		$this->getColumns(true);

		return $result;
	}

	private function prepareOrderEntitiesFields(array $orders = [])
	{
		$ids = array_keys($orders);
		static $currencies = [];
		static $personTypes = [];
		if (empty($currencies))
		{
			$currencies = CCrmCurrencyHelper::PrepareListItems();
		}
		if (empty($personTypes))
		{
			$personTypes = Order\PersonType::load(SITE_ID);
		}
		$orderClientRaw = \Bitrix\Crm\Binding\OrderContactCompanyTable::getList([
			'filter' => [
				'=ORDER_ID' => $ids
			]
		]);
		while ($orderClient = $orderClientRaw->fetch())
		{
			$orderId = $orderClient['ORDER_ID'];
			if ((int)$orderClient['ENTITY_TYPE_ID'] === \CCrmOwnerType::Contact)
			{
				if (empty($db[$orderId]['CONTACT_ID']) || $orderClient['IS_PRIMARY'] === 'Y')
				{
					$orders[$orderId]['CONTACT_ID'] = $orderClient['ENTITY_ID'];
				}
			}
			elseif ((int)$orderClient['ENTITY_TYPE_ID'] === \CCrmOwnerType::Company)
			{
				$orders[$orderId]['COMPANY_ID'] = $orderClient['ENTITY_ID'];
			}
		}

		$paymentRaw = Order\Payment::getList([
			'filter' => [
				'=ORDER_ID' => $ids
			]
		]);
		while ($payment = $paymentRaw->fetch())
		{
			$orderId = $payment['ORDER_ID'];
			$orders[$orderId]['PAYMENT'][$payment['ID']] = $payment;
		}

		$shipmentRaw = Order\Shipment::getList([
			'filter' => [
				'=ORDER_ID' => $ids,
				'SYSTEM' => 'N'
			]
		]);
		while ($shipment = $shipmentRaw->fetch())
		{
			$orderId = $shipment['ORDER_ID'];
			$orders[$orderId]['SHIPMENT'][$shipment['ID']] = $shipment;
		}

		$markersRaw = Order\EntityMarker::getList([
			'filter' => [
				'=ENTITY_TYPE' => Order\EntityMarker::ENTITY_TYPE_ORDER,
				'=ENTITY_ID' => $ids,
				'=SUCCESS' => 'N',
			],
			'select' => ['MESSAGE', 'ENTITY_ID']
		]);

		$markers = [];
		while ($marker = $markersRaw->fetch())
		{
			$markers[$marker['ENTITY_ID']][] = $marker['MESSAGE'];
		}

		if (!empty($markers))
		{
			foreach ($markers as $orderId => $marker )
			{
				if (count($marker) > 1)
				{
					$value = implode('<br>', $marker);
				}
				else
				{
					$value = $marker[0];
				}
				$orders[$orderId]['PROBLEM_NOTIFICATION'] = $value;
			}
		}

		$enumVariants = [];
		$enumPropertyVariantRaw = Order\PropertyVariant::getList([
			'select' => ['VALUE', 'NAME', 'ORDER_PROPS_ID']
		]);
		while ($variant = $enumPropertyVariantRaw->fetch())
		{
			$enumVariants[$variant['ORDER_PROPS_ID']][$variant['VALUE']] = $variant['NAME'];
		}

		$propertyValuesRaw = Order\PropertyValue::getList([
			'filter' => [
				'=ORDER_ID' => $ids
			],
			'select' => ['TYPE' => 'PROPERTY.TYPE', 'VALUE', 'ORDER_PROPS_ID', 'ORDER_ID']
		]);
		while ($propertyValue = $propertyValuesRaw->fetch())
		{
			$value = $propertyValue['VALUE'];
			$currentPropertyId = $propertyValue['ORDER_PROPS_ID'];
			if ($propertyValue['TYPE'] === 'ENUM')
			{
				$orders[$propertyValue['ORDER_ID']]['PROPERTY_'.$currentPropertyId] = $enumVariants[$currentPropertyId][$value];
			}
			if ($propertyValue['TYPE'] === 'Y/N')
			{
				$value = ($value === 'Y') ? 'Y' : 'N';
				$orders[$propertyValue['ORDER_ID']]['PROPERTY_'.$currentPropertyId] = Loc::getMessage('CRM_KANBAN_CHAR_' . $value);
			}
			else
			{
				$orders[$propertyValue['ORDER_ID']]['PROPERTY_'.$currentPropertyId] = $value;
			}
		}

		foreach ($orders as &$order)
		{
			if (isset($order['PERSON_TYPE_ID']))
			{
				$type = $personTypes[$order['PERSON_TYPE_ID']];
				$order['PERSON_TYPE_ID'] = $type['NAME'];
			}
			$order['CURRENCY_ID'] = $order['CURRENCY'];
			$order['USER'] = $order['USER_ID'];
			$order['CURRENCY'] = $currencies[$order['CURRENCY']];
			$order['TITLE'] = Loc::getMessage('CRM_KANBAN_ORDER_TITLE', [
				'#ACCOUNT_NUMBER#' => $order['ACCOUNT_NUMBER']
			]);
		}

		return $orders;
	}

	/**
	 * Refresh deals accounts.
	 * @return array
	 */
	protected function actionRefreshAccount()
	{
		$ids = $this->request('id');
		$idForUpdate = array();

		if ($ids && $this->type == $this->types['deal'])
		{
			$userPermissions = $this->getCurrentUserPermissions();
			if (!is_array($ids))
			{
				$ids = array($ids);
			}
			foreach ($ids  as $id)
			{
				$categoryId = $this->categoryId;
				if (\CCrmDeal::checkUpdatePermission($id, $userPermissions, $categoryId))
				{
					$idForUpdate[] = $id;
				}
			}
		}
		if (!empty($idForUpdate))
		{
			\CCrmDeal::refreshAccountingData($idForUpdate);
		}

		return array();
	}

	/**
	 * Set assigned id.
	 * @return array
	 */
	protected function actionSetAssigned()
	{
		$ids = $this->request('ids');
		$assignedId = $this->request('assignedId');
		$type = $this->type;
		$types = $this->types;
		$userPerms = $this->getCurrentUserPermissions();

		if ($type === $types['order'])
		{
			$assignedId = (int)$assignedId;
			if (!empty($ids) && $assignedId > 0)
			{
				if (!is_array($ids))
				{
					$ids = [$ids];
				}
				foreach ($ids as $id)
				{
					if (Order\Permissions\Order::checkUpdatePermission($id, $userPerms))
					{
						$order = Order\Order::load($id);
						$order->setField('RESPONSIBLE_ID', $assignedId);
						$order->save();
					}
				}
			}
			return [];
		}

		$provider = '\CCrm'.$type;

		if (
			$ids && $assignedId &&
			method_exists($provider, 'update')
		)
		{
			if (!is_array($ids))
			{
				$ids = array($ids);
			}
			$key = $type == $types['invoice'] ? 'RESPONSIBLE_ID' : 'ASSIGNED_BY_ID';
			$entity = new $provider();
			foreach ($ids as $id)
			{
				$fields = array(
					$key => $assignedId
				);
				$entity->update($id, $fields);
				$this->runAutomationOnUpdate($id, $fields);
			}
		}

		return array();
	}

	/**
	 * Make open / close.
	 * @return array
	 */
	protected function actionOpen()
	{
		$ids = $this->request('id');
		$flag = $this->request('flag');
		$type = $this->type;
		$types = $this->types;
		$provider = '\CCrm' . $type;

		if (
			$ids && $type != $types['invoice'] &&
			method_exists($provider, 'update')
		)
		{
			if (!is_array($ids))
			{
				$ids = array($ids);
			}
			$entity = new $provider();
			foreach ($ids as $id)
			{
				$fields = array(
					'OPENED' => $flag
				);
				$entity->update($id, $fields);
				$this->runAutomationOnUpdate($id, $fields);
			}
		}

		return array();
	}

	/**
	 * Change category of deals.
	 * @return array
	 */
	protected function actionChangeCategory()
	{
		$ids = $this->request('id');
		$category = (int)$this->request('category');
		$type = $this->type;
		$types = $this->types;
		$provider = '\CCrm' . $type;

		$userPermissions = $this->getCurrentUserPermissions();
		$catPerm = array_fill_keys(
			\CCrmDeal::GetPermittedToReadCategoryIDs($userPermissions),
			true
		);

		if (
			$ids &&
			isset($catPerm[$category]) &&
			$type == $types['deal'] &&
			method_exists($provider, 'update')
		)
		{

			if (!is_array($ids))
			{
				$ids = array($ids);
			}
			foreach ($ids as $id)
			{
				$id = (int)$id;
				if (
					$id > 0 &&
					\CCrmDeal::checkUpdatePermission($id, $userPermissions) &&
					\CCrmDeal::CheckCreatePermission($userPermissions, $category)
				)
				{
					// collect options
					$recurringData = \Bitrix\Crm\Recurring\Manager::getList(
						[
							'filter' => ['DEAL_ID' => $id],
							'limit' => 1
						],
						\Bitrix\Crm\Recurring\Manager::DEAL
					);
					$options = null;
					if ($recurringData->fetch())
					{
						$options = ['REGISTER_STATISTICS' => false];
					}
					// move
					$error = \CCrmDeal::moveToCategory($id, $category, $options);
					// automation
					if ($error === \Bitrix\Crm\Category\DealCategoryChangeError::NONE)
					{
						$errors = null;
						\CCrmBizProcHelper::autoStartWorkflows(
							\CCrmOwnerType::Deal,
							$id,
							\CCrmBizProcEventType::Edit,
							$errors
						);
						$dbResult = \CCrmDeal::GetListEx(
							array(),
							array('=ID' => $id, 'CHECK_PERMISSIONS' => 'N'),
							false,
							false,
							['STAGE_ID', 'CATEGORY_ID']
						);
						$newFields = $dbResult->Fetch();

						$starter = new Bitrix\Crm\Automation\Starter(CCrmOwnerType::Deal, $id);
						$starter->setUserIdFromCurrent()->runOnUpdate($newFields, []);
					}
				}
			}
			return [];
		}

		return [];
	}

	/**
	 * Show or hide contact center block in option.
	 * @return array
	 */
	protected function actionToggleCC()
	{
		$hidden = \CUserOptions::getOption(
			'crm',
			'kanban_cc_hide',
			false
		);
		\CUserOptions::setOption(
			'crm',
			'kanban_cc_hide',
			!$hidden
		);
		return [];
	}

	/**
	 * Show or hide REST demo block in option.
	 * @return array
	 */
	protected function actionToggleRest()
	{
		$hidden = \CUserOptions::getOption(
			'crm',
			'kanban_rest_hide',
			false
		);
		\CUserOptions::setOption(
			'crm',
			'kanban_rest_hide',
			!$hidden
		);
		return [];
	}
}