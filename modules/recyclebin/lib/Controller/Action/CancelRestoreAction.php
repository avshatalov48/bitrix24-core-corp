<?php
namespace Bitrix\Recyclebin\Controller\Action;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Recyclebin\Internals\BatchActionManager;

class CancelRestoreAction extends Action
{
	final public function run(array $params): ?array
	{
		global $USER;

		if (!$USER->IsAuthorized())
		{
			$this->addError(new Error('Access denied.'));

			return null;
		}

		$hash = $params['hash'] ?? null;
		if (empty($hash))
		{
			$this->addError(new Error('The parameter hash is required.'));

			return null;
		}

		$deletionManager = new BatchActionManager();
		$deletionManager->deleteFromSession(BatchActionManager::RESTORE_PROGRESS_SESSION_NAME, $hash);
		$deletionManager->deleteFromSession(BatchActionManager::RESTORE_DATA_SESSION_NAME, $hash);

		return [
			'hash' => $hash,
		];
	}
}