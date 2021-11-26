<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use \Bitrix\Crm\Integration\PullManager;
use \Bitrix\Crm\Service\Container;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use \Bitrix\Main\Result;
use \Bitrix\Main\Entity\AddResult;
use \Bitrix\Main\Error;
use Bitrix\Main\Web\Uri;
use \Bitrix\Sale;
use \Bitrix\Rest\Marketplace;
use \Bitrix\Crm\Kanban;
use \Bitrix\Crm\Deal;
use \Bitrix\Crm\Color\PhaseColorScheme;
use \Bitrix\Crm\Format\PersonNameFormatter;
use \Bitrix\Crm\Tracking;
use \Bitrix\Crm\Order;
use \Bitrix\Crm\PhaseSemantics;
use \Bitrix\Crm\Search\SearchEnvironment;
use \Bitrix\Crm\Tracking\Internals\TraceChannelTable;
use \Bitrix\Crm\Tracking\Channel;
use Bitrix\Main\Text\HtmlFilter;

class CrmKanbanComponent extends \CBitrixComponent
{
	protected const OPTION_CATEGORY = 'crm';
	protected const MAX_SORTED_ITEMS_COUNT = 1000;
	protected const OPTION_NAME_HIDE_CONTACT_CENTER = 'kanban_cc_hide';
	protected const OPTION_NAME_HIDE_REST_DEMO = 'kanban_rest_hide';
	protected const REST_CONFIGURATION_PLACEMENT_URL_CONTEST = 'crm_kanban';
	protected const COLUMN_NAME_DELETED = 'DELETED';

	/** @var Kanban\Entity */
	protected $entity;
	protected $fieldSum;
	protected $currency;
	protected $statusKey;
	protected $categoryId;
	protected $currentUserID = 0;

	protected $blockPage = 1;
	protected $blockSize = 20;
	protected $application;
	protected $allowSemantics = [];
	protected $allowStages = [];
	protected $types = [];
	protected $parents = [];
	protected $contact = [];
	protected $company = [];
	protected $fmTypes = [];
	protected $additionalSelect = [];
	protected $additionalEdit = [];
	protected $items = [];
	protected $requiredFields = [];
	protected $schemeFields = [];
	protected $userFields = [];
	protected $avatarSize = ['width' => 38, 'height' => 38];
	protected $allowedFMtypes = ['phone', 'email', 'im', 'web'];
	protected $pathMarkers = ['#lead_id#', '#contact_id#', '#company_id#', '#deal_id#', '#quote_id#', '#invoice_id#', '#order_id#'];
	protected $disableMoreFields = [
		'ACTIVE_TIME_PERIOD', 'PRODUCT_ROW_PRODUCT_ID', 'COMPANY_ID',
		'CONTACT_ID', 'EVENT_ID', 'EVENT_DATE', 'ACTIVITY_COUNTER',
		'IS_RETURN_CUSTOMER', 'IS_NEW', 'IS_REPEATED_APPROACH', 'CURRENCY_ID', 'WEBFORM_ID',
		'COMMUNICATION_TYPE', 'HAS_PHONE', 'HAS_EMAIL', 'STAGE_SEMANTIC_ID', 'CATEGORY_ID',
		'STATUS_ID', 'STATUS_SEMANTIC_ID', 'STATUS_CONVERTED', 'MODIFY_BY_ID', 'TRACKING_CHANNEL_CODE',
		'ADDRESS', 'ADDRESS_2', 'ADDRESS_CITY', 'ADDRESS_REGION', 'ADDRESS_PROVINCE',
		'ADDRESS', 'ADDRESS_POSTAL_CODE', 'ADDRESS_COUNTRY', 'CREATED_BY_ID', 'ORIGINATOR_ID', 'ORIGINATOR_ID',
		'UTM_SOURCE', 'UTM_MEDIUM', 'UTM_CAMPAIGN', 'UTM_CONTENT', 'UTM_TERM',
		'STAGE_ID_FROM_HISTORY', 'STAGE_ID_FROM_SUPPOSED_HISTORY', 'STAGE_SEMANTIC_ID_FROM_HISTORY'
	];

	protected $exclusiveFieldsReturnCustomer = [
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
	];
	protected $semanticIds = [];

	public function onPrepareComponentParams($arParams): array
	{
		$arParams['HIDE_CC'] = \CUserOptions::getOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME_HIDE_CONTACT_CENTER,
			false
		);

		if (!isset($arParams['ADDITIONAL_FILTER']) || !is_array($arParams['ADDITIONAL_FILTER']))
		{
			$arParams['ADDITIONAL_FILTER'] = [];
		}
		if (!isset($arParams['EXTRA']) || !is_array($arParams['EXTRA']))
		{
			$arParams['EXTRA'] = [];
		}
		$arParams['PAGE'] = (int)$arParams['PAGE'];
		$arParams['ONLY_COLUMNS'] = $arParams['ONLY_COLUMNS'] !== 'Y' ? 'N' : 'Y';
		$arParams['ONLY_ITEMS'] = $arParams['ONLY_ITEMS'] !== 'Y' ? 'N' : 'Y';
		$arParams['EMPTY_RESULT'] = $arParams['EMPTY_RESULT'] !== 'Y' ? 'N' : 'Y';
		$arParams['IS_AJAX'] = $arParams['IS_AJAX'] !== 'Y' ? 'N' : 'Y';
		$arParams['GET_AVATARS'] = $arParams['GET_AVATARS'] !== 'Y' ? 'N' : 'Y';
		$arParams['FORCE_FILTER'] = $arParams['FORCE_FILTER'] !== 'Y' ? 'N' : 'Y';
		$arParams['PATH_TO_USER'] = $arParams['PATH_TO_USER'] ?? '/company/personal/user/#user_id#/';
		$arParams['PATH_TO_MERGE'] = $arParams['PATH_TO_MERGE'] ?? '';
		$arParams['PATH_TO_DEAL_KANBANCATEGORY'] = $arParams['PATH_TO_DEAL_KANBANCATEGORY'] ?? '';

