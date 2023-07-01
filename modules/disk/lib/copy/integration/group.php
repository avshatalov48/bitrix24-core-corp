<?php
namespace Bitrix\Disk\Copy\Integration;

use Bitrix\Disk\BaseObject;
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
			if ($storage->isEnabledShowExtendedRights())
			{
				$targetStorage->enableShowExtendedRights();
			}

			$rootFolder = $storage->getRootObject();
			$targetRootFolder = $targetStorage->getRootObject();

			$mapFolderIds = [];
			$this->copyFolders(
				$groupId,
				$copiedGroupId,
				$rootFolder,
				$targetRootFolder,
				$mapFolderIds
			);

			if (!in_array("onlyFolders", $this->features))
			{
				Option::set(self::MODULE_ID, self::CHECKER_OPTION.$copiedGroupId, "Y");

				$dataToCopy = [
					"groupId" => $groupId,
					"copiedGroupId" => $copiedGroupId,
					"executiveUserId" => $this->executiveUserId,
					"mapFolderIds" => $mapFolderIds
				];
				Option::set(
					self::MODULE_ID,
					self::STEPPER_OPTION . $copiedGroupId,
					serialize($dataToCopy)
				);

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

	private function copyFolders(
		int $groupId,
		int $copiedGroupId,
		Folder $folder,
		Folder $targetFolder,
		array &$mapFolderIds
	)
	{
		$mapFolderIds[$folder->getId()] = $targetFolder->getId();

		$this->setFolderRights(
			$groupId,
			$copiedGroupId,
			$folder,
			$targetFolder
		);

		$children = $folder->getChildren(
			$this->securityContext,
			[
				'select' => [
					'*',
					'HAS_SUBFOLDERS',
				]
			]
		);
		foreach ($children as $child)
		{
			if ($child instanceof Folder)
			{
				$newFolder = $targetFolder->addSubFolder(
					[
						'NAME' => $child->getName(),
						'CREATED_BY' => $this->executiveUserId,
					]
				);
				if ($newFolder)
				{
					$mapFolderIds[$child->getId()] = $newFolder->getId();

					if ($child->getChildren($this->securityContext))
					{
						$this->copyFolders(
							$groupId,
							$copiedGroupId,
							$child,
							$newFolder,
							$mapFolderIds
						);
					}
				}
			}
		}
	}

	private function setFolderRights(
		int $groupId,
		int $copiedGroupId,
		BaseObject $folder,
		BaseObject $targetFolder
	): void
	{
		$rightsManager = Driver::getInstance()->getRightsManager();

		$sourceRights = $rightsManager->getSpecificRights($folder);

		$targetRights = [];
		foreach	($sourceRights as $right)
		{
			unset($right['ID']);

			$right['OBJECT_ID'] = $targetFolder->getId();

			$right['ACCESS_CODE'] = $this->prepareAccessCodeByCopiedGroup(
				$groupId,
				$copiedGroupId,
				$right['ACCESS_CODE']
			);

			$targetRights[] = $right;
		}

		$rightsManager->set($targetFolder, $targetRights);
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

	private function addToQueue(int $copiedGroupId)
	{
		$option = Option::get(self::MODULE_ID, self::QUEUE_OPTION, "");
		$option = ($option !== "" ? unserialize($option, ['allowed_classes' => false]) : []);
		$option = (is_array($option) ? $option : []);

		$option[] = $copiedGroupId;
		Option::set(self::MODULE_ID, self::QUEUE_OPTION, serialize($option));
	}
}