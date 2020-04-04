<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

class CVoximplantClosingDocumentsRequestComponent extends \CBitrixComponent
{
	public function executeComponent()
	{
		global $USER;
		$this->arResult["PERIODS"] = $this->getValidPeriods();
		$this->arResult["INDEX"] = CUserOptions::GetOption("voximplant.invoice.request", "default_index", "");
		$this->arResult["ADDRESS"] = CUserOptions::GetOption("voximplant.invoice.request", "default_address", "");
		$this->arResult["CURRENT_USER_EMAIL"] = $USER->GetEmail();
		$this->includeComponentTemplate();
	}

	public function getValidPeriods()
	{
		$result = [];

		$earliestPossibleDate = (new \Bitrix\Main\Type\Date("2019-07-01", "Y-m-d"))->getTimestamp();

		for($i = 1; $i <= 6; $i++)
		{
			$date = (new \Bitrix\Main\Type\DateTime())->add("-" . $i . " months");
			if($date->getTimestamp() < $earliestPossibleDate)
			{
				break;
			}

			$result[] = $date;
		}
		return $result;
	}
}