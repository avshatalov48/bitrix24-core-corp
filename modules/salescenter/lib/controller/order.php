<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Error;
use Bitrix\Sale;
use Bitrix\Crm;
use Bitrix\Catalog;
use Bitrix\Sale\Helpers\Order\Builder;
use Bitrix\Sale\Internals\SiteCurrencyTable;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\ImOpenLinesManager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\SaleManager;

class Order extends Base
{
	public function configureActions()
	{
		return array(
			'searchProduct' => array('class' => SearchProductAction::class)
		);
	}

	private function checkModules()
	{
		if (!Main\Loader::includeModule('crm'))
		{
			$this->addError(new Main\Error('module "crm" is not installed.'));
			return null;
		}
		if (!Main\Loader::includeModule('catalog'))
		{
			$this->addError(new Main\Error('module "catalog" is not installed.'));
			return null;
		}
		if (!Main\Loader::includeModule('sale'))
		{
			$this->addError(new Main\Error('module "sale" is not installed.'));
			return null;
		}
		if(Bitrix24Manager::getInstance()->isPaymentsLimitReached())
		{
			$this->addError(new Error('You have reached limit of payments for your tariff'));
			return null;
		}
	}

	public function getBaseProductPriceAction($productId)
	{
		$this->checkModules();
		if (!empty($this->getErrors()))
			return null;

		$priceRaw = Catalog\PriceTable::getList(array(
			'select' => array('PRICE', 'CURRENCY'),
			'filter' => array('=PRODUCT_ID' => (int)$productId, '=CATALOG_GROUP_ID' => \CCatalogGroup::GetBaseGroup()),
			'limit' => 1
		));

		if ($priceData = $priceRaw->fetch())
		{
			$price = (float)$priceData['PRICE'];
			$orderBaseCurrency = SiteCurrencyTable::getSiteCurrency(SITE_ID);
			if (empty($orderBaseCurrency))
			{
				$orderBaseCurrency = CurrencyManager::getBaseCurrency();
			}
			if (!empty($orderBaseCurrency) && $orderBaseCurrency !== $priceData['CURRENCY'])
			{
				$price = \CCurrencyRates::ConvertCurrency($price, $priceData['CURRENCY'], $orderBaseCurrency);
				$currencyFormat = \CCurrencyLang::GetFormatDescription($orderBaseCurrency);
				if ($currencyFormat === false)
				{
					$currencyFormat = \CCurrencyLang::GetDefaultValues();
				}

				$price = round($price,  $currencyFormat['DECIMALS']);
			}
			return $price;
		}

		return null;
	}

	public function refreshBasketAction(array $basketItems = array())
	{
		$this->checkModules();
		if (!empty($this->getErrors()))
		{
			return null;
		}

		$formData = ['SITE_ID' => SITE_ID];

		$sortedByCode =
		$newProducts =
		$catalogProducts = [];

		foreach ($basketItems as $item)
		{
			if ($item['module'] !== 'catalog' && strlen($item['name']) === 0)
			{
				continue;
			}

			if ($item['module'] === 'catalog' && (int)$item['productId'] > 0)
			{
				$catalogProducts[] = (int)$item['productId'];
			}

			if (!empty($item['code']))
			{
				$sortedByCode[$item['code']] = $item;
			}

			if (!empty($item['encodedFields']))
			{
				$formData['PRODUCT'][$item['code']]['FIELDS_VALUES'] = Main\Web\Json::encode($this->prepareExistProductFields($item));
			}
			elseif ($item['module'] !== 'catalog' || (int)$item['productId'] > 0)
			{
				$newProducts[] = $this->prepareNewProductFields($item);
			}
		}

		$order = $this->buildOrder($formData);
		if (!$order)
		{
			if ($this->errorCollection->getErrorByCode('SALE_BASKET_AVAILABLE_QUANTITY'))
			{
				$catalogProductQuantityMap = $this->getProductAvailableQuantity($catalogProducts);
				foreach($basketItems as &$item)
				{
					if (
						(int)$item['productId'] > 0 
						&& isset($catalogProductQuantityMap[$item['productId']])
						&& $item['module'] === 'catalog'
						&& $catalogProductQuantityMap[$item['productId']] < $item['quantity']
					)
					{
						$item['errors'] = ['SALE_BASKET_AVAILABLE_QUANTITY'];
						$item['encodedFields'] = '';
					}
				}
			}

			return ['items' => $basketItems];
		}

		$basket = $order->getBasket();
		if (!empty($newProducts))
		{
			$res = $this->addProductToBasket($basket, $newProducts);
			if (!$res->isSuccess())
			{
				$catalogProductQuantityMap = $this->getProductAvailableQuantity($catalogProducts);
				$this->addErrors($res->getErrors());
				foreach($basketItems as &$item)
				{
					if ((int)$item['productId'] > 0 && $item['module'] === 'catalog')
					{
						if ($this->errorCollection->getErrorByCode('SALE_BASKET_ITEM_WRONG_PRICE'))
						{
							if ($item['isCustomPrice'] === 'N' && (float)$item['catalogPrice'] === 0.0)
							{
								$item['errors'] = ['SALE_BASKET_ITEM_WRONG_PRICE'];
								$item['encodedFields'] = '';
							}
						}
						elseif (
							isset($catalogProductQuantityMap[$item['productId']])
							&& $catalogProductQuantityMap[$item['productId']] < $item['quantity']
						)
						{
							$item['errors'] = ['SALE_BASKET_AVAILABLE_QUANTITY'];
							$item['encodedFields'] = '';
						}
					}
				}

				return ['items' => $basketItems];
			}
		}

		$discountSum = 0;
		foreach ($basket as $basketItem)
		{
			$discountSum += ($basketItem->getDiscountPrice()) * $basketItem->getQuantity();
		}

		return [
			'items' => $this->fillResultBasket($order, $sortedByCode),
			'total' => [
				'discount' => SaleFormatCurrency($discountSum, $order->getCurrency()),
				'result' => SaleFormatCurrency($order->getPrice(), $order->getCurrency()),
			]
		];
	}

