<?php

namespace Bitrix\Crm\Volume;

interface IVolumeClear
{
	/**
	 * Returns availability to drop entity.
	 * @return boolean
	 */
	public function canClearEntity();

	/**
	 * Performs dropping entity.
	 * @return int
	 */
	public function clearEntity();

	/**
	 * Returns count of entities.
	 * @return int
	 */
	public function countEntity();

	/**
	 * Sets dropped count of entities.
	 * @param int $count Amount to set.
	 * @return void
	 */
	public function setDroppedEntityCount($count);

	/**
	 * Returns dropped count of entities.
	 * @param int $count Amount to increment.
	 * @return void
	 */
	public function incrementDroppedEntityCount($count);

	/**
	 * Returns dropped count of entities.
	 * @return int
	 */
	public function getDroppedEntityCount();

	/**
	 * Returns error count.
	 *
	 * @implements Crm\Volume\IVolumeClear
	 * @return int
	 */
	public function getFailCount();

	/**
	 * Sets error count.
	 *
	 * @implements Crm\Volume\IVolumeClear
	 * @param int $count Amount to set.
	 * @return void
	 */
	public function setFailCount($count);

	/**
	 * Returns error count.
	 *
	 * @implements Crm\Volume\IVolumeClearEvent
	 * @param int $count Amount to increment.
	 * @return void
	 */
	public function incrementFailCount($count = 1);

	/**
	 * Returns process offset.
	 * @return int
	 */
	public function getProcessOffset();

	/**
	 * Setup process offset.
	 * @param int $offset Offset position.
	 * @return void
	 */
	public function setProcessOffset($offset);

	/**
	 * Getting array of errors.
	 * @return \Bitrix\Main\Error[]
	 */
	public function getErrors();

	/**
	 * Has errors.
	 * @return boolean
	 */
	public function hasErrors();

	/**
	 * Returns errors list.
	 *
	 * @implements Crm\Volume\IVolumeClear
	 * @return \Bitrix\Main\Error|null
	 */
	public function getLastError();

	/**
	 * Start up timer.
	 * @param int $timeLimit Time limit.
	 * @return void
	 */
	public function startTimer($timeLimit = 25);

	/**
	 * Tells true if time limit reached.
	 * @return boolean
	 */
	public function hasTimeLimitReached();
}
