<?php

namespace Bitrix\Crm\Volume;

interface IVolumeClearFile extends \Bitrix\Crm\Volume\IVolumeClear
{
	/**
	 * Returns availability to drop entity attachments.
	 * @return boolean
	 */
	public function canClearFile();

	/**
	 * Performs dropping entity attachments.
	 * @return int
	 */
	public function clearFiles();

	/**
	 * Returns count of entities.
	 * @return int
	 */
	public function countEntityWithFile();

	/**
	 * Returns dropped count of entity attachments.
	 * @return int
	 */
	public function getDroppedFileCount();

	/**
	 * Sets dropped count of entity attachments.
	 * @param int $count Amount to set.
	 * @return void
	 */
	public function setDroppedFileCount($count);

	/**
	 * Returns dropped count of entity attachments.
	 * @param int $count Amount to increment.
	 * @return void
	 */
	public function incrementDroppedFileCount($count);
}