	/**
	 * @param Sale\BasketBase $basket
	 * @param array $products
	 *
	 * @return Main\Result
	 */
	private function addProductToBasket(Sale\BasketBase $basket, array $products)
	{
		$result = new Main\Result();
		$order = $basket->getOrder();

		$context = array(
			'SITE_ID' => $order->getSiteId(),
			'CURRENCY' => $order->getCurrency(),
		);
		$maxProductId = 0;
		foreach ($basket as $basketItem)
		{
			$productId = $basketItem->getProductId();
			$maxProductId = max($maxProductId, $productId);
		}
		foreach ($products as $productFields)
		{
			$productFields['CURRENCY'] = $order->getCurrency();
			if ($productFields['MODULE'] === 'catalog' && $productFields['CUSTOM_PRICE'] !== 'Y')
			{
				$result = Catalog\Product\Basket::addProductToBasket(
					$order->getBasket(),
					$productFields,
					$context
				);
			}
			else
			{
				$maxProductId++;
				$basketItem = $basket->createItem('', $maxProductId);
				if (isset($productFields['MODULE']))
				{
					$basketItem->setFieldNoDemand('MODULE', $productFields['MODULE']);
					unset($productFields['MODULE']);
				}
				$basketItem->setFields($productFields);
			}
		}

		return $result;
	}

