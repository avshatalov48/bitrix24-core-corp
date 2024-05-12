<?php

namespace Bitrix\Crm\Timeline;

use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\DateTime;
use CCrmOwnerType;

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
	 * @param ItemIdentifier	$item		Item that was moved to a final stage
	 * @param string			$stageId	Final stage ID
	 * @param int|null			$authorId	Author ID
	 *
	 * @throws ArgumentException
	 * @see PhaseSemantics
	 *
	 */
	final public function onItemMoveToFinalStage(ItemIdentifier $item, string $stageId, ?int $authorId = null): void
	{
		if (empty($stageId))
		{
			throw new ArgumentException('Such stage ID does not exist', 'stageId');
		}

		$finalStageSemantics = Container::getInstance()
			->getFactory($item->getEntityTypeId())
			?->getStageSemantics($stageId)
		;

		if (!PhaseSemantics::isDefined($finalStageSemantics))
		{
			throw new ArgumentException('Such stage semantics does not exist', 'finalStageSemantics');
		}

		if (!PhaseSemantics::isFinal($finalStageSemantics))
		{
			throw new ArgumentException('Stage should be final', 'finalStageSemantics');
		}

		$markTypeId = TimelineMarkType::getMarkTypeByPhaseSemantics($finalStageSemantics);

		// do not create a successful entry to lead
		if ($markTypeId === TimelineMarkType::SUCCESS && $item->getEntityTypeId() === CCrmOwnerType::Lead)
		{
			return;
		}

		$bindings = $this->getBindingsForItem($item);

		$timelineEntryId = $this->getTimelineEntryFacade()->create(
			TimelineEntry\Facade::MARK,
			[
				'MARK_TYPE_ID' => $markTypeId,
				'ENTITY_TYPE_ID' => $item->getEntityTypeId(),
				'ENTITY_ID' => $item->getEntityId(),
				'AUTHOR_ID' => ($authorId > 0) ? $authorId : static::getCurrentOrDefaultAuthorId(),
				'BINDINGS' => $bindings,
				'SETTINGS' => [
					'FINAL_STAGE_ID' => $stageId,
				],
			]
		);

		if ($timelineEntryId <= 0)
		{
			return;
		}

		foreach ($bindings as $binding)
		{
			$this->sendPullEventOnAdd(
				new ItemIdentifier($binding['ENTITY_TYPE_ID'], $binding['ENTITY_ID']),
				$timelineEntryId
			);
		}
	}

	public function prepareHistoryDataModel(array $data, array $options = null): array
	{
		if(isset($data['CREATED']) && $data['CREATED'] instanceof DateTime)
		{
			$data['CREATED_SERVER'] = $data['CREATED']->format('Y-m-d H:i:s');
		}

		return $data;
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
