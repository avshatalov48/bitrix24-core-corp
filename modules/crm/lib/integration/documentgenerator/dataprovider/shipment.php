<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\DataProvider;

use Bitrix\Crm\Integration\DocumentGenerator\Value\Money;
use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Order\DeliveryStatus;
use Bitrix\Crm\Order\ShipmentItem;
use Bitrix\Crm\Service;
use Bitrix\DocumentGenerator\DataProvider\ArrayDataProvider;
use Bitrix\DocumentGenerator\DataProvider\User;
use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Internals\BasketTable;
use Bitrix\Sale\Repository\ShipmentRepository;

class Shipment extends CrmEntityDataProvider
{
	/**
	 * @var \Bitrix\Crm\Order\Shipment
	 */
	protected $shipment;
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
		if (!empty($data['STATUS_ID']))
		{
			$data['STATUS_ID'] = DeliveryStatus::getAllStatusesNames()[$data['STATUS_ID']] ?? '';
		}
		$booleanFields = ['ALLOW_DELIVERY', 'DEDUCTED', 'MARKED', 'CANCELED'];
		foreach ($booleanFields as $fieldName)
		{
			$data[$fieldName] = DataProviderManager::getInstance()->getLangPhraseValue(
				$this,
				($data[$fieldName] ?? '') === 'Y'
					? 'UF_TYPE_BOOLEAN_YES'
					: 'UF_TYPE_BOOLEAN_NO'
			);
		}

		$data['PRICE_DELIVERY'] = new Money($data['PRICE_DELIVERY'] ?? '', [
			'CURRENCY_ID' => $data['CURRENCY'] ?? null
		]);