	/**
	 * @param Sale\Order $order
	 * @param array $sortedDefaultItems
	 *
	 * @return array
	 */
	private function fillResultBasket(Sale\Order $order, array $sortedDefaultItems = [])
	{
		$basket = $order->getBasket();
		$discount = $order->getDiscount();
		$discountResult = $discount->getApplyResult(true);
		$discountBasket = $discountResult['RESULT']['BASKET'];
		$discountList = $discountResult['DISCOUNT_LIST'];

		$resultBasket = [];
		$currencyFormat = \CCurrencyLang::GetFormatDescription($order->getCurrency());
		if ($currencyFormat === false)
		{
			$currencyFormat = \CCurrencyLang::GetDefaultValues();
		}
		/** @var Sale\BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
			$code = $basketItem->getBasketCode();
			$sort = $basketItem->getField('SORT');
			$preparedItem = [
				'code' => $code,
				'productId' => $basketItem->getProductId(),
				'sort' => $sort,
				'name' => $basketItem->getField('NAME'),
				'basePrice' => round($basketItem->getBasePrice(),  $currencyFormat['DECIMALS']),
				'quantity' => $basketItem->getQuantity(),
				'formattedPrice' => SaleFormatCurrency($basketItem->getPrice(),  $order->getCurrency()),
				'encodedFields' => Main\Web\Json::encode($basketItem->getFieldValues()),
				'errors' => [],
				'discountInfos' => []
			];
			if (isset($sortedDefaultItems[$code]))
			{
				$preparedItem = array_merge($sortedDefaultItems[$code], $preparedItem);
			}

			if (!empty($discountBasket[$code]) && is_array($discountBasket[$code]))
			{
				foreach ($discountBasket[$code] as $discountBasketItem)
				{
					$discountId = $discountBasketItem['DISCOUNT_ID'];
					$discount = $discountList[$discountId];
					if (!empty($discount))
					{
						$preparedItem['discountInfos'][] = [
							'name' => $discount['NAME'],
							'editPageUrl' => str_replace(
								[".php","/bitrix/admin/"],
								["/", "/shop/settings/"],
								$discount['EDIT_PAGE_URL']
							),
						];
					}
				}
			}

			if (!$basketItem->isCustomPrice())
			{
				if ($basketItem->getDiscountPrice() > 0)
				{
					if (empty($preparedItem['discountType']))
					{
						$preparedItem['discountType'] = 'percent';
					}

					if (empty($preparedItem['showDiscount']))
					{
						$preparedItem['showDiscount'] = 'Y';
					}

					if ($preparedItem['discountType'] !== 'percent')
					{
						$preparedItem['discount'] = (float)$basketItem->getDiscountPrice();
					}
					else
					{
						$preparedItem['discount'] = (float)(string)($basketItem->getDiscountPrice() / $basketItem->getBasePrice() * 100);
					}
				}
				else
				{
					$preparedItem['discount'] = 0;
				}
			}

			$resultBasket[$sort] = $preparedItem;
		}

		sort($resultBasket);

		return $resultBasket;
	}

	/**
	 * @param array $formData
	 *
	 * @return Sale\Order
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private function buildOrder(array $formData = [])
	{
		$settings =	[
			'createUserIfNeed' => Builder\SettingsContainer::SET_ANONYMOUS_USER,
			'acceptableErrorCodes' => [],
			'cacheProductProviderData' => true,
		];
		$builderSettings = new Builder\SettingsContainer($settings);
		$orderBuilder = new Crm\Order\OrderBuilderCrm($builderSettings);
		$director = new Builder\Director;

		/** @var Sale\Order $order */
		$order = $director->createOrder($orderBuilder, $formData);
		if (!$order)
		{
			$this->addErrors($orderBuilder->getErrorsContainer()->getErrors());
		}

		return $order;
	}

	private function prepareExistProductFields($item)
	{
		$productData = Main\Web\Json::decode($item['encodedFields']);
		$productData['OFFER_ID'] = $productData['PRODUCT_ID'];
		$productData['SORT'] = $item['sort'];
		if ((float)$item['quantity'] > 0)
		{
			$productData['QUANTITY'] = (float)$item['quantity'];
		}
		if ($item['isCustomPrice'] === 'Y')
		{
			$productData['CUSTOM_PRICE'] = 'Y';
			$productData['BASE_PRICE'] = (float)$item['basePrice'];
			$productData['PRICE'] = $productData['BASE_PRICE'];
			$productData['DISCOUNT_PRICE'] = 0;
			if ((float)$item['discount'] > 0)
			{
				$discountValue = (float)$item['discount'];
				if ($item['discountType'] === 'percent')
				{
					$discountValue = (float)$item['discount'] / 100 * $productData['BASE_PRICE'];
				}

				$productData['DISCOUNT_PRICE'] = ($discountValue < $productData['BASE_PRICE']) ? $discountValue : $productData['BASE_PRICE'];
				$productData['PRICE'] = max(0, ($productData['BASE_PRICE'] - $discountValue));
			}
		}
		if ($item['isCreatedProduct'] === 'Y')
		{
			$productData['NAME'] = $item['name'];
		}

		return $productData;
	}

	private function prepareNewProductFields($item)
	{
		$newItem = [
			'QUANTITY' => (float)$item['quantity'] > 0 ? (float)$item['quantity'] : 1,
			'PRODUCT_PROVIDER_CLASS' => '',
			'SORT' => (int)$item['sort'],
			'PRODUCT_ID' => $item['productId'],
		];

		if ($item['module'] === 'catalog')
		{
			$newItem['MODULE'] = 'catalog';
			if ((float)$item['basePrice'] > 0.0)
			{
				$newItem['PRODUCT_PROVIDER_CLASS'] = Catalog\Product\Basket::getDefaultProviderName();
			}
		}

		if ($item['module'] !== 'catalog' || $item['isCustomPrice'] === 'Y' || (float)$item['basePrice'] === 0.0)
		{
			if (empty($newItem['PRODUCT_ID']))
			{
				$newItem['PRODUCT_ID'] = (int)$item['sort'] + 1;
			}
			$newItem['BASE_PRICE'] = $item['basePrice'];
			$newItem['PRICE'] = $newItem['BASE_PRICE'];
			$newItem['NAME'] = $item['name'];
			$newItem['SORT'] = $item['sort'];
			$newItem['CUSTOM_PRICE'] = 'Y';
			if (!empty($item['measureName']))
			{
				$newItem['MEASURE_NAME'] = $item['measureName'];
			}
			if (!empty($item['measureCode']))
			{
				$newItem['MEASURE_CODE'] = $item['measureCode'];
			}
		}

		return $newItem;
	}

