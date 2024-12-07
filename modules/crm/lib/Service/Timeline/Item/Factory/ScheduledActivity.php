<?php

namespace Bitrix\Crm\Service\Timeline\Item\Factory;

use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Item\Model;

class ScheduledActivity
{
	public static function createItem(Context $context, array $rawData): Item
	{
		$typeId = (int)($rawData['TYPE_ID'] ?? 0);
		$providerId = (string)($rawData['PROVIDER_ID'] ?? '');

		$model = Model::createFromScheduledActivityArray($rawData);
		$item = Activity::create($typeId, $providerId, $context, $model);

		return $item ?? new Item\Compatible\ScheduledActivity($rawData);
	}
}
