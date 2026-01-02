<?php

namespace Bitrix\Crm\Component\EntityList\RepeatSaleDataProvider;

use Bitrix\Crm\Item;
use Bitrix\Crm\RepeatSale\Segment\DataFormatter;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\NotSupportedException;
use CCrmOwnerType;

final class Segments
{
	private const SUPPORTED_TYPES = [
		CCrmOwnerType::Deal, // dynamic types will be later
	];

	public function __construct(readonly int $entityTypeId)
	{
		if (!in_array($entityTypeId, self::SUPPORTED_TYPES, true))
		{
			throw new NotSupportedException(CCrmOwnerType::ResolveName($entityTypeId) . 'is not supported entity');
		}
	}

	public function prepareResult(array &$entities): void
	{
		$dataFormatter = DataFormatter::getInstance();
		$canRead = Container::getInstance()->getUserPermissions()->repeatSale()->canRead();

		foreach ($entities as $entityId => $entity)
		{
			$segmentId = $entity[Item::FIELD_NAME_REPEAT_SALE_SEGMENT_ID] ?? null;
			if ($segmentId && $canRead)
			{
				$entities[$entityId][Item::FIELD_NAME_REPEAT_SALE_SEGMENT_ID] = htmlspecialcharsbx($dataFormatter->getTitle($segmentId));
			}
			elseif ($segmentId)
			{
				$entities[$entityId][Item::FIELD_NAME_REPEAT_SALE_SEGMENT_ID] = Loc::getMessage('CRM_COMMON_HIDDEN_ITEM');
			}
		}
	}
}
