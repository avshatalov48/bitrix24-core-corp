<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Location\Entity\Address;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Crm;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\ImOpenLinesManager;
use Bitrix\SalesCenter\Integration\LocationManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\Salescenter;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Sale\Delivery;
use Bitrix\ImOpenLines;
use Bitrix\SalesCenter\Builder\Converter;

define('SALESCENTER_RECEIVE_PAYMENT_APP_AREA', true);

Loc::loadMessages(__FILE__);

class Order extends Base
{
	public function configureActions()
	{
		return [
			'searchProduct' => ['class' => SearchProductAction::class]
		];
	}

	protected function processBeforeAction(Action $action)
	{
		if (!$this->checkModules())
		{
			return false;
		}

		\CFile::DisableJSFunction(true);

		return parent::processBeforeAction($action);
	}

	private function checkModules()
	{
		if (!Main\Loader::includeModule('crm'))
		{
			$this->addError(new Main\Error('module "crm" is not installed.'));
			return false;
		}
		if (!Main\Loader::includeModule('catalog'))
		{
			$this->addError(new Main\Error('module "catalog" is not installed.'));
			return false;
		}
		if (!Main\Loader::includeModule('sale'))
		{
			$this->addError(new Main\Error('module "sale" is not installed.'));
			return false;
		}

		return true;
	}

	protected function processBasketItems(array $basketItems)
	{
		$result = [];

		foreach ($basketItems as $item)
		{
			if (
				!isset($item['code'])
				|| mb_strpos($item['code'], 'n') !== false
			)
			{
				$item['code'] = 'n'.(count($result) + 1);
			}

			$result[] = $item;
		}

		return $result;
	}

	public function refreshBasketAction($orderId, array $basketItems = [])
	{
		$basketItems = $this->processBasketItems($basketItems);

		$order = $this->buildOrder([
			'orderId' => $orderId,
			'basketItems' => $basketItems
		 ]);

		if ($order === null)
		{
			return ['items' => $basketItems];
		}

		$discountSum = 0;
		$baseSum = 0;
		$price = 0;
		$vatSum = 0;
		foreach ($basketItems as $item)
		{
			$basketItem = $order->getBasket()->getItemByXmlId($item['innerId']);
			if ($basketItem === null)
			{
				continue;
			}

			if ($basketItem->getBasePrice() !== $basketItem->getPrice() && empty($basketItem->getDiscountPrice()))
			{
				$discountSum += ($basketItem->getBasePrice() - $basketItem->getPrice()) * $item['quantity'];
			}
			else
			{
				$discountSum += $basketItem->getDiscountPrice() * $item['quantity'];
			}

			$baseSum += $basketItem->getBasePrice() * $item['quantity'];
			$price += $basketItem->getPrice() * $item['quantity'];

			if ($basketItem->isVatInPrice())
			{
				$vatSum += Sale\PriceMaths::roundPrecision(
					$basketItem->getPrice()
					* $item['quantity']
					* $basketItem->getVatRate()
					/ (
						$basketItem->getVatRate() + 1
					)
				);
			}
			else
			{
				$vatSum += Sale\PriceMaths::roundPrecision(
					$basketItem->getPrice()
					* $item['quantity']
					* $basketItem->getVatRate()
				);
			}
		}

		return [
			'items' => $this->fillResultBasket($basketItems, $order),
			'total' => [
				'discount' => $discountSum,
				'result' => $price,
				'taxSum' => $vatSum,
				'sum' => $baseSum,
			]
		];
	}

	protected function prepareParamsForBuilder(array $params, $scenario = null) : array
	{
		$basketItems = (isset($params['basketItems']) && is_array($params['basketItems']))
			? $params['basketItems']
			: []
		;

		$propertyValues = (isset($params['propertyValues']) && is_array($params['propertyValues']))
			? $params['propertyValues']
			: []
		;
		$formData = $this->obtainOrderFields($params);

		$formData['PRODUCT'] = Converter\CatalogJSProductForm::convertToBuilderFormat($basketItems);

		$formData['PROPERTIES'] = $this->obtainPropertiesFields($propertyValues);

		if ($this->needObtainShipmentFields($params))
		{
			$formData['SHIPMENT'][] = $this->obtainShipmentFields($params, $formData['PRODUCT']);
		}

		if ($scenario !== Salescenter\Builder\SettingsContainer::BUILDER_SCENARIO_SHIPMENT)
		{
			$formData['PAYMENT'][] = $this->obtainPaymentFields($formData);
		}

		return $formData;
	}

