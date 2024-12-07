<?php

declare(strict_types = 1);

namespace Bitrix\CatalogMobile\Controller\DocumentDetails;

use Bitrix\Catalog\StoreDocumentTable;
use Bitrix\CatalogMobile\InventoryControl\DataProvider\DocumentProducts;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Helpers\ReadsApplicationErrors;
use Bitrix\Mobile\UI\EntityEditor\FormWrapper;
use Bitrix\Sale\Internals\ShipmentTable;
use Bitrix\CatalogMobile\EntityEditor\RealizationDocumentProvider;
use Bitrix\Sale\Internals\PaymentTable;
use Bitrix\Crm\Order\TradingPlatform;
use Bitrix\Catalog\Integration\PullManager;
use Bitrix\Sale\Repository\ShipmentRepository;
use Bitrix\Sale\Configuration;
use Bitrix\Sale\Delivery\Services\EmptyDeliveryService;
use Bitrix\Main\Web\Json;
use Bitrix\Crm\Product\Url\ProductBuilder;
use Bitrix\Crm\Order;
use Bitrix\Sale\Helpers\Order\Builder\Director;
use Bitrix\Catalog\v2\Integration\JS\ProductForm\BasketBuilder;
use Bitrix\Catalog\Product\CatalogProvider;
use Bitrix\Sale\Internals\Catalog\ProductTypeMapper;
use Bitrix\Crm;

Loader::requireModule('catalog');

/**
 * Class RealizationDocumentDetails
 *
 * @package Bitrix\CatalogMobile\Controller
 */
class RealizationDocumentDetails extends BaseDocumentDetails
{
	use ReadsApplicationErrors;
	/**
	 * @param int|null $entityId
	 * @param string|null $docType
	 * @return array
	 */
	public function loadMainAction(int $entityId = null, string $docType = null, array $context = []): array
	{
		if (!$this->checkDocumentReadRights($entityId, $docType))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_READ_PERMS')));

			return [];
		}

		$provider = new RealizationDocumentProvider($entityId, $context);

