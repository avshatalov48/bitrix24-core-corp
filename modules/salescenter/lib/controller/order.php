<?php

namespace Bitrix\SalesCenter\Controller;

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
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\Salescenter;
use Bitrix\SalesCenter\OrderFacade;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Crm\EntityAddress;
use Bitrix\Sale\Delivery\Services\OrderPropsDictionary;

define('SALESCENTER_RECEIVE_PAYMENT_APP_AREA', true);

Loc::loadMessages(__FILE__);

class Order extends Base
{
	public function configureActions()
	{
		return array(
			'searchProduct' => array('class' => SearchProductAction::class)
		);
	}

	protected function processBeforeAction(Action $action)
	{
		\CFile::DisableJSFunction(true);

		return parent::processBeforeAction($action);
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
			$this->addError(new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_PAYMENTS_LIMIT_REACHED')));
			return null;
		}
	}

	public function refreshBasketAction(array $basketItems = array())
	{
		$this->checkModules();
		if (!empty($this->getErrors()))
		{
			return null;
		}

		$sortedByCode = [];
		$orderFacade = new OrderFacade();
		foreach ($basketItems as $item)
		{
			$item = $this->obtainProductFields($item);

			$orderFacade->addProduct($item);
			$sortedByCode[$item['code']] = $item;
		}

		$order = $orderFacade->buildOrder();
		if ($order === null)
		{
			return ['items' => $basketItems];
		}

		$discountSum = 0;
		foreach ($order->getBasket() as $basketItem)
		{
			$discountSum += $basketItem->getDiscountPrice() * $basketItem->getQuantity();
		}

		return [
			'items' => $this->fillResultBasket($order, $sortedByCode),
			'total' => [
				'discount' => SaleFormatCurrency($discountSum, $order->getCurrency(), true),
				'result' => SaleFormatCurrency($order->getPrice(), $order->getCurrency(), true),
				'resultNumeric' => $order->getPrice(),
				'sum' => SaleFormatCurrency($order->getBasket()->getBasePrice(), $order->getCurrency(), true),
			]
		];
	}

	/**
	 * @param array $basketItems
	 * @param array $options
	 * @param int $deliveryServiceId
	 * @param array $deliveryRelatedPropValues
	 * @param array $deliveryRelatedServiceValues
	 * @param int $deliveryResponsibleId
	 * @return array|null
	 */
	public function refreshDeliveryAction(
		array $basketItems,
		array $options,
		int $deliveryServiceId,
		array $deliveryRelatedPropValues = [],
		array $deliveryRelatedServiceValues = [],
		int $deliveryResponsibleId = 0
	)
	{
		$this->checkModules();

		if (!empty($this->getErrors()))
		{
			return null;
		}

		$formData = $this->obtainOrderFields($basketItems, $options);

		$formData['SHIPMENT'] = [
			$this->obtainShipmentFields($deliveryServiceId, $deliveryRelatedServiceValues, $deliveryResponsibleId)
		];

		$formData['PROPERTIES'] = $this->obtainPropertiesFields($deliveryRelatedPropValues);

		$orderFacade = new OrderFacade();
		$orderFacade->setFields($formData);

		foreach ($basketItems as $item)
		{
			$item = $this->obtainProductFields($item);

			$orderFacade->addProduct($item);
		}

		$order = $orderFacade->buildOrder(true);
		if ($order === null)
		{
			$this->addErrors($orderFacade->getErrors());
			return null;
		}

		return [
			'deliveryCalculationResult' => ['price' => $order->getDeliveryPrice()],
		];
	}