	/**
	 * @param array $basketItems
	 * @param array $options
	 * @param int $deliveryServiceId
	 * @param array $shipmentPropValues
	 * @param array $deliveryRelatedServiceValues
	 * @param int $deliveryResponsibleId
	 * @return array[]|null
	 */
	public function refreshDeliveryAction(
		array $basketItems = [],
		array $options = [],
		int $deliveryServiceId = 0,
		array $shipmentPropValues = [],
		array $deliveryRelatedServiceValues = [],
		int $deliveryResponsibleId = 0
	)
	{
		$basketItems = $this->processBasketItems($basketItems);

		$options['basketItems'] = $basketItems;
		$options['deliveryId'] = $deliveryServiceId;
		$options['deliveryExtraServicesValues'] = $deliveryRelatedServiceValues;
		$options['deliveryResponsibleId'] = $deliveryResponsibleId;
		$options['shipmentPropValues'] = $shipmentPropValues;

		$order = $this->buildOrder(
			$options,
			[
				'orderErrorsFilter' => ['DELIVERY_CALCULATION'],
				'builderScenario' => Salescenter\Builder\SettingsContainer::BUILDER_SCENARIO_SHIPMENT,
			]
		);
		$shipment = null;
		if ($order)
		{
			$shipment = $this->findNewShipment($order);
		}

		if (!$shipment)
		{
			if (!$this->getErrors())
			{
				$this->addError(new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_BUILD_ERROR')));
			}

			return null;
		}

		return [
			'deliveryPrice' => $shipment->getPrice(),
		];
	}

	/**
	 * @param array $basketItems
	 * @param array $options
	 * @param int $deliveryServiceId
	 * @param array $shipmentPropValues
	 * @param array $deliveryRelatedServiceValues
	 * @param int $deliveryResponsibleId
	 * @return array[]|null
	 */
	public function getCompatibleDeliverySystemsAction(
		array $basketItems = [],
		array $options = [],
		int $deliveryServiceId = 0,
		array $shipmentPropValues = [],
		array $deliveryRelatedServiceValues = [],
		int $deliveryResponsibleId = 0
	)
	{
		$basketItems = $this->processBasketItems($basketItems);

		$options['basketItems'] = $basketItems;
		$options['deliveryId'] = $deliveryServiceId;
		$options['deliveryExtraServicesValues'] = $deliveryRelatedServiceValues;
		$options['deliveryResponsibleId'] = $deliveryResponsibleId;
		$options['shipmentPropValues'] = $shipmentPropValues;

		$order = $this->buildOrder(
			$options,
			[
				'builderScenario' => Salescenter\Builder\SettingsContainer::BUILDER_SCENARIO_SHIPMENT,
			]
		);

		$shipment = null;
		if ($order)
		{
			$shipmentCollection = $order->getShipmentCollection()->getNotSystemItems();
			foreach ($shipmentCollection as $shipment)
			{
				break;
			}
		}

		$availableServices = [];
		$activeServices = Delivery\Services\Manager::getActiveList();
		foreach ($activeServices as $service)
		{
			$isCompatible = false;
			$compatibleExtraServiceIds = null;

			if (is_null($shipment))
			{
				$isCompatible = true;
			}
			else
			{
				$serviceObject = Delivery\Services\Manager::getObjectById($service['ID']);

				if ($serviceObject && $serviceObject->isCompatible($shipment))
				{
					$isCompatible = true;
					$compatibleExtraServiceIds = $serviceObject->getCompatibleExtraServiceIds($shipment);
				}
			}

			if ($isCompatible)
			{
				$availableServices[$service['ID']] = $compatibleExtraServiceIds;
			}
		}

		return [
			'availableServices' => $availableServices
		];
	}

	/**
	 * @param array $data
	 * @param array $options
	 * @return Sale\Order|null
	 */
	private function buildOrder(
		array $data,
		array $options = []
	): ?Sale\Order
	{
		$scenario = $this->getBuilderScenario($options);

		$formData = $this->prepareParamsForBuilder($data, $scenario);

		$builder = SalesCenter\Builder\Manager::getBuilder($scenario);

		try
		{
			$builder->build($formData);
			$order = $builder->getOrder();
		}
		catch (Sale\Helpers\Order\Builder\BuildingException $exception)
		{
			$order = null;
		}

		$errorsFilter = $options['orderErrorsFilter'] ?? null;
		if (is_null($errorsFilter))
		{
			return $order;
		}

		$filteredErrors = [];

		$errors = $builder->getErrorsContainer()->getErrors();
		foreach ($errors as $error)
		{
			if (empty($errorsFilter) || in_array($error->getCode(), $errorsFilter, true))
			{
				$filteredErrors[] = $error;
			}
		}

		$this->addErrors($filteredErrors);

		return $order;
	}

	protected function getBuilderScenario(array $options) :? string
	{
		if (isset($options['builderScenario']))
		{
			return $options['builderScenario'];
		}

		if ($this->needObtainShipmentFields($options))
		{
			return null;
		}

		return Salescenter\Builder\SettingsContainer::BUILDER_SCENARIO_PAYMENT;
	}

	/**
	 * @param $dealId
	 * @param $products
	 * @param $order
	 * @throws Main\ArgumentException
	 */
	protected function syncOrderProductsWithDeal($dealId, $products, $order)
	{
		$result = [];

		if (\CCrmSaleHelper::isWithOrdersMode())
		{
			if ($this->getCountBindingOrders($dealId) > 1)
			{
				$result = \CCrmDeal::LoadProductRows($dealId);
			}

			$sort = $this->getMaxProductDealSort($result);

			foreach ($products as $product)
			{
				$sort += 10;
				$item = [
					'PRODUCT_ID' => $product['skuId'] ?? $product['productId'],
					'PRODUCT_NAME' => $product['name'],
					'PRICE' => $product['price'],
					'PRICE_ACCOUNT' => $product['price'],
					'PRICE_EXCLUSIVE' => $product['basePrice'],
					'PRICE_NETTO' => $product['basePrice'],
					'PRICE_BRUTTO' => $product['price'],
					'QUANTITY' => $product['quantity'],
					'MEASURE_CODE' => $product['measureCode'],
					'MEASURE_NAME' => $product['measureName'],
					'TAX_RATE' => $product['taxRate'],
					'TAX_INCLUDED' => $product['taxIncluded'],
					'SORT' => $sort,
				];

				if (!empty($product['discount']))
				{
					$item['DISCOUNT_TYPE_ID'] =
						(int)$product['discountType'] === \Bitrix\Crm\Discount::MONETARY
							? \Bitrix\Crm\Discount::MONETARY
							: \Bitrix\Crm\Discount::PERCENTAGE
					;
					$item['DISCOUNT_RATE'] = $product['discountRate'];
					$item['DISCOUNT_SUM'] = $product['discount'];
				}

				$result[] = $item;
			}

			/**
			 * Delivery
			 */
			$hasActualDelivery = false;
			$emptyDeliveryServiceId = Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
			$deliverySystemIds = $order->getDeliveryIdList();
			foreach ($deliverySystemIds as $deliverySystemId)
			{
				if ($deliverySystemId != $emptyDeliveryServiceId)
				{
					$hasActualDelivery = true;
					break;
				}
			}

			if ($hasActualDelivery)
			{
				$deliveryPrice = $order->getDeliveryPrice();

				$sort += 10;

				$result[] = [
					'PRODUCT_NAME' => Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_DELIVERY'),
					'PRICE' => $deliveryPrice,
					'PRICE_ACCOUNT' => $deliveryPrice,
					'PRICE_EXCLUSIVE' => $deliveryPrice,
					'PRICE_NETTO' => $deliveryPrice,
					'PRICE_BRUTTO' => $deliveryPrice,
					'QUANTITY' => 1,
					'SORT' => $sort,
				];
			}
		}
		else
		{
			$result = \CCrmDeal::LoadProductRows($dealId);

			foreach ($products as $product)
			{
				$productId = $product['skuId'] ?? $product['productId'];

				if (
					!empty($product['additionalFields']['originBasketId'])
					&& $product['additionalFields']['originBasketId'] !== $product['code']
				)
				{
					$basketItem = $order->getBasket()->getItemByBasketCode($product['additionalFields']['originBasketId']);

					if ($basketItem)
					{
						$index = $this->searchProduct($result, $basketItem->getProductId());
						if ($index !== false)
						{
							$result[$index]['QUANTITY'] = $basketItem->getQuantity();
						}
					}
				}
				elseif (
					!empty($product['additionalFields']['originProductId'])
					&& $product['additionalFields']['originProductId'] !== $productId
				)
				{
					$index = $this->searchProduct($result, $product['additionalFields']['originProductId']);
					if ($index !== false)
					{
						$result[$index]['PRODUCT_ID'] = $productId;
						if ($result[$index]['QUANTITY'] < $product['quantity'])
						{
							$result[$index]['QUANTITY'] = $product['quantity'];
						}

						continue;
					}
				}
				else
				{
					$index = $this->searchProduct($result, $productId);
					if ($index !== false)
					{
						$basketItem = $order->getBasket()->getItemByXmlId($product['innerId']);
						if ($basketItem)
						{
							if ($result[$index]['QUANTITY'] < $basketItem->getQuantity())
							{
								$result[$index]['QUANTITY'] = $basketItem->getQuantity();
							}

							$result[$index]['PRICE'] = $product['price'];
							$result[$index]['PRICE_EXCLUSIVE'] = $product['basePrice'];
							$result[$index]['PRICE_ACCOUNT'] = $product['price'];
							$result[$index]['PRICE_NETTO'] = $product['basePrice'];
							$result[$index]['PRICE_BRUTTO'] = $product['price'];

							if (!empty($product['discount']))
							{
								$result[$index]['DISCOUNT_TYPE_ID'] =
									(int)$product['discountType'] === Crm\Discount::MONETARY
										? Crm\Discount::MONETARY
										: Crm\Discount::PERCENTAGE
								;
								$result[$index]['DISCOUNT_RATE'] = $product['discountRate'];
								$result[$index]['DISCOUNT_SUM'] = $product['discount'];
							}

							continue;
						}
					}
				}

				$item = [
					'PRODUCT_ID' => $productId,
					'PRODUCT_NAME' => $product['name'],
					'PRICE' => $product['price'],
					'PRICE_ACCOUNT' => $product['price'],
					'PRICE_EXCLUSIVE' => $product['basePrice'],
					'PRICE_NETTO' => $product['basePrice'],
					'PRICE_BRUTTO' => $product['price'],
					'QUANTITY' => $product['quantity'],
					'MEASURE_CODE' => $product['measureCode'],
					'MEASURE_NAME' => $product['measureName'],
					'TAX_RATE' => $product['taxRate'],
					'TAX_INCLUDED' => $product['taxIncluded'],
				];

				if (!empty($product['discount']))
				{
					$item['DISCOUNT_TYPE_ID'] =
						(int)$product['discountType'] === Crm\Discount::MONETARY
							? Crm\Discount::MONETARY
							: Crm\Discount::PERCENTAGE
					;
					$item['DISCOUNT_RATE'] = $product['discountRate'];
					$item['DISCOUNT_SUM'] = $product['discount'];
				}

				$result[] = $item;
			}
		}

		if ($result)
		{
			\CCrmDeal::SaveProductRows($dealId, $result);
		}
	}

	protected function getMaxProductDealSort($products)
	{
		$sort = 0;
		foreach ($products as $product)
		{
			if ($product['SORT'] > $sort)
			{
				$sort = $product['SORT'];
			}
		}

		return $sort;
	}

	protected function getCountBindingOrders($dealId)
	{
		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);

		/** @var Crm\Order\DealBinding $dealBinding */
		$dealBinding = $registry->get(ENTITY_CRM_ORDER_DEAL_BINDING);

		return $dealBinding::getList([
			'select' => ['ORDER_ID'],
			'filter' => [
				'=DEAL_ID' => $dealId
			],
			'count_total' => true
		])->getCount();
	}

