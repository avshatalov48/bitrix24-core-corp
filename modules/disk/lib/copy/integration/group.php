<?php
namespace Bitrix\Disk\Copy\Integration;

use Bitrix\Disk\Driver;
use Bitrix\Disk\Folder;
use Bitrix\Main\Config\Option;
use Bitrix\Socialnetwork\Copy\Integration\Feature;

class Group implements Feature
{
	private $executiveUserId;
	private $features = [];
	private $securityContext;

	const MODULE_ID = "disk";
	const QUEUE_OPTION = "DiskGroupQueue";
	const CHECKER_OPTION = "DiskGroupChecker_";
	const STEPPER_OPTION = "DiskGroupStepper_";
	const STEPPER_CLASS = GroupStepper::class;
	const ERROR_OPTION = "DiskGroupError_";

	public function __construct($executiveUserId = 0, array $features = [])
	{
		$this->executiveUserId = $executiveUserId;
		$this->features = $features;
		$this->securityContext = Driver::getInstance()->getFakeSecurityContext();
	}

	public function copy($groupId, $copiedGroupId)
	{
		$this->addToQueue($copiedGroupId);

		$storage = Driver::getInstance()->getStorageByGroupId($groupId);
		$targetStorage = Driver::getInstance()->getStorageByGroupId($copiedGroupId);

		if ($storage && $targetStorage)
		{
			$rootFolder = $storage->getRootObject();
			$targetRootFolder = $targetStorage->getRootObject();

			$mapFolderIds = [];
			$this->copyFolders($rootFolder, $targetRootFolder, $mapFolderIds);

			if (!in_array("onlyFolders", $this->features))
			{
				Option::set(self::MODULE_ID, self::CHECKER_OPTION.$copiedGroupId, "Y");

				$dataToCopy = [
					"groupId" => $groupId,
					"copiedGroupId" => $copiedGroupId,
					"executiveUserId" => $this->executiveUserId,
					"mapFolderIds" => $mapFolderIds
				];
				Option::set(self::MODULE_ID, self::STEPPER_OPTION.$copiedGroupId, serialize($dataToCopy));

				$agent = \CAgent::getList([], [
					"MODULE_ID" => self::MODULE_ID,
					"NAME" => GroupStepper::class."::execAgent();"
				])->fetch();
				if (!$agent)
				{
					GroupStepper::bind(1);
				}
			}
		}
	}

	private function copyFolders(Folder $rootFolder, Folder $targetRootFolder, array &$mapFolderIds)
	{
		$params = ["select" => ["*", "HAS_SUBFOLDERS"]];
		foreach ($rootFolder->getChildren($this->securityContext, $params) as $child)
		{
			if ($child instanceof Folder)
			{
				$newFolder = $targetRootFolder->addSubFolder([
					"NAME" => $child->getName(),
					"CREATED_BY" => $this->executiveUserId,
				]);
				if ($newFolder)
				{
					$mapFolderIds[$child->getId()] = $newFolder->getId();
					if ($child->getChildren($this->securityContext))
					{
						$this->copyFolders($child, $newFolder, $mapFolderIds);
					}
				}
			}
		}
		$mapFolderIds[$rootFolder->getId()] = $targetRootFolder->getId();
	}

	private function addToQueue(int $copiedGroupId)
	{
		$option = Option::get(self::MODULE_ID, self::QUEUE_OPTION, "");
		$option = ($option !== "" ? unserialize($option) : []);
		$option = (is_array($option) ? $option : []);

		$option[] = $copiedGroupId;
		Option::set(self::MODULE_ID, self::QUEUE_OPTION, serialize($option));
	}
}