	/**
	 * @param Sale\Order $order
	 * @param array $sortedDefaultItems
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	private function fillResultBasket(Sale\Order $order, array $sortedDefaultItems = []): array
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

		/** @var Sale\BasketItem $basketItem */
		foreach ($basket as $basketItem)
		{
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
				'quantity' => $basketItem->getQuantity(),
				'formattedPrice' => SaleFormatCurrency($basketItem->getPrice(),  $order->getCurrency(), true),
				'encodedFields' => Main\Web\Json::encode($basketItem->getFieldValues()),
				'errors' => $errors,
				'discountInfos' => [],
				'measureCode' => $basketItem->getField('MEASURE_CODE'),
				'measureName' => $basketItem->getField('MEASURE_NAME'),
				'measureRatio' => (float)($measureRatios[(int)$productId]),
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

			if (
				!isset($preparedItem['discountType'])
				&& $basketItem->isCustomPrice()
				&& (int)$preparedItem['discount'] === 0
			)
			{
				$preparedItem['discountType'] = 'currency';
			}

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
					$preparedItem['discount'] = roundEx($basketItem->getDiscountPrice() / $basketItem->getBasePrice() * 100, 2);
				}
			}
			else
			{
				$preparedItem['discount'] = 0;
			}

			$resultBasket[$sort] = $preparedItem;
		}

		sort($resultBasket);

		return $resultBasket;
	}

	public function resendPaymentAction($orderId, array $options = [])
	{
		$this->checkModules();

		if (!empty($this->getErrors()))
		{
			return null;
		}

		if ($options['sendingMethod'] === 'sms')
		{
			$order = Crm\Order\Order::load($orderId);

			$isSent = CrmManager::getInstance()->sendOrderBySms($order, $options['sendingMethodDesc']);
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
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\LoaderException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public function createPaymentAction(array $basketItems = array(), array $options = [])
	{
		$this->checkModules();

		if (!empty($this->getErrors()))
		{
			return null;
		}

		$formData = $this->obtainOrderFields($basketItems, $options);
		$formData['SHIPMENT'] = [$this->obtainShipmentFieldsOnCreate($options)];

		if (isset($options['propertyValues']))
		{
			$formData['PROPERTIES'] = $this->obtainPropertiesFields($options['propertyValues']);
		}

		$orderFacade = new Salescenter\OrderFacade();
		$orderFacade->setFields($formData);

		foreach ($basketItems as $item)
		{
			$item = $this->obtainProductFields($item);

			$orderFacade->addProduct($item);
		}

		$order = $orderFacade->saveOrder();

		if (!$orderFacade->hasErrors())
		{
			$dealId = $order->getDealBinding()->getDealId();
			if ($dealId)
			{
				if (isset($options['stageOnOrderPaid']))
				{
					CrmManager::getInstance()->saveTriggerOnOrderPaid($dealId, $options['stageOnOrderPaid']);
				}

				$this->syncOrderProductsWithDeal($dealId, $orderFacade->getField('PRODUCT'), $order);

				$primaryContactId = $this->getDealPrimaryContactId($dealId);
				if ($primaryContactId)
				{
					$this->tryToFillContactDeliveryAddress(
						$primaryContactId,
						$order->getId()
					);
				}
			}

			Bitrix24Manager::getInstance()->increasePaymentsCount();
			$data = [
				'order' => [
					'number' => $order->getField('ACCOUNT_NUMBER'),
					'id' => $order->getId()
				],
				'deal' => $this->getDealData((int)$dealId)
			];

			if ($options['sendingMethod'] === 'sms')
			{
				$isSent = CrmManager::getInstance()->sendOrderBySms($order, $options['sendingMethodDesc']);
				if (!$isSent)
				{
					$this->addError(
						new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_SEND_SMS_ERROR'))
					);
				}
			}
			elseif ($options['dialogId'])
			{
				$result = ImOpenLinesManager::getInstance()->sendOrderNotify($order, $options['dialogId']);
				if (!$result->isSuccess())
				{
					$this->addErrors($result->getErrors());
				}

				if (!isset($options['skipPublicMessage']) || $options['skipPublicMessage'] === 'n')
				{
					$paymentData = [];
					$payment = $order->getPaymentCollection()[0];
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

					$result = ImOpenLinesManager::getInstance()->sendOrderMessage($order, $options['dialogId'], $paymentData);
					if (!$result->isSuccess())
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

			return $data;
		}
		else
		{
			$this->addErrors($orderFacade->getErrors());
		}

		return [];
	}

	protected function obtainOrderFields(array $basket, $options)
	{
		$result = [
			'SITE_ID' => SITE_ID,
			'CONNECTOR' => $options['connector']
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

		return $result;
	}

	protected function obtainProductFields(array $product)
	{
		if (
			(int)$product['discount'] === 0
			&&
			abs($product['price'] - $product['basePrice']) > 1e-10
		)
		{
			$product['discount'] = 100 - ($product['price'] / $product['basePrice']) * 100;
			$product['discount'] = (int)$product['discount'];
		}

		return $product;
	}

	protected function obtainShipmentFieldsOnCreate($data)
	{
		$deliveryId = $data['deliveryId'] ?? 0;
		$extraServices = $data['deliveryExtraServicesValues'] ?? [];
		$responsibleId = $data['deliveryResponsibleId'] ?? 0;

		$result = $this->obtainShipmentFields($deliveryId, $extraServices, $responsibleId);

		$result['CUSTOM_PRICE_DELIVERY'] = 'Y';

		if (isset($data['deliveryPrice']) && (float)$data['deliveryPrice'] > 0)
		{
			$result['PRICE_DELIVERY'] = (float)$data['deliveryPrice'];
		}

		if (isset($data['expectedDeliveryPrice']) && (float)$data['expectedDeliveryPrice'] > 0)
		{
			$result['EXPECTED_PRICE_DELIVERY'] = (float)$data['expectedDeliveryPrice'];
		}

		return $result;
	}

	protected function obtainShipmentFields($deliveryId, $deliveryRelatedServiceValues, $deliveryResponsibleId)
	{
		if ((int)$deliveryId === 0)
		{
			$deliveryId = Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
		}

		/**
		 * Delivery extra services
		 */
		$extraServices = [];
		foreach ($deliveryRelatedServiceValues as $deliveryRelatedServiceValue)
		{
			$extraServices[$deliveryRelatedServiceValue['id']] = $deliveryRelatedServiceValue['value'];
		}

		return [
			'RESPONSIBLE_ID' => $deliveryResponsibleId,
			'DELIVERY_ID' => $deliveryId,
			'EXTRA_SERVICES' => $extraServices,
			'ALLOW_DELIVERY' => 'Y',
		];
	}

	protected function obtainPropertiesFields($deliveryRelatedPropValues)
	{
		$result = [];

		foreach ($deliveryRelatedPropValues as $prop)
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
				'id' => $product['ID'],
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
	 * @param int $dealId
	 * @param array $products
	 * @param Sale\Order $order
	 */
	protected function syncOrderProductsWithDeal($dealId, $products, $order)
	{
		$result = [];

		if ($this->getCountBindingOrders($dealId) > 1)
		{
			$result = \CCrmDeal::LoadProductRows($dealId);
		}

		$sort = $this->getMaxProductDealSort($result);

		foreach ($products as $product)
		{
			$sort += 10;
			$item = [
				'PRODUCT_ID' => $product['productId'],
				'PRODUCT_NAME' => $product['name'],
				'PRICE' => $product['basePrice'],
				'PRICE_ACCOUNT' => $product['basePrice'],
				'PRICE_EXCLUSIVE' => $product['basePrice'],
				'PRICE_NETTO' => $product['basePrice'],
				'PRICE_BRUTTO' => $product['basePrice'],
				'QUANTITY' => $product['quantity'],
				'MEASURE_CODE' => $product['measureCode'],
				'MEASURE_NAME' => $product['measureName'],
				'TAX_RATE' => $product['taxRate'],
				'TAX_INCLUDED' => $product['taxIncluded'],
				'SORT' => $sort,
			];

			$discount = 0;
			if ((string)$product['discountType'] === 'percent')
			{
				if ($product['discount'] > 0)
				{
					$discount = $product['basePrice'] * $product['discount'] / 100;

					$item['DISCOUNT_TYPE_ID'] = \Bitrix\Crm\Discount::PERCENTAGE;
					$item['DISCOUNT_RATE'] = $product['discount'];
					$item['DISCOUNT_SUM'] = $discount;
				}
			}
			elseif ((string)$product['discountType'] === 'currency')
			{
				$item['DISCOUNT_TYPE_ID'] = \Bitrix\Crm\Discount::MONETARY;
				$item['DISCOUNT_SUM'] = $product['discount'];

				$discount = $product['discount'];
			}

			$item['PRICE'] -= $discount;
			$item['PRICE_ACCOUNT'] -= $discount;
			$item['PRICE_EXCLUSIVE'] -= $discount;

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

		\CCrmDeal::SaveProductRows($dealId, $result);
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
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\LoaderException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
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
	 * @param null $productId
	 * @return string[]
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
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
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
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
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\LoaderException
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
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
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
	 * @throws Main\ArgumentException
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
	 * @throws Main\NotSupportedException
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
	 * @param int $orderId
	 * @return int|null
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\NotImplementedException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function getClientAddressId(int $orderId): ?int
	{
		$order = Sale\Order::load($orderId);
		if (!$order)
		{
			return null;
		}

		/** @var \Bitrix\Sale\PropertyValue $propValue */
		$propValue = $order->getPropertyCollection()
			->getItemByOrderPropertyCode(
				OrderPropsDictionary::ADDRESS_TO_PROPERTY_CODE
			);

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
	 * @param int $orderId
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\NotImplementedException
	 * @throws Main\NotSupportedException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	private function tryToFillContactDeliveryAddress(int $contactId, int $orderId)
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
			EntityAddress::Delivery,
			\CCrmOwnerType::Requisite,
			$requisiteId
		);
		if ($existingAddress)
		{
			return;
		}

		$addressId = $this->getClientAddressId($orderId);
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
			EntityAddress::Delivery,
			[
				'LOC_ADDR_ID' => $addressId,
			]
		);
	}
}
