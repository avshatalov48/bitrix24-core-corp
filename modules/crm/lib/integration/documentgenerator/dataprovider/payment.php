<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\Discount;
use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProvider\User;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Repository\PaymentRepository;

class Payment extends CrmEntityDataProvider
{
	/**
	 * @var \Bitrix\Crm\Order\Payment
	 */
	protected $payment;
	protected $products;

	public function __construct($data, array $options = [])
	{
		if (is_array($options) && isset($options['data']))
		{
			// data is fetched from order, just store it
			$this->data = $this->processData($options['data']);
			$source = $this->data['ID'] ?? 0;
		}
		else
		{
			$source = $data;
		}

		parent::__construct($source, $options);
	}

	protected function processData(array $data): array
	{
		$data['PAID'] = DataProviderManager::getInstance()->getLangPhraseValue(
			$this,
			($data['PAID'] ?? '') === 'Y'
				? 'UF_TYPE_BOOLEAN_YES'
				: 'UF_TYPE_BOOLEAN_NO'
		);

		$data['SUM'] = new Money($data['SUM'] ?? '', [
			'CURRENCY_ID' => $data['CURRENCY'] ?? null
		]);

		return $data;
	}

	protected function fetchData()
	{
		if ($this->data === null)
		{
			$this->data = [];
			$paymentId = (int)$this->source;
			if ($paymentId <= 0)
			{
				return;
			}
			$this->payment = PaymentRepository::getInstance()->getById($paymentId);
			if ($this->payment)
			{
				$this->data = $this->processData($this->payment->getFields()->getValues());
			}
		}
	}

	public function getLangPhrasesPath()
	{
		return Path::getDirectory(__FILE__).'/../phrases';
	}

	public function getFields(): array
	{
		if ($this->fields === null)
		{
			$this->fields = [
				'ID' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_ID'),
				],
				'ACCOUNT_NUMBER' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_ACCOUNT_NUMBER'),
				],
				'PAID' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_PAID'),
				],
				'DATE_PAID' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_DATE_PAID'),
					'TYPE' => static::FIELD_TYPE_DATE,
				],
				'PAY_SYSTEM_ID' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_PAY_SYSTEM_ID'),
				],
				'PAY_VOUCHER_NUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_PAY_VOUCHER_NUM'),
				],
				'PAY_VOUCHER_DATE' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_PAY_VOUCHER_DATE'),
					'TYPE' => static::FIELD_TYPE_DATE,
				],
				'DATE_PAY_BEFORE' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_DATE_PAY_BEFORE'),
					'TYPE' => static::FIELD_TYPE_DATE,
				],
				'DATE_BILL' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_DATE_BILL'),
				],
				'XML_ID' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_XML_ID'),
				],
				'SUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_SUM'),
				],
				'CURRENCY' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_CURRENCY'),
				],
				'PAY_SYSTEM_NAME' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_PAY_SYSTEM_NAME'),
				],
				'PAY_RETURN_NUM' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_PAY_RETURN_NUM'),
				],
				'PAY_RETURN_DATE' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_PAY_RETURN_DATE'),
					'TYPE' => static::FIELD_TYPE_DATE,
				],
				'PAY_RETURN_COMMENT' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_PAY_RETURN_COMMENT'),
				],
				'RESPONSIBLE' => [
					'TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_ORDER_ASSIGNED_TITLE'),
					'PROVIDER' => User::class,
					'OPTIONS' => [
						'FORMATTED_NAME_FORMAT' => [
							'format' => CrmEntityDataProvider::getNameFormat(),
						]
					],
					'VALUE' => [$this, 'getResponsibleId'],
				],
				'COMMENTS' => ['TITLE' => Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_QUOTE_COMMENTS_TITLE')]
			];

			if (!$this->isLightMode())
			{
				$this->fields['ORDER'] = [
					'TITLE' => Order::getLangName(),
					'PROVIDER' => Order::class,
					'OPTIONS' => [
						'isLightMode' => true,
						'enableMyCompany' => true,
					],
					'VALUE' => [$this, 'getOrderId'],
				];

				Loc::loadMessages(Path::combine(__DIR__, 'productsdataprovider.php'));
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
			}
		}

		return $this->fields;
	}

	public function getResponsibleId(): ?int
	{
		if ($this->payment)
		{
			return (int)$this->payment->getField('RESPONSIBLE_ID');
		}

		return (int)($this->data['RESPONSIBLE_ID'] ?? null);
	}

	public function getOrderId(): ?int
	{
		if ($this->payment)
		{
			return $this->payment->getOrderId();
		}

		return $this->data['ORDER_ID'] ?? null;
	}

	public function getCrmOwnerType(): int
	{
		return \CCrmOwnerType::OrderPayment;
	}

	protected function getUserFieldEntityID(): ?string
	{
		return null;
	}

	protected function getTableClass(): ?string
	{
		return null;
	}

	public static function getLangName(): ?string
	{
		return Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_PAYMENT_TITLE');
	}

	public function hasAccess($userId): bool
	{
		$orderId = $this->getOrderId();
		if ($orderId > 0)
		{
			return Service\Container::getInstance()->getUserPermissions($userId)->checkReadPermissions(
				\CCrmOwnerType::Order,
				(int)$orderId
			);
		}

		return false;
	}

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
					$product = new Product($productData);
					$product->setParentProvider($this);
					$products[] = $product;
				}
			}
			$this->products = $products;
		}

		return $this->products;
	}

	protected function loadProductsData()
	{
		$result = [];
		if ($this->payment)
		{
			$basketItems = $this->payment->getPayableItemCollection()->getBasketItems();
			foreach ($basketItems as $basketItem)
			{
				$result[] = Order::getProductProviderDataByBasketItem(
					$basketItem->getEntityObject()->toArray(),
					new ItemIdentifier(
						\CCrmOwnerType::OrderPayment,
						$this->payment->getId(),
					),
					$this->data['CURRENCY'] ?? \CCrmCurrency::GetDefaultCurrencyID()
				);
			}
		}

		return $result;
	}

	public function getTimelineItemIdentifier(): ?ItemIdentifier
	{
		$entityTypeId = \CCrmOwnerType::Order;
		$entityId = (int)$this->getOrderId();
		if ($entityTypeId > 0 && $entityId > 0)
		{
			return new ItemIdentifier($entityTypeId, $entityId);
		}

		return null;
	}
}