	public function createPaymentAction(array $basketItems = array(), array $options = [])
	{
		$dealId = null;
		$this->checkModules();
		if (!empty($this->getErrors()))
			return null;

		$formData = [
			'SITE_ID' => SITE_ID,
			'SHIPMENT' => [
				[
					'DELIVERY_ID' =>  Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId(),
					'ALLOW_DELIVERY' => 'Y',
					'DEDUCTED' => 'N'
				]
			]
		];

		foreach ($basketItems as $item)
		{
			if (empty($item['encodedFields']))
				continue;

			$formData['PRODUCT'][$item['code']]['FIELDS_VALUES'] = $item['encodedFields'];
		}

		$userId = $this->getUserId($options);
		$clientInfo = $this->getClientInfo($options);
		if(isset($clientInfo['DEAL_ID']) && $clientInfo['DEAL_ID'] > 0)
		{
			$dealId = $clientInfo['DEAL_ID'];
			unset($clientInfo['DEAL_ID']);
		}

		if ((int)$userId > 0)
		{
			$formData['USER_ID'] = (int)$userId;
		}

		$formData['CLIENT'] = $clientInfo;

		$personType = $this->getPersonTypeId($formData['CLIENT']);
		if ($personType > 0)
		{
			$formData['PERSON_TYPE_ID'] = $personType;
		}

		if (LandingManager::getInstance()->isSiteExists())
		{
			$connectedSiteId = LandingManager::getInstance()->getConnectedSiteId();
			$formData['TRADING_PLATFORM'] = Sale\TradingPlatform\Landing\Landing::getCodeBySiteId($connectedSiteId);
		}

		/** @var Crm\Order\Order $order */
		$order = $this->buildOrder($formData);
		if (!$order)
			return null;

		Crm\Order\DealBinding::enableBinding();

		if($dealId > 0 && method_exists($order->getDealBinding(), 'setDealId'))
		{
			$order->getDealBinding()->setDealId($dealId);
		}

		$basket = $order->getBasket();
		/** @var Sale\BasketItem $basketItem */
		foreach ($basket as &$basketItem)
		{
			if ($basketItem->isCustom())
			{
				$productId = $this->createProduct($basketItem->getFieldValues());
				if ((int)$productId > 0)
				{
					$basketItem->setFieldsNoDemand([
						'MODULE' => 'catalog',
						'PRODUCT_ID' => $productId,
						'PRODUCT_PROVIDER_CLASS' => Catalog\Product\Basket::getDefaultProviderName()
					]);
				}
			}
		}

		$paymentCollection = $order->getPaymentCollection();
		$payment = $paymentCollection->current();
		if (!$payment)
		{
			$payment = $paymentCollection->createItem();
		}

		$paySystemList = Sale\PaySystem\Manager::getListWithRestrictions($payment);

		$selectedPaySystem =
		$firstPaySystemInList = null;
		foreach ($paySystemList as $paySystem)
		{
			if ($paySystem['ACTION_FILE'] === 'cash')
			{
				$selectedPaySystem = $paySystem;
				break;
			}
			if (!$firstPaySystemInList && $paySystem['ACTION_FILE'] !== 'inner')
			{
				$firstPaySystemInList = $paySystem;
			}
		}

		if (!$selectedPaySystem)
		{
			$selectedPaySystem = $firstPaySystemInList;
		}

		$paymentFields = [
			'SUM' => $order->getPrice(),
			'CURRENCY'=> $order->getCurrency(),
		];
		if (!empty($selectedPaySystem))
		{
			$paymentFields['PAY_SYSTEM_ID'] = $selectedPaySystem['ID'];
			$paymentFields['PAY_SYSTEM_NAME'] = $selectedPaySystem['NAME'];
		}
		$payment->setFields($paymentFields);

		$resultSaving = $order->save();
		if ($resultSaving->isSuccess())
		{
			Bitrix24Manager::getInstance()->increasePaymentsCount();
			$data = [
				'order' => [
					'number' => $order->getField('ACCOUNT_NUMBER'),
					'id' => $order->getId(),
				]
			];

			if($options['dialogId'])
			{
				$result = ImOpenLinesManager::getInstance()->sendOrderNotify($order, $options['dialogId']);
				if(!$result->isSuccess())
				{
					$this->addErrors($result->getErrors());
				}
				if(!isset($options['skipPublicMessage']) || $options['skipPublicMessage'] == 'n')
				{
					$result = ImOpenLinesManager::getInstance()->sendOrderMessage($order, $options['dialogId']);
					if(!$result->isSuccess())
					{
						$this->addErrors($result->getErrors());
					}
				}
			}
			else
			{
				$orderPreviewData = ImOpenLinesManager::getInstance()->getOrderPreviewData($order);
				$orderPublicUrl = ImOpenLinesManager::getInstance()->getPublicUrlInfoForOrder($order);
				$data['order']['title'] = $orderPreviewData['title'];
				$data['order']['url'] = $orderPublicUrl['url'];
			}

			$resultSaving->setData($data);
		}
		else
		{
			$this->addErrors($resultSaving->getErrors());
		}

		return $resultSaving->getData();
	}

