<?php

use Bitrix\Crm\Filter;
use Bitrix\Crm\Integration\Intranet\CustomSection\CustomSectionQueries;
use Bitrix\Crm\Restriction\RestrictionManager;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\UI\Buttons;
use Bitrix\UI\Toolbar;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

\Bitrix\Main\Loader::includeModule('crm');

class CrmTypeListComponent extends Bitrix\Crm\Component\Base
{
	protected const GRID_ID = 'crm-type-list';
	protected const EXTERNAL_GRID_ID = 'crm-type-list-external';
	protected const DEFAULT_PAGE_SIZE = 10;

	protected const INTERNAL_DYNAMIC_TYPES_TITLE = 'CRM_TYPE_LIST_TITLE_MSGVER_1';
	protected const EXTERNAL_DYNAMIC_TYPES_TITLE = 'CRM_TYPE_LIST_EXTERNAL_DYNAMIC_TYPES_TITLE';

	/** @var Filter\TypeDataProvider */
	protected $provider;
	/** @var Filter\Filter */
	protected $filter;
	protected $defaultGridSort = [
		'ID' => 'desc',
	];
	protected $navParamName = 'page';
	protected $users;
	protected $isExternalDynamicTypes;
	protected $gridId;

	private CustomSectionQueries $customSectionQueries;