		return $arParams;
	}

	protected function fillDefaultAttributes(): void
	{
		$this->fmTypes = [
			'EMAIL_WORK' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_WORK'),
			'EMAIL_HOME' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_HOME'),
			'EMAIL_OTHER' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_OTHER'),
			'PHONE_MOBILE' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_MOBILE'),
			'PHONE_WORK' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_WORK'),
			'PHONE_FAX' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_FAX'),
			'PHONE_HOME' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_HOME'),
			'PHONE_PAGER' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_PAGER'),
			'PHONE_OTHER' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_OTHER'),
		];

		$this->allowSemantics = [
//			PhaseSemantics::PROCESS
		];
	}

	protected function init(): bool
	{
		if (!Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_KANBAN_CRM_NOT_INSTALLED'));
			return false;
		}
		if (!\CCrmPerms::IsAccessEnabled())
		{
			return false;
		}

		$this->fillDefaultAttributes();

		if (!isset($this->arParams['~NAME_TEMPLATE']))
		{
			$this->arParams['~NAME_TEMPLATE'] = PersonNameFormatter::getFormat();
		}
		else
		{
			$this->arParams['~NAME_TEMPLATE'] = str_replace(
				['#NOBR#', '#/NOBR#'],
				['', ''],
				trim($this->arParams['~NAME_TEMPLATE'])
			);
		}

		$type = mb_strtoupper($this->arParams['ENTITY_TYPE'] ?? '');

		$this->entity = Kanban\Entity::getInstance($type);
		if(!$this->entity)
		{
			return false;
		}

		if(!$this->entity->isKanbanSupported())
		{
			ShowError(Loc::getMessage('CRM_KANBAN_NOT_SUPPORTED'));
			return false;
		}

		$this->entity->setCanEditCommonSettings($this->canEditSettings());

		if (
			isset($this->arParams['EXTRA']['CATEGORY_ID'])
			&& $this->arParams['EXTRA']['CATEGORY_ID'] > 0
			&& $this->entity->isCategoriesSupported()
		)
		{
			$this->entity->setCategoryId((int)$this->arParams['EXTRA']['CATEGORY_ID']);
		}
		$this->arParams['ENTITY_TYPE_CHR'] = $this->entity->getTypeName();
		$this->arParams['ENTITY_TYPE_INT'] = $this->entity->getTypeId();
		$this->arParams['ENTITY_TYPE_INFO'] = $this->entity->getTypeInfo();
		$this->arParams['IS_DYNAMIC_ENTITY'] = \CCrmOwnerType::isPossibleDynamicTypeId($this->entity->getTypeId());
		$this->arParams['ENTITY_PATH'] = $this->getEntityPath($this->entity->getTypeName());
		$this->arParams['EDITOR_CONFIG_ID'] = $this->entity->getEditorConfigId();

		if (
			$this->entity->isRestPlacementSupported() &&
			Loader::includeModule('rest') &&
			is_callable('\Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl')
		)
		{
			$this->arParams['HIDE_REST'] = \CUserOptions::getOption(
				static::OPTION_CATEGORY,
				static::OPTION_NAME_HIDE_REST_DEMO,
				false
			);
			$this->arParams['REST_DEMO_URL'] = Marketplace\Url::getConfigurationPlacementUrl(
				$this->entity->getConfigurationPlacementUrlCode(),
				static::REST_CONFIGURATION_PLACEMENT_URL_CONTEST
			);
		}
		else
		{
			$this->arParams['HIDE_REST'] = true;
			$this->arParams['REST_DEMO_URL'] = '';
		}
		//additional select-edit fields
		$this->additionalSelect = $this->entity->getAdditionalSelectFields();
		$this->additionalEdit = $this->entity->getAdditionalEditFields();
		//redefine price-field
		if ($this->entity->isCustomPriceFieldsSupported())
		{
			$this->fieldSum = $this->entity->getCustomPriceFieldName() ?? '';
		}
		if($this->arParams['PAGE'] > 1)
		{
			$this->blockPage = $this->arParams['PAGE'];
		}
		//init arParams
		$this->statusKey = $this->entity->getStageFieldName();

		$this->currentUserID = \CCrmSecurityHelper::GetCurrentUserID();
		$this->arParams['USER_ID'] = $this->currentUserID;
		if (isset($this->arParams['PATH_TO_IMPORT']))
		{
			$uriImport = new Uri(
				$this->arParams['PATH_TO_IMPORT']
			);
			$importUriParams = [
				'from' => 'kanban'
			];
			if ($this->entity->getCategoryId() > 0)
			{
				$importUriParams['category_id'] = $this->entity->getCategoryId();
			}
			$uriImport->addParams($importUriParams);
			$this->arParams['PATH_TO_IMPORT'] = $uriImport->getUri();
		}
		else
		{
			$this->arParams['PATH_TO_IMPORT'] = '';
		}
		$this->currency = $this->arParams['CURRENCY'] = $this->entity->getCurrency();
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
	 * Get stages description for current entity.
	 *
	 * @param boolean $isClear Clear static cache.
	 * @return array
	 */
	protected function getStatuses($isClear = false): array
	{
		static $statuses = null;

		if ($isClear)
		{
			$statuses = null;
		}

		if ($statuses !== null)
		{
			return $statuses;
		}

		$statuses = [];
		$allStatuses = [];
		$statusEntityId = $this->entity->getStatusEntityId();
		$statusList = \Bitrix\Crm\StatusTable::getList([
			'filter' => [
				'=ENTITY_ID' => $statusEntityId,
			],
			'order' => [
				'SORT' => 'ASC',
			],
		]);
		while($status = $statusList->fetch())
		{
			$allStatuses[] = $status;
		}
		if($statusEntityId === Order\OrderStatus::NAME)
		{
			$orderStatuses = Order\OrderStatus::getListInCrmFormat($isClear);
			foreach($orderStatuses as &$status)
			{
				$status['SEMANTICS'] = Order\OrderStatus::getSemanticID($status['STATUS_ID']);
			}
			unset($status);
			$allStatuses = array_merge($allStatuses, $orderStatuses);
		}

		foreach ($allStatuses as $status)
		{
			$status['STATUS_ID'] = htmlspecialcharsbx($status['STATUS_ID']);

			if ($status['SEMANTICS'] === PhaseSemantics::SUCCESS)
			{
				$status['PROGRESS_TYPE'] = 'WIN';
			}
			elseif ($status['SEMANTICS'] === PhaseSemantics::FAILURE)
			{
				$status['PROGRESS_TYPE'] = 'LOOSE';
			}
			else
			{
				$status['PROGRESS_TYPE'] = 'PROGRESS';
				$status['SEMANTICS'] = PhaseSemantics::PROCESS;
			}

			$statuses[$status['STATUS_ID']] = $status;
		}

		return PhaseColorScheme::fillDefaultColors($statuses);
	}

	/**
	 * @param int $id
	 * @return array|null
	 */
	protected function getStatusById(int $id): ?array
	{
		$statuses = $this->getStatuses(true);
		return ($statuses[$id] ?? null);
	}

	/**
	 * Make select from presets.
	 * @return array
	 */
	protected function getSelect(): array
	{
		static $select = null;

		if ($select === null)
		{
			$additionalFields = array_keys($this->additionalSelect);

			$select = $this->entity->getItemsSelect($additionalFields);

			if (!empty($this->fieldSum))
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
	protected function getFilter(): array
	{
		static $filter = null;

		if ($this->arParams['FORCE_FILTER'] === 'Y')
		{
			return [];
		}

		if ($filter !== null)
		{
			return $filter;
		}

		$filter = [];
		$filterLogic = [
			'TITLE',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'POST',
			'COMMENTS',
			'COMPANY_TITLE',
			'COMPANY_COMMENTS',
			'CONTACT_FULL_NAME',
			'CONTACT_COMMENTS',
		];
		$filterAddress = [
			'ADDRESS', 'ADDRESS_2', 'ADDRESS_PROVINCE', 'ADDRESS_REGION', 'ADDRESS_CITY',
			'ADDRESS_COUNTRY', 'ADDRESS_POSTAL_CODE'
		];
		$filterHistory = ['STAGE_ID_FROM_HISTORY', 'STAGE_ID_FROM_SUPPOSED_HISTORY', 'STAGE_SEMANTIC_ID_FROM_HISTORY'];
		//from main.filter
		$grid = $this->entity->getFilterOptions();
		$gridFilter = $this->entity->getGridFilter();
		$search = $grid->GetFilter($gridFilter);
		\Bitrix\Crm\UI\Filter\EntityHandler::internalize($gridFilter, $search);
		if (!isset($search['FILTER_APPLIED']))
		{
			$search = [];
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
				if (
					in_array(mb_substr($key, 0, 1), array('=', '<', '>', '@', '!'))
					&& !(mb_substr($key, 0, 1) === '!' && $item === false)
				)
				{
					$key = mb_substr($key, 1);
				}
				$search[$key] = $item;
			}
			foreach ($gridFilter as $key => $item)
			{
				//fill filter by type
				$fromFieldName = $key . '_from';
				$toFieldName = $key . '_to';
				if ($item['type'] === 'date')
				{
					if (!empty($search[$fromFieldName]))
					{
						$filter['>='.$key] = $search[$fromFieldName] . ' 00:00:00';
					}
					if (!empty($search[$toFieldName]))
					{
						$filter['<='.$key] = $search[$toFieldName] . ' 23:59:00';
					}
					if ((isset($search[$key]) && $search[$key] === false))
					{
						$filter[$key] = $search[$key];
					}
					elseif ((isset($search['!'.$key]) && $search['!'.$key] === false))
					{
						$filter['!'.$key] = $search['!'.$key];
					}
				}
				elseif ($item['type'] === 'number')
				{
					$fltType = $search[$key . '_numsel'] ?? 'exact';
					if (
						($fltType === 'exact' || $fltType === 'range')
						&& isset($search[$fromFieldName], $search[$toFieldName])
					)
					{
						$filter['>='.$key] = $search[$fromFieldName];
						$filter['<='.$key] = $search[$toFieldName];
					}
					elseif ($fltType === 'exact' && isset($search[$fromFieldName]))
					{
						$filter[$key] = $search[$fromFieldName];
					}
					elseif (
						$fltType === 'more'
						&& isset($search[$fromFieldName])
					)
					{
						$filter['>'.$key] = $search[$fromFieldName];
					}
					elseif (
						$fltType === 'less' &&
						isset($search[$toFieldName])
					)
					{
						$filter['<'.$key] = $search[$toFieldName];
					}
					elseif ((isset($search[$key]) && $search[$key] === false))
					{
						$filter[$key] = $search[$key];
					}
					elseif ((isset($search['!'.$key]) && $search['!'.$key] === false))
					{
						$filter['!'.$key] = $search['!'.$key];
					}
				}
				elseif (isset($search[$key]))
				{
					if ((isset($search[$key]) && $search[$key] === false))
					{
						$filter[$key] = $search[$key];
					}
					elseif ((isset($search['!'.$key]) && $search['!'.$key] === false))
					{
						$filter['!'.$key] = $search['!'.$key];
					}
					elseif (in_array($key, $filterLogic, true))
					{
						$filter['?' . $key] = $search[$key];
					}
					elseif (in_array($key, $filterAddress, true))
					{
						$filter['=%' . $key] = $search[$key] . '%';
					}
					elseif (in_array($key, $filterHistory, true))
					{
						$filter['%' . $key] = $search[$key];
					}
					elseif ($key === 'STATUS_CONVERTED')
					{
						$filter[$key === 'N' ? 'STATUS_SEMANTIC_ID' : '!STATUS_SEMANTIC_ID'] = 'P';
					}
					elseif ($key === 'ORDER_TOPIC' || $key === 'ACCOUNT_NUMBER')
					{
						$filter['~' . $key] ='%' . $search[$key] . '%';
					}
					elseif (
						$key === 'ENTITIES_LINKS' &&
						(
						$this->entity->isEntitiesLinksInFilterSupported()
						)
					)
					{
						$ownerData = explode('_', $search[$key]);
						if (count($ownerData) > 1)
						{
							$ownerTypeName = \CCrmOwnerType::resolveName(
								\CCrmOwnerType::resolveID($ownerData[0])
							);
							$ownerID = (int)$ownerData[1];
							if (!empty($ownerTypeName) && $ownerID > 0)
							{
								$filter[$this->entity->getFilterFieldNameByEntityTypeName($ownerTypeName)] = $ownerID;
							}
						}
					}
					else
					{
						$filter[$this->entity->prepareFilterField($key)] = $search[$key];
					}
				}
				elseif (isset($search['!'.$key]) && $search['!'.$key] === false)
				{
					$filter['!'.$key] = $search['!'.$key];
				}
				elseif (isset($item['alias'], $search[$item['alias']]))
				{
					$filter['=' . $key] = $search[$item['alias']];
				}
			}
			//search index
			$find = $search['FIND'] ? trim($search['FIND']) : null;
			if (!empty($find))
			{
				$search['FIND'] = $find;
				$filter['SEARCH_CONTENT'] = $search['FIND'];
			}
		}
		if (isset($filter['COMMUNICATION_TYPE']))
		{
			if (!is_array($filter['COMMUNICATION_TYPE']))
			{
				$filter['COMMUNICATION_TYPE'] = [$filter['COMMUNICATION_TYPE']];
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
			isset($filter['OVERDUE'])
			&& ($this->entity->isOverdueFilterSupported())
		)
		{
			$key = $this->entity->getCloseDateFieldName();
			$date = new \Bitrix\Main\Type\Date;
			if ($filter['OVERDUE'] === 'Y')
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
			isset($filter['ACTIVITY_COUNTER'])
			&& $this->entity->isActivityCountersFilterSupported()
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
						$this->entity->getTypeId(),
						$counterTypeID,
						0
					);
					$filter += $counter->prepareEntityListFilter(
						array(
							'MASTER_ALIAS' => $this->entity->getTableAlias(),
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

		$filter = Deal\OrderFilter::prepareFilter($filter);

		//deal
		if ($this->entity->isCategoriesSupported())
		{
			$filter['CATEGORY_ID'] = $this->entity->getCategoryId();
		}
		//invoice
		if ($this->entity->isRecurringSupported())
		{
			$filter['!IS_RECURRING'] = 'Y';
		}
		if (isset($filter['OVERDUE']))
		{
			unset($filter['OVERDUE']);
		}
		//detect success/fail columns
		$this->prepareSemanticIdsAndStages($filter);

		$entityTypeID = $this->entity->getTypeId();
		//region Apply Search Restrictions
		$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
		if(!$searchRestriction->isExceeded($entityTypeID))
		{
			$searchRestriction->notifyIfLimitAlmostExceed($entityTypeID);

			SearchEnvironment::convertEntityFilterValues(
				$entityTypeID,
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
		$grid = $this->entity->getFilterOptions();
		$gridFilter = $this->entity->getGridFilter();
		$search = $grid->GetFilter($gridFilter);
		\Bitrix\Crm\UI\Filter\EntityHandler::internalize($gridFilter, $search);

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
			$filterFields = $crmCmp->createGlFilter($search, $runtime);
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

		$this->prepareSemanticIdsAndStages($filterFields);

		return $filterFields;
	}

	/**
	 * @param $filter
	 */
	protected function prepareSemanticIdsAndStages(array $filter = []): void
	{
		$this->semanticIds = [];
		if (isset($filter['STATUS_SEMANTIC_ID']))
		{
			$this->semanticIds = (array)$filter['STATUS_SEMANTIC_ID'];
		}
		elseif ($this->entity->getTypeName() === 'LEAD')
		{
			$this->semanticIds = [PhaseSemantics::PROCESS, PhaseSemantics::SUCCESS, PhaseSemantics::FAILURE];
		}
		elseif (isset($filter['STAGE_SEMANTIC_ID']))
		{
			$this->semanticIds = (array)$filter['STAGE_SEMANTIC_ID'];
		}
		elseif ($this->entity->getTypeName() === 'DEAL')
		{
			$this->semanticIds = [PhaseSemantics::PROCESS, PhaseSemantics::SUCCESS, PhaseSemantics::FAILURE];
		}
		elseif (
			isset($filter['=STATUS_ID'])
			&& $this->entity->getTypeName() === 'QUOTE'
		)
		{
			//todo refactor it somehow
			$list = \Bitrix\Crm\StatusTable::getList([
				'select' => [
					'STATUS_ID',
				],
				'order' => [
					'SORT' => 'ASC',
				],
				'filter' => [
					'=STATUS_ID' => $filter['=STATUS_ID'],
					'=ENTITY_ID' => 'QUOTE_STATUS',
				],
			]);
			while ($status = $list->fetch())
			{
				$this->allowStages[] = $status['STATUS_ID'];
			}
		}

		if (in_array(PhaseSemantics::PROCESS, $this->semanticIds, true))
		{
			$this->allowSemantics[] = PhaseSemantics::PROCESS;
		}
		if (in_array(PhaseSemantics::SUCCESS, $this->semanticIds, true))
		{
			$this->allowSemantics[] = PhaseSemantics::SUCCESS;
		}
		if (in_array(PhaseSemantics::FAILURE, $this->semanticIds, true))
		{
			$this->allowSemantics[] = PhaseSemantics::FAILURE;
		}
		if (empty($this->allowSemantics))
		{
			$this->allowSemantics[] = PhaseSemantics::PROCESS;
		}
		if (isset($filter[$this->statusKey]))
		{
			$this->allowStages = $filter[$this->statusKey];
		}
	}

	/**
	 * Get path for entity from params or module settings.
	 * @param string $type
	 * @return string
	 */
	protected function getEntityPath($type): ?string
	{
		$params = $this->arParams;
		$pathKey = 'PATH_TO_'.$type.'_DETAILS';
		$url = !array_key_exists($pathKey, $params) ? \CrmCheckPath($pathKey, '', '') : $params[$pathKey];
		if(!($url !== '' && CCrmOwnerType::IsSliderEnabled(CCrmOwnerType::ResolveID($type))))
		{
			$pathKey = 'PATH_TO_'.$type.'_SHOW';
			$url = !array_key_exists($pathKey, $params) ? \CrmCheckPath($pathKey, '', '') : $params[$pathKey];
		}

		if (!$url)
		{
			$url = $this->entity->getUrlTemplate();
		}

		return $url;
	}

	protected function getDeleteColumn(array $params): array
	{
		return [
			'real_id' => static::COLUMN_NAME_DELETED,
			'real_sort' => $params['real_sort'],
			'id' => static::COLUMN_NAME_DELETED,
			'name' => Loc::getMessage('CRM_KANBAN_SYS_STATUS_DELETE'),
			'color' => '',
			'type' => '',
			'sort' => $params['sort'],
			'count' => 0,
			'total' => 0,
			'currency' => $this->currency,
			'dropzone' => true,
			'alwaysShowInDropzone' => true
		];
	}

	protected function isDropZone(array $status): bool
	{
		if (!empty($this->allowStages) && !in_array($status['STATUS_ID'], $this->allowStages, true))
		{
			return true;
		}
		if (in_array($status['STATUS_ID'], $this->allowStages, true))
		{
			return false;
		}
		if (
			(
				$status['PROGRESS_TYPE'] === 'WIN' &&
				!in_array(PhaseSemantics::SUCCESS, $this->allowSemantics, true)
			)
			||
			(
				$status['PROGRESS_TYPE'] === 'LOOSE' &&
				!in_array(PhaseSemantics::FAILURE, $this->allowSemantics, true)
			)
			||
			(
				$status['PROGRESS_TYPE'] !== 'WIN' &&
				$status['PROGRESS_TYPE'] !== 'LOOSE' &&
				!in_array(PhaseSemantics::PROCESS, $this->allowSemantics, true)
			)
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * Get columns.
	 * @param boolean $clear Clear static var.
	 * @param boolean $withoutCache Without static cache.
	 * @param array $params Some additional params.
	 * @return array
	 */
	protected function getColumns($clear = false, $withoutCache = false, array $params = []): array
	{
		static $columns = [];

		if ($withoutCache)
		{
			$clear = $withoutCache;
		}

		if ($this->arParams['ONLY_ITEMS'] === 'Y')
		{
			return $columns;
		}

		if ($clear)
		{
			$columns = [];
		}

		$params['originalColumns'] = $params['originalColumns'] ?? false;

		if (empty($columns))
		{
			$runtime = [];
			$baseCurrency = $this->currency;
			if ($this->entity->getTypeName() === 'ORDER')
			{
				$filter = $this->getOrderFilter($runtime);
				if (isset($filter[$this->statusKey]))
				{
					$this->allowStages = $filter[$this->statusKey];
				}
			}
			else
			{
				$filter = $this->getFilter();
			}
			$sort = 0;
			$winColumn = [];
			$userPerms = $this->getCurrentUserPermissions();
			// prepare each status
			$isFirstDropZone = false;
			foreach ($this->getStatuses($clear) as $status)
			{
				$sort += 100;
				$isDropZone = $this->isDropZone($status);
				// first drop zone
				if (!$isFirstDropZone && $isDropZone)
				{
					$isFirstDropZone = true;
				}
				// add 'delete' column
				if (
					$isFirstDropZone &&
					!$params['originalColumns']
				)
				{
					$isFirstDropZone = false;
					$columns[static::COLUMN_NAME_DELETED] = $this->getDeleteColumn([
						'real_sort' => $status['SORT'],
						'sort' => $sort
					]);
				}
				// format column
				$column = [
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
					'dropzone' => $isDropZone,
					'alwaysShowInDropzone' => $this->isAlwaysShowInDropzone($status),
					'canAddItem' => $this->entity->canAddItemToStage($status['STATUS_ID'], $userPerms),
				];
				// win column
				if (
					!$params['originalColumns'] &&
					$status['PROGRESS_TYPE'] === 'WIN'
				)
				{
					$winColumn[$status['STATUS_ID']] = $column;
				}
				else
				{
					$columns[$status['STATUS_ID']] = $column;
				}
			}

			$columns += $winColumn;
			$lastColumn = end($columns);

			if (!isset($columns[static::COLUMN_NAME_DELETED]))
			{
				$columns[static::COLUMN_NAME_DELETED] = $this->getDeleteColumn([
					'real_sort' => $lastColumn['real_sort'] + 10,
					'sort' => $lastColumn['sort'] + 10
				]);
			}

			//get sums and counts
			$this->entity->fillStageTotalSums($filter, $runtime, $columns);
		}

		// without static cache
		if ($withoutCache)
		{
			$tmpColumns = $columns;
			$columns = [];

			return $tmpColumns;
		}

		return $columns;
	}

	/**
	 * @param array $status
	 * @return bool
	 */
	protected function isAlwaysShowInDropzone(array $status): bool
	{
		return ($status['PROGRESS_TYPE'] === 'WIN' || $status['PROGRESS_TYPE'] === 'LOOSE');
	}

	/**
	 * Insert new key/value in the array after the key.
	 * @param array $array Array for change.
	 * @param mixed $afterKey Insert after this key. If null then the value insert in beginning of the array.
	 * @param mixed $newKey New key.
	 * @param mixed $newValue New value.
	 * @return array
	 */
	protected function arrayInsertAfter(array $array, $afterKey, $newKey, $newValue): array
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

		return $array;
	}

	/**
	 * Remember last id of entity for user.
	 * @param int|bool $lastId If set, save in config.
	 * @return void|int
	 */
	protected function rememberLastId($lastId = false)
	{
		$lastIds = \CUserOptions::getOption(
			static::OPTION_CATEGORY,
			'kanban_sort_last_id',
			[]
		);
		$typeName = $this->entity->getTypeName();
		if ($lastId !== false)
		{
			$lastIds[$typeName] = $lastId;
			\CUserOptions::setOption(
				static::OPTION_CATEGORY,
				'kanban_sort_last_id',
				$lastIds
			);
		}
		else
		{
			return $lastIds[$typeName] ?? 0;
		}
	}

	/**
	 * Get last ID for current entity.
	 * @return int
	 */
	protected function getLastId(): int
	{
		return $this->entity->getItemLastId();
	}

	/**
	 * Sort items by user order.
	 * @param array $items Items for sort.
	 * @return array
	 */
	protected function sort(array $items)
	{
		static $lastIdremember = null;

		if ($lastIdremember === null)
		{
			$lastIdremember = $this->rememberLastId();
		}

		$sortedIds = [];
		$sort = Kanban\SortTable::getPrevious([
			'ENTITY_TYPE_ID' => $this->entity->getTypeName(),
			'ENTITY_ID' => array_keys($items),
		]);
		if (!empty($sort))
		{
			foreach ($sort as $id => $prev)
			{
				if ($prev > 0 && !isset($items[$prev]))
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
					Kanban\SortTable::setPrevious(array(
						'ENTITY_TYPE_ID' => $this->entity->getTypeName(),
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
	protected function getTracesById($entityId): array
	{
		static $actualSources = null;
		static $entityTypeId = null;
		static $channelNames = null;

		$entityId = (array) $entityId;
		$traces = [];
		$traceEntities = [];

		if ($entityTypeId === null)
		{
			$entityTypeId = $this->entity->getTypeId();
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
				'NAME' => $channelNames[$row['CODE']] ?? Channel\Base::getNameByCode($row['CODE']),
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
	 * @return array
	 * @noinspection SlowArrayOperationsInLoopInspection
	 */
	protected function getItems(array $filter = []): array
	{
		static $path = null;
		static $currency = null;
		static $columns = null;

		$result = [];
		$type = $this->entity->getTypeName();

		$runtime = [];
		$select = $this->getSelect();
		if ($type === 'ORDER')
		{
			$filterCommon = $this->getOrderFilter($runtime);
		}
		else
		{
			$filterCommon = $this->getFilter();
		}

		$displayedFields = $this->getDisplayedFieldsList();
		$addSelect = array_keys($this->additionalSelect);
		$statusKey = $this->statusKey;

		if ($this->requiredFields)
		{
			$select = array_merge(
				$select,
				array_keys($this->requiredFields)
			);
		}

		// remove conflict keys and merge filters
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

		$parameters = [
			'filter' => $filter,
			'select' => $select,
			'order' => ['ID' => 'DESC'],
		];
		if(!empty($runtime))
		{
			$parameters['runtime'] = $runtime;
		}

		/** @noinspection IssetConstructsCanBeMergedInspection */
		$canGetAllItems = (
			isset($filter[$statusKey]) &&
			is_string($filter[$statusKey]) &&
			isset($columns[$filter[$statusKey]]['count']) &&
			$columns[$filter[$statusKey]]['count'] <= static::MAX_SORTED_ITEMS_COUNT
		);

		if(!$canGetAllItems)
		{
			$parameters['limit'] = $this->blockSize;
			$parameters['offset'] = $this->blockSize * ($this->blockPage - 1);
		}

		$res = $this->entity->getItems($parameters);
		if($canGetAllItems)
		{
			$res->NavStart($this->blockSize, false, $this->blockPage);
		}

		$pageCount = $res->NavPageCount;
		$specialReqKeys = [
			'OPPORTUNITY' => 'OPPORTUNITY_WITH_CURRENCY',
			'CONTACT_ID' => 'CLIENT',
			'COMPANY_ID' => 'CLIENT',
			'STORAGE_ELEMENT_IDS' => 'FILES',
		];

		$rows = [];
		while ($row = $res->fetch())
		{
			$row = $this->entity->prepareItemCommonFields($row);
			$rows[$row['ID']] = $row;
		}
		$rows = $this->entity->appendRelatedEntitiesValues($rows, $addSelect);

		$displayOptions =
			(new \Bitrix\Crm\Service\Display\Options())
				->setReturnMultipleFieldsAsSingle(false)
		;
		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getWebFormResultsRestriction();
		$restrictedItemIds = [];
		if (!$restriction->hasPermission())
		{
			$itemIds = array_keys($rows);
			$restriction->prepareDisplayOptions($this->entity->getTypeId(), $itemIds, $displayOptions);
			$restrictedItemIds = $displayOptions->getRestrictedItemIds();
			$this->arResult['RESTRICTED_VALUE_CLICK_CALLBACK'] = $restriction->prepareInfoHelperScript();
		}
		$display = new \Bitrix\Crm\Service\Display($this->entity->getTypeId(), $displayedFields, $displayOptions);
		$display->setItems($rows);
		$renderedRows = $display->getAllValues();

		$this->parents = Container::getInstance()->getParentFieldManager()->getParentFields(
			array_column($rows, 'ID'),
			array_keys($displayedFields),
			$this->entity->getTypeId()
		);

		$inlineFieldTypes = [
			'string',
			'integer',
			'float',
			'url',
			'money',
			'date',
			'datetime',
			'crm',
			'crm_status',
			'file',
			'iblock_element',
			'iblock_section',
			'enumeration',
			'address',
		];

		foreach($rows as $rowId => $row)
		{
			if (is_array($renderedRows[$rowId]))
			{
				$row = array_merge(
					$row,
					$renderedRows[$rowId]
				);
			}

			if ($row['CONTACT_ID'] > 0)
			{
				$row['CONTACT_TYPE'] = 'CRM_CONTACT';
			}
			if ($row['COMPANY_ID'] > 0)
			{
				$row['CONTACT_TYPE'] = 'CRM_COMPANY';
			}

			//additional fields
			$fields = [];
			foreach ($addSelect as $code)
			{
				if (array_key_exists($code, $row) && array_key_exists($code, $displayedFields))
				{
					$displayedField = $displayedFields[$code];
					if (!empty($row[$code]))
					{
						$fields[] = [
							'code' => $code,
							'title' => htmlspecialcharsbx($displayedField->getTitle()),
							'type' => $displayedField->getType(),
							'value' => $row[$code],
							'valueDelimiter' => in_array($displayedField->getType(), $inlineFieldTypes) ? ', ' : '<br>',
							'icon' => $displayedField->getDisplayParam('icon'),
							'html' => $displayedField->wasRenderedAsHtml()
						];
					}
				}
			}

			$this->addParentFields($fields, $row['ID']);

			$returnCustomer = isset($row['IS_RETURN_CUSTOMER']) && $row['IS_RETURN_CUSTOMER'] === 'Y';
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
					if (isset($this->requiredFields[$key]) && !$val && $val !== '0')
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
					if(
						isset($this->requiredFields[$reqKey])
						&& $reqKey === 'CLIENT'
						&& (
							!empty($row['COMPANY_ID'])
							|| !empty($row['CONTACT_ID'])
						)
					)
					{
						continue;
					}

					if (
						isset($this->requiredFields[$reqKey]) &&
						(
							!$row[$reqKeyOrig]
							||
							(
								$reqKeyOrig === 'OPPORTUNITY'
								&& $row['OPPORTUNITY_ACCOUNT'] <= 0
							)
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

			$result[$row['ID']] = [
				'id' =>  $row['ID'],
				'name' => htmlspecialcharsbx($row['TITLE'] ?: '#' . $row['ID']),
				'link' => $row['LINK'] ?? str_replace($this->pathMarkers, $row['ID'], $path),
				'columnId' => $columnId = htmlspecialcharsbx($row[$this->statusKey]),
				'columnColor' => isset($columns[$columnId]) ? $columns[$columnId]['color'] : '',
				'price' => $row['PRICE'],
				'price_formatted' => $row['PRICE_FORMATTED'],
				'date' => $row['DATE_FORMATTED'],
				'contactId' => (int)$row['CONTACT_ID'],
				'companyId' => (!empty($row['COMPANY_ID']) ? (int)$row['COMPANY_ID'] : null),
				'contactType' => $row['CONTACT_TYPE'],
				'modifyById' => $row['MODIFY_BY_ID'] ?? 0,
				'modifyByAvatar' => '',
				'activityShow' => 1,
				'activityErrorTotal' => 0,
				'activityProgress' => 0,
				'activityTotal' => 0,
				'page' => $this->blockPage,
				'pageCount' => $pageCount,
				'fields' => $fields,
				'return' => $returnCustomer,
				'returnApproach' => isset($row['IS_REPEATED_APPROACH']) && $row['IS_REPEATED_APPROACH'] === 'Y',
				'assignedBy' => $row['ASSIGNED_BY'],
				'required' => $required,
				'required_fm' => $requiredFm
			];
			$isRestricted = (!empty($restrictedItemIds) && in_array($row['ID'], $restrictedItemIds));
			if ($isRestricted)
			{
				$result[$row['ID']]['updateRestrictionCallback'] = $restriction->prepareInfoHelperScript();
			}
			if ($this->entity->hasClientFields())
			{
				$clientFields = [
					'contactName',
					'contactTooltip',
					'companyName',
					'companyTooltip',
				];
				foreach ($clientFields as $clientField)
				{
					if ($row[$clientField])
					{
						$result[$row['ID']][$clientField] = $isRestricted
							? $displayOptions->getRestrictedValueHtmlReplacer()
							: $row[$clientField];
					}
				}
			}
		}
		$result = $this->sort($result);

		return $result;
	}

	/**
	 * @param array $fields
	 * @param int $id
	 */
	protected function addParentFields(array &$fields, int $id): void
	{
		if (isset($this->parents[$id]))
		{
			foreach ($this->parents[$id] as $parent)
			{
				if (!empty($parent['code']) && !empty($parent['value']))
				{
					$fields[] = [
						'code' => $parent['code'],
						'title' => $parent['entityDescription'],
						'value' => $parent['value'],
						'type' => 'url',
						'html' => true,
					];
				}
			}
		}
	}

	/**
	 * Get all activities by id's and type of entity.
	 * @param array $activity Id's.
	 * @param array $errors Count of errors (deadline).
	 * @return array
	 */
	protected function getActivityCounters($activity, &$errors = []): array
	{
		if (empty($activity))
		{
			return [];
		}

		$return = [];
		$activity = array_unique($activity);

		//make filter
		$filter = [
			'BINDINGS' => []
		];
		$typeId = $this->entity->getTypeId();
		foreach ($activity as $id)
		{
			$filter['BINDINGS'][] = [
				'OWNER_ID' => $id,
				'OWNER_TYPE_ID' => $typeId
			];
		}

		// counters
		$date = new \Bitrix\Main\Type\DateTime;
		$date->add('-'.date('G').' hours')->add('-'.date('i').' minutes')->add('+1 day');//@ #81166
		$res = \CCrmActivity::GetList(
			[],
			array_merge(
				$filter,
				[
					'=COMPLETED' => 'N',
					'<=DEADLINE' => $date
				]
			),
			false, false,
			[
				'ID', 'OWNER_ID'
			]
		);
		$fetched = [];
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
			[],
			$filter,
			false,
			false,
			[
				'ID', 'COMPLETED', 'OWNER_ID'
			]
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
	protected function getAdditionalFields($clearCache = false): array
	{
		static $additionalFields = null;

		if ($clearCache)
		{
			$additionalFields = null;
		}

		if ($additionalFields === null)
		{
			$additionalFields = [];
			$displayedFields = $this->getDisplayedFieldsList($clearCache);

			foreach ($displayedFields as $field)
			{
				$additionalFields[$field->getId()] = [
					'title' => HtmlFilter::encode($field->getTitle()),
					'type' => $field->getType(),
					'code' =>$field->getId(),
				];
			}
		}

		return $additionalFields;
	}

	/**
	 * @param bool $clearCache
	 * @return \Bitrix\Crm\Service\Display\Field[]
	 */
	protected function getDisplayedFieldsList($clearCache = false): array
	{
		return $this->entity->getDisplayedFieldsList($clearCache);
	}

	/**
	 * Get additional fields for quick form.
	 * @return array
	 */
	protected function getAdditionalEditFields(): array
	{
		return $this->additionalEdit;
	}

	/**
	 * Gets current user perms.
	 * @return CCrmPerms
	 */
	protected function getCurrentUserPermissions(): \CCrmPerms
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
	protected function processRequestActions(): void
	{
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
		$action = $request->get('action');
		$id = $request->get('entity_id');

		// some actions for editor
		if ($action === 'get' && check_bitrix_sessid())
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
			if ($action === 'status' && $status === static::COLUMN_NAME_DELETED)
			{
				$ids = [];
			}

			foreach ($ids as $id)
			{
				//delete
				if ($action === 'status' && $status === static::COLUMN_NAME_DELETED)
				{
					$this->actionDelete($id);
				}
				//change status / stage
				if ($action === 'status' && isset($statuses[$status]))
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

			if ($this->arParams['IS_AJAX'] !== 'Y')
			{
				$uri = new Uri($request->getRequestUri());
				\LocalRedirect($uri->deleteParams(['action', 'entity_id', 'status'])->getUri());
			}
		}

		//subscribe / unsunbscribe
		$supervisor = $request->get('supervisor');
		if ($request->get('apply_filter') === 'Y')
		{
			if (Kanban\SupervisorTable::isSupervisor($this->entity->getTypeName()))
			{
				$supervisor = 'N';
			}
		}
		if ($supervisor)
		{
			Kanban\SupervisorTable::set($this->entity->getTypeName(), $supervisor === 'Y');
			$uri = new Uri($request->getRequestUri());
			\LocalRedirect($uri->deleteParams(array('supervisor', 'clear_filter'))->getUri());
		}
	}

	/**
	 * Current user is crm admin?
	 * @return boolean
	 */
	protected function isCrmAdmin(): bool
	{
		$crmPerms = new \CCrmPerms($this->currentUserID);

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
	protected function getAdmins(): array
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
			'!ID' => $this->currentUserID,
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
			$row = $this->processAvatar($row);
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
			return [];
		}

		$inlineEditorParameters = $this->entity->getInlineEditorParameters();
		$this->arResult['FIELDS_SECTIONS'] = $inlineEditorParameters['fieldsSections'];

		if ($this->entity->isInlineEditorSupported())
		{
			$this->userFields = $this->entity->getUserFields();

			$this->schemeFields = $inlineEditorParameters['schemeFields'];

			if($this->schemeFields)
			{
				$schemeInlineEdit = [
					[
						'name' => 'main',
						'title' => '',
						'type' => 'section',
						'elements' => array_values($this->schemeFields)
					]
				];
			}
		}

		$this->arResult['ITEMS'] = [];
		$this->arResult['ADMINS'] = $this->getAdmins();
		$this->arResult['MORE_FIELDS'] = $this->getAdditionalFields();
		$this->arResult['MORE_EDIT_FIELDS'] = $this->getAdditionalEditFields();
		$this->arResult['FIELDS_DISABLED'] = $this->disableMoreFields;
		$this->arResult['CATEGORIES'] = [];

		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
		$action = $request->get('action');

		//new actions format
		if ($action && is_callable([$this, 'action' . $action]))
		{
			if (!check_bitrix_sessid())
			{
				return [
					'ERROR' => Loc::getMessage('CRM_KANBAN_ERROR_SESSION_EXPIRED'),
					'FATAL' => true
				];
			}

			return $this->{'action' . $action}();
		}

		$this->processRequestActions();

		if ($this->arParams['EMPTY_RESULT'] !== 'Y')
		{
			$userPermissions = $this->getCurrentUserPermissions();
			$this->arResult['ACCESS_CONFIG_PERMS'] = $this->isCrmAdmin();
			$this->arResult = array_merge($this->arResult, $this->entity->getPermissionParameters($userPermissions));

			//output
			if ($this->arParams['ONLY_COLUMNS'] === 'Y')
			{
				$this->arResult['ITEMS'] = [
					'items' => [],
					'columns' => array_values($this->getColumns())
				];
			}
			else
			{
				$items = [];
				$columns = $this->getColumns();
				$this->requiredFields = $this->entity->getRequiredFieldsByStages($this->getStatuses());
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
							$filter = [];
							$filter[$this->statusKey] = $column['id'];
							$items += $this->getItems($filter);
						}
					}
				}
				$this->entity->appendMultiFieldData($items, $this->allowedFMtypes);

				$this->arResult['ITEMS'] = array(
					'columns' => array_values($columns),
					'items' => $items
				);

				//get activity
				if (!empty($this->arResult['ITEMS']['items']))
				{
					if ($this->entity->isActivityCountersSupported())
					{
						$activityCounters = $this->getActivityCounters(
							array_keys($this->arResult['ITEMS']['items']),
							$errors
						);
						foreach ($activityCounters as $id => $actCC)
						{
							$this->arResult['ITEMS']['items'][$id]['activityProgress'] = $actCC['N'] ?? 0;
							$this->arResult['ITEMS']['items'][$id]['activityTotal'] = $actCC['Y'] ?? 0;
							if (isset($errors[$id]))
							{
								$this->arResult['ITEMS']['items'][$id]['activityErrorTotal'] = $errors[$id];
							}
						}
					}

					$this->arResult['ITEMS']['items'] = array_values($this->arResult['ITEMS']['items']);
				}
			}

			if ($this->entity->isCategoriesSupported())
			{
				$this->arResult['CATEGORIES'] = $this->entity->getCategories($userPermissions);
			}
		}

		$this->arResult['ITEMS']['last_id'] = $this->getLastId();
		$this->arResult['ITEMS']['scheme_inline'] = $schemeInlineEdit ?? null;
		$this->arResult['ITEMS']['customFields'] = array_keys($this->arResult['MORE_FIELDS']);

		// items for demo import
		if (
			$this->arParams['IS_AJAX'] !== 'Y'
			&&
			isset($this->arResult['ITEMS']['columns'][0]['id'], $this->arResult['ITEMS']['columns'][0]['count'])
			&& (
				$this->blockPage * $this->blockSize >= $this->arResult['ITEMS']['columns'][0]['count']
			)
		)
		{
			if ($this->entity->isInlineEditorSupported() && $this->entity->isContactCenterSupported())
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

		PullManager::getInstance()->subscribeOnKanbanUpdate(
			$this->arParams['ENTITY_TYPE'],
			($this->arParams['EXTRA'] ?? null)
		);

		if ($this->arParams['IS_AJAX'] === 'Y')
		{
			return $this->arResult;
		}
		else
		{
			$this->arResult['CLIENT_FIELDS_RESTRICTIONS'] = $this->entity->getClientFieldsRestrictions();
		}

		$GLOBALS['APPLICATION']->setTitle($this->entity->getTitle());
		$this->IncludeComponentTemplate();
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
		if (SITE_CHARSET !== 'UTF-8')
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
	protected function actionNotifyAdmin(): array
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
				if ($params['ENTITY_TYPE_CHR'] === 'DEAL')
				{
					$pathColumnEdit = '/crm/configs/status/?ACTIVE_TAB=status_tab_DEAL_STAGE';
				}
				elseif ($params['ENTITY_TYPE_CHR'] === 'LEAD')
				{
					$pathColumnEdit = '/crm/configs/status/?ACTIVE_TAB=status_tab_STATUS';
				}
				else
				{
					$pathColumnEdit = '/crm/configs/status/?ACTIVE_TAB=status_tab_'. $params['ENTITY_TYPE_CHR'] .'_STATUS';
				}
				\CIMNotify::Add([
					'TO_USER_ID' => $userId,
					'FROM_USER_ID' => $this->currentUserID,
					'NOTIFY_TYPE' => IM_NOTIFY_FROM,
					'NOTIFY_MODULE' => 'crm',
					'NOTIFY_TAG' => 'CRM|NOTIFY_ADMIN|'.$userId.'|'.$this->userId,
					'NOTIFY_MESSAGE' => Loc::getMessage('CRM_ACCESS_NOTIFY_MESSAGE', [
						'#URL#' => $pathColumnEdit
					])
				]);
			}
		}

		return [
			'status' => 'success'
		];
	}

	/**
	 * Add new stage, update stage, move stage.
	 * @return array
	 */
	protected function actionModifyStage(): array
	{
		if (!$this->isCrmAdmin())
		{
			return [];
		}
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
			'ENTITY_ID' => $this->entity->getStatusEntityId(),
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
		$fields['CATEGORY_ID'] = $this->entity->getCategoryId() > 0 ? $this->entity->getCategoryId() : null;
		//todo move to entity
		$isOrder = ($this->entity->getTypeName() === 'ORDER');
		if ($columnId !== '' && isset($stages[$columnId]))
		{
			if ($delete)
			{
				$result = $this->deleteStage($columnId, $stages);
			}
			else
			{
				$internalId = ($isOrder) ? $columnId : $stages[$columnId]['real_id'];
				$result = $this->updateStage($internalId, $fields, ['STATUS_ID' => $columnId]);
			}
		}
		else
		{
			$result = $this->addStage($fields);
			$internalId = $result->getId();
		}
		if ($result->isSuccess())
		{
			if($delete)
			{
				return [];
			}
			if ($isOrder)
			{
				$statusId = $internalId;
			}
			else
			{
				$statusEntityId = $this->entity->getStatusEntityId();
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
					$internalId = ($isOrder) ? $stage['id'] : $stage['real_id'];
					$this->updateStage($internalId, ['SORT' => $sort]);
					$sort += 10;
				}

				return \htmlspecialcharsback($stages[$statusId]);
			}

			return [
				'ERROR' => 'Unknown error'
			];
		}

		$errors = $result->getErrorMessages();

		return ['ERROR' => current($errors)];
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

		if ($stages[$columnId]['type'] === 'WIN')
		{
			$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_WIN')));
			return $result;
		}

		if (!$this->entity->isStageEmpty($columnId))
		{
			$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_NOT_EMPTY')));
			return $result;
		}

		$statusEntityId = $this->entity->getStatusEntityId();
		if (empty($statusEntityId))
		{
			return $result;
		}

		//todo move to entity
		if ($this->entity->getTypeName() === 'ORDER')
		{
			$defaultStatuses = Order\OrderStatus::getDefaultStatuses();
			if (isset($defaultStatuses[$columnId]))
			{
				$result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_STAGE_IS_SYSTEM')));
				return $result;
			}

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

			$statusObject->delete($internalId);
			if (!empty($statusObject->GetLastError()))
			{
				$result->addError(new Error($statusObject->GetLastError()));
			}
			else
			{
				$item = Kanban\Entity::getInstance($this->entity->getTypeName())
					->createPullStage($statusInfo);
				PullManager::getInstance()->sendStageDeletedEvent(
					$item,
					[
						'TYPE' => $this->entity->getTypeName(),
						'CATEGORY_ID' => $this->arParams['EXTRA']['CATEGORY_ID']
					]
				);
			}
		}

		return $result;
	}

	/**
	 * Save changes into status stage for 'actionModifyStage' method
	 *
	 * @param $id
	 * @param array $fields
	 * @param array|null $params
	 * @return Result
	 */
	private function updateStage($id, array $fields = [], ?array $params = []): Result
	{
		$result = new Result();

		//todo move to entity
		if ($this->entity->getTypeName() !== 'ORDER')
		{
			$statusEntityId = $this->entity->getStatusEntityId();
			$status = new \CCrmStatus($statusEntityId);
			$status->update($id, $fields);
			if (!empty($status->GetLastError()))
			{
				$result->addError(new Error($status->GetLastError()));
			}
			else if (isset($params['STATUS_ID']))
			{
				$data = array_merge($fields, $params);
				$item = Kanban\Entity::getInstance($this->entity->getTypeName())
					->createPullStage($data);
				PullManager::getInstance()->sendStageUpdatedEvent(
					$item,
					[
						'TYPE' => $this->entity->getTypeName(),
						'CATEGORY_ID' => $this->arParams['EXTRA']['CATEGORY_ID']
					]
				);
			}
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
			if (!empty($fields['NAME']) && $result->isSuccess())
			{
				Sale\Internals\StatusLangTable::update(
					[
						'STATUS_ID' => $id,
						'LID' => $this->getLanguageId(),
					],
					['NAME' => $fields['NAME']]
				);
			}
		}

		return $result;
	}

	/**
	 * Add new status stage
	 *
	 * @param array $fields
	 * @return AddResult
	 */
	private function addStage(array $fields = []): AddResult
	{
		$result = new AddResult();

		//todo move to entity
		if ($this->entity->getTypeName() !== 'ORDER')
		{
			$statusEntityId = $this->entity->getStatusEntityId();
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
		}
		else
		{
			if (!Loader::includeModule('sale'))
			{
				return $result;
			}

			$createFields = array_intersect_key($fields, array_flip(['SORT', 'COLOR']));

			if(!empty($createFields['COLOR']) && mb_strpos($createFields['COLOR'], '#') !== 0)
			{
				$createFields['COLOR'] = '#' . $createFields['COLOR'];
			}

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
				$newId = chr(random_int(65, 90)); //A-Z
				if (is_array($result) && count($result) >= 27)
				{
					$newId .= chr(random_int(65, 90));
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
					'LID' => $this->getLanguageId(),
					'NAME' => $fields['NAME']
				]);
			}
		}
		if($result->isSuccess())
		{
			$item = Kanban\Entity::getInstance($this->entity->getTypeName())
				->createPullStage($fields);
			PullManager::getInstance()->sendStageAddedEvent(
				$item,
				[
					'TYPE' => $this->entity->getTypeName(),
					'CATEGORY_ID' => $this->arParams['EXTRA']['CATEGORY_ID']
				]
			);
		}
		return $result;
	}

	/**
	 * Remove all private cards, for setting common.
	 * @return void
	 */
	protected function setCommonCardFields(): void
	{
		if ($this->canEditSettings())
		{
			$this->entity->removeUserAdditionalSelectFields();
		}
	}

	/**
	 * Reset fields in card.
	 * @return void
	 */
	protected function resetCardFields(): void
	{
		$this->additionalSelect = $this->entity->resetAdditionalSelectFields($this->canEditSettings());
		$this->arResult['MORE_FIELDS'] = $this->getAdditionalFields(true);
	}

	/**
	 * Save additional fields for card.
	 * @return array
	 */
	protected function actionSaveFields(): array
	{
		$type = (string) $this->request('type');
		$fields = $this->convertUtf($this->request('fields'), true);

		return $this->entity->saveAdditionalFields($fields, $type, $this->canEditSettings());
	}

	/**
	 * Delete entity.
	 * @param int|array $ids Optionally id for delete.
	 * @return array
	 */
	protected function actionDelete($ids = null): array
	{
		$ids = $ids ?: $this->request('id');
		$ids = (array)$ids;

		if(empty($ids))
		{
			return [];
		}

		$ignore = ($this->request('ignore') === 'Y');
		$userPerms = $this->getCurrentUserPermissions();

		try
		{
			$this->entity->deleteItems($ids, $ignore, $userPerms);
		}
		catch (Exception $exception)
		{
			return ['error' => $exception->getMessage()];
		}

		return [];
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
		$userPerms = $this->getCurrentUserPermissions();
		$request = Application::getInstance()->getContext()->getRequest();

		$result = new Result();

		if (!\CCrmPerms::IsAuthorized())
		{
			return $result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED')));
		}

		$item = $this->entity->getItem($id);
		if(!$item)
		{
			return $result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED')));
		}

		if(!$this->entity->checkUpdatePermissions($id, $userPerms))
		{
			return $result->addError(new Error(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED')));
		}

		Kanban\SortTable::setPrevious([
			'ENTITY_TYPE_ID' => $this->entity->getTypeName(),
			'ENTITY_ID' => $id,
			'PREV_ENTITY_ID' => $request->get('prev_entity_id')
		]);

		// remember last id
		$this->rememberLastId($this->getLastId());

		$statusKey = $this->statusKey;
		$isStatusChanged = $item[$statusKey] !== $status;
		if(!$isStatusChanged)
		{
			return $result;
		}

		$ajaxParamsName = ((int) $request->getPost('version') === 2) ? 'ajaxParams' : 'status_params';
		$newStateParams = (array)$request->getPost($ajaxParamsName);
		//add one more item for old column
		$isStatusChanged = $item[$statusKey] !== $status;
		if ($isStatusChanged && isset($newStateParams['old_status_lastid']))
		{
			$oneMore = $this->getItems(array(
				$statusKey => $item[$statusKey],
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


		$result = $this->entity->updateItemStage($id, $status, $this->convertUtf($newStateParams, true), $statuses);
		if(!$result->isSuccess())
		{
			return $result;
		}

		$this->getColumns(true);

		return $result;
	}

	/**
	 * Refresh deals accounts.
	 * @return array
	 */
	protected function actionRefreshAccount(): array
	{
		$ids = $this->request('id');
		$ids = (array)$ids;
		if(empty($ids) || $this->entity->getTypeName() !== 'DEAL')
		{
			return [];
		}
		$idForUpdate = [];
		$userPermissions = $this->getCurrentUserPermissions();
		foreach ($ids as $id)
		{
			$categoryId = $this->entity->getCategoryId();
			if (\CCrmDeal::checkUpdatePermission($id, $userPermissions, $categoryId))
			{
				$idForUpdate[] = $id;
			}
		}
		if (!empty($idForUpdate))
		{
			\CCrmDeal::refreshAccountingData($idForUpdate);
		}

		return [];
	}

	/**
	 * Set assigned id.
	 * @return array
	 */
	protected function actionSetAssigned(): array
	{
		$ids = (array)$this->request('ids');
		$assignedId = (int)$this->request('assignedId');
		if($assignedId <= 0 || empty($ids))
		{
			return [];
		}
		$userPerms = $this->getCurrentUserPermissions();

		$this->entity->setItemsAssigned($ids, $assignedId, $userPerms);

		return [];
	}

	/**
	 * Make open / close.
	 * @return array
	 */
	protected function actionOpen()
	{
		$ids = (array)$this->request('id');
		if(empty($ids))
		{
			return [];
		}
		$isOpened = ($this->request('flag') === 'Y');

		$this->entity->updateItemsOpened($ids, $isOpened);

		return [];
	}

	/**
	 * Change category of deals.
	 * @return array
	 */
	protected function actionChangeCategory()
	{
		$ids = (array)$this->request('id');
		if(empty($ids))
		{
			return [];
		}
		$categoryId = (int)$this->request('category');
		$userPermissions = $this->getCurrentUserPermissions();
		$this->entity->updateItemsCategory($ids, $categoryId, $userPermissions);

		return [];
	}

	/**
	 * Show or hide contact center block in option.
	 * @return array
	 */
	protected function actionToggleCC(): array
	{
		$hidden = \CUserOptions::getOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME_HIDE_CONTACT_CENTER,
			false
		);
		\CUserOptions::setOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME_HIDE_CONTACT_CENTER,
			!$hidden
		);
		return [];
	}

	/**
	 * Show or hide REST demo block in option.
	 * @return array
	 */
	protected function actionToggleRest(): array
	{
		$hidden = \CUserOptions::getOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME_HIDE_REST_DEMO,
			false
		);
		\CUserOptions::setOption(
			static::OPTION_CATEGORY,
			static::OPTION_NAME_HIDE_REST_DEMO,
			!$hidden
		);
		return [];
	}

	protected function processAvatar(array $user): array
	{
		$avatar = null;
		if ($user['PERSONAL_PHOTO'])
		{
			$avatar = \CFile::ResizeImageGet(
				$user['PERSONAL_PHOTO'],
				$this->avatarSize,
				BX_RESIZE_IMAGE_EXACT
			);
			if ($avatar)
			{
				$user['PERSONAL_PHOTO'] = $avatar['src'];
			}
		}

		return $user;
	}

	/**
	 * @param int[][] $crmEntities entityAbbreviation => [array of entityId]
	 *
	 * @return string[][] entityAbbreviation => [array of formatted html strings]
	 */
	protected function getLinksToCrmEntities(array $crmEntities): array
	{
		$result = [];

		$typesMap = \Bitrix\Crm\Service\Container::getInstance()->getTypesMap();
		$router = \Bitrix\Crm\Service\Container::getInstance()->getRouter();

		foreach ($crmEntities as $entityAbbreviation => $entityIds)
		{
			$entityTypeId = \CCrmOwnerTypeAbbr::ResolveTypeID($entityAbbreviation);

			$factory = $typesMap->getFactory($entityTypeId);
			// for example, $entityName = 'Deal';
			$entityName = mb_convert_case(\CCrmOwnerType::ResolveName($entityTypeId), MB_CASE_TITLE);
			$dataManager = $factory ? $factory->getDataClass() : ('\Bitrix\Crm\\' . $entityName . 'Table');

			if (!class_exists($dataManager) || !is_a($dataManager, DataManager::class, true))
			{
				continue;
			}

			$select = ['ID', 'TITLE'];
			if ($entityTypeId === \CCrmOwnerType::Contact)
			{
				$select = ['ID', 'NAME', 'LAST_NAME'];
			}

			/** @var array[] $rows */
			$rows = $dataManager::getList([
				'select' => $select,
				'filter' => [
					'@ID' => $entityIds,
				],
			])->fetchAll();

			foreach ($rows as $row)
			{
				$id = (int)$row['ID'];
				unset($row['ID']);

				// implode because in case of contact there are 2 strings in the array
				$title = implode(' ', $row);

				if (empty($title) && $factory)
				{
					$title = htmlspecialcharsbx($factory->getEntityDescription() . ' #' . $id);
				}

				$detailsUrl = $router->getItemDetailUrl($entityTypeId, $id);

				$result[$entityAbbreviation][$id] =
					'<a href="' . $detailsUrl . '">' . htmlspecialcharsbx($title) . '</a>'
				;
			}
		}

		return $result;
	}
}
