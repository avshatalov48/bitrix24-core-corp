<?php


namespace Bitrix\Crm;


use Bitrix\Crm\Color\PhaseColorScheme;
use Bitrix\Crm\Format\PersonNameFormatter;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Kanban\EntityNotFoundException;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Search\SearchEnvironment;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Crm\UI\Filter\EntityHandler;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Rest\Marketplace\Url;

abstract class Kanban
{
	public const BLOCK_SIZE = 20;

	protected const OPTION_CATEGORY = 'crm';
	protected const COLUMN_NAME_DELETED = 'DELETED';
	protected const MAX_SORTED_ITEMS_COUNT = 1000;
	protected const REST_CONFIGURATION_PLACEMENT_URL_CONTEST = 'crm_kanban';
	protected const OPTION_NAME_HIDE_REST_DEMO = 'kanban_rest_hide';

	protected static $instances = [];

	protected $entity;
	protected $entityType;
	protected $currency;
	protected $statusKey;
	protected $nameTemplate;
	protected $fieldSum;

	protected $params = [];
	protected $semanticIds = [];
	protected $allowSemantics = [];
	protected $allowStages = [];
	protected $additionalSelect = [];
	protected $additionalEdit = [];
	protected $requiredFields = [];

	protected $blockPage = 1;
	protected $currentUserId = 0;

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

	/**
	 * @param string $entityType
	 * @param array $params
	 * @return static
	 */
	public static function getInstance(string $entityType, array $params = []): self
	{
		$entityTypeName = $entityType;
		if (isset($params['CATEGORY_ID']) && $params['CATEGORY_ID'] > 0)
		{
			$entityTypeName = $entityType . '-' . (int)$params['CATEGORY_ID'];
		}

		if(!isset(self::$instances[$entityTypeName]))
		{
			self::$instances[$entityTypeName] = new static($entityType, $params);
		}

		return self::$instances[$entityTypeName];
	}

