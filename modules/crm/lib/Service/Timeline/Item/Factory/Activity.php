<?php

namespace Bitrix\Crm\Service\Timeline\Item\Factory;

use Bitrix\Crm\Activity\ProviderId;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Item\Model;

class Activity
{
	public static function create(int $typeId, string $providerId, Context $context, Model $model): ?Item
	{
		if ($typeId === \CCrmActivityType::Call)
		{
			return new Item\Activity\Call($context, $model);
		}

		if ($typeId === \CCrmActivityType::Provider)
		{
			if ($providerId === ProviderId::IMOPENLINES_SESSION)
			{
				return new Item\Activity\OpenLine($context, $model);
			}
		}

		return null;
	}
}
