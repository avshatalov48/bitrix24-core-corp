<?php
namespace Bitrix\Recyclebin\Controller\Action;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Recyclebin\Internals\BatchActionManager;
use Bitrix\Recyclebin\Internals\User;
use Bitrix\Recyclebin\Recyclebin;

class ProcessDeletionAction extends Action
{
	use ProcessTrait;

	protected const LIMIT = 10;

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

		return $this->doAction($params);
	}

	protected function doProcessAction(int $currentEntityId): mixed
	{
		return Recyclebin::remove($currentEntityId);
	}

	protected function getErrorMessage(): string
	{
		return Loc::getMessage('RECYCLEBIN_PROCESS_DELETION_ACTION_COMMON_DELETION_ERROR');
	}

	protected function getDataSessionName(): string
	{
		return BatchActionManager::DELETION_DATA_SESSION_NAME;
	}

	protected function getProgressSessionName(): string
	{
		return BatchActionManager::DELETION_PROGRESS_SESSION_NAME;
	}
}