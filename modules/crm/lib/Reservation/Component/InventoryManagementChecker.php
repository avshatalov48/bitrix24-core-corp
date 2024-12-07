<?php

namespace Bitrix\Crm\Reservation\Component;

use Bitrix\Main;
use Bitrix\Crm;
use CCrmOwnerType;
use Bitrix\Crm\Reservation\QuantityCheckerTrait;
use Bitrix\Crm\Reservation\AvailabilityServicesCheckerTrait;

final class InventoryManagementChecker
{
	use QuantityCheckerTrait;
	use AvailabilityServicesCheckerTrait;

	/**
	 * @var Crm\Service\Factory\Deal
	 */
	private Crm\Service\Factory $factory;
	private Crm\Item $item;

	public function __construct(Crm\Item $item)
	{
		$this->item = $item;
		$this->factory = Crm\Service\Container::getInstance()->getFactory($this->item->getEntityTypeId());
	}

	public function checkBeforeAdd(array $entityFields): Main\Result
	{
		$result = new Main\Result();
		$result->setData($entityFields);

		if (!$this->isProcessInventoryManagementAvailable())
		{
			return $result;
		}

		$semanticId =
			$this->item->hasField(Crm\Item::FIELD_NAME_STAGE_SEMANTIC_ID)
				? $this->item->getStageSemanticId()
				: null
		;
		if (!$semanticId)
		{
			$stageId = $entityFields[Crm\Item::FIELD_NAME_STAGE_ID] ?? null;
			if (!$stageId)
			{
				return $result;
			}

			$semanticId = $this->factory->getStageSemantics($stageId);
		}

		if ($semanticId && Crm\PhaseSemantics::isSuccess($semanticId))
		{
			$productRows = $this->getEntityProducts();
			if ($productRows)
			{
				$checkResult = self::checkQuantityFromArray(CCrmOwnerType::Deal, 0, $productRows);
				if (!$checkResult->isSuccess())
				{
					$result->addError(Crm\Reservation\Error\InventoryManagementError::create());
				}

				$checkResult = self::checkAvailabilityServices($productRows);
				if (!$checkResult->isSuccess())
				{
					$result->addError(Crm\Reservation\Error\AvailabilityServices::create());
				}

				if (!$result->isSuccess())
				{
					unset(
						$entityFields[Crm\Item::FIELD_NAME_STAGE_ID],
						$entityFields[Crm\Item::FIELD_NAME_STAGE_SEMANTIC_ID]
					);

					$result->setData($entityFields);
				}
			}
		}

		return $result;
	}

	public function checkBeforeUpdate(array $entityFields): Main\Result
	{
		$result = new Main\Result();
		$result->setData($entityFields);

		if (!$this->isProcessInventoryManagementAvailable())
		{
			return $result;
		}

		$currentStageId = $entityFields[Crm\Item::FIELD_NAME_STAGE_ID] ?? null;
		if (!$currentStageId)
		{
			return $result;
		}

		$currentSemanticId = $this->factory->getStageSemantics($currentStageId);
		$previousStageId = $this->getCurrentStage();

		$isMovedToFinalStage = Crm\Comparer\ComparerBase::isMovedToFinalStage(\CCrmOwnerType::Deal, $previousStageId, $currentStageId);
		if ($isMovedToFinalStage && Crm\PhaseSemantics::isSuccess($currentSemanticId))
		{
			$productRows = $this->getEntityProducts();
			if ($productRows)
			{
				$checkResult = self::checkQuantityFromArray($this->item->getEntityTypeId(), $this->item->getId(), $productRows);
				if (!$checkResult->isSuccess())
				{
					$result->addError(Crm\Reservation\Error\InventoryManagementError::create());
				}

				$checkResult = self::checkAvailabilityServices($productRows);
				if (!$checkResult->isSuccess())
				{
					$result->addError(Crm\Reservation\Error\AvailabilityServices::create());
				}

				if (!$result->isSuccess())
				{
					unset(
						$entityFields[Crm\Item::FIELD_NAME_STAGE_ID],
						$entityFields[Crm\Item::FIELD_NAME_STAGE_SEMANTIC_ID]
					);

					$result->setData($entityFields);
				}
			}
		}

		return $result;
	}

	private function getCurrentStage(): string
	{
		return
			$this->item->hasField(Crm\Item::FIELD_NAME_STAGE_ID)
				? $this->item->getStageId()
				: ''
		;
	}

	private function getEntityProducts(): array
	{
		$entityProducts = [];

		$productRows = $this->item->getProductRows();
		/** @var Crm\ProductRow $productRow */
		foreach ($productRows as $productRow)
		{
			$entityProduct = $productRow->toArray();

			$productReservation = $productRow->getProductRowReservation();
			if ($productReservation)
			{
				$entityProduct += $productReservation->toArray();
			}

			$entityProducts[] = $entityProduct;
		}

		return $entityProducts;
	}

	private function isProcessInventoryManagementAvailable(): bool
	{
		return $this->factory->isInventoryManagementEnabled();
	}
}
