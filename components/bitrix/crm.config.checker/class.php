<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;

class CrmConfigurationCheckerComponent extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	private $moduleId = "crm";
	/** \Bitrix\Main\ErrorCollection */
	protected $errorCollection;
	/** \Bitrix\Crm\ConfigChecker\Iterator */
	protected $configurator;

	/**
	 * CrmVolumeComponent constructor.
	 * @param \CBitrixComponent|null $component
	 */
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new \Bitrix\Main\ErrorCollection();
		if (!\Bitrix\Main\Loader::includeModule("crm"))
		{
			$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage("CRM_MODULE_IS_NOT_INSTALLED"), "ACCESS_DENIED"), "ACCESS_DENIED");
		}
		else
		{
			$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
			if (!$CrmPerms->HavePerm("CONFIG", BX_CRM_PERM_CONFIG, "WRITE"))
			{
				$this->errorCollection->setError(new \Bitrix\Main\Error(Loc::getMessage("CRM_DONT_HAVE_CONFIG_PERMS"), "ACCESS_DENIED"), "ACCESS_DENIED");
			}
		}
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->load();
		$this->configurator = new \Bitrix\Crm\ConfigChecker\IteratorCrm();
	}

	private function load()
	{
		$directory = new \Bitrix\Main\IO\Directory(__DIR__."/iterator");
		$parent = new \Bitrix\Main\IO\Directory(__DIR__);

		$autoloadClasses = [];
		/**@var $file \Bitrix\Main\IO\File */
		foreach ($directory->getChildren() as $file)
		{
			$shortName = $file->getName();
			if ($p = \Bitrix\Main\Text\UtfSafeString::getLastPosition($shortName, "."))
				$shortName = mb_substr($shortName, 0, $p);
			/* namespace \Bitrix\Crm\ConfigChecker */
			$autoloadClasses["bitrix\\crm\\configchecker\\".$shortName] = Path::combine("install/components/bitrix/", $parent->getName(), $directory->getName(), $file->getName());
		}
		\Bitrix\Main\Loader::registerAutoLoadClasses(
			$this->moduleId,
			$autoloadClasses
		);
	}

	public function executeComponent()
	{
		if (!$this->errorCollection->isEmpty())
		{
			$this->arResult["ERRORS"] = $this->errorCollection->getValues();
			$this->includeComponentTemplate("error");
			return;
		}
		$this->arResult["PATH_TO_CONFIG_CHECKER"] = CComponentEngine::MakePathFromTemplate(\COption::GetOptionString("crm", "path_to_config_checker"));
		if ($this->getTemplateName() === "invisible")
		{
			$this->includeComponentTemplate("");
			return;
		}

		$this->onPrepareComponentParams([]);

		$this->arResult["CODE"] = $this->configurator->getCode();
		$this->arResult["TITLE"] = $this->configurator->getTitle();
		$this->arResult["DESCRIPTION"] = $this->configurator->getDescription();
		$this->arResult["ICON"] = $this->configurator->getIcon();
		$this->arResult["COLOR"] = $this->configurator->getColor();

		$this->arResult["IS_STARTED"] = $this->configurator->isStarted();
		$this->arResult["IS_FINISHED"] = $this->configurator->isFinished();
		$this->arResult["IS_DEFAULT"] = $this->configurator->isDefault();

		$steps = [];
		/**
		 * @var $step \Bitrix\Crm\ConfigChecker\Step
		 */
		foreach ($this->configurator->getSteps() as $step)
		{
			$steps[] = [
				"ID" => $step->getId(),
				"TITLE" => $step->getTitle(),
				"URL" => $step->getUrl(),
				"DESCRIPTION" => $step->getDescription(),
				"IS_STARTED" => $step->isStarted(),
				"IS_FINISHED" => $step->isFinished(),
				"IS_ACTUAL" => $step->isActual(),
				"IS_CORRECT" => $step->isCorrect(),
				"ERRORS" => array_map(function($error){return $error->jsonSerialize();}, $step->getErrors()->toArray()),
				"NOTES" => $step->getNotes()->toArray()
			];
		}
		$this->arResult["STEPS"] = $steps;

		global $APPLICATION;
		$APPLICATION->SetTitle($this->configurator->getTitle());
		$this->includeComponentTemplate();
	}

	public function configureActions()
	{
		return [];
	}

	protected function checkStep()
	{
		$steps = [];
		if ($step = $this->configurator->checkStep())
		{
			$steps[$step->getId()] = [
				"actual" => $step->isActual(),
				"correct" => $step->isCorrect(),
				"started" => $step->isStarted(),
				"finished" => $step->isFinished(),
				"errors" => $step->getErrors()->toArray(),
				"notes" => $step->getNotes()->toArray(),
			];
		}

		$result = [
			"finished" => $this->configurator->isFinished(),
			"started" => $this->configurator->isStarted(),
			"stepSteps" => $steps
		];
		return $result;
	}

	public function resetAction()
	{
		$this->configurator->reset();

		return $this->checkStep();
	}

	public function continueAction()
	{
		return $this->checkStep();
	}

	public function executeStepAction()
	{
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();
		$steps = [];
		if ($step = $this->configurator->execStep(
			$request->getPost("stepId"),
			$request->getPost("stepAction"),
			$request->getPost("stepData"))
		)
		{
			$steps[$step->getId()] = [
				"actual" => $step->isActual(),
				"correct" => $step->isCorrect(),
				"started" => $step->isStarted(),
				"finished" => $step->isFinished(),
				"errors" => $step->getErrors()->toArray(),
				"notes" => $step->getNotes()->toArray(),
			];
		}

		$result = [
			"finished" => $this->configurator->isFinished(),
			"started" => $this->configurator->isStarted(),
			"stepSteps" => $steps
		];
		return $result;
	}
	
	public function showSliderAction()
	{
		$result = ["show" => "N", "lastVisit" => null];
		if (\Bitrix\Crm\Integration\Rest\Configuration\ConfigChecker::isNeedToCheck())
		{
			$result = \CUserOptions::GetOption("crm", "config_checker", ["lastTime" => null, "show" => "Y"]);
			$result["lastVisit"] = is_null($result["lastTime"]) ? null : (time() - $result["lastTime"]);
		}
		return $result;
	}

	public function closeSliderAction()
	{
		$config = \CUserOptions::GetOption("crm", "config_checker", ["lastTime" => 0, "show" => "Y"]);
		$config["lastTime"] = time();
		\CUserOptions::SetOption("crm", "config_checker", $config);
	}

	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}

