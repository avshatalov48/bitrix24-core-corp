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
	 * @return boolean
	 */
	public function clearEvent();

	/**
	 * Returns dropped count of associated entity activities.
	 * @return int
	 */
	public function getDroppedEventCount();

	/**
	 * Sets dropped count of associated entity activities.
	 * @param int $count Amount to set.
	 * @return void
	 */
	public function setDroppedEventCount($count);

	/**
	 * Returns dropped count of associated entity activities.
	 * @param int $count Amount to increment.
	 * @return void
	 */
	public function incrementDroppedEventCount($count);
}
