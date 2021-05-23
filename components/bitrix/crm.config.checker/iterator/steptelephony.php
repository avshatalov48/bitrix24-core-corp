<?php
namespace Bitrix\Crm\ConfigChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StepTelephony extends Step
{
	protected function checkActuality()
	{
		return isModuleInstalled("voximplant");
	}

	protected function checkCorrectness()
	{
		if (!\Bitrix\Main\Loader::includeModule("voximplant"))
		{
			$this->errorCollection->setError(new Error(Loc::getMessage("VOXIMPLANT_IS_NOT_INSTALLED"), "VOXIMPLANT_IS_NOT_INSTALLED"), "VOXIMPLANT_IS_NOT_INSTALLED");
		}
		else if (!($numbers = \CVoxImplantConfig::GetLinesEx([
			"showRestApps" => true,
			"showInboundOnly" => false
		])) || empty($numbers))
		{
			$link = \CVoxImplantMain::GetPublicFolder()."lines.php";
			$this->errorCollection->setError(new Error(
				Loc::getMessage("STEP_TELEPHONY_ERROR1"),
				"VOXIMPLANT_IS_NOT_CONFIGURED",
				["link" => $link]
			), "VOXIMPLANT_IS_NOT_CONFIGURED");
		}
		return $this->errorCollection->isEmpty();
	}

	public function getTitle()
	{
		return Loc::getMessage("STEP_TELEPHONY_TITLE");
	}

	public function getDescription()
	{
		return Loc::getMessage("STEP_TELEPHONY_DESCRIPTION");
	}

	public function getUrl()
	{
		if (\Bitrix\Main\Loader::includeModule("voximplant"))
			return \CVoxImplantMain::GetPublicFolder()."lines.php";
		return parent::getUrl();
	}
}

