<?php

namespace Bitrix\SalesCenter\Controller;

use Bitrix\Location\Entity\Address;
use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Crm;
use Bitrix\Sale\BasketItemBase;
use Bitrix\Sale\Label\EntityLabelService;
use Bitrix\SalesCenter\Component\ReceivePaymentModeDictionary;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\CrmManager;
use Bitrix\SalesCenter\Integration\ImOpenLinesManager;
use Bitrix\SalesCenter\Integration\ImConnectorManager;
use Bitrix\SalesCenter\Integration\LandingManager;
use Bitrix\SalesCenter\Integration\LocationManager;
use Bitrix\SalesCenter\Integration\SaleManager;
use Bitrix\Salescenter;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Conversion\LeadConverter;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\Builder\SettingsContainer;
use Bitrix\Crm\Order\PersonType;
use Bitrix\Crm\RequisiteAddress;
use Bitrix\Crm\Service\Container;
use Bitrix\Sale\Delivery;
use Bitrix\Sale\Helpers\Order\Builder\Converter\CatalogJSProductForm;
use Bitrix\Sale\PaySystem\PaymentAvailablesPaySystems;
use Bitrix\SalesCenter\Component\VatRate;
use CCrmLead;
use CCrmOwnerType;
use CCrmSecurityHelper;
use Bitrix\Sale\Internals\OrderTable;

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

		$order = $this->buildOrder(
			[
				'orderId' => $orderId,
				'basketItems' => $basketItems,
			],
			[
				'orderErrorsFilter' => [
					'SALE_BASKET_AVAILABLE_QUANTITY',
					'SALE_BASKET_ITEM_WRONG_AVAILABLE_QUANTITY',
				],
			]
		);

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
			$basketItem = $this->getBasketItemByProductId(
				$order->getBasket(),
				isset($item['skuId']) ? (int)$item['skuId'] : 0,
				$item['module'] ?? ''
			);
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
			$vatRate = (float)$basketItem->getVatRate();

			if ($basketItem->isVatInPrice())
			{
				$vatSum += Sale\PriceMaths::roundPrecision(
					$basketItem->getPrice()
					* $item['quantity']
					* $vatRate
					/ (
						$vatRate + 1
					)
				);
			}
			else
			{
				$vatSum += Sale\PriceMaths::roundPrecision(
					$basketItem->getPrice()
					* $item['quantity']
					* $vatRate
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

	private function getBasketItemByProductId(
		Sale\BasketBase $basketItemCollection,
		int $productId,
		string $module
	): ?BasketItemBase
	{
		/** @var BasketItemBase $basketItem */
		foreach ($basketItemCollection as $basketItem)
		{
			if (
				$basketItem->getProductId() === $productId
				&& $basketItem->getField('MODULE') === $module
			)
			{
				return $basketItem;
			}
		}

		return null;
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

		$formData['PRODUCT'] = CatalogJSProductForm::convertToBuilderFormat($basketItems);

		$formData['PRODUCT'] = $this->tryToObtainMissingProductProperties($basketItems, $formData['PRODUCT']);

		$formData['PROPERTIES'] = $this->obtainPropertiesFields($propertyValues);

		if ($this->needObtainShipmentFields($params))
		{
			$formData['SHIPMENT'][] = $this->obtainShipmentFields($params, $formData['PRODUCT']);
		}

		if ($scenario !== SettingsContainer::BUILDER_SCENARIO_SHIPMENT)
		{
			$formData['PAYMENT'][] = $this->obtainPaymentFields($formData);
		}

		if (!empty($params['currency']))
		{
			$formData['CURRENCY'] = $params['currency'];
		}

		if (empty($formData['SITE_ID']))
		{
			$formData['SITE_ID'] = SITE_ID;
		}

		if (!empty($formData['CLIENT']['COMPANY_ID']))
		{
			$formData['PERSON_TYPE_ID'] = PersonType::getCompanyPersonTypeId();
		}
		else
		{
			$formData['PERSON_TYPE_ID'] = PersonType::getContactPersonTypeId();
		}

		if (!isset($formData['RESPONSIBLE_ID']))
		{
			$formData['RESPONSIBLE_ID'] = CCrmSecurityHelper::GetCurrentUserID();
		}

		return $formData;
	}

	private function tryToObtainMissingProductProperties(array $basketItems, array $formProducts): array
	{
		$resultProducts = $formProducts;

		$itemsToFill = array_filter($basketItems, static function($item) {
			return !array_key_exists('properties', $item);
		});
		$idsToFill = array_column($itemsToFill, 'skuId');

		if (empty($idsToFill))
		{
			return $resultProducts;
		}

		$productsData = Sale\Helpers\Admin\Blocks\OrderBasket::getProductsData($idsToFill, SITE_ID, ['PROPS']);
		foreach ($resultProducts as $key => $product)
		{
			$productId = $product['PRODUCT_ID'];
			if (isset($productsData[$productId]['PROPS']))
			{
				$resultProducts[$key]['PROPS'] = $productsData[$productId]['PROPS'];
				$resultProducts[$key]['FIELDS_VALUES'] = Main\Web\Json::encode($resultProducts[$key]);
			}
		}

		return $resultProducts;
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

		$deliveryPriceResult = $shipment->calculateDelivery();
		if (!$deliveryPriceResult->isSuccess())
		{
			$this->addErrors($deliveryPriceResult->getErrors());
		}
		return [
			'deliveryPrice' => $deliveryPriceResult->getPrice(),
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
				$preparedItem['discountType'] = Crm\Discount::MONETARY;
			}

			if (
				!empty($basketItem->getDiscountPrice())
				&& $basketItem->getBasePrice() > 0
			)
			{
				if (empty($preparedItem['discountType']))
				{
					$preparedItem['discountType'] = Crm\Discount::PERCENTAGE;
				}

				if (empty($preparedItem['showDiscount']))
				{
					$preparedItem['showDiscount'] = 'Y';
				}

				$preparedItem['discount'] = $basketItem->getDiscountPrice();
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

			if ($payment === null)
			{
				$this->addError(
					new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_CANT_SEND_SMS_PAYMENT_NOT_FOUND'))
				);

				return;
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
				if (!LandingManager::getInstance()->isPhoneConfirmed())
				{
					$this->addError(
						new Error(
							Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_SEND_SMS_NOTICE_PHONE_IS_NOT_CONFIRMED'),
							9,
							['connectedSiteId' => LandingManager::getInstance()->getConnectedSiteId()],
						)
					);
				}
				else
				{
					$this->addError(
						new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_SEND_SMS_ERROR'), 20)
					);
				}
			}
			else
			{
				$this->processDocumentSelectorOptions($paymentId, $options);
			}
		}
	}

	protected function processDocumentSelectorOptions(int $paymentId, array $options): Main\Result
	{
		$result = new Main\Result();

		$documentGenerator = Crm\Integration\DocumentGeneratorManager::getInstance();
		$entityTypeId = (int)($options['ownerTypeId'] ?? 0);
		if (
			$entityTypeId > 0
			&& $documentGenerator->isEntitySupportsPaymentDocumentBinding($entityTypeId)
		)
		{
			$boundDocumentId = (int)($options['boundDocumentId'] ?? 0);
			if (!$boundDocumentId)
			{
				$selectedTemplateId = (int)($options['selectedTemplateId'] ?? 0);
				if (!($selectedTemplateId > 0))
				{
					return $result->addError(new Error('Could not create new document: selectedTemplateId is not specified'));
				}
				$entityId = (int)($options['ownerId'] ?? 0);
				if (!$entityId)
				{
					return $result->addError(new Error('Could not create new document: ownerId is not specified'));
				}

				$createDocumentResult = $documentGenerator->createDocumentForItem(
					new Crm\ItemIdentifier(
						$entityTypeId,
						$entityId,
					),
					$selectedTemplateId,
					$paymentId,
				);
				if ($createDocumentResult->isSuccess())
				{
					$boundDocumentId = $createDocumentResult->getData()['id'];
				}
				else
				{
					return $result->addErrors($createDocumentResult->getErrors());
				}
			}
			if ($boundDocumentId > 0)
			{
				return $documentGenerator->bindDocumentToPayment($boundDocumentId, $paymentId);
			}
		}

		return $result;
	}

	/**
	 * Move lead to finish status, and linked with deal
	 *
	 * @param int $leadId
	 * @param int $dealId
	 *
	 * @return void
	 */
	private function convertionLeadWithExistDeal(int $leadId, int $dealId): void
	{
		$fields = [
			'STATUS_ID' => 'CONVERTED',
		];

		$lead = new CCrmLead(false);
		$lead->Update($leadId, $fields);
		if ($lead->LAST_ERROR)
		{
			// as can't change status, that no point in converting.
			return;
		}

		$converter = new LeadConverter();
		$converter->setEntityID($leadId);

		$contextData = [
			CCrmOwnerType::DealName => $dealId,
		];

		// load deal relations
		$dealIdentifier = new ItemIdentifier(CCrmOwnerType::Deal, $dealId);
		$dealRelations = Container::getInstance()->getRelationManager()->getParentRelations($dealIdentifier->getEntityTypeId());
		foreach ($dealRelations as $relation)
		{
			if (isset($contextData[$relation->getParentEntityTypeId()]))
			{
				continue;
			}

			$parentIds = $relation->getParentElements($dealIdentifier);
			foreach ($parentIds as $parentId)
			{
				$entityTypeName = CCrmOwnerType::ResolveName($parentId->getEntityTypeId());
				if ($entityTypeName)
				{
					$contextData[$entityTypeName] = $parentId->getEntityId();
				}
			}
		}
		unset($contextData[CCrmOwnerType::LeadName]);

		$converter->setContextData($contextData);
		foreach ($contextData as $entityTypeName => $entityId)
		{
			$entityTypeId = CCrmOwnerType::ResolveID($entityTypeName);
			$item = $converter->getConfig()->getItem($entityTypeId);
			if ($item)
			{
				$item->setActive(true);
				$item->enableSynchronization(false);
			}
		}

		$converter->convert();
	}

	public function createTerminalPaymentAction(array $basketItems = [], array $options = [])
	{
		$ownerTypeId = (int)$options['ownerTypeId'];
		$ownerId = (int)$options['ownerId'];

		$factory = Crm\Service\Container::getInstance()->getFactory($ownerTypeId);
		$item = $factory?->getItem($ownerId);
		if (!$item)
		{
			$this->addError(new Error(
				Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_TERMINAL_PAYMENT_CREATION_ERROR')
			));
			return [];
		}

		$paymentOptions = [
			'currency' => $options['currency'],
			'responsibleId' => (int)$options['paymentResponsibleId'],
			'entity' => new ItemIdentifier($ownerTypeId, $ownerId),
		];

		$primaryContact = $item->getPrimaryContact();
		if ($primaryContact)
		{
			$primaryContactId = $primaryContact->getId();

			$contactPhoneNumber = CrmManager::getContactPhoneFormat($primaryContactId);
			if ($contactPhoneNumber)
			{
				$paymentOptions['phoneNumber'] = $contactPhoneNumber;
			}
		}

		$basketItems = VatRate::prepareTaxPrices($basketItems);
		$basketItems = $this->processBasketItems($basketItems);
		$products = CatalogJSProductForm::convertToBuilderFormat($basketItems);

		$paymentResult = Container::getInstance()->getTerminalPaymentService()->createByProducts(
			$products,
			Crm\Service\Sale\Terminal\CreatePaymentOptions::createFromArray($paymentOptions),
		);

		if (!$paymentResult->isSuccess())
		{
			$this->addErrors($paymentResult->getErrors());

			return [];
		}

		/* @var Sale\Payment $payment */
		$payment = $paymentResult->getData()['payment'];
		$order = $payment->getOrder();

		SaleManager::onSalescenterPaymentCreated($payment);

		if (isset($options['stageOnOrderPaid']))
		{
			CrmManager::getInstance()->saveTriggerOnOrderPaid(
				$ownerId,
				$ownerTypeId,
				$options['stageOnOrderPaid']
			);
		}

		$productManager = new Crm\Order\ProductManager((int)$options['ownerTypeId'], (int)$options['ownerId']);
		$productManager->setOrder($order)->syncOrderProducts($basketItems);

		$data = [
			'order' => [
				'number' => $order->getField('ACCOUNT_NUMBER'),
				'id' => $order->getId(),
				'paymentId' => $payment->getId(),
			],
		];

		if ($ownerTypeId === CCrmOwnerType::Deal)
		{
			// back compatibility ??
			$data['deal'] = $this->getEntityData($ownerTypeId, $ownerId);
		}

		$data['entity'] = $this->getEntityData($ownerTypeId, $ownerId);

		return $data;
	}

	public function updateTerminalPaymentAction(int $paymentId, array $options = [])
	{
		$updateOptions =
			(new Crm\Service\Sale\Terminal\UpdatePaymentOptions())
				->setResponsibleId($options['paymentResponsibleId'])
		;

		$updateResult = Container::getInstance()->getTerminalPaymentService()->update($paymentId, $updateOptions);
		if (!$updateResult->isSuccess())
		{
			$this->addErrors($updateResult->getErrors());

			return [];
		}

		/* @var Sale\Payment $payment */
		$payment = $updateResult->getData()['payment'];
		$order = $payment->getOrder();

		$data = [
			'order' => [
				'number' => $order->getField('ACCOUNT_NUMBER'),
				'id' => $order->getId(),
				'paymentId' => $payment->getId(),
			],
		];

		return $data;
	}

	/**
	 * @param array $basketItems
	 * @param array $options
	 * @return array|null
	 */
	public function createPaymentAction(array $basketItems = [], array $options = [])
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

		if (
			(int)$options['orderId'] > 0
			&& !OrderTable::getRow([
				'select' => ['ID'],
				'filter' => ['=ID' => $options['orderId']],
			])
		)
		{
			$options['orderId'] = 0;
		}

		// if pay from chat - find lead id
		$crmInfo = ImOpenLinesManager::getInstance()->setSessionId($options['sessionId'])->getCrmInfo();
		$dialogLeadId = $crmInfo ? (int)$crmInfo['LEAD'] : null;

		$basketItems = VatRate::prepareTaxPrices($basketItems);
		$basketItems = $this->processBasketItems($basketItems);

		$options['basketItems'] = $basketItems;

		$isEnabledAutomaticReservation = Sale\Configuration::isEnableAutomaticReservation();
		if ($isEnabledAutomaticReservation)
		{
			Sale\Configuration::disableAutomaticReservation();
		}

		/** @var Crm\Order\Order $order */
		$order = $this->buildOrder(
			$options,
			[
				'orderErrorsFilter' => [
					'SALE_BASKET_AVAILABLE_QUANTITY',
					'SALE_BASKET_ITEM_WRONG_AVAILABLE_QUANTITY',
				],
			]
		);
		if ($order === null)
		{
			// if throw error not in 'orderErrorsFilter'
			if ($this->errorCollection->count() === 0)
			{
				$this->addError(
					new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_CANT_BUILD_ORDER'))
				);
			}

			return [];
		}

		$shipment = $this->findNewShipment($order);
		$payment = $this->findNewPayment($order);
		$changedBasketItems = $this->getChangedBasketItems($order);

		$result = $order->save();

		if ($isEnabledAutomaticReservation)
		{
			Sale\Configuration::enableAutomaticReservation();
		}

		if ($result->isSuccess())
		{
			$isPaymentSaved = $payment && $payment->getId();

			Bitrix24Manager::getInstance()->increasePaymentsCount();

			$binding = $order->getEntityBinding();

			$entityTypeId = $binding ? $binding->getOwnerTypeId() : 0;
			$entityId = $binding ? $binding->getOwnerId() : 0;

			$productManager = new Crm\Order\ProductManager($entityTypeId, $entityId);
			$productManager->setOrder($order)->syncOrderProducts(
				$this->prepareItemsBeforeSync(
					$changedBasketItems,
					$basketItems
				)
			);

			$data = [
				'order' => [
					'number' => $order->getField('ACCOUNT_NUMBER'),
					'id' => $order->getId(),
					'paymentId' => $payment?->getId(),
					'shipmentId' => $shipment?->getId(),
				],
			];

			if (isset($options['stageOnOrderPaid']))
			{
				CrmManager::getInstance()->saveTriggerOnOrderPaid(
					$entityId,
					$entityTypeId,
					$options['stageOnOrderPaid']
				);
			}

			if (isset($options['stageOnDeliveryFinished']))
			{
				CrmManager::getInstance()->saveTriggerOnDeliveryFinished(
					$entityId,
					$entityTypeId,
					$options['stageOnDeliveryFinished']
				);
			}

			if ($entityTypeId === \CCrmOwnerType::Deal)
			{
				if ($dialogLeadId)
				{
					$this->convertionLeadWithExistDeal($dialogLeadId, $entityId);
				}

				if (!empty($options['sessionId']) && (int)$options['ownerId'] <= 0)
				{
					$this->onAfterDealAdd($entityId, $options['sessionId']);
				}

				$dealPrimaryContactId = $this->getDealPrimaryContactId($entityId);
				if ($shipment && $dealPrimaryContactId)
				{
					$this->tryToFillContactDeliveryAddress($dealPrimaryContactId, $shipment->getId());
				}

				if (isset($options['mode']) && $options['mode'] === ReceivePaymentModeDictionary::PAYMENT)
				{
					$this->setDefaultReceivePaymentMode(ReceivePaymentModeDictionary::PAYMENT);
				}

				// back compatibility ??
				$data['deal'] = $this->getEntityData($entityTypeId, $entityId);
			}

			if ($isPaymentSaved)
			{
				$this->processDocumentSelectorOptions($payment->getId(), $options);

				if (SaleManager::getInstance()->isTelegramOrder($order))
				{
					ImConnectorManager::getInstance()->sendTelegramPaymentNotification($payment, $options['sendingMethodDesc']);
				}

				$this->markPaymentWithLabels($payment, $options);

				SaleManager::onSalescenterPaymentCreated($payment);
			}

			$data['entity'] = $this->getEntityData($entityTypeId, $entityId);

			if ($shipment)
			{
				$this->saveDeliveryAddressFrom($shipment->getId());
			}

			if ($options['sendingMethod'] === 'sms')
			{
				if ($isPaymentSaved)
				{
					$isSent = CrmManager::getInstance()->sendPaymentBySms($payment, $options['sendingMethodDesc'], $shipment);

					if (!$isSent)
					{
						if (!LandingManager::getInstance()->isPhoneConfirmed())
						{
							$this->addError(
								new Error(
									Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_SEND_SMS_NOTICE_PHONE_IS_NOT_CONFIRMED'),
									9,
									['connectedSiteId' => LandingManager::getInstance()->getConnectedSiteId()],
								)
							);
						}
						else
						{
							$this->addError(
								new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_SEND_SMS_ERROR'), 10)
							);
						}
					}
				}
				else
				{
					$this->addError(
						new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_CANT_SEND_SMS_PAYMENT_NOT_CREATED'))
					);
				}
			}
			elseif ($options['dialogId'])
			{
				if ($isPaymentSaved)
				{
					$r = new Main\Result();

					$binding = $order->getEntityBinding();
					if ($binding && $binding->getOwnerTypeId() === \CCrmOwnerType::Deal)
					{
						$dealId = $binding->getOwnerId();

						if (
							$dealId
							&& (
								(int)$options['ownerId'] <= 0
								|| !CrmManager::getInstance()->isOwnerEntityExists(
									(int)$options['ownerId'],
									\CCrmOwnerType::Deal
								)
							)
						)
						{
							$r = ImOpenLinesManager::getInstance()->sendDealNotify($dealId, $options['dialogId']);
						}

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

				if (
					$isPaymentSaved
					&& (
						!isset($options['skipPublicMessage'])
						|| $options['skipPublicMessage'] === 'n'
					)
				)
				{
					$paymentData = [];
					$paySystemService = Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());

					if ($options['connector'] === 'imessage'
						&& $paySystemService
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
				if ($isPaymentSaved)
				{
					$publicUrl = ImOpenLinesManager::getInstance()->getPublicUrlInfoForPayment($payment);

					if ($options['context'] === SalesCenter\Component\ContextDictionary::SMS)
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

			// save restriction pay systems for order payment
			$availablePaySystemIds = (array) ($options['availablePaySystemsIds'] ?? []);
			if (
				$isPaymentSaved
				&& $availablePaySystemIds
			)
			{
				$result = PaymentAvailablesPaySystems::setBindings($payment->getId(), $availablePaySystemIds);
				if (!$result->isSuccess())
				{
					$this->addErrors($result->getErrors());
				}
			}

			return $data;
		}
		else
		{
			$this->addErrors($result->getErrors());
		}

		return [];
	}

	private function markPaymentWithLabels(Crm\Order\Payment $payment, array $options): void
	{
		$paymentLabels = $options['paymentLabels'] ?? [];

		$sectionLabel = '';
		if ($options['context'] === SalesCenter\Component\ContextDictionary::SMS)
		{
			$sectionLabel = Salescenter\Analytics\Dictionary\SectionDictionary::CRM_SMS->value;
		}
		elseif ($options['context'] === SalesCenter\Component\ContextDictionary::CHAT)
		{
			$sectionLabel = Salescenter\Analytics\Dictionary\SectionDictionary::CHATS->value;
		}
		else
		{
			$sectionLabel = Salescenter\Analytics\Dictionary\SectionDictionary::CRM->value;
		}
		$paymentLabels[] = new Sale\Label\Label('section', $sectionLabel);

		/** @var EntityLabelService $entityLabelService */
		$entityLabelService = ServiceLocator::getInstance()->get('sale.entityLabel');
		/** @var Sale\Label\Label $label */
		foreach ($paymentLabels as $label)
		{
			$entityLabelService->mark($payment, $label);
		}
	}

	/**
	 * @param array $basketItems
	 * @param array $options
	 * @return array|null
	 */
	public function createShipmentAction(array $basketItems = [], array $options = [])
	{
		if ((int)$options['orderId'] <= 0 && CrmManager::getInstance()->isOrderLimitReached())
		{
			$this->addError(
				new Main\Error('You have reached the order limit for your plan')
			);

			return [];
		}

		$basketItems = VatRate::prepareTaxPrices($basketItems);
		$basketItems = $this->processBasketItems($basketItems);

		$options['basketItems'] = $basketItems;
		$options['withoutPayment'] = true;

		$isEnabledAutomaticReservation = Sale\Configuration::isEnableAutomaticReservation();
		if ($isEnabledAutomaticReservation)
		{
			Sale\Configuration::disableAutomaticReservation();
		}

		/** @var Crm\Order\Order $order */
		$order = $this->buildOrder(
			$options,
			[
				'builderScenario' => Salescenter\Builder\SettingsContainer::BUILDER_SCENARIO_SHIPMENT,
				'orderErrorsFilter' => [
					'SALE_BASKET_AVAILABLE_QUANTITY',
					'SALE_BASKET_ITEM_WRONG_AVAILABLE_QUANTITY',
				],
			]
		);
		if ($order === null)
		{
			// if throw error not in 'orderErrorsFilter'
			if ($this->errorCollection->count() === 0)
			{
				$this->addError(
					new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_CANT_BUILD_ORDER'))
				);
			}

			return [];
		}

		$shipment = $this->findNewShipment($order);

		$result = $order->save();

		if ($isEnabledAutomaticReservation)
		{
			Sale\Configuration::enableAutomaticReservation();
		}

		if ($result->isSuccess())
		{
			$dealId = 0;

			if ($shipment)
			{
				$binding = $order->getEntityBinding();

				if ($binding)
				{
					$dealPrimaryContactId = $this->getDealPrimaryContactId($binding->getOwnerId());
					if ($dealPrimaryContactId)
					{
						$this->tryToFillContactDeliveryAddress($dealPrimaryContactId, $shipment->getId());
					}

					$productManager = new Crm\Order\ProductManager($binding->getOwnerTypeId(), $binding->getOwnerId());
					$productManager->setOrder($order)->syncOrderProducts($basketItems);
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

	protected function getChangedBasketItems(Crm\Order\Order $order) : array
	{
		$result = [];

		/** @var Crm\Order\BasketItem $item */
		foreach ($order->getBasket() as $item)
		{
			if ($item->isChanged())
			{
				$result[] = $item;
			}
		}

		return $result;
	}

	protected function prepareItemsBeforeSync(array $changedBasketItems, array $formItems) : array
	{
		$result = [];

		$id2XmlIdMap = $this->getBasketId2XmlIdMap($changedBasketItems);

		/** @var Crm\Order\BasketItem $basketItem */
		foreach ($changedBasketItems as $basketItem)
		{
			foreach ($formItems as $formItem)
			{
				if ($basketItem->getProductId() !== (int)$formItem['skuId'])
				{
					continue;
				}

				$formItem['innerId'] = $id2XmlIdMap[$basketItem->getId()] ?? '';
				$formItem['code'] = $basketItem->getBasketCode();

				$result[] = $formItem;

				break;
			}
		}

		return $result;
	}

	protected function getBasketId2XmlIdMap(array $basketItems)
	{
		$ids = [];

		/** @var Crm\Order\BasketItem $item */
		foreach ($basketItems as $item)
		{
			$ids[] = $item->getId();
		}

		$dbRes = Crm\Order\Basket::getList([
			'select' => ['ID', 'XML_ID'],
			'filter' => [
				'=ID' => $ids
			]
		]);

		$result = [];

		while ($item = $dbRes->fetch())
		{
			$result[$item['ID']] = $item['XML_ID'];
		}

		return $result;
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
			'ID' => (int)($options['orderId'] ?? 0),
			'SITE_ID' => SITE_ID,
			'CONNECTOR' => $options['connector'] ?? '',
			'SHIPMENT' => [],
			'PAYMENT' => [],
		];

		if (!empty($options['sessionId']))
		{
			$result['USER_ID'] = ImOpenLinesManager::getInstance()->setSessionId($options['sessionId'])->getUserId();
		}

		$clientInfo = $this->getClientInfo($options);
		if (
			isset($clientInfo['OWNER_ID'])
			&& isset($clientInfo['OWNER_TYPE_ID'])
			&& CrmManager::getInstance()->isOwnerEntityExists($clientInfo['OWNER_ID'], $clientInfo['OWNER_TYPE_ID'])
		)
		{
			if (
				!isset($options['context'])
				|| $options['context'] !== SalesCenter\Component\ContextDictionary::CHAT
				|| !CrmManager::getInstance()->isOwnerEntityInFinalStage($clientInfo['OWNER_ID'], $clientInfo['OWNER_TYPE_ID'])
			)
			{
				$result['OWNER_ID'] = $clientInfo['OWNER_ID'];
				$result['OWNER_TYPE_ID'] = $clientInfo['OWNER_TYPE_ID'];
			}

			unset($clientInfo['OWNER_ID']);
			unset($clientInfo['OWNER_TYPE_ID']);
		}

		if (!empty($options['assignedById']))
		{
			$result['RESPONSIBLE_ID'] = $options['assignedById'];
		}
		elseif (isset($clientInfo['OWNER_ID'], $clientInfo['OWNER_TYPE_ID']))
		{
			$factory = Crm\Service\Container::getInstance()->getFactory($result['OWNER_TYPE_ID']);
			if ($factory)
			{
				$item = $factory->getItem($result['OWNER_ID']);
				if ($item)
				{
					$result['RESPONSIBLE_ID'] = $item->getAssignedById();
				}
			}
		}

		$result['CLIENT'] = $clientInfo;

		if ($result['ID'] === 0 && isset($options['context']))
		{
			$platform = null;

			if ($options['context'] === SalesCenter\Component\ContextDictionary::DEAL)
			{
				$platform = Crm\Order\TradingPlatform\DynamicEntity::getInstanceByCode(
					Crm\Order\TradingPlatform\DynamicEntity::getCodeByEntityTypeId($options['ownerTypeId'])
				);
			}
			elseif ($options['context'] === SalesCenter\Component\ContextDictionary::SMS)
			{
				$platform = Crm\Order\TradingPlatform\Activity::getInstanceByCode(
					Crm\Order\TradingPlatform\Activity::TRADING_PLATFORM_CODE
				);
			}

			if ($platform)
			{
				if (!$platform->isInstalled())
				{
					$platform->install();
				}

				if ($platform->isInstalled())
				{
					$result['TRADING_PLATFORM'] = $platform->getId();
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
				$price = VatRate::getPriceWithTax($item);
				$sum += Sale\PriceMaths::roundPrecision($item['QUANTITY'] * $price);

				$result['PRODUCT'][$index] = [
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
			'RESPONSIBLE_ID' => $data['deliveryResponsibleId'] ?? 0,
			'IS_REALIZATION' => 'N',
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
			$result['CUSTOM_PRICE_DELIVERY'] = 'Y';
		}

		$result['PRICE_DELIVERY'] = (float)($data['deliveryPrice'] ?? 0);

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
			$result[$prop['id']] = $prop['value'] ?? '';
		}

		return $result;
	}

	protected function getDealData(int $entityId): array
	{
		return $this->getEntityData(\CCrmOwnerType::Deal, $entityId);
	}

	protected function getEntityData(int $entityTypeId, int $entityId): array
	{
		return [
			'PRODUCT_LIST' => $this->getEntityProductList($entityTypeId, $entityId)
		];
	}

	private function getEntityProductList(int $entityTypeId, int $entityId): array
	{
		$productManager = new Crm\Order\ProductManager($entityTypeId, $entityId);
		$productList = $productManager->getEntityProductList();
		return $this->prepareProductList($productList);
	}

	private function prepareProductList(array $products): array
	{
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
					'discountType' => $product['DISCOUNT_TYPE'] ?? Crm\Discount::MONETARY,
					'discountRate' => $product['DISCOUNT_RATE'],
					'discountSum' => $product['DISCOUNT_SUM'] ?? 0,
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
		$orderTitle = '';
		$sessionId = $dialogId = false;
		if(Bitrix24Manager::getInstance()->isPaymentsLimitReached())
		{
			$this->addError(new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_PAYMENTS_LIMIT_REACHED')));
			return null;
		}

		$context = $options['context'] ?? '';

		if ($context === SalesCenter\Component\ContextDictionary::CHAT)
		{
			if(isset($options['sessionId']))
			{
				$sessionId = (int)$options['sessionId'];
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
		}

		if (!empty($this->getErrors()))
		{
			return ['orders' => $sentOrders];
		}

		if (Main\Loader::includeModule('sale'))
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
					return ['orders' => $sentOrders];
				}

				if ($context === SalesCenter\Component\ContextDictionary::CHAT)
				{
					$sendResult = $this->sendOrderByIm($order, $dialogId);
					if ($sendResult->isSuccess())
					{
						$sentOrders[] = $sendResult->getData()['ORDER'];
					}
					else
					{
						$this->addErrors($sendResult->getErrors());
					}
				}
				elseif ($context === SalesCenter\Component\ContextDictionary::SMS)
				{
					$sendResult = $this->sendOrderBySms($order);
					if ($sendResult->isSuccess())
					{
						$sentOrders[] = $sendResult->getData()['ORDER'];
						$orderTitle = $sendResult->getData()['TEXT'];
					}
					else
					{
						$this->addErrors($sendResult->getErrors());
					}
				}
			}
		}

		return [
			'orders' => $sentOrders,
			'orderTitle' => $orderTitle,
		];
	}

	private function sendOrderByIm(Sale\Order $order, $dialogId): Main\Result
	{
		$result = new Main\Result();

		if(ImOpenLinesManager::getInstance()->getUserId() != $order->getUserId())
		{
			$result->addError(new Error('Wrong user'));
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
				$result->setData(['ORDER' => $order->getField('ACCOUNT_NUMBER')]);
			}
		}

		return $result;
	}

	private function sendOrderBySms(Sale\Order $order): Main\Result
	{
		$result = new Main\Result();

		if(LandingManager::getInstance()->isOrderPublicUrlAvailable())
		{
			$urlParams = [
				'orderId' => $order->getId(),
				'access' => $order->getHash()
			];

			$urlInfo = LandingManager::getInstance()->getOrderPublicUrlInfo($urlParams);

			if($urlInfo)
			{
				$smsTemplate = CrmManager::getInstance()->getSmsTemplate();
				$smsTitle = str_replace('#LINK#', $urlInfo['shortUrl'], $smsTemplate);
				$result->setData([
					'TEXT' => $smsTitle,
					'ORDER' => $order->getField('ACCOUNT_NUMBER')
				]);
			}
			else
			{
				$result->addError(new Error('Error retrieving url info'));
			}
		}
		else
		{
			$result->addError(new Error('Public url is not available'));
		}

		return $result;
	}

	/**
	 * @param array $paymentIds
	 * @param array $options
	 * @return array|null
	 */
	public function sendPaymentsAction(array $paymentIds, array $options)
	{
		$sentPayments = [];
		$paymentTitle = '';
		$sessionId = $dialogId = false;

		if (Bitrix24Manager::getInstance()->isPaymentsLimitReached())
		{
			$this->addError(new Error(Loc::getMessage('SALESCENTER_CONTROLLER_ORDER_PAYMENTS_LIMIT_REACHED')));
			return null;
		}

		$context = $options['context'] ?? '';

		if ($context === SalesCenter\Component\ContextDictionary::CHAT)
		{
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
		}

		if (!empty($this->getErrors()))
		{
			return ['payments' => $sentPayments];
		}

		if (Main\Loader::includeModule('sale'))
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

				$payment = $order->getPaymentCollection()->getItemById($paymentId);

				if ($context === SalesCenter\Component\ContextDictionary::CHAT)
				{
					$sendResult = $this->sendPaymentByIm($payment, $dialogId);
					if ($sendResult->isSuccess())
					{
						$sentPayments[] = $sendResult->getData()['PAYMENT'];
					}
					else
					{
						$this->addErrors($sendResult->getErrors());
					}
				}
				elseif ($context === SalesCenter\Component\ContextDictionary::SMS)
				{
					$sendResult = $this->sendPaymentBySms($payment);
					if ($sendResult->isSuccess())
					{
						$sentPayments[] = $sendResult->getData()['PAYMENT'];
						$paymentTitle = $sendResult->getData()['TEXT'];
					}
					else
					{
						$this->addErrors($sendResult->getErrors());
					}
				}
			}
		}

		return [
			'payments' => $sentPayments,
			'paymentTitle' => $paymentTitle,
		];
	}

	private function sendPaymentByIm(Sale\Payment $payment, $dialogId): Main\Result
	{
		$result = new Main\Result();

		if (ImOpenLinesManager::getInstance()->getUserId() != $payment->getOrder()->getUserId())
		{
			$result->addError(new Error('Wrong user'));
		}
		else
		{
			$sendResult = ImOpenLinesManager::getInstance()->sendPaymentMessage($payment, $dialogId);
			if ($sendResult->isSuccess())
			{
				$result->setData(['PAYMENT' => $payment->getField('ACCOUNT_NUMBER')]);
			}
			else
			{
				$result->addErrors($sendResult->getErrors());
			}
		}

		return $result;
	}

	private function sendPaymentBySms(Sale\Payment $payment): Main\Result
	{
		$result = new Main\Result();

		if(LandingManager::getInstance()->isOrderPublicUrlAvailable())
		{
			$order = $payment->getOrder();
			$urlParams = [
				'orderId' => $order->getId(),
				'paymentId' => $payment->getId(),
				'access' => $order->getHash()
			];

			$urlInfo = LandingManager::getInstance()->getOrderPublicUrlInfo($urlParams);

			if($urlInfo)
			{
				$smsTemplate = CrmManager::getInstance()->getSmsTemplate();
				$smsTitle = str_replace('#LINK#', $urlInfo['shortUrl'], $smsTemplate);
				$result->setData([
					'TEXT' => $smsTitle,
					'PAYMENT' => $order->getField('ACCOUNT_NUMBER')
				]);
			}
			else
			{
				$result->addError(new Error('Error retrieving url info'));
			}
		}
		else
		{
			$result->addError(new Error('Public url is not available'));
		}

		return $result;
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
				'select' => ['ID'],
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
		ImOpenLinesManager::getInstance()->updateDealAfterCreation($dealId, $sessionId);
	}

	private function setDefaultReceivePaymentMode(string $mode): void
	{
		\CUserOptions::SetOption('crm', 'receive_payment_mode', $mode);
	}
}
