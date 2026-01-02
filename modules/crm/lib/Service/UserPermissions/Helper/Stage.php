<?php

namespace Bitrix\Crm\Service\UserPermissions\Helper;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;

class Stage
{
	public static function combineStageIdAttribute(string $stageFieldName, string $stageId): string
	{
		return $stageFieldName . $stageId;
	}

	public static function getStageIdAttributeByEntityTypeId(int $entityTypeId, string $stageId): ?string
	{
		$stageFieldName = self::getStageFieldName($entityTypeId);
		if ($stageFieldName)
		{
			return self::combineStageIdAttribute($stageFieldName, $stageId);
		}

		return null;
	}

	public static function getStageFieldName(int $entityTypeId): ?string
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);

		$stageFieldName = $factory?->getEntityFieldNameByMap(Item::FIELD_NAME_STAGE_ID);

		if (!$stageFieldName && in_array($entityTypeId, [\CCrmOwnerType::Invoice, \CCrmOwnerType::Order], true))
		{
			// todo remove after adding factory for Invoice and Order (e.g. never)
			$stageFieldName = 'STATUS_ID';
		}

		return $stageFieldName;
	}
}
