<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Main;
use Bitrix\Crm\Timeline\Entity\TimelineTable;

class ProductCompilationEntry extends TimelineEntry
{
	public static function create(array $params)
	{
		[$authorId, $created, $settings, $bindings] = self::fetchParams($params);
		$entityTypeId = isset($params['ENTITY_TYPE_ID']) ? (int)$params['ENTITY_TYPE_ID'] : \CCrmOwnerType::Order;
		if ($entityTypeId <= 0)
		{
			throw new Main\ArgumentException('Category Id must be greater than zero.', 'entityTypeID');
		}
		$entityId = self::fetchEntityId($params);
		$authorId = self::fetchAuthorId($params);
		$categoryId = self::fetchCategoryId($params);

		$result = TimelineTable::add([
			'TYPE_ID' => TimelineType::PRODUCT_COMPILATION,
			'TYPE_CATEGORY_ID' => $categoryId,
			'CREATED' => $created,
			'AUTHOR_ID' => $authorId,
			'SETTINGS' => $settings,
			'ASSOCIATED_ENTITY_TYPE_ID' => $entityTypeId,
			'ASSOCIATED_ENTITY_ID' => $entityId
		]);
		if (!$result->isSuccess())
		{
			return 0;
		}

		$createdId = $result->getId();

		if (empty($bindings))
		{
			$bindings[] = ['ENTITY_TYPE_ID' => \CCrmOwnerType::Order, 'ENTITY_ID' => $entityId];
		}
		self::registerBindings($createdId, $bindings);

		return $createdId;
	}
}
