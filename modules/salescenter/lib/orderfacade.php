<?php

namespace Bitrix\SalesCenter;

use Bitrix\Main;
use Bitrix\Catalog;
use Bitrix\Sale;
use Bitrix\Sale\Helpers\Order;

/**
 * Class OrderFacade
 * @package Bitrix\SalesCenter
 */
class OrderFacade
{
	private $errorCollection;

	private $fields;

	/** @var ProductCreatorService */
	private $productCreatorService;

	public function __construct()
	{
		$this->errorCollection = new Main\ErrorCollection();

		$this->fields = [
			'SITE_ID' => SITE_ID,
			'PRODUCT' => [],
		];

		$deliveryId = Integration\SaleManager::getInstance()->getEmptyDeliveryServiceId();
		if ($deliveryId > 0)
		{
			$this->fields['SHIPMENT'] = [
				[
					'DELIVERY_ID' => $deliveryId,
					'ALLOW_DELIVERY' => 'Y',
				]
			];
		}

		$this->productCreatorService = new ProductCreatorService();
	}

	public function setResponsibleId($responsibleId)
	{
		$this->fields['RESPONSIBLE_ID'] = (int) $responsibleId;

		return $this;
	}

	public function setClientByCrmOwner($ownerTypeId, $ownerId)
	{
		$client = Integration\CrmManager::getInstance()->getClientInfo($ownerTypeId, $ownerId);;

		if(!empty($client['DEAL_ID']))
		{
			$formData['DEAL_ID'] = $client['DEAL_ID'];
			unset($client['DEAL_ID']);
		}

		$this->fields['CLIENT'] = $client;

		return $this;
	}

	public function setFields($fields)
	{
		foreach ($fields as $name => $value)
		{
			$this->fields[$name] = $value;
		}

	}

	public function getField($name)
	{
		return $this->fields[$name];
	}

	public function addProduct(array &$product)
	{
		if (!isset($product['code']) || !$product['code'])
		{
			$count = count($this->fields['PRODUCT']);
			$product['code'] = 'n'.($count + 1);
		}

		$product['FIELDS_VALUES'] = Main\Web\Json::encode($this->prepareProduct($product));

		$this->fields['PRODUCT'][$product['code']] = $product;

		return $this;
	}

	private function prepareProduct($fields)
	{
		$item = [
			'NAME' => $fields['name'],
			'QUANTITY' => (float)$fields['quantity'] > 0 ? (float)$fields['quantity'] : 1,
			'PRODUCT_PROVIDER_CLASS' => '',
			'SORT' => (int)$fields['sort'],
			'PRODUCT_ID' => $fields['skuId'] ?? $fields['productId'] ?? 0,
			'BASE_PRICE' => $fields['basePrice'],
			'PRICE' => $fields['priceExclusive'] ?? $fields['price'],
			'CUSTOM_PRICE' => $fields['isCustomPrice'] === 'Y' ? 'Y' : 'N',
			'DISCOUNT_PRICE' => 0,
			'MEASURE_NAME' => $fields['measureName'],
			'MEASURE_CODE' => (int)$fields['measureCode'],
			'MANUALLY_EDITED' => 'Y',
		];

		if ($fields['module'] === 'catalog')
		{
			$item['MODULE'] = 'catalog';
			$item['PRODUCT_PROVIDER_CLASS'] = Catalog\Product\Basket::getDefaultProviderName();
		}

		if ($fields['discount'] > 0)
		{
			$item['DISCOUNT_PRICE'] = $fields['discount'];
			$item['CUSTOM_PRICE'] = 'Y';
			$item['PRICE'] = $item['BASE_PRICE'] - $item['DISCOUNT_PRICE'];
		}

		if ($fields['isCreatedProduct'] === 'Y' || !$fields['productId'])
		{
			$item['MANUALLY_EDITED'] = 'Y';
		}

		return $item;
	}

	protected function processBasketItems(array $basketItems)
	{
		foreach ($basketItems as $code => $item)
		{
			if (empty($item['encodedFields']))
			{
				continue;
			}

			if ($item['isCreatedProduct'] === 'Y')
			{
				$productId = $this->productCreatorService->createProduct($item);

				if ($productId)
				{
					$basketItems[$code]['productId'] = $productId;

					$encodedFields = Main\Web\Json::decode($item['encodedFields']);

					$encodedFields['MODULE'] = 'catalog';
					$encodedFields['PRODUCT_PROVIDER_CLASS'] = Catalog\Product\Basket::getDefaultProviderName();
					$encodedFields['PRODUCT_ID'] = $productId;

					$basketItems[$code]['encodedFields'] = Main\Web\Json::encode($encodedFields);
				}
			}
		}

		unset($item);

		return $basketItems;
	}

	/*
	 * Save.
	 *
	 * @return Main\Result
	 */
	public function saveOrder()
	{
		$this->errorCollection->clear();

		$this->checkModules();

		if (Integration\Bitrix24Manager::getInstance()->isPaymentsLimitReached())
		{
			$this->errorCollection->setError(
				new Main\Error('You have reached limit of payments for your tariff')
			);
			return null;
		}

		if ($this->errorCollection->count() > 0)
		{
			return null;
		}

		$this->fields['PRODUCT'] = $this->processBasketItems($this->fields['PRODUCT']);

		$order = $this->buildOrder();
		if ($order === null)
		{
			return null;
		}

		$deal = $order->getDealBinding();
		if ($deal === null)
		{
			$this->registerDealCreator();
		}

		$resultSaving = $order->save();
		if ($resultSaving->isSuccess())
		{
			Integration\Bitrix24Manager::getInstance()->increasePaymentsCount();
		}
		else
		{
			$this->errorCollection->add($resultSaving->getErrors());
		}

		return $order;
	}

	protected function registerDealCreator()
	{
		$eventManager = Main\EventManager::getInstance();
		$eventManager->addEventHandler(
			'sale',
			'OnSaleOrderEntitySaved',
			[Integration\SaleManager::class, 'OnSaleOrderEntitySaved']
		);
	}

	/**
	 * @param bool $getErrorsAnyway
	 * @return Sale\Order
	 */
	public function buildOrder($getErrorsAnyway = false)
	{
		$settings =	[
			'createUserIfNeed' => Order\Builder\SettingsContainer::SET_ANONYMOUS_USER,
			'acceptableErrorCodes' => [],
			'cacheProductProviderData' => true,
		];
		$builderSettings = new Order\Builder\SettingsContainer($settings);
		$orderBuilder = new Builder\OrderBuilder($builderSettings);
		$director = new Order\Builder\Director;

		/** @var Sale\Order $order */
		$order = $director->createOrder($orderBuilder, $this->fields);

		$errors = $orderBuilder->getErrorsContainer()->getErrors();

		if (!$order || $getErrorsAnyway)
		{
			$this->errorCollection->add($errors);
		}

		return $order;
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function hasErrors()
	{
		return count($this->getErrors()) > 0;
	}

	private function checkModules()
	{
		if (!Main\Loader::includeModule('crm'))
		{
			$this->errorCollection->setError(new Main\Error('module "crm" is not installed.'));
			return;
		}
		if (!Main\Loader::includeModule('catalog'))
		{
			$this->errorCollection->setError(new Main\Error('module "catalog" is not installed.'));
			return;
		}
		if (!Main\Loader::includeModule('sale'))
		{
			$this->errorCollection->setError(new Main\Error('module "sale" is not installed.'));
			return;
		}
	}
}
