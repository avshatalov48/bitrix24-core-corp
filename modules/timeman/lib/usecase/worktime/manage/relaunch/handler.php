<?php
namespace Bitrix\Timeman\Usecase\Worktime\Manage\Relaunch;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Timeman\UseCase\Worktime\BaseWorktimeHandler;

class Handler extends BaseWorktimeHandler
{
	public function handle($recordForm)
	{
		if (!$this->getPermissionManager()->canManageWorktime())
		{
			return WorktimeServiceResult::createWithErrorText(
				Loc::getMessage('TM_WORKTIME_RESULT_ERROR_PERMISSION_MANAGE_WORKTIME'),
				WorktimeServiceResult::ERROR_FOR_USER
			);
		}

		return $this->getWorktimeService()->continueWork($recordForm);
	}
}