<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Bizproc;
use Bitrix\Main\Localization\Loc;

class BizprocMobileCommentsComponent extends CBitrixComponent
{
	private ErrorCollection $errors;

	private function checkModules(): bool
	{
		if (!Loader::includeModule('bizproc'))
		{
			$this->errors->setError(new Error('Bizproc module is not installed', 'BIZPROC_MODULE_NOT_INSTALLED'));
		}

		if (!Loader::includeModule('forum'))
		{
			$this->errors->setError(new Error('Forum module is not installed', 'FORUM_MODULE_NOT_INSTALLED'));
		}

		return $this->errors->isEmpty();
	}

	private function checkPermissions(): bool
	{
		$currentUserId = $this->arResult['USER_ID'] = (int)CurrentUser::get()->getId();
		$targetUserId = (int)($this->arParams['USER_ID'] ?? 0);

		if (!$currentUserId)
		{
			$this->errors->setError(new Error('Current user is not defined', 'ACCESS_DENIED'));
		}

		if ($currentUserId !== $targetUserId)
		{
			$this->errors->setError(new Error('Access denied', 'ACCESS_DENIED'));
		}

		return $this->errors->isEmpty();
	}

	private function checkParameters(): void
	{
		$this->arResult['NAME_TEMPLATE'] = (
			empty($this->arParams['NAME_TEMPLATE'])
				? CSite::GetNameFormat(false)
				: str_replace(['#NOBR#','#/NOBR#'], ['', ''], $this->arParams['NAME_TEMPLATE'])
		);
		$this->arResult['DATE_TIME_FORMAT'] = $this->arParams['DATE_TIME_FORMAT'] ?? FORMAT_DATETIME;
		$this->arResult['GUID'] = $this->arParams['GUID'] ?? null;
		$this->arResult['PATH_TEMPLATE_TO_USER_PROFILE'] =
			$this->arParams['PATH_TEMPLATE_TO_USER_PROFILE']
			?? '/company/personal/user/#user_id#/'
		;
	}

	public function getData(): void
	{
		$workflow = Bizproc\WorkflowStateTable::getById($this->arParams['WORKFLOW_ID'])->fetchObject();

		if (!$workflow)
		{
			$this->errors->setError(new Error(Loc::getMessage('BPMOBILE_COMMENTS_WORKFLOW_NOT_FOUND')));

			return;
		}

		$workflowId = $workflow->getId();
		$intId = CBPStateService::getWorkflowIntegerId($workflowId);

		$this->arResult['WORKFLOW'] = [
			'ID' => $workflowId,
			'ID_INT' => $intId,
			'STARTED_BY' => $workflow->getStartedBy() ?? 0,
		];
		$this->arResult['FORUM_ID'] = \CBPHelper::getForumId();
		$this->arResult['LOG_ID'] = $this->getLogId($intId);
	}

	private function getLogId(int $workflowIntId): int
	{
		if (!Loader::includeModule('socialnetwork'))
		{
			return 0;
		}

		$logId = 0;

		$res = CSocNetLog::getList(
			[],
			[
				'EVENT_ID' => 'lists_new_element',
				'SOURCE_ID' => $workflowIntId,
			],
			false,
			false,
			['ID']
		);
		if ($item = $res->Fetch())
		{
			$logId = (int)$item['ID'];
		}

		return $logId;
	}

	public function executeComponent()
	{
		$this->arResult['ERRORS'] = [];
		$this->errors = new ErrorCollection();

		if ($this->checkModules() && $this->checkPermissions())
		{
			$this->checkParameters();
			$this->getData();
		}

		$this->arResult['ERRORS'] = $this->errors->getValues();

		$this->includeComponentTemplate();
	}
}
