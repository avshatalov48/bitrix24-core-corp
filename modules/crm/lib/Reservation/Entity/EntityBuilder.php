<?php

namespace Bitrix\Crm\Reservation\Entity;

use Bitrix\Main;
use Bitrix\Crm;

final class EntityBuilder
{
	/** @var int $ownerTypeId */
	private $ownerTypeId;

	/** @var int $ownerId */
	private $ownerId;

	/** @var array $products */
	private $products = [];

	/** @var Crm\Order\Order $order */
	private $order;

	public function setOwnerTypeId(int $ownerTypeId): EntityBuilder
	{
		$this->ownerTypeId = $ownerTypeId;
		return $this;
	}

	public function setOwnerId(int $ownerId): EntityBuilder
	{
		$this->ownerId = $ownerId;
		return $this;
	}

	public function addProduct(Crm\Reservation\Product $product): EntityBuilder
	{
		$this->products[$product->getXmlId()] = $product;
		return $this;
	}

	public function setOrder(Crm\Order\Order $order): EntityBuilder
	{
		$this->order = $order;
		return $this;
	}

	public function build(): Base
	{
		$entity = null;
		if ($this->isDeal())
		{
			$entity = new Deal($this->ownerId);
		}
		elseif ($this->isDynamicEntity())
		{
			$entity = new DynamicEntity($this->ownerTypeId, $this->ownerId);
		}

		if ($entity)
		{
			if ($this->order)
			{
				$entity->setOrder($this->order);
			}

			if ($this->products)
			{
				foreach ($this->products as $product)
				{
					$entity->addProduct($product);
				}
			}

			return $entity;
		}

		$exceptionMessage = "Type {$this->ownerTypeId} not supported";
		$ownerTypeName = \CCrmOwnerType::ResolveName($this->ownerTypeId);
		if ($ownerTypeName)
		{
			$exceptionMessage = "$ownerTypeName not supported";
		}

		throw new Main\SystemException($exceptionMessage);
	}

	private function isDeal(): bool
	{
		return $this->ownerTypeId === \CCrmOwnerType::Deal;
	}

	private function isDynamicEntity(): bool
	{
		return \CCrmOwnerType::isPossibleDynamicTypeId($this->ownerTypeId);
	}
}
