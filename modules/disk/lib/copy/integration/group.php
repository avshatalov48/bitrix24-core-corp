<?php
namespace Bitrix\Disk\Copy\Integration;

use Bitrix\Disk\Driver;
use Bitrix\Disk\File;
use Bitrix\Disk\Folder;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Socialnetwork\Copy\Integration\Feature;
use Bitrix\Socialnetwork\Copy\Integration\Helper;

class Group implements Feature, Helper
{
	private $stepper;

	private $executiveUserId;
	private $features = [];
	private $securityContext;

	private $moduleId = "disk";
	private $queueOption = "DiskGroupQueue";
	private $checkerOption = "DiskGroupChecker_";
	private $stepperOption = "DiskGroupStepper_";
	private $errorOption = "DiskGroupError_";

	public function __construct($executiveUserId = 0, array $features = [])
	{
		$this->executiveUserId = $executiveUserId;
		$this->features = $features;
		$this->securityContext = Driver::getInstance()->getFakeSecurityContext();

		$this->stepper = GroupStepper::class;
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
				Option::set($this->moduleId, $this->checkerOption.$copiedGroupId, "Y");

				$dataToCopy = [
					"groupId" => $groupId,
					"copiedGroupId" => $copiedGroupId,
					"executiveUserId" => $this->executiveUserId,
					"mapFolderIds" => $mapFolderIds
				];
				Option::set($this->moduleId, $this->stepperOption.$copiedGroupId, serialize($dataToCopy));

				$agent = \CAgent::getList([], [
					"MODULE_ID" => $this->moduleId,
					"NAME" => $this->stepper."::execAgent();"
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
		$option = Option::get($this->moduleId, $this->queueOption, "");
		$option = ($option !== "" ? unserialize($option) : []);
		$option = (is_array($option) ? $option : []);

		$option[] = $copiedGroupId;
		Option::set($this->moduleId, $this->queueOption, serialize($option));
	}

	/**
	 * Returns a module id for work with options.
	 * @return string
	 */
	public function getModuleId()
	{
		return $this->moduleId;
	}

	/**
	 * Returns a map of option names.
	 *
	 * @return array
	 */
	public function getOptionNames()
	{
		return [
			"queue" => $this->queueOption,
			"checker" => $this->checkerOption,
			"stepper" => $this->stepperOption,
			"error" => $this->errorOption
		];
	}

	/**
	 * Returns a link to stepper class.
	 * @return string
	 */
	public function getLinkToStepperClass()
	{
		return $this->stepper;
	}

	/**
	 * Returns a text map.
	 * @return array
	 */
	public function getTextMap()
	{
		return [
			"title" => Loc::getMessage("GROUP_STEPPER_PROGRESS_TITLE"),
			"error" => Loc::getMessage("GROUP_STEPPER_PROGRESS_ERROR")
		];
	}
}