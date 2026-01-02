<?php

declare(strict_types=1);


namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add;

use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Entity\UF\UserField;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\ParticipantTrait;
use CIBlock;
use CIBlockElement;
use CIBlockElementRights;
use CWebDavIblock;

class AddWebDavFiles
{
	use ParticipantTrait;
	use ConfigTrait;

	public function __invoke(array $fields): void
	{
		if (
			!isset($fields[UserField::TASK_ATTACHMENTS])
			|| !is_array($fields[UserField::TASK_ATTACHMENTS])
		)
		{
			return;
		}

		$filesIds = array_filter($fields[UserField::TASK_ATTACHMENTS]);

		if (empty($filesIds))
		{
			return;
		}

		$this->addFilesRights($filesIds, $fields);
	}

	private function addFilesRights(array $filesIds, array $fields): void
	{
		$filesIds = array_unique(array_filter($filesIds));

		// Nothing to do?
		if (empty($filesIds))
		{
			return;
		}

		if (
			!Loader::includeModule('webdav')
			|| !Loader::includeModule('iblock')
		)
		{
			return;
		}

		$rightsTasks = CWebDavIblock::GetTasks();

		$members = $this->getParticipants($fields);

		$ibe = new CIBlockElement();
		$dbWDFile = $ibe->GetList(
			[],
			[
				'ID' => $filesIds,
				'SHOW_NEW' => 'Y',
			],
			false,
			false,
			['ID', 'NAME', 'SECTION_ID', 'IBLOCK_ID', 'WF_NEW']
		);

		if (!$dbWDFile)
		{
			return;
		}

		$i = 0;
		$rightsForTaskMembers = [];
		foreach ($members as $userId)
		{
			// For intranet users and their managers
			$rightsForTaskMembers['n' . $i++] = [
				'GROUP_CODE' => 'IU' . $userId,
				'TASK_ID' => $rightsTasks['R'],        // rights for reading
			];

			// For extranet users
			$rightsForTaskMembers['n' . $i++] = [
				'GROUP_CODE' => 'U' . $userId,
				'TASK_ID' => $rightsTasks['R'],        // rights for reading
			];
		}
		$iNext = $i;

		while ($wdFile = $dbWDFile->Fetch())
		{
			if (!$wdFile['IBLOCK_ID'])
			{
				continue;
			}

			$fileId = $wdFile['ID'];

			if (!CIBlock::GetArrayByID($wdFile['IBLOCK_ID'], "RIGHTS_MODE") === "E")
			{
				continue;
			}
			$ibRights = new CIBlockElementRights($wdFile['IBLOCK_ID'], $fileId);
			$curRightsRaw = $ibRights->getRights();

			// Preserve existing rights
			$i = $iNext;
			$rights = $rightsForTaskMembers;
			foreach ($curRightsRaw as $rightsData)
			{
				$rights['n' . $i++] = [
					'GROUP_CODE' => $rightsData['GROUP_CODE'],
					'TASK_ID' => $rightsData['TASK_ID'],
				];
			}

			$ibRights->setRights($rights);
		}
	}
}
