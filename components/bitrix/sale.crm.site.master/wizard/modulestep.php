<?php
namespace Bitrix\Sale\CrmSiteMaster\Steps;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ModuleStep
 * Show list of required modules
 *
 * @package Bitrix\Sale\CrmSiteMaster\Steps
 */
class ModuleStep extends \CWizardStep
{
	private $currentStepName = __CLASS__;

	/** @var \SaleCrmSiteMaster */
	private $component = null;

	private $modulesChecked = [
		"NOT_INSTALLED_MODULES" => [],
		"MIN_VERSION_MODULES" => [],
	];

	/**
	 * Check step errors
	 */
	private function setStepErrors()
	{
		$errors = $this->component->getWizardStepErrors($this->currentStepName);
		if ($errors)
		{
			foreach ($errors as $error)
			{
				$this->SetError($error);
			}
		}
	}

	/**
	 * Prepare next/prev buttons
	 *
	 * @throws \ReflectionException
	 */
	private function prepareButtons()
	{
		$steps = $this->component->getSteps($this->currentStepName);

		$shortClassName = (new \ReflectionClass($this))->getShortName();

		if (isset($steps["NEXT_STEP"]))
		{
			$this->SetNextStep($steps["NEXT_STEP"]);
			$this->SetNextCaption(Loc::getMessage("SALE_CSM_WIZARD_".mb_strtoupper($shortClassName)."_NEXT"));
		}
		if (isset($steps["PREV_STEP"]))
		{
			$this->SetPrevStep($steps["PREV_STEP"]);
			$this->SetPrevCaption(Loc::getMessage("SALE_CSM_WIZARD_".mb_strtoupper($shortClassName)."_PREV"));
		}
	}

	/**
	 * Initialization step id, title and next/prev step
	 *
	 * @throws \ReflectionException
	 */
	public function initStep()
	{
		$this->component = $this->GetWizard()->GetVar("component");

		$this->SetStepID($this->currentStepName);
		$this->SetTitle(Loc::getMessage("SALE_CSM_WIZARD_MODULESTEP_TITLE"));

		$this->prepareButtons();

		$this->setStepErrors();

		$this->modulesChecked["NOT_INSTALLED_MODULES"] = $this->GetWizard()->GetVar("not_installed_modules");
		$this->modulesChecked["MIN_VERSION_MODULES"] = $this->GetWizard()->GetVar("min_version_modules");
	}