	/**
	 * Kanban constructor.
	 * @param string $entityType
	 * @param array $params
	 * @throws EntityNotFoundException
	 */
	protected function __construct(string $entityType, array $params = [])
	{
		Loc::loadLanguageFile(__FILE__);
		$this->entityType = $entityType;

		$type = mb_strtoupper($this->entityType);
		$this->entity = Entity::getInstance($type);

		if(!$this->entity)
		{
			throw new EntityNotFoundException('Entity not found by type');
		}

		$this->params = $params;

		$this->setCategoryId($this->params['CATEGORY_ID'] ?? null);
		$this->setNameTemplate($this->params['NAME_TEMPLATE'] ?? null);

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

	/**
	 * @param int|null $categoryId
	 */
	protected function setCategoryId(?int $categoryId): void
	{
		if ($categoryId && $categoryId > 0 && $this->entity->isCategoriesSupported())
		{
			$this->entity->setCategoryId($categoryId);
		}
	}

	/**
	 * @param string|null $nameTemplate
	 */
	protected function setNameTemplate(?string $nameTemplate): void
	{
		if (!isset($nameTemplate))
		{
			$this->nameTemplate = PersonNameFormatter::getFormat();
		}
		else
		{
			$this->nameTemplate = str_replace(
				['#NOBR#', '#/NOBR#'],
				['', ''],
				trim($nameTemplate)
			);
		}
	}

	/**
	 * @return string
	 */
	protected function getNameTemplate(): string
	{
		return $this->nameTemplate;
	}

	/**
	 * Can current user edit settings or not.
	 * @return bool
	 */
	public function canEditSettings(): bool
	{
		return $GLOBALS['USER']->canDoOperation('edit_other_settings');
	}

	/**
	 * @return array
	 */
	public function getComponentParams(): array
	{
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
			'MORE_FIELDS' => (!$this->isOnlyItems() ? $this->getAdditionalFields() : []),
			'MORE_EDIT_FIELDS' => (!$this->isOnlyItems() ? $this->getAdditionalEditFields() : []),
			'FIELDS_DISABLED' => $this->disableMoreFields,
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

		if (!$this->isOnlyItems())
		{
			$inlineEditorParameters = $this->entity->getInlineEditorParameters();
			$params['FIELDS_SECTIONS'] = $inlineEditorParameters['fieldsSections'];

			if ($this->entity->isInlineEditorSupported())
			{
				$params['USER_FIELDS'] = $this->entity->getUserFields();

				$schemeFields = $inlineEditorParameters['schemeFields'];

				if ($schemeFields)
				{
					$params['SCHEME_INLINE'] = [
						[
							'name' => 'main',
							'title' => '',
							'type' => 'section',
							'elements' => array_values($schemeFields)
						]
					];
				}
			}
		}

		$this->prepareComponentParams($params);

		return $params;
	}

	/**
	 * @return bool
	 */
	protected function isOnlyItems(): bool
	{
		return (isset($this->params['ONLY_ITEMS']) && $this->params['ONLY_ITEMS'] === 'Y');
	}

	/**
	 * @param array $params
	 */
	protected function prepareComponentParams(array &$params): void
	{

	}

	/**
	 * Get admins and group moderators.
	 * @return array
	 */
	protected function getAdmins(): array
	{
		$users = [];

		$userQuery = new \Bitrix\Main\Entity\Query(\Bitrix\Main\UserTable::getEntity());
		// set select
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
			new \Bitrix\Main\Entity\ReferenceField(
				'UG',
				\Bitrix\Main\UserGroupTable::getEntity(),
				[
					'=this.ID' => 'ref.USER_ID',
					'=ref.GROUP_ID' => new \Bitrix\Main\DB\SqlExpression(1),
				],
				[
					'join_type' => 'INNER',
				]
			)
		);
		// set filter
		$date = new \Bitrix\Main\Type\DateTime;
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
	 * @return array
	 */
	protected function getAdditionalEditFields(): array
	{
		return $this->additionalEdit;
	}

	/**
	 * @param bool $clear
	 * @param bool $withoutCache
	 * @param array $params
	 * @return array
	 */
	public function getColumns(bool $clear = false, bool $withoutCache = false, array $params = []): array
	{
		static $columns = [];

		if($withoutCache)
		{
			$clear = $withoutCache;
		}

		if($params['ONLY_ITEMS'] === 'Y')
		{
			return $columns;
		}

		if($clear)
		{
			$columns = [];
		}

		$params['originalColumns'] = ($params['originalColumns'] ?? false);

		if(empty($columns))
		{
			$runtime = [];
			$baseCurrency = $this->currency;
			if($this->entity->getTypeName() === \CCrmOwnerType::OrderName)
			{
				$filter = $this->getOrderFilter($runtime);
				if(isset($filter[$this->getStatusKey()]))
				{
					$this->allowStages = $filter[$this->getStatusKey()];
				}
			}
			else
			{
				$filter = $this->getFilter($params);
			}
			$sort = 0;
			$winColumn = [];
			$userPerms = $this->getCurrentUserPermissions();
			// prepare each status
			$isFirstDropZone = false;
			foreach($this->getStatuses($clear) as $status)
			{
				$sort += 100;
				$isDropZone = $this->isDropZone($status);
				// first drop zone
				if(!$isFirstDropZone && $isDropZone)
				{
					$isFirstDropZone = true;
				}
				// add 'delete' column
				if($isFirstDropZone && !$params['originalColumns'])
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
					'color' => $this->getColumnColor($status),
					'type' => $status['PROGRESS_TYPE'],
					'sort' => $sort,
					'count' => 0,
					'total' => 0,
					'currency' => $baseCurrency,
					'dropzone' => $isDropZone,
					'alwaysShowInDropzone' => $this->isAlwaysShowInDropzone($status),
					'canAddItem' => $this->entity->canAddItemToStage($status['STATUS_ID'], $userPerms),
				];

				$column = array_merge($column, $this->getAdditionalColumnParams());

				// win column
				if(!$params['originalColumns'] && $status['PROGRESS_TYPE'] === 'WIN')
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

			if(!isset($columns[static::COLUMN_NAME_DELETED]))
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
		if($withoutCache)
		{
			$tmpColumns = $columns;
			$columns = [];

			return $tmpColumns;
		}

		return $columns;
	}

	/**
	 * @return array
	 */
	protected function getAdditionalColumnParams(): array
	{
		return [];
	}

	/**
	 * Get filter for orders
	 *
	 * @param array $runtime
	 *
	 * @return array
	 */
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

		if($params['FORCE_FILTER'] === 'Y')
		{
			if ($this->getEntity()->isCategoriesSupported())
			{
				return [
					'CATEGORY_ID' => $this->entity->getCategoryId(),
				];
			}
			return [];
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
		//from main.filter
		$grid = $this->entity->getFilterOptions();
		$gridFilter = $this->entity->getGridFilter();
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
				//fill filter by type
				$fromFieldName = $key . '_from';
				$toFieldName = $key . '_to';
				if($item['type'] === 'date')
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
				elseif($item['type'] === 'number')
				{
					$fltType = $search[$key . '_numsel'] ?? 'exact';
					if(
						($fltType === 'exact' || $fltType === 'range')
						&& isset($search[$fromFieldName], $search[$toFieldName])
					)
					{
						$filter['>=' . $key] = $search[$fromFieldName];
						$filter['<=' . $key] = $search[$toFieldName];
					}
					elseif($fltType === 'exact' && isset($search[$fromFieldName]))
					{
						$filter[$key] = $search[$fromFieldName];
					}
					elseif(
						$fltType === 'more'
						&& isset($search[$fromFieldName])
					)
					{
						$filter['>' . $key] = $search[$fromFieldName];
					}
					elseif(
						$fltType === 'less' &&
						isset($search[$toFieldName])
					)
					{
						$filter['<' . $key] = $search[$toFieldName];
					}
					elseif((isset($search[$key]) && $search[$key] === false))
					{
						$filter[$key] = $search[$key];
					}
					elseif((isset($search['!' . $key]) && $search['!' . $key] === false))
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
					elseif(in_array($key, $filterHistory, true))
					{
						$filter['%' . $key] = $search[$key];
					}
					elseif($key === 'STATUS_CONVERTED')
					{
						$filter[$key === 'N' ? 'STATUS_SEMANTIC_ID' : '!STATUS_SEMANTIC_ID'] = 'P';
					}
					elseif ($key === 'ORDER_TOPIC' ||
						(
							$this->entity instanceof \Bitrix\Crm\Kanban\Entity\Order
							&& $key === 'ACCOUNT_NUMBER'
						)
					)
					{
						$filter['~' . $key] = '%' . $search[$key] . '%';
					}
					elseif(
						$key === 'ENTITIES_LINKS' &&
						(
						$this->entity->isEntitiesLinksInFilterSupported()
						)
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
								$filter[$this->entity->getFilterFieldNameByEntityTypeName($ownerTypeName)] = $ownerID;
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
						$filter[$this->entity->prepareFilterField($key)] = $search[$key];
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
			&& ($this->entity->isOverdueFilterSupported())
		)
		{
			$key = $this->entity->getCloseDateFieldName();
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
		// counters
		if(
			isset($filter['ACTIVITY_COUNTER'])
			&& $this->entity->isActivityCountersFilterSupported()
		)
		{
			if(is_array($filter['ACTIVITY_COUNTER']))
			{
				$counterTypeID = \Bitrix\Crm\Counter\EntityCounterType::joinType(
					array_filter($filter['ACTIVITY_COUNTER'], 'is_numeric')
				);
			}
			else
			{
				$counterTypeID = (int)$filter['ACTIVITY_COUNTER'];
			}

			$counter = null;
			if($counterTypeID > 0)
			{
				// get assigned for this counter
				$counterUserIDs = array();
				if(isset($filter['ASSIGNED_BY_ID']))
				{
					if(is_array($filter['ASSIGNED_BY_ID']))
					{
						$counterUserIDs = array_filter($filter['ASSIGNED_BY_ID'], 'is_numeric');
					}
					elseif($filter['ASSIGNED_BY_ID'] > 0)
					{
						$counterUserIDs[] = $filter['ASSIGNED_BY_ID'];
					}
				}
				// set counter to the filter
				try
				{
					$counter = \Bitrix\Crm\Counter\EntityCounterFactory::create(
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
					if(isset($filter['ASSIGNED_BY_ID']))
					{
						unset($filter['ASSIGNED_BY_ID']);
					}
				} catch(\Bitrix\Main\NotSupportedException $e)
				{
				} catch(\Bitrix\Main\ArgumentException $e)
				{
				}
			}
		}

		$filter = Deal\OrderFilter::prepareFilter($filter);
		$filter = \Bitrix\Crm\Automation\Debugger\DebuggerFilter::prepareFilter($filter, $this->entity->getTypeId());

		//deal
		if($this->entity->isCategoriesSupported())
		{
			$filter['CATEGORY_ID'] = $this->entity->getCategoryId();
		}
		//invoice
		if($this->entity->isRecurringSupported())
		{
			$filter['!IS_RECURRING'] = 'Y';
		}
		if(isset($filter['OVERDUE']))
		{
			unset($filter['OVERDUE']);
		}
		//detect success/fail columns
		$this->prepareSemanticIdsAndStages($filter);

		$entityTypeId = $this->entity->getTypeId();
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

		\CCrmEntityHelper::prepareMultiFieldFilter($filter, [], '=%', false);

		return $filter;
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
	 * Gets current user perms.
	 * @return \CCrmPerms
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

		foreach($allStatuses as $status)
		{
			$status['STATUS_ID'] = htmlspecialcharsbx($status['STATUS_ID']);

			if($status['SEMANTICS'] === PhaseSemantics::SUCCESS)
			{
				$status['PROGRESS_TYPE'] = 'WIN';
			}
			elseif($status['SEMANTICS'] === PhaseSemantics::FAILURE)
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

	/**
	 * Base method for getting data.
	 * @param array $filter
	 * @param int $blockPage
	 * @return array
	 */
	public function getItems(array $filter = [], int $blockPage = 1): array
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
		$filterCommon = ($type === \CCrmOwnerType::OrderName ? $this->getOrderFilter($runtime) : $this->getFilter());

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
			'order' => ['ID' => 'DESC'],
			'limit' => static::BLOCK_SIZE,
			'offset' => static::BLOCK_SIZE * ($this->blockPage - 1),
		];

		if(!empty($runtime))
		{
			$parameters['runtime'] = $runtime;
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

		$inlineFieldTypes = $this->getInlineFieldTypes();
		$specialReqKeys = $this->getSpecialReqKeys();
		$result = [];

		$activeAutomationDebugEntityIds = \CCrmBizProcHelper::getActiveDebugEntityIds($this->entity->getTypeId());

		foreach($rows as $rowId => $row)
		{
			if (is_array($renderedRows[$rowId]))
			{
				$row = array_merge($row, $renderedRows[$rowId]);
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
							'html' => $displayedField->wasRenderedAsHtml(),
						];
					}
				}
			}

			$returnCustomer = (isset($row['IS_RETURN_CUSTOMER']) && $row['IS_RETURN_CUSTOMER'] === 'Y');
			// collect required
			$required = [];
			$requiredFm = [];
			if ($this->requiredFields)
			{
				// fm fields check later
				foreach ($this->getAllowedFmTypes() as $fm)
				{
					$fmUp = mb_strtoupper($fm);
					$requiredFm[$fmUp] = true;
					$row[$fmUp] = '';
				}
				// check each key
				foreach ($row as $fieldName => $fieldValue)
				{
					if ($returnCustomer && isset($this->exclusiveFieldsReturnCustomer[$fieldName]))
					{
						continue;
					}
					if (
						isset($this->requiredFields[$fieldName])
						&& !$fieldValue
						&& $fieldValue !== '0'
						&& $fieldValue !== 0
						&& $fieldValue !== 0.0
					)
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
			$result[$row['ID']] = [
				'id' =>  $row['ID'],
				'name' => htmlspecialcharsbx($row['TITLE'] ?: '#' . $row['ID']),
				'link' => ($row['LINK'] ?? str_replace($this->getPathMarkers(), $row['ID'], $path)),
				'columnId' => ($columnId = htmlspecialcharsbx($row[$this->getStatusKey()])),
				'columnColor' => (isset($columns[$columnId]) ? $columns[$columnId]['color'] : ''),
				'price' => $row['PRICE'],
				'price_formatted' => $row['PRICE_FORMATTED'],
				'date' => $row['DATE_FORMATTED'],
				'contactId' => (int)$row['CONTACT_ID'],
				'companyId' => (!empty($row['COMPANY_ID']) ? (int)$row['COMPANY_ID'] : null),
				'contactType' => $row['CONTACT_TYPE'],
				'modifyById' => ($row['MODIFY_BY_ID'] ?? 0),
				'modifyByAvatar' => '',
				'activityShow' => 1,
				'activityErrorTotal' => 0,
				'activityProgress' => 0,
				'activityTotal' => 0,
				'page' => $this->blockPage,
				'fields' => $fields,
				'return' => $returnCustomer,
				'returnApproach' => (isset($row['IS_REPEATED_APPROACH']) && $row['IS_REPEATED_APPROACH'] === 'Y'),
				'assignedBy' => $row['ASSIGNED_BY'],
				'required' => $required,
				'required_fm' => $requiredFm,
			];
			$isRestricted = (!empty($restrictedItemIds) && in_array($row['ID'], $restrictedItemIds));
			if ($isRestricted)
			{
				$result[$row['ID']]['updateRestrictionCallback'] = $restriction->prepareInfoHelperScript();
			}
			if ($this->entity->hasClientFields())
			{
				foreach ($this->getClientFields() as $clientField)
				{
					if ($row[$clientField])
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
		$result = $this->sort($result);

		return [
			'ITEMS' => $result,
			'RESTRICTED_VALUE_CLICK_CALLBACK' => $restrictedValueClickCallback,
		];
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
		return $this->entity->getDisplayedFieldsList($clearCache);
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
	 * Current user is crm admin?
	 * @return boolean
	 */
	public function isCrmAdmin(): bool
	{
		$crmPerms = new \CCrmPerms($this->currentUserId);

		return $crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
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
}
