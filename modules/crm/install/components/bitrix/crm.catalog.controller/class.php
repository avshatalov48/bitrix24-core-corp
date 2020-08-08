<?
use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc,
	Bitrix\Crm,
	Bitrix\Iblock;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

class CrmCatalogControllerComponent extends CBitrixComponent implements Main\Errorable
{
	private const PAGE_INDEX = 'index';
	private const PAGE_LIST = 'list';
	private const PAGE_SECTION_LIST = 'section_list';
	private const PAGE_SECTION_DETAIL = 'section_detail';
	private const PAGE_PRODUCT_DETAIL = 'product_detail';

	/** @var  Main\ErrorCollection */
	protected $errorCollection = null;

	/** @var int */
	protected $iblockId = null;
	/** @var array */
	protected $iblock = null;
	/** @var string */
	protected $iblockListMode = null;
	/** @var bool */
	protected $iblockListMixed = null;

	/** @var string */
	protected $pageId = null;

	/** @var Crm\Product\Url\ProductBuilder */
	protected $urlBuilder = null;

	/**
	 * Base constructor.
	 * @param \CBitrixComponent|null $component		Component object if exists.
	 */
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new Main\ErrorCollection();
	}

	/**
	 * @param $params
	 * @return array
	 */
	public function onPrepareComponentParams($params): array
	{
		if (!is_array($params))
		{
			$params = [];
		}

		return $params;
	}

	/**
	 * @return void
	 */
	public function onIncludeComponentLang(): void
	{
		$this->includeComponentLang('class.php');
	}

	/**
	 * @param string $code
	 * @return Main\Error|null
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @return array|Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * @return bool
	 */
	protected function isExistErrors(): bool
	{
		return !$this->errorCollection->isEmpty();
	}

	/**
	 * @return void
	 */
	protected function showErrors(): void
	{
		foreach ($this->getErrors() as $error)
		{
			\ShowError($error);
		}
		unset($error);
	}

	/**
	 * @param string $message
	 * @return void
	 */
	protected function addErrorMessage(string $message): void
	{
		$this->errorCollection->setError(new Main\Error($message));
	}

	public function executeComponent()
	{
		$this->checkModules();
		$this->checkAccess();
		if ($this->isExistErrors())
		{
			$this->showErrors();
			return;
		}
		$this->initConfig();
		if ($this->isExistErrors())
		{
			$this->showErrors();
			return;
		}
		$this->initUrlBuilder();
		if ($this->isExistErrors())
		{
			$this->showErrors();
			return;
		}
		$this->parseComponentVariables();
		if ($this->isExistErrors())
		{
			$this->showErrors();
			return;
		}
		$this->initUiScope();
		$this->arResult['PAGE_DESCRIPTION'] = $this->getPageDescription();
		$this->includeComponentTemplate($this->pageId);
	}

	protected function checkModules(): void
	{
		if (!Loader::includeModule('crm'))
		{
			$this->addErrorMessage(Loc::getMessage('CRM_CATALOG_CONTROLLER_ERR_CRM_MODULE_ABSENT'));
		}
		if (!Loader::includeModule('catalog'))
		{
			$this->addErrorMessage(Loc::getMessage('CRM_CATALOG_CONTROLLER_ERR_CATALOG_MODULE_ABSENT'));
		}
		if (!Loader::includeModule('iblock'))
		{
			$this->addErrorMessage(Loc::getMessage('CRM_CATALOG_CONTROLLER_ERR_IBLOCK_MODULE_ABSENT'));
		}
	}

	protected function checkAccess(): void
	{

	}

	protected function initConfig(): void
	{
		$iblockId = Crm\Product\Catalog::getDefaultId();
		if ($iblockId === null)
		{
			$this->addErrorMessage(Loc::getMessage('CRM_CATALOG_CONTROLLER_ERR_CATALOG_PRODUCT_ABSENT'));
			return;
		}
		$iblock = \CIBlock::GetArrayByID($iblockId);
		if (empty($iblock) || !is_array($iblock))
		{
			$this->addErrorMessage(Loc::getMessage('CRM_CATALOG_CONTROLLER_ERR_CATALOG_PRODUCT_ABSENT'));
			return;
		}
		$this->iblockId = $iblockId;
		$this->iblock = $iblock;
		$this->iblockListMode = \CIblock::GetAdminListMode($this->iblockId);
		$this->iblockListMixed = $this->iblockListMode == Iblock\IblockTable::LIST_MODE_COMBINED;
		unset($iblock, $iblockId);
	}

	protected function initUrlBuilder(): void
	{
		$this->urlBuilder = new Crm\Product\Url\ProductBuilder();
		$this->urlBuilder->setIblockId($this->iblockId);
		$this->urlBuilder->setUrlParams([]);
	}

	protected function parseComponentVariables(): void
	{
		$this->arParams['SEF_MODE'] = 'Y';
		$templateUrls = $this->getTemplateUrls();
		if ($this->arParams['SEF_MODE'] === 'Y')
		{
			[$template, $variables, $variableAliases] = $this->processSefMode($templateUrls);
		}
		else
		{
			[$template, $variables, $variableAliases] = $this->processRegularMode($templateUrls);
		}

		$this->arResult = array_merge(
			[
				'VARIABLES' => $variables,
				'ALIASES' => $variableAliases,
			],
			$this->arResult
		);

		$this->pageId = $template;
		if (empty($this->pageId))
		{
			$this->addErrorMessage(Loc::getMessage('CRM_CATALOG_CONTROLLER_ERR_PAGE_UNKNOWN'));
		}
	}

	protected function getTemplateUrls(): array
	{
		if ($this->iblockListMixed)
		{
			return [
				self::PAGE_INDEX => '',
				self::PAGE_LIST => 'list/#SECTION_ID#/',
				self::PAGE_PRODUCT_DETAIL => 'product/#PRODUCT_ID#/',
				self::PAGE_SECTION_DETAIL => 'section/#SECTION_ID#/',
			];
		}
		else
		{
			return [
				self::PAGE_INDEX => '',
				self::PAGE_LIST => 'list/#SECTION_ID#/',
				self::PAGE_PRODUCT_DETAIL => 'product/#PRODUCT_ID#/',
				self::PAGE_SECTION_LIST => 'section_list/#SECTION_ID#/',
				self::PAGE_SECTION_DETAIL => 'section/#SECTION_ID#/',
			];
		}
	}

	protected function processSefMode(array $templateUrls): array
	{
		$templateUrls = \CComponentEngine::MakeComponentUrlTemplates($templateUrls, $this->arParams['SEF_URL_TEMPLATES']);

		foreach ($templateUrls as $name => $url)
		{
			$this->arResult['PATH_TO'][ToUpper($name)] = $this->arParams['SEF_FOLDER'].$url;
		}

		$variableAliases = \CComponentEngine::MakeComponentVariableAliases([], $this->arParams['VARIABLE_ALIASES']);

		$variables = [];
		$template = \CComponentEngine::ParseComponentPath($this->arParams['SEF_FOLDER'], $templateUrls, $variables);

		if (!is_string($template) || !isset($templateUrls[$template]))
		{
			$template = key($templateUrls);
		}

		\CComponentEngine::InitComponentVariables($template, [], $variableAliases, $variables);

		return [$template, $variables, $variableAliases];
	}

	protected function processRegularMode(array $templateUrls): array
	{
		$variableAliases = \CComponentEngine::MakeComponentVariableAliases([], $this->arParams['VARIABLE_ALIASES']);

		$variables = [];
		\CComponentEngine::InitComponentVariables(false, [], $variableAliases, $variables);

		$currentPage = $this->request->getRequestedPage();
		$templates = array_keys($templateUrls);

		foreach ($templates as $template)
		{
			$this->arResult['PATH_TO'][ToUpper($template)] = $currentPage.'?'.self::TEMPLATE_CODE.'='.$template;
		}

		$template = $this->request->get(self::TEMPLATE_CODE);

		if ($template === null || !in_array($template, $templates, true))
		{
			$template = key($templateUrls);
		}

		return [$template, $variables, $variableAliases];
	}

	protected function getPageDescription(): ?array
	{
		$result = null;

		$pageUrl = Main\Application::getInstance()->getContext()->getRequest()->getRequestUri();
		$currentUri = new Main\Web\Uri($pageUrl);
		$queryString = $currentUri->getQuery();

		switch ($this->pageId)
		{
			case self::PAGE_INDEX:
				$result = [
					'PAGE_ID' => 'crm_catalog_products',
					'PAGE_PATH' => '/bitrix/modules/iblock/admin/'.($this->iblockListMixed
						? 'iblock_list_admin.php'
						: 'iblock_element_admin.php'
					),
					'PAGE_PARAMS' => $this->urlBuilder->getBaseParams(),
					'SEF_FOLDER' => '/', // hack for template files
					'INTERNAL_PAGE' => 'N',
					'CACHE_TYPE' => 'N',
					'PAGE_CONSTANTS' => [
						'CATALOG_PRODUCT' => 'Y',
						'URL_BUILDER_TYPE' => Crm\Product\Url\ProductBuilder::TYPE_ID
					]
				];
				break;
			case self::PAGE_LIST:
				$result = [
					'PAGE_ID' => ($this->iblockListMixed ? 'crm_catalog_item_list' : 'crm_catalog_product_list'),
					'PAGE_PATH' => '/bitrix/modules/iblock/admin/'.($this->iblockListMixed
						? 'iblock_list_admin.php'
						: 'iblock_element_admin.php'
					),
					'PAGE_PARAMS' => $this->urlBuilder->getBaseParams(),
					'SEF_FOLDER' => '/', // hack for template files
					'INTERNAL_PAGE' => 'N',
					'CACHE_TYPE' => 'N',
					'PAGE_CONSTANTS' => [
						'CATALOG_PRODUCT' => 'Y',
						'URL_BUILDER_TYPE' => Crm\Product\Url\ProductBuilder::TYPE_ID
					]
				];
				break;
			case self::PAGE_SECTION_LIST:
				$result = [
					'PAGE_ID' => 'crm_catalog_section_list',
					'PAGE_PATH' => '/bitrix/modules/iblock/admin/iblock_section_admin.php',
					'PAGE_PARAMS' => $this->urlBuilder->getBaseParams(),
					'SEF_FOLDER' => '/', // hack for template files
					'INTERNAL_PAGE' => 'N',
					'CACHE_TYPE' => 'N',
					'PAGE_CONSTANTS' => [
						'CATALOG_PRODUCT' => 'Y',
						'URL_BUILDER_TYPE' => Crm\Product\Url\ProductBuilder::TYPE_ID
					]
				];
				break;
			case self::PAGE_SECTION_DETAIL:
				$result = [
					'PAGE_ID' => 'crm_catalog_section_detail',
					'PAGE_PATH' => '',
					'PAGE_PARAMS' => $queryString,
					'SEF_FOLDER' => '/', // hack for template files
					'INTERNAL_PAGE' => 'Y',
					'CACHE_TYPE' => 'N',
					'PAGE_CONSTANTS' => [
						'CATALOG_PRODUCT' => 'Y',
						'URL_BUILDER_TYPE' => Crm\Product\Url\ProductBuilder::TYPE_ID
					]
				];
				break;
			case self::PAGE_PRODUCT_DETAIL:
				$result = [
					'PAGE_ID' => 'crm_catalog_product_detail',
					'PAGE_PATH' => '',
					'PAGE_PARAMS' => $queryString,
					'SEF_FOLDER' => '',
					'INTERNAL_PAGE' => 'Y',
					'CACHE_TYPE' => 'N',
					'PAGE_CONSTANTS' => [
						'CATALOG_PRODUCT' => 'Y',
						'URL_BUILDER_TYPE' => Crm\Product\Url\ProductBuilder::TYPE_ID
					]
				];
				break;
		}

		return $result;
	}


	/**
	 * @return void
	 */
	protected function initUiScope(): void
	{
		global $APPLICATION;

		Main\UI\Extension::load($this->getUiExtensions());

		foreach ($this->getUiStyles() as $styleList)
		{
			$APPLICATION->SetAdditionalCSS($styleList);
		}

		$scripts = $this->getUiScripts();
		if (!empty($scripts))
		{
			$asset = Main\Page\Asset::getInstance();
			foreach ($scripts as $row)
			{
				$asset->addJs($row);
			}
			unset($row, $asset);
		}
		unset($scripts);
	}

	/**
	 * @return array
	 */
	protected function getUiExtensions(): array
	{
		return [
			'admin_sidepanel'
		];
	}

	/**
	 * @return array
	 */
	protected function getUiStyles(): array
	{
		return [];
	}

	/**
	 * @return array
	 */
	protected function getUiScripts(): array
	{
		return [];
	}

	public function showCrmControlPanel(): void
	{
		/** global \CMain $APPLICATION */
		global $APPLICATION;
		$APPLICATION->IncludeComponent(
			'bitrix:crm.control_panel',
			'',
			array(
				'ID' => 'CATALOG_PRODUCT',
				'ACTIVE_ITEM_ID' => 'CATALOG',
				'PATH_TO_COMPANY_LIST' => isset($this->arResult['PATH_TO_COMPANY_LIST']) ? $this->arResult['PATH_TO_COMPANY_LIST'] : '',
				'PATH_TO_COMPANY_EDIT' => isset($this->arResult['PATH_TO_COMPANY_EDIT']) ? $this->arResult['PATH_TO_COMPANY_EDIT'] : '',
				'PATH_TO_CONTACT_LIST' => isset($this->arResult['PATH_TO_CONTACT_LIST']) ? $this->arResult['PATH_TO_CONTACT_LIST'] : '',
				'PATH_TO_CONTACT_EDIT' => isset($this->arResult['PATH_TO_CONTACT_EDIT']) ? $this->arResult['PATH_TO_CONTACT_EDIT'] : '',
				'PATH_TO_DEAL_LIST' => isset($this->arResult['PATH_TO_DEAL_LIST']) ? $this->arResult['PATH_TO_DEAL_LIST'] : '',
				'PATH_TO_DEAL_EDIT' => isset($this->arResult['PATH_TO_DEAL_EDIT']) ? $this->arResult['PATH_TO_DEAL_EDIT'] : '',
				'PATH_TO_LEAD_LIST' => isset($this->arResult['PATH_TO_LEAD_LIST']) ? $this->arResult['PATH_TO_LEAD_LIST'] : '',
				'PATH_TO_LEAD_EDIT' => isset($this->arResult['PATH_TO_LEAD_EDIT']) ? $this->arResult['PATH_TO_LEAD_EDIT'] : '',
				'PATH_TO_QUOTE_LIST' => isset($this->arResult['PATH_TO_QUOTE_LIST']) ? $this->arResult['PATH_TO_QUOTE_LIST'] : '',
				'PATH_TO_QUOTE_EDIT' => isset($this->arResult['PATH_TO_QUOTE_EDIT']) ? $this->arResult['PATH_TO_QUOTE_EDIT'] : '',
				'PATH_TO_INVOICE_LIST' => isset($this->arResult['PATH_TO_INVOICE_LIST']) ? $this->arResult['PATH_TO_INVOICE_LIST'] : '',
				'PATH_TO_INVOICE_EDIT' => isset($this->arResult['PATH_TO_INVOICE_EDIT']) ? $this->arResult['PATH_TO_INVOICE_EDIT'] : '',
				'PATH_TO_REPORT_LIST' => isset($this->arResult['PATH_TO_REPORT_LIST']) ? $this->arResult['PATH_TO_REPORT_LIST'] : '',
				'PATH_TO_DEAL_FUNNEL' => isset($this->arResult['PATH_TO_DEAL_FUNNEL']) ? $this->arResult['PATH_TO_DEAL_FUNNEL'] : '',
				'PATH_TO_EVENT_LIST' => isset($this->arResult['PATH_TO_EVENT_LIST']) ? $this->arResult['PATH_TO_EVENT_LIST'] : '',
				'PATH_TO_PRODUCT_LIST' => isset($this->arResult['PATH_TO_INDEX']) ? $this->arResult['PATH_TO_INDEX'] : ''
			),
			$this
		);
	}


}