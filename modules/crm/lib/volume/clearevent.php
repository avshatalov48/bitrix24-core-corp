<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Crm\Volume;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Error;


/**
 * Trait implements Crm\Volume\IVolumeClearEvent.
 *
 * @implements \Bitrix\Crm\Volume\IVolumeClearEvent
 * @see \Bitrix\Crm\Volume\IVolumeClearEvent
 */
trait ClearEvent
{
	/** @var int */
	protected $droppedEventCount = 0;


	/**
	 * Returns count of events.
	 *
	 * @param array $additionEventFilter Filter for events list.
	 * @return int
	 */
	protected function countRelationEvent($additionEventFilter = array())
	{
		$query = $this->prepareQuery();
		if (!$this->prepareFilter($query))
		{
			return -1;
		}

		$eventVolume = new Volume\Event();
		$eventVolume->setFilter($this->getFilter());

		foreach ($additionEventFilter as $key => $value)
		{
			$eventVolume->addFilter($key, $value);
		}

		$eventQuery = $eventVolume->prepareRelationQuery(static::className());

		$count = -1;

		if ($eventVolume->prepareFilter($eventQuery))
		{
			$count = 0;
			$countField = new ORM\Fields\ExpressionField('CNT', 'COUNT(DISTINCT %s)', 'EVENT_ID');

			$eventQuery->registerRuntimeField($countField);
			$eventQuery->addSelect('CNT');

			$res = $eventQuery->exec();
			if ($row = $res->fetch())
			{
				$count = $row['CNT'];
				$this->eventCount = $row['CNT'];
			}
		}


		return $count;
	}

	/**
	 * Returns availability to drop entity event.
	 * @see \Bitrix\Crm\Volume\IVolumeClearEvent::canClearEvent
	 *
	 * @return boolean
	 */
	public function canClearEvent()
	{
		$eventVolume = new Crm\Volume\Event();
		return $eventVolume->canClearEntity();
	}


	/**
	 * Returns dropped count of associated events.
	 * @see \Bitrix\Crm\Volume\IVolumeClearEvent::getDroppedEventCount
	 *
	 * @return int
	 */
	public function getDroppedEventCount()
	{
		return $this->droppedEventCount;
	}

	/**
	 * Sets dropped count of associated events.
	 * @see \Bitrix\Crm\Volume\IVolumeClearEvent::setDroppedEventCount
	 *
	 * @param int $count Amount to set.
	 * @return void
	 */
	public function setDroppedEventCount($count)
	{
		$this->droppedEventCount = $count;
	}

	/**
	 * Returns dropped count of associated events.
	 * @see \Bitrix\Crm\Volume\IVolumeClearEvent::incrementDroppedEventCount
	 *
	 * @param int $count Amount to increment.
	 * @return void
	 */
	public function incrementDroppedEventCount($count = 1)
	{
		$this->droppedEventCount += $count;
	}



	/**
	 * Gets SQL query code for event count.
	 *
	 * @param array $entityGroupField Entity fields to group by.
	 *
	 * @return string
	 */
	protected function prepareEventRelationQuerySql(array $entityGroupField = array())
	{
		$query = $this->prepareQuery();
		if (!$this->prepareFilter($query))
		{
			return '';
		}

		$eventVolume = new Volume\Event();
		$eventVolume->setFilter($this->getFilter());

		//-----------

		$eventQuery = $eventVolume->prepareRelationQuery(static::className());
		$eventVolume->prepareFilter($eventQuery);

		$fileSize = new ORM\Fields\ExpressionField('FILE_SIZE', '0');
		$fileCount = new ORM\Fields\ExpressionField('FILE_COUNT', '0');
		$eventQuery
			->registerRuntimeField($fileSize)
			->registerRuntimeField($fileCount)
			->addSelect('FILE_SIZE')
			->addSelect('FILE_COUNT');


		$eventCount = new ORM\Fields\ExpressionField('EVENT_COUNT', 'COUNT(DISTINCT %s)', 'EVENT_ID');
		$eventQuery
			->registerRuntimeField($eventCount)
			->addSelect('EVENT_COUNT');

		foreach ($entityGroupField as $alias => $field)
		{
			$eventQuery->addSelect($field, $alias);
			$eventQuery->addGroup($field);
		}

		//-----------

		$eventFileQuery = $eventVolume->getEventFileMeasureQuery(static::className());
		$eventCount = new ORM\Fields\ExpressionField('EVENT_COUNT', '0');
		$eventFileQuery
			->registerRuntimeField($eventCount)
			->addSelect('EVENT_COUNT')
		;

		foreach ($entityGroupField as $alias => $field)
		{
			$eventFileQuery->addSelect($field, $alias);
			$eventFileQuery->addGroup($field);
		}

		$sqlQuery =
			$eventQuery->getQuery().
			' UNION '.
			$eventFileQuery->getQuery();

		return $sqlQuery;
	}
}