	/**
	 * @param array $options
	 * @return false|int
	 */
	protected function getUserId(array $options)
	{
		if (!empty($options['sessionId']))
		{
			return ImOpenLinesManager::getInstance()->setSessionId($options['sessionId'])->getUserId();
		}
		else
		{
			return (int)\CSaleUser::GetAnonymousUserID();
		}
	}

	/**
	 * @param array $options
	 * @return array
	 */
	public function getClientInfo(array $options)
	{
		$clientInfo = [];

		if (!empty($options['sessionId']))
		{
			$clientInfo = ImOpenLinesManager::getInstance()->setSessionId($options['sessionId'])->getClientInfo();
		}
		elseif(!empty($options['ownerTypeId']) && !empty($options['ownerId']))
		{
			$clientInfo = CrmManager::getInstance()->getClientInfo($options['ownerTypeId'], $options['ownerId']);
		}

		return $clientInfo;
	}

	private function createProduct(array $fields)
	{
		if (empty($fields['CURRENCY']) || empty($fields['PRICE']))
			return null;

		$elementObject = new \CIBlockElement();

		$catalogIblockId = Option::get('crm', 'default_product_catalog_id');
		if (!$catalogIblockId)
			return null;

		$productId = $elementObject->Add([
			'NAME' => $fields['NAME'],
			'ACTIVE' => 'Y',
			'IBLOCK_ID' => $catalogIblockId
		]);

		if ((int)$productId <= 0)
			return null;

		$addFields = [
			'ID' => $productId,
			'QUANTITY_TRACE' => \Bitrix\Catalog\ProductTable::STATUS_DEFAULT,
			'CAN_BUY_ZERO' => \Bitrix\Catalog\ProductTable::STATUS_DEFAULT,
			'WEIGHT' => 0,
		];

		if (!empty($fields['MEASURE_CODE']))
		{
			$measureRaw = Catalog\MeasureTable::getList(array(
				'select' => array('ID'),
				'filter' => ['CODE' => $fields['MEASURE_CODE']],
				'limit' => 1
			));

			if ($measure = $measureRaw->fetch())
			{
				$addFields['MEASURE'] = $measure['ID'];
			}
		}

		if (
			Option::get('catalog', 'default_quantity_trace') === 'Y'
			&& Option::get('catalog', 'default_can_buy_zero') !== 'Y'
		)
		{
			$addFields['QUANTITY'] = $fields['QUANTITY'];
		}

		$r = Catalog\Model\Product::add($addFields);
		if (!$r->isSuccess())
			return null;

		\Bitrix\Catalog\MeasureRatioTable::add(array(
			'PRODUCT_ID' => $productId,
			'RATIO' => 1
		));

		$priceBaseGroup = \CCatalogGroup::GetBaseGroup();
		$r = Catalog\Model\Price::add([
			'PRODUCT_ID' => $productId,
			'CATALOG_GROUP_ID' => $priceBaseGroup['ID'],
			'CURRENCY' => $fields['CURRENCY'],
			'PRICE' => $fields['PRICE'],
		]);

		if (!$r->isSuccess())
			return null;

		return $productId;
	}

