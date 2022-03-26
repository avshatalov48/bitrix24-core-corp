<?php

namespace Bitrix\Crm\Invoice;

use Bitrix\Sale;
use Bitrix\Main;

if (!Main\Loader::includeModule('sale'))
{
	return;
}

class ReserveQuantity extends Sale\ReserveQuantity
{
	public static function getRegistryType()
	{
		return REGISTRY_TYPE_CRM_INVOICE;
	}

	public static function loadForBasketItem(int $id)
	{
		return [];
	}

	public function save()
	{
		return new Sale\Result();
	}

	protected function updateInternal($primary, array $data)
	{
		return new Main\ORM\Data\UpdateResult();
	}

	protected function addInternal(array $data)
	{
		return new Main\ORM\Data\AddResult();
	}
}