<?php

namespace Bitrix\Crm\Reservation;

final class Product
{
	/** @var int $id */
	private $id;

	/** @var float $quantity */
	private $quantity;

	/** @var int $storeId */
	private $storeId;

	/** @var string $xmlId */
	private $xmlId;

	public function __construct(
		int $id,
		float $quantity = null,
		int $storeId = null,
		string $xmlId = null
	)
	{
		$this->id = $id;
		$this->quantity = $quantity;
		$this->storeId = $storeId;

		if ($xmlId)
		{
			$this->xmlId = $xmlId;
		}
		else
		{
			$this->xmlId = md5($id);
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getQuantity(): float
	{
		return $this->quantity;
	}

	public function getStoreId(): int
	{
		return $this->storeId;
	}

	public function getXmlId(): string
	{
		return $this->xmlId;
	}
}
