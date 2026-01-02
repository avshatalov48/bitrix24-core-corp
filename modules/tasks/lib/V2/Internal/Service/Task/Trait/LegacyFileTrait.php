<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Trait;

trait LegacyFileTrait
{
	private function addFiles(array $fields, int $userId, int $taskId, bool $checkFileRights): void
	{
		if (
			!isset($fields['FILES'])
			|| !is_array($fields['FILES'])
		)
		{
			return;
		}

		$fileIds = array_map(function($el) {
			return (int)$el;
		}, $fields['FILES']);

		if (empty($fileIds))
		{
			return;
		}

		\CTaskFiles::AddMultiple(
			$taskId, $fileIds, [
					   'USER_ID' => $userId,
					   'CHECK_RIGHTS_ON_FILES' => $checkFileRights,
				   ]
		);
	}
}