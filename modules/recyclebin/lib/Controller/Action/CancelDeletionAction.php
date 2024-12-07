<?php
namespace Bitrix\Recyclebin\Controller\Action;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Recyclebin\Internals\BatchActionManager;
use Bitrix\Recyclebin\Internals\User;

class CancelDeletionAction extends Action
{
	final public function run(array $params): ?array
	{
		if (!User::isSuper() && !User::isAdmin())
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
		$deletionManager->deleteFromSession(BatchActionManager::DELETION_PROGRESS_SESSION_NAME, $hash);
		$deletionManager->deleteFromSession(BatchActionManager::DELETION_DATA_SESSION_NAME, $hash);

		return [
			'hash' => $hash,
		];
	}
}