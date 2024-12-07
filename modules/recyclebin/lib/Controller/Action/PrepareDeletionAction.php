<?php
namespace Bitrix\Recyclebin\Controller\Action;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Recyclebin\Internals\BatchActionManager;
use Bitrix\Recyclebin\Internals\User;

class PrepareDeletionAction extends Action
{
	use PrepareTrait;

	final public function run(array $params): ?array
	{
		if (!User::isSuper() && !User::isAdmin())
		{
			$this->addError(new Error('Access denied.'));

			return null;
		}

		$gridId = ($params['gridId'] ?? null);
		if ($gridId === null)
		{
			$this->addError(new Error('The parameter gridId is required.'));

			return null;
		}

		return $this->doAction($params);
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