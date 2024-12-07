<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!\Bitrix\Main\Loader::includeModule('rpa'))
{
	return;
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Rpa;

class RpaAutomationAddRobotComponent extends Rpa\Components\Base
{
	public function onPrepareComponentParams($arParams)
	{
		$arParams["typeId"] = (int)$arParams["typeId"];
		$this->fillParameterFromRequest('stage', $arParams);
		$arParams["SET_TITLE"] = (isset($arParams["SET_TITLE"]) && $arParams["SET_TITLE"] === "N" ? "N" : "Y");

		return $arParams;
	}

	public function executeComponent()
	{
		if (!Rpa\Integration\Bizproc\Automation\Factory::canUseAutomation())
		{
			return;
		}

		$this->arResult['DOCUMENT_TYPE'] = Rpa\Integration\Bizproc\Document\Item::makeComplexType(
			$this->arParams['typeId']
		);

		if (!$this->checkPermissions($this->arResult['DOCUMENT_TYPE']))
		{
			$this->showError(Loc::getMessage('RPA_MODIFY_TYPE_ACCESS_DENIED'));
			return;
		}

		$this->arResult['ROBOTS'] = \CBPRuntime::getRuntime()
			->searchActivitiesByType('rpa_activity', $this->arResult['DOCUMENT_TYPE']);

		if ($this->arParams['SET_TITLE'] === 'Y')
		{
			$this->getApplication()->SetTitle(GetMessage("RPA_AUTOMATION_ADDROBOT_TITLE"));
		}

		$this->includeComponentTemplate();
	}

	protected function checkPermissions(array $documentType)
	{
		$tplUser = new \CBPWorkflowTemplateUser(CBPWorkflowTemplateUser::CurrentUser);

		return (
			$tplUser->isAdmin()
			||
			CBPDocument::CanUserOperateDocumentType(
				\CBPCanUserOperateOperation::CreateAutomation,
				$tplUser->getId(),
				$documentType
			)
		);
	}

	private function showError($message)
	{
		echo <<<HTML
			<div class="ui-alert ui-alert-danger ui-alert-icon-danger">
				<span class="ui-alert-message">{$message}</span>
			</div>
HTML;

		return;
	}
}
