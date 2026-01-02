<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Activity\LastCommunication\LastCommunicationTimeFormatter;
use Bitrix\Crm\Activity\ToDo\CalendarSettings\CalendarSettingsProvider;
use Bitrix\Crm\Activity\ToDo\ColorSettings\ColorSettingsProvider;
use Bitrix\Crm\Color\PhaseColorScheme;
use Bitrix\Crm\Filter\FieldsTransform\UserBasedField;
use Bitrix\Crm\Format\PersonNameFormatter;
use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Kanban\Entity\Dynamic;
use Bitrix\Crm\Kanban\EntityNotFoundException;
use Bitrix\Crm\Kanban\Helper\FieldsPreparer;
use Bitrix\Crm\Kanban\Sort;
use Bitrix\Crm\Kanban\ViewMode;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Display\Field\BooleanField;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\Settings\CounterSettings;
use Bitrix\Crm\UI\Filter\EntityHandler;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserTable;
use Bitrix\Rest\Marketplace\Url;
use CCrmEntityHelper;

abstract class Kanban
{
	public const BLOCK_SIZE = 20;

	protected const OPTION_CATEGORY = 'crm';
	protected const COLUMN_NAME_DELETED = 'DELETED';
	protected const MAX_SORTED_ITEMS_COUNT = 1000;
	protected const REST_CONFIGURATION_PLACEMENT_URL_CONTEST = 'crm_kanban';
	protected const OPTION_NAME_HIDE_REST_DEMO = 'kanban_rest_hide';

	protected static array $instances = [];

	protected $entity;
	protected $entityType;
	protected $currency;
	protected $statusKey;
	protected $nameTemplate;
	protected $fieldSum;
	protected string $viewMode;

	protected array $params = [];
	protected array $semanticIds = [];
	protected array $allowSemantics = [];
	protected array $allowStages = [];
	protected array $additionalSelect = [];
	protected array $additionalEdit = [];
	protected array $requiredFields = [];

	protected int $blockPage = 1;
	protected int $currentUserId = 0;

	protected string $fieldsContext = Service\Display\Field::KANBAN_CONTEXT;

	protected array $exclusiveFieldsReturnCustomer = [
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
		'IM' => true,
	];
	protected array $disableMoreFields = [
		'ACTIVE_TIME_PERIOD', 'PRODUCT_ROW_PRODUCT_ID', 'COMPANY_ID',
		'CONTACT_ID', 'EVENT_ID', 'EVENT_DATE', 'ACTIVITY_COUNTER',
		'IS_RETURN_CUSTOMER', 'IS_NEW', 'IS_REPEATED_APPROACH', 'CURRENCY_ID', 'WEBFORM_ID',
		'COMMUNICATION_TYPE', 'HAS_PHONE', 'HAS_EMAIL', 'STAGE_SEMANTIC_ID', 'CATEGORY_ID',
		'STATUS_ID', 'STATUS_SEMANTIC_ID', 'STATUS_CONVERTED', 'MODIFY_BY_ID', 'TRACKING_CHANNEL_CODE',
		'ADDRESS', 'ADDRESS_2', 'ADDRESS_CITY', 'ADDRESS_REGION', 'ADDRESS_PROVINCE',
		'ADDRESS', 'ADDRESS_POSTAL_CODE', 'ADDRESS_COUNTRY', 'CREATED_BY_ID', 'ORIGINATOR_ID', 'ORIGINATOR_ID',
		'UTM_SOURCE', 'UTM_MEDIUM', 'UTM_CAMPAIGN', 'UTM_CONTENT', 'UTM_TERM',
		'STAGE_ID_FROM_HISTORY', 'STAGE_ID_FROM_SUPPOSED_HISTORY', 'STAGE_SEMANTIC_ID_FROM_HISTORY',
		'ACTIVITY_RESPONSIBLE_IDS', 'LAST_ACTIVITY_TIME',
	];

	protected ?array $order = null;
	protected FieldsPreparer $fieldsPreparer;

	public static function getInstance(string $entityType, array $params = []): self
	{
		$entityTypeName = $entityType;
		if (isset($params['CATEGORY_ID']) && $params['CATEGORY_ID'] > 0)
		{
			$entityTypeName = $entityType . '-' . (int)$params['CATEGORY_ID'];
		}

		if (!isset(self::$instances[$entityTypeName]))
		{
			self::$instances[$entityTypeName] = new static($entityType, $params);
		}

		return self::$instances[$entityTypeName];
	}

	/**
	 * @throws EntityNotFoundException
	 */
	protected function __construct(string $entityType, array $params = [])
	{
		Loc::loadLanguageFile(__FILE__);

		$this->fieldsPreparer = new FieldsPreparer();

		$this->entityType = $entityType;
		$this->viewMode = $params['VIEW_MODE'] ?? ViewMode::MODE_STAGES;

		$type = mb_strtoupper($this->entityType);
		$this->entity = Entity::getInstance($type, $this->viewMode);

		if (!$this->entity)
		{
			throw new EntityNotFoundException('Entity not found by type: ' . $entityType);
		}

		$this->params = $params;

		$categoryId = (isset($this->params['CATEGORY_ID']) ? (int)$this->params['CATEGORY_ID'] : null);
		$this->setCategoryId($categoryId);
		$this->setNameTemplate($this->params['NAME_TEMPLATE'] ?? null);

		$customSectionCode = $this->params['CUSTOM_SECTION_CODE'] ?? null;
		if (!is_null($customSectionCode) && $this->entity->isCustomSectionSupported())
		{
			$this->entity->setCustomSectionCode($customSectionCode);
		}

		$this->entity->setCanEditCommonSettings($this->canEditSettings());

		$this->currency = $this->entity->getCurrency();
		$this->statusKey = $this->entity->getStageFieldName();

		//additional select-edit fields
		$this->additionalSelect = $this->entity->getAdditionalSelectFields();
		$this->additionalEdit = $this->entity->getAdditionalEditFields();

		$this->currentUserId = Container::getInstance()->getContext()->getUserId();

		//redefine price-field
		if ($this->entity->isCustomPriceFieldsSupported())
		{
			$this->fieldSum = ($this->entity->getCustomPriceFieldName() ?? '');
		}

		if (!isset($this->params['ONLY_COLUMNS']) || $this->params['ONLY_COLUMNS'] !== 'Y')
		{
			$this->requiredFields = $this->entity->getRequiredFieldsByStages($this->getStatuses());
		}
	}

	public function getApiVersion(): int
	{
		return $this->params['API_VERSION'] ?? 1;
	}

	/**
	 * @param int|null $categoryId
	 */
	protected function setCategoryId(?int $categoryId): void
	{
		if (!$this->entity->isCategoriesSupported())
		{
			return;
		}

		if ($categoryId === -1 && !$this->entity->canUseAllCategories())
		{
			$categoryId = 0;
		}

		if ($categoryId >= -1)
		{
			$this->entity->setCategoryId($categoryId);
		}
	}

