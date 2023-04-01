<?php
namespace Bitrix\Crm\ConfigChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class StepImconnector extends Step
{
	protected static $url = "/contact_center/";

	protected function checkActuality() : bool
	{
		return isModuleInstalled("imopenlines") && isModuleInstalled("imconnector");
	}

	protected function checkCorrectness() : bool
	{
		if (!\Bitrix\Main\Loader::includeModule("imopenlines"))
		{
			$this->errorCollection->setError(new Error(Loc::getMessage("IMOPENLINES_IS_NOT_INSTALLED"), "IMOPENLINES_IS_NOT_INSTALLED"), "IMOPENLINES_IS_NOT_INSTALLED");
		}
		if (!\Bitrix\Main\Loader::includeModule("imconnector"))
		{
			$this->errorCollection->setError(new Error(Loc::getMessage("IMCONNECTOR_IS_NOT_INSTALLED_MSGVER_1"), "IMCONNECTOR_IS_NOT_INSTALLED"), "IMCONNECTOR_IS_NOT_INSTALLED");
		}
		if ($this->errorCollection->isEmpty())
		{
			$connectors = \Bitrix\ImConnector\Connector::getListConnectorMenu(true);
			$statusList = \Bitrix\ImConnector\Status::getInstanceAll();
			$linkTemplate = \Bitrix\ImOpenLines\Common::getContactCenterPublicFolder() . 'connector/';
			$codeMap = \Bitrix\ImConnector\Connector::getIconClassMap();
			$items = [];
			foreach ($connectors as $code => $connector)
			{
				$active = false;
				if (empty($statusList[$code]))
				{
					continue;
				}
				foreach ($statusList[$code] as $lineId => $status)
				{
					if (($status instanceof \Bitrix\ImConnector\Status) && $status->getActive() === true)
					{
						$correct = ($status->isStatus());
						$connector["link"] = \CUtil::JSEscape( $linkTemplate . "?ID=" . $code . "&LINE=" . $lineId);
						if (!$correct)
						{
							$items[$code] = [
								"name" => $connector["name"],
								"link" => !empty($connector["link"]) ? $connector["link"] : \CUtil::JSEscape( $linkTemplate . "?ID={$code}&LINE=$lineId"),
								"active" => $active,
								"correct" => $correct,
								"icon_class" => "ui-icon ui-icon-service-" . $codeMap[$code]
							];
						}
					}
				}
			}
			if (!empty($items))
			{
				$this->errorCollection->setError(new Error(
					Loc::getMessage("STEP_IMCONNECTOR_ERROR1"),
					"IMCONNECTOR_IS_NOT_CORRECT",
					array_values($items)),
					"IMCONNECTOR_IS_NOT_CORRECT"
				);
			}
		}
		return $this->errorCollection->isEmpty();
	}

	public function getTitle()
	{
		return Loc::getMessage("STEP_IMCONNECTOR_TITLE");
	}

	public function getDescription()
	{
		return Loc::getMessage("STEP_IMCONNECTOR_DESCRIPTION");
	}
}

