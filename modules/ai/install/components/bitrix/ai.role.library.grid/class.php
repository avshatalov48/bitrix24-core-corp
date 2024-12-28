<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\AI\Facade\User;
use Bitrix\AI\ShareRole\Components\Grid\ShareRoleGrid;
use Bitrix\AI\ShareRole\Service\GridRole\Dto\GridParamsDto;
use Bitrix\AI\ShareRole\Service\GridRole\GridRoleService;
use Bitrix\AI\Container;
use Bitrix\Main\Grid\Grid as Grid;
use Bitrix\Main\Grid\Settings as Settings;
use Bitrix\Main\Grid\Component\ComponentParams;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\UI\Buttons;
use Bitrix\AI\ShareRole\Components\Grid\Request\FilterRequest;
use Bitrix\Main\UI\Filter;

Loader::includeModule('ai');
Loader::includeModule('ui');

Extension::load([
	'ui.switcher',
	'ui.label',
	'ui.cnt',
	'ui.counter',
]);

Loc::loadMessages(__FILE__);

$GRID_COLUMNS = [
	'NAME' => 'NAME',
	'TYPE' => 'TYPE',
	'AUTHOR' => 'AUTHOR',
	'DATE_CREATE' => 'DATE_CREATE',
	'ACCESS' => 'ACCESS',
];

class AiRoleLibraryComponentGrid extends CBitrixComponent
{
	private ShareRoleGrid $grid;
	private GridRoleService $gridRoleService;
	private FilterRequest $filterRequest;
	private GridParamsDto $gridParamsDto;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$shareRoleContainer = Container::init();