	protected function setNameTemplate(?string $nameTemplate): void
	{
		$this->nameTemplate = (
			isset($nameTemplate)
				? str_replace(['#NOBR#', '#/NOBR#'], ['', ''], trim($nameTemplate))
				: PersonNameFormatter::getFormat()
		);
	}

	/**
	 * @return string
	 */
	protected function getNameTemplate(): string
	{
		return $this->nameTemplate;
	}

	public function canEditSettings(): bool
	{
		return $GLOBALS['USER']->canDoOperation('edit_other_settings');
	}

	public function getComponentParams(): array
	{
		$isOnlyItems = $this->isOnlyItems();

		$params = [
			'ENTITY_TYPE_CHR' => $this->entity->getTypeName(),
			'ENTITY_TYPE_INT' => $this->entity->getTypeId(),
			'ENTITY_TYPE_INFO' => $this->entity->getTypeInfo(),
			'IS_DYNAMIC_ENTITY' => \CCrmOwnerType::isPossibleDynamicTypeId($this->entity->getTypeId()),
			'ENTITY_PATH' => $this->getEntityPath($this->entity->getTypeName()),
			'EDITOR_CONFIG_ID' => $this->entity->getEditorConfigId(),

			'HIDE_REST' => true,
			'REST_DEMO_URL' => '',

			'ITEMS' => [],
			'ADMINS' => $this->getAdmins(),
			'MORE_FIELDS' => ($isOnlyItems ? [] : $this->getAdditionalFields()),
			'MORE_EDIT_FIELDS' => ($isOnlyItems ? [] : $this->getAdditionalEditFields()),
			'CATEGORIES' => [],

			'CURRENT_USER_ID' => $this->currentUserId,
			'CURRENCY' => $this->currency,
			'STATUS_KEY' => $this->entity->getStageFieldName(),
		];

		if (
			$this->entity->isRestPlacementSupported()
			&& Loader::includeModule('rest')
			&& is_callable('\Bitrix\Rest\Marketplace\Url::getConfigurationPlacementUrl')
		)
		{
			$params['HIDE_REST'] = \CUserOptions::getOption(
				static::OPTION_CATEGORY,
				static::OPTION_NAME_HIDE_REST_DEMO,
				false
			);
			$params['REST_DEMO_URL'] = Url::getConfigurationPlacementUrl(
				$this->entity->getConfigurationPlacementUrlCode(),
				static::REST_CONFIGURATION_PLACEMENT_URL_CONTEST
			);
		}

		$this->prepareComponentParams($params);

		return $params;
	}

	protected function isOnlyItems(): bool
	{
		return (isset($this->params['ONLY_ITEMS']) && $this->params['ONLY_ITEMS'] === 'Y');
	}

	protected function prepareComponentParams(array &$params): void
	{
		// may be implement in child class
	}

	protected function getAdmins(): array
	{
		$users = [];

		$userQuery = new Query(UserTable::getEntity());

		$userQuery->setSelect([
			'ID',
			'LOGIN',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'PERSONAL_PHOTO',
		]);

		// set runtime for inner group ID=1 (admins)
		$userQuery->registerRuntimeField(
			null,
			new ReferenceField(
				'UG',
				UserGroupTable::getEntity(),
				[
					'=this.ID' => 'ref.USER_ID',
					'=ref.GROUP_ID' => new SqlExpression(1),
				],
				[
					'join_type' => 'INNER',
				]
			)
		);

		$date = new DateTime;
		$userQuery->setFilter([
			'=ACTIVE' => 'Y',
			'!ID' => $this->currentUserId,
			[
				'LOGIC' => 'OR',
				'<=UG.DATE_ACTIVE_FROM' => $date,
				'UG.DATE_ACTIVE_FROM' => false,
			],
			[
				'LOGIC' => 'OR',
				'>=UG.DATE_ACTIVE_TO' => $date,
				'UG.DATE_ACTIVE_TO' => false,
			],
		]);

		$res = $userQuery->exec();

		while ($row = $res->fetch())
		{
			$row = $this->processAvatar($row);
			$users[$row['ID']] = [
				'id' => $row['ID'],
				'name' => \CUser::FormatName($this->getNameTemplate(), $row, true, false),
				'img' => $row['PERSONAL_PHOTO'],
			];
		}

		return $users;
	}

