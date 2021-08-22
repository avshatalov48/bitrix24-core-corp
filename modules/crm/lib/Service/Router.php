<?php
namespace Bitrix\Crm\Service;

use Bitrix\Crm\Integration\IntranetManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Router\ParseResult;
use Bitrix\Crm\Settings\EntityViewSettings;
use Bitrix\Intranet\Util;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Response\DataType\ContentUri;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Loader;
use Bitrix\Main\SiteTable;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

class Router
{
	public const LIST_VIEW_KANBAN = EntityViewSettings::KANBAN_VIEW_NAME;
	public const LIST_VIEW_LIST = EntityViewSettings::LIST_VIEW_NAME;

	protected const GET_COMPONENT_NAME = 'c';
	protected const GET_COMPONENT_PARAMETERS = 'cp';
	protected const GET_COMPONENT_TEMPLATE = 'cpt';
	protected const OPTION_DEFAULT_ROOT = 'crm_default_root';
	protected const OPTION_NAME_TEMPLATES = 'crm_url_templates';

	protected const OPTION_CATALOG_PRODUCT_SHOW = 'path_to_product_show';
	protected const OPTION_CATALOG_PRODUCT_EDIT = 'path_to_product_edit';

	protected const MODULE_ID = 'crm';
	protected const DEFAULT_ROOT = '/' . self::MODULE_ID . '/';

	protected $siteId;
	protected $root;
	protected $isSefMode;
	protected $siteData;
	protected $customRoots = [];
	protected $defaultComponent = 'bitrix:crm.type.list';
	protected $defaultComponentParameters = [];

	public function __construct()
	{
		$this->root = $this->getDefaultRoot();
		$this->isSefMode = true;
		$this->siteId = Application::getInstance()->getContext()->getSite();

		$this->initCustomRoots();
	}

	/**
	 * Re-initialize router
	 *
	 * @return $this
	 */
	public function reInit(): self
	{
		$this->initCustomRoots();

		return $this;
	}

