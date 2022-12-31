<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Catalog\Access\Permission\PermissionDictionary;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Product\Url;
use Bitrix\Iblock\Url\AdminPage\BuilderManager;
use Bitrix\Catalog;

Loc::loadMessages(__FILE__);

final class CCrmOrderShipmentProductListComponent extends \CBitrixComponent
{
	private $userId = 0;
	private $userPermissions;
	private $errors = [];

	/** @var Bitrix\Crm\Order\Order */
	private $order = null;
	/** @var \Bitrix\Crm\Order\Shipment  */
	private $shipment = null;

	/** @var null|\Bitrix\Catalog\Url\AdminPage\CatalogBuilder  */
	private $urlBuilder = null;

	private function init()
	{
		if(!CModule::IncludeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		if(!CModule::IncludeModule('currency'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
			return false;
		}

		if(!CModule::IncludeModule('catalog'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');
			return false;
		}

		if (!CModule::IncludeModule('sale'))
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

		if(!empty($this->arParams['SHIPMENT']))
		{
			$this->shipment = $this->arParams['SHIPMENT'];
			$this->order = $this->shipment->getParentOrder();
		}
		elseif((int)$this->arParams['SHIPMENT_ID'])
		{
			$this->shipment = \Bitrix\Crm\Order\Manager::getShipmentObject($this->arParams['SHIPMENT_ID']);
			$this->order = $this->shipment->getParentOrder();
		}
		elseif((int)$this->arParams['ORDER_ID'] > 0)
		{
			$this->order = \Bitrix\Crm\Order\Order::load($this->arParams['ORDER_ID']);
			$shipments = $this->order->getShipmentCollection();
			$this->shipment = $shipments->createItem();
		}

		if(!$this->shipment)
		{
			$this->errors[] = new Main\Error(Loc::getMessage('CRM_ORDER_SPLC_FAILED_TO_CREATE_OBJECT'));
			return false;
		}

		$this->userId = CCrmSecurityHelper::GetCurrentUserID();
		CUtil::InitJSCore(['ajax', 'tooltip', 'sidepanel']);
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
			$this->errors[] = new Main\Error(Loc::getMessage('CRM_ORDER_SPLC_ERR_URL_BUILDER_ABSENT'));
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
		$result = [
			['id' => 'NAME', 'name' => Loc::getMessage('CRM_ORDER_SPLC_NAME'), 'sort' => 'NAME', 'default' => true],
			['id' => 'PICTURE', 'name' => Loc::getMessage('CRM_ORDER_SPLC_PICTURE'), 'default' => true],
			['id' => 'PROPERTIES', 'name' => Loc::getMessage('CRM_ORDER_SPLC_PROPERTIES'), 'default' => true],
			['id' => 'QUANTITY', 'name' => Loc::getMessage('CRM_ORDER_SPLC_QUANTITY'), 'sort' => 'QUANTITY', 'default' => true]
		];

		if(!$this->shipment->isSystem()) //skip during adding product mode
		{
			$result[] = ['id' => 'AMOUNT', 'name' => Loc::getMessage('CRM_ORDER_SPLC_AMOUNT'), 'sort' => 'AMOUNT', 'default' => true, 'editable' => true];
			$useStoreControl = (Main\Config\Option::get('catalog', 'default_use_store_control', 'N') == 'Y');

			if($useStoreControl)
			{
				$result = array_merge($result,[
					['id' => 'STORE_ID', 'name' => Loc::getMessage('CRM_ORDER_SPLC_STORE_ID'), 'default' => true, 'editable' => true],
					['id' => 'STORE_QUANTITY', 'name' => Loc::getMessage('CRM_ORDER_SPLC_STORE_QUANTITY'), 'default' => true, 'editable' => true],
					['id' => 'STORE_REMAINING_QUANTITY', 'name' => Loc::getMessage('CRM_ORDER_SPLC_STORE_REMAINING_QUANTITY'), 'default' => true]
				]);
			}

			$result[] = ['id' => 'STORE_BARCODE', 'name' => Loc::getMessage('CRM_ORDER_SPLC_STORE_BARCODE'), 'default' => true];
		}

		/*
		$result = array_merge($result,[
			['id' => 'WEIGHT', 'name' => Loc::getMessage('CRM_ORDER_SPLC_WEIGHT'), 'sort' => 'WEIGHT'],
			['id' => 'DIMENSIONS', 'name' => Loc::getMessage('CRM_ORDER_SPLC_DIMENSIONS')],
			['id' => 'SORT', 'name' => Loc::getMessage('CRM_ORDER_SPLC_SORTING'), 'sort' => 'SORT'],
		]);
		*/

		return $result;
	}

	/**
	 * @param \Bitrix\Crm\Order\ShipmentItem $item
	 * @return array
	 */
	private function createBarcodeInfo($item)
	{
		$result = [];
		$itemStoreCollection = $item->getShipmentItemStoreCollection();
		if (!$itemStoreCollection)
		{
			return $result;
		}

		/** @var \Bitrix\Sale\ShipmentItemStore $barcode */
		foreach ($itemStoreCollection as $barcode)
		{
			$storeId = $barcode->getStoreId();

			if (!isset($result[$storeId]))
			{
				$result[$storeId] = [];
			}

			$result[$storeId][] = [
				'ID' => $barcode->getId(),
				'BARCODE' => $barcode->getField('BARCODE'),
				'MARKING_CODE' => $barcode->getField('MARKING_CODE'),
				'QUANTITY' => $barcode->getQuantity()
			];
		}

		return $result;
	}

	/**
	 * @return array
	 * @throws Exception
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\LoaderException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectNotFoundException
	 */
	private function getProductsFields($collection)
	{
		$shipmentItemCollection = $collection;
		$systemShipmentItemCollection = null;
		$systemShipmentItem = null;

		if (!$this->shipment->isSystem())
		{
			/** @var \Bitrix\Crm\Order\Shipment $systemShipment */
			$systemShipment = $this->shipment->getCollection()->getSystemShipment();
			$systemShipmentItemCollection = $systemShipment->getShipmentItemCollection();
		}

		$items = array();
		$catalogProductsIds = array();

		/** @var \Bitrix\Sale\ShipmentItem $item */
		foreach($shipmentItemCollection as $item)
		{
			$basketItem = $item->getBasketItem();

			if ($basketItem && $basketItem->getField("MODULE") == "catalog")
				$catalogProductsIds[] = $basketItem->getProductId();
		}

		if(!empty($catalogProductsIds))
		{
			$catalogProductsFields = \Bitrix\Sale\Helpers\Admin\Blocks\OrderBasket::getProductsData(
				$catalogProductsIds,
				$this->order->getSiteId(),
				array(),
				$this->order->getUserId()
			);

			$this->prepareProductUrls($catalogProductsFields);
		}

		/** @var \Bitrix\Sale\ShipmentItem $item */
		foreach($shipmentItemCollection as $item)
		{
			$params = array();

			$basketItem = $item->getBasketItem();
			if ($basketItem)
			{
				if ($systemShipmentItemCollection)
				{
					/** @var \Bitrix\Sale\ShipmentItemCollection $systemShipmentItemCollection */
					$systemShipmentItem = $systemShipmentItemCollection->getItemByBasketCode($basketItem->getBasketCode());
				}

				$productId = $basketItem->getProductId();

				if ($basketItem->getField("MODULE") === "catalog" && !empty($catalogProductsFields[$productId]))
				{
					$params = $catalogProductsFields[$productId];
				}

				if (intval($basketItem->getField("MEASURE_CODE")) > 0)
					$params["MEASURE_CODE"] = intval($basketItem->getField("MEASURE_CODE"));
				elseif (!isset($params["MEASURE_CODE"]))
					$params["MEASURE_CODE"] = 0;

				if($basketItem->getField("MEASURE_NAME") <> '')
					$params["MEASURE_TEXT"] = $basketItem->getField("MEASURE_NAME");
				elseif(!isset($params["MEASURE_TEXT"]))
					$params["MEASURE_TEXT"] = "";

				if ($basketItem->isBundleParent())
				{
					$params["BASE_ELEMENTS_QUANTITY"] = $basketItem->getBundleBaseQuantity();
					if (!isset($params['IS_SET_ITEM']))
						$params['IS_SET_ITEM'] = 'N';
					if (!isset($params['IS_SET_PARENT']))
						$params['IS_SET_PARENT'] = 'Y';
					if (!isset($params['OLD_PARENT_ID']))
						$params['OLD_PARENT_ID'] = '';
				}
				$params["BASKET_ID"] = $basketItem->getId();
				$params["PRODUCT_PROVIDER_CLASS"] = $basketItem->getProvider();
				$params["NAME"] = $basketItem->getField("NAME");
				$params["MODULE"] = $basketItem->getField("MODULE");
				$params['BARCODE_INFO'] = $this->createBarcodeInfo($item);
				$params['ORDER_DELIVERY_BASKET_ID'] = $item->getId();
				$params["QUANTITY"] = floatval($item->getQuantity());
				$params["AMOUNT"] = floatval($item->getQuantity());
				$params["PRICE"] = $basketItem->getPrice();
				$params["CURRENCY"] = $basketItem->getCurrency();
				$params["PRODUCT_PROVIDER_CLASS"] = $basketItem->getProvider();
				$params["PROPS"] = array();
				$params["IS_SUPPORTED_MARKING_CODE"] = $basketItem->isSupportedMarkingCode() ? 'Y' : 'N';

				/** @var \Bitrix\Sale\BasketPropertyItem $property */
				foreach($basketItem->getPropertyCollection() as $property)
				{
					$params["PROPS"][] = array(
						"VALUE" => $property->getField("VALUE"),
						"NAME" => $property->getField("NAME"),
						"CODE" => $property->getField("CODE"),
						"SORT" => $property->getField("SORT")
					);
				}

				if(\Bitrix\Main\Loader::includeModule("catalog"))
				{
					$productInfo = \CCatalogSku::GetProductInfo($productId);
					$params["OFFERS_IBLOCK_ID"] = $productInfo["OFFER_IBLOCK_ID"];
					$params["IBLOCK_ID"] = $productInfo["IBLOCK_ID"];
					$params["PRODUCT_ID"] = $productInfo["ID"];
				}

				if ($basketItem->isBundleChild())
					$params["PARENT_BASKET_ID"] = $basketItem->getParentBasketItem()->getId();

				//If product became bundle, but in saved order it is a simple product.
				if ($basketItem->getBasketCode() == intval($basketItem->getBasketCode()) && !$basketItem->isBundleParent() && !empty($params['SET_ITEMS']))
				{
					unset($params['SET_ITEMS'], $params['OLD_PARENT_ID']);
					$params['IS_SET_PARENT'] = 'N';
				}
			}
			else
			{
				if ($systemShipmentItemCollection)
				{
					/** @var \Bitrix\Sale\ShipmentItemCollection $systemShipmentItemCollection */
					$systemShipmentItem = $systemShipmentItemCollection->getItemByBasketId($item->getBasketId());
				}

				$systemItemQuantity = ($systemShipmentItem) ? $systemShipmentItem->getQuantity() : 0;
				$params = array(
					'NAME' => Loc::getMessage("CRM_ORDER_SPLC_FAILED_TO_CREATE_OBJECT_B"),
					'QUANTITY' => floatval($item->getQuantity() + $systemItemQuantity),
					'AMOUNT' => floatval($item->getQuantity()),
					'BASKET_ID' => $item->getBasketId(),
				);
			}

			$params['BASKET_CODE'] = $item->getBasketCode();
			if (is_array($params['STORES']) && !empty($params['STORES']))
			{
				$params['STORES'] = $this->filterStores($params['STORES']);
			}
			if (!$basketItem->isService())
			{
				$this->setStoresBarcodesInfo($params);
			}

			if($this->arResult['SHOW_TOOL_PANEL'] != 'Y')
			{
				if(empty($params['BARCODE_INFO']) && is_array($params['STORES']) && !empty($params['STORES']))
				{
					$this->arResult['SHOW_TOOL_PANEL'] = 'Y';
				}
			}

			$items[$params['BASKET_ID']] = $params;
		}

		$bundleChild = array();

		foreach ($items as $basketId => $item)
		{
			$parentBasketId = $item['PARENT_BASKET_ID'];
			if ($parentBasketId > 0)
			{
				$item['IS_SET_ITEM'] = 'Y';
				$item['OLD_PARENT_ID'] = $items[$parentBasketId]['OLD_PARENT_ID'];
				$bundleChild[$parentBasketId][] = $item;
				unset($items[$basketId]);
			}
		}

		if ($this->arResult['LOADING_SET_ITEMS'] && !empty($bundleChild[$_REQUEST['parent_id']]))
		{
			$items = $bundleChild[$_REQUEST['parent_id']];
		}
		else
		{
			foreach ($items as $basketId => $item)
			{
				if (isset($bundleChild[$basketId]))
				{
					$items[$basketId]['SET_ITEMS'] = $bundleChild[$basketId];
				}
			}
		}


		return $items;
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

	private function filterStores(array $stores): array
	{
		$allowedStores = $this->getAllowedStores();
		if (!$allowedStores)
		{
			return [];
		}

		if (in_array(PermissionDictionary::VALUE_VARIATION_ALL, $allowedStores, true))
		{
			return $stores;
		}

		$flippedAllowedStores = array_flip($allowedStores);

		return array_filter(
			$stores,
			static function ($storeInfo) use ($flippedAllowedStores) {
				return isset($flippedAllowedStores[$storeInfo['STORE_ID']]);
			}
		);
	}

	private function setStoresBarcodesInfo(&$product)
	{
		if(is_array($product['STORES']) && !empty($product['STORES']))
		{
			$stores = [];
			$storesBarcodesInfo = [];

			foreach($product['STORES'] as $store)
			{
				$stores[$store['STORE_ID']] = $store;
			}

			$barcode = '';

			if($product['BARCODE_MULTI'] != 'Y' )
			{
				$res = \Bitrix\Catalog\StoreBarcodeTable::getList([
					'filter' => ['PRODUCT_ID' => $product['OFFER_ID']],
					'limit' => 1
				]);

				if($fields = $res->fetch())
				{
					$barcode = $fields['BARCODE'];
				}
			}

			if(!empty($product['BARCODE_INFO']))
			{
				foreach($product['BARCODE_INFO'] as $storeId => $barCodeInfo)
				{
					$quantity = 0;
					$barcodes = [];
					$barcode = '';
					$barcodeId = 0;

					foreach($barCodeInfo as $item)
					{
						$quantity += $item['QUANTITY'];

						if($product['BARCODE_MULTI'] == 'Y' || $product['IS_SUPPORTED_MARKING_CODE'] == 'Y')
						{
							$barcodes[] = ['ID' => (int)$item['ID'], 'VALUE' => (string)$item['BARCODE'], 'MARKING_CODE' => (string)$item['MARKING_CODE']];
						}
						elseif(empty($barcode))
						{
							$barcode = $item['BARCODE'];
							$barcodeId = $item['ID'];
						}
					}

					$storesBarcodesInfo[$storeId] = [
						'QUANTITY' => $quantity,
						'BARCODES' => ($product['BARCODE_MULTI'] == 'Y' || $product['IS_SUPPORTED_MARKING_CODE'] == 'Y' ? $barcodes : []),
						'BARCODE' => ($product['BARCODE_MULTI'] != 'Y' && $product['IS_SUPPORTED_MARKING_CODE'] != 'Y' ? $barcode : ''),
						'BARCODE_ID' => ($product['BARCODE_MULTI'] != 'Y' && $product['IS_SUPPORTED_MARKING_CODE'] != 'Y' ? $barcodeId : 0),
						'STORE_ID' => $storeId,
						'AMOUNT' => isset($stores[$storeId]['AMOUNT']) ? $stores[$storeId]['AMOUNT'] : 0,
						'STORE_NAME' => isset($stores[$storeId]['STORE_NAME']) ? $stores[$storeId]['STORE_NAME'] : 'Unknown store "'.$storeId.'"',
						'MEASURE_TEXT' => $product['MEASURE_TEXT'],
						'BASKET_ID' => $product['BASKET_ID'],
						'BASKET_CODE' => $product['BASKET_CODE'],
						'IS_USED' => 'Y',
						'BARCODE_MULTI' => $product['BARCODE_MULTI'],
						'IS_SUPPORTED_MARKING_CODE' => $product['IS_SUPPORTED_MARKING_CODE']
					];

					unset($stores[$storeId]);
				}
			}
			else
			{
				reset($stores);
				$store = current($stores);
				$storeId = $store['STORE_ID'];

				$storesBarcodesInfo[$storeId] = [
					'QUANTITY' => $product['QUANTITY'],
					'BARCODES' => [],
					'BARCODE' => $barcode,
					'BARCODE_ID' => 0,
					'STORE_ID' => $storeId,
					'AMOUNT' => $store['AMOUNT'],
					'STORE_NAME' => $store['STORE_NAME'],
					'MEASURE_TEXT' => $product['MEASURE_TEXT'],
					'BASKET_ID' => $product['BASKET_ID'],
					'BASKET_CODE' => $product['BASKET_CODE'],
					'IS_USED' => 'Y',
					'BARCODE_MULTI' => $product['BARCODE_MULTI'],
					'IS_SUPPORTED_MARKING_CODE' => $product['IS_SUPPORTED_MARKING_CODE']
				];

				unset($stores[$storeId]);
			}

			if(!empty($stores))
			{
				foreach($stores as $storeId => $store)
				{
					$storesBarcodesInfo[$storeId] = [
						'QUANTITY' => 0,
						'BARCODES' => [],
						'BARCODE' => $barcode,
						'BARCODE_ID' => 0,
						'STORE_ID' => $storeId,
						'AMOUNT' => $store['AMOUNT'],
						'STORE_NAME' => $store['STORE_NAME'],
						'MEASURE_TEXT' => $product['MEASURE_TEXT'],
						'BASKET_ID' => $product['BASKET_ID'],
						'BASKET_CODE' => $product['BASKET_CODE'],
						'IS_USED' => 'N',
						'BARCODE_MULTI' => $product['BARCODE_MULTI'],
						'IS_SUPPORTED_MARKING_CODE' => $product['IS_SUPPORTED_MARKING_CODE']
					];
				}
			}

			$product['STORE_BARCODE_INFO'] = $storesBarcodesInfo;
		}
		elseif ($product['IS_SUPPORTED_MARKING_CODE'] == 'Y' && (float)$product['QUANTITY'] > 0)
		{
			$barcodes = [];

			if(!empty($product['BARCODE_INFO'][0]))
			{
				$barCodeInfo = $product['BARCODE_INFO'][0];

				foreach($barCodeInfo as $item)
				{
					if($product['BARCODE_MULTI'] == 'Y' || $product['IS_SUPPORTED_MARKING_CODE'] == 'Y')
					{
						$barcodes[] = ['ID' => (int)$item['ID'], 'VALUE' => (string)$item['BARCODE'], 'MARKING_CODE' => (string)$item['MARKING_CODE']];
					}
				}
			}
			else
			{
				$barcodes = array_fill(
					0,
					(int)$product['QUANTITY'],
					['ID' => 0, 'VALUE' => '', 'MARKING_CODE' => '']
				);
			}

			$product['STORE_BARCODE_INFO'] = [
				0 => [
					'QUANTITY' => $product['QUANTITY'],
					'BARCODES' => $barcodes,
					'BARCODE' => '',
					'BARCODE_ID' => 0,
					'STORE_ID' => 0,
					'AMOUNT' => 0,
					'STORE_NAME' => '',
					'MEASURE_TEXT' => $product['MEASURE_TEXT'],
					'BASKET_ID' => $product['BASKET_ID'],
					'BASKET_CODE' => $product['BASKET_CODE'],
					'IS_USED' => 'Y',
					'BARCODE_MULTI' => $product['BARCODE_MULTI'],
					'IS_SUPPORTED_MARKING_CODE' => $product['IS_SUPPORTED_MARKING_CODE']
				]
			];
		}
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

		$this->arResult['GRID_ID'] = 'crm_order_shipment_product_list';
		$this->arResult['HEADERS'] = $this->getHeaders();
		$this->arResult['AJAX_OPTION_JUMP'] = isset($this->arParams['AJAX_OPTION_JUMP']) ? $this->arParams['AJAX_OPTION_JUMP'] : 'N';
		$this->arResult['AJAX_OPTION_HISTORY'] = isset($this->arParams['AJAX_OPTION_HISTORY']) ? $this->arParams['AJAX_OPTION_HISTORY'] : 'N';
		$this->arResult['PRESERVE_HISTORY'] = isset($this->arParams['PRESERVE_HISTORY']) ? $this->arParams['PRESERVE_HISTORY'] : false;
		$this->arResult['FORM_ID'] = isset($this->arParams['FORM_ID']) ? $this->arParams['FORM_ID'] : 'form_'.$this->arResult['GRID_ID'];
		$this->arResult['TAB_ID'] = isset($this->arParams['TAB_ID']) ? $this->arParams['TAB_ID'] : '';
		$this->arResult['AJAX_ID'] = isset($this->arParams['AJAX_ID']) ? $this->arParams['AJAX_ID'] : '';
		$this->arResult['PATH_TO_ORDER_SHIPMENT_PRODUCT_LIST'] = $this->arParams['PATH_TO_ORDER_SHIPMENT_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_ORDER_SHIPMENT_PRODUCT_LIST', $this->arParams['PATH_TO_ORDER_SHIPMENT_PRODUCT_LIST'], $APPLICATION->GetCurPage());
		$this->arResult['ORDER_SITE_ID'] = $this->order->getSiteId();
		$this->arResult['ORDER_ID'] =$this->order->getId();
		$this->arResult['LOADING_SET_ITEMS'] = ($_REQUEST['action'] === \Bitrix\Main\Grid\Actions::GRID_GET_CHILD_ROWS);
		$this->arResult['CAN_UPDATE_ORDER'] = \Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission(intval($this->arResult['ORDER_ID']), $this->userPermissions);
		if ($this->shipment->getField('DEDUCTED') === 'Y')
		{
			$this->arResult['CAN_UPDATE_ORDER'] = false;
		}

		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID']);

		$_arSort = $gridOptions->GetSorting([
			'sort' => ['ID' => 'desc'],
			'vars' => ['by' => 'by', 'order' => 'order']
		]);

		$this->arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
		$this->arResult['SORT_VARS'] = $_arSort['vars'];
		$this->arResult['VISIBLE_COLUMNS'] = $this->getVisibleColumns($gridOptions, $this->arResult['HEADERS']);

		/** @var \Bitrix\Crm\Order\ShipmentItemCollection $itemsCollection */
		$itemsCollection = $this->shipment->getShipmentItemCollection();

		if($itemsCollection->count() <= 0 && $this->shipment->getId() <= 0 && empty($this->arParams['SHIPMENT']))
		{
			$itemsCollection = $this->shipment->getCollection()->getSystemShipment()->getShipmentItemCollection();
		}

		$this->arResult['SHOW_TOOL_PANEL'] = 'N';
		$this->arResult['DEDUCTED'] = $this->shipment->getField('DEDUCTED');
		$this->arResult['PRODUCTS'] = $this->getProductsFields($itemsCollection);
		$this->arResult['SERVICE_URL'] = $this->getPath().'/ajax.php';
		$this->arResult['ALLOW_SELECT_PRODUCT'] = $this->isAllowedCatalogView();
		$this->arResult['ADD_PRODUCT_URL'] = $this->getPath().'/addproduct.php?shipmentId='.$this->shipment->getId().'&orderId='.$this->order->getId().'&'.bitrix_sessid_get();
		$this->IncludeComponentTemplate();
	}

	private function getAllowedStores(): ?array
	{
		return AccessController::getCurrent()->getPermissionValue(ActionDictionary::ACTION_STORE_VIEW);
	}

	private function isAllowedCatalogView(): bool
	{
		return AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ);
	}
}