	private function searchProduct(array $productList, int $productId)
	{
		if ($productId === 0)
		{
			return false;
		}

		foreach ($productList as $index => $item)
		{
			if ($productId === (int)$item['PRODUCT_ID'])
			{
				return $index;
			}
		}

		return false;
	}

	protected function getBasketItemByProductId(Crm\Order\Order $order, $productId)
	{
		foreach ($order->getBasket() as $basketItem)
		{
			if ((int)$basketItem->getField('PRODUCT_ID') === (int)$productId)
			{
				return $basketItem;
			}
		}

		return null;
	}

	protected function getBasketByOrderId($orderId)
	{
		if ($orderId > 0)
		{
			$order = Crm\Order\Order::load($orderId);

			return $order->getBasket();
		}

		return null;
	}

	/**
	 * @param array $formBasket
	 * @param Sale\Order $order
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private function fillResultBasket(array $formBasket, Sale\Order $order): array
	{
		$basket = $order->getBasket();
		$discount = $order->getDiscount();
		$discountResult = $discount->getApplyResult(true);
		$discountBasket = $discountResult['RESULT']['BASKET'];
		$discountList = $discountResult['DISCOUNT_LIST'];

		$productIds = array_map(static function ($basketItem) {
			return $basketItem->getProductId();
		}, $basket->getBasketItems());

		$measureRatios = \Bitrix\Catalog\MeasureRatioTable::getCurrentRatio($productIds);

		$resultBasket = [];

		foreach ($formBasket as $index => $item)
		{
			$basketItem = $basket->getItemByXmlId($item['innerId']);
			if (!$basketItem)
			{
				$resultBasket[$item['sort']] = $item;
				continue;
			}

			$errors = [];
			if (!$basketItem->getField('NAME'))
			{
				$errors[] = 'SALE_BASKET_ITEM_NAME';
			}

			$code = $basketItem->getBasketCode();
			$sort = $basketItem->getField('SORT');
			$productId = $basketItem->getProductId();

			$preparedItem = [
				'code' => $code,
				'productId' => $productId,
				'sort' => $sort,
				'name' => $basketItem->getField('NAME'),
				'basePrice' => Sale\PriceMaths::roundPrecision($basketItem->getBasePrice()),
				'price' => Sale\PriceMaths::roundPrecision($basketItem->getPrice()),
				'priceExclusive' => Sale\PriceMaths::roundPrecision($basketItem->getPrice()),
				'quantity' => $item['quantity'],
				'module' => $basketItem->getField('MODULE'),
				'formattedPrice' => SaleFormatCurrency($basketItem->getPrice(), $order->getCurrency(), true),
				'encodedFields' => Main\Web\Json::encode($basketItem->getFieldValues()),
				'errors' => $errors,
				'discountInfos' => [],
				'measureCode' => $basketItem->getField('MEASURE_CODE'),
				'measureName' => $basketItem->getField('MEASURE_NAME'),
				'measureRatio' => (float)($measureRatios[(int)$productId]),
			];

			$preparedItem = array_merge($item, $preparedItem);

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

			if (
				!isset($preparedItem['discountType'])
				&& $basketItem->isCustomPrice()
				&& (int)$preparedItem['discount'] === 0
			)
			{
				$preparedItem['discountType'] = \Bitrix\Crm\Discount::MONETARY;
			}

			if (!empty($basketItem->getDiscountPrice()))
			{
				if (empty($preparedItem['discountType']))
				{
					$preparedItem['discountType'] = \Bitrix\Crm\Discount::PERCENTAGE;
				}

				if (empty($preparedItem['showDiscount']))
				{
					$preparedItem['showDiscount'] = 'Y';
				}

				$preparedItem['discount'] = (float)$basketItem->getDiscountPrice();
				$preparedItem['discountRate'] = roundEx($basketItem->getDiscountPrice() / $basketItem->getBasePrice() * 100, 2);
			}
			else
			{
				$preparedItem['discount'] = 0;
			}

			$key = $preparedItem['innerId'] ?? $preparedItem['code'];
			$resultBasket[$key] = $preparedItem;
		}

		sort($resultBasket);

		return $resultBasket;
	}

	public function resendPaymentAction($orderId, $paymentId, $shipmentId, array $options = [])
	{
		if ($options['sendingMethod'] === 'sms')
		{
			$order = Crm\Order\Order::load($orderId);

			$payment = null;
			if ($paymentId)
			{
				/** @var Crm\Order\Payment $payment */
				$payment = $order->getPaymentCollection()->getItemById($paymentId);
			}

			$shipment = null;
			if ($shipmentId)
			{
				/** @var Crm\Order\Shipment $shipment */
				$shipment = $order->getShipmentCollection()->getItemById($shipmentId);
			}

			$isSent = CrmManager::getInstance()->sendPaymentBySms($payment, $options['sendingMethodDesc'], $shipment);
			if ($isSent === false)
			{
				$this->addError(
					new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_SEND_SMS_ERROR'))
				);
			}
		}
	}

	/**
	 * @param array $basketItems
	 * @param array $options
	 * @return array|null
	 */
	public function createPaymentAction(array $basketItems = array(), array $options = [])
	{
		if (Bitrix24Manager::getInstance()->isPaymentsLimitReached())
		{
			$this->addError(
				new Main\Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_PAYMENTS_LIMIT_REACHED'))
			);

			return [];
		}

		if ((int)$options['orderId'] <= 0 && CrmManager::getInstance()->isOrderLimitReached())
		{
			$this->addError(
				new Main\Error('You have reached the order limit for your plan')
			);

			return [];
		}

		$basketItems = $this->processBasketItems($basketItems);

		$options['basketItems'] = $basketItems;

		/** @var Crm\Order\Order $order */
		$order = $this->buildOrder($options);
		if ($order === null)
		{
			return [];
		}

		$shipment = $this->findNewShipment($order);
		$payment = $this->findNewPayment($order);

		$result = $order->save();
		if ($result->isSuccess())
		{
			$dealId = $order->getDealBinding()->getDealId();
			if ($dealId)
			{
				if (!empty($options['sessionId']) && (int)$options['ownerId'] <= 0)
				{
					$this->onAfterDealAdd($dealId, $options['sessionId']);
				}

				$this->syncOrderProductsWithDeal($dealId, $basketItems, $order);

				if (isset($options['stageOnOrderPaid']))
				{
					CrmManager::getInstance()->saveTriggerOnOrderPaid(
						$dealId,
						$options['stageOnOrderPaid']
					);
				}

				if (isset($options['stageOnDeliveryFinished']))
				{
					CrmManager::getInstance()->saveTriggerOnDeliveryFinished(
						$dealId,
						$options['stageOnDeliveryFinished']
					);
				}

				$dealPrimaryContactId = $this->getDealPrimaryContactId($dealId);
				if ($shipment && $dealPrimaryContactId)
				{
					$this->tryToFillContactDeliveryAddress($dealPrimaryContactId, $shipment->getId());
				}
			}

			if ($shipment)
			{
				$this->saveDeliveryAddressFrom($shipment->getId());
			}

			Bitrix24Manager::getInstance()->increasePaymentsCount();
			$data = [
				'order' => [
					'number' => $order->getField('ACCOUNT_NUMBER'),
					'id' => $order->getId(),
					'paymentId' => $payment ? $payment->getId() : null,
					'shipmentId' => $shipment ? $shipment->getId() : null,
				],
				'deal' => $this->getDealData((int)$dealId)
			];

			if ($options['sendingMethod'] === 'sms')
			{
				if ($payment)
				{
					$isSent = CrmManager::getInstance()->sendPaymentBySms($payment, $options['sendingMethodDesc'], $shipment);
					if (!$isSent)
					{
						$this->addError(
							new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_SEND_SMS_ERROR'))
						);
					}
				}
			}
			elseif ($options['dialogId'])
			{
				if ($payment)
				{
					$r = new Main\Result();
					if ($dealId && (int)$options['ownerId'] <= 0)
					{
						$r = ImOpenLinesManager::getInstance()->sendDealNotify($dealId, $options['dialogId']);
					}

					if ($r->isSuccess())
					{
						$r = ImOpenLinesManager::getInstance()->sendPaymentNotify($payment, $options['dialogId']);
					}
				}
				else
				{
					$r = ImOpenLinesManager::getInstance()->sendOrderNotify($order, $options['dialogId']);
				}

				if (!$r->isSuccess())
				{
					$this->addErrors($r->getErrors());
				}

				if (!isset($options['skipPublicMessage']) || $options['skipPublicMessage'] === 'n')
				{
					$paymentData = [];
					$paySystemService = Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());

					if ($options['connector'] === 'imessage'
						&& SaleManager::getInstance()->isApplePayPayment($paySystemService->getFieldsValues())
					)
					{
						$request = Main\Context::getCurrent()->getRequest();
						$request->set(array_merge($request->toArray(), [
							"action" => "getIMessagePaymentAction",
						]));

						$initiatePayResult = $paySystemService->initiatePay(
							$payment,
							$request,
							Sale\PaySystem\BaseServiceHandler::STRING
						);
						if ($initiatePayResult->isSuccess())
						{
							$paymentData = $initiatePayResult->getData();
						}
						else
						{
							$this->addErrors($initiatePayResult->getErrors());
						}
					}

					$result = ImOpenLinesManager::getInstance()->sendPaymentMessage($payment, $options['dialogId'], $paymentData);
					if (!$result->isSuccess())
					{
						$this->addErrors($result->getErrors());
					}
				}
			}
			else
			{
				if ($payment)
				{
					$publicUrl = ImOpenLinesManager::getInstance()->getPublicUrlInfoForPayment($payment);

					if ($options['context'] === 'sms')
					{
						$smsTemplate = CrmManager::getInstance()->getSmsTemplate();
						$smsTitle = str_replace('#LINK#', $publicUrl['url'], $smsTemplate);
						$previewData = [
							'title' => $smsTitle,
						];
					}
					else
					{
						$previewData = ImOpenLinesManager::getInstance()->getPaymentPreviewData($payment);
					}
				}
				else
				{
					$previewData = ImOpenLinesManager::getInstance()->getOrderPreviewData($order);
					$publicUrl = ImOpenLinesManager::getInstance()->getPublicUrlInfoForOrder($order);
				}

				$data['order']['title'] = $previewData['title'];
				$data['order']['url'] = $publicUrl['url'];
			}

			return $data;
		}
		else
		{
			$this->addErrors($result->getErrors());
		}

		return [];
	}

	/**
	 * @param array $basketItems
	 * @param array $options
	 * @return array|null
	 */
	public function createShipmentAction(array $basketItems = array(), array $options = [])
	{
		if ((int)$options['orderId'] <= 0 && CrmManager::getInstance()->isOrderLimitReached())
		{
			$this->addError(
				new Main\Error('You have reached the order limit for your plan')
			);

			return [];
		}

		$basketItems = $this->processBasketItems($basketItems);

		$options['basketItems'] = $basketItems;
		$options['withoutPayment'] = true;

		/** @var Crm\Order\Order $order */
		$order = $this->buildOrder(
			$options,
			[
				'builderScenario' => Salescenter\Builder\SettingsContainer::BUILDER_SCENARIO_SHIPMENT,
			]
		);
		if ($order === null)
		{
			return [];
		}

		$shipment = $this->findNewShipment($order);

		$result = $order->save();
		if ($result->isSuccess())
		{
			$dealId = $order->getDealBinding()->getDealId();

			if ($shipment)
			{
				if ($dealId)
				{
					$dealPrimaryContactId = $this->getDealPrimaryContactId($dealId);
					if ($dealPrimaryContactId)
					{
						$this->tryToFillContactDeliveryAddress($dealPrimaryContactId, $shipment->getId());
					}

					$this->syncOrderProductsWithDeal($dealId, $basketItems, $order);
				}

				$this->saveDeliveryAddressFrom($shipment->getId());
			}

			return [
				'order' => [
					'number' => $order->getField('ACCOUNT_NUMBER'),
					'id' => $order->getId()
				],
				'deal' => $this->getDealData((int)$dealId)
			];
		}
		else
		{
			$this->addErrors($result->getErrors());
		}

		return [];
	}

	/**
	 * @param Crm\Order\Order $order
	 * @return Crm\Order\Shipment|null
	 */
	private function findNewShipment(Crm\Order\Order $order): ?Crm\Order\Shipment
	{
		foreach ($order->getShipmentCollection()->getNotSystemItems() as $shipment)
		{
			if ($shipment->getId() === 0)
			{
				return $shipment;
			}
		}

		return null;
	}

	/**
	 * @param Crm\Order\Order $order
	 * @return Crm\Order\Payment|null
	 */
	private function findNewPayment(Crm\Order\Order $order): ?Crm\Order\Payment
	{
		foreach ($order->getPaymentCollection() as $payment)
		{
			if ($payment->getId() === 0)
			{
				return $payment;
			}
		}

		return null;
	}

	protected function obtainOrderFields($options)
	{
		$result = [
			'ID' => (int)$options['orderId'] ?? 0,
			'SITE_ID' => SITE_ID,
			'CONNECTOR' => $options['connector'],
			'SHIPMENT' => [],
			'PAYMENT' => [],
		];

		if (!empty($options['sessionId']))
		{
			$result['USER_ID'] = ImOpenLinesManager::getInstance()->setSessionId($options['sessionId'])->getUserId();
		}

		$clientInfo = $this->getClientInfo($options);
		if (isset($clientInfo['DEAL_ID']) && $clientInfo['DEAL_ID'] > 0)
		{
			$result['DEAL_ID'] = $clientInfo['DEAL_ID'];
			unset($clientInfo['DEAL_ID']);
		}

		$result['CLIENT'] = $clientInfo;

		if ($result['ID'] === 0)
		{
			if (isset($options['context']))
			{
				$platformCode = '';

				if ($options['context'] === 'deal')
				{
					$platformCode = Crm\Order\TradingPlatform\Deal::TRADING_PLATFORM_CODE;
				}
				elseif ($options['context'] === 'sms')
				{
					$platformCode = Crm\Order\TradingPlatform\Activity::TRADING_PLATFORM_CODE;
				}

				if ($platformCode)
				{
					$platform = Crm\Order\TradingPlatform\Deal::getInstanceByCode($platformCode);
					if ($platform->isInstalled())
					{
						$result['TRADING_PLATFORM'] = $platform->getId();
					}
				}
			}
		}

		return $result;
	}

	protected function obtainPaymentFields(array $data)
	{
		$result = [
			'PRODUCT' => []
		];

		$sum = 0;

		if (
			isset($data['PRODUCT'])
			&& is_array($data['PRODUCT'])
		)
		{
			foreach ($data['PRODUCT'] as $index => $item)
			{
				$sum += Sale\PriceMaths::roundPrecision($item['QUANTITY'] * $item['PRICE']);

				$result['PRODUCT'][] = [
					'BASKET_CODE' => $index,
					'QUANTITY' => $item['QUANTITY']
				];
			}
		}

		if (
			isset($data['SHIPMENT'])
			&& is_array($data['SHIPMENT'])
		)
		{
			foreach ($data['SHIPMENT'] as $index => $item)
			{
				$sum += $item['PRICE_DELIVERY'];

				$result['PRODUCT'][] = [
					'DELIVERY_ID' => $item['DELIVERY_ID'],
					'QUANTITY' => 1
				];
			}
		}

		$result['SUM'] = $sum;

		return $result;
	}

	protected function needObtainShipmentFields(array $params) : bool
	{
		$deliveryId = Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
		return
			isset($params['deliveryId'])
			&& $params['deliveryId'] > 0
			&& (int)$params['deliveryId'] !== $deliveryId
		;
	}

	protected function obtainShipmentFields(array $data, array $basketItems)
	{
		$result = [
			'ALLOW_DELIVERY' => 'Y',
			'RESPONSIBLE_ID' =>	$data['deliveryResponsibleId'] ?? 0,
		];

		if (!empty($data['deliveryId']))
		{
			$result['DELIVERY_ID'] = $data['deliveryId'];
		}
		else
		{
			$result['DELIVERY_ID'] = Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
		}

		if (!empty($data['deliveryExtraServicesValues']))
		{
			$extraServices = [];

			foreach ($data['deliveryExtraServicesValues'] as $deliveryRelatedServiceValue)
			{
				$extraServices[$deliveryRelatedServiceValue['id']] = $deliveryRelatedServiceValue['value'];
			}

			$result['EXTRA_SERVICES'] = $extraServices;
		}

		if (
			isset($data['deliveryPrice'])
			&& isset($data['expectedDeliveryPrice'])
			&& $data['deliveryPrice'] !== $data['expectedDeliveryPrice']
		)
		{
			$result['BASE_PRICE_DELIVERY'] = (float)$data['expectedDeliveryPrice'];
			$result['CUSTOM_PRICE_DELIVERY'] = 'Y';
		}

		$result['PRICE_DELIVERY'] = (float)$data['deliveryPrice'];

		if (isset($data['shipmentPropValues']) && is_array($data['shipmentPropValues']))
		{
			$result['PROPERTIES'] = $this->obtainPropertiesFields($data['shipmentPropValues']);
		}

		$result['PRODUCT'] = [];

		foreach ($basketItems as $index => $item)
		{
			$result['PRODUCT'][] = [
				'BASKET_CODE' => $index,
				'QUANTITY' => $item['QUANTITY'],
				'AMOUNT' => $item['QUANTITY']
			];
		}

		return $result;
	}

	protected function obtainPropertiesFields(array $shipmentPropValues)
	{
		$result = [];

		foreach ($shipmentPropValues as $prop)
		{
			$result[$prop['id']] = $prop['value'];
		}

		return $result;
	}

	protected function getDealData(int $dealId)
	{
		return [
			'PRODUCT_LIST' => $this->getProductList($dealId)
		];
	}

	protected function getProductList($dealId)
	{
		$products = \CCrmProductRow::LoadRows('D', $dealId);

		$result = [];
		foreach ($products as $product)
		{
			$item = [
				'id' => $product['PRODUCT_ID'],
				'name' => $product['PRODUCT_NAME'],
				'price' => $product['PRICE'],
				'quantity' => $product['QUANTITY'],
				'measureName' => $product['MEASURE_NAME'],
				'measureCode' => $product['MEASURE_CODE'],
				'customized' => $product['CUSTOMIZED']
			];

			if ($product['DISCOUNT_RATE'])
			{
				$item['discount'] = [
					'discountType' => $product['DISCOUNT_TYPE'],
					'discountRate' => $product['DISCOUNT_RATE'],
					'discountSum' => $product['DISCOUNT_SUM'],
				];
			}

			if ($product['TAX_RATE'])
			{
				$item['tax'] = [
					'id' => $this->getVatRateIdByValue($product['TAX_RATE']),
					'included' => $product['TAX_INCLUDED'] === 'Y',
				];
			}


			$result[] = $item;
		}

		return $result;
	}

	private function getVatRateIdByValue($rate)
	{
		$vatList = \CCrmTax::GetVatRateInfos();
		foreach ($vatList as $vat)
		{
			if ((int)$vat['VALUE'] === (int)$rate)
			{
				return $vat['ID'];
			}
		}

		return 0;
	}

	/**
	 * @param $dealId
	 * @param $products
	 * @param int $deliveryPrice
	 */

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

	/**
	 * @param array $orderIds
	 * @param array $options
	 * @return array|null
	 */
	public function sendOrdersAction(array $orderIds, array $options)
	{
		$sentOrders = [];
		$sessionId = $dialogId = false;
		if(Bitrix24Manager::getInstance()->isPaymentsLimitReached())
		{
			$this->addError(new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_PAYMENTS_LIMIT_REACHED')));
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
			$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
			/** @var Sale\Order $orderClass */
			$orderClass = $registry->getOrderClassName();

			foreach($orderIds as $orderId)
			{
				$order = $orderClass::load($orderId);
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
	 * @param array $paymentIds
	 * @param array $options
	 * @return array|null
	 */
	public function sendPaymentsAction(array $paymentIds, array $options)
	{
		$sentPayments = [];
		$sessionId = $dialogId = false;

		if (Bitrix24Manager::getInstance()->isPaymentsLimitReached())
		{
			$this->addError(new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_PAYMENTS_LIMIT_REACHED')));
			return null;
		}

		if (isset($options['sessionId']))
		{
			$sessionId = (int)$options['sessionId'];
			ImOpenLinesManager::getInstance()->setSessionId($sessionId);
		}

		$dialogId = ImOpenLinesManager::getInstance()->getDialogId();
		if (!$dialogId)
		{
			$this->addError(new Error('Dialog not found'));
		}
		elseif (!$sessionId)
		{
			$this->addError(new Error('Session not found'));
		}
		elseif (Main\Loader::includeModule('sale'))
		{
			$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);

			/** @var Sale\Order $orderClass */
			$orderClass = $registry->getOrderClassName();
			/** @var Sale\Payment $paymentClass */
			$paymentClass = $registry->getPaymentClassName();

			$orderPaymentBinding = [];
			$paymentsIterator = $paymentClass::getList([
				'select' => ['ID', 'ORDER_ID'],
				'filter' => [
					'=ID' => $paymentIds,
				],
			]);
			while ($paymentData = $paymentsIterator->fetch())
			{
				$orderPaymentBinding[$paymentData['ID']] = $paymentData['ORDER_ID'];
			}

			foreach ($paymentIds as $paymentId)
			{
				$orderId = $orderPaymentBinding[$paymentId];
				$order = $orderClass::load($orderId);
				if (!$order)
				{
					$this->addError(new Error('Order '. $orderId .' not found'));
				}
				elseif (ImOpenLinesManager::getInstance()->getUserId() != $order->getUserId())
				{
					$this->addError(new Error('Wrong user'));
				}
				else
				{
					$payment = $order->getPaymentCollection()->getItemById($paymentId);
					if ($payment)
					{
						$sendResult = ImOpenLinesManager::getInstance()->sendPaymentMessage($payment, $dialogId);
						if ($sendResult->isSuccess())
						{
							$sentPayments[] = $payment->getField('ACCOUNT_NUMBER');
						}
						else
						{
							$this->addErrors($sendResult->getErrors());
						}
					}
				}
			}
		}

		return ['payments' => $sentPayments];
	}

	/**
	 * @param $sessionId
	 * @return int
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
	 * @param $sessionId
	 * @return int
	 */
	public function getActivePaymentsCountAction($sessionId)
	{
		$count = 0;
		if (ImOpenLinesManager::getInstance()->isEnabled() && SaleManager::getInstance()->isEnabled() && CrmManager::getInstance()->isEnabled())
		{
			$userId = ImOpenLinesManager::getInstance()->setSessionId($sessionId)->getUserId();
			if ($userId > 0)
			{
				$count = Sale\Internals\PaymentTable::getCount([
					'=ORDER.USER_ID' => $userId,
					'=ORDER.STATUS_ID' => Crm\Order\OrderStatus::getSemanticProcessStatuses(),
					'!=PAY_SYSTEM_ID' => Sale\PaySystem\Manager::getInnerPaySystemId(),
				]);
			}
		}

		return $count;
	}

	/**
	 * @param null $productId
	 * @return string[]
	 */
	public function getFileControlAction($productId = null): array
	{
		return [
			'fileControl' => $this->getFileControl($productId)
		];
	}

	/**getFileControl
	 * @param $elementId
	 * @return string
	 */
	public function getFileControl($elementId = null): string
	{
		$productImagePropertyDescription = $this->getProductImagePropertyDescription($elementId);
		$property = $productImagePropertyDescription['description'];
		$value = $productImagePropertyDescription['values'];

		$inputName = $this->getFilePropertyInputName($property);

		if ($value && !is_array($value))
		{
			$value = [$value];
		}

		$fileValues = [];

		if (!empty($value) && is_array($value))
		{
			foreach ($value as $fileId)
			{
				$propName = str_replace('n#IND#', $fileId, $inputName);
				$fileValues[$propName] = $fileId;
			}
		}

		$fileType = $property['settings']['FILE_TYPE'] ?? null;

		$fileParams = [
			'name' => $inputName,
			'id' => $inputName.'_'.random_int(1, 1000000),
			'description' => $property['settings']['WITH_DESCRIPTION'] ?? 'Y',
			'allowUpload' => $fileType ? 'F' : 'I',
			'allowUploadExt' => $fileType,
			'maxCount' => ($property['settings']['MULTIPLE'] ?? 'N') !== 'Y' ? 1 : null,
			'upload' => true,
			'medialib' => false,
			'fileDialog' => true,
			'cloud' => true,
		];

		return $this->getFileControlComponentContent([
			'FILE_SETTINGS' => $fileParams,
			'FILE_VALUES' => $fileValues,
			'LOADER_PREVIEW' => $this->getFilePropertyViewHtml($value),
		]);
	}

	/**
	 * @param $elementId
	 * @return array
	 */
	private function getProductImagePropertyDescription($elementId = null): array
	{
		$result = [];

		if ($elementId)
		{
			$imageProperty = $this->getProductImageProperty($elementId);
		}
		else
		{
			$imageProperty = $this->getDefaultImageProperty();
		}

		if ($imageProperty['entity'] === 'property')
		{
			$result['description'] = [
				'entity' => $imageProperty['entity'],
				'name' => 'PROPERTY_MORE_PHOTO',
				'index' => 'MORE_PHOTO',
				'propertyId' => $imageProperty['properties']['ID'],
				'title' => $imageProperty['properties']['NAME'],
				'editable' => true,
				'required' => $imageProperty['properties']['IS_REQUIRED'] === 'Y',
				'multiple' => $imageProperty['properties']['MULTIPLE'] === 'Y',
				'defaultValue' => $imageProperty['properties']['DEFAULT_VALUE'],
				'settings' => $imageProperty['properties'],
				'type' => 'custom',
				'data' => [
					'view' => 'PROPERTY_MORE_PHOTO[VIEW_HTML]',
					'data' => 'PROPERTY_MORE_PHOTO[EDIT_HTML]',
				],
			];

			$result['values'] = $imageProperty['values'];
		}
		else
		{
			$result['description'] = [
				'entity' => $imageProperty['entity'],
				'name' => 'DETAIL_PICTURE',
				'originalName' => 'DETAIL_PICTURE',
				'title' => $imageProperty['properties']['NAME'],
				'editable' => true,
				'required' => $imageProperty['properties']['IS_REQUIRED'] === 'Y',
				'defaultValue' => $imageProperty['properties']['DEFAULT_VALUE'],
				'type' => 'custom',
				'placeholders' => null,
				'optionFlags' => 1,
				'data' => [
					'view' => 'DETAIL_PICTURE[VIEW_HTML]',
					'data' => 'DETAIL_PICTURE[EDIT_HTML]',
				],
			];

			$result['values'] = $imageProperty['values'];
		}

		return $result;
	}

	/**
	 * @param $elementId
	 * @return array
	 */
	private function getProductImageProperty($elementId): array
	{
		$result = [];

		$elementIterator = \CIBlockElement::GetList(
			[],
			[
				'ID' => $elementId,
			],
			false,
			false,
			[
				'ID',
				'IBLOCK_ID',
				'DETAIL_PICTURE',
				'PROPERTY_MORE_PHOTO',
			]
		);
		if ($element = $elementIterator->GetNextElement())
		{
			$properties = $element->GetProperties();
			if (isset($properties['MORE_PHOTO']))
			{
				$result = [
					'entity' => 'property',
					'properties' => $properties['MORE_PHOTO'],
					'values' => $properties['MORE_PHOTO']['VALUE'],
				];
			}
			else
			{
				$catalogIblockId = Option::get('crm', 'default_product_catalog_id');
				$iblockFields = \CIBlock::GetFields($catalogIblockId);

				$fields = $element->GetFields();
				$result = [
					'entity' => 'product',
					'properties' => $iblockFields['DETAIL_PICTURE'],
					'values' => $fields['DETAIL_PICTURE'],
				];
			}
		}

		return $result;
	}

	/**
	 * @return array|null
	 */
	private function getDefaultImageProperty(): ?array
	{
		$catalogIblockId = Option::get('crm', 'default_product_catalog_id');
		if (!$catalogIblockId)
			return null;

		$propertyIterator = \CIBlock::GetProperties(
			$catalogIblockId,
			[],
			[
				'CODE'=>'MORE_PHOTO',
			]
		);
		if ($propertyData = $propertyIterator->Fetch())
		{
			$result = [
				'entity' => 'property',
				'properties' => $propertyData,
				'values' => null,
			];
		}
		else
		{
			$fields = \CIBlock::GetFields($catalogIblockId);
			$result = [
				'entity' => 'product',
				'properties' => $fields['DETAIL_PICTURE'],
				'values' => null,
			];
		}

		return $result;
	}

	private function getFileControlComponentContent(array $params): string
	{
		global $APPLICATION;
		ob_start();
		$APPLICATION->includeComponent('bitrix:ui.image.input', '', $params);
		return ob_get_clean();
	}

	private function getFilePropertyInputName(array $property): string
	{
		$inputName = $property['name'] ?? '';

		if (isset($property['settings']['MULTIPLE']) && $property['settings']['MULTIPLE'] === 'Y')
		{
			$inputName .= '[n#IND#]';
		}

		return $inputName;
	}

	private function getFilePropertyViewHtml($value): string
	{
		$fileCount = 0;

		// single scalar property
		if (!empty($value) && !is_array($value))
		{
			$value = [$value];
		}

		if (is_array($value))
		{
			$fileCount = min(count($value), 3);
			$value = reset($value);
		}

		$imageSrc = null;

		if (!empty($value))
		{
			$image = \CFile::GetFileArray($value);
			if ($image)
			{
				$imageSrc = $image['SRC'];
			}
		}

		switch ($fileCount)
		{
			case 3:
				$multipleClass = ' ui-image-input-img-block-multiple';
				break;

			case 2:
				$multipleClass = ' ui-image-input-img-block-double';
				break;

			case 0:
				$multipleClass = ' ui-image-input-img-block-empty';
				break;

			case 1:
			default:
				$multipleClass = '';
				break;
		}

		if ($imageSrc)
		{
			$imageSrc = " src=\"{$imageSrc}\"";

			return <<<HTML
<div class="ui-image-input-img-block{$multipleClass}">
	<div class="ui-image-input-img-inner">
		<img class="ui-image-input-img"{$imageSrc}>
	</div>
</div>
HTML;
		}

		return '';
	}

	/**
	 * @param int $dealId
	 * @return int|null
	 */
	private function getDealPrimaryContactId(int $dealId): ?int
	{
		$contacts = DealContactTable::getDealBindings($dealId);
		foreach ($contacts as $contact)
		{
			if ($contact['IS_PRIMARY'] !== 'Y')
			{
				continue;
			}

			return (int)$contact['CONTACT_ID'];
		}

		return null;
	}

	/**
	 * @param int $contactId
	 * @return int|null
	 */
	private function getDefaultRequisiteId(int $contactId): ?int
	{
		$result = null;

		$requisiteInstance = EntityRequisite::getSingleInstance();
		$settings = $requisiteInstance->loadSettings(\CCrmOwnerType::Contact, $contactId);
		if (is_array($settings)
			&& isset($settings['REQUISITE_ID_SELECTED'])
			&& $settings['REQUISITE_ID_SELECTED'] > 0)
		{
			$requisiteId = (int)$settings['REQUISITE_ID_SELECTED'];

			if ($requisiteInstance->exists($requisiteId))
			{
				return $requisiteId;
			}
		}

		return null;
	}

	/**
	 * @param int $contactId
	 * @return int|null
	 */
	private function getFirstRequisiteId(int $contactId): ?int
	{
		$requisite = EntityRequisite::getSingleInstance()->getList(
			[
				'order' => [
					'SORT' => 'ASC',
					'ID' => 'ASC'
				],
				'filter' => [
					'=ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
					'=ENTITY_ID' => $contactId
				],
				'limit' => 1,
			]
		)->fetch();

		if (!$requisite)
		{
			return null;
		}

		return (int)$requisite['ID'];
	}

	/**
	 * @param int $contactId
	 * @return int|null
	 */
	private function getRequisiteId(int $contactId): ?int
	{
		$defaultRequisiteId = $this->getDefaultRequisiteId($contactId);
		if ($defaultRequisiteId)
		{
			return $defaultRequisiteId;
		}

		return $this->getFirstRequisiteId($contactId);
	}

	/**
	 * @param int $contactId
	 * @return int|null
	 */
	private function createDefaultRequisite(int $contactId): ?int
	{
		$requisiteInstance = EntityRequisite::getSingleInstance();

		$result = $requisiteInstance->add(
			[
				'ENTITY_TYPE_ID' => \CCrmOwnerType::Contact,
				'ENTITY_ID' => $contactId,
				'PRESET_ID' => $requisiteInstance->getDefaultPresetId(
					\CCrmOwnerType::Contact
				),
				'NAME' => \CCrmOwnerType::GetCaption(
					\CCrmOwnerType::Contact,
					$contactId,
					false
				),
				'SORT' => 500,
				'ADDRESS_ONLY' => 'Y',
				'ACTIVE' => 'Y'
			]
		);
		if(!$result->isSuccess())
		{
			return null;
		}

		return (int)$result->getId();
	}

	/**
	 * @param int $shipmentId
	 * @return int|null
	 */
	private function getClientAddressId(int $shipmentId): ?int
	{
		return $this->getAddressId($shipmentId, 'IS_ADDRESS_TO');
	}

	/**
	 * @param int $shipmentId
	 * @return int|null
	 */
	private function getDeliveryFromAddressId(int $shipmentId): ?int
	{
		return $this->getAddressId($shipmentId, 'IS_ADDRESS_FROM');
	}

	/**
	 * @param int $shipmentId
	 * @param string $attribute
	 * @return int|null
	 */
	private function getAddressId(int $shipmentId, string $attribute)
	{
		$shipment = Sale\Repository\ShipmentRepository::getInstance()->getById($shipmentId);

		/** @var \Bitrix\Sale\PropertyValue $propValue */
		$propValue = $shipment->getPropertyCollection()->getAttribute($attribute);
		if (!$propValue)
		{
			return null;
		}

		$addressArray = $propValue->getValue();
		if (!is_array($addressArray))
		{
			return null;
		}

		return (int)$addressArray['id'];
	}

	/**
	 * @param int $contactId
	 * @param int $shipmentId
	 */
	private function tryToFillContactDeliveryAddress(int $contactId, int $shipmentId)
	{
		/**
		 * Get existing requisite or create a new one
		 */
		$requisiteId = $this->getRequisiteId($contactId);
		if (!$requisiteId)
		{
			$requisiteId = $this->createDefaultRequisite($contactId);
		}

		if (!$requisiteId)
		{
			return;
		}

		/**
		 * Check if address is specified so that we do not overwrite it
		 */
		$existingAddress = RequisiteAddress::getByOwner(
			Crm\EntityAddressType::Delivery,
			\CCrmOwnerType::Requisite,
			$requisiteId
		);
		if ($existingAddress)
		{
			return;
		}

		$addressId = $this->getClientAddressId($shipmentId);
		if (!$addressId)
		{
			return;
		}

		/**
		 * Register address
		 */
		RequisiteAddress::register(
			\CCrmOwnerType::Requisite,
			$requisiteId,
			Crm\EntityAddressType::Delivery,
			[
				//@TODO wait for the fix on the CRM's side and get back to passing ID instead of the object
				//'LOC_ADDR_ID' => $addressId,
				'LOC_ADDR' => Address::load($addressId),
			]
		);
	}

	/**
	 * @param int $shipmentId
	 */
	private function saveDeliveryAddressFrom(int $shipmentId)
	{
		$addressId = $this->getDeliveryFromAddressId($shipmentId);
		if (!$addressId)
		{
			return;
		}

		LocationManager::getInstance()->storeLocationFrom($addressId);
	}

	private function onAfterDealAdd(int $dealId, int $sessionId): void
	{
		$sessionInfo = ImOpenLinesManager::getInstance()->setSessionId($sessionId)->getSessionInfo();
		if ($sessionInfo)
		{
			$session = new ImOpenLines\Session();
			$sessionStart = $session->load([
				'USER_CODE' => $sessionInfo['USER_CODE'],
				'SKIP_CREATE' => 'Y',
			]);
			if ($sessionStart)
			{
				$dealContactData = Crm\Binding\DealContactTable::getList([
					'select' => ['CONTACT_ID'],
					'filter' => [
						'=DEAL_ID' => $dealId,
						'=IS_PRIMARY' => 'Y',
						'!=CONTACT_ID' => 0,
					],
				])->fetch();
				if ($dealContactData)
				{
					$contactId = $dealContactData['CONTACT_ID'];
				}

				$updateSession = [
					'CRM_CREATE_DEAL' => 'Y',
				];

				$updateChat = [
					'DEAL' => $dealId,
					'ENTITY_ID' => $dealId,
					'ENTITY_TYPE' => 'DEAL',
					'CRM' => 'Y',
				];

				$crmManager = new ImOpenLines\Crm($session);
				$selector = $crmManager->getEntityManageFacility()->getSelector();
				$registeredEntities = $crmManager->getEntityManageFacility()->getRegisteredEntities();

				if ($selector)
				{
					$entity = new Crm\Entity\Identificator\Complex(\CCrmOwnerType::Deal, $dealId);
					$selector->setEntity($entity->getTypeId(), $entity->getId());
					$registeredEntities->setComplex($entity, true);
				}

				if (isset($contactId))
				{
					$updateSession['CRM_CREATE_CONTACT'] = 'Y';
					$updateChat['CONTACT'] = $contactId;

					if ($selector)
					{
						$entity = new Crm\Entity\Identificator\Complex(\CCrmOwnerType::Contact, $contactId);
						$selector->setEntity($entity->getTypeId(), $entity->getId());
						$registeredEntities->setComplex($entity, true);
					}
				}

				$registerActivityResult = $crmManager->registerActivity();
				if ($registerActivityResult->isSuccess())
				{
					$updateSession['CRM_ACTIVITY_ID'] = $registerActivityResult->getResult();
					$session->updateCrmFlags($updateSession);
					$chat = $session->getChat();
					if ($chat)
					{
						$chat->setCrmFlag($updateChat);
					}

					$trace = $crmManager->getEntityManageFacility()->getTrace();
					if ($trace && !$trace->getId())
					{
						$traceId = $trace->save();
						if ($traceId)
						{
							Crm\Tracking\Trace::appendEntity($traceId, \CCrmOwnerType::Deal, $dealId);
						}
					}

					$crmManager->updateUserConnector();
				}


				$dealFields = [];
				if (isset($contactId))
				{
					$contactData = Crm\Entity\Contact::getByID($contactId);
					if (!empty($contactData['LAST_NAME']))
					{
						$dealFields = [
							'TITLE' => $contactData['LAST_NAME'] . ' - ' . $session->getConfig('LINE_NAME')
						];
					}
				}

				if (!$dealFields && $session->getChat())
				{
					$dealFields = [
						'TITLE' => $session->getChat()->getData('TITLE')
					];
				}

				if ($dealFields)
				{
					$deal = new \CCrmDeal(false);
					$deal->Update($dealId, $dealFields);
				}
			}
		}
	}
}
