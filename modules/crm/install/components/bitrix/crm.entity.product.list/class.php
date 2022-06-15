<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Catalog\Component\ImageInput;
use Bitrix\Catalog\StoreProductTable;
use Bitrix\Catalog\v2\IoC\ServiceContainer;
use Bitrix\Crm;
use Bitrix\Crm\Discount;
use Bitrix\Crm\Product\Url\ProductBuilder;
use Bitrix\Crm\Component\EntityDetails\ProductList;
use Bitrix\Iblock;
use Bitrix\Iblock\Url\AdminPage\BuilderManager;
use Bitrix\Main;
use Bitrix\Main\Grid\Editor\Types;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Web\Json;
use Bitrix\Sale;
use Bitrix\Catalog;
use Bitrix\Catalog\StoreTable;
use Bitrix\Crm\Service\Container;
use Bitrix\UI\Util;
use Bitrix\Crm\Service\Sale\Reservation\ReservationService;

if (!Loader::includeModule('crm'))
{
	return;
}

final class CCrmEntityProductListComponent
	extends \CBitrixComponent
	implements Main\Engine\Contract\Controllerable, Main\Errorable
{
	use Main\ErrorableImplementation;

	protected const STORAGE_GRID = 'GRID';
	private const PREFIX_MEASURE_INDEX = 'measure_';
	private const NEW_ROW_ID_PREFIX = 'n';

	/** @var Main\Grid\Options $gridConfig */
	protected $gridConfig;
	protected $storage = [];
	protected $defaultSettings = [];
	protected $rows = [];

	/** @var Main\UI\PageNavigation $navigation */
	protected $navigation;
	protected $implicitPageNavigation = false;

	protected $entity = [
		'TYPE_NAME' => null,
		'TYPE_CODE' => null,
		'TYPE_ID' => \CCrmOwnerType::Undefined,
		'ID' => 0,
		'CRM_FORMAT' => true,
		'SETTINGS' => [],
	];

	protected $crmSettings = [];

	protected $currency = [
		'ID' => '',
		'TEMPLATE' => '',
		'TEXT' => '',
		'FORMAT' => [],
	];
	protected $measures = [];
	protected $stores = [];
	protected $productVatList = [];
	protected $discountTypes = [];
	protected $newRowCounter = 0;

	/**
	 * Base constructor.
	 *
	 * @param \CBitrixComponent|null $component Component object if exists.
	 */
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new Main\ErrorCollection();
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	/**
	 * @param $params
	 * @return array
	 */
	public function onPrepareComponentParams($params): array
	{
		/**
		 * GRID_ID - string - custom grid id
		 * NAVIGATION_ID - string - custom navigation id (may be create from GRID_ID)
		 * FORM_ID - string - custom form identifier (may be create from GRID_ID), default empty
		 * TAB_ID - string - custom product tab identifier, default empty
		 *
		 * AJAX_ID - string - ajax component identifier
		 * AJAX_MODE - string - is ajax enabled (Y/N), default Y
		 * AJAX_OPTION_JUMP - string - ajax option (Y/N), default N
		 * AJAX_OPTION_HISTORY - string - ajax option (Y/N), default N
		 * AJAX_LOADER - mixed|null - not used in titleflex template, default null
		 *
		 * SHOW_PAGINATION - bool or Y/N - show pagination block, default false
		 * SHOW_TOTAL_COUNTER - bool or Y/N - show count of rows, default false
		 * SHOW_PAGESIZE - bool or Y/N - show page size select, default false
		 * PAGINATION - array - pagination info (pages size, offset, etc), default - empty array
		 *
		 * PRODUCTS - array|null - product list
		 * TOTAL_PRODUCTS_COUNT - int - full product rows quantity
		 *
		 * CUSTOM_SITE_ID - string - entity site identifier, default SITE_ID
		 * CUSTOM_LANGUAGE_ID - string - current lang identifier, default LANGUAGE_ID
		 * CURRENCY_ID - string - currency identifier, default - base currency
		 * SET_ITEMS - bool - set rows (Y/N), default N
		 * ALLOW_EDIT - bool - allow modify data (Y/N), default N
		 * ALLOW_ADD_PRODUCT - bool - add product to entity button (Y/N), default N
		 * ALLOW_CREATE_NEW_PRODUCT - bool - create fake products button (Y/N), default N
		 * if ALLOW_EDIT off - ALLOW_ADD_PRODUCT and ALLOW_CREATE_NEW_PRODUCT already off
		 *
		 * ENTITY_TYPE_NAME - string - code of entity type
		 * ENTITY_ID - string|int - parent entity id
		 */

		$this->prepareEntityIds($params);
		$this->prepareAjaxOptions($params);
		$this->preparePaginationOptions($params);
		$this->prepareProducts($params);
		$this->prepareSettings($params);
		$this->prepareEntitySettings($params);

		// white column list
		// black column list

		return $params;
	}

	/**
	 * @return void
	 */
	public function executeComponent(): void
	{
		$this->fillSettings();
		if ($this->isExistErrors())
		{
			$this->showErrors();

			return;
		}

		$this->loadData();

		$this->prepareResult();

		$this->includeComponentTemplate();
	}

	/** @noinspection PhpUnused
	 *
	 * @param array $products
	 * @param string $currencyId
	 * @param array $options
	 * @return null|array
	 */
	public function calculateTotalDataAction(array $products, $currencyId, array $options): ?array
	{
		$this->fillSettings();
		if ($this->isExistErrors())
		{
			return null;
		}

		if (!$this->checkUserPermissions())
		{
			return null;
		}

		$totalDiscount = 0;

		foreach ($products as &$productRow)
		{
			$productRow['ID'] = $productRow['ID'] ?? $this->getNewRowId();
			$productRow['PRODUCT_ID'] = (int)($productRow['PRODUCT_ID'] ?? 0);
			$productRow['PRODUCT_NAME'] = $productRow['PRODUCT_NAME'] ?? '';
			$productRow['NAME'] = $productRow['PRODUCT_NAME'];
			$productRow['QUANTITY'] = (float)($productRow['QUANTITY'] ?? 1);
			$productRow['DISCOUNT_TYPE_ID'] = (isset($productRow['DISCOUNT_TYPE_ID'])
				? (int)$productRow['DISCOUNT_TYPE_ID']
				: Discount::UNDEFINED
			);
			$productRow['DISCOUNT_TYPE_ID'] = (Discount::isDefined($productRow['DISCOUNT_TYPE_ID'])
				? $productRow['DISCOUNT_TYPE_ID']
				: Discount::PERCENTAGE
			);
			$productRow['DISCOUNT_RATE'] = (float)($productRow['DISCOUNT_RATE'] ?? 0);
			$productRow['DISCOUNT_SUM'] = (float)($productRow['DISCOUNT_SUM'] ?? 0);
			$productRow['PRICE'] = (float)($productRow['PRICE'] ?? 1);
			$productRow['PRICE_EXCLUSIVE'] = (float)($productRow['PRICE_EXCLUSIVE'] ?? 1);
			$productRow['CUSTOMIZED'] = isset($productRow['CUSTOMIZED']) && $productRow['CUSTOMIZED'] === 'Y' ? 'Y' : 'N';
			if ($productRow['CUSTOMIZED'] === 'Y')
			{
				$productRow['TAX_RATE'] = (float)($productRow['TAX_RATE'] ?? 0);
			}
			$productRow['TAX_INCLUDED'] = isset($productRow['TAX_INCLUDED']) && $productRow['TAX_INCLUDED'] === 'Y' ? 'Y' : 'N';

			$totalDiscount += round($productRow['DISCOUNT_SUM'] * $productRow['QUANTITY'], $this->crmSettings['PRICE_PRECISION']);
		}
		unset($productRow);

		$enableSaleDiscount = false;
		$calculateOptions = [];
		if ($this->crmSettings['ALLOW_LD_TAX'])
		{
			$calculateOptions['ALLOW_LD_TAX'] = 'Y';
			$calculateOptions['LOCATION_ID'] = isset($this->arParams['LOCATION_ID']) ? $this->arParams['LOCATION_ID'] : '';
		}
		$result = CCrmSaleHelper::Calculate(
			$products,
			$currencyId,
			$this->crmSettings['PERSON_TYPE_ID'],
			$enableSaleDiscount,
			$this->getSiteId(),
			$calculateOptions
		);

		if (!is_array($result))
		{
			$result = [];
		}

		$totalSum = isset($result['PRICE']) ? round((float)$result['PRICE'], $this->crmSettings['PRICE_PRECISION']) : 0;
		$totalTax = isset($result['TAX_VALUE']) ? round((float)$result['TAX_VALUE'], $this->crmSettings['PRICE_PRECISION']) : 0;
		$totalBeforeTax = round($totalSum - $totalTax, $this->crmSettings['PRICE_PRECISION']);
		$totalBeforeDiscount = round($totalBeforeTax + $totalDiscount, $this->crmSettings['PRICE_PRECISION']);
		$totalDelivery = $this->getTotalDeliverySum();
		$totalSum = $totalSum + $totalDelivery;

		$response = [
			'totalCost' => $totalSum,
			'totalDelivery' => $totalDelivery,
			'totalTax' => $totalTax,
			'totalWithoutTax' => $totalBeforeTax,
			'totalDiscount' => $totalDiscount,
			'totalWithoutDiscount' => $totalBeforeDiscount,
		];

		if ($this->crmSettings['ALLOW_LD_TAX'])
		{
			$taxes = (is_array($result['TAX_LIST'])) ? $result['TAX_LIST'] : null;
			$LDTaxes = [];
			if (empty($taxes) || !is_array($taxes))
			{
				$LDTaxes = [
					[
						'TAX_NAME' => GetMessage('CRM_PRODUCT_TOTAL_TAX'),
						'TAX_VALUE' => CCrmCurrency::MoneyToString($totalTax, $currencyId),
					],
				];
			}
			$LDTaxPrecision = isset($_POST['LD_TAX_PRECISION']) ? (int)$_POST['LD_TAX_PRECISION'] : 2;
			if (is_array($taxes))
			{
				foreach ($taxes as $taxInfo)
				{
					$LDTaxes[] = [
						'TAX_NAME' => sprintf(
							"%s%s%s",
							($taxInfo["IS_IN_PRICE"] == "Y") ? GetMessage('CRM_PRODUCT_TAX_INCLUDING') . " " : "",
							$taxInfo["NAME"],
							(/*$vat <= 0 &&*/ $taxInfo["IS_PERCENT"] == "Y")
								? sprintf(' (%s%%)', roundEx($taxInfo["VALUE"], $LDTaxPrecision))
								: ""
						),
						'TAX_VALUE' => CCrmCurrency::MoneyToString(
							$taxInfo['VALUE_MONEY'], $currencyId
						),
					];
				}
			}
			$response['locationTaxes'] = $LDTaxes;
		}

		return $this->getActionResponse($response, $options);
	}

	/** @noinspection PhpUnused
	 *
	 * @param array $products
	 * @param string currencyId
	 * @return null|array
	 */
	public function calculateProductPricesAction(array $products, string $currencyId, array $options): ?array
	{
		$response = [];

		foreach ($products as $product)
		{
			$fields = $product['fields'] ?? [];
			$oldCurrencyId = $fields['CURRENCY'];
			if (empty($oldCurrencyId))
			{
				continue;
			}

			if (isset($fields['TAX_INCLUDED'], $fields['PRICE_NETTO'], $fields['PRICE_BRUTTO']))
			{
				$basePrice = $fields['TAX_INCLUDED'] === 'Y' ? $fields['PRICE_BRUTTO'] : $fields['PRICE_NETTO'];
			}
			else
			{
				$basePrice = $fields['PRICE'] ?? 1;
			}

			$response[$product['id']] = [
				'CURRENCY_ID' => $currencyId,
				'PRICE' => CCrmCurrency::ConvertMoney($basePrice, $oldCurrencyId, $currencyId),
				'BASE_PRICE' => CCrmCurrency::ConvertMoney($basePrice, $oldCurrencyId, $currencyId),
				'ENTERED_PRICE' => CCrmCurrency::ConvertMoney($fields['ENTERED_PRICE'], $oldCurrencyId, $currencyId),
				'DISCOUNT_ROW' => CCrmCurrency::ConvertMoney($fields['DISCOUNT_ROW'], $oldCurrencyId, $currencyId),
				'DISCOUNT_SUM' => CCrmCurrency::ConvertMoney($fields['DISCOUNT_SUM'], $oldCurrencyId, $currencyId),
			];
		}

		return $this->getActionResponse($response, $options);
	}

	/**
	 * @param array $response
	 * @param array $options
	 * @return array|null
	 */
	protected function getActionResponse(array $response, array $options): ?array
	{
		return [
			'action' => $options['ACTION'],
			'result' => $response,
		];
	}

	/**
	 * @return array
	 */
	protected function listKeysSignedParameters()
	{
		return [
			// prepareEntityIds
			'GRID_ID',
			'NAVIGATION_ID',
			'FORM_ID',
			'TAB_ID',
			'AJAX_ID',
			// prepareAjaxOptions
			'AJAX_MODE',
			'AJAX_OPTION_JUMP',
			'AJAX_OPTION_HISTORY',
			'AJAX_LOADER',
			// preparePaginationOptions
			'SHOW_PAGINATION',
			'SHOW_TOTAL_COUNTER',
			'SHOW_PAGESIZE',
			// prepareSettings
			'CUSTOM_SITE_ID',
			'CUSTOM_LANGUAGE_ID',
			'CURRENCY_ID',
			'SET_ITEMS',
			'ALLOW_EDIT',
			'ALLOW_ADD_PRODUCT',
			'ALLOW_CREATE_NEW_PRODUCT',
			'PREFIX',
			'ID',
			'PRODUCT_DATA_FIELD_NAME',
			'PERSON_TYPE_ID',
			'ALLOW_LD_TAX',
			'LOCATION_ID',
			// prepareEntitySettings
			'ENTITY_TYPE_NAME',
			'ENTITY_ID',
		];
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
	}

	/**
	 * @param string $message
	 * @return void
	 */
	protected function addErrorMessage(string $message): void
	{
		$this->errorCollection->setError(new Main\Error($message));
	}

	/**
	 * @param array &$params
	 * @return void
	 */
	protected function prepareEntityIds(array &$params): void
	{
		static::validateListParameters(
			$params,
			[
				'GRID_ID',
				'NAVIGATION_ID',
				'FORM_ID',
				'TAB_ID',
				'AJAX_ID',
			]
		);

		if (!empty($params['GRID_ID']))
		{
			if (empty($params['NAVIGATION_ID']))
			{
				$params['NAVIGATION_ID'] = static::createNavigationId($params['GRID_ID']);
			}
			if (!isset($params['FORM_ID']))
			{
				$params['FORM_ID'] = static::createFormId($params['GRID_ID']);
			}
		}
	}

	/**
	 * @param array &$params
	 * @return void
	 */
	protected function prepareAjaxOptions(array &$params): void
	{
		$params['AJAX_MODE'] = isset($params['AJAX_MODE']) && $params['AJAX_MODE'] === 'N' ? 'N' : 'Y';
		$params['AJAX_OPTION_JUMP'] = isset($params['AJAX_OPTION_JUMP']) && $params['AJAX_OPTION_JUMP'] === 'Y' ? 'Y' : 'N';
		$params['AJAX_OPTION_HISTORY'] = isset($params['AJAX_OPTION_HISTORY']) && $params['AJAX_OPTION_HISTORY'] === 'Y' ? 'Y' : 'N';
		$params['AJAX_LOADER'] = $params['AJAX_LOADER'] ?? null;
	}

	/**
	 * @param array &$params
	 * @return void
	 */
	protected function preparePaginationOptions(array &$params): void
	{
		static::validateBoolList(
			$params,
			[
				'SHOW_PAGINATION',
				'SHOW_TOTAL_COUNTER',
				'SHOW_PAGESIZE',
			]
		);

		if (empty($params['PAGINATION']) || !is_array($params['PAGINATION']))
		{
			$params['PAGINATION'] = [];
		}
	}

	/**
	 * @param array &$params
	 * @return void
	 */
	protected function prepareProducts(array &$params): void
	{
		if (isset($params['PRODUCTS']) && !is_array($params['PRODUCTS']))
		{
			$params['PRODUCTS'] = null;
		}

		if (!isset($params['TOTAL_PRODUCTS_COUNT']) && is_array($params['PRODUCTS']))
		{
			$params['TOTAL_PRODUCTS_COUNT'] = count($params['PRODUCTS']);
		}

		$params['TOTAL_PRODUCTS_COUNT'] = (int)($params['TOTAL_PRODUCTS_COUNT'] ?? 0);
	}

	/**
	 * @param array &$params
	 * @return void
	 */
	protected function prepareSettings(array &$params): void
	{
		$params['CUSTOM_SITE_ID'] = (isset($params['CUSTOM_SITE_ID']) && is_string($params['CUSTOM_SITE_ID'])
			? $params['CUSTOM_SITE_ID']
			: ''
		);
		if ($params['CUSTOM_SITE_ID'] !== '')
		{
			$this->setSiteId($params['CUSTOM_SITE_ID']);
		}

		$params['CUSTOM_LANGUAGE_ID'] = (isset($params['CUSTOM_LANGUAGE_ID']) && is_string($params['CUSTOM_LANGUAGE_ID'])
			? $params['CUSTOM_LANGUAGE_ID']
			: ''
		);
		if ($params['CUSTOM_LANGUAGE_ID'] !== '')
		{
			$this->setLanguageId($params['CUSTOM_LANGUAGE_ID']);
		}

		$params['CURRENCY_ID'] = isset($params['CURRENCY_ID']) && is_string($params['CURRENCY_ID'])
			? $params['CURRENCY_ID']
			: '';

		$baseGroup = \CCatalogGroup::GetBaseGroup();
		$params['BASE_PRICE_ID'] = (is_array($baseGroup) && isset($baseGroup['ID'])) ? (int)$baseGroup['ID'] : null;

		$params['SET_ITEMS'] = isset($params['SET_ITEMS']) && $params['SET_ITEMS'] === 'Y';
		$params['ALLOW_EDIT'] = isset($params['ALLOW_EDIT']) && $params['ALLOW_EDIT'] === 'Y';

		$params['ALLOW_ADD_PRODUCT'] = isset($params['ALLOW_ADD_PRODUCT']) && $params['ALLOW_ADD_PRODUCT'] === 'Y';
		$params['ALLOW_CREATE_NEW_PRODUCT'] = isset($params['ALLOW_CREATE_NEW_PRODUCT']) && $params['ALLOW_CREATE_NEW_PRODUCT'] === 'Y';

		if (!$params['ALLOW_EDIT'])
		{
			$params['ALLOW_ADD_PRODUCT'] = false;
			$params['ALLOW_CREATE_NEW_PRODUCT'] = false;
		}

		$params['PREFIX'] = (isset($params['PREFIX']) && is_string($params['PREFIX']) ? trim($params['PREFIX']) : '');
		$params['ID'] = (isset($params['ID']) && is_string($params['ID']) ? trim($params['ID']) : '');
		$params['PRODUCT_DATA_FIELD_NAME'] = isset($params['PRODUCT_DATA_FIELD_NAME']) ? $params['PRODUCT_DATA_FIELD_NAME'] : 'PRODUCT_ROW_DATA';
	}

	/**
	 * @param array &$params
	 * @return void
	 */
	protected function prepareEntitySettings(array &$params): void
	{
		$params['ENTITY_TYPE_NAME'] = (isset($params['ENTITY_TYPE_NAME']) && is_string($params['ENTITY_TYPE_NAME'])
			? $params['ENTITY_TYPE_NAME']
			: ''
		);
		$entityTypeId = \CCrmOwnerType::ResolveID($params['ENTITY_TYPE_NAME']);
		if (
			$entityTypeId === \CCrmOwnerType::Order
			|| !\CCrmOwnerType::IsDefined($entityTypeId)
		)
		{
			$this->addErrorMessage(Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_ERR_BAD_ENTITY_TYPE'));
		}
		$params['ENTITY_ID'] = (isset($params['ENTITY_ID']) ? (int)$params['ENTITY_ID'] : 0);
		if ($params['ENTITY_ID'] < 0)
		{
			$this->addErrorMessage(Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_ERR_BAD_ENTITY_ID'));
		}
		if (!$this->isExistErrors())
		{
			$this->entity['TYPE_NAME'] = $params['ENTITY_TYPE_NAME'];
			$this->entity['ID'] = $params['ENTITY_ID'];
			$this->entity['TITLE'] = $params['ENTITY_TITLE'];
			if ($this->entity['TYPE_NAME'] === \CCrmOwnerType::OrderName)
			{
				$this->entity['CRM_FORMAT'] = false;
			}
			else
			{
				$this->entity['TYPE_CODE'] = \CCrmOwnerTypeAbbr::ResolveByTypeName($this->entity['TYPE_NAME']);
			}
			$this->entity['TYPE_ID'] = \CCrmOwnerType::ResolveID($this->entity['TYPE_NAME']);
			if ($this->entity['ID'] > 0)
			{
				$settings = \CCrmProductRow::LoadSettings($this->entity['TYPE_CODE'], $this->entity['ID']);

				if (!empty($settings))
				{
					$this->entity['SETTINGS'] = $settings;
				}
				unset($settings);
			}
		}
	}

	/**
	 * @return void
	 */
	protected function fillSettings(): void
	{
		$this->checkModules();
		$this->initDefaultSettings();
		$this->loadReferences();
		$this->initSettings();
	}

	/**
	 * @return void
	 */
	protected function useImplicitPageNavigation()
	{
		$this->implicitPageNavigation = true;
	}

	/**
	 * @return bool
	 */
	protected function isUsedImplicitPageNavigation()
	{
		return $this->implicitPageNavigation;
	}

	/**
	 * @return void
	 */
	protected function initDefaultSettings(): void
	{
		$this->defaultSettings = [
			'GRID_ID' => self::getDefaultGridId(),
		];
		$this->defaultSettings['NAVIGATION_ID'] = static::createNavigationId($this->defaultSettings['GRID_ID']);
		$this->defaultSettings['FORM_ID'] = static::createFormId($this->defaultSettings['GRID_ID']);
		$this->defaultSettings['TAB_ID'] = '';
		$this->defaultSettings['AJAX_ID'] = '';
		$this->defaultSettings['PAGE_SIZES'] = [5, 10, 20, 50, 100];
		$this->defaultSettings['NEW_ROW_POSITION'] = CUserOptions::GetOption("crm.entity.product.list", 'new.row.position', 'top');
		$this->defaultSettings['ALLOW_CATALOG_PRICE_EDIT'] = CUserOptions::GetOption("crm.entity.product.list", 'new.row.position', 'top');
	}

	/**
	 * @return string
	 */
	public static function getDefaultGridId(): string
	{
		return self::clearStringValue(self::class);
	}

	/**
	 * @param string $gridId
	 * @return string
	 */
	protected static function createNavigationId(string $gridId): string
	{
		return $gridId . '_NAVIGATION';
	}

	/**
	 * @param string $gridId
	 * @return string
	 */
	protected static function createFormId(string $gridId): string
	{
		return 'form_' . $gridId;
	}

	/**
	 * @return void
	 */
	protected function initEntitySettings(): void
	{

	}

	/**
	 * @return void
	 */
	protected function initSettings(): void
	{
		$this->initEntitySettings();

		$paramsList = [
			self::STORAGE_GRID => [
				'GRID_ID',
				'NAVIGATION_ID',
				'PAGE_SIZES',
				'FORM_ID',
				'TAB_ID',
				'AJAX_ID',
				'NEW_ROW_POSITION',
			],
		];
		foreach ($paramsList as $entity => $list)
		{
			foreach ($list as $param)
			{
				$value = !empty($this->arParams[$param]) ? $this->arParams[$param] : $this->defaultSettings[$param];
				$this->setStorageItem($entity, $param, $value);
			}
		}

		$this->initCrmSettings();

		$this->initGrid();
	}

	/**
	 * @return void
	 */
	protected function loadReferences(): void
	{
		$this->loadCurrency();
		$this->loadMeasures();
		$this->loadStores();
		$this->loadProductVatList();
		$this->loadDiscountTypes();
	}

	/**
	 * @return void
	 */
	protected function loadCurrency(): void
	{
		$this->currency['ID'] = ($this->arParams['CURRENCY_ID'] !== ''
			? $this->arParams['CURRENCY_ID']
			: \CCrmCurrency::GetDefaultCurrencyID()
		);
		$this->currency['TEMPLATE'] = \CCrmCurrency::GetCurrencyFormatString($this->currency['ID']);
		$this->currency['TEXT'] = \CCrmCurrency::getCurrencyText($this->currency['ID']);
		$format = \CCrmCurrency::GetCurrencyFormatParams($this->currency['ID']);
		$this->currency['FORMAT'] = [
			'TEMPLATE' => $format['TEMPLATE'],
			'FORMAT_STRING' => $format['FORMAT_STRING'],
			'DEC_POINT' => $format['DEC_POINT'],
			'THOUSANDS_SEP' => $format['THOUSANDS_SEP'],
			'DECIMALS' => $format['DECIMALS'],
			'THOUSANDS_VARIANT' => $format['THOUSANDS_VARIANT'],
			'HIDE_ZERO' => $format['HIDE_ZERO'],
		];
		unset($format);
	}

	/**
	 * @return void
	 */
	protected function loadStores(): void
	{
		$this->stores = [];
		if (!$this->isAllowedInventoryManagement())
		{
			return;
		}

		$productStoreRaw = StoreTable::getList([
			'filter' => ['=ACTIVE' => 'Y'],
			'select' => ['ID', 'TITLE', 'IS_DEFAULT'],
		]);

		while ($store = $productStoreRaw->fetch())
		{
			if ($store['TITLE'] === '')
			{
				$store['TITLE'] = Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_EMPTY_STORE_TITLE');
			}

			$this->stores[$store['ID']] = $store;
		}
	}

	/**
	 * @return array
	 */
	protected function getCurrency(): array
	{
		return $this->currency;
	}

	/**
	 * @return string
	 */
	protected function getCurrencyId(): string
	{
		return $this->currency['ID'];
	}

	/**
	 * @return string
	 */
	protected function getDefaultStoreId(): int
	{
		foreach ($this->stores as $store)
		{
			if ($store['IS_DEFAULT'] === 'Y')
			{
				return $store['ID'];
			}
		}

		return 0;
	}

	/**
	 * @return string
	 */
	protected function getCurrencyTemplate(): string
	{
		return $this->currency['TEMPLATE'];
	}

	/**
	 * @return string
	 */
	protected function getCurrencyText(): string
	{
		return $this->currency['TEXT'];
	}

	/**
	 * @return array
	 */
	protected function getCurrencyFormat(): array
	{
		return $this->currency['FORMAT'];
	}

	/**
	 * @return void
	 */
	protected function loadMeasures(): void
	{
		$this->measures['LIST'] = [];
		// TODO: remove limit after creation new measure control
		foreach (Crm\Measure::getMeasures(100) as $row)
		{
			$this->measures['LIST'][self::PREFIX_MEASURE_INDEX . $row['CODE']] = [
				'CODE' => (string)$row['CODE'],
				'SYMBOL' => $row['SYMBOL'],
			];
		}

		$this->measures['DEFAULT'] = Crm\Measure::getDefaultMeasure();
		$this->measures['DEFAULT']['CODE'] = (string)$this->measures['DEFAULT']['CODE'];
		$this->measures['DEFAULT']['SYMBOL'] = htmlspecialcharsback($this->measures['DEFAULT']['SYMBOL']);
	}

	/**
	 * @return void
	 */
	protected function loadProductVatList(): void
	{
		foreach (CCrmTax::GetVatRateInfos() as $vatRow)
		{
			$this->productVatList[$vatRow['ID']] = $vatRow['VALUE'];
		}

		asort($this->productVatList, SORT_NUMERIC);
	}

	/**
	 * @return void
	 */
	protected function loadDiscountTypes(): void
	{
		$this->discountTypes[Discount::MONETARY] = $this->getCurrencyText();
		$this->discountTypes[Discount::PERCENTAGE] = '%';
	}

	/**
	 * Returns measure by code, if exists
	 *
	 * @param string $code Measure code.
	 * @return array|null
	 */
	protected function getMeasureByCode(string $code): ?array
	{
		if ($code === '')
		{
			return null;
		}
		if (!isset($this->measures['LIST'][self::PREFIX_MEASURE_INDEX . $code]))
		{
			$this->measures['LIST'][self::PREFIX_MEASURE_INDEX . $code] = false;
			$measure = Crm\Measure::getMeasureByCode($code);
			if (!empty($measure))
			{
				$this->measures['LIST'][self::PREFIX_MEASURE_INDEX . $code] = [
					'CODE' => $measure['CODE'],
					'SYMBOL' => $measure['SYMBOL'],
				];
			}
		}

		return (
			!empty($this->measures['LIST'][self::PREFIX_MEASURE_INDEX . $code])
				? $this->measures['LIST'][self::PREFIX_MEASURE_INDEX . $code]
				: null
		);
	}

	protected function checkModules(): bool
	{
		if (!Loader::includeModule('catalog'))
		{
			$this->addErrorMessage('Module "catalog" is not installed.');

			return false;
		}

		if (!Loader::includeModule('sale'))
		{
			$this->addErrorMessage('Module "sale" is not installed.');

			return false;
		}

		return true;
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
	 * @return void
	 */
	protected function loadData(): void
	{
		$this->rows = [];

		if (!$this->entity['CRM_FORMAT'])
		{
			return;
		}

		$shippedRowMap = Container::getInstance()->getShipmentProductService()->getShippedQuantityByEntity(
			$this->entity['TYPE_ID'],
			$this->entity['ID']
		);

		if (is_array($this->arParams['~PRODUCTS']))
		{
			$this->rows = $this->arParams['~PRODUCTS'];

			foreach ($this->rows as $index => &$row)
			{
				if (!isset($row['ID']))
				{
					$row['ID'] = $this->getNewRowId();
				}

				$id = $row['ID'];
				$row['DEDUCTED_QUANTITY'] = null;
				if ((int)$row['PRODUCT_ID'] > 0)
				{
					$row['DEDUCTED_QUANTITY'] = $shippedRowMap[$id] ?? 0.0;
				}

				$intFields = [
					'IBLOCK_ID',
					'BASE_PRICE_ID',
					'PARENT_PRODUCT_ID',
					'PRODUCT_ID',
					'OFFERS_IBLOCK_ID',
					'OFFER_ID',
					'DISCOUNT_TYPE_ID',
					'SORT',
					'STORE_ID',
				];
				foreach ($intFields as $name)
				{
					if (isset($this->rows[$index][$name]))
					{
						$this->rows[$index][$name] = (int)$this->rows[$index][$name];
					}
				}

				$floatFields = [
					'QUANTITY',
					'DISCOUNT_RATE',
					'DISCOUNT_SUM',
					'DISCOUNT_ROW',
					'PRICE',
					'BASE_PRICE',
					'PRICE_EXCLUSIVE',
					'PRICE_NETTO',
					'PRICE_BRUTTO',
					'TAX_RATE',
					'TAX_SUM',
					'SUM',
				];
				foreach ($floatFields as $name)
				{
					if (isset($this->rows[$index][$name]))
					{
						$this->rows[$index][$name] = (float)$this->rows[$index][$name];
					}
				}
			}

			$this->rows = ReservationService::getInstance()->fillBasketReserves($this->rows);
		}
		elseif ($this->entity['ID'] > 0)
		{
			$this->rows = CCrmProductRow::LoadRows($this->entity['TYPE_CODE'], $this->entity['ID']);
			if ($this->rows && $this->isAllowedInventoryManagement())
			{
				$reserveData = \Bitrix\Crm\Reservation\Internals\ProductRowReservationTable::getList([
					'filter' => [
						'=ROW_ID' => array_column($this->rows, 'ID'),
					],
					'select' => [
						'INPUT_RESERVE_QUANTITY' => 'RESERVE_QUANTITY',
						'DATE_RESERVE_END',
						'ROW_ID',
						'STORE_ID',
					],
				]);

				$reserveRowMap = [];
				while ($reservation = $reserveData->fetch())
				{
					$rowId = $reservation['ROW_ID'];
					unset($reservation['ROW_ID']);

					if ($reservation['DATE_RESERVE_END'] instanceof Date)
					{
						$reservation['DATE_RESERVE_END'] = $reservation['DATE_RESERVE_END']->toString();
					}
					$reserveRowMap[$rowId] = $reservation;
				}

				foreach ($this->rows as &$row)
				{
					$id = $row['ID'];

					if ($reserveRowMap[$id])
					{
						$row += $reserveRowMap[$id];
					}

					$row['DEDUCTED_QUANTITY'] = null;
					if ((int)$row['PRODUCT_ID'] > 0)
					{
						$row['DEDUCTED_QUANTITY'] = $shippedRowMap[$id] ?? 0.0;
					}
				}
			}
		}
	}

	/**
	 * @return void
	 */
	protected function prepareResult(): void
	{
		$this->initUiScope();

		$grid = [
			'NAV_OBJECT' => $this->navigation,
			'~NAV_PARAMS' => ['SHOW_ALWAYS' => false],
			'SHOW_ROW_CHECKBOXES' => false,

			'SHOW_SELECTED_COUNTER' => false,
			'ACTION_PANEL' => $this->getGridActionPanel(),

			// checked
			'GRID_ID' => $this->getGridId(),
			'COLUMNS' => array_values($this->getColumns()),
			'VISIBLE_COLUMNS' => array_values($this->getVisibleColumns()),

			'AJAX_ID' => $this->getStorageItem(self::STORAGE_GRID, 'AJAX_ID'),
			'AJAX_MODE' => $this->arParams['~AJAX_MODE'],
			'AJAX_OPTION_JUMP' => $this->arParams['~AJAX_OPTION_JUMP'],
			'AJAX_OPTION_HISTORY' => $this->arParams['~AJAX_OPTION_HISTORY'],
			'AJAX_LOADER' => $this->arParams['~AJAX_LOADER'],

			'SHOW_NAVIGATION_PANEL' => false,
			//'SHOW_PAGINATION' => $this->arParams['~SHOW_PAGINATION'],
			'SHOW_PAGINATION' => false,
			//'SHOW_TOTAL_COUNTER' => $this->arParams['~SHOW_TOTAL_COUNTER'],
			'SHOW_TOTAL_COUNTER' => false,
			//'SHOW_PAGESIZE' => $this->arParams['~SHOW_PAGESIZE'],
			'SHOW_PAGESIZE' => false,
			//'PAGINATION' => $this->arParams['~PAGINATION'],
			'PAGINATION' => [],

			'FORM_ID' => $this->getStorageItem(self::STORAGE_GRID, 'FORM_ID'),
			'TAB_ID' => $this->getStorageItem(self::STORAGE_GRID, 'TAB_ID'),
		];

		$grid['SORT'] = $this->getStorageItem(self::STORAGE_GRID, 'GRID_ORDER');
		$grid['SORT_VARS'] = $this->getStorageItem(self::STORAGE_GRID, 'GRID_ORDER_VARS');

		$grid['ROWS'] = $this->getGridRows();
		$grid['TOTAL_ROWS_COUNT'] = $this->arParams['~TOTAL_PRODUCTS_COUNT'];

		$this->arResult['GRID'] = $grid;
		$this->arResult['SETTINGS'] = $this->getSettings();
		$this->arResult['ENTITY'] = $this->entity;
		$this->arResult['CATALOG_TYPE_ID'] = \CCrmCatalog::GetCatalogTypeID();
		$this->arResult['CATALOG_ID'] = \CCrmCatalog::EnsureDefaultExists();
		$this->arResult['COMPONENT_ID'] = $this->randString();
		$this->arResult['NEW_ROW_POSITION'] = $this->getStorageItem(self::STORAGE_GRID, 'NEW_ROW_POSITION');
		unset($grid);

		$this->fillCrmSettings();

		$this->prepareReferencesToResult();

		$this->arResult['PREFIX'] = $this->arParams['PREFIX'];
		if ($this->arResult['PREFIX'] === '')
		{
			$this->arResult['PREFIX'] = $this->getDefaultPrefix();
		}

		$this->arResult['ID'] = $this->arParams['ID'];
		if ($this->arResult['ID'] === '')
		{
			$this->arResult['ID'] = $this->arResult['PREFIX'];
		}

		$this->arResult['URL_BUILDER_CONTEXT'] = ProductBuilder::TYPE_ID;
		$this->arResult['PRODUCT_DATA_FIELD_NAME'] = $this->arParams['PRODUCT_DATA_FIELD_NAME'];
		$this->arResult['DEFAULT_DATE_RESERVATION'] = (string)ReservationService::getInstance()->getDefaultDateReserveEnd();

		if(
			Bitrix\Main\Loader::includeModule('pull')
			&& $this->entity['TYPE_NAME'] === CCrmOwnerType::DealName
			&& !$this->isAllowedInventoryManagement()
		)
		{
			global $USER;
			\CPullWatch::Add($USER->GetID(), 'CATALOG_INVENTORY_MANAGEMENT_CHANGED');
		}
	}

	protected function getGridActionPanel(): array
	{
		return [];
	}

	/**
	 * @return array
	 */
	protected function getUiExtensions(): array
	{
		return [
			'core',
			'ajax',
			'tooltip',
			'ui.hint',
			'ui.fonts.ruble',
			'ui.notification',
			'currency.currency-core',
			'catalog.product-calculator',
			'catalog.product-model',
			'catalog.store-selector',
			'catalog.product-selector',
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

	/**
	 * @return array
	 */
	protected function getSettings(): array
	{
		$result = [
			'SITE_ID' => $this->getSiteId(),
			'LANGUAGE_ID' => $this->getLanguageId(),
			'SET_ITEMS' => $this->arParams['SET_ITEMS'],
			'ALLOW_EDIT' => $this->arParams['ALLOW_EDIT'],
		];

		$result['NEW_ROW_ID_PREFIX'] = self::NEW_ROW_ID_PREFIX;
		$result['NEW_ROW_ID_COUNTER'] = $this->getNewRowCounter();

		return $result;
	}

	/* Storage tools */

	/**
	 * @param string $node
	 * @param array $nodeValues
	 * @return void
	 */
	protected function fillStorageNode(string $node, array $nodeValues): void
	{
		if ($node === '' || empty($nodeValues))
		{
			return;
		}

		if (!isset($this->storage[$node]))
		{
			$this->storage[$node] = [];
		}

		$this->storage[$node] = array_merge($this->storage[$node], $nodeValues);
	}

	/**
	 * @param string $node
	 * @return array|null
	 */
	protected function getStorageNode(string $node): ?array
	{
		return $this->storage[$node] ?? null;
	}

	/**
	 * @param string $node
	 * @param string $item
	 * @param mixed $value
	 * @return void
	 */
	protected function setStorageItem(string $node, string $item, $value): void
	{
		$this->fillStorageNode($node, [$item => $value]);
	}

	/**
	 * @param string $node
	 * @param string $item
	 * @return mixed|null
	 */
	protected function getStorageItem(string $node, string $item)
	{
		return $this->storage[$node][$item] ?? null;
	}

	/**
	 * @return string
	 */
	protected function getGridId(): ?string
	{
		return $this->getStorageItem(self::STORAGE_GRID, 'GRID_ID');
	}

	/**
	 * @return string
	 */
	protected function getNavigationId(): string
	{
		return $this->getStorageItem(self::STORAGE_GRID, 'NAVIGATION_ID');
	}

	/**
	 * @return array
	 */
	protected function getPageSizes(): array
	{
		return $this->getStorageItem(self::STORAGE_GRID, 'PAGE_SIZES');
	}

	/* Storage tools finish */

	/**
	 * @return void
	 */
	protected function initGrid(): void
	{
		$this->initGridConfig();
		$this->initGridColumns();
		$this->initGridPageNavigation();
		$this->initGridOrder();
	}

	/**
	 * @return void
	 */
	protected function initGridConfig(): void
	{
		$this->gridConfig = new Main\Grid\Options($this->getGridId());
	}

	/**
	 * @return void
	 */
	protected function initGridColumns(): void
	{
		$visibleColumns = [];
		$visibleColumnsMap = [];

		$defaultList = true;
		$userColumnsIndex = [];
		$userColumns = $this->getUserGridColumnIds();
		if (!empty($userColumns))
		{
			$defaultList = false;
			$userColumnsIndex = array_fill_keys($userColumns, true);
		}

		$columns = $this->getGridColumnsDescription();
		foreach (array_keys($columns) as $index)
		{
			if ($defaultList || isset($userColumnsIndex[$index]))
			{
				$visibleColumnsMap[$index] = true;
				$visibleColumns[$index] = $columns[$index];
			}
		}

		$this->fillStorageNode(self::STORAGE_GRID, [
			'COLUMNS' => $columns,
			'VISIBLE_COLUMNS' => $visibleColumns,
			'VISIBLE_COLUMNS_MAP' => $visibleColumnsMap,
		]);
	}

	/**
	 * @return void
	 */
	protected function initGridPageNavigation(): void
	{
		$naviParams = $this->getGridNavigationParams();
		$this->navigation = new Main\UI\PageNavigation($this->getNavigationId());
		$this->navigation->setPageSizes($this->getPageSizes());
		$this->navigation->allowAllRecords(false);
		$this->navigation->setPageSize($naviParams['nPageSize']);

		if (!$this->isUsedImplicitPageNavigation())
		{
			$this->navigation->initFromUri();
		}
	}

	/**
	 * @return array
	 */
	protected function getGridNavigationParams(): array
	{
		return $this->gridConfig->getNavParams(['nPageSize' => 20]);
	}

	/**
	 * @return void
	 */
	protected function initGridOrder(): void
	{
		$result = ['ID' => 'DESC'];

		$sorting = $this->gridConfig->getSorting(['sort' => $result]);

		$order = strtolower(reset($sorting['sort']));
		if ($order !== 'asc')
			$order = 'desc';
		$field = key($sorting['sort']);
		$found = false;

		foreach ($this->getVisibleColumns() as $column)
		{
			if (!isset($column['sort']))
				continue;
			if ($column['sort'] == $field)
			{
				$found = true;
				break;
			}
		}
		unset($column);

		if ($found)
			$result = [$field => $order];

		$this->fillStorageNode(
			self::STORAGE_GRID,
			[
				'GRID_ORDER' => $this->modifyGridOrder($result),
				'GRID_ORDER_VARS' => $sorting['vars'],
			]
		);

		unset($found, $field, $order, $sorting, $result);
	}

	/**
	 * @param array $order
	 * @return array
	 */
	protected function modifyGridOrder(array $order): array
	{
		return $order;
	}

	protected function getCurrencyListForMoneyField(): array
	{
		return [
			$this->getCurrencyId() => $this->getCurrencyText(),
		];
	}

	protected function getDiscountListForMoneyField(): array
	{
		$currencyList = $this->getCurrencyListForMoneyField();

		return [
			Discount::PERCENTAGE => '%',
			Discount::MONETARY => reset($currencyList),
		];
	}

	protected function getMeasureListForMoneyField(): array
	{
		return array_column($this->measures['LIST'], 'SYMBOL', 'CODE');
	}

	/**
	 * @return array
	 */
	protected function getGridColumnsDescription(): array
	{
		$result = [];
		$columnDefaultWidth = 150;

		$result['MAIN_INFO'] = [
			'id' => 'MAIN_INFO',
			'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_MAIN_INFO'),
			'sort' => 'NAME',
		];

		$result['PRICE'] = [
			'id' => 'PRICE',
			'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_PRICE'),
			'sort' => 'PRICE',
			'editable' => [
				'TYPE' => Types::MONEY,
				'CURRENCY_LIST' => $this->getCurrencyListForMoneyField(),
				'PLACEHOLDER' => '0',
				'HTML_ENTITY' => true,
			],
			'align' =>
				$this->isReadOnly()
					? 'left'
					: 'right'
			,
			'width' => $columnDefaultWidth,
		];
		$result['QUANTITY'] = [
			'id' => 'QUANTITY',
			'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_QUANTITY'),
			'sort' => 'QUANTITY',
			'editable' => [
				'TYPE' => Types::MONEY,
				'CURRENCY_LIST' => $this->getMeasureListForMoneyField(),
				'PLACEHOLDER' => '0',
			],
			'align' =>
				$this->isReadOnly()
					? 'left'
					: 'right'
			,
			'width' => $columnDefaultWidth,
		];
		$result['DISCOUNT_PRICE'] = [
			'id' => 'DISCOUNT_PRICE',
			'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_DISCOUNT_PRICE'),
			'editable' => [
				'TYPE' => Types::MONEY,
				'CURRENCY_LIST' => $this->getDiscountListForMoneyField(),
				'PLACEHOLDER' => '0',
				'HTML_ENTITY' => true,
			],
			'align' =>
				$this->isReadOnly()
					? 'left'
					: 'right'
			,
			'width' => $columnDefaultWidth,
		];
		$result['DISCOUNT_ROW'] = [
			'id' => 'DISCOUNT_ROW',
			'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_DISCOUNT_ROW'),
			'editable' => [
				'TYPE' => Types::MONEY,
				'CURRENCY_LIST' => $this->getCurrencyListForMoneyField(),
				'PLACEHOLDER' => '0',
				'HTML_ENTITY' => true,
			],
			'align' =>
				$this->isReadOnly()
					? 'left'
					: 'right'
			,
			'width' => $columnDefaultWidth,
		];

		if ($this->crmSettings['ALLOW_TAX'])
		{
			$result['TAX_RATE'] = [
				'id' => 'TAX_RATE',
				'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_TAX_RATE'),
				'width' => $columnDefaultWidth,
			];
			$result['TAX_INCLUDED'] = [
				'id' => 'TAX_INCLUDED',
				'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_TAX_INCLUDED'),
				'width' => $columnDefaultWidth,
			];
			$result['TAX_SUM'] = [
				'id' => 'TAX_SUM',
				'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_TAX_SUM'),
				'editable' => false,
				'align' =>
					$this->isReadOnly()
						? 'left'
						: 'right'
				,
				'width' => $columnDefaultWidth,
			];
		}

		if ($this->isAllowedInventoryManagement())
		{
			$result['STORE_INFO'] = [
				'id' => 'STORE_INFO',
				'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_STORE_FROM_TITLE'),
				'title' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_STORE_FROM_INFO'),
				'hint' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_STORE_FROM_INFO_HINT'),
				'hintHtml' => true,
				'hintInteractivity' => true,
				'sort' => 'STORE_ID',
				'align' => 'right',
			];

			$linkUrl = Util::getArticleUrlByCode(14826418);
			$linkTitle = Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_HINT_ABOUT_TITLE');
			$articleLinkHtml = "<a class='crm-entity-product-helper-link' href='{$linkUrl}'target='blank'>{$linkTitle}</a>";

			$result['STORE_AVAILABLE'] = [
				'id' => 'STORE_AVAILABLE',
				'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_AVAILABLE'),
				'title' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_AVAILABLE'),
				'hint' => Loc::getMessage(
					'CRM_ENTITY_PRODUCT_LIST_COLUMN_AVAILABLE_HINT_2',
					[
						'#HELPER_HTML_LINK#' => $articleLinkHtml
					]
				),
				'hintHtml' => true,
				'hintInteractivity' => true,
				'sort' => 'STORE_AVAILABLE',
				'editable' => false,
				'align' => 'right',
				'width' => $columnDefaultWidth,
			];

			$result['RESERVE_INFO'] = [
				'id' => 'RESERVE_INFO',
				'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_STORE_FROM_RESERVED'),
				'title' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_STORE_FROM_RESERVED'),
				'hint' => Loc::getMessage(
					'CRM_ENTITY_PRODUCT_LIST_COLUMN_STORE_FROM_RESERVED_HINT',
					[
						'#HELPER_HTML_LINK#' => $articleLinkHtml
					]
				),
				'hintHtml' => true,
				'hintInteractivity' => true,
				'sort' => 'INPUT_RESERVE_QUANTITY',
				'align' => 'right',
			];

			$result['ROW_RESERVED'] = [
				'id' => 'ROW_RESERVED',
				'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_RESERVED_INTO_DEAL'),
				'title' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_RESERVED_INTO_DEAL'),
				'hint' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_RESERVED_INTO_DEAL_HINT'),
				'hintInteractivity' => true,
				'sort' => 'ROW_RESERVED',
				'align' => 'right',
				'editable' => false,
				'width' => $columnDefaultWidth,
			];

			$result['DEDUCTED_INFO'] = [
				'id' => 'DEDUCTED_INFO',
				'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_DEDUCTED'),
				'title' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_DEDUCTED'),
				'sort' => 'DEDUCTED_QUANTITY',
				'align' => 'right',
				'editable' => false,
			];
		}

		$result['SUM'] = [
			'id' => 'SUM',
			'name' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_COLUMN_SUMM'),
			'editable' => [
				'TYPE' => Types::MONEY,
				'CURRENCY_LIST' => $this->getCurrencyListForMoneyField(),
				'PLACEHOLDER' => '0',
				'HTML_ENTITY' => true,
			],
			'align' =>
				$this->isReadOnly()
					? 'left'
					: 'right'
			,
			'width' => $columnDefaultWidth,
		];

		$headerDefaultMap = Crm\Component\EntityDetails\ProductList::getHeaderDefaultMap();
		foreach ($result as $key => &$item)
		{
			$item['default'] = $headerDefaultMap[$key];
			if (empty($item['editable']) && $item['editable'] !== false)
			{
				$item['editable'] = [
					'TYPE' => Types::CUSTOM,
				];
			}
		}

		unset($item);

		return $result;
	}

	protected function getUserGridColumnIds(): array
	{
		$result = $this->gridConfig->GetVisibleColumns();

		if (!empty($result) && !in_array('ID', $result, true))
		{
			array_unshift($result, 'ID');
		}

		return $result;
	}

	/**
	 * @return array
	 */
	protected function getColumns()
	{
		return $this->getStorageItem(self::STORAGE_GRID, 'COLUMNS');
	}

	/**
	 * @return array
	 */
	protected function getVisibleColumns()
	{
		return $this->getStorageItem(self::STORAGE_GRID, 'VISIBLE_COLUMNS');
	}

	/**
	 * @return void
	 */
	protected function initCrmSettings(): void
	{
		$this->crmSettings['HIDE_ALL_TAXES'] = isset($this->arParams['HIDE_ALL_TAXES']) ? ($this->arParams['HIDE_ALL_TAXES'] === 'Y') : false;

		$this->crmSettings['ALLOW_TAX'] = isset($this->arParams['ALLOW_TAX']) ? ($this->arParams['ALLOW_TAX'] === 'Y') : \CCrmTax::isVatMode();
		$this->crmSettings['ALLOW_TAX'] = $this->crmSettings['ALLOW_TAX'] && !$this->crmSettings['HIDE_ALL_TAXES'];

		$this->crmSettings['ALLOW_LD_TAX'] = isset($this->arParams['ALLOW_LD_TAX']) ? ($this->arParams['ALLOW_LD_TAX'] === 'Y') : \CCrmTax::isTaxMode();
		$this->crmSettings['ALLOW_LD_TAX'] = $this->crmSettings['ALLOW_LD_TAX'] || $this->crmSettings['HIDE_ALL_TAXES'];

		$this->crmSettings['LOCATION_ID'] = isset($this->arParams['LOCATION_ID']) ? $this->arParams['LOCATION_ID'] : '';

		$this->crmSettings['PRODUCT_ROW_TAX_UNIFORM'] = (Main\Config\Option::get('crm', 'product_row_tax_uniform') === 'Y');
		$this->crmSettings['ALLOW_CATALOG_PRICE_EDIT'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isCatalogPriceEditEnabled();
		$this->crmSettings['ALLOW_CATALOG_PRICE_SAVE'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isCatalogPriceSaveEnabled();
		$priceNotification = \Bitrix\Crm\Config\State::getProductPriceChangingNotification();
		$this->crmSettings['CATALOG_PRICE_EDIT_ARTICLE_HINT'] = $priceNotification['MESSAGE'] ?? '';
		$this->crmSettings['CATALOG_PRICE_EDIT_ARTICLE_CODE'] = $priceNotification['ARTICLE_CODE'] ?? '';

		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		$personTypeId = 0;
		$this->crmSettings['CLIENT_SELECTOR_ID'] = isset($this->arParams['CLIENT_SELECTOR_ID']) ? $this->arParams['CLIENT_SELECTOR_ID'] : 'CLIENT';
		$this->crmSettings['CLIENT_TYPE_NAME'] = "CONTACT";
		if (isset($this->arParams['PERSON_TYPE_ID']) && isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
			$personTypeId = (int)$this->arParams['PERSON_TYPE_ID'];
		if ($personTypeId > 0)
		{
			if ($personTypeId === (int)$arPersonTypes['COMPANY'])
				$this->crmSettings['CLIENT_TYPE_NAME'] = "COMPANY";
			elseif ($personTypeId !== intval($arPersonTypes['CONTACT']))
				$personTypeId = 0;
		}
		$this->crmSettings['PERSON_TYPE_ID'] = $personTypeId;

		$taxList = [];
		if (isset($this->crmSettings['ALLOW_LD_TAX']))
		{
			$this->crmSettings['TAX_LIST_PERCENT_PRECISION'] = defined("SALE_VALUE_PRECISION") ? SALE_VALUE_PRECISION : 2;

			$totalInfo = CCrmProductRow::PrepareTotalInfoFromSettings($this->entity['SETTINGS']);
			if (!empty($totalInfo['TAX_LIST']) && is_array($totalInfo['TAX_LIST']))
			{
				$taxList = $totalInfo['TAX_LIST'];
			}
			unset($totalInfo);
		}
		$this->crmSettings['TAX_LIST'] = $taxList;

		$this->crmSettings['ENABLE_TAX'] = isset($this->arParams['ENABLE_TAX']) ? ($this->arParams['ENABLE_TAX'] === 'Y') : false;
		$this->crmSettings['ENABLE_DISCOUNT'] = isset($this->arParams['ENABLE_DISCOUNT']) ? ($this->arParams['ENABLE_DISCOUNT'] === 'Y') : false;

		if ($this->entity['ID'] > 0 && !empty($this->entity['SETTINGS']))
		{
			if (isset($this->entity['SETTINGS']['ENABLE_TAX']))
			{
				$this->crmSettings['ENABLE_TAX'] = (bool)$this->entity['SETTINGS']['ENABLE_TAX'];
			}
			if (isset($this->entity['SETTINGS']['ENABLE_DISCOUNT']))
			{
				$this->crmSettings['ENABLE_DISCOUNT'] = (bool)$this->entity['SETTINGS']['ENABLE_DISCOUNT'];
			}
		}

		$this->crmSettings['PRICE_PRECISION'] = 2; // need fix it
		$this->crmSettings['QUANTITY_PRECISION'] = 4;
		$this->crmSettings['COMMON_PRECISION'] = 2;

		$this->crmSettings['IS_RESERVE_BLOCKED'] = $this->isReservationRestrictedByPlan();
		$this->crmSettings['IS_RESERVE_EQUAL_PRODUCT_QUANTITY'] = $this->isReserveEqualProductQuantity();

		unset($taxList);
	}

	/**
	 * @return void
	 */
	protected function fillCrmSettings(): void
	{
		$this->arResult['HIDE_ALL_TAXES'] = $this->crmSettings['HIDE_ALL_TAXES'];
		$this->arResult['ALLOW_TAX'] = $this->crmSettings['ALLOW_TAX'];

		$this->arResult['ALLOW_LD_TAX'] = $this->crmSettings['ALLOW_LD_TAX'];
		$this->arResult['LOCATION_ID'] = $this->crmSettings['LOCATION_ID'];

		$this->arResult['PRODUCT_ROW_TAX_UNIFORM'] = $this->crmSettings['PRODUCT_ROW_TAX_UNIFORM'];

		$this->arResult['CLIENT_SELECTOR_ID'] = $this->crmSettings['CLIENT_SELECTOR_ID'];
		$this->arResult['CLIENT_TYPE_NAME'] = $this->crmSettings['CLIENT_TYPE_NAME'];
		$this->arResult['PERSON_TYPE_ID'] = $this->crmSettings['PERSON_TYPE_ID'];

		if (isset($this->crmSettings['TAX_LIST_PERCENT_PRECISION']))
		{
			$this->arResult['TAX_LIST_PERCENT_PRECISION'] = $this->crmSettings['TAX_LIST_PERCENT_PRECISION'];
		}

		$this->arResult['TAX_LIST'] = $this->crmSettings['TAX_LIST'];

		$this->arResult['ENABLE_TAX'] = $this->crmSettings['ENABLE_TAX'];
		$this->arResult['ENABLE_DISCOUNT'] = $this->crmSettings['ENABLE_DISCOUNT'];

		$this->arResult['PRICE_PRECISION'] = $this->crmSettings['PRICE_PRECISION'];
		$this->arResult['QUANTITY_PRECISION'] = $this->crmSettings['QUANTITY_PRECISION'];
		$this->arResult['COMMON_PRECISION'] = $this->crmSettings['COMMON_PRECISION'];

		$this->arResult['IS_RESERVE_BLOCKED'] = $this->crmSettings['IS_RESERVE_BLOCKED'];
		$this->arResult['IS_RESERVE_EQUAL_PRODUCT_QUANTITY'] = $this->crmSettings['IS_RESERVE_EQUAL_PRODUCT_QUANTITY'];
		$this->arResult['ALLOW_CATALOG_PRICE_EDIT'] = $this->crmSettings['ALLOW_CATALOG_PRICE_EDIT'];
		$this->arResult['ALLOW_CATALOG_PRICE_SAVE'] = $this->crmSettings['ALLOW_CATALOG_PRICE_SAVE'];
		$this->arResult['CATALOG_PRICE_EDIT_ARTICLE_CODE'] = $this->crmSettings['CATALOG_PRICE_EDIT_ARTICLE_CODE'];
		$this->arResult['CATALOG_PRICE_EDIT_ARTICLE_HINT'] = $this->crmSettings['CATALOG_PRICE_EDIT_ARTICLE_HINT'];
		$this->arResult['CATALOG_PRICE_CHANGING_DISABLE_HINT'] = \CUserOptions::GetOption(
			'crm.entity.product.list',
			'disable_notify_changing_price',
			false
		);

		$this->arResult['READ_ONLY'] = $this->isReadOnly(); // compatibility
	}

	private function isReadOnly(): bool
	{
		return !$this->arParams['ALLOW_EDIT'];
	}

	/**
	 * @return void
	 */
	private function prepareReferencesToResult(): void
	{
		$this->arResult['CURRENCY'] = $this->getCurrency();
		$this->arResult['MEASURES'] = $this->measures;
		$this->arResult['STORES'] = $this->stores;
		$this->arResult['MEASURES']['LIST'] = array_values(array_filter(
			$this->arResult['MEASURES']['LIST']
		));
		$this->arResult['PRODUCT_VAT_LIST'] = $this->productVatList;
		$this->arResult['DISCOUNT_TYPES'] = $this->discountTypes;
		$this->arResult['DEFAULT_STORE_ID'] = $this->getDefaultStoreId();
	}

	/**
	 * @return array
	 */
	protected function getGridRows(): array
	{
		$this->calculateRows();

		$currencyId = $this->getCurrencyId();

		if (!empty($this->rows) && $this->isCrmFormat())
		{
			$catalogItems = [];

			$basketCode = 0;

			foreach ($this->rows as $index => $row)
			{
				if (is_numeric($row['ID']) && $row['ID'] > 0)
				{
					$row['BASKET_CODE'] = $row['ID'];
				}
				else
				{
					$row['BASKET_CODE'] = 'n' . $basketCode;
					$basketCode++;
				}

				if (empty($row['IBLOCK_ID']))
				{
					$row['IBLOCK_ID'] = \CCrmCatalog::GetDefaultID();
				}

				if (empty($row['CURRENCY']))
				{
					$row['CURRENCY'] = $currencyId;
				}

				$id = (int)$row['PRODUCT_ID'];
				if ($id > 0)
				{
					if (!isset($catalogItems[$id]))
					{
						$catalogItems[$id] = [];
					}
					$catalogItems[$id][] = $index;
				}

				$row['IS_NEW'] = ($row['IS_NEW'] ?? 'N') === 'Y' ? 'Y' : 'N';
				if (!isset($row['BASE_PRICE']))
				{
					$row['BASE_PRICE'] =
						($row['TAX_INCLUDED'] ?? 'N') !== 'Y'
							? $row['PRICE_NETTO']
							: $row['PRICE_BRUTTO']
					;
				}

				$row['STORE_TITLE'] = '';
				if ($this->isAllowedInventoryManagement())
				{
					$row['STORE_ID'] =
						empty($row['STORE_ID'])
							? $this->getDefaultStoreId()
							: (int)$row['STORE_ID']
					;

					$row['STORE_TITLE'] =
						isset($this->stores[$row['STORE_ID']])
							? $this->stores[$row['STORE_ID']]['TITLE']
							: ''
					;
				}

				$this->rows[$index] = $row;
			}

			if (!empty($catalogItems))
			{
				$this->loadCatalog($catalogItems);
				$this->loadSkuTree($catalogItems);
			}

			$this->fillEmptyCatalog();
		}

		return $this->rows;
	}

	/**
	 * @return string
	 */
	protected function getNewRowId(): string
	{
		$result = self::NEW_ROW_ID_PREFIX . $this->getNewRowCounter();
		$this->newRowCounter++;

		return $result;
	}

	/**
	 * @return int
	 */
	protected function getNewRowCounter(): int
	{
		return $this->newRowCounter;
	}

	/* Access rights tools */

	/**
	 * @return bool
	 */
	protected function checkUserPermissions(): bool
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$hasPermission = ($this->entity['ID'] > 0
			? Crm\Security\EntityAuthorization::checkUpdatePermission(
				$this->entity['TYPE_ID'],
				$this->entity['ID'],
				$userPermissions
			)
			: Crm\Security\EntityAuthorization::checkCreatePermission(
				$this->entity['TYPE_ID'],
				$userPermissions
			)
		);

		if (!$hasPermission)
		{
			$this->addErrorMessage(Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_ERR_ACCESS_DENIED'));

			return false;
		}

		return true;
	}

	/* Access rights tools finish */

	private function calculateRows(): void
	{
		$totalSum = 0;
		$totalTax = 0;
		$totalDiscount = 0;

		if (!empty($this->rows) && $this->isCrmFormat())
		{
			foreach ($this->rows as $index => $row)
			{
				if ($this->crmSettings['ALLOW_LD_TAX'])
				{
					$row['PRICE'] = CCrmProductRow::CalculateExclusivePrice($row['PRICE'], $row['TAX_RATE']);
					$row['TAX_RATE'] = 0;
					$row['TAX_INCLUDED'] = 'N';
				}

				$row['TAX_SUM'] = 0;

				if ($row['TAX_RATE'] != 0)
				{
					if ($row['TAX_INCLUDED'] === 'Y')
					{
						$taxSum = $row['PRICE'] * $row['QUANTITY'] * (1 - 1 / (1 + $row['TAX_RATE'] / 100));
					}
					else
					{
						$taxSum = $row['PRICE_EXCLUSIVE'] * $row['QUANTITY'] * $row['TAX_RATE'] / 100;
					}

					$row['TAX_SUM'] = round($taxSum, $this->crmSettings['PRICE_PRECISION']);
				}

				if (!isset($row['PRICE_NETTO']) || $row['PRICE_NETTO'] == 0)
				{
					$discountTypeID = (int)$row['DISCOUNT_TYPE_ID'];
					if ($discountTypeID === Discount::MONETARY)
					{
						$row['PRICE_NETTO'] = $row['PRICE_EXCLUSIVE'] + $row['DISCOUNT_SUM'];
					}
					else
					{
						$discoutRate = (float)$row['DISCOUNT_RATE'];
						$discoutSum = $discoutRate < 100
							? Discount::calculateDiscountSum($row['PRICE_EXCLUSIVE'], $discoutRate)
							: (float)$row['DISCOUNT_SUM'];
						$row['PRICE_NETTO'] = $row['PRICE_EXCLUSIVE'] + $discoutSum;
					}
				}

				if (!isset($row['PRICE_BRUTTO']) || $row['PRICE_BRUTTO'] == 0)
				{
					$row['PRICE_BRUTTO'] = CCrmProductRow::CalculateInclusivePrice($row['PRICE_NETTO'], $row['TAX_RATE']);
				}

				if (!isset($row['MEASURE_CODE']) || $row['MEASURE_CODE'] == '')
				{
					$row['MEASURE_CODE'] = $this->measures['DEFAULT']['CODE'];
					$row['MEASURE_NAME'] = $this->measures['DEFAULT']['SYMBOL'];
					$row['MEASURE_EXISTS'] = true; // TODO: fix it, because default can absent in list
				}
				else
				{
					$code = (string)$row['MEASURE_CODE'];
					$measure = $this->getMeasureByCode($code);
					if (!empty($measure))
					{
						$row['MEASURE_NAME'] = $measure['SYMBOL'];
						$row['MEASURE_EXISTS'] = true;
					}
					else
					{
						$row['MEASURE_EXISTS'] = false;
					}
					unset($measure);
				}

				$totalDiscount += round($row['QUANTITY'] * $row['DISCOUNT_SUM'], $this->crmSettings['PRICE_PRECISION']);

				$this->rows[$index] = $row;
			}
			unset($index);

			$enableSaleDiscount = false;
			$calculateOptions = [];
			if ($this->crmSettings['ALLOW_LD_TAX'])
			{
				$calculateOptions['ALLOW_LD_TAX'] = 'Y';
				$calculateOptions['LOCATION_ID'] = isset($this->arParams['LOCATION_ID']) ? $this->arParams['LOCATION_ID'] : '';
			}

			$result = CCrmSaleHelper::Calculate(
				$this->rows,
				$this->getCurrencyId(),
				$this->crmSettings['PERSON_TYPE_ID'],
				$enableSaleDiscount,
				$this->getSiteId(),
				$calculateOptions
			);

			if (isset($result['TAX_LIST']) && is_array($result['TAX_LIST']))
			{
				$this->crmSettings['TAX_LIST'] = $result['TAX_LIST'];
			}

			$totalSum = isset($result['PRICE']) ? round((float)$result['PRICE'], $this->crmSettings['PRICE_PRECISION']) : 0;
			$totalTax = isset($result['TAX_VALUE']) ? round((float)$result['TAX_VALUE'], $this->crmSettings['PRICE_PRECISION']) : 0;
		}

		$deliverySum = $this->getTotalDeliverySum();
		$this->arResult['TOTAL_DELIVERY_SUM'] = $deliverySum;
		$this->arResult['TOTAL_SUM'] = $totalSum + $deliverySum;

		$this->arResult['TOTAL_DISCOUNT'] = $totalDiscount;
		$this->arResult['TOTAL_TAX'] = $totalTax;
		$this->arResult['TOTAL_BEFORE_TAX'] = round($totalSum - $this->arResult['TOTAL_TAX'], $this->crmSettings['PRICE_PRECISION']);
		$this->arResult['TOTAL_BEFORE_DISCOUNT'] = $this->arResult['TOTAL_BEFORE_TAX'] + $this->arResult['TOTAL_DISCOUNT'];
	}

	/**
	 * @return int|float
	 */
	private function getTotalDeliverySum()
	{
		$total = 0;

		if ($this->entity['TYPE_NAME'] === CCrmOwnerType::DealName)
		{
			return CCrmDeal::calculateDeliveryTotal($this->entity['ID']);
		}

		return $total;
	}

	/**
	 * @return bool
	 */
	private function isCrmFormat(): bool
	{
		return $this->entity['CRM_FORMAT'];
	}

	/**
	 * @return bool
	 */
	private function isReservationRestrictedByPlan(): bool
	{
		return !\Bitrix\Crm\Restriction\RestrictionManager::getInventoryControlIntegrationRestriction()->hasPermission();
	}

	/**
	 * @return bool
	 */
	private function isReserveEqualProductQuantity(): bool
	{
		return ReservationService::getInstance()->isReserveEqualProductQuantity();
	}

	/**
	 * @return bool
	 */
	private function isAllowedInventoryManagement(): bool
	{
		return
			$this->entity['TYPE_NAME'] === CCrmOwnerType::DealName
			&& Catalog\Config\State::isUsedInventoryManagement()
			&& !\CCrmSaleHelper::isWithOrdersMode()
		;
	}

	/**
	 * @param array $items
	 * @return void
	 */
	private function loadCatalog(array $items): void
	{
		if (empty($items))
		{
			return;
		}
		$idList = array_keys($items);
		Main\Type\Collection::normalizeArrayValuesByInt($idList, true);
		$products = Sale\Helpers\Admin\Product::getData($idList, $this->getSiteId());
		$repositoryFacade = ServiceContainer::getRepositoryFacade();

		$basePriceId = $this->arParams['BASE_PRICE_ID'];

		if (!empty($products))
		{
			$oldProductId = [];

			$offerIds = array_unique(
				array_column($products, 'OFFER_ID')
			);

			if ($offerIds)
			{
				$storeOfferMap = [];
				$storeAmounts = StoreProductTable::getList([
					'filter' => [
						'=PRODUCT_ID' => $offerIds,
					],
					'select' => [
						'AMOUNT',
						'QUANTITY_RESERVED',
						'STORE_ID',
						'PRODUCT_ID',
					]
				]);

				while ($storeAmount = $storeAmounts->fetch())
				{
					$storeOfferMap[$storeAmount['PRODUCT_ID']] = $storeOfferMap[$storeAmount['PRODUCT_ID']] ?? [];
					$storeOfferMap[$storeAmount['PRODUCT_ID']][$storeAmount['STORE_ID']] = $storeAmount;
				}
			}

			foreach ($items as $id => $list)
			{
				if (!isset($products[$id]))
				{
					continue;
				}

				$variation = null;
				$data = $products[$id];
				unset($data['NAME']);
				$replace = [
					'BASE_PRICE_ID' => $basePriceId,
					'DETAIL_URL' => '',
					'STORE_AVAILABLE' => 0,
					'STORE_AMOUNT' => 0,
					'COMMON_STORE_RESERVED' => 0,
					'COMMON_STORE_AMOUNT' => 0,
					'COMMON_STORE_AVAILABLE' => 0,
				];

				if ($repositoryFacade)
				{
					$variation = $repositoryFacade->loadVariation((int)$data['OFFER_ID']);
					$replace['DETAIL_URL'] = $this->getElementDetailUrl(
						(int)$data['IBLOCK_ID'],
						(int)$data['PRODUCT_ID']
					);
				}

				if (
					$variation !== null
					&& (int)$data['OFFER_ID'] > 0
					&& $basePriceId
				)
				{
					$price = $variation->getPriceCollection()->findByGroupId($basePriceId);
					if ($price)
					{
						$replace['CATALOG_PRICE'] = $price->getPrice();
					}
				}

				if (!empty($data['OFFERS_IBLOCK_ID']))
				{
					$replace['OFFERS_IBLOCK_ID'] = $data['OFFERS_IBLOCK_ID'];
				}
				if (!empty($data['IBLOCK_ID']))
				{
					$replace['IBLOCK_ID'] = $data['IBLOCK_ID'];
				}
				if (!empty($data['OFFER_ID']))
				{
					$replace['OFFER_ID'] = $data['OFFER_ID'];
				}
				if (!empty($data['PRODUCT_ID']))
				{
					$replace['PRODUCT_ID'] = $data['PRODUCT_ID'];
				}
				if (!empty($data['VAT_ID']))
				{
					$replace['VAT_ID'] = $data['VAT_ID'];
				}
				if (!empty($data['EDIT_PAGE_URL']))
				{
					$replace['EDIT_PAGE_URL'] = $this->prepareAdminLink($data['EDIT_PAGE_URL']);
				}

				foreach ($list as $index)
				{
					$imageInfo = [];
					if ($variation)
					{
						$replace['COMMON_STORE_RESERVED'] = $variation->getField('QUANTITY_RESERVED');
						$replace['COMMON_STORE_AMOUNT'] = $variation->getField('QUANTITY');
						$skuImageField = new ImageInput($variation);
						$imageInfo = $skuImageField->getFormattedField();
					}
					$this->rows[$index]['IMAGE_INFO'] = Json::encode($imageInfo);

					$storeId = $this->rows[$index]['STORE_ID'];
					if (
						(int)$data['OFFER_ID'] > 0
						&& (int)$storeId > 0
						&& isset($storeOfferMap[$data['OFFER_ID']][$storeId])
						&& 	$this->isAllowedInventoryManagement()
						&& !$this->isReservationRestrictedByPlan()
					)
					{
						$storeInfo = $storeOfferMap[$data['OFFER_ID']][$storeId];
						$replace['STORE_AMOUNT'] = $storeInfo['AMOUNT'];
						$replace['STORE_AVAILABLE'] = $storeInfo['AMOUNT'] - $storeInfo['QUANTITY_RESERVED'];
					}

					$this->rows[$index]['INPUT_RESERVE_QUANTITY'] = (float)($this->rows[$index]['INPUT_RESERVE_QUANTITY'] ?? 0);
					$this->rows[$index]['RESERVE_QUANTITY'] = (float)($this->rows[$index]['RESERVE_QUANTITY'] ?? 0);
					$this->rows[$index]['ROW_RESERVED'] = (float)($this->rows[$index]['RESERVE_QUANTITY'] ?? 0);
					$this->rows[$index]['DEDUCTED_QUANTITY'] = (float)($this->rows[$index]['DEDUCTED_QUANTITY'] ?? 0);
					$this->rows[$index]['DATE_RESERVE'] = $this->rows[$index]['DATE_RESERVE'] ?? '';
					$this->rows[$index]['DATE_RESERVE_END'] = $this->rows[$index]['DATE_RESERVE_END'] ?? '';
					$this->rows[$index]['SKY_TREE'] = $this->rows[$index]['SKY_TREE'] ?? [];

					$oldProductId[$index] = $this->rows[$index]['PRODUCT_ID'];
					$this->rows[$index] = array_merge($data, $this->rows[$index]);
					if (!empty($replace))
					{
						$this->rows[$index] = array_merge($this->rows[$index], $replace);
					}
				}
				unset($replace, $data);
			}

			$skuParams = \Bitrix\Sale\Helpers\Admin\Blocks\OrderBasket::getOffersSkuParamsMode(
				['ITEMS' => $this->rows],
				//$visibleColumns,
				[],
				Sale\Helpers\Admin\Blocks\OrderBasket::EDIT_MODE
			);

			$this->arResult['IBLOCKS_SKU_PARAMS'] = isset($skuParams['IBLOCKS_SKU_PARAMS']) ? $skuParams['IBLOCKS_SKU_PARAMS'] : [];
			$this->arResult['IBLOCKS_SKU_PARAMS_ORDER'] = isset($skuParams['IBLOCKS_SKU_PARAMS_ORDER']) ? $skuParams['IBLOCKS_SKU_PARAMS_ORDER'] : [];

			if (is_array($skuParams['ITEMS']) && !empty($skuParams['ITEMS']))
			{
				$this->rows = $skuParams['ITEMS'];
			}

			foreach ($oldProductId as $index => $productId)
			{
				$this->rows[$index]['PARENT_PRODUCT_ID'] = $this->rows[$index]['PRODUCT_ID'];
				$this->rows[$index]['PRODUCT_ID'] = $productId;
			}

			unset($oldProductId);
		}
		unset($products, $idList);
	}

	private function loadSkuTree(array $items): void
	{
		$itemsByIblock = [];
		$productIdsToOffers = [];

		foreach ($items as $id => $positions)
		{
			foreach ($positions as $position)
			{
				$row = $this->rows[$position];

				// skip simple products
				if (empty($row['OFFERS_IBLOCK_ID']))
				{
					continue;
				}

				$itemsByIblock[$row['IBLOCK_ID']][] = $row['PARENT_PRODUCT_ID'];
				$productIdsToOffers[$row['PARENT_PRODUCT_ID']][] = $id;
			}
		}

		foreach ($itemsByIblock as $iblockId => $productIds)
		{
			$iblockProductsToOffers = array_intersect_key($productIdsToOffers, array_fill_keys($productIds, true));

			if (!empty($iblockProductsToOffers))
			{
				/** @var \Bitrix\Catalog\Component\SkuTree $skuTree */
				$skuTree = \Bitrix\Catalog\v2\IoC\ServiceContainer::make('sku.tree', ['iblockId' => $iblockId]);
				if ($skuTree)
				{
					$skuTreeItems = $skuTree->loadJsonOffers($iblockProductsToOffers);
					if (!empty($skuTreeItems))
					{
						foreach ($skuTreeItems as $offers)
						{
							foreach ($offers as $skuId => $skuTreeItem)
							{
								$rowIndexes = $items[$skuId];

								foreach ($rowIndexes as $rowIndex)
								{
									$this->rows[$rowIndex]['SKU_TREE'] = Json::encode($skuTreeItem);
								}
							}
						}
					}
				}
			}
		}
	}

	private function getElementDetailUrl(int $iblockId, int $skuId = 0): string
	{
		$urlBuilder = BuilderManager::getInstance()->getBuilder(ProductBuilder::TYPE_ID);
		if (!$urlBuilder)
		{
			return '';
		}

		$urlBuilder->setIblockId($iblockId);
		return $urlBuilder->getElementDetailUrl($skuId);
	}

	/**
	 * @param string $url
	 * @return string
	 */
	private function prepareAdminLink(string $url): string
	{
		return str_replace(
			[".php", "/bitrix/admin/"],
			["/", "/shop/settings/"],
			$url
		);
	}

	/**
	 * @return void
	 */
	private function fillEmptyCatalog(): void
	{
		if (empty($this->rows))
		{
			return;
		}
		$measure = Crm\Measure::getDefaultMeasure();
		$fields = [
			'OFFERS_IBLOCK_ID' => null,
			'PRODUCT_XML_ID' => null,
			'CATALOG_XML_ID' => null,
			'PRODUCT_PROPS_VALUES' => [],
			'EDIT_PAGE_URL' => '',
			'DIMENSIONS' => '',
			//$row['AVAILABLE'] = $rawData['QUANTITY']; // it deprecated, fix it
			'WEIGHT' => '',
			'BARCODE_MULTI' => 'N',
			'SET_ITEMS' => [],
			'IS_SET_ITEM' => 'N',
			'VAT_ID' => null,
			'IS_SET_PARENT' => 'N',
			'PICTURE_URL' => '',
			'PRODUCT_PROVIDER_CLASS' => '',
			'STORES' => [],
			'PROPS' => null,
			'MEASURE_TEXT' => $measure['SYMBOL'],
			'MEASURE_CODE' => $measure['CODE'],
			'MEASURE_RATIO' => 1,
			'VAT' => 0, // fix it - \Bitrix\Sale\BasketItemBase::getVat
			'TYPE' => null,
			'FIELDS_VALUES' => '', // fix it
			'CURRENCY' => $this->arParams['CURRENCY_ID'], // fix it
			'NOTES' => '',
			'BASKET_CODE' => '', // fix it!!!
			'PATH_TO_DELETE' => '' // fix it
		];

		foreach (array_keys($this->rows) as $index)
		{
			$this->rows[$index] = array_merge($fields, $this->rows[$index]);
		}
	}

	/**
	 * @return string
	 */
	protected function getDefaultPrefix(): string
	{
		return (
			$this->entity['ID'] > 0
				? strtolower($this->entity['TYPE_NAME']) . '_' . $this->entity['ID']
				: 'new_' . strtolower($this->entity['TYPE_NAME'])
			)
			. '_product_editor'
		;
	}

	/**
	 * @param string $value
	 * @return string
	 */
	private static function clearStringValue(string $value): string
	{
		return preg_replace('/[^a-zA-Z0-9_:\\[\\]]/', '', $value);
	}

	/**
	 * @param array &$params
	 * @param string $field
	 * @return void
	 */
	private static function validateSingleParameter(array &$params, string $field): void
	{
		$value = '';

		if (isset($params[$field]) && is_string($params[$field]))
		{
			$value = static::clearStringValue($params[$field]);
		}

		$params[$field] = $value;
	}

	/**
	 * @param array &$params
	 * @param array $list
	 * @return void
	 */
	private static function validateListParameters(array &$params, array $list): void
	{
		foreach ($list as $field)
		{
			static::validateSingleParameter($params, $field);
		}
	}

	/**
	 * @param array $params
	 * @param string $field
	 * @return void
	 */
	private static function validateBoolParameter(array &$params, string $field): void
	{
		if (!isset($params[$field]))
		{
			$params[$field] = false;
		}
		if (is_string($params[$field]))
		{
			$params[$field] = ($params[$field] === 'Y');
		}
		$params[$field] = (is_bool($params[$field]) && $params[$field]);
	}

	/**
	 * @param array $params
	 * @param array $list
	 * @return void
	 */
	private static function validateBoolList(array &$params, array $list): void
	{
		foreach ($list as $field)
		{
			static::validateBoolParameter($params, $field);
		}
		unset($field);
	}

	private static function getImage(int $id): ?array
	{
		$result = null;
		if ($id > 0)
		{
			$file = \CFile::GetFileArray($id);
			if (!empty($file))
			{
				$result = [
					'ID' => (int)$file['ID'],
					'SRC' => Iblock\Component\Tools::getImageSrc($file, true),
					'WIDTH' => (int)$file['WIDTH'],
					'HEIGHT' => (int)$file['HEIGHT'],
				];
			}
			unset($file);
		}

		return $result;
	}

	public function getPopupSettings(): array
	{
		$gridColumnSettings = [
			'DISCOUNTS' => ['DISCOUNT_PRICE', 'DISCOUNT_ROW']
		];

		if ($this->crmSettings['ALLOW_TAX'])
		{
			$gridColumnSettings['TAXES'] = ['TAX_RATE', 'TAX_INCLUDED', 'TAX_SUM'];
		}

		$activeSettings = [];
		$options = new \Bitrix\Main\Grid\Options($this->getGridId());
		$allUsedColumns = $options->getUsedColumns();
		if (!empty($allUsedColumns))
		{
			foreach ($gridColumnSettings as $setting => $columns)
			{
				if (empty(array_diff($columns, $allUsedColumns)))
				{
					$activeSettings[] = $setting;
				}
			}
		}

		$items = [];

		foreach ($gridColumnSettings as $setting => $columns)
		{
			$items[] = [
				'id' => $setting,
				'checked' => in_array($setting, $activeSettings, true),
				'title' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_SETTING_' . $setting . '_TITLE'),
				'desc' => '',
				'action' => 'grid',
				'columns' => $columns,
			];
		}

		$items[] = [
			'id' => 'ADD_NEW_ROW_TOP',
			'checked' => ($this->defaultSettings['NEW_ROW_POSITION'] !== 'bottom'),
			'title' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_SETTING_NEW_ROW_POSITION_TITLE'),
			'desc' => '',
			'action' => 'grid',
		];

		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			return $items;
		}

		$isInventoryControlEnabled = \Bitrix\Catalog\Component\UseStore::isUsed();
		$sliderPath = \CComponentEngine::makeComponentPath('bitrix:catalog.warehouse.master.clear');
		$sliderPath = getLocalPath('components' . $sliderPath . '/slider.php');

		$items[] = [
			'id' => 'SLIDER',
			'checked' => $isInventoryControlEnabled,
			'disabled' => $isInventoryControlEnabled,
			'title' => Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_SETTING_WAREHOUSE_TITLE'),
			'url' => $sliderPath,
			'desc' => '',
			'hint' => $isInventoryControlEnabled ? Loc::getMessage('CRM_ENTITY_PRODUCT_LIST_SETTING_WAREHOUSE_HINT') : '',
			'action' => 'grid',
		];

		return $items;
	}

	public function setGridSettingAction(
		string $settingId,
		$selected,
		array $currentHeaders = []
	): Bitrix\Main\Engine\Response\AjaxJson
	{
		if (!$this->checkModules() || !$this->checkUserPermissions())
		{
			return Bitrix\Main\Engine\Response\AjaxJson::createError($this->errorCollection);
		}

		$headers = [];

		if ($settingId === 'TAXES')
		{
			$headers = ['TAX_RATE', 'TAX_INCLUDED', 'TAX_SUM'];
		}
		elseif ($settingId === 'DISCOUNTS')
		{
			$headers = ['DISCOUNT_PRICE', 'DISCOUNT_ROW'];
		}
		elseif ($settingId === 'ADD_NEW_ROW_TOP')
		{
			$direction = ($selected === 'true') ? 'top' : 'bottom';
			\CUserOptions::SetOption("crm.entity.product.list", 'new.row.position', $direction);
		}
		elseif ($settingId === 'WAREHOUSE')
		{
			\Bitrix\Catalog\Component\UseStore::disable();
		}
		elseif ($settingId === 'DISABLE_NOTIFY_CHANGING_PRICE')
		{
			\CUserOptions::SetOption('crm.entity.product.list', 'disable_notify_changing_price', true);
		}

		if (!empty($headers))
		{
			if ($selected === 'true')
			{
				ProductList::addGridHeaders($headers);
			}
			else
			{
				ProductList::removeGridHeaders($headers);
			}
		}

		return Bitrix\Main\Engine\Response\AjaxJson::createSuccess();
	}
}
