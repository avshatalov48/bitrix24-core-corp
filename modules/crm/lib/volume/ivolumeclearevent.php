<?php

namespace Bitrix\Crm\Volume;

interface IVolumeClearEvent extends \Bitrix\Crm\Volume\IVolumeClear
{
	/**
	 * Runs measure test for activities.
	 * @param array $additionEventFilter Filter for activity list.
	 * @return self
	 */
	public function measureEvent($additionEventFilter = array());

	/**
	 * Returns availability to drop entity activities.
	 * @return boolean
	 * @see \Bitrix\Crm\Volume\ClearEvent::canClearEvent
	 */
	public function canClearEvent();

	/**
	 * Returns count of activities.
	 * @param array $additionEventFilter Filter for activity list.
	 * @return int
	 */
	public function countEvent($additionEventFilter = array());

	/**
	 * Performs dropping associated entity activities.
	 * @return int
	 */
	public function clearEvent();

	/**
	 * Returns dropped count of associated entity activities.
	 * @return int
	 * @see \Bitrix\Crm\Volume\ClearEvent::getDroppedEventCount
	 */
	public function getDroppedEventCount();

	/**
	 * Sets dropped count of associated entity activities.
	 * @param int $count Amount to set.
	 * @return void
	 * @see \Bitrix\Crm\Volume\ClearEvent::setDroppedEventCount
	 */
	public function setDroppedEventCount($count);

	/**
	 * Returns dropped count of associated entity activities.
	 * @param int $count Amount to increment.
	 * @return void
	 * @see \Bitrix\Crm\Volume\ClearEvent::incrementDroppedEventCount
	 */
	public function incrementDroppedEventCount($count);
}
