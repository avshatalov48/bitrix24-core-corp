<?php

namespace Bitrix\Crm\Reservation\Entity;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Catalog;

abstract class Base
{
	/** @var int $ownerTypeId */
	protected int $ownerTypeId;

	/** @var int $ownerId */
	protected int $ownerId;

	/** @var array $entityProducts */
	protected array $entityProducts = [];

	/** @var Crm\Reservation\Product[] $products */
	protected $products = [];

	/** @var Crm\Order\Order|null */
	private ?Crm\Order\Order $order = null;

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

	public function createOrderByEntity(): ?Crm\Order\Order
	{
		return (new Crm\Order\OrderCreator($this->ownerId, $this->ownerTypeId))->create();
	}

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

	public function getOwnerTypeId(): int
	{
		return $this->ownerTypeId;
	}

	public function getOwnerId(): int
	{
		return $this->ownerId;
	}
}
