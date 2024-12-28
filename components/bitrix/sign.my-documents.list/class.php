<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Application;
use Bitrix\Sign\Config\User;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Loader;
use Bitrix\Sign\Factory\MyDocumentsGrid\MyDocumentsFilterFactory;
use Bitrix\Sign\Integration\Bitrix24\B2eTariff;
use Bitrix\Sign\Item\MyDocumentsGrid\MyDocumentsFilter;
use Bitrix\Sign\Service\B2e\MyDocumentsGrid\DataService;
use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Service\CounterService;
use Bitrix\Sign\Type\CounterType;
use Bitrix\Sign\Type\MyDocumentsGrid\ActorRole;
use Bitrix\Sign\Type\MyDocumentsGrid\FilterStatus;
use Bitrix\Sign\Util\UI\PageNavigation;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\CreateButton;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Sign\Config\Feature;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass('bitrix:sign.base');

class SignMyDocumentsComponent extends SignBaseComponent
{
	private const DEFAULT_PAGE_SIZE = 10;
	private const DEFAULT_NAV_KEY = "sign-my-documents-list-nav";
	private const DEFAULT_GRID_ID = 'SIGN_B2E_MY_DOCUMENTS_GRID';
	private const DEFAULT_FILTER_ID = 'SIGN_B2E_MI_DOCUMENTS_FILTER';
	private const PARAM_FILTER_ID = 'FILTER_ID';
	private const RESULT_FILTER = 'FILTER';
	private const RESULT_FILTER_PRESETS = 'FILTER_PRESETS';
	private const COUNTER_PANEL_DELIMITER = '__';
	private const FILTER_PRESET_SIGNED = 'preset_signed';
	private const FILTER_PRESET_IN_PROGRESS = 'preset_in_progress';
	private const SIGN_B2E_MY_DOCUMENTS_CREATE_BUTTON_CLASS = 'sign_b2e_my_documents_create_button';
	private const SIGN_B2E_MY_DOCUMENTS_SALARY_AND_VACATION_BUTTON_CLASS = 'sign-grid-salary-and-vacation-button';
	private readonly DataService $myDocumentService;
	private readonly CounterService $counterService;

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->myDocumentService = Container::instance()->getMyDocumentService();
		$this->counterService = Container::instance()->getCounterService();
	}

	public function executeComponent(): void
	{
		$userId = (int)CurrentUser::get()->getId();
		if (!$userId || !User::instance()->canUserParticipateInSigning($userId))
		{
			showError('access denied');

			return;
		}

		$this->prepareNavigationParams();
		$this->prepareComponentParams();
		$this->addCreateButton();
		$this->addSalaryAndVacationButton();
		$this->includeComponentTemplate();
	}

	private function addCreateButton(): void
	{
		if (!Feature::instance()->isSendDocumentByEmployeeEnabled())
		{
			return;
		}

		$button = new CreateButton();
		$button->setText(Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_CREATE_BUTTON_TITLE') ?? '');
		$button->addClass(self::SIGN_B2E_MY_DOCUMENTS_CREATE_BUTTON_CLASS);
		if (B2eTariff::instance()->isB2eRestrictedInCurrentTariff())
		{
			$button->addClass('ui-btn-icon-lock');
			$button->setTag('button');
		}

		Toolbar::addButton($button, ButtonLocation::AFTER_TITLE);
	}

	private function addSalaryAndVacationButton(): void
	{
		if (
			!Feature::instance()->isSendDocumentByEmployeeEnabled()
			|| !Container::instance()->getHcmLinkService()->isAvailable()
			|| !Loader::includeModule('humanresources')
			|| !method_exists(\Bitrix\HumanResources\Service\Container::class, 'getHcmLinkSalaryAndVacationService')
		)
		{
			return;
		}

		$userId = CurrentUser::get()->getId();
		if (!$userId)
		{
			return;
		}

		$salaryAndVacationService = \Bitrix\HumanResources\Service\Container::getHcmLinkSalaryAndVacationService();

		$this->setParam(
			'SALARY_VACATION_SETTINGS',
			$salaryAndVacationService->getSettingsForFrontendByUser($userId)
		);

		$button = new Button();
		$button->addClass(self::SIGN_B2E_MY_DOCUMENTS_SALARY_AND_VACATION_BUTTON_CLASS);
		$button->setText(Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_SALARY_AND_VACATION_BUTTON_TITLE') ?? '');
		$button->setDropdown();
		$button->setColor(\Bitrix\UI\Buttons\Color::PRIMARY);

		if (
			!$salaryAndVacationService->isConfigured()
			|| !$salaryAndVacationService->isAvailableForUser($userId)
		)
		{
			$button->setDisabled();
			$button->setDropdown(false);
		}

		Toolbar::addButton($button, ButtonLocation::AFTER_FILTER);
	}

	private function getOffsetForQuery(): int
	{
		return (int)$this->getNavigation()->getOffset();
	}

	private function getLimitForQuery(): int
	{
		return (int)$this->getNavigation()->getLimit();
	}

	private function getNavigation()
	{
		if (!isset($this->arResult['NAVIGATION_OBJECT']))
		{
			return $this->prepareNavigation();
		}

		return $this->arResult['NAVIGATION_OBJECT'];
	}

	private function getPageNavigation(int $userId, ?MyDocumentsFilter $filter = null): PageNavigation
	{
		$pageSize = (int)$this->getParam('PAGE_SIZE');
		$pageSize = $pageSize > 0 ? $pageSize : self::DEFAULT_PAGE_SIZE;
		$navigationKey = $this->getParam('NAVIGATION_KEY') ?? self::DEFAULT_NAV_KEY;
		$totalCountMembers = $this->myDocumentService->getTotalCountMembers($userId, $filter);

		$pageNavigation = new \Bitrix\Sign\Util\UI\PageNavigation($navigationKey);
		$pageNavigation->setPageSize($pageSize)
			->setRecordCount($totalCountMembers)
			->allowAllRecords(false)
			->initFromUri()
		;

		return $pageNavigation;
	}

	private function prepareNavigation(): PageNavigation
	{
		$pageNavigation = new PageNavigation($this->arResult['NAVIGATION_KEY']);
		$pageNavigation
			->setPageSize($this->arResult['PAGE_SIZE'])
			->allowAllRecords(false)
			->initFromUri()
		;
		$this->arResult['NAVIGATION_OBJECT'] = $pageNavigation;

		return $pageNavigation;
	}

	private function prepareNavigationParams(): void
	{
		$this->arResult['PAGE_SIZE'] = isset($this->arParams['PAGE_SIZE']) && (int)$this->arParams['PAGE_SIZE'] > 0
				? (int)$this->arParams['PAGE_SIZE']
				: self::DEFAULT_PAGE_SIZE
		;
		$this->arResult['NAVIGATION_KEY'] = $this->arParams['NAVIGATION_KEY'] ?? self::DEFAULT_NAV_KEY;
	}

	private function prepareComponentParams(): void
	{
		global $USER;
		$userId = $USER->IsAuthorized() ? (int)$USER->getId() : null;

		$this->setParam(
			'SIGN_B2E_MY_DOCUMENTS_CREATE_BUTTON_CLASS',
			self::SIGN_B2E_MY_DOCUMENTS_CREATE_BUTTON_CLASS
		);

		$this->setParam(
			'SIGN_B2E_MY_DOCUMENTS_SALARY_VACATION_BUTTON_CLASS',
			self::SIGN_B2E_MY_DOCUMENTS_SALARY_AND_VACATION_BUTTON_CLASS,
		);

		$this->setParam('IS_B2E_FROM_EMPLOYEE_ENABLED', Feature::instance()->isSendDocumentByEmployeeEnabled());
		$this->setResult('SEND_DOCUMENT_BY_EMPLOYEE_ANALYTIC_CONTEXT', $this->getSendDocumentByEmployeeAnalyticContext());
		$this->setParam(
			'IS_B2E_AVAILIBLE_IN_CURENT_TARIFF',
			!B2eTariff::instance()->isB2eRestrictedInCurrentTariff(),
		);

		$this->setParam('GRID_ID', self::DEFAULT_GRID_ID);
		$this->setParam('COLUMNS', $this->getGridColumnList());
		$this->setParam(self::PARAM_FILTER_ID, self::DEFAULT_FILTER_ID);

		$this->setResult('TITLE', Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_TITLE'));
		$this->setResult(self::RESULT_FILTER, $this->getFilters());
		$this->setResult(self::RESULT_FILTER_PRESETS, $this->getFilterPresets($userId));

		$filter = $this->getFilterFromRequest();
		$this->setResult('PAGE_NAVIGATION', $this->getPageNavigation($userId, $filter));
		$this->setResult('TOTAL_COUNT', $this->myDocumentService->getTotalCountMembers($userId, $filter));

		$gridData = $this->myDocumentService->getGridData(
			$this->getLimitForQuery(),
			$this->getOffsetForQuery(),
			$userId,
			$filter,
		);
		$this->setResult('DOCUMENTS', $gridData);
		$this->setResult('COUNTER_ITEMS', $this->getCounterItems($userId, $filter));
		$this->setResult('NEED_ACTION_COUNTER_ID', $this->getNeedActionCounterId());

		$pullEventName = $this->counterService->getPullEventName(CounterType::SIGN_B2E_MY_DOCUMENTS);
		$this->setResult('COUNTER_PULL_EVENT_NAME', $pullEventName);
	}

	private function getGridColumnList(): array
	{
		return [
			[
				'id' => 'ID',
				'name' => (string)Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_LIST_COLUMN_ID'),
				'default' => false,
			],
			[
				'id' => 'TITLE',
				'name' => (string)Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_LIST_COLUMN_TITLE'),
				'default' => true,
			],
			[
				'id' => 'MEMBERS',
				'name' => (string)Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_LIST_COLUMN_MEMBERS'),
				'default' => true,
			],
			[
				'id' => 'ACTION',
				'name' => (string)Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_LIST_COLUMN_ACTION'),
				'default' => true,
			],
		];
	}

	private function getFilters(): array
	{
		return [
			MyDocumentsFilterFactory::ROLE => [
				'id' => MyDocumentsFilterFactory::ROLE,
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_MY_ROLE'),
				'type' => 'list',
				'default' => true,
				'items' => $this->getMyRoleItems(),
			],
			MyDocumentsFilterFactory::INITIATOR => [
				'id' => MyDocumentsFilterFactory::INITIATOR,
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FROM_WHOM'),
				'type' => 'entity_selector',
				'partial' => true,
				'default' => true,
				'params' => $this->getUserEntitySelectorDefaultParams(),
			],
			MyDocumentsFilterFactory::EDITOR => [
				'id' => MyDocumentsFilterFactory::EDITOR,
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_EDITOR'),
				'type' => 'entity_selector',
				'partial' => true,
				'default' => true,
				'params' => $this->getUserEntitySelectorDefaultParams(),
			],
			MyDocumentsFilterFactory::REVIEWER => [
				'id' => MyDocumentsFilterFactory::REVIEWER,
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_REVIEWER'),
				'type' => 'entity_selector',
				'partial' => true,
				'default' => true,
				'params' => $this->getUserEntitySelectorDefaultParams(),
			],
			MyDocumentsFilterFactory::SIGNER => [
				'id' => MyDocumentsFilterFactory::SIGNER,
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_SIGNER'),
				'type' => 'entity_selector',
				'partial' => true,
				'default' => true,
				'params' => $this->getUserEntitySelectorDefaultParams(),
			],
			MyDocumentsFilterFactory::STATUS => [
				'id' => MyDocumentsFilterFactory::STATUS,
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_STATUS'),
				'type' => 'list',
				'default' => true,
				'items' => [
					FilterStatus::SIGNED->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_STATUS_SIGNED'),
					FilterStatus::IN_PROGRESS->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_STATUS_IN_PROGRESS'),
					FilterStatus::NEED_ACTION->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_STATUS_NEED_ACTION'),
					FilterStatus::MY_REVIEW->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_STATUS_MY_REVIEW'),
					FilterStatus::MY_SIGNED->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_STATUS_MY_SIGNED'),
					FilterStatus::MY_EDITED->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_STATUS_MY_EDITED'),
					FilterStatus::MY_STOPPED->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_STATUS_MY_STOPPED'),
					FilterStatus::STOPPED->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_STATUS_STOPPED'),
				],
				'params' => [
					'multiple' => 'Y',
				],
			],
			MyDocumentsFilterFactory::DATE_MODIFY => [
				'id' => MyDocumentsFilterFactory::DATE_MODIFY,
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_DATE_MODIFY'),
				'type' => 'date',
				'default' => false,
			],
			MyDocumentsFilterFactory::COMPANY => [
				'id' => MyDocumentsFilterFactory::COMPANY,
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_COMPANY'),
				'type' => 'entity_selector',
				'partial' => true,
				'default' => true,
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 240,
						'dropdownMode' => false,
						'entities' => [
							[
								'id' => 'sign-mycompany',
								'dynamicLoad' => true,
								'dynamicSearch' => true,
								'options' => [
									'enableMyCompanyOnly' => true,
								],
							],
						],
					],
				],
			],
		];
	}

	private function getFilterPresets(int $userId): array
	{
		$isSignedDefault = $this->myDocumentService->isSignedExists($userId)
			&& !$this->myDocumentService->isInProgressExists($userId);

		$this->replaceDefaultPresetIfNeed($isSignedDefault);

		$presets = [
			self::FILTER_PRESET_SIGNED => [
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_PRESET_SIGNED'),
				'default' => $isSignedDefault,
				'fields' => [
					MyDocumentsFilterFactory::STATUS => [FilterStatus::SIGNED->value],
				],
			],
			self::FILTER_PRESET_IN_PROGRESS => [
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_PRESET_IN_PROGRESS'),
				'default' => !$isSignedDefault,
				'fields' => [
					MyDocumentsFilterFactory::STATUS => [FilterStatus::IN_PROGRESS->value],
				],
			],
			'preset_need_action' => [
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_PRESET_NEED_ACTION'),
				'default' => false,
				'fields' => [
					MyDocumentsFilterFactory::STATUS => [FilterStatus::NEED_ACTION->value],
				],
			],
			'preset_my_action_done' => [
				'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_PRESET_MY_ACTION_DONE'),
				'default' => false,
				'fields' => [
					MyDocumentsFilterFactory::STATUS => [
						FilterStatus::MY_SIGNED->value,
						FilterStatus::MY_REVIEW->value,
						FilterStatus::MY_EDITED->value,
						FilterStatus::MY_STOPPED->value,
					],
				],
			],
		];

		if (Feature::instance()->isSendDocumentByEmployeeEnabled())
		{
			$presets += [
				'preset_sent' => [
					'name' => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_FILTER_PRESET_SENT'),
					'default' => false,
					'fields' => [
						MyDocumentsFilterFactory::ROLE => ActorRole::INITIATOR->value,
					],
				],
			];
		}

		return $presets;
	}

	private function getFilterOptions(): Filter\Options
	{
		return new Filter\Options(
			$this->getParam(self::PARAM_FILTER_ID),
			$this->getResult(self::RESULT_FILTER_PRESETS)
		);
	}

	private function getRequestFilters(): array
	{
		return $this->getFilterOptions()->getFilter($this->getResult(self::RESULT_FILTER));
	}

	private function getFilterFromRequest(): ?MyDocumentsFilter
	{
		return (new MyDocumentsFilterFactory())
			->createFromRequestFilterOptions(
				(int)CurrentUser::get()->getId(),
				$this->getRequestFilters(),
			);
	}

	private function getUserEntitySelectorDefaultParams(): array
	{
		return [
			'multiple' => 'Y',
			'dialogOptions' => [
				'height' => 240,
				'context' => 'filter',
				'entities' => [
					[
						'id' => 'user',
						'options' => [
							'inviteEmployeeLink' => false,
						],
					],
				],
			],
		];
	}

	private function getCounterItems(int $userId, ?MyDocumentsFilter $filter = null): array
	{
		$needActionCount = $this->myDocumentService->getTotalCountNeedAction($userId);

		$isActive = $filter?->isFilterOnlyNeedAction() ?? false;

		return [
			[
				'id' => $this->getNeedActionCounterId(),
				'title' => Loc::getMessage('SIGN_MY_DOCUMENT_LIST_GRID_COUNTER_NEED_ACTION'),
				'value' => $needActionCount,
				'color' => $needActionCount > 0 ? 'DANGER' : 'THEME',
				'isRestricted' => false,
				'isActive' => $isActive,
			],
		];
	}

	private function replaceDefaultPresetIfNeed(bool $isSignedDefault): void
	{
		$options = $this->getFilterOptions();
		$defaultPreset = $options->getDefaultFilterId();
		if ($isSignedDefault && $defaultPreset === self::FILTER_PRESET_IN_PROGRESS)
		{
			$options->setDefaultPreset(self::FILTER_PRESET_SIGNED);
			$options->save();
		}
		elseif (!$isSignedDefault && $defaultPreset === self::FILTER_PRESET_SIGNED)
		{
			$options->setDefaultPreset(self::FILTER_PRESET_IN_PROGRESS);
			$options->save();
		}
	}

	private function getNeedActionCounterId(): string
	{
		return MyDocumentsFilterFactory::STATUS . self::COUNTER_PANEL_DELIMITER . FilterStatus::NEED_ACTION->value;
	}

	/**
	 * @return array<string, string>
	 */
	private function getMyRoleItems(): array
	{
		$items = [];
		if (Feature::instance()->isSendDocumentByEmployeeEnabled())
		{
			$items += [ActorRole::INITIATOR->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_MY_ROLE_SENDER')];
		}

		$items += [
			ActorRole::SIGNER->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_MY_ROLE_SIGNER'),
			ActorRole::ASSIGNEE->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_MY_ROLE_ASSIGNEE'),
			ActorRole::REVIEWER->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_MY_ROLE_REVIEWER'),
			ActorRole::EDITOR->value => Loc::getMessage('SIGN_B2E_MY_DOCUMENTS_MY_ROLE_EDITOR'),
		];

		return $items;
	}

	private function getSendDocumentByEmployeeAnalyticContext(): array
	{
		$requestedPage = Application::getInstance()->getContext()->getRequest()->getRequestedPage();
		$cSection = null;
		if (str_starts_with($requestedPage, '/sign/b2e/my-documents'))
		{
			$cSection = 'sign';
		}
		elseif (str_starts_with($requestedPage, '/company/personal/user/'))
		{
			$cSection = 'profile';
		}

		return [
			'category' => 'documents',
			'c_section' => $cSection,
			'type' => 'from_employee',
		];
	}
}