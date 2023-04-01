<?php

namespace Bitrix\Crm\Timeline\Traits;

use Bitrix\Crm\Binding\OrderEntityTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Timeline\TimelineEntry\Facade;
use Bitrix\Crm\Timeline\TimelineType;
use Bitrix\Crm\Timeline\Interfaces\FinalSummaryController;

/**
 * @implements FinalSummaryController
 * @see \Bitrix\Crm\Timeline\Interfaces\FinalSummaryController
 */
trait FinalSummaryControllerTrait
{
	public function onCreateFinalSummary(Item $item): void
	{
		$entityId = $item->getId();
		$entityTypeId = $item->getEntityTypeId();

		$orderIdList = OrderEntityTable::getOrderIdsByOwner($entityId, $entityTypeId);
		if (empty($orderIdList))
		{
			return;
		}

		$entryId = $this->getTimelineEntryFacade()->create(
			\CCrmSaleHelper::isWithOrdersMode()
				? Facade::FINAL_SUMMARY
				: Facade::FINAL_SUMMARY_DOCUMENTS
			,
			[
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'TYPE_CATEGORY_ID' => TimelineType::CREATION,
				'AUTHOR_ID' => $this->getCurrentOrDefaultAuthorId(),
				'SETTINGS' => [
					'ORDER_IDS' => $orderIdList
				],
				'BINDINGS' => [
					[
						'ENTITY_TYPE_ID' => $entityTypeId,
						'ENTITY_ID' => $entityId,
					]
				]
			]
		);

		if ($entryId)
		{
			$this->sendPullEventOnAdd(
				ItemIdentifier::createByItem($item),
				$entryId
			);
		}
	}

	abstract protected static function getCurrentOrDefaultAuthorId(): int;

	abstract protected function getTimelineEntryFacade(): Facade;

	abstract public function sendPullEventOnAdd(
		ItemIdentifier $itemIdentifier,
		int $timelineEntryId,
		int $userId = null
	): void;
}