	/**
	 * Show step content
	 *
	 * @return bool
	 */
	public function showStep()
	{
		if ($this->GetErrors())
		{
			return false;
		}

		if ($this->modulesChecked["NOT_INSTALLED_MODULES"])
		{
			$this->SetNextStep("Bitrix\Sale\CrmSiteMaster\Steps\ModuleInstallStep");
			$this->installModulesHtml();
		}
		elseif ($this->modulesChecked["MIN_VERSION_MODULES"])
		{
			$this->SetNextCaption(Loc::getMessage("SALE_CSM_WIZARD_MODULESTEP_NEXT_BUTTON"));
			$this->minModulesHtml();
		}
		else
		{
			$this->SetNextCaption(Loc::getMessage("SALE_CSM_WIZARD_MODULESTEP_NEXT_BUTTON"));
			ob_start();
			?>
			<div class="adm-crm-site-master-paragraph-wrapper">
				<div class="adm-crm-site-master-paragraph"><?=Loc::getMessage("SALE_CSM_WIZARD_MODULESTEP_CHECK_SUCCESS")?></div>
			</div>
			<?
			$content = ob_get_contents();
			ob_end_clean();

			$this->content .= $content;
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function showButtons()
	{
		if ($this->GetErrors())
		{
			return [
				"CONTENT" => ""
			];
		}

		ob_start();
		if ($this->GetPrevStepID() !== null)
		{
			?>
			<input type="hidden" name="<?=$this->GetWizard()->prevStepHiddenID?>" value="<?=$this->GetPrevStepID()?>">
			<button type="submit" class="ui-btn ui-btn-primary" name="<?=$this->GetWizard()->prevButtonID?>">
				<?=$this->GetPrevCaption()?>
			</button>
			<?
		}
		if ($this->GetNextStepID() !== null)
		{
			?>
			<input type="hidden" name="<?=$this->GetWizard()->nextStepHiddenID?>" value="<?=$this->GetNextStepID()?>">
			<button type="submit" class="ui-btn ui-btn-primary" name="<?=$this->GetWizard()->nextButtonID?>">
				<?=$this->GetNextCaption()?>
			</button>
			<?
		}
		$content = ob_get_contents();
		ob_end_clean();

		return [
			"CONTENT" => $content,
			"NEED_WRAPPER" => true,
			"CENTER" => true,
		];
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\ArgumentNullException
	 */
	public function onPostForm()
	{
		$wizard =& $this->GetWizard();
		if ($wizard->IsPrevButtonClick())
		{
			return false;
		}

		if ($this->modulesChecked["NOT_INSTALLED_MODULES"])
		{
			$wizard->SetVar("modules", $this->modulesChecked["NOT_INSTALLED_MODULES"]);
			$wizard->SetVar("modulesCount", count($this->modulesChecked["NOT_INSTALLED_MODULES"]));

			return false;
		}

		if (!$this->modulesChecked["NOT_INSTALLED_MODULES"] && !$this->modulesChecked["MIN_VERSION_MODULES"])
		{
			$this->GetWizard()->SetCurrentStep("Bitrix\Sale\CrmSiteMaster\Steps\SiteInstructionStep");
			$this->component->getModuleChecker()->deleteInstallStatus();
		}

		return true;
	}

	/**
	 * Prepare html content with modules to be installed
	 */
	private function installModulesHtml()
	{
		$rows = [];
		foreach ($this->modulesChecked["NOT_INSTALLED_MODULES"] as $module)
		{
			$rows[]["data"] = [
				"MODULE" => $module["name"],
			];
		};

		$columns = [
			[
				'id' => 'MODULE',
				'name' => Loc::getMessage("SALE_CSM_WIZARD_MODULESTEP_INSTALL_COLUMN"),
				'sort' => 'MODULE',
				'default' => true,
				'resizeable' => false,
			],
		];

		$this->content .= $this->includeGridComponent("module_install_list", $columns, $rows);
	}

	/**
	 * Prepare html content with modules to be updated
	 */
	private function minModulesHtml()
	{
		$rows = [];
		foreach ($this->modulesChecked["MIN_VERSION_MODULES"] as $module)
		{
			$rows[]["data"] = [
				"MODULE" => $module["NAME"],
				"CURRENT_VERSION" => $module["CURRENT_VERSION"],
				"REQUIRED_VERSION" => $module["REQUIRED_VERSION"],
			];
		};

		$columns = [
			[
				'id' => 'MODULE',
				'name' => Loc::getMessage("SALE_CSM_WIZARD_MODULESTEP_UPDATE_COLUMN"),
				'sort' => 'MODULE',
				'default' => true,
				'resizeable' => false,
			],
			[
				'id' => 'CURRENT_VERSION',
				'name' => Loc::getMessage("SALE_CSM_WIZARD_MODULESTEP_UPDATE_CURRENT_VERSION"),
				'sort' => 'CURRENT_VERSION',
				'default' => true,
				'resizeable' => false,
			],
			[
				'id' => 'REQUIRED_VERSION',
				'name' => Loc::getMessage("SALE_CSM_WIZARD_MODULESTEP_UPDATE_REQUIRED_VERSION"),
				'sort' => 'REQUIRED_VERSION',
				'default' => true,
				'resizeable' => false,
			],
		];

		$this->content .= $this->includeGridComponent("module_update_list", $columns, $rows);

		ob_start();
		?>
		<div class="adm-crm-site-master-paragraph-wrapper">
			<div class="adm-crm-site-master-paragraph"><?=Loc::getMessage("SALE_CSM_WIZARD_MODULESTEP_NOTES1", [
					"#UPDATE_SYSTEM_LINK#" => "/bitrix/admin/update_system.php?lang=".LANGUAGE_ID
				])?>
			</div>
			<div class="adm-crm-site-master-paragraph"><?=Loc::getMessage("SALE_CSM_WIZARD_MODULESTEP_NOTES2")?></div>
		</div>
		<?
		$content = ob_get_contents();
		ob_end_clean();

		$this->content .= $content;
	}

	/**
	 * @param $id
	 * @param $columns
	 * @param $rows
	 * @return false|string
	 */
	private function includeGridComponent($id, $columns, $rows)
	{
		/** @noinspection PhpVariableNamingConventionInspection */
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
			'GRID_ID' => $id,
			'COLUMNS' => $columns,
			'ROWS' => $rows,
			'SHOW_ROW_CHECKBOXES' => false,
			'AJAX_MODE' => 'N',
			'AJAX_OPTION_JUMP'          => 'N',
			'SHOW_CHECK_ALL_CHECKBOXES' => false,
			'SHOW_ROW_ACTIONS_MENU'     => false,
			'SHOW_GRID_SETTINGS_MENU'   => false,
			'SHOW_NAVIGATION_PANEL'     => false,
			'SHOW_PAGINATION'           => false,
			'SHOW_SELECTED_COUNTER'     => false,
			'SHOW_TOTAL_COUNTER'        => false,
			'SHOW_PAGESIZE'             => false,
			'SHOW_ACTION_PANEL'         => false,
			'ACTION_PANEL'              => [],
			'ALLOW_COLUMNS_SORT'        => false,
			'ALLOW_COLUMNS_RESIZE'      => false,
			'ALLOW_HORIZONTAL_SCROLL'   => false,
			'ALLOW_SORT'                => false,
			'ALLOW_PIN_HEADER'          => false,
			'AJAX_OPTION_HISTORY'       => 'N'
		]);
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
}