	public function getUserPersonalUrlTemplate(): ?string
	{
		return Option::get('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $this->getSiteId());
	}

	public function getUserPersonalUrl(int $userId): Uri
	{
		$template = $this->getUserPersonalUrlTemplate();

		return new Uri(str_replace('#USER_ID#', $userId, $template));
	}

	public function setRoot(string $root): Router
	{
		$this->root = $root;

		return $this;
	}

	public function getRoot(): string
	{
		return $this->root;
	}

	public function setSefMode(bool $isSefMode): Router
	{
		$this->isSefMode = $isSefMode;

		return $this;
	}

	public function isSefMode(): bool
	{
		return $this->isSefMode;
	}

	public function setSiteId(string $siteId): Router
	{
		$this->siteId = $siteId;

		return $this;
	}

	public function getSiteId(): ?string
	{
		return $this->siteId;
	}

	public function getDefaultRoot(): string
	{
		return Option::get(static::MODULE_ID, static::OPTION_DEFAULT_ROOT, static::DEFAULT_ROOT);
	}

	protected function getSiteData(): ?array
	{
		if ($this->siteData === null || (is_array($this->siteData) && $this->siteData['LID'] !== $this->siteId))
		{
			$data = $this->siteId ? SiteTable::getById($this->siteId)->fetch() : null;
			if (!$data)
			{
				$data = null;
			}

			$this->siteData = $data;
		}

		return $this->siteData;
	}

	protected function getSiteFolder(): string
	{
		$dir = '/';

		$data = $this->getSiteData();
		if (is_array($data) && isset($data['DIR']) && !empty($data['DIR']))
		{
			$dir = $data['DIR'];
		}

		return $dir;
	}

	public function getDefaultUrlTemplates(): array
	{
		return [
			'bitrix:crm.quote.details' => 'type/' . \CCrmOwnerType::Quote . '/details/#ENTITY_ID#/',
			'bitrix:crm.item.details' => 'type/#ENTITY_TYPE_ID#/details/#ENTITY_ID#/',
			'bitrix:crm.item.kanban' => 'type/#entityTypeId#/kanban/category/#categoryId#/',
			'bitrix:crm.type.detail' => 'type/detail/#entityTypeId#/',
			'bitrix:crm.type.list' => 'type/',
			'bitrix:crm.item.list' => 'type/#entityTypeId#/list/category/#categoryId#/',
			'bitrix:crm.sales.tunnels' => 'type/#entityTypeId#/categories/',
			'bitrix:crm.item.automation' => 'type/#entityTypeId#/automation/#categoryId#/',
		];
	}

	public function saveCustomUrlTemplates(array $templates): void
	{
		Option::set(static::MODULE_ID, static::OPTION_NAME_TEMPLATES, Json::encode($templates));
	}

	public function getCustomUrlTemplates(): array
	{
		$result = [];

		$optionTemplates = Option::get(static::MODULE_ID, static::OPTION_NAME_TEMPLATES);
		if (is_string($optionTemplates) && !empty($optionTemplates))
		{
			try
			{
				$result = Json::decode($optionTemplates);
			}
			catch (ArgumentException $e)
			{
				$result = [];
			}
		}

		return $result;
	}

	protected function getUrlTemplates(): array
	{
		return \CComponentEngine::makeComponentUrlTemplates(
			$this->getDefaultUrlTemplates(),
			$this->getCustomUrlTemplates()
		);
	}

	public function getPreparedTemplates(): array
	{
		return $this->prepareTemplatesForRoot();
	}

	/**
	 * Returns prepared templates with root that is associated with the provided entity type
	 *
	 * @param int|null $entityTypeId - if null, prepares templates for default root
	 *
	 * @return array
	 */
	protected function prepareTemplatesForRoot(int $entityTypeId = null): array
	{
		$result = [];

		$templates = $this->getUrlTemplates();
		foreach ($templates as $name => $template)
		{
			$result[$name] = $this->getFullPath($template, $entityTypeId);
		}

		return $result;
	}

	/**
	 * Returns templates for the frontend crm router
	 *
	 * @return array[]
	 */
	public function getTemplatesForJsRouter(): array
	{
		$customRootTemplates = [];
		foreach ($this->customRoots as $entityTypeId => $customRoot)
		{
			$customRootTemplates[$entityTypeId] = $this->prepareTemplatesForRoot($entityTypeId);
		}

		return [
			'defaultRootUrlTemplates' => $this->prepareTemplatesForRoot(),
			'customRootUrlTemplates' => $customRootTemplates,
		];
	}

	public function parseRequest(HttpRequest $httpRequest = null): ParseResult
	{
		if ($this->isSefMode())
		{
			return $this->parseRequestInSefMode($httpRequest);
		}

		return $this->parseRequestParameters($httpRequest);
	}

	protected function parseRequestInSefMode(HttpRequest $httpRequest = null): ParseResult
	{
		if ($httpRequest)
		{
			$requestUrl = $httpRequest->getRequestUri();
		}
		else
		{
			$requestUrl = false;
		}
		$componentParameters = [];
		$componentName = \CComponentEngine::parseComponentPath(
			$this->getRoot(),
			$this->getUrlTemplates(),
			$componentParameters,
			$requestUrl
		);
		if (is_string($componentName))
		{
			$result = new ParseResult($componentName, $componentParameters);
		}
		else
		{
			$result = new ParseResult();
		}

		return $result;
	}

	protected function parseRequestParameters(HttpRequest $httpRequest = null): ParseResult
	{
		if (!$httpRequest)
		{
			$httpRequest = Application::getInstance()->getContext()->getRequest();
		}

		$componentName = $httpRequest->get(static::GET_COMPONENT_NAME);
		if (
			!array_key_exists($componentName, $this->getUrlTemplates())
			|| mb_strpos($httpRequest->getRequestUri(), $this->getFullPath()) !== 0
		)
		{
			$componentName = null;
		}

		return new ParseResult(
			$componentName,
			$httpRequest->get(static::GET_COMPONENT_PARAMETERS),
			$httpRequest->get(static::GET_COMPONENT_TEMPLATE)
		);
	}

	protected function getUrlForTemplate(string $componentName, array $parameters = [], array $getParameters = []): ?Uri
	{
		$entityTypeId = $parameters['ENTITY_TYPE_ID'] ?? $parameters['entityTypeId'] ?? null;
		$entityTypeId = (int)$entityTypeId;
		$template = $this->getUrlTemplates()[$componentName] ?? null;
		if ($template)
		{
			if ($this->isSefMode())
			{
				$path = \CComponentEngine::makePathFromTemplate($template, $parameters);
				if ($path)
				{
					$url = new Uri($this->getFullPath($path, $entityTypeId));
					if (!empty($getParameters))
					{
						$url->addParams($getParameters);
					}

					return $url;
				}
			}
			else
			{
				$uri = new Uri($this->getFullPath());
				$params = [
					static::GET_COMPONENT_NAME => $componentName,
					static::GET_COMPONENT_PARAMETERS => $parameters,
				];
				$params = array_merge($getParameters, $params);

				return $uri->addParams($params);
			}
		}

		return null;
	}

	protected function getFullPath(string $folder = '', ?int $entityTypeId = null): string
	{
		$root = $this->getRoot();
		if ($entityTypeId > 0 && isset($this->customRoots[$entityTypeId]))
		{
			$root = $this->customRoots[$entityTypeId];
		}
		return Path::combine($this->getSiteFolder(), $root, $folder) . '/';
	}

	public function getTypeListUrl(): ?Uri
	{
		return $this->getUrlForTemplate('bitrix:crm.type.list');
	}

	public function getTypeDetailUrl(int $entityTypeId): ?Uri
	{
		return $this->getUrlForTemplate(
			'bitrix:crm.type.detail',
			[
				'entityTypeId' => $entityTypeId,
			]
		);
	}

	public function getItemListUrl(int $entityTypeId, int $categoryId = null): ?Uri
	{
		if ($this->isNewRoutingForListEnabled($entityTypeId))
		{
			return $this->getItemListUrlWithNewRouting($entityTypeId, $categoryId);
		}

		return $this->getItemListUrlWithOldRouting($entityTypeId, $categoryId);
	}

	protected function getItemListUrlWithNewRouting(int $entityTypeId, int $categoryId = null): ?Uri
	{
		return $this->getUrlForTemplate(
			'bitrix:crm.item.list',
			[
				'entityTypeId' => $entityTypeId,
				'categoryId' => $categoryId ?? 0,
			]
		);
	}

	protected function getItemListUrlWithOldRouting(int $entityTypeId, int $categoryId = null): ?Uri
	{
		if ($entityTypeId === \CCrmOwnerType::Deal && !is_null($categoryId))
		{
			$template = Option::get(self::MODULE_ID, 'path_to_deal_category');

			return new Uri(\CComponentEngine::makePathFromTemplate($template, ['category_id' => $categoryId]));
		}

		$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));
		$template = Option::get(static::MODULE_ID, "path_to_{$entityName}_list");
		if (empty($template))
		{
			return null;
		}

