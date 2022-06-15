<?php

namespace Bitrix\Crm\Reservation\Entity;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Catalog;

abstract class Base
{
	/** @var int $ownerTypeId */
	protected $ownerTypeId;

	/** @var int $ownerId */
	protected $ownerId;

	/** @var array $entityProducts */
	protected $entityProducts = [];

	/** @var Crm\Reservation\Product[] $products */
	protected $products = [];

	/** @var Crm\Order\Order */
	private $order;

	/** @var int|null */
	private $defaultStore;

	/**
	 * @param int $ownerId
	 * @throws Main\SystemException
	 */
	public function __construct(int $ownerTypeId, int $ownerId)
	{
		$this->ownerId = $ownerId;
		$this->ownerTypeId = $ownerTypeId;

		$checkLoadedEntityResult = $this->checkLoadedEntity();
		if (!$checkLoadedEntityResult->isSuccess())
		{
			throw new Main\SystemException(implode("\n", $checkLoadedEntityResult->getErrorMessages()));
		}

		$this->entityProducts = $this->loadEntityProducts();

		$this->defaultStore = Catalog\StoreTable::getDefaultStoreId();
	}

	abstract protected function checkLoadedEntity(): Main\Result;

	/**
	 * @param Crm\Order\Order $order
	 * @return $this
	 */
	public function setOrder(Crm\Order\Order $order): Base
	{
		if ($order->getEntityBinding()->getOwnerId() !== $this->ownerId)
		{
			throw new Main\InvalidOperationException('Order not bound with entity');
		}

		$this->order = $order;
		return $this;
	}

	/**
	 * @return Crm\Order\Order|null
	 */
	public function getOrder(): ?Crm\Order\Order
	{
		return $this->order;
	}

	abstract public function createOrderByEntity(): ?Crm\Order\Order;

	/**
	 * @return array
	 */
	public function getEntityProducts(): array
	{
		if (!$this->entityProducts)
		{
			$this->entityProducts = $this->loadEntityProducts();
		}

		return $this->entityProducts;
	}

	/**
	 * @return array
	 */
	abstract public function loadEntityProducts(): array;

	/**
	 * @param Crm\Reservation\Product $product
	 * @throws Main\ArgumentException
	 */
	public function addProduct(Crm\Reservation\Product $product): void
	{
		$id = $product->getId();
		if (!isset($this->entityProducts[$id]))
		{
			throw new Main\ArgumentException("Product with id {$id} not found in entity products");
		}

		$quantity = $product->getQuantity();
		if ($this->entityProducts[$id]['QUANTITY'] < $quantity)
		{
			throw new Main\ArgumentException("Quantity {$quantity} larger than quantity in entity");
		}

		$xmlId = $product->getXmlId();
		$this->products[$xmlId] = $product;
	}

	/**
	 * @return Crm\Reservation\Product[]
	 */
	public function getProducts(): array
	{
		return $this->products;
	}

	/**
	 * @return array
	 */
	public function getProductsByProductId(): array
	{
		$products = [];

		foreach ($this->products as $product)
		{
			$entityProduct = $this->entityProducts[$product->getId()];
			$productId = $entityProduct['PRODUCT_ID'];

			$storeId = $entityProduct['STORE_ID'] ? (int)$entityProduct['STORE_ID'] : $this->defaultStore;

			if (isset($products[$productId]))
			{
				$products[$productId]['QUANTITY'] += $entityProduct['QUANTITY'];

				if (isset($products[$productId]['STORE_LIST'][$storeId]))
				{
					$products[$productId]['STORE_LIST'][$storeId] += $entityProduct['QUANTITY'];
				}
				else
				{
					$products[$productId]['STORE_LIST'][$storeId] = $entityProduct['QUANTITY'];
				}
			}
			else
			{
				$products[$productId] = [
					'QUANTITY' => $entityProduct['QUANTITY'],
					'PRODUCT' => $entityProduct,
					'STORE_LIST' => [
						$storeId => $entityProduct['QUANTITY'],
					],
				];
			}
		}

		return $products;
	}

	public function getOwnerTypeId(): int
	{
		return $this->ownerTypeId;
	}

	public function getOwnerId(): int
	{
		return $this->ownerId;
	}
}
