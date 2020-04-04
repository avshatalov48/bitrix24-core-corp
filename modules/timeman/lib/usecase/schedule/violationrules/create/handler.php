<?php
namespace Bitrix\Timeman\UseCase\Schedule\ViolationRules\Create;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Timeman\Form\Schedule\ViolationForm;
use Bitrix\Timeman\Service\DependencyManager;
use Bitrix\Timeman\UseCase\BaseUseCaseHandler;

class Handler extends BaseUseCaseHandler
{
	/**
	 * @param ViolationForm $violationForm
	 * @return Result|\Bitrix\Timeman\Service\Schedule\Result\ViolationRulesServiceResult
	 */
	public function handle(ViolationForm $violationForm)
	{
		if (!$violationForm->scheduleId || !$violationForm->entityCode
			|| !$this->getPermissionManager()->canCreateViolationRules($violationForm->entityCode))
		{
			return (new Result())->addError(new Error(Loc::getMessage('TM_WORKTIME_RESULT_ERROR_PERMISSION_ACCESS_DENIED')));
		}

		return DependencyManager::getInstance()->getViolationRulesService()->add($violationForm);
	}

}