		return new Uri(\CComponentEngine::makePathFromTemplate($template));
	}

	public function getKanbanUrl(int $entityTypeId, int $categoryId = null): ?Uri
	{
		if ($this->isNewRoutingForListEnabled($entityTypeId))
		{
			return $this->getKanbanUrlWithNewRouting($entityTypeId, $categoryId);
		}

		return $this->getKanbanUrlWithOldRouting($entityTypeId, $categoryId);
	}

	protected function getKanbanUrlWithNewRouting(int $entityTypeId, int $categoryId = null): ?Uri
	{
		return $this->getUrlForTemplate(
			'bitrix:crm.item.kanban',
			[
				'entityTypeId' => $entityTypeId,
				'categoryId' => $categoryId ?? 0,
			]
		);
	}

	protected function getKanbanUrlWithOldRouting(int $entityTypeId, int $categoryId = null): ?Uri
	{
		if ($entityTypeId === \CCrmOwnerType::DealCategory)
		{
			return null;
		}

		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$template = Option::get(self::MODULE_ID, 'path_to_deal_category_kanban');

			return new Uri(\CComponentEngine::makePathFromTemplate($template, ['category_id' => $categoryId ?? 0]));
		}

		$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));
		$template = Option::get(static::MODULE_ID, "path_to_{$entityName}_kanban");
		if (empty($template))
		{
			return null;
		}

		return new Uri(\CComponentEngine::makePathFromTemplate($template));
	}

	public function getItemDetailUrlCompatibleTemplate(int $entityTypeId): ?string
	{
		if ($this->isNewRoutingForDetailEnabled($entityTypeId))
		{
			$uri = $this->getUrlForTemplate(
				'bitrix:crm.item.details',
				[
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_ID' => '#' . $this->getCompatiblePlaceholder($entityTypeId) . '#',
				]
			);

			return $uri ? $uri->getUri() : null;
		}

		return $this->getCompatibleItemDetailsTemplate($entityTypeId);
	}

	public function getItemDetailUrl(
		int $entityTypeId,
		int $id = 0,
		int $categoryId = null,
		?ItemIdentifier $parentItemIdentifier = null
	): ?Uri
	{
		if ($this->isNewRoutingForDetailEnabled($entityTypeId))
		{
			return $this->getItemDetailUrlWithNewRouting($entityTypeId, $id, $categoryId, $parentItemIdentifier);
		}

		return $this->getItemDetailUrlWithOldRouting($entityTypeId, $id, $categoryId);
	}

	/**
	 * Returns true if this entity uses new routing in item lists or kanban urls generation
	 *
	 * @param int $entityTypeId
	 *
	 * @return bool
	 */
	public function isNewRoutingForListEnabled(int $entityTypeId): bool
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return false;
		}

		return $factory->isNewRoutingForListEnabled();
	}

	/**
	 * Returns true is this entity uses new routing in item detail urls generation
	 *
	 * @param int $entityTypeId
	 *
	 * @return bool
	 */
	public function isNewRoutingForDetailEnabled(int $entityTypeId): bool
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return false;
		}

		return $factory->isNewRoutingForDetailEnabled();
	}

	public function isNewRoutingForAutomationEnabled(int $entityTypeId): bool
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return false;
		}

		return $factory->isNewRoutingForAutomationEnabled();
	}

	/**
	 * @param int $entityTypeId
	 * @param int $id
	 * @param int|null $categoryId
	 * @param ItemIdentifier $parentItemIdentifier
	 *
	 * @return Uri|null
	 */
	protected function getItemDetailUrlWithNewRouting(
		int $entityTypeId,
		int $id = 0,
		int $categoryId = null,
		?ItemIdentifier $parentItemIdentifier = null
	): ?Uri
	{
		$url = $this->getUrlForTemplate(
			'bitrix:crm.item.details',
			[
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_ID' => $id,
			]
		);
		if ($url)
		{
			if ($categoryId > 0)
			{
				$url->addParams([
					'categoryId' => $categoryId,
				]);
			}
			if ($parentItemIdentifier)
			{
				$url->addParams([
					'parentTypeId' => $parentItemIdentifier->getEntityTypeId(),
					'parentId' => $parentItemIdentifier->getEntityId(),
				]);
			}
		}

		return $url;
	}

	protected function getItemDetailUrlWithOldRouting(int $entityTypeId, int $id = 0, int $categoryId = null): ?Uri
	{
		$isEdit = ($id <= 0);
		$template = $this->getCompatibleItemDetailsTemplate($entityTypeId, $isEdit);

		if (empty($template))
		{
			return null;
		}

		$url = new Uri(\CComponentEngine::makePathFromTemplate(
			$template,
			[
				$this->getCompatiblePlaceholder($entityTypeId) => $id,
			]
		));

		if ($categoryId > 0)
		{
			$url->addParams(['category_id' => $categoryId]);
		}

		return $url;
	}

	protected function getCompatibleItemDetailsTemplate(int $entityTypeId, bool $isEdit = false): ?string
	{
		$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));
		if (\CCrmOwnerType::IsSliderEnabled($entityTypeId))
		{
			$optionName = "path_to_{$entityName}_details";
		}
		elseif ($isEdit)
		{
			$optionName = "path_to_{$entityName}_edit";
		}
		else
		{
			$optionName = "path_to_{$entityName}_show";
		}

		return Option::get(static::MODULE_ID, $optionName, null);
	}

	protected function getCompatiblePlaceholder(int $entityTypeId): string
	{
		if ($entityTypeId === \CCrmOwnerType::DealCategory)
		{
			return 'category_id';
		}

		$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));
		$paramName = "{$entityName}_id";
		if (mb_strpos($entityName, 'order_') !== false)
		{
			$orderSubEntityName = str_replace('order_', '', $entityName);
			$paramName = "{$orderSubEntityName}_id";
		}

		return $paramName;
	}

	public function getItemCopyUrl(int $entityTypeId, int $id = 0, int $categoryId = null): ?Uri
	{
		$url = $this->getItemDetailUrl($entityTypeId, $id, $categoryId);
		if ($url)
		{
			$url->addParams(['copy' => 1]);
		}

		return $url;
	}

	public function getUserFieldListUrl(int $entityTypeId): ?Uri
	{
		$userFieldEntityID = \CCrmOwnerType::ResolveUserFieldEntityID($entityTypeId);
		if ($userFieldEntityID && Loader::includeModule('intranet'))
		{
			return Util::getUserFieldListConfigUrl('crm', $userFieldEntityID);
		}

		return null;
	}

	public function getUserFieldDetailUrl(int $entityTypeId, int $fieldId): ?Uri
	{
		$userFieldEntityID = \CCrmOwnerType::ResolveUserFieldEntityID($entityTypeId);
		if ($userFieldEntityID && Loader::includeModule('intranet'))
		{
			return Util::getUserFieldDetailConfigUrl('crm', $userFieldEntityID, $fieldId);
		}

		return null;
	}

	public function getCategoryListUrl(int $entityTypeId): ?Uri
	{
		return $this->getUrlForTemplate(
			'bitrix:crm.sales.tunnels',
			[
				'entityTypeId' => $entityTypeId,
			]
		);
	}

	public function getProductDetailUrl(int $productId = 0): Uri
	{
		$template = $this->getProductDetailUrlTemplate();

		return new Uri(str_replace('#product_id#', $productId, $template));
	}

	public function getProductDetailUrlTemplate(): string
	{
		if (Loader::includeModule('catalog') && \Bitrix\Catalog\Config\State::isProductCardSliderEnabled())
		{
			$catalogId = \CCrmCatalog::ensureDefaultExists();

			return "/shop/catalog/{$catalogId}/product/#product_id#/";
		}

		$pathFromOption = Option::get(static::MODULE_ID, static::OPTION_CATALOG_PRODUCT_SHOW);
		if (!empty($pathFromOption))
		{
			return $pathFromOption;
		}

		$requestedPage = Application::getInstance()->getContext()->getRequest()->getRequestedPage();

		return ($requestedPage . '?product_id=#product_id#&show');
	}

	public function getProductEditUrl(int $productId): Uri
	{
		$template = $this->getProductEditUrlTemplate();

		return new Uri(str_replace('#product_id#', $productId, $template));
	}

	public function getProductEditUrlTemplate(): string
	{
		$pathFromOption = Option::get(static::MODULE_ID, static::OPTION_CATALOG_PRODUCT_EDIT);
		if (!empty($pathFromOption))
		{
			return $pathFromOption;
		}

		$requestedPage = Application::getInstance()->getContext()->getRequest()->getRequestedPage();

		return ($requestedPage . '?product_id=#product_id#&edit');
	}

	public function getQuotePrintUrl(int $quoteId, bool $isBlank): Uri
	{
		$baseUrl = $this->getQuotePaymentUrl($quoteId);

		$isBlankValue = $isBlank ? 'Y' : 'N';
		$baseUrl->addParams(['PRINT' => 'Y', 'BLANK' => $isBlankValue]);

		return $baseUrl;
	}

	public function getQuotePdfUrl(int $quoteId, bool $isBlank): Uri
	{
		$baseUrl = $this->getQuotePaymentUrl($quoteId);

		$isBlankValue = $isBlank ? 'Y' : 'N';
		$baseUrl->addParams(['pdf' => 1, 'DOWNLOAD' => 'Y', 'BLANK' => $isBlankValue]);

		return $baseUrl;
	}

	protected function getQuotePaymentUrl(int $quoteId): Uri
	{
		return new Uri("/crm/quote/payment/$quoteId/?ncc=1");
	}

	public function getItemListUrlInCurrentView(int $entityTypeId, int $categoryId = null): ?Uri
	{
		$currentView = $this->getCurrentListView($entityTypeId);

		if ($currentView === static::LIST_VIEW_KANBAN)
		{
			return $this->getKanbanUrl($entityTypeId, $categoryId);
		}

		if ($currentView === static::LIST_VIEW_LIST)
		{
			return $this->getItemListUrl($entityTypeId, $categoryId);
		}

		return null;
	}

	public function setCurrentListView(int $entityTypeId, string $view): Router
	{
		$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));

		// for example, 20190603
		$date = (new Date())->format('Ymd');

		$currentViews = $this->loadCurrentViews();
		$currentViews[$entityName] = "$view:$date";
		$this->saveCurrentViews($currentViews);

		return $this;
	}

	public function getCurrentListView(int $entityTypeId): string
	{
		$currentView = $this->loadCurrentViews();

		$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));

		$entityCurrentView = $currentView[$entityName] ?? null;
		if (!$entityCurrentView)
		{
			return $this->getDefaultListView($entityTypeId);
		}

		$view = mb_split(':', $entityCurrentView)[0];

		// For compatibility
		return mb_strtoupper($view);
	}

	public function getDefaultListView(int $entityTypeId): string
	{
		$view = Option::get('crm',
			\CCrmOwnerType::ResolveName($entityTypeId),
			$this->getInitialDefaultListView($entityTypeId)
		);

		// For compatibility. Old API writes int in option, new - string
		if (is_numeric($view))
		{
			$view = EntityViewSettings::resolveName((int)$view);
		}

		return $view;
	}

	public function setDefaultListView(int $entityTypeId, string $view): void
	{
		if ($view === $this->getInitialDefaultListView($entityTypeId))
		{
			Option::delete('crm', ['name' => $this->getDefaultListViewOptionName($entityTypeId)]);
		}
		else
		{
			Option::set('crm', $this->getDefaultListViewOptionName($entityTypeId), $view);
		}
	}

	/**
	 * Returns list view that used when the corresponding option is not set
	 *
	 * @return string
	 */
	protected function getInitialDefaultListView(int $entityTypeId): string
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if ($factory && $factory->isStagesEnabled())
		{
			return Router::LIST_VIEW_KANBAN;
		}

		return Router::LIST_VIEW_LIST;
	}

	protected function getDefaultListViewOptionName(int $entityTypeId): string
	{
		return mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId) . '_default_list_view');
	}

	protected function loadCurrentViews(): array
	{
		return (array)\CUserOptions::GetOption('crm.navigation', 'index', []);
	}

	protected function saveCurrentViews(array $currentViews): void
	{
		\CUserOptions::SetOption('crm.navigation', 'index', $currentViews);
	}

	public function getAutomationUrlTemplate(int $entityTypeId): ?string
	{
		if ($entityTypeId === \CCrmOwnerType::Order)
		{
			return '/shop/orders/automation/0/';
		}
		if ($this->isNewRoutingForAutomationEnabled($entityTypeId))
		{
			return $this->getPreparedTemplates()['bitrix:crm.item.automation'] ?? null;
		}
		$entityName = mb_strtolower(\CCrmOwnerType::ResolveName($entityTypeId));

		return '/crm/' . $entityName . '/automation/#categoryId#/';
	}

	public function getAutomationUrl(int $entityTypeId, int $categoryId = null): ?Uri
	{
		if ($this->isNewRoutingForAutomationEnabled($entityTypeId))
		{
			return $this->getUrlForTemplate(
				'bitrix:crm.item.automation',
				[
					'entityTypeId' => $entityTypeId,
					'categoryId' => $categoryId ?? 0,
				]
			);
		}

		$template = $this->getAutomationUrlTemplate($entityTypeId);
		if ($template)
		{
			return new Uri(str_replace('#categoryId#', $categoryId ?? 0, $template));
		}

		return null;
	}

	public function getFileUrl(int $entityTypeId, int $id, string $fieldName, int $fileId): Uri
	{
		return new ContentUri(\Bitrix\Main\Engine\UrlManager::getInstance()->create('crm.controller.item.getFile', [
			'entityTypeId' => $entityTypeId,
			'id' => $id,
			'fieldName' => $fieldName,
			'fileId' => $fileId,
		], true));
	}

	public function setCustomRoots(array $customRoots): self
	{
		$this->customRoots = $customRoots;

		return $this;
	}

	public function getCustomRoots(): array
	{
		return $this->customRoots;
	}

	public function getDefaultComponent(): string
	{
		return $this->defaultComponent;
	}

	public function setDefaultComponent(string $defaultComponent): self
	{
		$this->defaultComponent = $defaultComponent;

		return $this;
	}

	public function getDefaultComponentParameters(): array
	{
		return $this->defaultComponentParameters;
	}

	public function setDefaultComponentParameters(array $defaultComponentParameters): self
	{
		$this->defaultComponentParameters = $defaultComponentParameters;

		return $this;
	}

	protected function initCustomRoots(): void
	{
		$customSections = IntranetManager::getCustomSections();
		if (empty($customSections))
		{
			return;
		}

		$customRoots = [];
		foreach ($customSections as $section)
		{
			foreach ($section->getPages() as $page)
			{
				$entityTypeId = IntranetManager::getEntityTypeIdByPageSettings($page->getSettings());
				$url = IntranetManager::getUrlForCustomSectionPage($section->getCode(), $page->getCode());
				if ($entityTypeId > 0 && !is_null($url))
				{
					$customRoots[$entityTypeId] = $url->getPath();
				}
			}
		}

		$this->setCustomRoots($customRoots);
	}

	public function getConsistentUrlFromPartlyDefined(string $currentUrl): ?Uri
	{
		if (!$this->isSefMode())
		{
			return null;
		}

		$url = new Uri($currentUrl);
		$path = $url->getPath();
		if (preg_match('#type/(\d+)/(list|kanban)?#', $path, $matches))
		{
			$entityTypeId = (int)$matches[1];
			if (isset($matches[2]))
			{
				$viewType = mb_strtoupper($matches[2]);
			}
			else
			{
				$viewType = $this->getCurrentListView($entityTypeId);
			}

			if ($viewType === static::LIST_VIEW_LIST)
			{
				return $this->getItemListUrl($entityTypeId);
			}

			return $this->getKanbanUrl($entityTypeId);
		}

		return null;
	}

	public function getChildrenItemsListUrl(int $entityTypeId, int $parentEntityTypeId, int $parentEntityId): ?Uri
	{
		// only dynamic types for now
		if (!\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return null;
		}

		$componentName = 'bitrix:crm.item.list';
		$componentPath = \CComponentEngine::makeComponentPath($componentName);
		$componentPath = getLocalPath('components'.$componentPath.'/lazyload.ajax.php');

		$url = new Uri($componentPath);
		$url->addParams([
			'entityTypeId' => $entityTypeId,
			'parentEntityTypeId' => $parentEntityTypeId,
			'parentEntityId' => $parentEntityId,
			'sessid' => bitrix_sessid(),
		]);

		return $url;
	}

	/**
	 * Return name of detail component by $entityTypeId.
	 *
	 * @param int $entityTypeId
	 * @return string|null
	 */
	public function getItemDetailComponentName(int $entityTypeId): ?string
	{
		if ($entityTypeId === \CCrmOwnerType::Quote)
		{
			return 'bitrix:crm.quote.details';
		}
		if (\CCrmOwnerType::isPossibleDynamicTypeId($entityTypeId))
		{
			return 'bitrix:crm.item.details';
		}

		return null;
	}
}