	private function getPersonTypeId($clientInfo)
	{
		Main\Loader::includeModule('sale');

		$searchCode = 'CRM_CONTACT';
		$businessValueDomain = Sale\BusinessValue::INDIVIDUAL_DOMAIN;
		if (!empty($clientInfo['COMPANY']))
		{
			$searchCode = 'CRM_COMPANY';
			$businessValueDomain = Sale\BusinessValue::ENTITY_DOMAIN;
		}

		$personTypeRaw = Sale\PersonType::getList([
			'filter' => [
				'CODE' => $searchCode,
				'ENTITY_REGISTRY_TYPE' => 'ORDER'
			],
			'select' => ['ID'],
			'limit' => 1
		]);
		if ($personType = $personTypeRaw->fetch())
		{
			return $personType['ID'];
		}

		$personTypeRaw = Sale\PersonType::getList([
			'filter' => [
				'ENTITY_REGISTRY_TYPE' => 'ORDER',
				'BIZVAL.DOMAIN' => $businessValueDomain,
			],
			'select' => ['ID'],
			'runtime' => array(
				new \Bitrix\Main\Entity\ReferenceField(
					'BIZVAL',
					'Bitrix\Sale\Internals\BusinessValuePersonDomainTable',
					array(
						'=this.ID' => 'ref.PERSON_TYPE_ID'
					),
					array('join_type' => 'LEFT')
				),
			),
			'limit' => 1
		]);
		if ($personType = $personTypeRaw->fetch())
		{
			return $personType['ID'];
		}

		return null;
	}

	/**
	 * @param array $orderIds
	 * @param array $options
	 * @return array
	 * @throws Main\ArgumentNullException
	 * @throws Main\LoaderException
	 */
	public function sendOrdersAction(array $orderIds, array $options)
	{
		$sentOrders = [];
		$sessionId = $dialogId = false;
		if(Bitrix24Manager::getInstance()->isPaymentsLimitReached())
		{
			$this->addError(new Error('You have reached limit of payments for your tariff'));
			return null;
		}
		if(isset($options['sessionId']))
		{
			$sessionId = intval($options['sessionId']);
			ImOpenLinesManager::getInstance()->setSessionId($sessionId);
		}
		$dialogId = ImOpenLinesManager::getInstance()->getDialogId();
		if(!$dialogId)
		{
			$this->addError(new Error('Dialog not found'));
		}
		elseif(!$sessionId)
		{
			$this->addError(new Error('Session not found'));
		}
		elseif(Main\Loader::includeModule('sale'))
		{
			foreach($orderIds as $orderId)
			{
				$order = Sale\Order::load($orderId);
				if(!$order)
				{
					$this->addError(new Error('Order not found'));
				}
				elseif(ImOpenLinesManager::getInstance()->getUserId() != $order->getUserId())
				{
					$this->addError(new Error('Wrong user'));
				}
				else
				{
					$sendResult = ImOpenLinesManager::getInstance()->sendOrderMessage($order, $dialogId);
					if(!$sendResult->isSuccess())
					{
						$this->addErrors($sendResult->getErrors());
					}
					else
					{
						$sentOrders[] = $order->getField('ACCOUNT_NUMBER');
					}
				}
			}
		}

		return ['orders' => $sentOrders];
	}

	/**
	 * @param $sessionId
	 * @return int
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function getActiveOrdersCountAction($sessionId)
	{
		$count = 0;
		if(ImOpenLinesManager::getInstance()->isEnabled() && SaleManager::getInstance()->isEnabled() && CrmManager::getInstance()->isEnabled())
		{
			$userId = ImOpenLinesManager::getInstance()->setSessionId($sessionId)->getUserId();
			if($userId > 0)
			{
				$count = Sale\Internals\OrderTable::getCount([
					'=USER_ID' => $userId,
					'=STATUS_ID' => Crm\Order\OrderStatus::getSemanticProcessStatuses(),
				]);
			}
		}

		return $count;
	}

	/**
	 * @param array $catalogProducts
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getProductAvailableQuantity(array $catalogProducts)
	{
		$catalogProductQuantityMap = [];
		if (!empty($catalogProducts))
		{
			$catalogInfo = Catalog\ProductTable::getList(array(
				'select' => ['ID', 'QUANTITY'],
				'filter' => ['@ID' => $catalogProducts]
			));

			while ($product = $catalogInfo->fetch())
			{
				$catalogProductQuantityMap[$product['ID']] = $product['QUANTITY'];
			}
		}
		
		return $catalogProductQuantityMap;
	}
}