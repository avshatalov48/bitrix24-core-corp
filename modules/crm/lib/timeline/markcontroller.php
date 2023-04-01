<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;

class MarkController extends Controller
{
	protected function __construct()
	{
	}

	protected function __clone()
	{
	}

	/**
	 * Register in timeline that an item was moved to a final stage
	 *
	 * @see PhaseSemantics
	 *
	 * @param string $finalStageSemantics - semantics of a final stage (constant of \Bitrix\Crm\PhaseSemantics)
	 * @param ItemIdentifier $item - item that was moved to a final stage
	 * @param int|null $authorId
	 *
	 * @throws ArgumentException
	 */
	public function onItemMoveToFinalStage(ItemIdentifier $item, string $finalStageSemantics, ?int $authorId = null): void
	{
		if (!PhaseSemantics::isDefined($finalStageSemantics))
		{
			throw new ArgumentException('Such stage semantics does not exist', 'finalStageSemantics');
		}

		if (!PhaseSemantics::isFinal($finalStageSemantics))
		{
			throw new ArgumentException('Stage should be final', 'finalStageSemantics');
		}

		$bindings = $this->getBindingsForItem($item);

		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			TimelineEntry\Facade::MARK,
			[
				'MARK_TYPE_ID' => TimelineMarkType::getMarkTypeByPhaseSemantics($finalStageSemantics),
				'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
				'ENTITY_ID' => $item->getEntityId(),
				'AUTHOR_ID' => ($authorId > 0) ? $authorId : static::getCurrentOrDefaultAuthorId(),
				'BINDINGS' => $bindings,
			]
		);

		if ($timelineEntryId <= 0)
		{
			return;
		}

		$historyDataModel = null;

		$timelineEntry = $this->getTimelineEntryFacade()->getById($timelineEntryId);
		if (is_array($timelineEntry))
		{
			$historyDataModel = Container::getInstance()->getTimelineHistoryDataModelMaker()->prepareHistoryDataModel(
				$timelineEntry,
				['ENABLE_USER_INFO' => true]
			);
		}

		foreach ($bindings as $binding)
		{
			Container::getInstance()->getTimelinePusher()->sendPullEvent(
				$binding['ENTITY_TYPE_ID'],
				$binding['ENTITY_ID'],
				Pusher::ADD_ACTIVITY_PULL_COMMAND,
				$historyDataModel
			);
		}
	}

	protected function getBindingsForItem(ItemIdentifier $item): array
	{
		$bindings = [
			$item->toArray(),
		];

		foreach (Container::getInstance()->getRelationManager()->getElements($item) as $boundItem)
		{
			$bindings[] = $boundItem->toArray();
		}

		return $bindings;
	}
}
