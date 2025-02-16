<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\SmartDocument;
use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Session\SessionInterface;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Config\Feature;
use Bitrix\Sign\Item\B2e\KanbanCategory;
use Bitrix\Sign\Item\B2e\KanbanCategoryCollection;
use Bitrix\Sign\Service\Sign\UrlGeneratorService;
use Bitrix\Sign\Type\CounterType;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\Sign\Service\Container as SignContainer;

\CBitrixComponent::includeComponentClass('bitrix:sign.base');

class SignStartComponent extends SignBaseComponent
{
	private const SIGN_B2E_SESSION_CATEGORY_ID_NAME = 'SIGN_B2E_SESSION_CATEGORY_ID_NAME';
	private const SIGN_B2E_SESSION_PATH_VALUE = 'SIGN_B2E_SESSION_PATH_VALUE';
	private const SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE = 'SIGN_B2E_EMPLOYEE_ITEM_CATEGORY';
	private const SIGN_B2E_CRM_DIRECTION_BUTTON_CSS_CLASS = 'ui-toolbar-btn-dropdown';
	private const SIGN_B2E_HIDDEN_BUTTON_CSS_CLASS = 'ui-btn-dropdown-hidden';
	private const SIGN_B2E_CLASS_FOR_ONBOARDING_ROUTE = 'sign-b2e-onboarding-route';

	/**
	 * Section menu item index.
	 * @var string|null
	 */
	private static $menuIndex = null;

	/**
	 * Required params of component.
	 * If not specified, will be set to null.
	 * @var string[]
	 */
	protected static array $requiredParams = [
		'SEF_FOLDER'
	];

	/**
	 * Default sef urls.
	 * @var string[]
	 */
	private array $defaultUrlTemplates = [
		'main_page' => '',
		'kanban' => 'kanban/',
		'list' => 'list/',
		'mysafe' => 'mysafe/',
		'b2e_mysafe' => 'b2e/mysafe/',
		'b2e_employee_template_list' => 'b2e/employee/templates',
		'contact' => 'contact/',
		'b2e_kanban' => 'b2e/',
		'b2e_list' => 'b2e/list/',
		'b2e_my_documents' => 'b2e/my-documents/',
		'config_permissions' => 'config/permission/',
		'document' => 'doc/#doc_id#/',
		'b2e_document' => 'b2e/doc/#doc_id#/',
		'edit' => 'edit/#doc_id#/',
		'b2e_settings' => 'b2e/settings/',
		'b2e_member_dynamic_settings' => 'b2e/member_dynamic_settings/',
		'b2e_preview' => 'b2e/preview/#doc_id#/',
	];

	/**
	 * Map of url and their params.
	 * @var array
	 */
	private array $urlWithVariables = [
		'main_page' => [],
		'kanban' => [],
		'list' => [],
		'mysafe' => [],
		'b2e_kanban' => [],
		'b2e_list' => [],
		'b2e_mysafe' => [],
		'b2e_employee_template_list' => [],
		'b2e_my_documents' => [],
		'contact' => [],
		'config_permissions' => [],
		'document' => ['doc_id'],
		'b2e_document' => ['doc_id'],
		'edit' => ['doc_id'],
		'b2e_settings' => [],
		'b2e_member_dynamic_settings' => [],
		'b2e_preview' => ['doc_id'],
	];

	/**
	 * Map between crm and local urls.
	 * @var string[]
	 */
	private array $crmUrls = [
		'bitrix:crm.document.details' => 'PAGE_URL_DOCUMENT',
		'bitrix:crm.item.kanban' => 'PAGE_URL_KANBAN',
		'bitrix:crm.item.list' => 'PAGE_URL_LIST'
	];

	public function executeComponent(): void
	{
		if (!Loader::includeModule('sign'))
		{
			showError('Sign module is not installed');

			return;
		}

		if ($this->isB2eKanbanOrList())
		{
			if (
				!Feature::instance()->isSendDocumentByEmployeeEnabled()
				&& $this->getCategory()?->code === self::SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE
			)
			{
				showError('access denied');

				return;
			}

			if ($this->getCategoryIdFromRequest())
			{
				$this->getSession()->set(self::SIGN_B2E_SESSION_PATH_VALUE, $this->getUrlPath());
			}
		}

		parent::executeComponent();

		if ($this->isB2eKanbanOrList() && $this->removeButtons())
		{
			$this->addCategorySelectButton();
		}
	}

