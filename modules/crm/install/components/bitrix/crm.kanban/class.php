<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use \Bitrix\Main\Config\Option;
use \Bitrix\Crm\Category\DealCategory;
use \Bitrix\Crm\PhaseSemantics;
use \Bitrix\Crm\Attribute\FieldAttributeManager;
use \Bitrix\Crm\Color\PhaseColorSchemeManager;

class CrmKanbanComponent extends \CBitrixComponent
{
	protected $type = '';
	protected $fieldSum = '';
	protected $currency = '';
	protected $statusKey = 'STATUS_ID';
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
	protected $modifyUsers = array();
	protected $additionalSelect = array();
	protected $additionalTypes = array();
	protected $items = array();
	protected $requiredFields = array();
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
	protected $pathMarkers = array('#lead_id#', '#contact_id#', '#company_id#', '#deal_id#', '#quote_id#', '#invoice_id#');
	protected $selectPresets = array(
								'lead' => array('ID', 'STATUS_ID', 'TITLE', 'DATE_CREATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID', 'IS_RETURN_CUSTOMER', 'ASSIGNED_BY'),
								'deal' => array('ID', 'STAGE_ID', 'TITLE', 'DATE_CREATE', 'BEGINDATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'IS_REPEATED_APPROACH', 'IS_RETURN_CUSTOMER', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID', 'ASSIGNED_BY'),
								'quote' => array('ID', 'STATUS_ID', 'TITLE', 'DATE_CREATE', 'BEGINDATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID', 'ASSIGNED_BY'),
								'invoice' => array('ID', 'STATUS_ID', 'DATE_INSERT', 'DATE_INSERT_FORMAT', 'PAY_VOUCHER_DATE', 'DATE_BILL', 'ORDER_TOPIC', 'PRICE', 'CURRENCY', 'UF_CONTACT_ID', 'UF_COMPANY_ID', 'RESPONSIBLE_ID'),
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
			'invoice' => \CCrmOwnerType::InvoiceName
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
		$this->type = strtoupper(isset($this->arParams['ENTITY_TYPE']) ? $this->arParams['ENTITY_TYPE'] : '');
		if (!$this->type || !in_array($this->type, $this->types))
		{
			return false;
		}
		$this->arParams['ENTITY_TYPE_CHR'] = array_flip($this->types);
		$this->arParams['ENTITY_TYPE_CHR'] = strtoupper($this->arParams['ENTITY_TYPE_CHR'][$this->type]);
		$this->arParams['ENTITY_TYPE_INT'] = \CCrmOwnerType::resolveID($this->type);
		$this->arParams['ENTITY_PATH'] = $this->getEntityPath($this->type);
		$this->arParams['HIDE_CC'] = \CUserOptions::getOption(
			'crm',
			'kanban_cc_hide',
			false
		);;
		//select
		$this->additionalSelect = (array)\CUserOptions::getOption(
			'crm',
			'kanban_select_more_' . $this->type,
			array()
		);
		//redefine price-field
		if ($this->type != $this->types['quote'])
		{
			$slots = \Bitrix\Crm\Statistics\StatisticEntryManager::prepareSlotBingingData($this->type .  '_SUM_STATS');
			if (is_array($slots) && isset($slots['SLOT_BINDINGS']) && is_array($slots['SLOT_BINDINGS']))
			{
				foreach ($slots['SLOT_BINDINGS'] as $slot)
				{
					if ($slot['SLOT'] == 'SUM_TOTAL')
					{
						$this->fieldSum = $slot['FIELD'];
						break;
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
			if (isset($this->arParams['EXTRA']['CATEGORY_ID']))
			{
				$uriImport->addParams(array(
					'category_id' => $this->arParams['EXTRA']['CATEGORY_ID']
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
		$filter = $this->getFilter();
		$semantic = \CCrmStatus::GetEntityTypes();

		//colors
		$colors = array(
			'QUOTE_STATUS' => ($type == $types['quote']) ? $this->getStatusColor('CONFIG_STATUS_QUOTE_STATUS') : array(),
			'INVOICE_STATUS' => ($type == $types['invoice']) ? $this->getStatusColor('CONFIG_STATUS_INVOICE_STATUS') : array(),
			'STATUS' => ($type == $types['lead']) ? $this->getStatusColor('CONFIG_STATUS_STATUS') : array(),
		);
		if (
			$type == $types['deal'] &&
			isset($this->arParams['EXTRA']['CATEGORY_ID']) &&
			$this->arParams['EXTRA']['CATEGORY_ID'] > 0
		)
		{
			$categories = DealCategory::getList(array(
				'filter' => array(
					'ID' => $this->arParams['EXTRA']['CATEGORY_ID']
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
		foreach ($db as $row)
		{
			$row['NAME'] = htmlspecialcharsbx($row['NAME']);
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
		static $select = null;

		if ($select === null)
		{
			$types = array_flip($this->types);
			$select = $this->selectPresets[$types[$this->type]];

			if (!empty($this->additionalSelect))
			{
				$select = array_merge($select, array_keys($this->additionalSelect));
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
			if (isset($this->arParams['EXTRA']['CATEGORY_ID']))
			{
				\Bitrix\Crm\Kanban\Helper::setCategoryId(
					$this->arParams['EXTRA']['CATEGORY_ID']
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
					if (in_array(substr($key, 0, 2), array('>=', '<=', '<>')))
					{
						$key = substr($key, 2);
					}
					if (in_array(substr($key, 0, 1), array('=', '<', '>', '@', '!')))
					{
						$key = substr($key, 1);
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
							$filter['FLT_DATE_EXIST'] = 'Y';
						}
						if (isset($search[$key . '_to']) && $search[$key . '_to']!='')
						{
							$filter['<='.$key] = $search[$key . '_to'] . ' 23:59:00';
							$filter['FLT_DATE_EXIST'] = 'Y';
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
			if ($this->type == $this->types['deal'] && isset($this->arParams['EXTRA']['CATEGORY_ID']))
			{
				$filter['CATEGORY_ID'] = $this->arParams['EXTRA']['CATEGORY_ID'];
			}
			//invoice
			if ($this->type == $this->types['invoice'])
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
	 * Get path for entity from params or module settings.
	 * @param string $type
	 * @return string
	 */
	protected function getEntityPath($type)
	{
		$params = $this->arParams;
		$pathKey = 'PATH_TO_'.strtoupper($type).'_DETAILS';
		$url = !array_key_exists($pathKey, $params) ? \CrmCheckPath($pathKey, '', '') : $params[$pathKey];
		if(!($url !== '' && CCrmOwnerType::IsSliderEnabled(CCrmOwnerType::ResolveID($type))))
		{
			$pathKey = 'PATH_TO_'.strtoupper($type).'_SHOW';
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
				$row['TYPE_ID'] = strtolower($row['TYPE_ID']);
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

		$contragent = strtolower(trim($contragent));
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
			$baseCurrency = $this->currency;
			$baseFilter = $this->getFilter();
			$filter = $baseFilter;
			$types = $this->types;
			$statusCode = $this->statusKey;
			$sort = 0;
			$winColumn = [];
			$commonRequireds = [];
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
				$res = \CUserTypeEntity::getList(
					array(),
					array(
						'ENTITY_ID' => ($this->type == $types['lead'])
										? 'CRM_LEAD'
										: 'CRM_DEAL',
						'MANDATORY' => 'Y'
					)
				);
				while ($row = $res->fetch())
				{
					$commonRequireds[] = $row['FIELD_NAME'];
				}
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
					'color' => strpos($status['COLOR'], '#')===0 ? substr($status['COLOR'], 1) : $status['COLOR'],
					'type' => $status['PROGRESS_TYPE'],
					'sort' => $sort,
					'count' => 0,
					'total' => 0,
					'currency' => $baseCurrency,
					'dropzone' => $dropzone
				);
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

			if ($this->type == $types['invoice'])
			{
				$baseFilter['!IS_RECURRING'] = 'Y';
				$stats = array();
				$res = \CCrmInvoice::GetList(array(), $baseFilter,
											array('STATUS_ID', 'SUM' => 'PRICE'), false,
											array('STATUS_ID', 'PRICE')
						);
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
					//$filter[$statusCode] = array_keys($columns);
					if (method_exists($provider, 'getListEx'))
					{
						$res = $provider::GetListEx(array(), $filter,
													array($statusCode, 'SUM' => 'OPPORTUNITY_ACCOUNT'), false,
													array($statusCode, 'OPPORTUNITY_ACCOUNT'));
					}
					else
					{
						$res = $provider::GetList(array(), $filter,
													array($statusCode, 'SUM' => 'OPPORTUNITY_ACCOUNT'), false,
													array($statusCode, 'OPPORTUNITY_ACCOUNT'));
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
						else
						{
							$columns[$stat[$statusCode]]['count'] = $stat['CNT'];
							$columns[$stat[$statusCode]]['total'] = $stat['OPPORTUNITY_ACCOUNT'];
							$columns[$stat[$statusCode]]['total_format'] = \CCrmCurrency::MoneyToString(round($stat['OPPORTUNITY_ACCOUNT']), $baseCurrency);
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
		$types = $this->types;
		$provider = '\CCrm'.$type;
		$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';
		$select = $this->getSelect();
		$filterCommon = $this->getFilter();
		$addFields = $this->getAdditionalFields();
		$addSelect = array_keys($this->additionalSelect);
		$addTypes = $this->additionalTypes;
		$navParams = $pagen ? array('iNumPage' => $this->blockPage, 'nPageSize' => $this->blockSize) : false;
		$statusKey = $this->statusKey;

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
			//user sorting
			if (
				isset($filter[$statusKey]) && isset($columns[$filter[$statusKey]]) &&
				$columns[$filter[$statusKey]]['count'] <= $this->maxSortSize
			)
			{
				//get all and sort
				$sorting = true;
				$db = array();
				$res = $provider::$method(array('ID' => 'DESC'), $filter, false, false, $select);
				while ($row = $res->fetch())
				{
					$db[$row['ID']] = $row;
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
				$res = $provider::$method(array('ID' => 'DESC'), $filter, false, $navParams, $select);
			}
			$timeOffset = \CTimeZone::GetOffset();
			$timeFull = time() + $timeOffset;
			$pageCount = $res->NavPageCount;
			$specialReqKeys = [
				'OPPORTUNITY' => 'OPPORTUNITY_WITH_CURRENCY',
				'CONTACT_ID' => 'CLIENT'
			];
			while ($row = $res->fetch())
			{
				$row['FORMAT_TIME'] = true;
				//base
				if (!isset($row['ASSIGNED_BY']))
				{
					$row['ASSIGNED_BY'] = $row['RESPONSIBLE_ID'];
				}
				if (isset($row['MODIFY_BY_ID']))
				{
					$this->modifyUsers[$row['ID']] = $row['MODIFY_BY_ID'];
				}
				if ($type == $types['lead'])
				{
					$row['PRICE'] = $row['OPPORTUNITY'];
					$row['DATE'] = $row['DATE_CREATE'];
				}
				elseif ($type == $types['deal'])
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
				elseif ($row['COMPANY_ID'] > 0)
				{
					$row['CONTACT_TYPE'] = 'CRM_COMPANY';
					$row['CONTACT_ID'] = $row['COMPANY_ID'];
					$this->company[$row['ID']] = $row['COMPANY_ID'];
				}
				else
				{
					$row['CONTACT_TYPE'] = '';
				}
				//additional fields
				$fields = array();
				foreach ($addSelect as $code)
				{
					if (array_key_exists($code, $row) && array_key_exists($code, $addFields))
					{
						if (isset($addTypes[$code]) && isset($addTypes[$code][$row[$code]]))
						{
							$row[$code] = $addTypes[$code][$row[$code]];
						}
						if (true || !empty($row[$code]))
						{
							if ($addFields[$code]['type'] == 'enumeration')
							{
								$row[$code] = implode(', ', array_intersect_key(
									$addFields[$code]['enumerations'],
									array_flip($row[$code])
								));
							}
							$fields[] = array(
								'code' => $code,
								'title' => $addFields[$code]['title'],
								'value' => htmlspecialcharsbx($row[$code])
							);
						}
					}
				}
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
				$row['DATE_UNIX'] = \makeTimeStamp($row['DATE']);
				$dateFormat = $this->dateFormats[date('Y') == date('Y', $row['DATE_UNIX']) ? 'short' : 'full'][$this->formatLang];
				// collect required
				$required = [];
				$requiredFm = [];
				if ($this->requiredFields)
				{
					// fm fields check later
					foreach ($this->allowedFMtypes as $fm)
					{
						$fmUp = strtoupper($fm);
						$requiredFm[$fmUp] = true;
						$row[$fmUp] = '';
					}
					// check each key
					foreach ($row as $key => $val)
					{
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
							$row[$reqKeyOrig] <= 0
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
					'return' => isset($row['IS_RETURN_CUSTOMER']) && $row['IS_RETURN_CUSTOMER'] == 'Y',
					'returnApproach' => isset($row['IS_REPEATED_APPROACH']) && $row['IS_REPEATED_APPROACH'] == 'Y',
					'assignedBy' => $row['ASSIGNED_BY'],
					'required' => $required,
					'required_fm' => $requiredFm
				);
			}
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
	 * @return array
	 */
	protected function getAdditionalFields()
	{
		static $additional = null;

		if ($additional === null)
		{
			$type = $this->type;
			$additional = array();
			$ufExist = false;
			$exist = $this->additionalSelect;

			//base fields
			foreach ($this->additionalSelect as $key => $title)
			{
				if (strpos($key, 'UF_') === 0)
				{
					$ufExist = true;
				}
				else
				{
					$additional[$key] = array(
						'title' => $title,
						'type' => 'string',
						'code' => $key
					);
				}
			}

			//user fields
			if ($ufExist)
			{
				$enumerations = array();
				$res = \CUserTypeEntity::getList(
					array(
						'SORT' => 'ASC',
						'NAME' => 'ASC'
					),
					array(
						'ENTITY_ID' => 'CRM_' . $type,
						'LANG' => LANGUAGE_ID
					)
				);
				while ($row = $res->fetch())
				{
					if (isset($this->additionalSelect[$row['FIELD_NAME']]))
					{
						$additional[$row['FIELD_NAME']] = array(
							'title' => htmlspecialcharsbx($row['EDIT_FORM_LABEL']),
							'new' => !in_array($row['FIELD_NAME'], $exist) ? 1 : 0,
							'type' => $row['USER_TYPE_ID'],
							'code' => $row['FIELD_NAME'],
							'enumerations' => array()
						);
						if ($row['USER_TYPE_ID'] == 'enumeration')
						{
							$enumerations[$row['ID']] = $row['FIELD_NAME'];
						}
					}
				}
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
				}
			}
		}

		return $additional;
	}

	/**
	 * Make some actions (set, update, etc).
	 * @return void
	 */
	protected function makeAction()
	{
		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$request = $context->getRequest();
		$type = $this->type;
		$types = $this->types;
		$provider = '\CCrm'.$this->type;

		//update fields
		if (
			($action = $request->get('action')) &&
			($id = $request->get('entity_id')) &&
			check_bitrix_sessid()
		)
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
					$entity = new $provider(false);
					$userPerms = \CCrmPerms::GetCurrentUserPermissions();
					if (!\CCrmPerms::IsAuthorized())
					{
						$this->setError(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED'));
					}
					elseif (!($row = $entity->getById($id)))
					{
						$this->setError(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED'));
					}
					elseif (!$provider::CheckUpdatePermission($id, $userPerms))
					{
						$this->setError(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED'));
					}
					else
					{
						$statusKey = $this->statusKey;
						if ($request->get('version') == 2)
						{
							$newStateParams = (array)$request->get('ajaxParams');
						}
						else
						{
							$newStateParams = (array)$request->get('status_params');
						}
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
						$skipUpdate = false;
						if ($type == $types['invoice'])
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
						else
						{
							//if lead, check status
							if ($type == $types['lead'] && $row[$statusKey] != $status)
							{
								if ($statuses[$row[$statusKey]]['PROGRESS_TYPE'] == 'WIN')
								{
									$skipUpdate = true;
									$this->setError(Loc::getMessage('CRM_KANBAN_ERROR_LEAD_ALREADY_CONVERTED'));
								}
								elseif ($statuses[$status]['PROGRESS_TYPE'] == 'WIN')
								{
									$skipUpdate = true;
								}
							}
							//update
							if ($row[$statusKey] != $status && !$skipUpdate)
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
								if (empty($entity->LAST_ERROR) && ($type == $types['lead'] || $type == $types['deal']))
								{
									$errors = array();
									\CCrmBizProcHelper::AutoStartWorkflows(
										($type == $types['lead']) ? \CCrmOwnerType::Lead : \CCrmOwnerType::Deal,
										$id, \CCrmBizProcEventType::Edit, $errors
									);
									\Bitrix\Crm\Automation\Factory::runOnStatusChanged(
										($type == $types['lead']) ? \CCrmOwnerType::Lead : \CCrmOwnerType::Deal,
										$id
									);
								}
								elseif (!empty($entity->LAST_ERROR))
								{
									$this->setError($entity->LAST_ERROR);
								}
							}
						}

						if (!$skipUpdate)
						{
							//change sort
							if (1 || count($ids) <= 1)
							{
								\Bitrix\Crm\Kanban\SortTable::setPrevious(array(
									'ENTITY_TYPE_ID' => $type,
									'ENTITY_ID' => $id,
									'PREV_ENTITY_ID' => $request->get('prev_entity_id')
								));
							}
							// remember last id
							$this->rememberLastId($this->getLastId());
							$this->getColumns(true);
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
	 * Base executable method.
	 * @return array
	 */
	public function executeComponent()
	{
		if (!$this->init())
		{
			return;
		}

		$this->arResult['ITEMS'] = array();
		$this->arResult['ADMINS'] = $this->getAdmins();
		$this->arResult['MORE_FIELDS'] = $this->getAdditionalFields();
		$this->arResult['CATEGORIES'] = array();

		$isOLinstalled = IsModuleInstalled('imopenlines');
		$context = \Bitrix\Main\Application::getInstance()->getContext();
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
			$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
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
				//get avatars
				if ($this->arParams['GET_AVATARS'] == 'Y' && !empty($this->modifyUsers))
				{
					$users = array();
					$res = \Bitrix\Main\UserTable::getList(array(
						'select' => array('ID', 'PERSONAL_PHOTO'),
						'filter' => array('ID' => array_values($this->modifyUsers))
					));
					while ($row = $res->fetch())
					{
						if ($row['PERSONAL_PHOTO'])
						{
							$row['PERSONAL_PHOTO'] = \CFile::ResizeImageGet($row['PERSONAL_PHOTO'], $this->avatarSize, BX_RESIZE_IMAGE_EXACT);
						}
						$users[$row['ID']] = $row;
					}
					foreach ($items as &$item)
					{
						if ($users[$item['modifyById']]['PERSONAL_PHOTO'])
						{
							$item['modifyByAvatar'] = $users[$item['modifyById']]['PERSONAL_PHOTO']['src'];
						}
					}
					unset($item);
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
						if ($item['contactId'] > 0 && $item['contactType'] == 'CRM_CONTACT' && isset($contacts[$item['contactId']]))
						{
							$contragentTypeId = CCrmOwnerType::Contact;
							$contragent = $contacts[$item['contactId']];
						}
						elseif ($item['contactId'] > 0 && $item['contactType'] == 'CRM_COMPANY' && isset($companies[$item['contactId']]))
						{
							$contragentTypeId = CCrmOwnerType::Company;
							$contragent = $companies[$item['contactId']];
						}
						else
						{
							$contragent = array();
						}
						if (!empty($contragent))
						{
							$item['contactName'] = $contragent['TITLE'];
							$item['contactLink'] = $contragent['URL'];
							$item['contactTooltip'] = \CCrmViewHelper::PrepareEntityBaloonHtml([
								'ENTITY_TYPE_ID' => $contragentTypeId,
								'ENTITY_ID' => $item['contactId'],
								'TITLE' => $contragent['~TITLE'],
								'PREFIX' => $this->type . '_' . $item['id'],
							]);
						}
						//phone, email, chat
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
											if ((strpos($val, 'imol|') === 0))
											{
												$item[$code] = $val;
												break;
											}
										}
									}
								}
								else
								{
									$item[$code] = $values;
								}
								$item['required_fm'][strtoupper($code)] = false;
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
											if (strpos($val, 'imol|') === 0)
											{
												$item[$code] = $val;
												break;
											}
										}
									}
								}
								else
								{
									$item[$code] = $values;
								}
								$item['required_fm'][strtoupper($code)] = false;
							}
							unset($item['FM_VALUES'], $item['FM']);
						}
					}
					unset($item);
				}

				//get activity
				if (!empty($this->arResult['ITEMS']['items']))
				{
					if ($type == $types['deal'] || $type == $types['lead'])
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

		// items for demo import
		if (
			isset($this->arResult['ITEMS']['columns'][0]['id']) &&
			isset($this->arResult['ITEMS']['columns'][0]['count']) &&
			(
				$this->blockPage * $this->blockSize
				>=
				$this->arResult['ITEMS']['columns'][0]['count']
			)
			&&
			(
				$type == $types['lead'] ||
				$type == $types['deal']
			)
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
			$type = $this->type;
			$types = $this->types;
			$filter = $this->getFilter();
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

			$codeColor = '';
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
			// switch entity type
			if ($type == $types['quote'])
			{
				$entityId = 'QUOTE_STATUS';
				$codeColor = 'CONFIG_STATUS_QUOTE_STATUS';
			}
			elseif ($type == $types['invoice'])
			{
				$entityId = 'INVOICE_STATUS';
				$codeColor = 'CONFIG_STATUS_INVOICE_STATUS';
			}
			elseif ($type == $types['lead'])
			{
				$entityId = 'STATUS';
				$codeColor = 'CONFIG_STATUS_STATUS';
			}
			if ($type == $types['deal'] && isset($filter['CATEGORY_ID']) && $filter['CATEGORY_ID']>0)
			{
				$entityId = 'DEAL_STAGE_' . $filter['CATEGORY_ID'];
				$codeColor = 'CONFIG_STATUS_DEAL_STAGE_' . $filter['CATEGORY_ID'];
			}
			elseif ($type == $types['deal'])
			{
				$entityId = 'DEAL_STAGE';
				$codeColor = 'CONFIG_STATUS_DEAL_STAGE';
			}
			// delete / update / add
			$status = new \CCrmStatus($entityId);
			$fields = array(
				'ENTITY_ID' => $entityId,
				'SORT' => ++$sort
			);
			if ($columnName)
			{
				$fields['NAME'] = $columnName;
				$fields['NAME_INIT'] = $columnName;
			}
			if ($columnId != '' && isset($stages[$columnId]))
			{
				$chId = $stages[$columnId]['real_id'];
				if ($delete)
				{
					$provider = '\CCrm'.$type;
					$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';
					if (is_callable(array($provider, $method)))
					{
						$checkEmpty = $provider::$method(
							array('ID' => 'DESC'),
							array(
								$this->statusKey => $columnId,
								'CHECK_PERMISSIONS' => 'N'
							),
							false,
							false,
							array('ID')
						)->fetch();
					}
					else
					{
						$checkEmpty = array();
					}
					if ($stages[$columnId]['type'] == 'WIN')
					{
						return array(
							'ERROR' => Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_WIN')
						);
					}
					elseif (!empty($checkEmpty))
					{
						return array(
							'ERROR' => Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_NOT_EMPTY')
						);
					}
					else
					{
						$newStatus = $status->GetStatusById($chId);
						if ($newStatus['SYSTEM'] == 'Y')
						{
							return array(
								'ERROR' => Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_SYSTEM')
							);
						}
						else
						{
							$colors = $this->getStatusColor($codeColor);
							if (isset($colors[$newStatus['STATUS_ID']]))
							{
								unset($colors[$newStatus['STATUS_ID']]);
							}
							Option::set('crm', $codeColor, serialize($colors));
							$status->delete($chId);
							return array();
						}
					}
				}
				else
				{
					$status->update($chId, $fields);
				}
			}
			else
			{
				$chId = $status->add($fields);
			}
			if (!$status->GetLastError())
			{
				$newStatus = $status->GetStatusById($chId);
				// update color
				if ($columnColor)
				{
					$colors = $this->getStatusColor($codeColor);
					if (!isset($colors[$newStatus['STATUS_ID']]))
					{
						$colors[$newStatus['STATUS_ID']] = array();
					}
					$colors[$newStatus['STATUS_ID']]['COLOR'] = '#' . $columnColor;
					Option::set('crm', $codeColor, serialize($colors));
				}
				// output
				$stages = $this->getColumns(
					true,
					true,
					array(
						'originalColumns' => true
					)
				);
				if (isset($stages[$newStatus['STATUS_ID']]))
				{
					// range sorts
					$sort = 10;
					foreach ($stages as $stage)
					{
						$status->update($stage['real_id'], array(
							'SORT' => $sort
						));
						$sort += 10;
					}
					return \htmlspecialcharsback($stages[$newStatus['STATUS_ID']]);
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
				return array(
					'ERROR' => $status->GetLastError()
				);
			}
		}
		return array();
	}


	/**
	 * Save additional fields for card.
	 * @return array
	 */
	protected function actionSaveFields()
	{
		\CUserOptions::setOption(
			'crm',
			'kanban_select_more_' . $this->type,
			$this->convertUtf($this->request('fields'), true)
		);
		return array();
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
	 * Refresh deals accounts.
	 * @return array
	 */
	protected function actionRefreshAccount()
	{
		$ids = $this->request('id');
		$idForUpdate = array();

		if ($ids && $this->type == $this->types['deal'])
		{
			$userPermissions = \CCrmPerms::getCurrentUserPermissions();
			if (!is_array($ids))
			{
				$ids = array($ids);
			}
			foreach ($ids  as $id)
			{
				$categoryId = isset($this->arParams['EXTRA']['CATEGORY_ID'])
					? $this->arParams['EXTRA']['CATEGORY_ID']
					: null;
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
		$category = $this->request('category');
		$type = $this->type;
		$types = $this->types;
		$provider = '\CCrm' . $type;

		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
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
				CCrmDeal::MoveToCategory($id, $category);
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
}