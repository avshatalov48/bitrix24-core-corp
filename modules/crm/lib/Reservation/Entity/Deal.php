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
		$dealProducts = [];

		foreach (\CCrmDeal::LoadProductRows($this->ownerId) as $dealProduct)
		{
			$dealProducts[$dealProduct['ID']] = $dealProduct;
		}

		return $dealProducts;
	}

	public function createOrderByEntity(): ?Crm\Order\Order
	{
		return Crm\Order\Manager::createOrderWithoutProductByDeal($this->ownerId);
	}
}