	/**
	 * Resolves complex component's URLs.
	 * @return void
	 */
	private function resolveTemplate(): void
	{
		// if sef mode is ON
		if ($this->getParam('SEF_MODE') === 'Y')
		{
			// merge default paths with custom
			$urlTemplates = array_merge(
				$this->defaultUrlTemplates,
				$this->getArrayParam('SEF_URL_TEMPLATES')
			);

			// resolve template page
			$componentPage = \CComponentEngine::parseComponentPath(
				$this->getStringParam('SEF_FOLDER'),
				$urlTemplates,
				$variables
			);

			// init variables
			\CComponentEngine::initComponentVariables($componentPage, [], [], $variables);

			// build urls by rules
			foreach ($this->urlWithVariables as $code => $var)
			{
				$this->setParam(
					'PAGE_URL_' . mb_strtoupper($code),
					$this->getStringParam('SEF_FOLDER') . $urlTemplates[$code]
				);
			}
		}
		// if sef mode is OFF
		else
		{
			// collect all expected variables
			$defaultVariableAliases = [
				'page' => 'page'
			];
			foreach ($this->urlWithVariables as $vars)
			{
				foreach ($vars as $var)
				{
					$defaultVariableAliases[$var] = $var;
				}
			}

			// merge default variables with custom
			$variableAliases = array_merge(
				$defaultVariableAliases,
				$this->getArrayParam('VARIABLE_ALIASES')
			);

			// init variables
			\CComponentEngine::initComponentVariables(
				false,
				$defaultVariableAliases,
				$variableAliases,
				$variables
			);

			// resolve template page
			$varPage = $variableAliases['page'];
			$componentPage = $variables['page'] ?? '';
			if (!($this->urlWithVariables[$componentPage] ?? null))
			{
				$componentPage = '';
			}

			// build urls by rules
			foreach ($this->urlWithVariables as $code => $vars)
			{
				$paramCode = 'PAGE_URL_' . mb_strtoupper($code);
				$uri = new Uri($this->getRequestedPage());
				$uri->addParams([$varPage => $code]);

				foreach ($vars as $var)
				{
					if (isset($variableAliases[$var]))
					{
						$uri->addParams([$variableAliases[$var] => '#' . $var . '#']);
					}
				}

				$this->setParam($paramCode, urldecode($uri->getUri()));
			}
		}

		// set variables to params
		if ($componentPage)
		{
			foreach ($this->urlWithVariables[$componentPage] as $var)
			{
				$this->setParam('VAR_' . mb_strtoupper($var), null);
			}
		}
		foreach ($variables as $code => $var)
		{
			$this->setParam('VAR_' . mb_strtoupper($code), $var);
		}

		$this->setTemplate($componentPage ?: (array_keys($this->urlWithVariables)[0] ?? ''));
	}

