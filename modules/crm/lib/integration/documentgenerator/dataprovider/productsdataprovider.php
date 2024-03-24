<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\Integration\DocumentGenerator\ProductLoader;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\PayableBasketItem;
use Bitrix\Crm\ProductType;
use Bitrix\Crm\UI\Barcode;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Dictionary;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Repository\PaymentRepository;

abstract class ProductsDataProvider extends CrmEntityDataProvider
{
	/** @var Product[] */
	protected $products;
	/** @var Tax[] */
	protected $taxes;

	protected function getCrmProductOwnerType()
	{
		return \CCrmOwnerTypeAbbr::ResolveByTypeID($this->getCrmOwnerType());
	}

	/**
	 * @return array
	 */
	public function getFields()
	{
		if($this->fields === null)
		{
			parent::getFields();

			if($this->hasStatusField())
			{
				$this->fields['STATUS'] = [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_STATUS_TITLE'),
					'VALUE' => [$this, 'getStatus'],
				];
			}

			if(!$this->isLightMode())
			{
				$this->fields['PRODUCTS'] = [
					'PROVIDER' => ArrayDataProvider::class,
					'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_PRODUCTS_TITLE'),
					'OPTIONS' => [
						'ITEM_PROVIDER' => Product::class,
						'ITEM_NAME' => 'PRODUCT',
						'ITEM_TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_PRODUCT_TITLE'),
					],
					'VALUE' => [$this, 'loadProducts'],
				];
				$this->fields['TAXES'] = [
					'PROVIDER' => ArrayDataProvider::class,
					'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TAXES_TITLE'),
					'OPTIONS' => [
						'ITEM_PROVIDER' => Tax::class,
						'ITEM_NAME' => 'TAX',
						'ITEM_TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TAX_TITLE'),
					],
					'VALUE' => [$this, 'loadTaxes'],
				];
			}
			$this->fields['CURRENCY_ID'] = [
				'VALUE' => [$this, 'getCurrencyId'],
			];
			$this->fields['CURRENCY_NAME'] = [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_CURRENCY_NAME_TITLE'),
				'VALUE' => [$this, 'getCurrencyName'],
			];
		}

		$this->fields = array_merge($this->fields, $this->getTotalFields());

		return $this->fields;
	}

	protected function fetchData()
	{
		parent::fetchData();
		if(!$this->isLightMode())
		{
			$this->loadProducts();
			$this->calculateTotalFields();
		}
	}

	/**
	 * Create product entity.
	 *
	 * @param array $data
	 *
	 * @return Product
	 */
	protected function createProduct(array $data): Product
	{
		return new Product($data);
	}

	/**
	 * @return Product[]
	 */
	public function loadProducts()
	{
		if($this->products === null)
		{
			$products = [];
			if($this->isLoaded())
			{
				$productsData = $this->loadProductsData();
				foreach($productsData as $productData)
				{
					DocumentGeneratorManager::getInstance()->getProductLoader()->addRow($productData);
					$product = $this->createProduct($productData);
					$product->setParentProvider($this);
					$products[] = $product;
				}
			}
			$this->products = $products;
		}

		return $this->products;
	}

	/**
	 * @return array
	 */
	protected function loadProductsData()
	{
		$result = [];

		$payment = null;
		$paymentId = (int)($this->getOptions()['VALUES'][DocumentGeneratorManager::VALUE_PAYMENT_ID] ?? 0);
		if ($paymentId > 0)
		{
			$payment = PaymentRepository::getInstance()->getById($paymentId);
		}
		if ($payment)
		{
			$basketItems = $payment->getPayableItemCollection()->getBasketItems();
			/** @var PayableBasketItem $basketItem */
			foreach ($basketItems as $basketItem)
			{
				$productData = $basketItem->getEntityObject()->toArray();
				$productData['QUANTITY'] = $basketItem->getQuantity();

				$item = Order::getProductProviderDataByBasketItem(
					$productData,
					new ItemIdentifier(
						\CCrmOwnerType::OrderPayment,
						$payment->getId(),
					),
					$this->getCurrencyId()
				);

				if (!$this->isProductVariantSupported($item['PRODUCT_VARIANT']))
				{
					continue;
				}

				$result[] = $item;

				DocumentGeneratorManager::getInstance()->getProductLoader()->addRow($productData);
			}
		}
		else
		{
			$crmProducts = \CAllCrmProductRow::LoadRows($this->getCrmProductOwnerType(), $this->source);
			foreach($crmProducts as $crmProduct)
			{
				$productVariant = $this->getProductVariantByType((int)$crmProduct['TYPE']);
				if (!$this->isProductVariantSupported($productVariant))
				{
					continue;
				}

				if($crmProduct['TAX_INCLUDED'] !== 'Y')
				{
					$crmProduct['PRICE'] = $crmProduct['PRICE_EXCLUSIVE'];
				}

				$result[] = [
					'ID' => $crmProduct['ID'],
					'NAME' => $crmProduct['PRODUCT_NAME'],
					'PRODUCT_ID' => $crmProduct['PRODUCT_ID'],
					'QUANTITY' => $crmProduct['QUANTITY'],
					'PRICE' => $crmProduct['PRICE'],
					'DISCOUNT_RATE' => $crmProduct['DISCOUNT_RATE'],
					'DISCOUNT_SUM' => $crmProduct['DISCOUNT_SUM'],
					'TAX_RATE' => $crmProduct['TAX_RATE'],
					'TAX_INCLUDED' => $crmProduct['TAX_INCLUDED'],
					'PRODUCT_VARIANT' => $productVariant,
					'SORT' => $crmProduct['SORT'],
					'MEASURE_CODE' => $crmProduct['MEASURE_CODE'],
					'MEASURE_NAME' => $crmProduct['MEASURE_NAME'],
					'OWNER_ID' => $this->source,
					'OWNER_TYPE' => $this->getCrmProductOwnerType(),
					'CUSTOMIZED' => $crmProduct['CUSTOMIZED'],
					'DISCOUNT_TYPE_ID' => $crmProduct['DISCOUNT_TYPE_ID'],
					'CURRENCY_ID' => $this->getCurrencyId(),
				];
			}
		}

		return $result;
	}

	private static function getProductVariantByType(int $type) : string
	{
		if ($type === ProductType::TYPE_SERVICE)
		{
			return Dictionary\ProductVariant::SERVICE;
		}

		return Dictionary\ProductVariant::GOODS;
	}

	protected function isProductVariantSupported(string $productVariant) : bool
	{
		$options = $this->getOptions()['VALUES'] ?? [];

		if (empty($options['productsTableVariant']))
		{
			return true;
		}

		return $options['productsTableVariant'] === $productVariant;
	}

	/**
	 * @deprecated
	 * @see ProductLoader::loadIblockValues()
	 * @param array $products
	 */
	protected function loadIblockProductsData(array &$products)
	{
	}

	protected function calculateTotalFields()
	{
		$currencyID = $this->getCurrencyId();
		if(empty($this->products))
		{
			$sum = $this->data['OPPORTUNITY'] ?? 0;
			$this->data['TOTAL_RAW'] = $this->data['TOTAL_SUM'] = $sum;
			return;
		}
		$crmProducts = [];
		foreach($this->getTotalFields() as $placeholder => $field)
		{
			if(isset($field['FORMAT']) && (!isset($field['FORMAT']['WORDS']) || $field['FORMAT']['WORDS'] !== true))
			{
				$this->data[$placeholder] = 0;
			}
		}
		foreach($this->products as $product)
		{
			$this->data['TOTAL_DISCOUNT'] += $product->getRawValue('QUANTITY') * $product->getRawValue('DISCOUNT_SUM');
			$this->data['TOTAL_QUANTITY'] += $product->getRawValue('QUANTITY');
			$this->data['TOTAL_RAW'] += $product->getRawValue('PRICE_RAW_SUM');
			$this->data['TOTAL_RAW_BEFORE_DISCOUNT'] += $product->getRawValue('QUANTITY') * $product->getRawValue('PRICE_RAW_NETTO');
			$crmProducts[] = DataProviderManager::getInstance()->getArray($product, ['rawValue' => true]);
		}
		$calcOptions = [];
		if(\CCrmTax::isTaxMode())
		{
			$calcOptions = [
				'LOCATION_ID' => $this->getLocationId(),
				'ALLOW_LD_TAX' => 'Y',
			];
		}
		$calculate = \CCrmSaleHelper::Calculate($crmProducts, $currencyID, $this->getPersonTypeID(), false, 's1', $calcOptions);

		if (
			isset($calculate['TAX_LIST'])
			&& is_array($calculate['TAX_LIST'])
			&& \CCrmTax::isTaxMode()
		)
		{
			foreach($calculate['TAX_LIST'] as $taxInfo)
			{
				$tax = new Tax([
					'NAME' => $taxInfo['NAME'],
					'VALUE' => new Money($taxInfo['VALUE_MONEY'], ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]),
					'NETTO' => 0,
					'BRUTTO' => 0,
					'RATE' => (float)$taxInfo['VALUE'],
					'TAX_INCLUDED' => $taxInfo['IS_IN_PRICE'],
					'MODE' => Tax::MODE_TAX,
				]);
				$tax->setParentProvider($this);
				$this->taxes[] = $tax;
			}
		}
		$this->data['TOTAL_SUM'] = $calculate['PRICE'];
		$this->data['TOTAL_TAX'] = $calculate['TAX_VALUE'];
		$this->data['TOTAL_BEFORE_TAX'] = $this->data['TOTAL_SUM'] - $this->data['TOTAL_TAX'];
		$this->data['TOTAL_BEFORE_DISCOUNT'] = $this->data['TOTAL_BEFORE_TAX'] + $this->data['TOTAL_DISCOUNT'];
	}

	/**
	 * @return array
	 */
	protected function getTotalFields()
	{
		$currencyID = $this->getCurrencyId();
		$totalFields = [
			'TOTAL_DISCOUNT' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_DISCOUNT_TITLE'),
				'TYPE' => Money::class,
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true],
			],
			'TOTAL_SUM' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_SUM_TITLE'),
				'TYPE' => Money::class,
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true],
			],
			'TOTAL_RAW' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_RAW_TITLE'),
				'TYPE' => Money::class,
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true],
			],
			'TOTAL_TAX' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_TAX_TITLE'),
				'TYPE' => Money::class,
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true],
			],
			'TOTAL_TAX_WORDS' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_TAX_WORDS_TITLE'),
				'TYPE' => Money::class,
				'VALUE' => [$this, 'getSumWithoutWords'],
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WORDS' => true],
			],
			'TOTAL_BEFORE_TAX' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_BEFORE_TAX_TITLE'),
				'TYPE' => Money::class,
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true],
			],
			'TOTAL_RAW_BEFORE_DISCOUNT' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_RAW_BEFORE_DISCOUNT_TITLE'),
				'TYPE' => Money::class,
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true],
			],
			'TOTAL_BEFORE_TAX_WORDS' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_BEFORE_TAX_WORDS_TITLE'),
				'TYPE' => Money::class,
				'VALUE' => [$this, 'getSumWithoutWords'],
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WORDS' => true],
			],
			'TOTAL_BEFORE_DISCOUNT' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_BEFORE_DISCOUNT_TITLE'),
				'TYPE' => Money::class,
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true],
			],
			'TOTAL_SUM_WORDS' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_SUM_WORDS_TITLE'),
				'TYPE' => Money::class,
				'VALUE' => [$this, 'getSumWithoutWords'],
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WORDS' => true],
			],
			'TOTAL_QUANTITY' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_QUANTITY_TITLE'),
			],
			'TOTAL_QUANTITY_WORDS' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_QUANTITY_WORDS_TITLE'),
				'TYPE' => Money::class,
				'VALUE' => [$this, 'getSumWithoutWords'],
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WORDS' => true, 'NO_SIGN' => true],
			],
			'TOTAL_ROWS' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_ROWS_TITLE'),
				'VALUE' => [$this, 'getTotalRows'],
			],
			'TOTAL_ROWS_WORDS' => [
				'TITLE' => GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TOTAL_ROWS_WORDS_TITLE'),
				'TYPE' => Money::class,
				'VALUE' => [$this, 'getTotalRows'],
				'FORMAT' => ['CURRENCY_ID' => $currencyID, 'WORDS' => true, 'NO_SIGN' => true],
			],

		];

		return $totalFields;
	}

	/**
	 * @return int
	 */
	public function getTotalRows()
	{
		if(is_array($this->products) || $this->products instanceof \Countable)
		{
			return count($this->products);
		}

		return 0;
	}

	/**
	 * @return int
	 */
	protected function getPersonTypeID()
	{
		$personTypes = \CCrmPaySystem::getPersonTypeIDs();
		$personTypeId = $personTypes['CONTACT'];
		if($this->data['COMPANY_ID'] > 0)
		{
			$personTypeId = $personTypes['COMPANY'];
		}

		return $personTypeId;
	}

	/**
	 * @return string
	 */
	public function getCurrencyId()
	{
		if(empty($this->data['CURRENCY_ID']))
		{
			$this->data['CURRENCY_ID'] = \CCrmCurrency::GetBaseCurrencyID();
		}
		return $this->data['CURRENCY_ID'];
	}

	/**
	 * @return string
	 */
	public function getCurrencyName()
	{
		return \CCrmCurrency::GetCurrencyName($this->getCurrencyId());
	}

	/**
	 * @return array|Tax[]|null
	 */
	public function loadTaxes()
	{
		$this->loadProducts();
		if(!empty($this->data))
		{
			if($this->taxes === null)
			{
				$this->taxes = [];
				if(\CCrmTax::isTaxMode())
				{
					return $this->taxes;
				}
				$taxes = $this->loadVatTaxesInfo();
				foreach($taxes as $tax)
				{
					$tax = new Tax($tax);
					$tax->setParentProvider($this);
					$this->taxes[] = $tax;
				}
			}
		}

		return $this->taxes;
	}

	/**
	 * @return array
	 */
	protected function loadVatTaxesInfo()
	{
		$taxes = $taxNames = [];
		$taxInfos = \CCrmTax::GetVatRateInfos();
		foreach($taxInfos as $taxInfo)
		{
			$taxNames[$taxInfo['VALUE']] = $taxInfo['NAME'];
		}
		foreach($this->products as $product)
		{
			$rate = $product->getRawValue('TAX_RATE');
			if (
				$rate !== false
				&& (
					$rate > 0
					|| ($rate == 0 && isset($taxNames[$rate]))
				)
			)
			{
				if(!isset($taxes[$rate]))
				{
					$name = GetMessage('CRM_DOCGEN_PRODUCTSDATAPROVIDER_TAX_VAT_NAME');
					if(isset($taxNames[$rate]))
					{
						$name = $taxNames[$rate];
					}
					$taxes[$rate] = [
						'NAME' => $name,
						'VALUE' => 0,
						'NETTO' => 0,
						'BRUTTO' => 0,
						'RATE' => $rate,
						'TAX_INCLUDED' => $product->getRawValue('TAX_INCLUDED'),
						'MODE' => Tax::MODE_VAT,
					];
				}
				$taxes[$product->getRawValue('TAX_RATE')]['VALUE'] += $product->getRawValue('TAX_VALUE_SUM');
				$taxes[$product->getRawValue('TAX_RATE')]['NETTO'] += $product->getRawValue('PRICE_EXCLUSIVE_SUM');
				$taxes[$product->getRawValue('TAX_RATE')]['BRUTTO'] += $product->getRawValue('PRICE_SUM');
			}
		}
		$currencyID = $this->getCurrencyId();
		foreach($taxes as &$tax)
		{
			$tax['VALUE'] = new Money($tax['VALUE'], ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]);
			$tax['NETTO'] = new Money($tax['NETTO'], ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]);
			$tax['BRUTTO'] = new Money($tax['BRUTTO'], ['CURRENCY_ID' => $currencyID, 'WITH_ZEROS' => true]);
		}

		return $taxes;
	}

	/**
	 * @return mixed
	 */
	protected function getLocationId()
	{
		if($this->isLoaded())
		{
			return $this->data['LOCATION_ID'] ?? null;
		}

		return null;
	}

	protected function getHiddenFields()
	{
		return array_merge(parent::getHiddenFields(), [
			'CURRENCY_ID',
			'LOCATION_ID',
			Item::FIELD_NAME_PRODUCTS,
		]);
	}

	/**
	 * @param string $placeholder
	 * @return bool|mixed
	 */
	public function getSumWithoutWords($placeholder)
	{
		if(!is_string($placeholder) || empty($placeholder))
		{
			return false;
		}
		$shortPlaceholder = str_replace('_WORDS', '', $placeholder);
		// prevent recursion
		if($shortPlaceholder != $placeholder)
		{
			return $this->getRawValue($shortPlaceholder);
		}

		return false;
	}

	protected function hasStatusField(): bool
	{
		return ($this->getStatusEntityId() !== null);
	}

	protected function getStatusEntityId(): ?string
	{
		return null;
	}

	public function getStatus(): ?string
	{
		$statusId = (string) $this->data['STATUS_ID'];
		$crmStatus = new \CCrmStatus($this->getStatusEntityId());

		$data = $crmStatus->GetStatusByStatusId($statusId);

		return $data['NAME'] ?? null;
	}

	public function prepareTransactionData(): Barcode\Payment\TransactionData
	{
		$transactionData = parent::prepareTransactionData();

		$sum = $this->getRawValue('TOTAL_SUM');
		$optionValues = $this->getOptions()['VALUES'] ?? [];
		$templatePlaceholder = DataProviderManager::getInstance()->valueToPlaceholder('TOTAL_SUM');
		if (isset($optionValues[$templatePlaceholder]))
		{
			$sum = $optionValues[$templatePlaceholder];
		}

		if (is_numeric($sum))
		{
			$transactionData->setSum((float)$sum);
		}

		return $transactionData;
	}
}
