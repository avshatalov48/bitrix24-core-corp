<?php
namespace Bitrix\Recyclebin\Controller\Action;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Error;
use Bitrix\Recyclebin\Internals\BatchActionManager;

class PrepareRestoreAction extends Action
{
	use PrepareTrait;

	final public function run(array $params): ?array
	{
		global $USER;

		if (!$USER->IsAuthorized())
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
		return BatchActionManager::RESTORE_DATA_SESSION_NAME;
	}

	protected function getProgressSessionName(): string
	{
		return BatchActionManager::RESTORE_PROGRESS_SESSION_NAME;
	}
}