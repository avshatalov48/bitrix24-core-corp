<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory\SmartDocument;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Config\Feature;

\CBitrixComponent::includeComponentClass('bitrix:sign.base');

class SignStartComponent extends SignBaseComponent
{
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
		'b2e_current' => 'b2e/current/',
		'config_permissions' => 'config/permission/',
		'document' => 'doc/#doc_id#/',
		'b2e_document' => 'b2e/doc/#doc_id#/',
		'edit' => 'edit/#doc_id#/',
		'b2e_settings' => 'b2e/settings/',
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
		'b2e_current' => [],
		'contact' => [],
		'config_permissions' => [],
		'document' => ['doc_id'],
		'b2e_document' => ['doc_id'],
		'edit' => ['doc_id'],
		'b2e_settings' => [],
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
			LocalRedirect('/sign/b2e/current/');
		}

		$this->arParams['MENU_ITEMS'] = $items;
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
		if ($this->accessController->check(ActionDictionary::ACTION_B2E_DOCUMENT_READ))
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_KANBAN_MSGVER_1'),
				'URL' => $this->arParams['PAGE_URL_B2E_KANBAN'],
				'ID' => 'sign_b2e_kanban',
				'COUNTER' => 0,
				'COUNTER_ID' => 'sign_b2e_kanban',
			];
		}

		$counterId = \Bitrix\Sign\Service\Container::instance()
			->getB2eUserToSignDocumentCounterService()
			->getCounterId()
		;
		$counter = \Bitrix\Sign\Service\Container::instance()
			->getB2eUserToSignDocumentCounterService()
			->get()
		;

		$items[] = [
			'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_CURRENT_DOCUMENT'),
			'URL' => $this->arParams['PAGE_URL_B2E_CURRENT'],
			'ID' => 'sign_b2e_current',
			'COUNTER' => $counter,
			'COUNTER_ID' => $counterId,
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
			Feature::instance()->isSendDocumentByEmployeeEnabled()
			&& $this->accessController->check(ActionDictionary::ACTION_B2E_DOCUMENT_READ)
		)
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_EMPLOYEE_TEMPLATE_LIST'),
				'URL' => $this->arParams['PAGE_URL_B2E_EMPLOYEE_TEMPLATE_LIST'] ?? '',
				'ID' => 'sign_b2e_employee_template_list'
			];
		}

		if ($this->accessController->check(ActionDictionary::ACTION_B2E_PROFILE_FIELDS_DELETE))
		{
			$items[] = [
				'TEXT' => Loc::getMessage('SIGN_CMP_START_TPL_MENU_B2E_SETTINGS'),
				'URL' => $this->arParams['PAGE_URL_B2E_SETTINGS'],
				'ID' => 'sign_b2e_settings',
				'COUNTER' => 0,
				'COUNTER_ID' => 'sign_b2e_settings',
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
}
