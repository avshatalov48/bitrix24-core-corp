<?php

namespace Bitrix\Crm\Service\Timeline\Item\Factory;

use Bitrix\Catalog\Access\Model\StoreDocument;
use Bitrix\Crm\Service\Timeline\Context;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Item\Model;
use Bitrix\Crm\Timeline\TimelineType;

class HistoryItemStoreDocument
{
	public static function createItem(Context $context, Model $model): ?Item
	{
		$typeCategoryId = $model->getTypeCategoryId();

		if ($typeCategoryId === TimelineType::CREATION)
		{
			$item = self::getStoreDocumentCreationItem($context, $model);
			if ($item)
			{
				return $item;
			}
		}
		elseif ($typeCategoryId === TimelineType::MODIFICATION)
		{
			$item = self::getStoreDocumentModificationItem($context, $model);
			if ($item)
			{
				return $item;
			}
		}

		return null;
	}

	private static function getStoreDocumentCreationItem(Context $context, Model $model): ?Item
	{
		$documentType = $model->getAssociatedEntityModel()->get('DOC_TYPE');
		if (!$documentType)
		{
			return null;
		}

		$map = [
			StoreDocument::TYPE_ARRIVAL => Item\LogMessage\StoreDocument\Creation\Arrival::class,
			StoreDocument::TYPE_STORE_ADJUSTMENT => Item\LogMessage\StoreDocument\Creation\StoreAdjustment::class,
			StoreDocument::TYPE_MOVING => Item\LogMessage\StoreDocument\Creation\Moving::class,
			StoreDocument::TYPE_DEDUCT => Item\LogMessage\StoreDocument\Creation\Deduct::class,
			StoreDocument::TYPE_SALES_ORDERS => Item\LogMessage\StoreDocument\Creation\Realization::class,
		];

		if (!isset($map[$documentType]))
		{
			return null;
		}

		/** @var Item $className */
		$className = $map[$documentType];

		return new $className($context, $model);
	}

	private static function getStoreDocumentModificationItem(Context $context, Model $model): ?Item
	{
		/** @var Item|null $className */
		$className = null;

		$field = $model->getHistoryItemModel()->get('FIELD');
		$error = $model->getHistoryItemModel()->get('ERROR');
		if ($field)
		{
			$className = self::getStoreDocumentModificationFieldClass(
				$field,
				$model->getAssociatedEntityModel()->get('DOC_TYPE')
			);
		}
		elseif ($error)
		{
			$className = self::getStoreDocumentModificationErrorClass($error);
		}

		if (!$className)
		{
			return null;
		}

		return new $className($context, $model);
	}

	private static function getStoreDocumentModificationErrorClass(string $error): ?string
	{
		if ($error === 'CONDUCT')
		{
			return Item\LogMessage\StoreDocument\Modification\Error\Conduction::class;
		}

		return null;
	}

	private static function getStoreDocumentModificationFieldClass(string $field, ?string $documentType): ?string
	{
		$map = [
			StoreDocument::TYPE_ARRIVAL => Item\LogMessage\StoreDocument\Modification\Field\Arrival::class,
			StoreDocument::TYPE_STORE_ADJUSTMENT => Item\LogMessage\StoreDocument\Modification\Field\StoreAdjustment::class,
			StoreDocument::TYPE_MOVING => Item\LogMessage\StoreDocument\Modification\Field\Moving::class,
			StoreDocument::TYPE_DEDUCT => Item\LogMessage\StoreDocument\Modification\Field\Deduct::class,
			StoreDocument::TYPE_SALES_ORDERS => Item\LogMessage\StoreDocument\Modification\Field\Realization::class,
		];

		if ($field === 'STATUS')
		{
			$map = [
				StoreDocument::TYPE_ARRIVAL => Item\LogMessage\StoreDocument\Modification\Status\Arrival::class,
				StoreDocument::TYPE_STORE_ADJUSTMENT => Item\LogMessage\StoreDocument\Modification\Status\StoreAdjustment::class,
				StoreDocument::TYPE_MOVING => Item\LogMessage\StoreDocument\Modification\Status\Moving::class,
				StoreDocument::TYPE_DEDUCT => Item\LogMessage\StoreDocument\Modification\Status\Deduct::class,
				StoreDocument::TYPE_SALES_ORDERS => Item\LogMessage\StoreDocument\Modification\Status\Realization::class,
			];
		}

		return $map[$documentType] ?? null;
	}
}
