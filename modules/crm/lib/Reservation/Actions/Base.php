<?php

namespace Bitrix\Crm\Reservation\Actions;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Catalog;
use Bitrix\Crm\Reservation\QuantityCheckerTrait;

abstract class Base extends Crm\Service\Operation\Action
{
	use QuantityCheckerTrait;

	protected static function checkAvailabilityServices(Crm\ProductRowCollection $productRows): Main\Result
	{
		$result = new Main\Result();

		$products = $productRows->toArray();

		$productIds = array_column($products, 'PRODUCT_ID');
		if (!$productIds)
		{
			return $result;
		}

		$productIterator = Catalog\ProductTable::getList([
			'select' => [
				'ID',
				'AVAILABLE',
			],
			'filter' => [
				'=ID' => $productIds,
			],
		]);
		while ($product = $productIterator->fetch())
		{
			if ($product['AVAILABLE'] === Catalog\ProductTable::STATUS_NO)
			{
				$result->addError(
					new Main\Error("Product with id {$product['ID']} is not available")
				);
			}
		}

		return $result;
	}

	protected function isFinalStage(Crm\Item $item): bool
	{
		$semanticId =
			$item->hasField(Crm\Item::FIELD_NAME_STAGE_SEMANTIC_ID)
				? $item->getStageSemanticId()
				: null
		;

		return $semanticId && Crm\PhaseSemantics::isFinal($semanticId);
	}

	protected function isSuccessStage(Crm\Item $item): bool
	{
		$semanticId =
			$item->hasField(Crm\Item::FIELD_NAME_STAGE_SEMANTIC_ID)
				? $item->getStageSemanticId()
				: null
		;

		return $semanticId && Crm\PhaseSemantics::isSuccess($semanticId);
	}

	protected function isMovedToFinalStage(Crm\Item $item, Crm\Item $itemBeforeSave): bool
	{
		$previousStageId = $itemBeforeSave->remindActual(Crm\Item::FIELD_NAME_STAGE_ID);
		$currentStageId =
			$item->hasField(Crm\Item::FIELD_NAME_STAGE_ID)
				? $item->getStageId()
				: null
		;

		return Crm\Comparer\ComparerBase::isMovedToFinalStage($item->getEntityTypeId(), $previousStageId, $currentStageId);
	}

	protected function isMovedToSuccessfulStage(Crm\Item $item): bool
	{
		if (!$item->hasField(Crm\Item::FIELD_NAME_STAGE_ID))
		{
			return false;
		}

		$previousStageId = $item->remindActual(Crm\Item::FIELD_NAME_STAGE_ID);
		$currentStageId = $item->getStageId();

		return Crm\Comparer\ComparerBase::isMovedToSuccessfulStage(
			$item->getEntityTypeId(),
			$previousStageId,
			$currentStageId
		);
	}
}