		return $data;
	}

	protected function fetchData()
	{
		if ($this->data === null)
		{
			$this->data = [];
			$shipmentId = (int)$this->source;
			if ($shipmentId <= 0)
			{
				return;
			}
			$this->shipment = ShipmentRepository::getInstance()->getById($shipmentId);
			if ($this->shipment)
			{
				$this->data = $this->processData($this->shipment->getFields()->getValues());
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
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_ID'),
				],
				'DATE_INSERT' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_DATE_INSERT'),
				],
				'STATUS_ID'             => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_STATUS_ID')],
				'PRICE_DELIVERY'        => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_PRICE_DELIVERY')],
				'ALLOW_DELIVERY'        => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_ALLOW_DELIVERY')],
				'DATE_ALLOW_DELIVERY'   => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_DATE_ALLOW_DELIVERY')],
				'EMP_ALLOW_DELIVERY' => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_EMP_ALLOW_DELIVERY_ID'),
					'PROVIDER' => User::class,
					'OPTIONS' => [
						'FORMATTED_NAME_FORMAT' => [
							'format' => CrmEntityDataProvider::getNameFormat(),
						]
					],
					'VALUE' => [$this, 'getEmpAllowDelivery'],
				],
				'DEDUCTED'              => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_DEDUCTED')],
				'DATE_DEDUCTED'         => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_DATE_DEDUCTED')],
				'EMP_DEDUCTED'       => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_EMP_DEDUCTED_ID'),
					'PROVIDER' => User::class,
					'OPTIONS' => [
						'FORMATTED_NAME_FORMAT' => [
							'format' => CrmEntityDataProvider::getNameFormat(),
						]
					],
					'VALUE' => [$this, 'getEmpDeducted'],
				],
				'REASON_UNDO_DEDUCTED'  => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_REASON_UNDO_DEDUCTED')],
				'DELIVERY_ID'           => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_DELIVERY_ID')],
				'DELIVERY_DOC_NUM'      => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_DELIVERY_DOC_NUM')],
				'DELIVERY_DOC_DATE'     => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_DELIVERY_DOC_DATE')],
				'TRACKING_NUMBER'       => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_TRACKING_NUMBER')],
				'XML_ID'                => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_XML_ID')],
				'DELIVERY_NAME'         => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_DELIVERY_NAME')],
				'MARKED'                => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_MARKED')],
				'DATE_MARKED'           => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_DATE_MARKED')],
				'EMP_MARKED'         => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_EMP_MARKED_ID'),
					'PROVIDER' => User::class,
					'OPTIONS' => [
						'FORMATTED_NAME_FORMAT' => [
							'format' => CrmEntityDataProvider::getNameFormat(),
						]
					],
					'VALUE' => [$this, 'getEmpMarked'],
				],
				'REASON_MARKED'         => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_REASON_MARKED')],
				'CANCELED'              => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_CANCELED')],
				'DATE_CANCELED'         => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_DATE_CANCELED')],
				'EMP_CANCELED'       => [
					'TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_SHIPMENT_EMP_CANCELED_ID'),
					'PROVIDER' => User::class,
					'OPTIONS' => [
						'FORMATTED_NAME_FORMAT' => [
							'format' => CrmEntityDataProvider::getNameFormat(),
						]
					],
					'VALUE' => [$this, 'getEmpCanceled'],
				],
				'CURRENCY' => ['TITLE' => Loc::getMessage('CRM_DOCUMENTGENERATOR_DATAPROVIDER_PAYMENT_CURRENCY'),],
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

	public function getEmpAllowDelivery()
	{
		return $this->data['EMP_ALLOW_DELIVERY_ID'] ?? null;
	}

	public function getEmpDeducted()
	{
		return $this->data['EMP_DEDUCTED_ID'] ?? null;
	}

	public function getEmpMarked()
	{
		return $this->data['EMP_MARKED_ID'] ?? null;
	}

	public function getEmpCanceled()
	{
		return $this->data['EMP_CANCELED_ID'] ?? null;
	}

	public function getResponsibleId()
	{
		if ($this->shipment)
		{
			return (int)$this->shipment->getField('RESPONSIBLE_ID');
		}

		return (int)($this->data['RESPONSIBLE_ID'] ?? null);
	}

	public function getOrderId(): ?int
	{
		if ($this->shipment)
		{
			return $this->shipment->getParentOrderId();
		}

		return $this->data['ORDER_ID'] ?? null;
	}

	public function getCrmOwnerType(): int
	{
		return \CCrmOwnerType::OrderShipment;
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
		return Loc::getMessage('CRM_DOCGEN_DATAPROVIDER_SHIPMENT_TITLE');
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
		$orderId = $this->getOrderId();
		if ($this->shipment && $orderId > 0)
		{
			$shipmentItems = [];
			foreach ($this->shipment->getShipmentItemCollection() as $shipmentItem)
			{
				/** @var ShipmentItem $shipmentItem */
				$basketId = $shipmentItem->getBasketId();
				$shipmentItems[$basketId] = $shipmentItem->getQuantity();
			}
			if (empty($shipmentItems))
			{
				return $result;
			}
			$dbRes = BasketTable::getList([
				'filter' => [
					'=ORDER_ID' => $orderId,
					'@ID' => array_keys($shipmentItems),
				],
			]);

			while($basketItem = $dbRes->fetch())
			{
				$product = Order::getProductProviderDataByBasketItem(
					$basketItem,
					new ItemIdentifier(
						\CCrmOwnerType::Order,
						$orderId
					),
					$this->data['CURRENCY'] ?? \CCrmCurrency::GetDefaultCurrencyID()
				);

				$product['QUANTITY'] = (float) ($shipmentItems[(int)$basketItem['ID']]);
				$result[] = $product;
			}
		}

		return $result;
	}

	public function getTimelineItemIdentifier(): ?ItemIdentifier
	{
		$entityTypeId = \CCrmOwnerType::Order;
		$entityId = $this->getOrderId();
		if ($entityId > 0)
		{
			return new ItemIdentifier($entityTypeId, $entityId);
		}

		return null;
	}
}
