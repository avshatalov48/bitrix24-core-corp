<?php

namespace Bitrix\Crm\Reservation\Entity;

use Bitrix\Main;
use Bitrix\Crm;

class Deal extends Base
{
	public function __construct(int $ownerId)
	{
		parent::__construct(\CCrmOwnerType::Deal, $ownerId);
	}

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

	public function loadEntityProducts(): array
	{
		$dealProducts = [];

		$dealProductRows = \CCrmDeal::LoadProductRows($this->ownerId);

		$basketReservation = new Crm\Reservation\BasketReservation();
		$basketReservation->addProducts($dealProductRows);
		$reservedProducts = $basketReservation->getReservedProducts();

		foreach ($dealProductRows as $dealProduct)
		{
			$dealProducts[$dealProduct['ID']] = $dealProduct;

			$reservedProductData = $reservedProducts[$dealProduct['ID']] ?? null;
			if ($reservedProductData)
			{
				$dealProducts[$dealProduct['ID']] = array_merge($dealProduct, $reservedProductData);
			}
		}

		return $dealProducts;
	}
}
