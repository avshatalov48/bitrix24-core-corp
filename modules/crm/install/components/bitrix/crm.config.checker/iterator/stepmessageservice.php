<?php
namespace Bitrix\Crm\ConfigChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StepMessageService extends Step
{
	protected static $url = "/crm/configs/";

	private $lastTemplateId = 0;

	protected function init(array $data)
	{
		$this->lastTemplateId = (int)($data["lastTemplateId"] ?? 0);
	}

	protected function pack()
	{
		return ["lastTemplateId" => $this->lastTemplateId];
	}

	public function reset()
	{
		$this->lastTemplateId = 0;
		parent::reset();
	}

	protected function checkActuality()
	{
		return isModuleInstalled("messageservice");
	}

	private function getServices($count = 10)
	{
		$result = null;
		if (\Bitrix\Main\Loader::includeModule("bizproc"))
		{
			$dbRes = \CBPWorkflowTemplateLoader::getList(
				["ID" => "ASC"],
				["MODULE_ID" => "crm", "ACTIVE" => "Y", ">ID" => $this->lastTemplateId],
				false,
				["nTopCount" => $count]
			);
			if ($res = $dbRes->fetch())
			{
				$result = [];
				do
				{
					$result[] = $res;
					$this->lastTemplateId = $res["ID"];
				} while ($res = $dbRes->fetch());
			}
		}
		return $result;
	}

	protected function checkCorrectness()
	{
		if (!\Bitrix\Main\Loader::includeModule("messageservice"))
		{
			$this->errorCollection->setError(
				new Error(
					Loc::getMessage("STEP_MESSAGESERVICE_IS_NOT_INSTALLED"),
					"MESSAGESERVICE_IS_NOT_INSTALLED"),
				"MESSAGESERVICE_IS_NOT_INSTALLED"
			);
		}
		else if (!isset($this->noteCollection["items"]))
		{
			$items = [];
			foreach (\Bitrix\Crm\Integration\SmsManager::getSenderInfoList() as $item)
			{
				if ($item["canUse"] && $item["id"] != "dummy")
				{
					$items[] = $items;
				}
			}
			if (empty($items))
			{
				$this->errorCollection->setError(
					new Error(
						Loc::getMessage("STEP_MESSAGESERVICES_IS_NOT_CONFIGURED"),
						"MESSAGESERVICE_IS_NOT_CONFIGURED"
					),
					"MESSAGESERVICE_IS_NOT_CONFIGURED"
				);
			}
			else
			{
				$item = reset($items);
				\Bitrix\Crm\Integration\SmsManager::setEditorDefaultsCommon(["senderId" => $item["id"]]);
			}
			$this->noteCollection["items"] = \Bitrix\Crm\Integration\SmsManager::getSenderInfoList();
		}
		$count = 10;
		if ($templates = $this->getServices($count))
		{
			$searcher = function ($activity, &$result) use (&$searcher)
			{
				if ($activity["Type"] === "CrmSendSmsActivity")
				{
					if ($activity["Properties"]["ProviderId"] !== ':default:')
					{
						$result[] = $activity["Properties"]["ProviderId"];
					}
				}
				if (is_array($activity["Children"]))
				{
					foreach ($activity["Children"] as $child)
					{
						$searcher($child, $result);
					}
				}
			};
			$result = [];
			foreach ($templates as $res)
			{
				$searcher($res["TEMPLATE"][0], $result);
			}
			if (!empty($result))
			{
				$result = array_unique($result);
				$providers = \Bitrix\Crm\Integration\SmsManager::getSenderInfoList();
				foreach ($result as $smsProviderId)
				{
					$provider = array_filter($providers, function($item) use ($smsProviderId) {
						return $item["id"] == $smsProviderId;
					});
					$provider = reset($provider);
					if (empty($provider))
					{
						$this->errorCollection->setError(
							new Error(
								Loc::getMessage("STEP_MESSAGESERVICE_ERROR_NONEXISTENT_PROVIDER", ["#provider#" => $smsProviderId]),
								"NONEXISTENT_PROVIDER"),
							$smsProviderId
						);
					}
					else if ($provider["canUse"] !== true)
					{
						$this->errorCollection->setError(
							new Error(
								Loc::getMessage("STEP_MESSAGESERVICE_ERROR_NONWORKING_PROVIDER", ["#provider#" => $provider["name"]]),
								"NONWORKING_PROVIDER",
								$provider),
							$smsProviderId
						);
					}
				}
			}
			if (count($templates) >= $count)
			{
				return null;
			}
		}

		return $this->errorCollection->isEmpty();
	}

	public function getTitle()
	{
		return Loc::getMessage("STEP_MESSAGESERVICES_TITLE");
	}

	public function getDescription()
	{
		return Loc::getMessage("STEP_MESSAGESERVICES_DESCRIPTION");
	}
}
