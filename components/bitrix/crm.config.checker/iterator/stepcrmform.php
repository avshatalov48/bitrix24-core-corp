<?php
namespace Bitrix\Crm\ConfigChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Crm\Activity\Provider\Call;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StepCrmForm extends Step
{
	private static $forms = null;

	private function getForms()
	{
		if (static::$forms === null)
		{
			$forms = [];
			$dbRes = \Bitrix\Crm\WebForm\Internals\FormTable::getDefaultTypeList([
				"select" => ["ID", "NAME", "CALL_FROM"],
				"filter" => ["IS_CALLBACK_FORM" => "Y", "ACTIVE" => "Y"]
			]);
			while ($res = $dbRes->fetch())
			{
				$forms[$res["ID"]] = $res;
			}
		}
		return (empty($forms) ? null : $forms);
	}

	private function updateForm(int $id, $phoneNumberId)
	{
		static::$forms = null;
		\Bitrix\Crm\WebForm\Internals\FormTable::update($id, ["CALL_FROM" => $phoneNumberId]);
	}

	protected function checkActuality()
	{
		return !empty($this->getForms());
	}

	protected function checkCorrectness()
	{
		if (!isModuleInstalled("voximplant") || !\Bitrix\Main\Loader::includeModule("voximplant"))
		{
			$this->errorCollection->setError(new Error(Loc::getMessage("VOXIMPLANT_IS_NOT_INSTALLED"), "VOXIMPLANT_IS_NOT_INSTALLED"), "VOXIMPLANT_IS_NOT_INSTALLED");
		}
		else if (!($numbers = \CVoxImplantConfig::GetLinesEx([
			"showRestApps" => true,
			"showInboundOnly" => false
		])) || empty($numbers))
		{
			$this->errorCollection->setError(new Error(Loc::getMessage("STEP_CRMFORM_ERROR1"), "VOXIMPLANT_IS_NOT_CONFIGURED"), "VOXIMPLANT_IS_NOT_CONFIGURED");
		}
		else if ($forms = $this->getForms())
		{
			foreach ($forms as $form)
			{
				if (!array_key_exists($form["CALL_FROM"], $numbers))
				{
					if (count($numbers) <= 1)
					{
						$number = reset($numbers);
						$this->updateForm($form["ID"], $number["LINE_NUMBER"]);
					}
					else if ($this->errorCollection->isEmpty())
					{
						$this->errorCollection->setError(
							new Error(
								Loc::getMessage("STEP_CRMFORM_ERROR2"),
								"VOXIMPLANT_IS_NOT_ACTUAL_IN_FORMS"
							),
							"VOXIMPLANT_IS_NOT_ACTUAL_IN_FORMS"
						);
					}
				}
				else
				{
					$numbers[$form["CALL_FROM"]]["IS_IN_USE"] = true;
				}
			}
			$this->noteCollection["items"] = array_values($numbers);
		}
		return $this->errorCollection->isEmpty();
	}

	public function actionSetNumber($data)
	{
		$numberId = $data["numberId"];
		if (\Bitrix\Main\Loader::includeModule("voximplant"))
		{
			$numbers = \CVoxImplantConfig::GetLinesEx([
				"showRestApps" => true,
				"showInboundOnly" => false
			]);
			if (array_key_exists($numberId, $numbers))
			{
				$forms = $this->getForms();
				foreach ($forms as $form)
				{
					$this->updateForm($form["ID"], $numbers[$numberId]["LINE_NUMBER"]);
				}
			}
		}
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage("STEP_CRMFORM_TITLE");
	}

	public function getDescription()
	{
		return Loc::getMessage("STEP_CRMFORM_DESCRIPTION");
	}
}

