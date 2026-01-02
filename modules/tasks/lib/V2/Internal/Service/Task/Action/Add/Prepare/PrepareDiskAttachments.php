<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Disk\AttachedObject;
use Bitrix\Disk\Uf\FileUserType;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Entity\UF\UserField;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Integration\Disk;

class PrepareDiskAttachments implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		if (
			!$this->config->isCloneAttachments()
			|| !Loader::includeModule('disk')
		)
		{
			return $fields;
		}

		if (empty($fields[UserField::TASK_ATTACHMENTS]))
		{
			return $fields;
		}

		$source = $fields[UserField::TASK_ATTACHMENTS];
		$fields[UserField::TASK_ATTACHMENTS] = Disk::cloneFileAttachment(
			$fields[UserField::TASK_ATTACHMENTS],
			$this->config->getUserId()
		);

		if (count($source) !== count($fields[UserField::TASK_ATTACHMENTS]))
		{
			return $fields;
		}

		$relations = array_combine($source, $fields[UserField::TASK_ATTACHMENTS]);

		return $this->updateInlineFiles($fields, $relations);
	}

	private function updateInlineFiles(array $fields, array $relations): array
	{
		if (empty($relations))
		{
			return $fields;
		}

		$searchTpl = '[DISK FILE ID=%s]';

		$search = [];
		$replace = [];

		foreach ($relations as $source => $destination)
		{
			$search[] = sprintf($searchTpl, $source);
			$replace[] = sprintf($searchTpl, $destination);

			if (!preg_match('/^' . FileUserType::NEW_FILE_PREFIX . '/', (string)$source))
			{
				$attachedObject = AttachedObject::loadById($source);
				if ($attachedObject)
				{
					$search[] = sprintf($searchTpl, FileUserType::NEW_FILE_PREFIX . $attachedObject->getObjectId());
					$replace[] = sprintf($searchTpl, $destination);
				}
			}
		}

		$fields['DESCRIPTION'] = str_replace($search, $replace, $fields['DESCRIPTION']);

		return $fields;
	}
}