		$this->gridRoleService = $shareRoleContainer->getItem(GridRoleService::class);
		$this->filterRequest = $shareRoleContainer->getItem(FilterRequest::class);
	}

	public function executeComponent(): void
	{
		$this->initFilterPresents();
		$this->initGrid();

		$this->getGrid()->processRequest();
		$this->fillGridParamsDto();

		$rows = $this->getRows();
		$this->getGrid()->setRawRows($rows);

		$this->initResult(count($rows) === 0 && $this->hasUserRoles() === false);

		$this->initToolbar();
		$this->includeComponentTemplate();
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function getGrid(): Grid
	{
		if (empty($this->grid))
		{
			$this->initGrid();
		}

		return $this->grid;
	}

	/**
	 * @throws \Bitrix\Main\ArgumentException
	 */
	private function initGrid(): Grid
	{
		if (!empty($this->grid))
		{
			return $this->grid;
		}

		$this->grid = new ShareRoleGrid(
			new Settings(['ID' => $this->getGridId()])
		);

		//for fake init pagination
		$this->grid->initPagination(1000000);

		return $this->grid;
	}

	private function initFilterPresents(): void
	{
		$filterOptions = new Filter\Options($this->getGridId());
		$filterPresets = $filterOptions->getPresets();

		$isChanged = false;

		if (empty($filterPresets['mine']))
		{
			$isChanged = true;

			$filterOptions->setFilterSettings(
				"mine",
				[
					"name" => Loc::getMessage('ROLE_LIBRARY_GRID_FILTER_PRESET_MINE'),
					"fields" => [
						"AUTHOR" => User::getCurrentUserId(),
					],
					false,
					false,
				]
			);
		}

		if (empty($filterPresets['active']))
		{
			$isChanged = true;

			$filterOptions->setFilterSettings(
				"active",
				[
					"name" => Loc::getMessage('ROLE_LIBRARY_GRID_FILTER_PRESET_ACTIVE'),
					"fields" => [
						"IS_ACTIVE" => 'Y',
					],
					true,
					false,
				]
			);
			$filterOptions->pinPreset("active");
		}

		if ($isChanged)
		{
			$filterOptions->save();
		}
	}

	private function getGridId(): string
	{
		return 'ai_share_role_library_grid';
	}

	private function getRows(): array
	{
		if (empty($this->gridParamsDto?->filter?->roleIds))
		{
			return [];
		}

		$gridRoles = $this->gridRoleService->getRolesForGrid(
			User::getCurrentUserId(),
			$this->gridParamsDto
		);

		$rows = [];

		foreach ($gridRoles as $gridRole)
		{
			$rows[] = [
				'ID' => $gridRole->getCode(),
				'NAME' => $gridRole,
				'AUTHOR' => [
					'name' => $gridRole->getAuthor(),
					'photo' => $gridRole->getAuthorPhoto(),
				],
				'EDITOR' => [
					'name' => $gridRole->getEditor(),
					'photo' => $gridRole->getEditorPhoto(),
				],
				'DATE_CREATE' => $gridRole->getDateCreate(),
				'DATE_MODIFY' => $gridRole->getDateModify(),
				'IS_ACTIVE' => $gridRole->isActive(),
				'IS_DELETED' => $gridRole->isDeleted(),
				'ACCESS' => [
					'roleCode' => $gridRole->getCode(),
				],
				'SHARE' => [
					'shares' => $gridRole->getShare(),
					'roleCode' => $gridRole->getCode(),
					'totalCount' => $gridRole->getCountShare(),
				],
				'DATA' => [
					'IS_FAVOURITE' => $gridRole->isFavorite(),
					'ROLE_CODE' => $gridRole->getCode(),
					'NAME' => $gridRole->getTitle(),
				],
			];
		}

		return $rows;
	}

	private function initResult(bool $showStub): void
	{
		$stub = null;

		if ($showStub)
		{
			$stub = $this->getStubHtml();
		}

		$this->arResult = [
			'GRID' => [...ComponentParams::get($this->getGrid()), 'STUB' => $stub],
		];
	}

	private function initToolbar(): void
	{
		$this->initToolbarCreateRoleButton();
		$this->initToolbarFilter();
//		$this->initToolbarSettingsButton();
		$this->initToolbarHelpDeskButton();
		Toolbar::deleteFavoriteStar();
	}

	private function initToolbarCreateRoleButton(): void
	{
		$createRoleButton = new Buttons\Button([
			"text" => Loc::getMessage('ROLE_LIBRARY_ADD_ROLE_BUTTON'),
			"color" => Buttons\Color::SUCCESS,
			"click" => new Buttons\JsHandler(
				"BX.AI.ShareRole.Library.Controller.handleClickOnCreateRoleButton"
			),
		]);

		Toolbar::addButton($createRoleButton, ButtonLocation::AFTER_TITLE);
	}

	private function initToolbarSettingsButton(): void
	{
		$settingsButton = new Buttons\SettingsButton([
			'menu' => [
				'id' => 'roles-library-toolbar-settings-menu',
				'items' => [
					['text' => "Заполнить пунктами", 'href' => "/path/to/page"],
					['text' => "В будущем", 'disabled' => true],
					['delimiter' => true],
				],
				'offsetLeft' => 20,
				'closeByEsc' => true,
				'angle' => true
			],
		]);

		Toolbar::addButton($settingsButton, ButtonLocation::AFTER_NAVIGATION);
	}

	private function initToolbarHelpDeskButton(): void
	{
		$link = \Bitrix\UI\Util::getArticleUrlByCode('23184474');

		$helpdeskButton = new Buttons\Button([
			"color" => Buttons\Color::LIGHT_BORDER,
			'classList'=> ['ui-btn-icon-help'],
			"link" => $link
		]);

		Toolbar::addButton($helpdeskButton, ButtonLocation::AFTER_NAVIGATION);
	}

	private function initToolbarFilter(): void
	{
		$options = $this->getToolbarFilterOptions();

		$this->addToolbarFilter($options);
	}

	protected function getToolbarFilterOptions(): array
	{
		return \Bitrix\Main\Filter\Component\ComponentParams::get(
			$this->getGrid()->getFilter(),
			[
				'GRID_ID' => $this->getGridId(),
				'RESET_TO_DEFAULT_MODE' => true,
				'DISABLE_SEARCH' => false,
				'ENABLE_LIVE_SEARCH' => false,
			],
		);
	}

	protected function addToolbarFilter(array $filterOptions): void
	{
		Toolbar::addFilter($filterOptions);
	}

	private function getStubHtml(): string
	{
		$stubTitle = Loc::getMessage('ROLE_LIBRARY_GRID_STUB_TITLE');;
		$stubSubtitle = Loc::getMessage('ROLE_LIBRARY_GRID_STUB_SUBTITLE');;

		return <<<HTML
			<div class="ai__role-library-grid-stub">
				<div class="ai__role-library-grid-stub-img"></div>
				<div class="ai__role-library-grid-stub-title">$stubTitle</div>
				<div class="ai__role-library-grid-stub-subtitle">$stubSubtitle</div>
			</div>
HTML;
	}

	private function getGridOrmParamsWithSearchFilter(): array
	{
		$ormParams = $this->grid->getOrmParams();

		if (isset($ormParams['filter']['IS_DELETED']))
		{
			$ormParams['filter']['IS_DELETED'] = $ormParams['filter']['IS_DELETED'] === 'Y' ? 'N' : 'Y';
		}

		$filterOptions = new Filter\Options($this->getGridId());
		$searchString = $filterOptions->getSearchString();

		if ($searchString !== '')
		{
			$ormParams['filter']['NAME'] = $searchString;
		}

		return $ormParams;
	}

	private function hasUserRoles(): bool
	{
		$gridParamsDto = $this->filterRequest->getDataFromParams(['filter'=>['IS_ACTIVE' => 'Y']]);

		$this->gridRoleService->fillRoleIdsInFilter(
			User::getCurrentUserId(),
			$gridParamsDto
		);

		return !empty($gridParamsDto->filter->roleIds);
	}

	private function updatePagination()
	{
		$rowsCount = count($this->gridParamsDto->filter->roleIds);

		$this->grid->initPagination($rowsCount);
		$this->grid->getPagination()->initFromUri();

		$this->filterRequest->addLimiterAndOffset(
			$this->gridParamsDto, $this->grid->getOrmParams()
		);

		if ($this->gridParamsDto->offset >= $rowsCount)
		{
			$this->grid->getPagination()->setCurrentPage(1);
			$this->filterRequest->addLimiterAndOffset(
				$this->gridParamsDto, $this->grid->getOrmParams()
			);
		}
	}

	private function fillGridParamsDto(): void
	{
		$this->gridParamsDto = $this->filterRequest->getDataFromParams(
			$this->getGridOrmParamsWithSearchFilter(),
		);

		$this->gridRoleService->fillRoleIdsInFilter(
			User::getCurrentUserId(),
			$this->gridParamsDto
		);

		$this->updatePagination();
	}
}