	/**
	 * Sets new custom urls in some CRM's places.
	 * @return void
	 */
	private function subscribeOnEventsToReplaceCrmUrls(): void
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->addEventHandler(
			'crm',
			'onGetUrlForTemplateRouter',
			function(\Bitrix\Main\Event $event)
			{
				$componentName = $event->getParameter('componentName');
				$parameters = $event->getParameter('parameters');
				$entityId = $parameters['ENTITY_ID'] ?? null;

				if ($componentName === 'bitrix:crm.document.details' && ($entityId ?? 0) === 0)
				{
					return new \Bitrix\Main\Web\Uri(
						str_replace('#doc_id#', 0, $this->arParams[$this->crmUrls[$componentName]])
					);
				}
				else if ($componentName === 'bitrix:crm.document.details'
					&& ($entityId > 0)
					&& ($parameters['ENTITY_TYPE_ID'] === \CCrmOwnerType::SmartB2eDocument)
				)
				{
					return new \Bitrix\Main\Web\Uri(
						'/crm/type/' . \CCrmOwnerType::SmartB2eDocument . "/details/$entityId/"
					);
				}
				else if ($componentName === 'bitrix:crm.item.list' || $componentName === 'bitrix:crm.item.kanban')
				{
					return new \Bitrix\Main\Web\Uri($this->arParams[$this->crmUrls[$componentName]]);
				}
			}
		);
		$eventManager->addEventHandler(
			'crm',
			'onGetComponentItemListAddBtnParameters',
			Closure::fromCallable([$this, 'onCrmGetComponentItemListAddBtnParameters'])
		);
	}

	/**
	 * Sets section menu item index.
	 * @param string $code Menu item code.
	 * @return void
	 */
	public function setMenuIndex(string $code): void
	{
		$this::$menuIndex = $code;
	}

	/**
	 * Returns section menu item index.
	 * @return string|null
	 */
	public function getMenuIndex(): ?string
	{
		return $this::$menuIndex;
	}

	private function prepareMenuItems(): void
	{
		$page = $this->getRequestedPage();
		$storage = \Bitrix\Sign\Config\Storage::instance();
		$isB2eActive = str_starts_with($page, '/sign/b2e/') && $storage->isB2eAvailable();
		$items = match (true)
		{
			// im not sure about this condition
			$isB2eActive => $this->getB2eMenuItems(),
			default => $this->getB2bMenuItems(),
		};

		if (
			$isB2eActive
			&& !$this->hasB2eKanbanMenuItem($items)
			&& $page === '/sign/b2e/'
		)
		{
			LocalRedirect("/sign/b2e/my-documents/");
		}

		$categoryIdFromRequest = $this->getCategoryIdFromRequest();
		if (
			$this->isB2eKanbanOrList()
			&& !$categoryIdFromRequest
			&& $this->getKanbanCategoryCollection()->isManyCategoriesAvailable()
			&& Feature::instance()->isSendDocumentByEmployeeEnabled()
		)
		{
			$categoryIdFromSession = (int)$this->getSession()->get(self::SIGN_B2E_SESSION_CATEGORY_ID_NAME);

			$path = $this->getUrlPath();
			if (!$this->isB2ePath($this->getRefererPath()))
			{
				$path = (string)$this->getSession()->get(self::SIGN_B2E_SESSION_PATH_VALUE);
			}

			$this->getSession()->set(self::SIGN_B2E_SESSION_PATH_VALUE, '');
			$category = $this->getKanbanCategoryCollection()->findById($categoryIdFromSession);
			if (!$category)
			{
				$category = $this->getKanbanCategoryCollection()->getDefaultCategory();
			}

			$categoryId = $category?->id ?? 0;
			if ($categoryId)
			{
				$redirectUrl = $this->getB2eUrl($path, $categoryId);
				LocalRedirect($redirectUrl);
			}
		}

		$this->arParams['MENU_ITEMS'] = $items;
	}

	private function isB2eKanbanOrList(): bool
	{
		return $this->isB2ePath($this->getUrlPath());
	}

	private function isB2ePath(string $path): bool
	{
		return in_array($path, [UrlGeneratorService::B2E_KANBAN_URL, UrlGeneratorService::B2E_LIST_URL], true);
	}

	private function getB2eUrl(string $path, int $categoryId): string
	{
		$urlGeneratorService = SignContainer::instance()->getUrlGeneratorService();

		return match ($path)
		{
			UrlGeneratorService::B2E_LIST_URL => $urlGeneratorService->makeB2eListCategoryUrl($categoryId),
			default => $urlGeneratorService->makeB2eKanbanCategoryUrl($categoryId),
		};
	}

	private function getUrlPath(): string
	{
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
		$page = $request->getRequestUri();

		return (string)parse_url($page, PHP_URL_PATH);
	}

	private function getRefererPath(): string
	{
		$context = Application::getInstance()->getContext();
		$referer = (string)$context->getServer()->getRaw('HTTP_REFERER');

		return (string)parse_url($referer, PHP_URL_PATH);
	}

	/**
	 * Executes component.
	 * @return void
	 */
	public function exec(): void
	{
		$this->resolveTemplate();
		$this->subscribeOnEventsToReplaceCrmUrls();
		$this->prepareMenuItems();
		$this->setParam('ENTITY_ID', \Bitrix\Sign\Document\Entity\Smart::getEntityTypeId());
	}

	private function onCrmGetComponentItemListAddBtnParameters(\Bitrix\Main\Event $event): ?\Bitrix\Main\EventResult
	{
		$entityTypeId = $event->getParameter('entityTypeId');
		$btnParameters = $event->getParameter('btnParameters');
		if (
			!is_int($entityTypeId)
			|| !is_array($btnParameters)
			|| !array_key_exists('link', $btnParameters)
			|| !is_string($btnParameters['link'])
		)
		{
			return null;
		}
		if (\Bitrix\Sign\Document\Entity\SmartB2e::getEntityTypeId() !== $entityTypeId)
		{
			return null;
		}

		$btnParameters['link'] = CComponentEngine::makePathFromTemplate(
			$this->getStringParam('SEF_FOLDER') . $this->defaultUrlTemplates['b2e_document'],
			['doc_id' => 0],
		);

		return new \Bitrix\Main\EventResult(\Bitrix\Main\EventResult::SUCCESS, $btnParameters, 'sign');
	}

	private function getB2bMenuItems(): array
	{
		$userPermissions = \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions();
		$contactCategoryId = Container::getInstance()
			->getFactory(CCrmOwnerType::Contact)
			?->getCategoryByCode(SmartDocument::CONTACT_CATEGORY_CODE)
			?->getId()
		;

		$canReadSmartDocumentContact = $userPermissions->checkReadPermissions(
			CCrmOwnerType::Contact,
			0,
			$contactCategoryId
		);
		$items = [];

		$items[] = [
			'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_INDEX_1'),
			'URL' => $this->arParams['PAGE_URL_MAIN_PAGE'],
			'ID' => 'sign_index',
			'URL_CONSTANT' => true,
		];
		if ($this->accessController->check(ActionDictionary::ACTION_MY_SAFE))
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_MYSAFE'),
				'URL' => $this->arParams['PAGE_URL_MYSAFE'],
				'ID' => 'sign_mysafe',
				'COUNTER' => 0,
				'COUNTER_ID' => 'sign_mysafe',
			];
		}

		if (
			$contactCategoryId
			&& $canReadSmartDocumentContact
		)
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_CONTACTS'),
				'URL' => $this->arParams['PAGE_URL_CONTACT'],
				'ID' => 'sign_contacts',
				'COUNTER' => 0,
				'COUNTER_ID' => 'sign_contacts',
			];
		}
		if ($this->accessController->check(ActionDictionary::ACTION_ACCESS_RIGHTS))
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_CONFIG_PERMISSIONS'),
				'URL' => $this->arParams['PAGE_URL_CONFIG_PERMISSIONS'],
				'ID' => 'sign_config_permission',
				'COUNTER' => 0,
				'COUNTER_ID' => 'sign_config_permission',
			];
		}

		return $items;
	}

	private function getB2eMenuItems(): array
	{
		$items = [];
		if ($this->getAccessController()->check(ActionDictionary::ACTION_B2E_DOCUMENT_READ))
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_KANBAN_MSGVER_1'),
				'ID' => 'sign_b2e_kanban',
				'URL' => '/sign/b2e/',
				'COUNTER' => 0,
				'COUNTER_ID' => 'sign_b2e_kanban',
			];
		}

		$userId = (int)CurrentUser::get()->getId();
		$counter = SignContainer::instance()
			->getCounterService()
			->get(CounterType::SIGN_B2E_MY_DOCUMENTS, $userId)
		;

		$items[] = [
			'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_MY_DOCUMENTS'),
			'URL' => $this->arParams['PAGE_URL_B2E_MY_DOCUMENTS'],
			'ID' => 'sign_b2e_my_documents',
			'COUNTER' => $counter,
			'COUNTER_ID' => CounterType::SIGN_B2E_MY_DOCUMENTS->value,
		];

		if ($this->accessController->check(ActionDictionary::ACTION_B2E_MY_SAFE))
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_SAFE'),
				'URL' => $this->arParams['PAGE_URL_B2E_MYSAFE'],
				'ID' => 'sign_b2e_mysafe',
				'COUNTER' => 0,
				'COUNTER_ID' => 'sign_b2e_mysafe',
			];
		}

		if (
			SignContainer::instance()->getHcmLinkService()->isAvailable()
			&& $this->accessController->check(ActionDictionary::ACTION_B2E_DOCUMENT_ADD)
		)
		{
			$companyCounterService = \Bitrix\HumanResources\Service\Container::getHcmLinkCompanyCounterService();

			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_HCMLINK_INTEGRATION'),
				'URL' => '/hr/hcmlink/companies/',
				'ID' => 'hr_hcmlink_companies',
				'COUNTER' => $companyCounterService->get(),
				'COUNTER_ID' => $companyCounterService->getCounterId(),
				'TOUR' => [
					'id' => 'sign-tour-guide-menu-hcmlink',
					'targetId' => '#sign_hr_hcmlink_companies',
					'title' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_HCMLINK_INTEGRATION_TOUR_TITLE'),
					'description' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_HCMLINK_INTEGRATION_TOUR_DESCRIPTION'),
					'articleCode' => '23264608',
				],
			];
		}

		if (
			Feature::instance()->isSendDocumentByEmployeeEnabled()
			&& $this->accessController->check(ActionDictionary::ACTION_B2E_TEMPLATE_READ)
		)
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_EMPLOYEE_TEMPLATE_LIST'),
				'URL' => $this->arParams['PAGE_URL_B2E_EMPLOYEE_TEMPLATE_LIST'] ?? '',
				'ID' => 'sign_b2e_employee_template_list'
			];
		}

		if ($settingsSubitems = $this->getB2eSettingsItems())
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_SETTINGS_MSGVER_1'),
				'ID' => 'sign_b2e_settings_sub',
				'ITEMS' => $settingsSubitems,
			];
		}

		if ($this->accessController->check(ActionDictionary::ACTION_ACCESS_RIGHTS))
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_CONFIG_PERMISSIONS'),
				'URL' => $this->arParams['PAGE_URL_CONFIG_PERMISSIONS'],
				'ID' => 'sign_config_permission',
				'COUNTER' => 0,
				'COUNTER_ID' => 'sign_config_permission',
			];
		}

		return $items;
	}

	private function hasB2eKanbanMenuItem(array $items): bool
	{
		return !empty(array_filter($items, static fn (array $item): bool => $item['ID'] === 'sign_b2e_kanban'));
	}

	private function getB2eSettingsItems(): array
	{
		$items = [];

		if ($this->accessController->check(ActionDictionary::ACTION_B2E_PROFILE_FIELDS_DELETE))
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_LEGAL_SETTINGS'),
				'URL' => $this->arParams['PAGE_URL_B2E_SETTINGS'],
				'ID' => 'sign_b2e_settings',
				'COUNTER' => 0,
				'COUNTER_ID' => 'sign_b2e_settings',
			];
		}

		if (
			Feature::instance()->isSendDocumentByEmployeeEnabled()
			&& $this->accessController->check(ActionDictionary::ACTION_B2E_MEMBER_DYNAMIC_FIELDS_DELETE)
		)
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_MEMBER_DYNAMIC_SETTINGS'),
				'URL' => $this->arParams['PAGE_URL_B2E_MEMBER_DYNAMIC_SETTINGS'] ?? '',
				'ID' => 'sign_b2e_member_dynamic_settings',
			];
		}

		return $items;
	}

	private function removeButtons(): bool
	{
		$category = $this->getCategory();
		$isNeedToRemove = ($category && !$category->isDefault());

		Toolbar::deleteButtons(
			fn(Button $button, string $location): bool => $location === ButtonLocation::AFTER_TITLE
				&& ($button->hasClass(self::SIGN_B2E_CRM_DIRECTION_BUTTON_CSS_CLASS) || $isNeedToRemove)
		);

		foreach (Toolbar::getButtons() as $button)
		{
			if ($button->hasClass(self::SIGN_B2E_CRM_DIRECTION_BUTTON_CSS_CLASS))
			{
				$button->addClass(self::SIGN_B2E_HIDDEN_BUTTON_CSS_CLASS);

				return false;
			}
		}

		return true;
	}

	private function addCategorySelectButton(): void
	{
		if (!Feature::instance()->isSendDocumentByEmployeeEnabled())
		{
			return;
		}

		if (!$this->getKanbanCategoryCollection()->isManyCategoriesAvailable())
		{
			return;
		}

		$category = $this->getCategory();
		$code = $category?->code ?? '';
		$title = $this->getCategoryButtonTranslationByCode($code);
		$button = new Button();
		$button->setText($title);
		$button->addClass(self::SIGN_B2E_CLASS_FOR_ONBOARDING_ROUTE);
		$button->addClass('ui-btn-light-border');
		$button->addClass($this->getCategoryButtonIconClassByCode($code));
		$button->setDropdown(true);
		$button->setMenu($this->getB2eKanbanButtonMenuItems());

		Toolbar::addButton($button, ButtonLocation::AFTER_TITLE);
	}

	private function getCategoryButtonIconClassByCode(string $code): string
	{
		return $code === self::SIGN_B2E_EMPLOYEE_ITEM_CATEGORY_CODE
			? 'ui-btn-icon-kanban-employee-category'
			: 'ui-btn-icon-kanban-category';
	}

	private function getB2eKanbanButtonMenuItems(): array
	{
		$categories = $this->getKanbanCategoryCollection();
		$items = [];
		$categoryCodesForMenu = SignContainer::instance()
			->getB2eKanbanCategoryService()
			->getSmartB2eDocumentCategoryCodesForMenu()
		;

		foreach ($categories as $category)
		{
			$categoryId = $category->id ?? 0;

			if ($categoryId < 1)
			{
				continue;
			}

			$categoryCode = $category?->code ?? null;
			if ($categoryCode === null)
			{
				continue;
			}

			if (!in_array($categoryCode, $categoryCodesForMenu, true))
			{
				continue;
			}

			$items[] = [
				'text' => $this->getCategoryButtonTranslationByCode($categoryCode),
				'href' => $this->getB2eUrl($this->getUrlPath(), $categoryId),
				'id' => 'sign_b2e_kanban_' . $categoryId,
			];
		}

		return ['items' => $items];
	}

	private function getCategoryButtonTranslationByCode(string $code): string
	{
		return Loc::getMessage('SIGN_KANBAN_BUTTON_TITLE_' . $code) ?? '';
	}

	private function getCategory(): ?KanbanCategory
	{
		$categoryIdFromRequest = $this->getCategoryIdFromRequest();
		$categoryIdFromSession = (int)$this->getSession()->get(self::SIGN_B2E_SESSION_CATEGORY_ID_NAME);
		$category = $this->getKanbanCategoryCollection()->findById($categoryIdFromRequest);
		if (!$category)
		{
			$category = $this->getKanbanCategoryCollection()->getDefaultCategory();
		}

		if ($category && $categoryIdFromRequest && $category->id !== $categoryIdFromSession)
		{
			$this->getSession()->set(self::SIGN_B2E_SESSION_CATEGORY_ID_NAME, $category->id);
		}

		return $category;
	}

	private function getSession(): SessionInterface
	{
		return Application::getInstance()->getSession();
	}

	private function getCategoryIdFromRequest(): int
	{
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
		$result = $request->get('categoryId');

		return is_array($result) ? 0 : (int)$result;
	}

	private function getKanbanCategoryCollection(): KanbanCategoryCollection
	{
		return	SignContainer::instance()
			->getB2eKanbanCategoryService()
			->getSmartB2eDocumentCategories()
		;
	}
}
