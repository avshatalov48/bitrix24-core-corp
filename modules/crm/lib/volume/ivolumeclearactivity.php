<?php

namespace Bitrix\Crm\Volume;

interface IVolumeClearActivity extends \Bitrix\Crm\Volume\IVolumeClear
{
	/**
	 * Runs measure test for activities.
	 * @param array $additionActivityFilter Filter for activity list.
	 * @return self
	 */
	public function measureActivity($additionActivityFilter = array());

	/**
	 * Returns availability to drop entity activities.
	 * @return boolean
	 */
	public function canClearActivity();

	/**
	 * Returns count of activities.
	 * @param array $additionActivityFilter Filter for activity list.
	 * @return int
	 */
	public function countActivity($additionActivityFilter = array());

	/**
	 * Performs dropping associated entity activities.
	 * @return int
	 */
	public function clearActivity();

	/**
	 * Returns dropped count of associated entity activities.
	 * @return int
	 */
	public function getDroppedActivityCount();

	/**
	 * Sets dropped count of associated entity activities.
	 * @param int $count Amount to set.
	 * @return void
	 */
	public function setDroppedActivityCount($count);

	/**
	 * Returns dropped count of associated entity activities.
	 * @param int $count Amount to increment.
	 * @return void
	 */
	public function incrementDroppedActivityCount($count);
}