	protected function init(): void
	{
		parent::init();

		$this->customSectionQueries = CustomSectionQueries::getInstance();

		$consistentUrl = Container::getInstance()->getRouter()->getConsistentUrlFromPartlyDefined(\Bitrix\Main\Application::getInstance()->getContext()->getRequest()->getRequestUri());
		if ($consistentUrl)
		{
			LocalRedirect($consistentUrl->getUri());
			return;
		}

		if (!Container::getInstance()->getUserPermissions()->canWriteConfig())
		{
			$this->errorCollection[] = new \Bitrix\Main\Error(Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'));
			return;
		}

		$isExternalDynamicTypes = isset($this->arParams['isExternal']) && $this->arParams['isExternal'] === true;
		$this->gridId = $isExternalDynamicTypes ? static::EXTERNAL_GRID_ID : static::GRID_ID;

		/** @var Filter\TypeSettings $settings */
		$settings = new Filter\TypeSettings([
			'ID' => $this->gridId,
			'IS_EXTERNAL_DYNAMICAL_TYPES' => $isExternalDynamicTypes,
		]);

		$this->provider = new Filter\TypeDataProvider($settings);
		$this->filter = new Filter\Filter($settings->getID(), $this->provider);

		$this->isExternalDynamicTypes = $this->provider->getSettings()->getIsExternalDynamicalTypes();
	}

	public function executeComponent()
	{
		$this->init();

		if($this->getErrors())
		{
			$this->includeComponentTemplate();
			return;
		}

		$title = $this->isExternalDynamicTypes
			? Loc::getMessage(static::EXTERNAL_DYNAMIC_TYPES_TITLE)
			: Loc::getMessage(static::INTERNAL_DYNAMIC_TYPES_TITLE)
		;
		$this->getApplication()->SetTitle($title);

		$this->arResult['isExternal'] = $this->isExternalDynamicTypes;
		$this->arResult['grid'] = $this->prepareGrid();
		$this->arResult['isEmptyList'] = empty($this->arResult['grid']['ROWS']);
		$this->arResult['filter'] = $this->prepareFilter();
		$this->arResult['welcome'] = $this->getPreparedEmptyState();
		$this->includeComponentTemplate();
	}

	protected function getPreparedEmptyState(): array
	{
		if ($this->isExternalDynamicTypes)
		{
			return [
				'title' => Loc::getMessage('CRM_TYPE_LIST_CLASS_EXTERNAL_WELCOME_TITLE'),
				'text' => Loc::getMessage('CRM_TYPE_LIST_CLASS_EXTERNAL_WELCOME_TEXT'),
				'link' => Loc::getMessage('CRM_TYPE_LIST_CLASS_EXTERNAL_WELCOME_LINK'),
				'helpdeskCode' => 18913896,
			];
		}

		return [
			'title' => Loc::getMessage('CRM_TYPE_LIST_CLASS_WELCOME_TITLE'),
			'text' =>  Loc::getMessage('CRM_TYPE_LIST_CLASS_WELCOME_TEXT'),
			'link' => Loc::getMessage('CRM_TYPE_LIST_CLASS_WELCOME_LINK'),
			'helpdeskCode' => 13315798,
		];
	}

	protected function prepareGrid(): array
	{
		$grid = [];
		$grid['GRID_ID'] = $this->gridId;
		$grid['COLUMNS'] = $this->provider->getGridColumns();
		$grid['ROWS'] = [];

		$gridOptions = new Bitrix\Main\Grid\Options($this->gridId);
		$navParams = $gridOptions->getNavParams(['nPageSize' => static::DEFAULT_PAGE_SIZE]);
		$pageSize = (int)$navParams['nPageSize'];
		$gridSort = $gridOptions->GetSorting(['sort' => $this->defaultGridSort]);
		$pageNavigation = $this->getPageNavigation($pageSize);
		$userIds = [];
		$listFilter = $this->getListFilter();

		$list = Container::getInstance()->getDynamicTypeDataClass()::getList([
			'order' => $gridSort['sort'],
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
			'filter' => $listFilter,
		])->fetchAll();

		$totalCount = Container::getInstance()->getDynamicTypeDataClass()::getCount($listFilter);

		if(count($list) > 0)
		{
			$format = CCrmDateTimeHelper::getDefaultDateTimeFormat();
			$currentTime = time() + CTimeZone::GetOffset();

			$typeIds = [];
			foreach($list as $item)
			{
				$userIds[$item['CREATED_BY']] = $item['CREATED_BY'];
				$userIds[$item['UPDATED_BY']] = $item['UPDATED_BY'];
				$typeIds[] = $item['ENTITY_TYPE_ID'];
			}

			$customSections = $this->customSectionQueries->findByEntityTypeIds($typeIds);

			$this->users = Container::getInstance()->getUserBroker()->getBunchByIds($userIds);
			foreach($list as $item)
			{
				$item['CUSTOM_SECTION'] = $customSections[$item['ENTITY_TYPE_ID']] ?? null;
				$eventData = \CUtil::PhpToJSObject([
					'entityTypeId' => $item['ENTITY_TYPE_ID'],
					'id' => $item['ID'],
				]);
				$isTypeSettingsRestricted = RestrictionManager::getDynamicTypesLimitRestriction()->isTypeSettingsRestricted($item['ENTITY_TYPE_ID']);
				$editAction = [
					'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_EDIT'),
					'HREF' => Container::getInstance()->getRouter()->getTypeDetailUrl($item['ENTITY_TYPE_ID']),
				];
				$fieldsAction = null;
				if (!$isTypeSettingsRestricted)
				{
					$fieldsAction = [
						'TEXT' => Loc::getMessage('CRM_TYPE_TYPE_FIELDS_SETTINGS'),
						'HREF' => Container::getInstance()->getRouter()->getUserFieldListUrl($item['ENTITY_TYPE_ID']),
					];
				}

				$item['LAST_ACTIVITY_TIME'] = FormatDate($format, CCrmDateTimeHelper::getUserTime($this->getLastActivityTime($item)), $currentTime);
				$item['CREATED_TIME'] = FormatDate($format, CCrmDateTimeHelper::getUserTime($item['CREATED_TIME']), $currentTime);
				$item['UPDATED_TIME'] = FormatDate($format, CCrmDateTimeHelper::getUserTime($item['UPDATED_TIME']), $currentTime);

				$grid['ROWS'][] = [
					'id' => $item['ID'],
					'data' => $item,
					'columns' => $this->getItemColumn($item),
					'actions' => [
						$editAction,
						$fieldsAction,
						[
							'TEXT' => Loc::getMessage('CRM_COMMON_ACTION_DELETE'),
							'ONCLICK' => "BX.Event.EventEmitter.emit('BX.Crm.TypeListComponent:onClickDelete', ".$eventData.")",
						]
					]
				];
			}
		}
		$pageNavigation->setRecordCount($totalCount);
		$grid['NAV_PARAM_NAME'] = $this->navParamName;
		$grid['CURRENT_PAGE'] = $pageNavigation->getCurrentPage();
		$grid['NAV_OBJECT'] = $pageNavigation;
		$grid['TOTAL_ROWS_COUNT'] = $totalCount;
		$grid['AJAX_MODE'] = 'Y';
		$grid['ALLOW_ROWS_SORT'] = false;
		$grid['AJAX_OPTION_JUMP'] = "N";
		$grid['AJAX_OPTION_STYLE'] = "N";
		$grid['AJAX_OPTION_HISTORY'] = "N";
		$grid['SHOW_PAGESIZE'] = true;
		$grid['PAGE_SIZES'] = [['NAME' => '10', 'VALUE' => '10'], ['NAME' => '20', 'VALUE' => '20'], ['NAME' => '50', 'VALUE' => '50']];
		$grid['SHOW_ACTION_PANEL'] = false;
		$grid['SHOW_PAGINATION'] = true;
		$grid['ALLOW_CONTEXT_MENU'] = false;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = false;
		$grid['SHOW_ROW_CHECKBOXES'] = false;
		$grid['SHOW_ROW_ACTIONS_MENU'] = true;
		$grid['USE_CHECKBOX_LIST_FOR_SETTINGS_POPUP'] = true;

		return $grid;
	}

	/**
	 * @param array $item
	 * @return Type\DateTime
	 */
	protected function getLastActivityTime(array $item) : Type\DateTime
	{
		$entityTypeId = (int)$item['ENTITY_TYPE_ID'];
		$createdTime = $item['CREATED_TIME'];

		$cacheManager = \Bitrix\Main\Application::getInstance()->getManagedCache();
		$ttl = 86400;
		$cacheDir = "crm_dynamic_type_{$entityTypeId}";
		$cacheTag = "{$cacheDir}_last_activity_datetime";

		if ($cacheManager->read($ttl, $cacheTag, $cacheDir))
		{
			$lastActivityTimestamp = $cacheManager->get($cacheTag);
			return Type\DateTime::createFromTimestamp($lastActivityTimestamp);
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			$cacheManager->set($cacheTag, $createdTime->getTimestamp());
			return $createdTime;
		}

		$lastActivityTime = $factory->getDataClass()::getList([
			'select' => ['MAX'],
			'runtime' => [
				new ExpressionField('MAX', 'MAX(UPDATED_TIME)')
			],
		])->fetch();
		$lastActivityTime = $lastActivityTime['MAX'] ?? $createdTime;
		$cacheManager->set($cacheTag, $lastActivityTime->getTimestamp());

		return $lastActivityTime;
	}

