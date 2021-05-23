<?php
namespace Bitrix\Crm\ConfigChecker;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use \Bitrix\Main\Localization\Loc;

class IteratorCrm extends Iterator
{
	protected static $moduleId = "crm";
	protected static $steps = [
		StepTelephony::class,
		StepCrmForm::class,
		StepImconnector::class,
		StepMessageService::class,
		StepPaySystem::class
	];

	public function __construct()
	{
		parent::__construct();
		$this->title = Loc::getMessage("CRM_CONFIG_CHECKER_TITLE");

		$config = \Bitrix\Main\Config\Option::get("crm", "crm_was_imported");
		if (!empty($config) && ($config = unserialize($config, ['allowed_classes' => false])) && is_array($config))
		{
			$this->code = $config["CODE"];
			$this->title = $config["TITLE"];
			$this->description = $config["DESCRIPTION"];
			$this->icon = $config["ICON"];
			$this->color = $config["COLOR"];
			if ($this->id != $config["ID"])
			{
				$this->id = $config["ID"];
				$this->reset();
			}
		}
	}

	public function finish()
	{
		parent::finish();
		\CUserOptions::DeleteOption("crm", "config_checker");
		if (!$this->isDefault())
		{
			\Bitrix\Main\Config\Option::set("crm", "crm_was_imported", serialize([
				"ID" => $this->id,
				"CODE" => $this->getCode(),
				"TITLE" => $this->getTitle(),
				"DESCRIPTION" => $this->getDescription(),
				"ICON" => $this->getIcon(),
				"COLOR" => $this->getColor(),
				"CHECKED" => true
			]));
		}
	}
}

