<?php

namespace Bitrix\Crm\Reservation\Entity;

use Bitrix\Main;
use Bitrix\Crm;

class Deal extends Base
{
	protected function checkLoadedEntity(): Main\Result
	{
		$result = new Main\Result();

		$fields = \CCrmDeal::GetByID($this->ownerId, false);
		if (!$fields)
		{
			$result->addError(
				new Main\Error("Deal with id {$this->ownerId} not found")
			);
		}

		return $result;
	}

	public function getEntityProducts(): array
	{
		static $dealProducts = [];

		if ($dealProducts)
		{
			return $dealProducts;
		}

		$basketReservation = new Crm\Reservation\BasketReservation();
		$dealProductRows = \CCrmDeal::LoadProductRows($this->ownerId);

		foreach ($dealProductRows as $dealProduct)
		{
			$basketReservation->addProduct($dealProduct);
		}

		$reservedProducts = $basketReservation->getReservedProducts();
		if ($reservedProducts)
		{
			foreach ($dealProductRows as $dealProduct)
			{
				$reservedProductData = $reservedProducts[$dealProduct['ID']] ?? null;
				if ($reservedProductData)
				{
					$dealProducts[$dealProduct['ID']] = array_merge($dealProduct, $reservedProductData);
				}
			}
		}
		else
		{
			foreach ($dealProductRows as $dealProduct)
			{
				$dealProducts[$dealProduct['ID']] = $dealProduct;
			}
		}

		return $dealProducts;
	}

	public function createOrderByEntity(): ?Crm\Order\Order
	{
		return Crm\Order\Manager::createOrderWithoutProductByDeal($this->ownerId);
	}
}
