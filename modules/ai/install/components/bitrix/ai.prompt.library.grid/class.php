<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\AI\Facade\User;
use Bitrix\AI\SharePrompt\Components\Grid\SharePromptGrid;
use Bitrix\AI\SharePrompt\Service\GridPrompt\Dto\GridParamsDto;
use Bitrix\AI\SharePrompt\Service\GridPrompt\GridPromptService;
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
use Bitrix\AI\SharePrompt\Components\Grid\Request\FilterRequest;
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

class AiPromptLibraryComponentGrid extends CBitrixComponent
{
	private SharePromptGrid $grid;
	private GridPromptService $gridPromptService;
	private FilterRequest $filterRequest;
	private GridParamsDto $gridParamsDto;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$sharePromptContainer = Container::init();

		$this->gridPromptService = $sharePromptContainer->getItem(GridPromptService::class);
		$this->filterRequest = $sharePromptContainer->getItem(FilterRequest::class);
	}

	public function executeComponent(): void
	{
		$this->initFilterPresents();
		$this->initGrid();

		$this->getGrid()->processRequest();
		$this->fillGridParamsDto();

		$rows = $this->getRows();
		$this->getGrid()->setRawRows($rows);

		$this->initResult(count($rows) === 0 && $this->hasUserPrompts() === false);
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

		$this->grid = new SharePromptGrid(
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
					"name" => Loc::getMessage('PROMPT_LIBRARY_GRID_FILTER_PRESET_MINE'),
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
					"name" => Loc::getMessage('PROMPT_LIBRARY_GRID_FILTER_PRESET_ACTIVE'),
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
		return 'ai_share_prompt_library_grid';
//		return $this->arParams['GRID_ID'] ?? (new ReflectionClass($this))->getShortName();
	}

	private function getRows(): array
	{
		if (empty($this->gridParamsDto?->filter?->promptIds))
		{
			return [];
		}

		$gridPrompts = $this->gridPromptService->getPromptsForGrid(
			User::getCurrentUserId(),
			$this->gridParamsDto
		);

		$rows = [];

		foreach ($gridPrompts as $gridPrompt)
		{
			$rows[] = [
				'ID' => $gridPrompt->getCode(),
				'NAME' => $gridPrompt,
				'TYPE' => $gridPrompt->getType(),
				'AUTHOR' => [
					'name' => $gridPrompt->getAuthor(),
					'photo' => $gridPrompt->getAuthorPhoto(),
				],
				'EDITOR' => [
					'name' => $gridPrompt->getEditor(),
					'photo' => $gridPrompt->getEditorPhoto(),
				],
				'DATE_CREATE' => $gridPrompt->getDateCreate(),
				'DATE_MODIFY' => $gridPrompt->getDateModify(),
				'IS_ACTIVE' => $gridPrompt->isActive(),
				'IS_DELETED' => $gridPrompt->isDeleted(),
				'ACCESS' => [
					'promptCode' => $gridPrompt->getCode(),
					'categories' => $gridPrompt->getCategories()
				],
				'SHARE' => [
					'shares' => $gridPrompt->getShare(),
					'promptCode' => $gridPrompt->getCode(),
					'totalCount' => $gridPrompt->getCountShare(),
				],
				'DATA' => [
					'IS_FAVOURITE' => $gridPrompt->isFavorite(),
					'PROMPT_CODE' => $gridPrompt->getCode(),
					'NAME' => $gridPrompt->getTitle(),
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
		$this->initToolbarCreatePromptButton();
		$this->initToolbarFilter();
		Toolbar::deleteFavoriteStar();
	}

	private function initToolbarCreatePromptButton(): void
	{
		$createPromptButton = new Buttons\Button([
			"text" => Loc::getMessage('PROMPT_LIBRARY_ADD_PROMPT_BUTTON'),
			"color" => Buttons\Color::SUCCESS,
			"click" => new Buttons\JsHandler(
				"BX.AI.SharePrompt.Library.Controller.handleClickOnCreatePromptButton"
			),
		]);

		Toolbar::addButton($createPromptButton, ButtonLocation::AFTER_TITLE);
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
		$stubTitle = Loc::getMessage('PROMPT_LIBRARY_GRID_STUB_TITLE');
		$stubSubtitle = Loc::getMessage('PROMPT_LIBRARY_GRID_STUB_SUBTITLE');

		return <<<HTML
			<div class="ai__prompt-library-grid-stub">
				<div class="ai__prompt-library-grid-stub-img"></div>
				<div class="ai__prompt-library-grid-stub-title">$stubTitle</div>
				<div class="ai__prompt-library-grid-stub-subtitle">$stubSubtitle</div>
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

	private function hasUserPrompts(): bool
	{
		$gridParamsDto = $this->filterRequest->getDataFromParams([]);

		$this->gridPromptService->fillPromptIdsInFilter(
			User::getCurrentUserId(),
			$gridParamsDto
		);

		return !empty($gridParamsDto->filter->promptIds);
	}

	private function updatePagination()
	{
		$rowsCount = count($this->gridParamsDto->filter->promptIds);

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

		$this->gridPromptService->fillPromptIdsInFilter(
			User::getCurrentUserId(),
			$this->gridParamsDto
		);

		$this->updatePagination();
	}
}
