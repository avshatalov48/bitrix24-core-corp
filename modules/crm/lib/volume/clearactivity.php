<?php

namespace Bitrix\Crm\Volume;

use Bitrix\Crm;
use Bitrix\Crm\Volume;
use Bitrix\Main;
use Bitrix\Main\ORM;
use Bitrix\Main\Error;


/**
 * Trait implements Volume\IVolumeClearActivity
 *
 * @implements \Bitrix\Crm\Volume\IVolumeClearActivity
 * @see \Bitrix\Crm\Volume\IVolumeClearActivity
 */
trait ClearActivity
{
	/** @var int */
	private $droppedActivityCount = 0;

	/**
	 * Returns availability to drop entity activities.
	 *
	 * @return boolean
	 */
	public function canClearActivity()
	{
		$activityVolume = new Volume\Activity();
		return $activityVolume->canClearEntity();
	}


	/**
	 * Returns count of activities.
	 * @see \Bitrix\Crm\Volume\IVolumeClearActivity::countActivity
	 *
	 * @param array $additionActivityFilter Filter for activity list.
	 * @return int
	 */
	public function countRelationActivity($additionActivityFilter = array())
	{
		$activityVolume = new Volume\Activity();
		$activityVolume->setFilter($this->getFilter());

		foreach ($additionActivityFilter as $key => $value)
		{
			$activityVolume->addFilter($key, $value);
		}

		return $activityVolume->countEntity();
	}


	/**
	 * Returns dropped count of associated entity activities.
	 * @see \Bitrix\Crm\Volume\IVolumeClearActivity::getDroppedActivityCount
	 *
	 * @return int
	 */
	public function getDroppedActivityCount()
	{
		return $this->droppedActivityCount;
	}

	/**
	 * Sets dropped count of associated entity activities.
	 * @see \Bitrix\Crm\Volume\IVolumeClearActivity::setDroppedActivityCount
	 *
	 * @param int $count Amount to set.
	 * @return void
	 */
	public function setDroppedActivityCount($count)
	{
		$this->droppedActivityCount = $count;
	}

	/**
	 * Returns dropped count of associated entity activities.
	 * @see \Bitrix\Crm\Volume\IVolumeClearActivity::incrementDroppedActivityCount
	 *
	 * @param int $count Amount to increment.
	 * @return void
	 */
	public function incrementDroppedActivityCount($count = 1)
	{
		$this->droppedActivityCount += $count;
	}


	/**
	 * Gets SQL query code for activity count.
	 *
	 * @param array $entityGroupField Entity fields to group by.
	 *
	 * @return string
	 */
	protected function prepareActivityRelationQuerySql(array $entityGroupField = array())
	{
		if (!(
			$this instanceof Volume\Deal ||
			$this instanceof Volume\Lead ||
			$this instanceof Volume\Quote ||
			$this instanceof Volume\Invoice ||
			$this instanceof Volume\Company ||
			$this instanceof Volume\Contact ||
			$this instanceof Volume\Activity
		))
		{
			return '';
		}

		$query = $this->prepareQuery();
		if ($this->prepareFilter($query))
		{
			$activityVolume = new Volume\Activity();
			$activityVolume->setFilter($this->getFilter());
		}
		else
		{
			return '';
		}

		//-----------

		$activityQuery = $activityVolume->prepareQuery(static::className());
		$activityVolume->prepareFilter($activityQuery);

		$fileCount = new ORM\Fields\ExpressionField('FILE_SIZE', '0');
		$fileBindingCount = new ORM\Fields\ExpressionField('FILE_COUNT', '0');
		$activityQuery
			->registerRuntimeField($fileCount)
			->registerRuntimeField($fileBindingCount)
			->addSelect('FILE_SIZE')
			->addSelect('FILE_COUNT');

		if (self::isModuleAvailable('disk'))
		{
			$diskCount = new ORM\Fields\ExpressionField('DISK_SIZE', '0');
			$diskBindingCount = new ORM\Fields\ExpressionField('DISK_COUNT', '0');
			$activityQuery
				->registerRuntimeField($diskCount)
				->registerRuntimeField($diskBindingCount)
				->addSelect('DISK_SIZE')
				->addSelect('DISK_COUNT');
		}

		$activityCount = new ORM\Fields\ExpressionField('ACTIVITY_COUNT', 'COUNT(DISTINCT %s)', 'ID');
		$activityBindingCount = new ORM\Fields\ExpressionField('BINDINGS_COUNT', 'COUNT(%s)', 'BINDINGS.ID');
		$activityQuery
			->registerRuntimeField($activityCount)
			->registerRuntimeField($activityBindingCount)
			->addSelect('ACTIVITY_COUNT')
			->addSelect('BINDINGS_COUNT');

		foreach ($entityGroupField as $alias => $field)
		{
			$activityQuery->addSelect($field, $alias);
			$activityQuery->addGroup($field);
		}

		//-----------

		$activityFileQuery = $activityVolume->getActivityFileMeasureQuery(static::className(), $entityGroupField);

		$activityCount = new ORM\Fields\ExpressionField('ACTIVITY_COUNT', '0');
		$activityBindingCount = new ORM\Fields\ExpressionField('BINDINGS_COUNT', '0');
		$activityFileQuery
			->registerRuntimeField($activityCount)
			->registerRuntimeField($activityBindingCount)
			->addSelect('ACTIVITY_COUNT')
			->addSelect('BINDINGS_COUNT');

		foreach ($entityGroupField as $alias => $field)
		{
			$field = str_replace('.', '_', $field);
			$activityFileQuery->addSelect($field, $alias);
			$activityFileQuery->addGroup($field);
		}

		$sqlQuery =
			$activityQuery->getQuery().
			' UNION '.
			$activityFileQuery->getQuery();

		return $sqlQuery;
	}

}
