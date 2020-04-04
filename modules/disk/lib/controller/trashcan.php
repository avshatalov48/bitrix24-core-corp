<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Internals\Engine;

final class TrashCan extends Engine\Controller
{
	/**
	 * Returns default pre-filters for action.
	 * @return array
	 */
	protected function getDefaultPreFilters()
	{
		$defaultPreFilters = parent::getDefaultPreFilters();
		$defaultPreFilters[] = new Engine\ActionFilter\CheckReadPermission();

		return $defaultPreFilters;
	}

	public function emptyAction(Disk\Storage $storage)
	{
		$indicator = new Disk\Volume\Storage\TrashCan();
		$indicator
			->setOwner($this->getCurrentUser()->getId())
			->addFilter('STORAGE_ID', $storage->getId())
			->purify()
			->measure([
				Disk\Volume\Base::DISK_FILE
		  	])
		;

		$task = $indicator->getMeasurementResult()->fetch();
		$taskId = $task['ID'];

		$agentParams = [
			'delay' => 5,
			'filterId' => $taskId,
			'ownerId' => $this->getCurrentUser()->getId(),
			'storageId' => $storage->getId(),
			Disk\Volume\Task::DROP_TRASHCAN => true,
		];

		Disk\Volume\Cleaner::addWorker($agentParams);
	}
}