	protected function processAvatar(array $user): array
	{
		$avatar = null;
		if ($user['PERSONAL_PHOTO'])
		{
			$avatar = \CFile::ResizeImageGet(
				$user['PERSONAL_PHOTO'],
				$this->getAvatarSize(),
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
	 * Get additional fields for quick form.
	 */
	protected function getAdditionalEditFields(): array
	{
		return $this->additionalEdit;
	}

	public function getColumns(bool $clear = false, bool $withoutCache = false, array $params = []): array
	{
		static $columns = [];

		if ($withoutCache)
		{
			$clear = $withoutCache;
		}

		if (($params['ONLY_ITEMS'] ?? 'N') === 'Y')
		{
			return $columns;
		}

		if ($clear)
		{
			$columns = [];
		}

		$params['originalColumns'] = ($params['originalColumns'] ?? false);

		$filter = ($params['filter'] ?? []);
		unset($params['filter']);

		if(empty($columns))
		{
			$runtime = [];
			$baseCurrency = $this->currency;
			if ($this->entity->getTypeName() === \CCrmOwnerType::OrderName)
			{
				$filterCommon = $this->getOrderFilter($runtime);
				if (isset($filterCommon[$this->getStatusKey()]))
				{
					$this->allowStages = $filterCommon[$this->getStatusKey()];
				}
			}
			else
			{
				$filterCommon = $this->getFilter($params);
			}

			$filter = array_merge($filterCommon, $filter);

			$viewMode = ($params['VIEW_MODE'] ?? ViewMode::MODE_STAGES);
			if ($viewMode === ViewMode::MODE_ACTIVITIES && ($filter['CATEGORY_ID'] ?? null) === -1)
			{
				unset($filter['CATEGORY_ID']);
			}

			$sort = 0;
			$winColumn = [];

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
				if ($isFirstDropZone && !$params['originalColumns'])
				{
					$isFirstDropZone = false;
					$columns[static::COLUMN_NAME_DELETED] = $this->getDeleteColumn([
						'real_sort' => $status['SORT'] ?? null,
						'sort' => $sort
					]);
				}

				$column = [
					'real_id' => $status['ID'] ?? null,
					'real_sort' => $status['SORT'] ?? null,
					'id' => $status['STATUS_ID'] ?? null,
					'name' => $status['NAME'] ?? null,
					'color' => $this->getColumnColor($status),
					'type' => $status['PROGRESS_TYPE'] ?? null,
					'sort' => $sort,
					'count' => 0,
					'total' => 0,
					'currency' => $baseCurrency,
					'dropzone' => $isDropZone,
					'alwaysShowInDropzone' => $this->isAlwaysShowInDropzone($status),
					'canAddItem' => $this->entity->canAddItemToStage($status['STATUS_ID'], $status['SEMANTICS']),
					'blockedIncomingMoving' => ($status['BLOCKED_INCOMING_MOVING'] ?? false),
				];

				$column = array_merge($column, $this->getAdditionalColumnParams());

				// win column
				if (!$params['originalColumns'] && $status['PROGRESS_TYPE'] === 'WIN')
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

			// Pass semantic param to the deadlines view for correct filter works
			if (
				$this->viewMode === ViewMode::MODE_DEADLINES
				&& !empty($this->allowSemantics)
				&& is_array($this->allowSemantics)
				&& $this->entity instanceof Dynamic
			)
			{
				$filter['STAGE_SEMANTIC_ID'] = $this->allowSemantics;
			}

			//get sums and counts
			$this->entity->fillStageTotalSums($filter, $runtime, $columns);
		}

		if ($withoutCache)
		{
			$tmpColumns = $columns;
			$columns = [];

			return $tmpColumns;
		}

		return $columns;
	}

	protected function getAdditionalColumnParams(): array
	{
		return [];
	}

	protected function getOrderFilter(array &$runtime): array
	{
		$grid = $this->entity->getFilterOptions();
		$gridFilter = $this->entity->getGridFilter();
		$search = $grid->GetFilter($gridFilter);
		EntityHandler::internalize($gridFilter, $search);

		$filterFields = [];
		$componentName = 'bitrix:crm.order.list';
		$className = \CBitrixComponent::includeComponentClass($componentName);

		/** @var \CCrmOrderListComponent $crmCmp */
		$crmCmp = new $className;
		$crmCmp->initComponent($componentName);
		if($crmCmp->init())
		{
			$filterFields = $crmCmp->createGlFilter($search, $runtime);
		}

		if(!empty($filterFields['DELIVERY_SERVICE']))
		{
			$services = (
			is_array($filterFields['DELIVERY_SERVICE'])
				? $filterFields['DELIVERY_SERVICE']
				: [$filterFields['DELIVERY_SERVICE']]
			);

			$whereExpression = '';
			foreach($services as $serviceId)
			{
				$serviceId = (int)$serviceId;
				if($serviceId <= 0)
				{
					continue;
				}
				$whereExpression .= (empty($whereExpression) ? '(' : ' OR ');
				$whereExpression .= "DELIVERY_ID = {$serviceId}";
			}

			if(!empty($whereExpression))
			{
				$whereExpression .= ')';
				$expression = "CASE WHEN EXISTS (SELECT ID FROM b_sale_order_delivery WHERE ORDER_ID = %s AND SYSTEM=\"N\" AND {$whereExpression}) THEN 1 ELSE 0 END";
				$runtime[] = new ExpressionField(
					'REQUIRED_DELIVERY_PRESENTED',
					$expression,
					['ID'],
					['date_type' => 'boolean']
				);
				$filterFields['=REQUIRED_DELIVERY_PRESENTED'] = 1;
				unset($filterFields['DELIVERY_SERVICE']);
			}
		}

		if(!empty($filterFields['PAY_SYSTEM']))
		{
			$paySystems = (
			is_array($filterFields['PAY_SYSTEM'])
				? $filterFields['PAY_SYSTEM']
				: [$filterFields['PAY_SYSTEM']]
			);

			$whereExpression = '';
			foreach($paySystems as $systemId)
			{
				$systemId = (int)$systemId;
				if($systemId <= 0)
				{
					continue;
				}
				$whereExpression .= (empty($whereExpression) ? '(' : ' OR ');
				$whereExpression .= "PAY_SYSTEM_ID = {$systemId}";
			}

			if(!empty($whereExpression))
			{
				$whereExpression .= ')';
				$expression = "CASE WHEN EXISTS (SELECT ID FROM b_sale_order_payment WHERE ORDER_ID = %s AND {$whereExpression}) THEN 1 ELSE 0 END";
				$runtime[] = new ExpressionField(
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
	 * Make filter from env.
	 * @param array $params
	 * @return array
	 */
	protected function getFilter(array $params = []): array
	{
		static $filter = null;
		$entity = $this->getEntity();

		if(isset($params['FORCE_FILTER']) && $params['FORCE_FILTER'] === 'Y')
		{
			$forceFilter = [];

			if ($entity->canUseAllCategories() && $entity->getCategoryId() === -1)
			{
				$forceFilter = [];
			}

			if ($entity->isCategoriesSupported())
			{
				$forceFilter = [
					'CATEGORY_ID' => $entity->getCategoryId(),
				];
			}

			$this->prepareSemanticIdsAndStages($forceFilter);

			return $forceFilter;
		}

		if($filter !== null)
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
		$filterUtm = ['UTM_SOURCE', 'UTM_MEDIUM', 'UTM_CAMPAIGN', 'UTM_CONTENT', 'UTM_TERM'];
		/**
		 * possible subtype values for example:
		 *   null | employee | string | url | address | money | integer | double | boolean | datetime |
		 *   date | enumeration | crm_status | iblock_element | iblock_section | crm
		 * may also take other value
		 */
		$filterSubtype = ['money'];
		//from main.filter
		$grid = $entity->getFilterOptions();

		$filterId = ($params['FILTER_PRESET_ID'] ?? null);
		if ($filterId)
		{
			$grid->setCurrentFilterPresetId($filterId);
		}
		$gridFilter = $entity->getGridFilter($filterId);
		$search = $grid->GetFilter($gridFilter);
		\Bitrix\Crm\UI\Filter\EntityHandler::internalize($gridFilter, $search);
		if(!isset($search['FILTER_APPLIED']))
		{
			$search = [];
		}
		if(!empty($search))
		{
			foreach($search as $key => $item)
			{
				unset($search[$key]);
				if(in_array(mb_substr($key, 0, 2), array('>=', '<=', '<>')))
				{
					$key = mb_substr($key, 2);
				}
				if(
					in_array(mb_substr($key, 0, 1), array('=', '<', '>', '@', '!'))
					&& !(mb_substr($key, 0, 1) === '!' && $item === false)
				)
				{
					$key = mb_substr($key, 1);
				}
				$search[$key] = $item;
			}
			foreach($gridFilter as $key => $item)
			{
				// skip ActivityFastSearch fields, it will be processed later.
				if (str_starts_with($key, 'ACTIVITY_FASTSEARCH_'))
				{
					continue;
				}

				//fill filter by type
				$fromFieldName = $key . '_from';
				$toFieldName = $key . '_to';
				if(isset($item['type']) && $item['type'] === 'date')
				{
					if(!empty($search[$fromFieldName]))
					{
						$filter['>=' . $key] = $search[$fromFieldName] . ' 00:00:00';
					}
					if(!empty($search[$toFieldName]))
					{
						$filter['<=' . $key] = $search[$toFieldName] . ' 23:59:00';
					}
					if(isset($search[$key]) && $search[$key] === false)
					{
						$filter[$key] = $search[$key];
					}
					elseif(isset($search['!' . $key]) && $search['!' . $key] === false)
					{
						$filter['!' . $key] = $search['!' . $key];
					}
				}
				elseif (isset($item['type']) && $item['type'] === 'number')
				{
					$fltType = $search[$key . '_numsel'] ?? 'exact';
					if (
						($fltType === 'exact' || $fltType === 'range')
						&& isset($search[$fromFieldName], $search[$toFieldName])
					)
					{
						$filter['>=' . $key] = $search[$fromFieldName];
						$filter['<=' . $key] = $search[$toFieldName];
					}
					elseif ($fltType === 'exact' && isset($search[$fromFieldName]))
					{
						$filter[$key] = $search[$fromFieldName];
					}
					elseif ($fltType === 'more' && isset($search[$fromFieldName]))
					{
						$filter['>' . $key] = $search[$fromFieldName];
					}
					elseif ($fltType === 'less' && isset($search[$toFieldName]))
					{
						$filter['<' . $key] = $search[$toFieldName];
					}
					elseif (isset($search[$key]) && $search[$key] === false)
					{
						$filter[$key] = $search[$key];
					}
					elseif (isset($search['!' . $key]) && $search['!' . $key] === false)
					{
						$filter['!' . $key] = $search['!' . $key];
					}
				}
				elseif(isset($search[$key]))
				{
					if((isset($search[$key]) && $search[$key] === false))
					{
						$filter[$key] = $search[$key];
					}
					elseif((isset($search['!' . $key]) && $search['!' . $key] === false))
					{
						$filter['!' . $key] = $search['!' . $key];
					}
					elseif(in_array($key, $filterLogic, true))
					{
						$filter['?' . $key] = $search[$key];
					}
					elseif(in_array($key, $filterAddress, true))
					{
						$filter['=%' . $key] = $search[$key] . '%';
					}
					elseif(in_array($key, array_merge($filterHistory, $filterUtm), true) ||
						(
							isset($item['subtype']) && in_array($item['subtype'], $filterSubtype, true)
						)
					)
					{
						$filter['%' . $key] = $search[$key];
					}
					elseif($key === 'STATUS_CONVERTED')
					{
						$filter[$key === 'N' ? 'STATUS_SEMANTIC_ID' : '!STATUS_SEMANTIC_ID'] = 'P';
					}
					elseif ($key === 'ORDER_TOPIC' ||
						(
							$entity instanceof \Bitrix\Crm\Kanban\Entity\Order
							&& $key === 'ACCOUNT_NUMBER'
						)
					)
					{
						$filter['~' . $key] = '%' . $search[$key] . '%';
					}
					elseif(
						$key === 'ENTITIES_LINKS'
						&& $entity->isEntitiesLinksInFilterSupported()
					)
					{
						$ownerData = explode('_', $search[$key]);
						if(count($ownerData) > 1)
						{
							$ownerTypeName = \CCrmOwnerType::resolveName(
								\CCrmOwnerType::resolveID($ownerData[0])
							);
							$ownerID = (int)$ownerData[1];
							if(!empty($ownerTypeName) && $ownerID > 0)
							{
								$filter[$entity->getFilterFieldNameByEntityTypeName($ownerTypeName)] = $ownerID;
							}
						}
					}
					elseif(ParentFieldManager::isParentFieldName($key))
					{
						$filter[$key] = ParentFieldManager::transformEncodedFilterValueIntoInteger(
							$key,
							$search[$key]
						);
					}
					else
					{
						$filter[$entity->prepareFilterField($key)] = $search[$key];
					}
				}
				elseif(isset($search['!' . $key]) && $search['!' . $key] === false)
				{
					$filter['!' . $key] = $search['!' . $key];
				}
				elseif(isset($item['alias'], $search[$item['alias']]))
				{
					$filter['=' . $key] = $search[$item['alias']];
				}
			}
			//search index
			$find = $search['FIND'] ? trim($search['FIND']) : null;
			if(!empty($find))
			{
				$search['FIND'] = $find;
				$filter['SEARCH_CONTENT'] = $search['FIND'];
			}
		}
		if(isset($filter['COMMUNICATION_TYPE']))
		{
			if(!is_array($filter['COMMUNICATION_TYPE']))
			{
				$filter['COMMUNICATION_TYPE'] = [$filter['COMMUNICATION_TYPE']];
			}
			if(in_array(\CCrmFieldMulti::PHONE, $filter['COMMUNICATION_TYPE']))
			{
				$filter['HAS_PHONE'] = 'Y';
			}
			if(in_array(\CCrmFieldMulti::EMAIL, $filter['COMMUNICATION_TYPE']))
			{
				$filter['HAS_EMAIL'] = 'Y';
			}
			unset($filter['COMMUNICATION_TYPE']);
		}
		//overdue
		if(
			isset($filter['OVERDUE'])
			&& ($entity->isOverdueFilterSupported())
		)
		{
			$key = $entity->getCloseDateFieldName();
			$date = new \Bitrix\Main\Type\Date;
			if($filter['OVERDUE'] === 'Y')
			{
				$filter['<' . $key] = $date;
				$filter['>' . $key] = \Bitrix\Main\Type\Date::createFromTimestamp(0);
			}
			else
			{
				$filter['>=' . $key] = $date;
			}
		}

		if ($this->viewMode === ViewMode::MODE_ACTIVITIES)
		{
			// The responsible field for activity view should not be altered at this time. It must be preserved for later use.
			$fieldToSkip = !CounterSettings::getInstance()->useActivityResponsible()
				? 'ACTIVITY_RESPONSIBLE_IDS'
				: 'ASSIGNED_BY_ID';
			$userFieldsToTransform = [$fieldToSkip];
		}
		else
		{
			$userFieldsToTransform = ['ASSIGNED_BY_ID', 'ACTIVITY_RESPONSIBLE_IDS'];
		}

		/** @var UserBasedField $userFieldPrepare */
		$userFieldPrepare = ServiceLocator::getInstance()->get('crm.filter.fieldsTransform.userBasedField');
		$userFieldPrepare->transformAll($filter, $userFieldsToTransform, $this->currentUserId);

		// add ACTIVITY_FASTSEARCH fields to filter set before subquery processing.
		foreach ($search as $key => $item)
		{
			if (str_starts_with($key, 'ACTIVITY_FASTSEARCH_'))
			{
				$filter[$key] = $item;
			}
		}

		$entity->prepareFilter($filter, $this->viewMode);
		$entity->applySubQueryBasedFilters($filter, $this->viewMode);

		$filter = Deal\OrderFilter::prepareFilter($filter);
		$filter = \Bitrix\Crm\Automation\Debugger\DebuggerFilter::prepareFilter($filter, $entity->getTypeId());

		if(
			($entity->isCategoriesSupported() && !$entity->canUseAllCategories())
			|| ($entity->canUseAllCategories() && $entity->getCategoryId() > -1)
		)
		{
			$filter['CATEGORY_ID'] = $this->entity->getCategoryId();
		}

		if ($entity->isCustomSectionSupported())
		{
			$customSectionCode = $entity->getCustomSectionCode();
			if (IntranetManager::isCustomSectionExists($customSectionCode))
			{
				$filter['@OWNER_TYPE_ID'] = IntranetManager::getEntityTypesInCustomSection($customSectionCode);
			}
			else
			{
				$allEntityTypesInSections = IntranetManager::getEntityTypesInCustomSections();
				if (!empty($allEntityTypesInSections))
				{
					$filter['!@OWNER_TYPE_ID'] = $allEntityTypesInSections;
				}
			}
		}

		//invoice, smart-invoice, etc.
		if($entity->isRecurringSupported())
		{
			$filter['=IS_RECURRING'] = 'N';
		}
		if(isset($filter['OVERDUE']))
		{
			unset($filter['OVERDUE']);
		}
		//detect success/fail columns
		$this->prepareSemanticIdsAndStages($filter);

		$entityTypeId = $entity->getTypeId();
		//region Apply Search Restrictions
		$searchRestriction = RestrictionManager::getSearchLimitRestriction();
		if(!$searchRestriction->isExceeded($entityTypeId))
		{
			$searchRestriction->notifyIfLimitAlmostExceed($entityTypeId);

			SearchEnvironment::convertEntityFilterValues(
				$entityTypeId,
				$filter
			);
		}
		//endregion

		CCrmEntityHelper::prepareMultiFieldFilter($filter, [], '=%', false);

		return $filter;
	}

	protected function getOrder(): array
	{
		if ($this->order === null)
		{
			$sortType = 'DESC';
			if ($this->getEntity()->getSortSettings()->getCurrentType() === Sort\Type::BY_LAST_ACTIVITY_TIME)
			{
				$this->order = [
					Item::FIELD_NAME_LAST_ACTIVITY_TIME => $sortType,
					Item::FIELD_NAME_ID => $sortType,
				];
			}
			else
			{
				$this->order = [
					Item::FIELD_NAME_ID => $sortType,
				];
			}
		}

		return $this->order;
	}

	/**
	 * @param $filter
	 */
	protected function prepareSemanticIdsAndStages(array $filter = []): void
	{
		$this->prepareSemanticIds($filter);
		$this->prepareAllowStages($filter);
		$this->prepareAllowSemantics();
	}

	/**
	 * @param array $filter
	 */
	protected function prepareSemanticIds(array $filter): void
	{
		$this->semanticIds = [];
		if(isset($filter['STATUS_SEMANTIC_ID']))
		{
			$this->semanticIds = (array)$filter['STATUS_SEMANTIC_ID'];
		}
		elseif(isset($filter['STAGE_SEMANTIC_ID']))
		{
			$this->semanticIds = (array)$filter['STAGE_SEMANTIC_ID'];
		}
		else
		{
			$this->semanticIds = $this->entity->getSemanticIds();
		}
	}

	/**
	 * @param array $filter
	 */
	protected function prepareAllowStages(array $filter): void
	{
		$this->allowStages = ($filter[$this->getStatusKey()] ?? $this->entity->getAllowStages($filter));
	}

	protected function prepareAllowSemantics(): void
	{
		if(in_array(PhaseSemantics::PROCESS, $this->semanticIds, true))
		{
			$this->allowSemantics[] = PhaseSemantics::PROCESS;
		}

		if(in_array(PhaseSemantics::SUCCESS, $this->semanticIds, true))
		{
			$this->allowSemantics[] = PhaseSemantics::SUCCESS;
		}

		if(in_array(PhaseSemantics::FAILURE, $this->semanticIds, true))
		{
			$this->allowSemantics[] = PhaseSemantics::FAILURE;
		}

		if(empty($this->allowSemantics))
		{
			$this->allowSemantics[] = PhaseSemantics::PROCESS;
		}
	}

	/**
	 * @deprecated
	 * @see \Bitrix\Crm\Kanban::getEntity()->getUserPermissions()
	 */
	public function getCurrentUserPermissions(): \CCrmPerms
	{
		static $userPerms = null;
		if($userPerms === null)
		{
			$userPerms = \CCrmPerms::getCurrentUserPermissions();
		}
		return $userPerms;
	}

	/**
	 * Get stages description for current entity.
	 *
	 * @param bool $isClear Clear static cache.
	 * @return array
	 */
	public function getStatuses(bool $isClear = false): array
	{
		static $statuses = null;

		if($isClear)
		{
			$statuses = null;
		}

		if($statuses !== null)
		{
			return $statuses;
		}

		$statuses = [];
		$allStatuses = $this->entity->getStagesList();

		foreach($allStatuses as $status)
		{
			$status['STATUS_ID'] = $this->sanitizeString((string)$status['STATUS_ID']);

			if (isset($status['SEMANTICS']) && $status['SEMANTICS'] === PhaseSemantics::SUCCESS)
			{
				$status['PROGRESS_TYPE'] = 'WIN';
			}
			elseif(isset($status['SEMANTICS']) && $status['SEMANTICS'] === PhaseSemantics::FAILURE)
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

		$statuses = PhaseColorScheme::fillDefaultColors($statuses);

		return $statuses;
	}

	/**
	 * @param array $status
	 * @return bool
	 */
	protected function isDropZone(array $status = []): bool
	{
		if (!isset($status['STATUS_ID']))
		{
			return false;
		}

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
				$status['PROGRESS_TYPE'] === 'WIN'
				&& !in_array(PhaseSemantics::SUCCESS, $this->allowSemantics, true)
			)
			|| (
				$status['PROGRESS_TYPE'] === 'LOOSE'
				&& !in_array(PhaseSemantics::FAILURE, $this->allowSemantics, true)
			)
			|| (
				$status['PROGRESS_TYPE'] !== 'WIN'
				&& $status['PROGRESS_TYPE'] !== 'LOOSE'
				&& !in_array(PhaseSemantics::PROCESS, $this->allowSemantics, true)
			)
		)
		{
			return true;
		}

		return false;
	}

	/**
	 * @param array $params
	 * @return array
	 */
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
			'alwaysShowInDropzone' => true,
		];
	}

	/**
	 * @param array $status
	 * @return string
	 */
	protected function getColumnColor(array $status): string
	{
		$color = ($status['COLOR'] ?? '');
		return (mb_strpos($color, '#') === 0 ? mb_substr($color, 1) : $color);
	}

	/**
	 * @param array $status
	 * @return bool
	 */
	protected function isAlwaysShowInDropzone(array $status): bool
	{
		return ($status['PROGRESS_TYPE'] === 'WIN' || $status['PROGRESS_TYPE'] === 'LOOSE');
	}

	protected function shouldShowField(\Bitrix\Crm\Service\Display\Field $displayField, $value): bool
	{
		if ($this->isMobileContext() && $displayField->getType() === BooleanField::TYPE)
		{
			return isset($value);
		}

		return !empty($value);
	}

	public function getItemsConfig(array $params = []): array
	{
		// may be implement in child class
		throw new NotSupportedException();
	}

	public function getItems(array $filter = [], int $blockPage = 1, array $params = []): array
	{
		$this->blockPage = $blockPage;

		static $path = null;
		static $currency = null;
		static $columns = null;

		$select = $this->getSelect();
		if ($this->requiredFields)
		{
			$select = array_merge($select, array_keys($this->requiredFields));
		}

		$type = $this->entity->getTypeName();
		$runtime = [];
		$filterCommon = ($type === \CCrmOwnerType::OrderName ? $this->getOrderFilter($runtime) : $this->getFilter($params['filter'] ?? []));

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
			$columns = $this->getColumns(false, false, $this->params);
		}

		$parameters = [
			'filter' => $filter,
			'select' => $select,
			'order' => $this->getOrder(),
			'limit' => static::BLOCK_SIZE,
			'offset' => static::BLOCK_SIZE * ($this->blockPage - 1),
		];

		if(!empty($runtime))
		{
			$parameters['runtime'] = $runtime;
		}

		// Pass semantic param to the deadlines view for correct filter works
		if (
			$this->viewMode === ViewMode::MODE_DEADLINES
			&& !empty($this->allowSemantics)
			&& is_array($this->allowSemantics)
			&& $this->entity instanceof Dynamic
		)
		{
			$parameters['filter']['STAGE_SEMANTIC_ID'] = $this->allowSemantics;
		}

		$res = $this->entity->getItems($parameters);

		$rows = [];
		while ($row = $res->fetch())
		{
			$row = $this->entity->prepareItemCommonFields($row);
			$rows[$row['ID']] = $row;
		}

		$addSelect = array_keys($this->additionalSelect);
		$rows = $this->entity->appendRelatedEntitiesValues($rows, $addSelect);

		$displayOptions = new \Bitrix\Crm\Service\Display\Options();
		if ($this->getApiVersion() > 1)
		{
			$displayOptions->useRawValue(true);
		}

		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getWebFormResultsRestriction();
		$restrictedItemIds = [];

		$restrictedValueClickCallback = null;
		if (!$restriction->hasPermission())
		{
			$itemIds = array_keys($rows);
			$restriction->prepareDisplayOptions($this->entity->getTypeId(), $itemIds, $displayOptions);
			$restrictedItemIds = $displayOptions->getRestrictedItemIds();
			$restrictedValueClickCallback = $restriction->prepareInfoHelperScript();
		}

		$displayedFields = $this->getDisplayedFieldsList();
		$display = new \Bitrix\Crm\Service\Display($this->entity->getTypeId(), $displayedFields, $displayOptions);
		$display->setItems($rows);
		$renderedRows = $display->getAllValues();

		$specialReqKeys = $this->getSpecialReqKeys();
		$result = [];

		$apiVersion = $this->getApiVersion();
		$entity = $this->getEntity();

		$lastActivityInfo = $entity->prepareMultipleItemsLastActivity($rows, $apiVersion >= 2);
		$pingSettingsInfo = ($apiVersion <= 1 ? $entity->prepareMultipleItemsPingSettings($entity->getTypeId()) : null);
		$colorSettings = ($apiVersion <= 1 ? (new ColorSettingsProvider())->fetchForJsComponent() : null);
		$calendarSettings = ($apiVersion <= 1 ? (new CalendarSettingsProvider())->fetchForJsComponent() : null);

		$activeAutomationDebugEntityIds = \CCrmBizProcHelper::getActiveDebugEntityIds($this->entity->getTypeId());

		$stageNames = array_keys($this->getStatuses());

		foreach ($rows as $rowId => $row)
		{
			if (is_array($renderedRows[$rowId]))
			{
				$row = $this->mergeItemFieldsValues($row, $renderedRows[$rowId]);
			}

			if (isset($row['CONTACT_ID']) && $row['CONTACT_ID'] > 0)
			{
				$row['CONTACT_TYPE'] = 'CRM_CONTACT';
			}

			if (isset($row['COMPANY_ID']) && $row['COMPANY_ID'] > 0)
			{
				$row['CONTACT_TYPE'] = 'CRM_COMPANY';
			}

			$returnCustomer = (isset($row['IS_RETURN_CUSTOMER']) && $row['IS_RETURN_CUSTOMER'] === 'Y');
			// collect required
			$required = [];
			$requiredFm = [];
			if ($this->requiredFields && $this->viewMode === ViewMode::MODE_STAGES)
			{
				// fm fields check later
				foreach ($this->getAllowedFmTypes() as $fm)
				{
					$fmUp = mb_strtoupper($fm);

					if ($apiVersion <= 1)
					{
						$requiredFm[$fmUp] = true;
					}
					$row[$fmUp] = '';
				}
				// check each key
				foreach ($row as $fieldName => $fieldValue)
				{
					if ($returnCustomer && isset($this->exclusiveFieldsReturnCustomer[$fieldName]))
					{
						continue;
					}

					if ($this->isRequiredFieldEmpty($fieldName, $fieldValue))
					{
						foreach ($this->requiredFields[$fieldName] as $stageId)
						{
							if (!isset($required[$stageId]))
							{
								$required[$stageId] = [];
							}

							$required[$stageId][] = $fieldName;
						}
					}
				}
				// special keys
				foreach ($specialReqKeys as $reqKeyOrig => $reqKey)
				{
					if(
						isset($this->requiredFields[$reqKey])
						&& $reqKey === 'CLIENT'
						&& (!empty($row['COMPANY_ID']) || !empty($row['CONTACT_ID']))
					)
					{
						continue;
					}

					if (
						isset($this->requiredFields[$reqKey])
						&& (!$row[$reqKeyOrig] || ($reqKeyOrig === 'OPPORTUNITY' && $row['OPPORTUNITY_ACCOUNT'] <= 0))
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
			$columnId = $this->sanitizeString((string)$row[$this->getStatusKey()]);
			$rowId = $row['ID'];
			$item = [
				'id' =>  $rowId,
				'name' => $this->sanitizeString($this->getItemTitle($row) ?: '#' . $rowId),
				'link' => $row['LINK'] ?? str_replace($this->getPathMarkers(), $rowId, $path),
				'columnId' => $columnId,
				'columnColor' => $columns[$columnId]['color'] ?? '',
				'price' => $row['PRICE'],
				'price_formatted' => $row['PRICE_FORMATTED'],
				'entity_price' => $row['OPPORTUNITY_VALUE'],
				'currency' => $row['CURRENCY_ID'] ?? null,
				'entity_currency' => $row['ENTITY_CURRENCY_ID'],
				'date' => $row['DATE_FORMATTED'],
				'dateCreate' => $row['DATE_CREATE'] ?? '',
				'contactId' => (!empty($row['CONTACT_ID']) ? (int)$row['CONTACT_ID'] : null),
				'companyId' => (!empty($row['COMPANY_ID']) ? (int)$row['COMPANY_ID'] : null),
				'contactType' => $row['CONTACT_TYPE'],
				'modifyById' => ($row['MODIFY_BY_ID'] ?? 0),
				'page' => $this->blockPage,
				'fields' => $this->getItemFields($row, $renderedRows),
				'return' => $returnCustomer,
				'returnApproach' => (isset($row['IS_REPEATED_APPROACH']) && $row['IS_REPEATED_APPROACH'] === 'Y'),
				'assignedBy' => $row['ASSIGNED_BY'],
				'required' => $this->getPreparedRequiredFields($required, $stageNames),
				'required_fm' => $requiredFm,
				'sort' => $entity->prepareItemSort($rows[$rowId]),
				'lastActivity' => $lastActivityInfo[$row['ID']] ?? null,
				'draggable' => (bool)($row['DRAGGABLE'] ?? true),
			];

			if ($apiVersion <= 1)
			{
				$item = array_merge(
					$item,
					[
						'activityStageId' => ($row['ACTIVITY_STAGE_ID'] ?? null),
						'activityIncomingTotal' => 0,
						'activityCounterTotal' => 0,
						'activityProgress' => 0,
						'activityTotal' => 0,
						'activitiesByUser' => [],
						'pingSettings' => isset($row['CATEGORY_ID']) ? $pingSettingsInfo[$row['CATEGORY_ID']] : null,
						'colorSettings' => $colorSettings,
						'calendarSettings' => $calendarSettings,
						'badges' => [],
					],
				);
			}
			else
			{
				if ($item['draggable'])
				{
					unset($item['draggable']);
				}

				if (empty($item['columnColor']))
				{
					unset($item['columnColor']);
				}

				if (empty($item['contactId']))
				{
					unset($item['contactId']);
				}

				if (empty($item['companyId']))
				{
					unset($item['companyId']);
				}

				if (empty($item['contactType']))
				{
					unset($item['contactType']);
				}
			}


			$result[$rowId] = $item;

			$result[$rowId] = array_merge($result[$rowId], $this->prepareAdditionalFields($row));
			$isRestricted = (!empty($restrictedItemIds) && in_array($row['ID'], $restrictedItemIds));
			if ($isRestricted)
			{
				$result[$row['ID']]['updateRestrictionCallback'] = $restriction->prepareInfoHelperScript();
			}

			if ($this->entity->hasClientFields())
			{
				foreach ($this->getClientFields() as $clientField)
				{
					if (isset($row[$clientField]) && $row[$clientField])
					{
						$result[$row['ID']][$clientField] = (
							$isRestricted
								? $displayOptions->getRestrictedValueHtmlReplacer()
								: $row[$clientField]
						);
					}
				}
			}

			$isAutomationDebugItem =
				!empty($activeAutomationDebugEntityIds) && in_array($row['ID'], $activeAutomationDebugEntityIds)
			;
			$result[$row['ID']]['isAutomationDebugItem'] = $isAutomationDebugItem;
		}

		if ($entity->getSortSettings()->isUserSortSupported())
		{
			$result = $this->sort($result);
		}

		$this->entity->appendAdditionalData($result);

		return [
			'ITEMS' => $result,
			'RESTRICTED_VALUE_CLICK_CALLBACK' => $restrictedValueClickCallback,
		];
	}

	protected function getItemFields(array $row, array $renderedRows): array
	{
		$fields = [];
		$displayedFields = $this->getDisplayedFieldsList();
		$inlineFieldTypes = $this->getInlineFieldTypes();

		$addSelect = array_keys($this->additionalSelect);
		foreach ($addSelect as $code)
		{
			if (!array_key_exists($code, $row) || !array_key_exists($code, $displayedFields))
			{
				continue;
			}

			$displayedField = $displayedFields[$code];
			if ($this->shouldShowField($displayedField, $row[$code]) && !$this->skipClientField($row, $code))
			{
				$field = [
					'code' => $code,
					'title' => $this->sanitizeString($displayedField->getTitle()),
					'type' => $displayedField->getType(),
					'valueDelimiter' => in_array($displayedField->getType(), $inlineFieldTypes, true) ? ', ' : '<br>',
					'icon' => $displayedField->getDisplayParam('icon'),
					'html' => $displayedField->wasRenderedAsHtml(),
					'isMultiple' => $displayedField->isMultiple(),
				];

				$this->prepareField($field, $row[$code], $renderedRows[$row['ID']]);

				if ($this->getApiVersion() <= 1)
				{
					$fields[] = $field;
				}
				else
				{
					$data = [
						'code' => $field['code'],
						'value' => $field['value'] ?? null,
						'html' => $field['html'] ?? null,
					];

					if (isset($field['config']))
					{
						$data['config'] = $field['config'];
					}

					$fields[] = $data;
				}
			}
		}

		return $fields;
	}

	protected function getPreparedRequiredFields(array $required, array $allStages): array
	{
		if ($this->getApiVersion() <= 1)
		{
			return $required;
		}

		return $this->fieldsPreparer->getPreparedRequiredFields($required, $allStages);
	}

	protected function skipClientField(array $row, string $code): bool
	{
		return $this->entity->skipClientField($row, $code);
	}

	protected function isRequiredFieldEmpty(string $fieldName, mixed $fieldValue): bool
	{
		if (!isset($this->requiredFields[$fieldName]))
		{
			return false;
		}

		$field = $this->entity->getField($fieldName);
		if ($field === null)
		{
			return
				!$fieldValue
				&& $fieldValue !== '0'
				&& $fieldValue !== 0
				&& $fieldValue !== 0.0
			;
		}

		return $field->isValueEmpty($fieldValue);
	}

	protected function prepareAdditionalFields(array $item): array
	{
		return [];
	}

	/**
	 * @param array $row
	 * @param array $displayedFields
	 * @return array
	 */
	protected function mergeItemFieldsValues(array $row, array $displayedFields = []): array
	{
		return array_merge($row, $displayedFields);
	}

	/**
	 * @param array $data
	 * @param $value
	 * @param array|null $displayedFieldsValues
	 */
	protected function prepareField(array &$data, $value, ?array $displayedFieldsValues = []): void
	{
		$data['value'] = $value;

		(new LastCommunicationTimeFormatter())->formatKanbanDate($data);
	}

	/**
	 * @param array $row
	 * @return string|null
	 */
	protected function getItemTitle(array $row): ?string
	{
		return ($row['TITLE'] ?? null);
	}

	/**
	 * @param string $context
	 * @return $this
	 */
	public function setFieldsContext(string $context): self
	{
		$this->fieldsContext = $context;
		return $this;
	}

	/**
	 * @return string
	 */
	protected function getFieldsContext(): string
	{
		return $this->fieldsContext;
	}

	final protected function isMobileContext(): bool
	{
		return $this->getFieldsContext() === \Bitrix\Crm\Service\Display\Field::MOBILE_CONTEXT;
	}

	public function setContactEntityDataProvider(Component\EntityList\ClientDataProvider $provider): self
	{
		$this->getEntity()->setContactDataProvider($provider);

		return $this;
	}

	public function setCompanyEntityDataProvider(Component\EntityList\ClientDataProvider $provider): self
	{
		$this->getEntity()->setCompanyDataProvider($provider);

		return $this;
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
	 * Get path for entity from params or module settings.
	 * @param string $type
	 * @param array $params
	 * @return string
	 */
	protected function getEntityPath(string $type, array $params = []): ?string
	{
		$pathKey = 'PATH_TO_' . $type . '_DETAILS';
		$url = (
			!array_key_exists($pathKey, $params)
				? \CrmCheckPath($pathKey, '', '')
				: $params[$pathKey]
		);

		if ($url === '' || !\CCrmOwnerType::IsSliderEnabled(\CCrmOwnerType::ResolveID($type)))
		{
			$pathKey = 'PATH_TO_' . $type . '_SHOW';
			$url = !array_key_exists($pathKey, $params) ? \CrmCheckPath($pathKey, '', '') : $params[$pathKey];
		}

		if (!$url)
		{
			$url = $this->entity->getUrlTemplate();
		}

		return $url;
	}

	/**
	 * @param bool $clearCache
	 * @return \Bitrix\Crm\Service\Display\Field[]
	 */
	protected function getDisplayedFieldsList($clearCache = false): array
	{
		return $this->entity->getDisplayedFieldsList($clearCache, $this->getFieldsContext());
	}

	/**
	 * @return string[]
	 */
	protected function getSpecialReqKeys(): array
	{
		return [
			'OPPORTUNITY' => 'OPPORTUNITY_WITH_CURRENCY',
			'CONTACT_ID' => 'CLIENT',
			'COMPANY_ID' => 'CLIENT',
			'STORAGE_ELEMENT_IDS' => 'FILES',
		];
	}

	/**
	 * @return string[]
	 */
	protected function getInlineFieldTypes(): array
	{
		return [
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
	}

	/**
	 * @param array $fields
	 * @param int $id
	 */
	protected function addParentFields(array &$fields, int $id): void
	{
		if (isset($this->parents[$id]))
		{
			foreach ($this->parents[$id] as $parentTypeId => $parent)
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

	/**
	 * @return string[]
	 */
	protected function getClientFields(): array
	{
		return [
			'contactName',
			'contactTooltip',
			'companyName',
			'companyTooltip',
		];
	}

	/**
	 * Sort items by user order.
	 * @param array $items Items for sort.
	 * @return array
	 */
	protected function sort(array $items): array
	{
		static $lastIdRemember = null;

		if ($lastIdRemember === null)
		{
			$lastIdRemember = $this->rememberLastId();
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
				$prev = (int)$prev;

				if ($prev > 0 && !isset($items[$prev]))
				{
					continue;
				}
				if ($id === $prev)
				{
					continue;
				}

				$moveItem = $items[$id];
				unset($items[$id]);

				if ($prev === 0)
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
				if ($id > $lastIdRemember)
				{
					$moveItem = $items[$id];
					unset($items[$id]);
					$items = $this->arrayInsertAfter($items, null, $id, $moveItem);
					Kanban\SortTable::setPrevious([
						'ENTITY_TYPE_ID' => $this->entity->getTypeName(),
						'ENTITY_ID' => $id,
						'PREV_ENTITY_ID' => 0,
					]);
				}
			}
		}

		return $items;
	}

	/**
	 * Remember last id of entity for user.
	 * @param int|bool $lastId If set, save in config.
	 * @return void|int
	 */
	public function rememberLastId($lastId = false): ?int
	{
		$lastIds = \CUserOptions::getOption(static::OPTION_CATEGORY,'kanban_sort_last_id',	[]);
		$typeName = $this->entity->getTypeName();

		if ($lastId !== false)
		{
			$lastIds[$typeName] = $lastId;
			\CUserOptions::setOption(static::OPTION_CATEGORY, 'kanban_sort_last_id', $lastIds);
			return null;
		}

		return ($lastIds[$typeName] ?? 0);
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

		if (array_key_exists($afterKey, $array))
		{
			$newArray = [];
			foreach ($array as $k => $value)
			{
				$newArray[$k] = $value;
				// @todo == or maybe ===
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
	 * @return array
	 */
	public function resetCardFields(): array
	{
		$this->additionalSelect = $this->entity->resetAdditionalSelectFields($this->canEditSettings());
		return $this->getAdditionalFields(true);
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
				$fieldId = $field->getId();
				$additionalFields[$fieldId] = [
					'title' => HtmlFilter::encode($field->getTitle()),
					'type' => $field->getType(),
					'code' => $fieldId,
				];
			}
		}

		return $additionalFields;
	}

	/**
	 * Current user is admin?
	 * @return bool
	 */
	public function isCrmAdmin(): bool
	{
		$entity = $this->getEntity();

		return $entity->getUserPermissions()->isAdminForEntity($entity->getTypeId(), $entity->getCategoryId());
	}

	public function removeUserAdditionalSelectFields(): void
	{
		if ($this->canEditSettings())
		{
			$this->entity->removeUserAdditionalSelectFields();
		}
	}

	/**
	 * @return bool
	 */
	public function isSupported(): bool
	{
		return $this->entity->isKanbanSupported();
	}

	/**
	 * @return Entity
	 * @throws EntityNotFoundException
	 */
	public function getEntity(): Entity
	{
		if ($this->entity === null)
		{
			throw new EntityNotFoundException('Set Entity before use this method');
		}
		return $this->entity;
	}

	/**
	 * @return string[]
	 */
	protected function getPathMarkers(): array
	{
		return [
			'#lead_id#',
			'#contact_id#',
			'#company_id#',
			'#deal_id#',
			'#quote_id#',
			'#invoice_id#',
			'#order_id#',
		];
	}

	/**
	 * @return int[]
	 */
	protected function getAvatarSize(): array
	{
		return [
			'width' => 38,
			'height' => 38,
		];
	}

	/**
	 * @return string[]
	 */
	public function getAllowedFmTypes(): array
	{
		return [
			'phone',
			'email',
			'im',
			'web',
		];
	}

	/**
	 * @return string
	 */
	protected function getStatusKey(): string
	{
		return ($this->statusKey ?? $this->entity->getStageFieldName());
	}

	protected function sanitizeString(string $string): string
	{
		if ($this->isMobileContext())
		{
			return $string;
		}

		return htmlspecialcharsbx($string);
	}

	public function getDisabledMoreFields(): array
	{
		return $this->disableMoreFields;
	}

}
