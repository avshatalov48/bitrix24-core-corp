<?php
namespace Bitrix\Timeman\Usecase\Worktime\Manage\Approve;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Form\Worktime\WorktimeRecordForm;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\Service\Worktime\Result\WorktimeServiceResult;
use Bitrix\Timeman\UseCase\Worktime\BaseWorktimeHandler;

class Handler extends BaseWorktimeHandler
{
	public function handle(WorktimeRecordForm $recordForm)
	{
		$userId = DependencyManager::getInstance()->getWorktimeRepository()->findRecordOwnerUserId($recordForm->id);
		if (!$this->getPermissionManager()->canApproveWorktime($userId))
		{
			return WorktimeServiceResult::createWithErrorText(
				Loc::getMessage('TM_WORKTIME_RESULT_ERROR_PERMISSION_MANAGE_WORKTIME'),
				WorktimeServiceResult::ERROR_FOR_USER
			);
		}

		return $this->getWorktimeService()->approveWorktimeRecord($recordForm);
	}
}