	protected function getPageNavigation(int $pageSize): PageNavigation
	{
		$pageNavigation = new PageNavigation($this->navParamName);
		$pageNavigation->allowAllRecords(true)->setPageSize($pageSize)->initFromUri();

		return $pageNavigation;
	}

	protected function getListFilter(): array
	{
		$filterOptions = new Options($this->gridId);
		$requestFilter = $filterOptions->getFilter($this->filter->getFieldArrays());

		$filter = [];
		$this->provider->prepareListFilter($filter, $requestFilter);
		$filter['!@ENTITY_TYPE_ID'] = \CCrmOwnerType::getDynamicTypeBasedStaticEntityTypeIds();

		return $filter;
	}

	protected function prepareFilter(): array
	{
		return [
			'FILTER_ID' => $this->gridId,
			'GRID_ID' => $this->gridId,
			'FILTER' => $this->filter->getFieldArrays(),
			'DISABLE_SEARCH' => false,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => false,
			'ENABLE_LIVE_SEARCH' => true,
			'THEME' => Bitrix\Main\UI\Filter\Theme::MUTED,
		];
	}

	protected function getItemColumn(array $item): array
	{
		$createdBy = $item['CREATED_BY'];
		$updatedBy = $item['UPDATED_BY'];

		$item['CREATED_BY'] = isset($this->users[$createdBy])
			? $this->prepareUserDataForGrid($this->users[$createdBy])
			: ''
		;

		$item['UPDATED_BY'] =
			isset($this->users[$updatedBy])
			?  $this->prepareUserDataForGrid($this->users[$updatedBy])
			: ''
		;

		$detailUrl = htmlspecialcharsbx(Container::getInstance()->getRouter()->getItemListUrlInCurrentView($item['ENTITY_TYPE_ID']));
		$item['TITLE'] = '<a href="'.$detailUrl.'">'.htmlspecialcharsbx($item['TITLE']).'</a>';

		if (array_key_exists('CUSTOM_SECTION', $item))
		{
			$item = $this->getCustomSectionItemCell($item);
		}

		return $item;
	}

	/**
	 * @param array $item
	 * @return array
	 */
	private function getCustomSectionItemCell(array $item): array
	{
		if ($item['CUSTOM_SECTION'] !== null)
		{
			$title = $item['CUSTOM_SECTION']['SECTION_TITLE'];
			$id = (int)$item['CUSTOM_SECTION']['CUSTOM_SECTION_ID'];
		}
		else
		{
			$title = Loc::getMessage('CRM_TYPE_LIST_CUSTOM_SECTION_DEFAULT_VALUE');
			$id = -1;
		}

		$title = htmlspecialcharsbx($title);

		$filterOptions = new Options($this->gridId);
		$requestFilter = $filterOptions->getFilter($this->filter->getFieldArrays());

		$activeClass = 'crm-type-grid-custom-section-'
			. (isset($requestFilter['CUSTOM_SECTION']) ? 'active' : 'inactive');

		$item['CUSTOM_SECTION'] = <<<HTML
<div class='crm-type-grid-custom-section-wrapper $activeClass'>
	<span 
		class="crm-type-grid-custom-section-title"
		onclick="BX.Event.EventEmitter.emit('BX.Crm.TypeListComponent:onFilterByCustomSection', '$id')"
	>$title</span>
	<div 
		class="crm-type-grid-filter-remove"
		onclick="BX.Event.EventEmitter.emit('BX.Crm.TypeListComponent:onResetFilterByCustomSection')"
	></div>
</div>
HTML;
		return $item;
	}

	protected function getToolbarParameters(): array
	{
		$buttons = [];
		if(Container::getInstance()->getUserPermissions()->canAddType())
		{
			$createUrl = Container::getInstance()->getRouter()->getTypeDetailUrl(0);
			if ($this->isExternalDynamicTypes)
			{
				$createUrl->addParams(['isExternal' => 'Y']);
			}

			$buttons[Toolbar\ButtonLocation::AFTER_TITLE][] = new Buttons\Button([
				'color' => Buttons\Color::SUCCESS,
				'text' => Loc::getMessage('CRM_COMMON_ACTION_CREATE'),
				'link' => $createUrl->getUri(),
			]);
		}

		return array_merge(parent::getToolbarParameters(), [
			'buttons' => $buttons,
			'isWithFavoriteStar' => true,
			'hideBorder' => true,
		]);
	}

	protected function getTopPanelId(int $entityTypeId): string
	{
		return 'DYNAMIC_LIST';
	}
}
