<?php

namespace Bitrix\SalesCenter;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Catalog;
use Bitrix\Sale;
use Bitrix\Sale\Helpers\Order;

class OrderFacade
{
	private $errorCollection;

	private $fields;

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
			'PRODUCT_ID' => $fields['productId'] ?? 0,
			'OFFER_ID' => $fields['productId'],
			'BASE_PRICE' => $fields['basePrice'],
			'PRICE' => $fields['price'],
			'CUSTOM_PRICE' => $fields['isCustomPrice'] === 'Y' ? 'Y' : 'N',
			'DISCOUNT_PRICE' => 0,
			'MEASURE_NAME' => $fields['measureName'],
			'MEASURE_CODE' => $fields['measureCode'],
		];

		if ($fields['module'] === 'catalog')
		{
			$item['MODULE'] = 'catalog';
			$item['PRODUCT_PROVIDER_CLASS'] = Catalog\Product\Basket::getDefaultProviderName();
		}

		if ($fields['discount'] > 0)
		{
			if ($fields['discountType'] === 'currency')
			{
				$item['DISCOUNT_PRICE'] = $fields['discount'];
			}
			else
			{
				$item['DISCOUNT_PRICE'] = Sale\PriceMaths::roundPrecision($item['BASE_PRICE'] * $fields['discount'] / 100);
			}

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
				$productId = $this->createProduct($item);
				if ($productId)
				{
					$basketItems[$code]['MODULE'] = 'catalog';
					$basketItems[$code]['PRODUCT_PROVIDER_CLASS'] = Catalog\Product\Basket::getDefaultProviderName();
					$basketItems[$code]['PRODUCT_ID'] = $productId;

					$encodedFields = Main\Web\Json::decode($item['encodedFields']);

					$encodedFields['MODULE'] = $basketItems[$code]['MODULE'];
					$encodedFields['PRODUCT_PROVIDER_CLASS'] = $basketItems[$code]['PRODUCT_PROVIDER_CLASS'];
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
	 * @param bool $fallOnAnyError
	 * @return Sale\Order
	 */
	public function buildOrder($fallOnAnyError = false)
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
		if ($fallOnAnyError && !empty($errors))
		{
			$this->errorCollection->add($errors);
			return null;
		}

		if (!$order)
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
		if(Integration\Bitrix24Manager::getInstance()->isPaymentsLimitReached())
		{
			$this->errorCollection->setError(new Main\Error('You have reached limit of payments for your tariff'));
			return;
		}
	}

	private function createProduct(array $fields)
	{
		$basketFields = Main\Web\Json::decode($fields['encodedFields']);

		if (empty($basketFields['CURRENCY']) || empty($basketFields['PRICE']))
		{
			return null;
		}

		$catalogIblockId = Main\Config\Option::get('crm', 'default_product_catalog_id');
		if (!$catalogIblockId)
		{
			return null;
		}

		$iblockElementFields = [
			'NAME' => $basketFields['NAME'],
			'ACTIVE' => 'Y',
			'IBLOCK_ID' => $catalogIblockId
		];

		if (!empty($fields['image']))
		{
			$files = [];
			foreach ($fields['image'] as $image)
			{
				$files[] = \CAllIBlock::makeFilePropArray($image['data']);
			}

			$propertyData = \CIBlock::GetProperties($catalogIblockId, [], ['CODE'=>'MORE_PHOTO'])->Fetch();
			$isMorePhoto = $propertyData ? true : false;
			if ($isMorePhoto)
			{
				$iblockElementFields['PROPERTY_VALUES'] = [
					'MORE_PHOTO' => $files,
				];
			}

			$iblockElementFields['DETAIL_PICTURE'] = current($files)['VALUE'];
		}

		$elementObject = new \CIBlockElement();
		$productId = $elementObject->Add($iblockElementFields);

		if ((int)$productId <= 0)
		{
			return null;
		}

		$addFields = [
			'ID' => $productId,
			'QUANTITY_TRACE' => Catalog\ProductTable::STATUS_DEFAULT,
			'CAN_BUY_ZERO' => Catalog\ProductTable::STATUS_DEFAULT,
			'WEIGHT' => 0,
		];

		if (!empty($basketFields['MEASURE_CODE']))
		{
			$measureRaw = Catalog\MeasureTable::getList(array(
				'select' => array('ID'),
				'filter' => ['CODE' => $basketFields['MEASURE_CODE']],
				'limit' => 1
			));

			if ($measure = $measureRaw->fetch())
			{
				$addFields['MEASURE'] = $measure['ID'];
			}
		}

		if (
			Main\Config\Option::get('catalog', 'default_quantity_trace') === 'Y'
			&& Main\Config\Option::get('catalog', 'default_can_buy_zero') !== 'Y'
		)
		{
			$addFields['QUANTITY'] = $basketFields['QUANTITY'];
		}

		$r = Catalog\Model\Product::add($addFields);
		if (!$r->isSuccess())
			return null;

		Catalog\MeasureRatioTable::add(array(
			'PRODUCT_ID' => $productId,
			'RATIO' => 1
		));

		$priceBaseGroup = \CCatalogGroup::GetBaseGroup();
		$r = Catalog\Model\Price::add([
			'PRODUCT_ID' => $productId,
			'CATALOG_GROUP_ID' => $priceBaseGroup['ID'],
			'CURRENCY' => $basketFields['CURRENCY'],
			'PRICE' => $basketFields['PRICE'],
		]);

		if (!$r->isSuccess())
		{
			return null;
		}

		return $productId;
	}
}