<?php
namespace Bitrix\Disk\Copy\Integration;

use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\SystemUser;
use Bitrix\Main\Loader;
use Bitrix\Main\Update\Stepper;

class GroupStepper extends Stepper
{
	protected static $moduleId = "disk";

	protected $queueName = "DiskGroupQueue";
	protected $checkerName = "DiskGroupChecker_";
	protected $baseName = "DiskGroupStepper_";
	protected $errorName = "DiskGroupError_";

	/**
	 * Executes some action, and if return value is false, agent will be deleted.
	 * @param array $option Array with main data to show if it is necessary like {steps : 35, count : 7},
	 * where steps is an amount of iterations, count - current position.
	 * @return boolean
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function execute(array &$option)
	{
		if (!Loader::includeModule(self::$moduleId))
		{
			return false;
		}

		try
		{
			$queue = $this->getQueue();
			$this->setQueue($queue);
			$queueOption = $this->getOptionData($this->baseName);
			if (empty($queueOption))
			{
				$this->deleteQueueOption();
				return !$this->isQueueEmpty();
			}

			$groupId = ($queueOption["groupId"] ?: 0);
			$copiedGroupId = ($queueOption["copiedGroupId"] ?: 0);

			$storage = Driver::getInstance()->getStorageByGroupId($groupId);
			$targetStorage = Driver::getInstance()->getStorageByGroupId($copiedGroupId);
			if (!$storage || !$targetStorage)
			{
				$this->deleteQueueOption();
				return !$this->isQueueEmpty();
			}

			$rootFolder = $storage->getRootObject();
			$targetRootFolder = $targetStorage->getRootObject();

			$limit = 5;
			$offset = $this->getOffset($targetRootFolder);

			$executiveUserId = ($queueOption["executiveUserId"] ?: SystemUser::SYSTEM_USER_ID);
			$mapFolderIds = ($queueOption["mapFolderIds"] ?: []);

			$fileIds = $this->getFileIds($rootFolder);
			$count = count($fileIds);
			$fileIds = array_slice($fileIds, $offset, $limit);

			if (!$fileIds || !$mapFolderIds)
			{
				$this->deleteCurrentQueue($queue);
				$this->deleteQueueOption();
				return !$this->isQueueEmpty();
			}

			$copiedFileIds = [];
			foreach ($fileIds as $fileId)
			{
				try
				{
					$file = File::loadById($fileId);
					$folderId = $this->getFolderId($file);
					$targetFolderId = ($mapFolderIds[$folderId] ?: 0);
					$targetFolder = Folder::loadById($targetFolderId);
					$copiedFile = $file->copyTo($targetFolder, $executiveUserId);
					$copiedFileIds[] = ($copiedFile && $copiedFile->getId() ? $fileId : 0);
				}
				catch (\Throwable $exception)
				{
					continue;
				}
			}

			$option["count"] = $count;
			$option["steps"] = $offset;

			return true;
		}
		catch (\Exception $exception)
		{
			$this->writeToLog($exception);
			$this->deleteQueueOption();
			return false;
		}
	}

	private function getFolderId(File $file)
	{
		$folderId = $file->getParentId();
		if (empty($folderId))
		{
			$storage = $file->getStorage();
			$folderId = $storage->getRootObject()->getId();
		}
		return $folderId;
	}

	private function getFileIds(?Folder $rootFolder): array
	{
		$fileIds = [];

		$securityContext = Driver::getInstance()->getFakeSecurityContext();

		$params = ["select" => ["*", "HAS_SUBFOLDERS"]];
		foreach ($rootFolder->getChildren($securityContext, $params) as $child)
		{
			if ($child instanceof Folder)
			{
				if ($child->getChildren($securityContext))
				{
					$fileIds = array_merge($fileIds, $this->getFileIds($child));
				}
			}
			elseif ($child instanceof File)
			{
				$fileIds[] = $child->getId();
			}
		}

		return $fileIds;
	}

	private function getOffset(?Folder $targetRootFolder): int
	{
		$fileIds = $this->getFileIds($targetRootFolder);
		return count($fileIds);
	}
}