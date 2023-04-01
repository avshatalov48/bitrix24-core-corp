<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm;
use Bitrix\Iblock;

class CrmCatalogControllerComponent extends CBitrixComponent implements Main\Errorable
{
	private const PAGE_INDEX = 'index';
	private const PAGE_LIST = 'list';
	private const PAGE_LIST_SLIDER = 'list_slider';
	private const PAGE_SECTION_LIST = 'section_list';
	private const PAGE_SECTION_DETAIL = 'section_detail';
	private const PAGE_PRODUCT_DETAIL = 'product_detail';
	private const PAGE_CSV_IMPORT = 'csv_import';
	private const PAGE_ERROR = 'error';

	private const MODE_SLIDER_VIEW_NAME = 'sliderList';

	/** @var  Main\ErrorCollection */
	protected $errorCollection;

	/** @var int */
	protected $iblockId;
	/** @var array */
	protected $iblock;
	/** @var string */
	protected $iblockListMode;
	/** @var bool */
	protected $iblockListMixed;
	/** @var $sliderMode */
	private $sliderMode;

	/** @var string */
	protected $pageId;

	/** @var Crm\Product\Url\ProductBuilder */
	protected $urlBuilder;

	/** @var Main\HttpRequest  */
	protected $request;

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

		$params['PATH_TO_PRODUCT_LIST'] = (
			(
				isset($params['PATH_TO_PRODUCT_LIST'])
				&& is_string($params['PATH_TO_PRODUCT_LIST'])
				&& $params['PATH_TO_PRODUCT_LIST'] !== ''
			)
			? $params['PATH_TO_PRODUCT_LIST'] :
			'#SITE_DIR#crm/product/index.php'
		);
		$params['SEF_MODE'] = 'Y';
		if (!isset($params['SEF_URL_TEMPLATES']) || !is_array($params['SEF_URL_TEMPLATES']))
		{
			$params['SEF_URL_TEMPLATES'] = [];
		}
		if (!isset($params['VARIABLE_ALIASES']) || !is_array($params['VARIABLE_ALIASES']))
		{
			$params['VARIABLE_ALIASES'] = [];
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
	 * @return Main\Error[]
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
		if ($this->isExistErrors())
		{
			$this->showErrors();
			return;
		}
		$this->checkAccess();
		if ($this->isExistErrors())
		{
			$request = Main\Application::getInstance()->getContext()->getRequest();
			$this->arResult['IS_SIDE_PANEL'] = (
				$request->get('IFRAME') === 'Y'
				&& $request->get('IFRAME_TYPE') === 'SIDE_SLIDER'
				&& $request->get('disableRedirect') !== 'Y'
			);

			$this->includeComponentTemplate(self::PAGE_ERROR);

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

	public static function getViewModeParams(): array
	{
		return [
			self::MODE_SLIDER_VIEW_NAME => 'Y',
			'INCLUDE_SUBSECTIONS' => 'Y',
		];
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
		if (!CCrmSaleHelper::isShopAccess())
		{
			$this->addErrorMessage('Access Denied');
		}
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
		$this->request = Main\Application::getInstance()->getContext()->getRequest();
		$this->sliderMode = $this->request->get(self::MODE_SLIDER_VIEW_NAME) === 'Y';
	}

	protected function initUrlBuilder(): void
	{
		$this->urlBuilder = new Crm\Product\Url\ProductBuilder();
		$this->urlBuilder->setIblockId($this->iblockId);
		$params = [];
		if ($this->sliderMode)
		{
			$this->urlBuilder->setSeparateIblockList();
			$params = static::getViewModeParams();
		}
		$this->urlBuilder->setUrlParams($params);
		$this->iblockListMixed = $this->urlBuilder->isIblockListMixed();
		$this->iblockListMode =
			$this->iblockListMixed
				? Iblock\IblockTable::LIST_MODE_COMBINED
				: Iblock\IblockTable::LIST_MODE_SEPARATE
		;
	}

	protected function parseComponentVariables(): void
	{
		if (!\Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isFullCatalogEnabled())
		{
			LocalRedirect(CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_PRODUCT_LIST']));
		}

		CBitrixComponent::includeComponentClass('bitrix:catalog.productcard.controller');

		$templateUrls = $this->getTemplateUrls();
		[$template, $variables, $variableAliases] = $this->processSefMode($templateUrls);

		if (\CatalogProductControllerComponent::hasUrlTemplateId($template))
		{
			$template = self::PAGE_PRODUCT_DETAIL;
		}
		elseif ($this->sliderMode && $template === self::PAGE_LIST)
		{
			$template = self::PAGE_LIST_SLIDER;
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
		if ($this->pageId == self::PAGE_INDEX)
		{
			if (
				!$this->request->isPost()
				&& !$this->request->isAjaxRequest()
				&& (
					$this->request->getQuery('find_section_section') === null
					|| $this->request->getQuery('SECTION_ID') === null
					|| $this->request->getQuery('apply_filter') === null
				)
			)
			{
				$pageUrl = $this->request->getRequestUri();
				$currentUri = new Main\Web\Uri($pageUrl);
				LocalRedirect($currentUri->addParams([
					'find_section_section' => 0,
					'SECTION_ID' => 0,
					'apply_filter' => 'Y'
				])->getUri());
			}
		}
	}

	protected function getTemplateUrls(): array
	{
		if ($this->iblockListMixed)
		{
			$result = [
				self::PAGE_INDEX => '',
				self::PAGE_LIST => 'list/#SECTION_ID#/',
				self::PAGE_SECTION_DETAIL => 'section/#SECTION_ID#/',
				self::PAGE_CSV_IMPORT => 'import/'
			];
		}
		else
		{
			$result = [
				self::PAGE_INDEX => '',
				self::PAGE_LIST => 'list/#SECTION_ID#/',
				self::PAGE_SECTION_LIST => 'section_list/#SECTION_ID#/',
				self::PAGE_SECTION_DETAIL => 'section/#SECTION_ID#/',
				self::PAGE_CSV_IMPORT => 'import/'
			];
		}

		$result = $result + \CatalogProductControllerComponent::getTemplateUrls();

		return $result;
	}

	protected function processSefMode(array $templateUrls): array
	{
		$templateUrls = \CComponentEngine::MakeComponentUrlTemplates($templateUrls, $this->arParams['SEF_URL_TEMPLATES']);
		foreach ($templateUrls as $name => $url)
		{
			$this->arResult['PATH_TO'][strtoupper($name)] = $this->arParams['SEF_FOLDER'].$url;
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

	protected function getPageDescription(): ?array
	{
		$result = null;

		$pageUrl = $this->request->getRequestUri();
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
						'URL_BUILDER_TYPE' => Crm\Product\Url\ProductBuilder::TYPE_ID,
						'SELF_FOLDER_URL' => '/shop/settings/'
					]
				];
				break;
			case self::PAGE_LIST:
			case self::PAGE_LIST_SLIDER:
				$result = [
					'PAGE_ID' => ($this->iblockListMixed ? 'crm_catalog_item_list' : 'crm_catalog_product_list'),
					'PAGE_PATH' => '/bitrix/modules/iblock/admin/'.($this->iblockListMixed
						? 'iblock_list_admin.php'
						: 'iblock_element_admin.php'
					),
					'PAGE_PARAMS' =>
						($this->pageId === self::PAGE_LIST_SLIDER)
							? $queryString
							: $this->urlBuilder->getBaseParams()
					,
					'SEF_FOLDER' => '/', // hack for template files
					'INTERNAL_PAGE' => 'N',
					'CACHE_TYPE' => 'N',
					'PAGE_CONSTANTS' => [
						'CATALOG_PRODUCT' => 'Y',
						'URL_BUILDER_TYPE' => Crm\Product\Url\ProductBuilder::TYPE_ID,
						'SELF_FOLDER_URL' => '/shop/settings/'
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
						'URL_BUILDER_TYPE' => Crm\Product\Url\ProductBuilder::TYPE_ID,
						'SELF_FOLDER_URL' => '/shop/settings/'
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
						'URL_BUILDER_TYPE' => Crm\Product\Url\ProductBuilder::TYPE_ID,
						'SELF_FOLDER_URL' => '/shop/settings/'
					]
				];
				break;
			case self::PAGE_PRODUCT_DETAIL:
				$result = [];
				break;
			case self::PAGE_CSV_IMPORT:
				$templateUrls = $this->getTemplateUrls();
				$result = [
					'PATH_TO_PRODUCT_LIST' => $this->arParams['SEF_FOLDER'],
					'PATH_TO_PRODUCT_IMPORT' => $this->arParams['SEF_FOLDER'].$templateUrls[self::PAGE_CSV_IMPORT]
				];
				unset($templateUrls);
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
			'admin_interface',
			'sidepanel'
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
