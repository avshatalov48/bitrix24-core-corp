<?php

declare(strict_types=1);


namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\V2\Internal\Service\Task\Trait\LegacyFileTrait;

class UpdateLegacyFiles
{
	use ConfigTrait;
	use LegacyFileTrait;

	public function __invoke(array $fields, array $fullTaskData, array $changes): void
	{
		if (
			isset($fields["FILES"])
			&& (isset($changes["NEW_FILES"]) || isset($changes["DELETED_FILES"]))
		)
		{
			$taskId = (int)$fullTaskData['ID'];
			$arNotDeleteFiles = $fields["FILES"];
			\CTaskFiles::DeleteByTaskID($taskId, $arNotDeleteFiles);
			$this->addFiles($fields, $this->config->getUserId(), $taskId, $this->config->isCheckFileRights());
		}
	}
}