		return [
			'editor' => (new FormWrapper($provider))->getResult(),
		];
	}

	/**
	 * @param int|null $entityId
	 * @param string|null $docType
	 * @return array
	 */
	public function loadProductsAction(int $entityId = null, string $docType = null, array $context = []): array
	{
		if (!$this->checkDocumentReadRights($entityId, $docType))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_READ_PRODUCTS_PERMS')));

			return [];
		}

		return DocumentProducts\Facade::loadByDocumentId($entityId, $docType, $context);
	}

	public function addInternalAction(string $docType, array $data, array $context = []): ?int
	{
		if (!$this->checkDocumentModifyRights(null, $docType))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_ADD_PERMS')));

			return null;
		}

		if (!$docType)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

		if (!$this->validateBarcodes($data))
		{
			return null;
		}

		return $this->saveRealization($data, null, $context);
	}

	private function saveRealization(array $formData, ?int $shipmentId = null, array $context = []): ?int
	{
		$isNew = $shipmentId === null;
		if (!Loader::includeModule('sale'))
		{
			$this->addError(new Error('Module sale is not installed'));

			return null;
		}

		if (!Loader::includeModule('crm'))
		{
			$this->addError(new Error('Module crm is not installed'));

			return null;
		}

		$orderId = null;
		if ($shipmentId)
		{
			$shipment = ShipmentTable::getRow([
				'select' => ['ORDER_ID', 'DEDUCTED'],
				'filter' => [
					'=ID' => $shipmentId,
				],
			]);
			if (!$shipment)
			{
				$this->addError(new Error('Realization nof found'));

				return null;
			}
			$orderId = (int)$shipment['ORDER_ID'];
			unset($shipment);
		}
		else if ($context['paymentId'])
		{
			$payment = PaymentTable::getRow([
				'select' => ['ORDER_ID'],
				'filter' => ['=ID' => (int)$context['paymentId']],
			]);
			if (!$payment)
			{
				$this->addError(new Error('Payment document not found'));

				return null;
			}
			$orderId = (int)$payment['ORDER_ID'];
			unset($payment);
		}
		elseif ($context['orderId'])
		{
			$orderId = (int)$context['orderId'];
		}

		$tradingPlatform = null;
		if (!$orderId)
		{
			$platformCode = TradingPlatform\RealizationDocument::TRADING_PLATFORM_CODE;
			$platform = TradingPlatform\RealizationDocument::getInstanceByCode($platformCode);
			if ($platform->isInstalled())
			{
				$tradingPlatform = $platform->getId();
			}
		}

		$needEnableAutomation = false;
		if (Configuration::isEnableAutomaticReservation())
		{
			Configuration::disableAutomaticReservation();
			$needEnableAutomation = true;
		}

		$orderData = [
			'ID' => $orderId,
			'CLIENT' => [
				'COMPANY_ID' => $formData['COMPANY_ID'][0] ?? null,
				'CONTACT_IDS' => array_map(static fn($id) => (int)$id, $formData['CONTACT_IDS']),
			],
			'RESPONSIBLE_ID' => (int)$formData['RESPONSIBLE_ID'],
			'TRADING_PLATFORM' => $tradingPlatform,
			'SHIPMENT' => [
				[
					'ID' => $shipmentId,
					'RESPONSIBLE_ID' => (int)$formData['RESPONSIBLE_ID'],
					'IS_REALIZATION' => 'Y',
					'DELIVERY_ID' => $shipmentId ? null : EmptyDeliveryService::getEmptyDeliveryServiceId(),
				],
			],
		];

		if (isset($context['ownerId'], $context['ownerTypeId']))
		{
			$orderData['OWNER_ID'] = $context['ownerId'];
			$orderData['OWNER_TYPE_ID'] = $context['ownerTypeId'];
		}

		if (isset($formData['PRODUCTS']))
		{
			$orderData['SHIPMENT'][0]['PRODUCT'] = $this->parseProductsForShipment($formData['PRODUCTS']);
			$orderData['PRODUCT'] = $this->parseProductsForOrder($formData['PRODUCTS']);
		}

		$orderBuilder = Order\Builder\Factory::createBuilderForShipment();
		$director = new Director;
		/** @var Order\Order $order */
		$order = $director->createOrder($orderBuilder, $orderData);
		$errorContainer = $orderBuilder->getErrorsContainer();
		if ($errorContainer && !empty($errorContainer->getErrors()))
		{
			$this->addErrors($errorContainer->getErrors());
		}

		$shipment = $order ? $this->findNewShipment($order) : null;

		$discount = $order->getDiscount();

		$saveOrderResult = $discount->calculate();
		if (!$saveOrderResult->isSuccess())
		{
			$this->addErrors($saveOrderResult->getErrors());
		}

		$saveOrderResult = $order->save();

		if ($needEnableAutomation)
		{
			Configuration::enableAutomaticReservation();
		}

		if (!$saveOrderResult->isSuccess())
		{
			$this->addErrors($saveOrderResult->getErrors());

			return null;
		}

		if (!$shipment && $shipmentId)
		{
			$shipment = $order->getShipmentCollection()->getItemById($shipmentId);
		}

		if (!$shipment)
		{
			$this->addError(new Error('Realization not found'));

			return null;
		}

		$fields = $shipment->getFields()->getValues();
		$fields['DOC_TYPE'] = StoreDocumentTable::TYPE_SALES_ORDERS;
		$pullManagerItems = [
			[
				'id' => $shipment->getId(),
				'data' => [
					'fields' => $fields,
				],
			],
		];
		if ($isNew)
		{
			PullManager::getInstance()->sendDocumentAddedEvent($pullManagerItems);
		}
		else
		{
			PullManager::getInstance()->sendDocumentsUpdatedEvent($pullManagerItems);
		}

		$this->syncShipmentProducts($shipment);

		if (isset($formData['PRODUCTS']))
		{
			$this->updateCatalogProducts($formData['PRODUCTS']);
		}

		return $shipment->getId();
	}

	private function parseProductsForOrder(?array $products = null): array
	{
		if (!$products)
		{
			return [];
		}

		$parsedProducts = [];
		foreach ($products as $productKey => $product)
		{
			$basketCode = $product['basketCode'] ?? 'n' . $productKey;;
			$parsedProducts[$basketCode] = [
				'NAME' => $product['name'],
				'QUANTITY' => (float)$product['amount'],
				'PRODUCT_PROVIDER_CLASS' => '\\' . CatalogProvider::class,
				'MODULE' => 'catalog',
				'BASKET_CODE' => $basketCode,
				'PRODUCT_ID' => $product['productId'],
				'OFFER_ID' => $product['productId'],
				'BASE_PRICE' => $product['price']['sell']['basePrice'],
				'PRICE' => $product['price']['sell']['amount'],
				'VAT_RATE' => $product['price']['vat']['vatRate'],
				'VAT_INCLUDED' => $product['price']['vat']['vatIncluded'],
				'CUSTOM_PRICE' => 'Y',
				'TYPE' => $product['type'] ? ProductTypeMapper::getType($product['type']) : null,
				'DISCOUNT_PRICE' => 0,
				'MEASURE_NAME' => $product['measure']['name'],
				'MEASURE_CODE' => $product['measure']['code'],
				'MANUALLY_EDITED' => 'Y',
			];

			$parsedProducts[$basketCode]['FIELDS_VALUES'] = Json::encode($parsedProducts[$basketCode]);
		}

		return $parsedProducts;
	}

	private function parseProductsForShipment(?array $products = null): array
	{
		if (!$products)
		{
			return [];
		}

		$parsedProducts = [];
		foreach ($products as $productKey => $product)
		{
			$basketCode = $product['basketCode'] ?? 'n' . $productKey;;
			$parsedProducts[$basketCode] = [
				'QUANTITY' => (float)$product['amount'],
				'AMOUNT' => (float)$product['amount'],
				'BASKET_ID' => $basketCode,
				'BASKET_CODE' => $basketCode,
				'BARCODE_INFO' => [
					(int)$product['storeFrom']['id'] => [
						'STORE_ID' => (int)$product['storeFrom']['id'],
						'QUANTITY' => (float)$product['amount'],
						'BARCODE' => [
							[
								'VALUE' => $product['barcode'],
							],
						],
					],
				],
			];
			if (!is_int($product['id']))
			{
				$parsedProducts[$basketCode]['XML_ID'] = uniqid('bx_');
			}
		}

		return $parsedProducts;
	}

	private function syncShipmentProducts(Order\Shipment $shipment): void
	{
		$order = $shipment->getOrder();
		$entityBinding = $order->getEntityBinding();
		if ($entityBinding)
		{
			$productManager = new Order\ProductManager(
				$entityBinding->getOwnerTypeId(),
				$entityBinding->getOwnerId()
			);
			$productManager->setOrder($order);

			$basketItems = $this->prepareBasketItemsForSync($shipment);
			$productManager->syncOrderProducts($basketItems);
		}
	}

	private function prepareBasketItemsForSync(Order\Shipment $shipment): array
	{
		$basketItems = [];

		if (Loader::includeModule('catalog'))
		{
			$formBuilder = new BasketBuilder();

			/** @var Order\ShipmentItem $shipmentItem */
			foreach ($shipment->getShipmentItemCollection() as $shipmentItem)
			{
				$basketItem = $shipmentItem->getBasketItem();
				$product = $basketItem->getFieldValues();

				$item = $formBuilder->loadItemBySkuId((int)$product['PRODUCT_ID']);
				if ($item)
				{
					$item
						->setDetailUrlManagerType(ProductBuilder::TYPE_ID)
						->addAdditionalField('originProductId', (string)$product['PRODUCT_ID'])
						->addAdditionalField('originBasketId', (string)$product['ID'])
						->setName($product['NAME'])
						->setPrice((float)$product['PRICE'])
						->setCode((string)$product['ID'])
						->setBasePrice((float)$product['BASE_PRICE'])
						->setPriceExclusive((float)$product['PRICE'])
						->setQuantity((float)$product['QUANTITY'])
						->setMeasureCode((int)$product['MEASURE_CODE'])
						->setMeasureName($product['MEASURE_NAME'])
						->setTaxIncluded($product['VAT_INCLUDED'])
						->setTaxRate(($product['VAT_RATE'] !== null) ? $product['VAT_RATE'] * 100 : null)
					;

					$basketItems[] = $item->getFields();
				}
			}
		}

		return $basketItems;
	}

	private function findNewShipment(Order\Order $order): ?Order\Shipment
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

	public function updateInternalAction(int $entityId, string $docType, array $data, array $context = []): ?int
	{
		if (!$this->checkDocumentModifyRights($entityId))
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_UPDATE_PERMS')));

			return null;
		}

		if (!$entityId)
		{
			$this->addError(new Error(Loc::getMessage('MOBILE_CONTROLLER_CATALOG_DETAILS_ERROR_NOT_FOUND')));

			return null;
		}

		if (!$this->validateBarcodes($data))
		{
			return null;
		}

		return $this->saveRealization($data, $entityId, $context);
	}

	/**
	 * @param int $entityId
	 * @return array|null
	 */
	public function conductAction(int $entityId, string $docType): ?array
	{
		return $this->setShipped($entityId, 'Y');
	}

	/**
	 * @param int $entityId
	 * @return array|null
	 */
	public function cancelAction(int $entityId, string $docType): ?array
	{
		return $this->setShipped($entityId, 'N');
	}

	private function setShipped(int $id, string $shipped): ?array
	{
		if (!Loader::requireModule('crm'))
		{
			$this->addError(new Error('Module crm is not installed'));

			return null;
		}

		$this->forward(
			Crm\Controller\RealizationDocument::class,
			'setShipped',
			[
				'id' => $id,
				'value' => $shipped
			]
		);

		if (!empty($this->getErrors()))
		{
			$this->extractErrors();

			return null;
		}

		$shipment = ShipmentRepository::getInstance()->getById($id);
		if (!$shipment)
		{
			return null;
		}

		$fields = $shipment->getFields()->getValues();
		$fields['DOC_TYPE'] = StoreDocumentTable::TYPE_SALES_ORDERS;
		PullManager::getInstance()->sendDocumentsUpdatedEvent([
			[
				'id' => $shipment->getId(),
				'data' => [
					'fields' => $fields,
				],
			],
		]);

		return [
			'load' => $this->createLoadResponse(),
		];
	}

	private function extractErrors(): void
	{
		$errors = explode('<br>', $this->getErrors()[0]->getMessage());
		$code = $this->getErrors()[0]->getCode();
		$this->errorCollection->clear();
		foreach ($errors as $error)
		{
			$this->addError(new Error($error, $code));
		}
	}

	protected function getEntityTitle(): string
	{
		$entityId = (int)$this->findInSourceParametersList('entityId');
		$shipment = ShipmentTable::getRow([
			'select' => ['ACCOUNT_NUMBER'],
			'filter' => [
				'=ID' => $entityId,
			],
		]);
		if ($shipment)
		{
			return Loc::getMessage(
				'MOBILE_CONTROLLER_CATALOG_DETAILS_REALIZATION_TITLE',
				[
					'#DOCUMENT_ID#' => $shipment['ACCOUNT_NUMBER'],
				]
			);
		}

		return '';
	}
}
