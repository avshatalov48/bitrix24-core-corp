<?php
namespace Bitrix\Disk\Copy\Integration;

use Bitrix\Disk\BaseObject;
use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Disk\SystemUser;
use Bitrix\Main\Config\Option;
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
			$errorOffset = ($queueOption["errorOffset"] ?: 0);

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
			$offset = $this->getOffset($targetRootFolder) + $errorOffset;

			$executiveUserId = ($queueOption["executiveUserId"] ?: SystemUser::SYSTEM_USER_ID);
			$mapFolderIds = ($queueOption["mapFolderIds"] ?: []);
			$mapIdsCopiedFiles = ($queueOption["mapIdsCopiedFiles"] ?: []);

			$fileIds = $this->getFileIds($rootFolder);
			$count = count($fileIds);
			$fileIds = array_slice($fileIds, $offset, $limit);

			if (!$fileIds || !$mapFolderIds)
			{
				$this->onAfterCopy($queueOption);
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
					if (!$copiedFile || $file->getErrors())
					{
						$queueOption["errorOffset"] += 1;
					}
					$this->setFileRights($groupId, $copiedGroupId, $file, $copiedFile);
					$copiedFileIds[] = ($copiedFile->getId() ? $fileId : 0);
				}
				catch (\Throwable $exception)
				{
					$this->deleteQueueOption();
					return false;
				}
			}

			$queueOption["mapIdsCopiedFiles"] = $mapIdsCopiedFiles + $copiedFileIds;
			$this->saveQueueOption($queueOption);

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

	private function onAfterCopy(array $queueOption)
	{
		$this->saveErrorOption($queueOption);
	}

	private function saveErrorOption(array $queueOption)
	{
		$mapIdsCopiedFiles = $queueOption["mapIdsCopiedFiles"] ?: [];

		$mapIdsWithErrors = [];
		foreach ($mapIdsCopiedFiles as $fileId => $copiedFileId)
		{
			if (!$copiedFileId)
			{
				$mapIdsWithErrors[] = $fileId;
			}
		}

		if ($mapIdsWithErrors)
		{
			Option::set(self::$moduleId, $this->errorName, serialize($mapIdsWithErrors));
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

	private function setFileRights(
		int $groupId,
		int $copiedGroupId,
		BaseObject $sourceFile,
		BaseObject $copiedFile
	): void
	{
		$rightsManager = Driver::getInstance()->getRightsManager();

		$sourceRights = $rightsManager->getSpecificRights($sourceFile);

		$newRights = [];
		foreach	($sourceRights as $right)
		{
			unset($right['ID']);

			$right['OBJECT_ID'] = $copiedFile->getId();

			$right['ACCESS_CODE'] = $this->prepareAccessCodeByCopiedGroup(
				$groupId,
				$copiedGroupId,
				$right['ACCESS_CODE']
			);

			$newRights[] = $right;
		}

		$rightsManager->set($copiedFile, $newRights);
	}

	private function prepareAccessCodeByCopiedGroup(
		int $groupId,
		int $copiedGroupId,
		string $accessCode
	): string
	{
		if (mb_substr($accessCode, 0, 2) === 'SG')
		{
			[$code,] = explode('_', $accessCode);
			if ($groupId == mb_substr($code, 2))
			{
				$accessCode = str_replace($groupId, $copiedGroupId, $accessCode);
			}
		}

		return $accessCode;
	}

	private function getOffset(?Folder $targetRootFolder): int
	{
		$fileIds = $this->getFileIds($targetRootFolder);
		return count($fileIds);
	}

	protected function getQueue(): array
	{
		return $this->getOptionData($this->queueName);
	}

	protected function setQueue(array $queue): void
	{
		$queueId = (string) current($queue);
		$this->checkerName = (mb_strpos($this->checkerName, $queueId) === false ?
			$this->checkerName.$queueId : $this->checkerName);
		$this->baseName = (mb_strpos($this->baseName, $queueId) === false ?
			$this->baseName.$queueId : $this->baseName);
		$this->errorName = (mb_strpos($this->errorName, $queueId) === false ?
			$this->errorName.$queueId : $this->errorName);
	}

	protected function getQueueOption()
	{
		return $this->getOptionData($this->baseName);
	}

	protected function saveQueueOption(array $data)
	{
		Option::set(static::$moduleId, $this->baseName, serialize($data));
	}

	protected function deleteQueueOption()
	{
		$queue = $this->getQueue();
		$this->setQueue($queue);
		$this->deleteCurrentQueue($queue);
		Option::delete(static::$moduleId, ["name" => $this->checkerName]);
		Option::delete(static::$moduleId, ["name" => $this->baseName]);
	}

	protected function deleteCurrentQueue(array $queue): void
	{
		$queueId = current($queue);
		$currentPos = array_search($queueId, $queue);
		if ($currentPos !== false)
		{
			unset($queue[$currentPos]);
			Option::set(static::$moduleId, $this->queueName, serialize($queue));
		}
	}

	protected function isQueueEmpty()
	{
		$queue = $this->getOptionData($this->queueName);
		return empty($queue);
	}

	protected function getOptionData($optionName)
	{
		$option = Option::get(static::$moduleId, $optionName);
		$option = ($option !== "" ? unserialize($option, ['allowed_classes' => false]) : []);
		return (is_array($option) ? $option : []);
	}

	protected function deleteOption($optionName)
	{
		Option::delete(static::$moduleId, ["name" => $optionName]);
	}
}