<?php

namespace Bitrix\Crm\Service\Sale\Basket\ProductRelations;

/**
 * An item with product information for the builder.
 */
class Item
{
	private int $id;
	private int $productId;
	private float $price;
	private float $quantity;
	private string $xmlId;

	/**
	 * @param int $id
	 * @param int $productId
	 * @param float $price
	 * @param float $quantity
	 * @param string $xmlId
	 */
	public function __construct(int $id, int $productId, float $price, float $quantity, string $xmlId)
	{
		$this->id = $id;
		$this->productId = $productId;
		$this->price = $price;
		$this->quantity = $quantity;
		$this->xmlId = $xmlId;
	}

	/**
	 * @return int
	 */
	public function getId(): int
	{
		return $this->id;
	}

	/**
	 * @return int
	 */
	public function getProductId(): int
	{
		return $this->productId;
	}

	/**
	 * @return float
	 */
	public function getPrice(): float
	{
		return $this->price;
	}

	/**
	 * @return float
	 */
	public function getQuantity(): float
	{
		return $this->quantity;
	}

	/**
	 * @return string
	 */
	public function getXmlId(): string
	{
		return $this->xmlId;
	}
}
