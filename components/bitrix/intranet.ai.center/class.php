<?

use Bitrix\Intranet\AI;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

class IntranetAiCenter extends \CBitrixComponent
{
	public function executeComponent()
	{
		if (!Loader::includeModule("intranet"))
		{
			ShowError("The Intranet module is not installed.");
			return;
		}

		$this->initParams();
		$this->prepareResult();
		$this->includeComponentTemplate();
	}

	protected function initParams()
	{
		$this->arParams["SET_TITLE"] =
			isset($this->arParams["SET_TITLE"])
				? $this->arParams["SET_TITLE"] === "Y"
				: true
		;
	}

	protected function prepareResult()
	{
		if ($this->arParams["SET_TITLE"])
		{
			$GLOBALS["APPLICATION"]->setTitle(Loc::getMessage("INTRANET_AI_CENTER_PAGE_TITLE"));
		}

		$this->arResult["ITEMS"] = $this->getItems();

		$app = AI\Center::getAssistantApp();
		$this->arResult["ASSISTANT_APP_ID"] = is_array($app) && $app["ACTIVE"] === "Y" ? intval($app["ID"]) : 0;
		$this->arResult["ML_INSTALLED"] = \Bitrix\Main\ModuleManager::isModuleInstalled("ml");

		return true;
	}

	protected function getItems()
	{
		return array_merge(
			AI\Center::getAssistants(),
			AI\Center::getCrmScoring(),
			AI\Center::getSegmentScoring(),
			AI\Center::getFaceCard(),
			AI\Center::getFaceTracker(),
		);
	}
}