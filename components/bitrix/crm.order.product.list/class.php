<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Catalog\Product\Price;
use Bitrix\Crm\Product\Url;
use Bitrix\Iblock\Url\AdminPage\BuilderManager;
use Bitrix\Catalog;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;

Loc::loadMessages(__FILE__);

final class CCrmOrderProductListComponent extends \CBitrixComponent
{
	private $userId = 0;
	private $userPermissions;
	private $errors = [];

	/** @var Bitrix\Crm\Order\Order */
	private $order = null;

	/** @var null|\Bitrix\Catalog\Url\AdminPage\CatalogBuilder  */
	private $urlBuilder = null;

	private function init()
	{
		if(!Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		if(!Loader::includeModule('currency'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
			return false;
		}

		if(!Loader::includeModule('catalog'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
			return false;
		}

		if (!Loader::includeModule('sale'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE');
			return false;
		}

		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();

		if (!\Bitrix\Crm\Order\Permissions\Order::checkReadPermission(0, $this->userPermissions))
		{
			$this->errors[] = new Main\Error(Loc::getMessage('CRM_PERMISSION_DENIED'));
			return false;
		}

		if(!empty($this->arParams['ORDER']))
		{
			$this->order = $this->arParams['ORDER'];
			$this->arResult['SITE_ID'] = $this->order->getSiteId();
		}
		elseif(!empty($this->arParams['ORDER_ID']))
		{
			$this->order = \Bitrix\Crm\Order\Order::load((int)$this->arParams['ORDER_ID']);

			if($this->order)
			{
				$this->arResult['SITE_ID'] = $this->order->getSiteId();
				if ((int)$this->arParams['FUSER_ID'] > 0)
				{
					$basket = \Bitrix\Crm\Order\Basket::loadItemsForFUser((int)$this->arParams['FUSER_ID'], $this->order->getSiteId());
					$this->order->setBasket($basket);
				}
			}
		}

		if(empty($this->arResult['SITE_ID']))
		{
			$this->arResult['SITE_ID'] = !empty($this->arParams['SITE_ID']) ? htmlspecialcharsbx($this->arParams['SITE_ID']) : SITE_ID;
		}

		if(!$this->order)
		{
			$this->order = \Bitrix\Crm\Order\Manager::createEmptyOrder($this->arResult['SITE_ID']);
		}

		$this->arResult['IS_CHANGED'] = (!empty($this->arParams['ORDER']) || isset($this->arParams['SESSION_BASKET']));

		if (!$this->arResult['IS_CHANGED'] && isset($_SESSION['ORDER_BASKET'][$this->order->getId()]))
		{
			unset($_SESSION['ORDER_BASKET'][$this->order->getId()]);
		}

		if(!$this->order)
		{
			$this->errors[] = new Main\Error(Loc::getMessage('CRM_ORDER_PLC_FAILED_TO_CREATE_OBJECT'));
			return false;
		}

		if ((int)($this->arParams['ORDER_PRODUCT_COUNT']) <= 0)
		{
			$this->arParams['ORDER_PRODUCT_COUNT'] = 20;
		}

		$this->initCouponsData($this->order->getUserId(), $this->order->getId());
		$this->userId = CCrmSecurityHelper::GetCurrentUserID();
		CUtil::InitJSCore(['ajax', 'tooltip']);
		return true;
	}

	private function initUrlBuilder(): bool
	{
		if (!isset($this->arParams['BUILDER_CONTEXT']))
		{
			$this->arParams['BUILDER_CONTEXT'] = '';
		}
		if (
			$this->arParams['BUILDER_CONTEXT'] !== Catalog\Url\ShopBuilder::TYPE_ID
			&& $this->arParams['BUILDER_CONTEXT'] !== Url\ProductBuilder::TYPE_ID
		)
		{
			$this->arParams['BUILDER_CONTEXT'] = Catalog\Url\ShopBuilder::TYPE_ID;
		}

		$manager = BuilderManager::getInstance();
		$this->urlBuilder = $manager->getBuilder($this->arParams['BUILDER_CONTEXT']);
		if ($this->urlBuilder === null)
		{
			$this->errors[] = new Main\Error(Loc::getMessage('CRM_ORDER_PLC_ERR_URL_BUILDER_ABSENT'));
			return false;
		}
		return true;
	}

	private function showErrors()
	{
		foreach($this->errors as $error)
		{
			ShowError($error);
		}
	}

	private function getHeaders()
	{
		return [
			['id' => 'MAIN_INFO', 'name' => Loc::getMessage('CRM_ORDER_PLC_PRODUCT'), 'sort' => 'NAME', 'default' => true, 'width' => '500'],
			['id' => 'PROPERTIES', 'name' => Loc::getMessage('CRM_ORDER_PLC_PROPERTIES'), 'default' => true],
			['id' => 'PRICE', 'name' => Loc::getMessage('CRM_ORDER_PLC_PRICE'), 'sort' => 'PRICE', 'default' => true],
			['id' => 'QUANTITY', 'name' => Loc::getMessage('CRM_ORDER_PLC_QUANTITY'), 'sort' => 'QUANTITY', 'default' => true, 'editable' => true],
			['id' => 'SUMM', 'name' => Loc::getMessage('CRM_ORDER_PLC_THE_SUMM'), 'default' => true],
			['id' => 'VAT', 'name' => Loc::getMessage('CRM_ORDER_PLC_VAT'), 'default' => true, 'editable' => true],
			['id' => 'PRODUCT_ID', 'name' => Loc::getMessage('CRM_ORDER_PLC_PRODUCT_ID'), 'sort' => 'PRODUCT_ID'],
			['id' => 'PRODUCT_PRICE_ID', 'name' => Loc::getMessage('CRM_ORDER_PLC_PRODUCT_PRICE_ID'), 'sort' => 'PRODUCT_PRICE_ID'],
			['id' => 'PRICE_TYPE_ID', 'name' => Loc::getMessage('CRM_ORDER_PLC_TYPE_PRICE_TYPE_ID'), 'sort' => 'PRICE_TYPE_ID'],
			['id' => 'CURRENCY', 'name' => Loc::getMessage('CRM_ORDER_PLC_CURRENCY'), 'sort' => 'CURRENCY'],
			['id' => 'BASE_PRICE', 'name' => Loc::getMessage('CRM_ORDER_PLC_BASE_PRICE'), 'sort' => 'BASE_PRICE'],
			['id' => 'VAT_INCLUDED', 'name' => Loc::getMessage('CRM_ORDER_PLC_VAT_INCLUDED'), 'sort' => 'VAT_INCLUDED'],
			['id' => 'WEIGHT', 'name' => Loc::getMessage('CRM_ORDER_PLC_WEIGHT'), 'sort' => 'WEIGHT'],
			['id' => 'NAME', 'name' => Loc::getMessage('CRM_ORDER_PLC_NAME'), 'sort' => 'NAME'],
			['id' => 'NOTES', 'name' => Loc::getMessage('CRM_ORDER_PLC_NOTES')],
			['id' => 'DISCOUNT_PRICE', 'name' => Loc::getMessage('CRM_ORDER_PLC_DISCOUNT_PRICE'), 'sort' => 'DISCOUNT_PRICE'],
			['id' => 'VAT_RATE', 'name' => Loc::getMessage('CRM_ORDER_PLC_VAT_RATE'), 'sort' => 'VAT_RATE'],
			['id' => 'DEDUCTED', 'name' => Loc::getMessage('CRM_ORDER_PLC_DEDUCTED'), 'sort' => 'DEDUCTED'],
			['id' => 'CUSTOM_PRICE', 'name' => Loc::getMessage('CRM_ORDER_PLC_CUSTOM_PRICE'), 'sort' => 'CUSTOM_PRICE'],
			['id' => 'DIMENSIONS', 'name' => Loc::getMessage('CRM_ORDER_PLC_DIMENSIONS_EXT')],
			['id' => 'SORT', 'name' => Loc::getMessage('CRM_ORDER_PLC_SORTING'), 'sort' => 'SORT']
		];
	}

	private function getVatRates()
	{
		static $result = null;

		if($result === null)
		{
			$dbRes = \Bitrix\Catalog\VatTable::getList([
				'filter' => ['ACTIVE' => 'Y'],
				'order' => ['SORT' => 'ASC']
			]);

			while($vat = $dbRes->fetch())
				$result[$vat['RATE']] = $vat['NAME'];
		}

		return $result;
	}

	protected function getBasketItemsValues()
	{
		if ($this->order->getId() > 0 && isset($_SESSION['ORDER_BASKET'][$this->order->getId()]))
		{
			return $_SESSION['ORDER_BASKET'][$this->order->getId()]['ITEMS'];
		}

		$values = [];
		$basket = $this->order->getBasket();
		/** @var \Bitrix\Sale\BasketItem $basketItem */
		foreach($basket->getBasketItems() as $basketItem)
		{
			$item = $basketItem->getFieldValues();
			$item['VAT'] = $basketItem->getVat();
			$item['BASKET_CODE'] = $basketItem->getBasketCode();
			$propertyCollection = $basketItem->getPropertyCollection();
			foreach ($propertyCollection as $property)
			{
				$propertyValues = $property->getFieldValues();
				unset($propertyValues['BASKET_ID']);
				$item['PROPS'][] = $propertyValues;
			}
			$values[] = $item;
		}
		return $values;
	}
	protected function getSetItems()
	{
		if (!Main\Loader::includeModule('catalog'))
		{
			return [];
		}

		$productId =
		$parentQuantity =
		$provider = null;
		if (isset($_SESSION['ORDER_BASKET'][$this->order->getId()]['ITEMS'][$_REQUEST['parent_id']]))
		{
			$item = $_SESSION['ORDER_BASKET'][$this->order->getId()]['ITEMS'][$_REQUEST['parent_id']];
			$productId = $item['PRODUCT_ID'];
			$parentQuantity = (float)$item['QUANTITY'];
		}
		elseif ((int)$_REQUEST['parent_id'] > 0)
		{
			$basket = $this->order->getBasket();
			if ($basketItem = $basket->getItemByBasketCode((int)$_REQUEST['parent_id']))
			{
				$productId = $basketItem->getProductId();
				$parentQuantity = (float)$basketItem->getQuantity();
			}
		}


		$provider = \CSaleBasket::GetProductProvider(
			array(
				"MODULE" => 'catalog',
				"PRODUCT_PROVIDER_CLASS" => 'CCatalogProductProvider'
			)
		);

		if (empty($productId) || !method_exists($provider, 'GetSetItems'))
		{
			return [];
		}

		$sets = $provider::GetSetItems($productId, \CSaleBasket::TYPE_SET);

		if (empty($sets))
		{
			return [];
		}

		$set = current($sets);
		$buyersGroups = \CUser::getUserGroup($this->order->getUserId());
		Price\Calculation::pushConfig();
		Price\Calculation::setConfig(array(
			'CURRENCY' => $this->order->getCurrency(),
			'PRECISION' => (int)Main\Config\Option::get('sale', 'value_precision'),
			'USE_DISCOUNTS' => false,
			'RESULT_WITH_VAT' => true
		));

		foreach ($set['ITEMS'] as &$item)
		{
			$price = \CCatalogProduct::getOptimalPrice($item["PRODUCT_ID"], 1, $buyersGroups, "N", array(), $this->order->getSiteId());
			$item['PRICE'] = $price['DISCOUNT_PRICE'];
			$item['BASE_PRICE'] = $price['DISCOUNT_PRICE'];
			$item['CURRENCY'] = $this->order->getCurrency();
			$item['PARENT_ID'] = $_REQUEST['parent_id'];
			$item['QUANTITY'] = $item['QUANTITY'] * $parentQuantity;
		}
		Price\Calculation::popConfig();

		$this->arResult['READ_ONLY'] = 'Y';
		$this->arResult['IS_SET_ITEMS'] = true;
		return $set['ITEMS'];
	}

	private function getProductsFields($visibleColumns)
	{
		$rows = [];
		$flippedVisibleColumns = array_flip($visibleColumns);
		$catalogProductIds = [];
		if ($_REQUEST['action'] !== \Bitrix\Main\Grid\Actions::GRID_GET_CHILD_ROWS)
		{
			$rawValues = $this->getBasketItemsValues();
		}
		else
		{
			$rawValues = $this->getSetItems();
		}

		/** @var \Bitrix\Crm\Order\BasketItem $basketItem */
		foreach($rawValues as $values)
		{
			if(isset($values["MODULE"]) && $values["MODULE"] === "catalog")
			{
				$catalogProductIds[] = $values['PRODUCT_ID'];
			}
			$flippedVisibleColumns = $this->addRequiredFields($flippedVisibleColumns);
			$data = array_intersect_key($values, $flippedVisibleColumns);
			$data['PROPS'] = $values['PROPS'];
			$data['TYPE'] = $values['TYPE'];
			$data['MEASURE_TEXT'] = htmlspecialcharsbx($values['MEASURE_NAME']);
			$data['FIELDS_VALUES'] = Main\Web\Json::encode(array_filter($values));

			if(isset($flippedVisibleColumns['PRICE_CURRENCY']))
			{
				$data['PRICE_CURRENCY'] = CCrmCurrency::MoneyToString($values['PRICE'], $values['CURRENCY']);
			}

			if(isset($flippedVisibleColumns['SUMM']))
			{
				$data['SUMM'] = $values['PRICE'] * $values['QUANTITY'];
				$data['VAT'] = $values['VAT'];
				$data['CURRENCY'] = $values['CURRENCY'];
			}

			if(isset($flippedVisibleColumns['MAIN_INFO']))
			{
				$data['NAME'] = $values['NAME'];
			}

			if(isset($flippedVisibleColumns['PRICE']))
			{
				$data['NOTES'] = $values['NOTES'];
				$data['BASE_PRICE'] = $values['BASE_PRICE'];
				$data['CURRENCY'] = $values['CURRENCY'];
				$data['CURRENCY_NAME_SHORT'] =  $this->getCurrencyNameShort($values['CURRENCY']);
				$data['FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($values['PRICE'], $values['CURRENCY'], '#');
				$data['FORMATTED_PRICE_WITH_CURRENCY'] = CCrmCurrency::MoneyToString($values['PRICE'], $values['CURRENCY'], '');
			}

			if (isset($flippedVisibleColumns['DIMENSIONS']))
			{
				if (!isset($values['DIMENSIONS']))
				{
					$data['DIMENSIONS'] = '';
				}
				else
				{
					$data['DIMENSIONS'] = $this->getDimensions($values['DIMENSIONS']);
				}
			}

			//we need this data always
			$data['BASKET_CODE'] = $values['BASKET_CODE'];

			if(isset($this->arResult['DISCOUNTS_LIST']['RESULT']['BASKET'][$data['BASKET_CODE']]))
			{
				$data['DISCOUNTS'] = [];

				foreach($this->arResult['DISCOUNTS_LIST']['RESULT']['BASKET'][$data['BASKET_CODE']] as $discount)
				{
					if(is_array($discount['DESCR']) && !empty($discount['DESCR']))
					{
						$description = implode(' ', $discount['DESCR']);
					}
					else
					{
						$description = $discount['DESCR'];
					}

					if(isset($this->arResult['DISCOUNTS_LIST']['DISCOUNT_LIST'][$discount['DISCOUNT_ID']]['NAME']))
					{
						$name = $this->arResult['DISCOUNTS_LIST']['DISCOUNT_LIST'][$discount['DISCOUNT_ID']]['NAME'];
					}
					else
					{
						$name = Loc::getMessage('CRM_ORDER_PLC_UNKNOWN_DISCOUNT');
					}

					if(isset($this->arResult['DISCOUNTS_LIST']['DISCOUNT_LIST'][$discount['DISCOUNT_ID']]['EDIT_PAGE_URL']))
					{
						$editPageUrl = $this->prepareAdminLink(
							$this->arResult['DISCOUNTS_LIST']['DISCOUNT_LIST'][$discount['DISCOUNT_ID']]['EDIT_PAGE_URL']
						);
					}
					else
					{
						$editPageUrl = '';
					}

					$data['DISCOUNTS'][$discount['DISCOUNT_ID']] = [
						'NAME' => $name,
						'DESCR' => $description,
						'APPLY' => $discount['APPLY'],
						'EDIT_PAGE_URL' => $editPageUrl
					];
				}
			}

			$data['PATH_TO_DELETE'] =  CHTTP::urlAddParams(
				$this->arResult['PATH_TO_ORDER_PRODUCT_LIST'],
				[
					'action_'.$this->arResult['GRID_ID'] => 'delete',
					'ID' => $data['BASKET_CODE'],
					'sessid' => bitrix_sessid()
			]);

			$data["OFFER_ID"] = $data["PRODUCT_ID"];
			$data["SORT"] = $values["SORT"];
			if (!empty($values["PARENT_ID"]))
			{
				$data["PARENT_ID"] = $values["PARENT_ID"];
			}
			$data["DISCOUNT_PRICE"] = CCrmCurrency::MoneyToString($values['DISCOUNT_PRICE'], $values['CURRENCY']);
			$data["CUSTOM_PRICE"] = $values["CUSTOM_PRICE"];
			$data['NAME'] = htmlspecialcharsbx($data['NAME']);
			$rows[] = $data;
		}

		if(!empty($catalogProductIds))
		{
			$catalogProducts = \Bitrix\Sale\Helpers\Admin\Product::getData($catalogProductIds, $this->order->getSiteId());

			$this->prepareProductUrls($catalogProducts);

			foreach($rows as $k => $row)
			{
				if(isset($catalogProducts[$row['PRODUCT_ID']]))
				{
					$rows[$k] = array_merge($catalogProducts[$row['PRODUCT_ID']], $rows[$k]);

					if(!empty($catalogProducts[$row['PRODUCT_ID']]['OFFERS_IBLOCK_ID']))
						$rows[$k]["OFFERS_IBLOCK_ID"] = $catalogProducts[$row['PRODUCT_ID']]['OFFERS_IBLOCK_ID'];

					if(!empty($catalogProducts[$row['PRODUCT_ID']]['IBLOCK_ID']))
						$rows[$k]["IBLOCK_ID"] = $catalogProducts[$row['PRODUCT_ID']]['IBLOCK_ID'];

					if(!empty($catalogProducts[$row['PRODUCT_ID']]['PRODUCT_ID']))
						$rows[$k]["PRODUCT_ID"] = $catalogProducts[$row['PRODUCT_ID']]['PRODUCT_ID'];

					if(!empty($catalogProducts[$row['PRODUCT_ID']]['VAT_ID']))
						$rows[$k]["VAT_ID"] = $catalogProducts[$row['PRODUCT_ID']]['VAT_ID'];

					if(!empty($catalogProducts[$row['PRODUCT_ID']]['EDIT_PAGE_URL']))
						$rows[$k]['EDIT_PAGE_URL'] = $catalogProducts[$row['PRODUCT_ID']]['EDIT_PAGE_URL'];
				}
			}
		}

		$skuParams = \Bitrix\Sale\Helpers\Admin\Blocks\OrderBasket::getOffersSkuParamsMode(array('ITEMS' => $rows), $visibleColumns, \Bitrix\Sale\Helpers\Admin\Blocks\OrderBasket::EDIT_MODE);
		$this->arResult['IBLOCKS_SKU_PARAMS'] = isset($skuParams['IBLOCKS_SKU_PARAMS']) ? $skuParams['IBLOCKS_SKU_PARAMS'] : array();
		$this->arResult['IBLOCKS_SKU_PARAMS_ORDER'] = isset($skuParams['IBLOCKS_SKU_PARAMS_ORDER']) ? $skuParams['IBLOCKS_SKU_PARAMS_ORDER'] : array();

		if(is_array($skuParams['ITEMS']) && !empty($skuParams['ITEMS']))
		{
			$rows = $skuParams['ITEMS'];
		}

		sortByColumn($rows, array("SORT" => SORT_ASC), '', null, false);
		return $rows;
	}

	private function getDimensions($dimensions): string
	{
		$result = '';
		if (empty($dimensions))
		{
			return $result;
		}
		if (!is_array($dimensions))
		{
			$dimensions = unserialize($dimensions, ['allowed_classes' => false]);
		}
		if (is_array($dimensions))
		{
			if (!empty($dimensions['LENGTH']) && !empty($dimensions['WIDTH']) && !empty($dimensions['HEIGHT']))
			{
				$result = Loc::getMessage(
					'CRM_ORDER_PLC_DIMENSIONS_FORMAT',
					[
						'#LENGTH#' => $dimensions['LENGTH'],
						'#WIDTH#' => $dimensions['WIDTH'],
						'#HEIGHT#' => $dimensions['HEIGHT']
					]
				);
			}
			else
			{
				$result = Loc::getMessage('CRM_ORDER_PLC_DIMENSIONS_EMPTY');
			}
		}
		return $result;
	}

	private function prepareProductUrls(array &$list): void
	{
		$items = array_filter($list, [__CLASS__, 'filterCatalogProducts']);
		if (!empty($items))
		{
			$iblocks = [];
			foreach ($items as $id => $row)
			{
				$iblockId = ($row['OFFERS_IBLOCK_ID'] > 0 ? $row['OFFERS_IBLOCK_ID'] : $row['IBLOCK_ID']);
				if (!isset($iblocks[$iblockId]))
				{
					$iblocks[$iblockId] = [];
				}
				$iblocks[$iblockId][] = $id;
			}
			unset($id, $row);
			foreach ($iblocks as $iblockId => $rows)
			{
				$this->urlBuilder->setIblockId($iblockId);
				$this->urlBuilder->preloadUrlData($this->urlBuilder::ENTITY_ELEMENT, $rows);
				foreach ($rows as $id)
				{
					$url = $this->urlBuilder->getProductDetailUrl($id);
					if ($url != '')
					{
						$list[$id]['EDIT_PAGE_URL'] = $url;
					}
				}
				unset($url, $id);
				$this->urlBuilder->clearPreloadedUrlData();
			}
			unset($iblockId, $rows, $iblocks);
		}
		unset($items);
	}

	private static function filterCatalogProducts(array $row)
	{
		return (isset($row['MODULE']) && $row['MODULE'] == 'catalog');
	}

	private function prepareAdminLink($url)
	{
		return str_replace(
			[".php","/bitrix/admin/"],
			["/", "/shop/settings/"],
			$url
		);
	}

	/**
	 * @param array $headers
	 * @param \Bitrix\Main\Grid\Options $gridOptions
	 * @return array
	 */
	private function getVisibleColumns($gridOptions, $headers)
	{
		$visibleColumns = $gridOptions->GetVisibleColumns();

		if (empty($visibleColumns))
		{
			foreach ($headers as $header)
			{
				if ($header['default'])
				{
					$visibleColumns[] = $header['id'];
				}
			}
		}

		return $visibleColumns;
	}

	private function getCurrencyNameShort($currency)
	{
		$result = $currency;

		if(\Bitrix\Main\Loader::includeModule('currency'))
		{
			$parsedCurrencyFormat = \CCurrencyLang::getParsedCurrencyFormat($currency);
			$key = array_search('#', $parsedCurrencyFormat);
			$parsedCurrencyFormat[$key] = '';
			$result = implode('', $parsedCurrencyFormat);
		}

		return $result;
	}

	private function initCouponsData($newUserId, $orderId = 0, $oldUserId = 0)
	{
		\Bitrix\Sale\Helpers\Admin\OrderEdit::initCouponsData($newUserId, $orderId, $oldUserId);
	}

	private function addRequiredFields($flippedVisibleColumns)
	{
		//This fields are required always
		$flippedVisibleColumns = array_merge(
			array_flip(
				['PRODUCT_ID', 'MAIN_INFO', 'PRICE', 'QUANTITY']
			),
			$flippedVisibleColumns
		);

		if(isset($flippedVisibleColumns['VAT']))
		{
			$flippedVisibleColumns['VAT_RATE'] = true;
		}

		return $flippedVisibleColumns;
	}

	private function getCouponList()
	{
		return \Bitrix\Sale\Helpers\Admin\OrderEdit::getCouponList($this->order, false);
	}

	private function preparePagination()
	{
		$pageNum = 0;
		$navParams = array(
			'nPageSize' => $this->arParams['ORDER_PRODUCT_COUNT']
		);

		$this->arResult['FILTER_PRESETS'] = array();
		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID'], $this->arResult['FILTER_PRESETS']);
		$navParams = $gridOptions->GetNavParams($navParams);
		$pageSize = (int)(isset($navParams['nPageSize']) ? $navParams['nPageSize'] : $this->arParams['ORDER_PRODUCT_COUNT']);
		$enableNextPage = true;

		$count = count($this->arResult['PRODUCTS']);

		if(isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y')
		{
			$pageNum = 1;
		}
		elseif($pageSize > 0 && isset($_REQUEST['page']))
		{
			$nav = new \Bitrix\Main\UI\PageNavigation("orderProductList");
			$nav->allowAllRecords(false)
				->setPageSize($pageSize);
			$nav->setRecordCount($count);
			if ((int)$_REQUEST['page'] * $pageSize >= $count || (int)($_REQUEST['page']) < 0)
			{
				$pageNum = $nav->getPageCount();
			}
			else
			{
				$pageNum = (int)$_REQUEST['page'];
			}
		}

		if($pageNum > 0)
		{
			if(!isset($_SESSION['CRM_PAGINATION_DATA']))
			{
				$_SESSION['CRM_PAGINATION_DATA'] = array();
			}
			$_SESSION['CRM_PAGINATION_DATA'][$this->arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum, 'PAGE_SIZE' => $pageSize);
		}
		else
		{
			if( !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
				&& isset($_SESSION['CRM_PAGINATION_DATA'])
				&& isset($_SESSION['CRM_PAGINATION_DATA'][$this->arResult['GRID_ID']])
			)
			{
				$paginationData = $_SESSION['CRM_PAGINATION_DATA'][$this->arResult['GRID_ID']];
				if(isset($paginationData['PAGE_NUM'])
					&& isset($paginationData['PAGE_SIZE'])
					&& $paginationData['PAGE_SIZE'] == $pageSize
				)
				{
					$pageNum = (int)$paginationData['PAGE_NUM'];
				}
			}

			if($pageNum <= 0)
			{
				$pageNum  = 1;
			}
		}
		$offset = 0;
		if (isset($navParams['nTopCount']))
		{
			$limit = $navParams['nTopCount'];
		}
		else
		{
			$limit = $pageSize;
			$offset = $pageSize * ($pageNum - 1);
			if ($offset + $limit >= $count)
			{
				$enableNextPage = false;
			}
		}

		$result = [
			'PAGE_NUM' => $pageNum,
			'ENABLE_NEXT_PAGE' => $enableNextPage,
			'LIMIT' => $limit,

			'OFFSET' => $offset
		];

		if (isset($this->arParams['ACTION_URL']))
		{
			$result['URL'] = $this->arParams['ACTION_URL'];
		}

		return $result;
	}

	private function isProductPriceEditable(): bool
	{
		return AccessController::getCurrent()->checkByValue(
			ActionDictionary::ACTION_PRICE_ENTITY_EDIT,
			\CCrmOwnerType::Order
		);
	}
	
	private function isProductDiscountSet(): bool
	{
		return AccessController::getCurrent()->checkByValue(
			ActionDictionary::ACTION_PRODUCT_DISCOUNT_SET,
			\CCrmOwnerType::Order
		);
	}

	private function isAllowedProductCreation(): bool
	{
		return
			Main\Config\Option::get('sale', 'SALE_ADMIN_NEW_PRODUCT', 'Y') === 'Y'
			&& AccessController::getCurrent()->check(ActionDictionary::ACTION_PRODUCT_EDIT)
		;
	}

	private function isAllowedCatalogView(): bool
	{
		return AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ);
	}

	public function executeComponent()
	{
		global $APPLICATION;

		if(!$this->init())
		{
			$this->showErrors();
			return;
		}

		if (!$this->initUrlBuilder())
		{
			$this->showErrors();
			return;
		}

		$this->arResult['GRID_ID'] = 'crm_order_product_list';
		$this->arResult['HEADERS'] = $this->getHeaders();
		$this->arResult['AJAX_OPTION_JUMP'] = isset($this->arParams['AJAX_OPTION_JUMP']) ? $this->arParams['AJAX_OPTION_JUMP'] : 'N';
		$this->arResult['AJAX_OPTION_HISTORY'] = isset($this->arParams['AJAX_OPTION_HISTORY']) ? $this->arParams['AJAX_OPTION_HISTORY'] : 'N';
		$this->arResult['PRESERVE_HISTORY'] = isset($this->arParams['PRESERVE_HISTORY']) ? $this->arParams['PRESERVE_HISTORY'] : false;
		$this->arResult['FORM_ID'] = isset($this->arParams['FORM_ID']) ? $this->arParams['FORM_ID'] : 'form_'.$this->arResult['GRID_ID'];
		$this->arResult['TAB_ID'] = isset($this->arParams['TAB_ID']) ? $this->arParams['TAB_ID'] : '';
		$this->arResult['AJAX_ID'] = isset($this->arParams['AJAX_ID']) ? $this->arParams['AJAX_ID'] : '';
		$this->arResult['PATH_TO_ORDER_PRODUCT_LIST'] = $this->arParams['PATH_TO_ORDER_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_ORDER_PRODUCT_LIST', $this->arParams['PATH_TO_ORDER_PRODUCT_LIST'], $APPLICATION->GetCurPage());
		$this->arResult['ORDER_SITE_ID'] = $this->order->getSiteId();
		$this->arResult['ORDER_ID'] = $this->order->getId();
		$this->arResult['CAN_UPDATE_ORDER'] = \Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission(intval($this->arResult['ORDER_ID']), $this->userPermissions);
		$this->arResult['VAT_RATES'] = $this->getVatRates();

		$this->arResult["DISCOUNTS_LIST"] = \Bitrix\Sale\Helpers\Admin\OrderEdit::getOrderedDiscounts($this->order);

		if(is_array($this->arResult["DISCOUNTS_LIST"]['DISCOUNT_LIST']))
		{
			foreach($this->arResult["DISCOUNTS_LIST"]['DISCOUNT_LIST'] as $id => $discount)
			{
				if(!empty($discount['EDIT_PAGE_URL']))
				{
					$this->arResult["DISCOUNTS_LIST"]['DISCOUNT_LIST'][$id]['EDIT_PAGE_URL'] = $this->prepareAdminLink($discount['EDIT_PAGE_URL']);
				}
			}
		}

		//TOTAL VALUES
		$this->arResult['PRICE_TOTAL'] = $this->order->getPrice();
		$this->arResult['CURRENCY'] = $this->order->getCurrency();
		$this->arResult['TAX_VALUE'] = $this->order->getTaxValue();
		$this->arResult['PRICE_DELIVERY_DISCOUNTED'] = $this->order->getDeliveryPrice();
		$this->arResult['SUM_PAID'] = $this->order->getSumPaid();
		$this->arResult['ORDER_DISCOUNT_VALUE'] = $this->order->getField('DISCOUNT_VALUE');
		$this->arResult['SUM_UNPAID'] = $this->arResult['PRICE_TOTAL'] - $this->arResult['SUM_PAID'];

		if($this->arResult["DISCOUNTS_LIST"]["PRICES"]["DELIVERY"]["DISCOUNT"])
		{
			$this->arResult['DELIVERY_DISCOUNT'] = $this->arResult["DISCOUNTS_LIST"]["PRICES"]["DELIVERY"]["DISCOUNT"];
		}
		else
		{
			$this->arResult['DELIVERY_DISCOUNT'] = 0;
		}

		$this->arResult['PRICE_DELIVERY'] = $this->arResult['PRICE_DELIVERY_DISCOUNTED'] + $this->arResult['DELIVERY_DISCOUNT'];
		$this->arResult["WEIGHT_UNIT"] = htmlspecialcharsbx(Main\Config\Option::get('sale', 'weight_unit', Loc::getMessage('CRM_ORDER_PLC_DEFAULT_WEIGHT_UNIT'), $this->order->getSiteId()));

		if($basket = $this->order->getBasket())
		{
			$this->arResult['PRICE_BASKET_DISCOUNTED'] = $basket->getPrice();
			$this->arResult['PRICE_BASKET'] = $basket->getBasePrice();
			$this->arResult["WEIGHT"] = $basket->getWeight();

			$weightKoef = (float)Main\Config\Option::get('sale', 'weight_koef', 1000, $this->order->getSiteId());
			if ($weightKoef <= 0)
			{
				$weightKoef = 1;
			}

			$this->arResult["WEIGHT_FOR_HUMAN"] = round(
				(float)($this->arResult["WEIGHT"]/$weightKoef),
				SALE_WEIGHT_PRECISION
			);
		}
		else
		{
			$this->arResult['PRICE_BASKET_DISCOUNTED'] = 0;
			$this->arResult['PRICE_BASKET'] = 0;
			$this->arResult["WEIGHT_FOR_HUMAN"] = 0;
		}

		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID']);

		$_arSort = $gridOptions->GetSorting([
			'sort' => ['SORT' => 'asc'],
			'vars' => ['by' => 'by', 'order' => 'order']
		]);

		$this->arResult['SORT'] = $_arSort['sort'];
		$this->arResult['SORT_VARS'] = $_arSort['vars'];
		$this->arResult['VISIBLE_COLUMNS'] = $this->getVisibleColumns($gridOptions, $this->arResult['HEADERS']);
		$this->arResult['PRODUCTS'] = $this->getProductsFields($this->arResult['VISIBLE_COLUMNS']);
		$this->arResult['COUPONS_LIST'] = $this->getCouponList();
		$this->arResult['TOTAL_ROWS_COUNT'] = count($this->arResult['PRODUCTS']);

		if (!$this->order->isNew())
		{
			$pagination = $this->preparePagination();
			$this->arResult['PRODUCTS'] = array_slice($this->arResult['PRODUCTS'], $pagination['OFFSET'], $pagination['LIMIT']);
			$this->arResult['PAGINATION'] = $pagination;
		}

		$this->arResult['SHOW_PAGINATION'] =
		$this->arResult['SHOW_TOTAL_COUNTER'] =
		$this->arResult['SHOW_PAGESIZE'] = !$this->order->isNew();
		$this->arResult['ALLOW_SELECT_PRODUCT'] = $this->isAllowedCatalogView();
		$this->arResult['ALLOW_CREATE_NEW_PRODUCT'] = $this->isAllowedProductCreation();
		$this->arResult['ORDER_PRODUCT_PRICE_EDITABLE'] = $this->isProductPriceEditable();
		$this->arResult['ALLOW_SET_ORDER_PRODUCT_DISCOUNT'] = $this->isProductDiscountSet();
		$this->IncludeComponentTemplate();
	}
}