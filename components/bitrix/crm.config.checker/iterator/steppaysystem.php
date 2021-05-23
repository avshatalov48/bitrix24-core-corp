<?php
namespace Bitrix\Crm\ConfigChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StepPaySystem extends Step
{
	protected static $url = "/saleshub/"; // "/shop/settings/sale_pay_system/";

	protected function checkActuality()
	{
		return isModuleInstalled("sale");
	}

	protected function checkCorrectness()
	{
		if (!\Bitrix\Main\Loader::includeModule("sale"))
		{
			$this->errorCollection->setError(new Error(Loc::getMessage("SALE_IS_NOT_INSTALLED"), "SALE_IS_NOT_INSTALLED"), "SALE_IS_NOT_INSTALLED");
		}
		else if (!(\Bitrix\Sale\PaySystem\Manager::getList(["filter" => ["=ACTIVE" => "Y"]])->fetch()))
		{
			$this->errorCollection->setError(new Error(
				Loc::getMessage("STEP_PAYSYSTEM_ERROR1"),
				"PAY_SYSTEM_IS_NOT_CONFIGURED"
			), "PAY_SYSTEM_IS_NOT_CONFIGURED");
		}
		return $this->errorCollection->isEmpty();
	}

	public function getTitle()
	{
		return Loc::getMessage("STEP_PAYSYSTEM_TITLE");
	}

	public function getDescription()
	{
		return Loc::getMessage("STEP_PAYSYSTEM_DESCRIPTION");
	}
}

