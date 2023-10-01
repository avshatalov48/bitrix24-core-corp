<?php

namespace Bitrix\Crm\Update\EventHistory;

use Bitrix\Crm\EO_Event;
use Bitrix\Crm\EO_EventRelations;
use Bitrix\Crm\EventRelationsTable;
use Bitrix\Crm\EventTable;
use Bitrix\Crm\Service\EventHistory;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Update\Stepper;

final class MoveRelationEventsStepper extends Stepper
{
	protected static $moduleId = 'crm';

	private const START_EVENT_TYPE = EventHistory::EVENT_TYPE_LINK;
	private const FINISH_EVENT_TYPE = EventHistory::EVENT_TYPE_UNLINK;
	private const ALL_EVENT_TYPES = [self::START_EVENT_TYPE, self::FINISH_EVENT_TYPE];

	function execute(array &$option)
	{
		$lastDateCreate = null;
		if (isset($option['lastDateCreateTimestamp']) && is_numeric($option['lastDateCreateTimestamp']))
		{
			$lastDateCreate = DateTime::createFromTimestamp($option['lastDateCreateTimestamp']);
		}

		$lastId = isset($option['lastId']) ? (int)$option['lastId'] : 0;

		$type = self::START_EVENT_TYPE;
		if (
			isset($option['type'])
			&& in_array((int)$option['type'], self::ALL_EVENT_TYPES, true)
		)
		{
			$type = (int)$option['type'];
		}

		$query = EventTable::query()
			->setSelect([
				'ID',
				'DATE_CREATE',
			])
			->where('EVENT_TYPE', $type)
			->addOrder('DATE_CREATE')
			->addOrder('ID')
			->setLimit($this->getStepLimit())
		;

		if ($lastDateCreate instanceof DateTime)
		{
			$query->where('DATE_CREATE', '>=', $lastDateCreate);
		}
		if ($lastId > 0)
		{
			$query->where('ID', '>', $lastId);
		}

		$processedCount = 0;
		foreach ($query->fetchCollection() as $eventCandidate)
		{
			$lastDateCreate = $eventCandidate->requireDateCreate();
			$lastId = $eventCandidate->getId();
			$processedCount++;

			$this->processRow($eventCandidate);
		}

		$option['lastDateCreateTimestamp'] = $lastDateCreate ? $lastDateCreate->getTimestamp() : null;
		$option['lastId'] = $lastId;
		$option['type'] = $type;

		if ($processedCount < $this->getStepLimit())
		{
			if ($type === self::FINISH_EVENT_TYPE)
			{
				return self::FINISH_EXECUTION;
			}

			//start processing next type
			unset($option['lastDateCreateTimestamp'], $option['lastId']);
			$option['type'] = self::FINISH_EVENT_TYPE;

			return self::CONTINUE_EXECUTION;
		}

		return self::CONTINUE_EXECUTION;
	}

	private function processRow(EO_Event $eventCandidate): void
	{
		// the event could have been deleted on previous iteration, get relevant data before anything else
		$event = EventTable::query()
			->setSelect([
				'ID',
				'EVENT_TEXT_1',
				'EVENT_TEXT_2',
				'DATE_CREATE',
				'EVENT_TYPE',
				'EVENT_RELATION.ENTITY_TYPE',
				'EVENT_RELATION.ENTITY_ID',
				'EVENT_RELATION.ASSIGNED_BY_ID',
			])
			->where('ID', $eventCandidate->getId())
			->fetchObject()
		;
		if (!$event)
		{
			// seems that this event was indeed deleted, skip
			return;
		}

		$boundEntityTypeName = (string)$event->require('EVENT_TEXT_1');
		$boundEntityId = is_numeric($event->require('EVENT_TEXT_2')) ? (int)$event->require('EVENT_TEXT_2') : 0;
		if (empty($boundEntityTypeName) || $boundEntityId <= 0)
		{
			//seems that this event has new format
			return;
		}

		$relations = $event->requireEventRelation()->getAll();
		if (count($relations) !== 1)
		{
			//either this event has new format already, or has inconsistent structure
			//skip it
			return;
		}

		/** @var EO_EventRelations $relationToEventOwner */
		$relationToEventOwner = reset($relations);

		$timeWindowMin = (clone $event->requireDateCreate())->add('-1 second');
		$timeWindowMax = (clone $event->requireDateCreate())->add('+1 second');

		$sameEventWithAnotherOwner =
			EventTable::query()
				->setSelect(['ID', 'RELATION_ID' => 'EVENT_RELATION.ID'])
				->where('EVENT_TYPE', $event->requireEventType())
				->where('EVENT_RELATION.ENTITY_TYPE', $boundEntityTypeName)
				->where('EVENT_RELATION.ENTITY_ID', $boundEntityId)
				->where('EVENT_TEXT_1', $relationToEventOwner->requireEntityType())
				->where('EVENT_TEXT_2', $relationToEventOwner->requireEntityId())
				// time window in case the second had changed the moment link was created
				->whereBetween('DATE_CREATE', $timeWindowMin, $timeWindowMax)
				// to additionally ensure that the duplicate has old structure, and the duplicate is not inconsistent
				->where('RELATIONS_COUNT', 1)
				->registerRuntimeField(
					new ExpressionField('RELATIONS_COUNT', 'COUNT(%s)', 'EVENT_RELATION.ID')
				)
				->addOrder('DATE_CREATE')
				->setLimit(1)
				->fetch()
		;

		if (empty($sameEventWithAnotherOwner))
		{
			//okay, no duplicate to rebind relation record from. create a second relation record manually

			$newRelationRow = EventRelationsTable::createObject();
			$newRelationRow
				->setEntityType($boundEntityTypeName)
				->setEntityId($boundEntityId)
				->setEventId($event->getId())
				->setEntityField('')
				->setAssignedById($relationToEventOwner->requireAssignedById())
			;

			$newRelationRow->save();
		}
		else
		{
			//delete the event from other side, or we will get duplicates for each link/unlink
			EventTable::delete($sameEventWithAnotherOwner['ID']);

			//rebind relation record
			EventRelationsTable::update($sameEventWithAnotherOwner['RELATION_ID'], ['EVENT_ID' => $event->getId()]);
		}

		EventTable::update($event->getId(), ['EVENT_TEXT_1' => '', 'EVENT_TEXT_2' => '']);
	}

	private function getStepLimit(): int
	{
		return (int)Option::get('crm', 'move_relation_events_stepper_step_limit', 20);
	}
}
