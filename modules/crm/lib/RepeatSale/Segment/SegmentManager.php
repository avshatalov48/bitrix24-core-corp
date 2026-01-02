<?php

namespace Bitrix\Crm\RepeatSale\Segment;

use Bitrix\Crm\CategoryIdentifier;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\RepeatSale\Log\Controller\RepeatSaleLogController;
use Bitrix\Crm\RepeatSale\Logger;
use Bitrix\Crm\RepeatSale\Queue\Controller\RepeatSaleQueueController;
use Bitrix\Crm\RepeatSale\Segment\Controller\RepeatSaleSegmentController;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Config\Option;
use Bitrix\Main\DI\ServiceLocator;

final class SegmentManager
{
	public static function getDefaultEnableSegmentCodes(): array
	{
		return [
			SystemSegmentCode::DEAL_EVERY_MONTH->value,
			SystemSegmentCode::DEAL_EVERY_HALF_YEAR->value,
			SystemSegmentCode::DEAL_EVERY_YEAR->value,
		];
	}

	public static function onCategoryDelete(CategoryIdentifier $categoryIdentifier): void
	{
		$entityTypeId = $categoryIdentifier->getEntityTypeId();

		if ($entityTypeId !== \CCrmOwnerType::Deal)
		{
			return;
		}

		$deletedCategoryId = $categoryIdentifier->getCategoryId();
		if ($deletedCategoryId === null)
		{
			return;
		}

		$controller = RepeatSaleSegmentController::getInstance();

		$segments = $controller->getList([
			'select' => ['*', 'ASSIGNMENT_USERS.*'],
			'filter' => [
				'=ENTITY_CATEGORY_ID' => $deletedCategoryId,
			],
		]);

		if ($segments->isEmpty())
		{
			return;
		}

		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
		$categoryId = $factory->getDefaultCategory()?->getId() ?? 0;

		$fieldRepository = ServiceLocator::getInstance()->get('crm.model.fieldRepository');
		$resolver = $fieldRepository->getDefaultStageIdResolver($entityTypeId);

		foreach ($segments as $segment)
		{
			$segmentItem = SegmentItem::createFromEntity($segment);
			$segmentItem->setEntityCategoryId($categoryId);
			$segmentItem->setEntityStageId($resolver());

			$controller->update($segment->getId(), $segmentItem);
		}
	}

	public static function onEntityDelete(ItemIdentifier $itemIdentifier): void
	{
		$controller = RepeatSaleLogController::getInstance();
		$controller->deleteByItemIdentifier($itemIdentifier);
	}

	public function updateFlowToPending(): void
	{
		$availabilityChecker = Container::getInstance()->getRepeatSaleAvailabilityChecker();

		Option::delete('crm', ['name' => $availabilityChecker::SEGMENT_INITIALIZATION_OPTION_NAME]);
		Option::set('crm', $availabilityChecker::ENABLE_PENDING_OPTION_NAME, 'Y');

		RepeatSaleQueueController::getInstance()->deleteOnlyCalcItems();

		(new Logger())->debug('The flow is updated to pending', []);
	}
}
