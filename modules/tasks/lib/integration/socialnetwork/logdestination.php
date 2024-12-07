<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Integration\SocialNetwork;


use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Util\User;

class LogDestination
{

	private $context;
	private $parameters;

	private $destinationParams;

	private $destination = [];
	private $dataAdditional = [];

	public function __construct(string $context, array $parameters = [])
	{
		$this->context = $context;
		$this->parameters = $parameters;
	}

	public function getData()
	{
		$this->checkUser();

		$this
			->initDestination()
			->fillStructure()
			->fillLastDestination()
			->fillUsers()
			->fillProjects()
			->fillExtranet()
			->fillNetwork()
			->fillVacation()
			->canAddMailUsers();

		return $this->destination;
	}

	private function canAddMailUsers(): self
	{
		$taskMailUserIntegrationEnabled = Bitrix24::checkFeatureEnabled(
			Bitrix24\FeatureDictionary::TASK_MAIL_USER_INTEGRATION
		);

		$this->destination['CAN_ADD_MAIL_USERS'] = (
			$taskMailUserIntegrationEnabled
			&& ModuleManager::isModuleInstalled('mail')
			&& ModuleManager::isModuleInstalled('intranet')
			&& (
				!Loader::includeModule('bitrix24')
				|| \CBitrix24::isEmailConfirmed()
			)
		);

		return $this;
	}
	private function fillVacation(): self
	{
		$this->destination['SHOW_VACATIONS'] = ModuleManager::isModuleInstalled('intranet');
		if ($this->destination['SHOW_VACATIONS'])
		{
			$this->destination['USERS_VACATION'] = \Bitrix\Socialnetwork\Integration\Intranet\Absence\User::getDayVacationList();
		}

		return $this;
	}
	private function fillNetwork(): self
	{
		$this->destination['NETWORK_ENABLED'] = Option::get('tasks', 'network_enabled') == 'Y';

		return $this;
	}
	private function fillExtranet(): self
	{
		// add virtual department: extranet
		if (!\Bitrix\Tasks\Integration\Extranet::isConfigured()) {
			return $this;
		}

		$this->destination['DEPARTMENT']['EX'] = array(
			'id' => 'EX',
			'entityId' => 'EX',
			'name' => Loc::getMessage("TASKS_INTEGRATION_EXTRANET_ROOT"),
			'parent' => 'DR0',
		);
		$this->destination['DEPARTMENT_RELATION']['EX'] = array(
			'id' => 'EX',
			'type' => 'category',
			'items' => array(),
		);

		return $this;
	}
	private function fillProjects(): self
	{
		$userId = User::getId();

		$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
		$cacheId = "dest_project_".$userId.md5(serialize($this->parameters)).SITE_ID;
		$cacheDir = "/tasks/dest/".$userId;
		$cache = new \CPHPCache;

		if($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			$this->fillProjectsFromCache($cache);
			return $this;
		}

		$cache->startDataCache();

		$limitReached = false;
		$this->destination["SONETGROUPS"] = \CSocNetLogDestination::getSocnetGroup(array_merge($this->destinationParams, array(
			"ALL" => "Y",
			"GROUP_CLOSED" => "N",
			"features" => array(
				"tasks", array("create_tasks")
			)
		)), $limitReached);

		if (isset($this->destination['SONETGROUPS']['PROJECTS']))
		{
			$this->destination['PROJECTS'] = $this->destination['SONETGROUPS']['PROJECTS'];
		}
		if (isset($this->destination['SONETGROUPS']['SONETGROUPS']))
		{
			$this->destination['SONETGROUPS'] = $this->destination['SONETGROUPS']['SONETGROUPS'];
		}

		$this->destination["SONETGROUPS_LIMITED"] = ($limitReached ? 'Y' : 'N');

		if(defined("BX_COMP_MANAGED_CACHE"))
		{
			global $CACHE_MANAGER;
			$CACHE_MANAGER->startTagCache($cacheDir);
			$CACHE_MANAGER->registerTag("sonet_group");
			foreach($this->destination["SONETGROUPS"] as $val)
			{
				$CACHE_MANAGER->registerTag("sonet_features_G_".$val["entityId"]);
				$CACHE_MANAGER->registerTag("sonet_group_".$val["entityId"]);
			}
			if (!empty($this->destination['PROJECTS']))
			{
				foreach($this->destination["PROJECTS"] as $val)
				{
					$CACHE_MANAGER->registerTag("sonet_features_G_".$val["entityId"]);
					$CACHE_MANAGER->registerTag("sonet_group_".$val["entityId"]);
				}
			}
			$CACHE_MANAGER->registerTag("sonet_user2group_U".$userId);
			$CACHE_MANAGER->endTagCache();
		}

		$cache->endDataCache(array(
			"SONETGROUPS" => $this->destination["SONETGROUPS"],
			"PROJECTS" => ($this->destination["PROJECTS"] ?? null),
			"SONETGROUPS_LIMITED" => $this->destination["SONETGROUPS_LIMITED"]
		));

		return $this;
	}
	private function fillProjectsFromCache(\CPHPCache $cache)
	{
		$cacheVars = $cache->getVars();
		$this->destination["SONETGROUPS"] = $cacheVars["SONETGROUPS"];
		$this->destination["PROJECTS"] = (isset($cacheVars["PROJECTS"]) ? $cacheVars["PROJECTS"] : array());
		$this->destination["SONETGROUPS_LIMITED"] = $cacheVars["SONETGROUPS_LIMITED"];
	}
	private function fillUsers(): self
	{
		$destinationParams = $this->getDestinationParams();

		if (\Bitrix\Tasks\Integration\Extranet\User::isExtranet())
		{
			$this->destination["EXTRANET_USER"] = "Y";
			$this->destination["USERS"] = \CSocNetLogDestination::getExtranetUser($destinationParams);
			return $this;
		}

		$this->destination["EXTRANET_USER"] = "N";

		$destUser = [];
		foreach ($this->destination["LAST"]["USERS"] as $value)
		{
			$destUser[] = str_replace("U", "", $value);
		}

		$destinationParams = array_merge($destinationParams, ["id" => $destUser]);

		$this->destination["USERS"] = \CSocNetLogDestination::getUsers($destinationParams);

		\CSocNetLogDestination::fillEmails($this->destination);

		return $this;
	}
	private function fillLastDestination(): self
	{
		\CSocNetLogDestination::fillLastDestination(
			$this->destination["DEST_SORT"],
			$this->destination["LAST"],
			array(
				"EMAILS" => ModuleManager::isModuleInstalled("mail"),
				"PROJECTS" => (isset($this->parameters['USE_PROJECTS']) && $this->parameters['USE_PROJECTS'] == 'Y' ? 'Y' : 'N'),
				"DATA_ADDITIONAL" => $this->dataAdditional
			)
		);

		return $this;
	}
	private function fillStructure(): self
	{
		$structure = \CSocNetLogDestination::GetStucture([
			'HEAD_DEPT' => 1
		]);

		$this->destination['DEPARTMENT'] = $structure["department"];
		$this->destination['DEPARTMENT_RELATION'] = $structure["department_relation"];
		$this->destination['DEPARTMENT_RELATION_HEAD'] = $structure["department_relation_head"];

		return $this;
	}
	private function initDestination(): self
	{
		$this->destination = [
			"DEST_SORT" => \CSocNetLogDestination::GetDestinationSort(
				[
					"DEST_CONTEXT" => $this->context,
					"ALLOW_EMAIL_INVITATION" => ModuleManager::isModuleInstalled("mail"),
				],
				$this->dataAdditional
			),
			"LAST" => [
				"USERS" 		=> [],
				"SONETGROUPS" 	=> [],
				"PROJECTS" 		=> [],
				"DEPARTMENT" 	=> []
			],
			/*
			"SELECTED" => array(
				"USERS" => array(User::getId())
			)
			*/
		];

		return $this;
	}

	private function getDestinationParams(): array
	{
		if (!$this->destinationParams)
		{
			$this->destinationParams = array(
				'useProjects' => (isset($this->parameters['USE_PROJECTS']) && $this->parameters['USE_PROJECTS'] == 'Y'? 'Y' : 'N'),
				'CRM_ENTITY' => 'Y'
			);
			if(
				isset($this->parameters['AVATAR_HEIGHT'])
				&& isset($this->parameters['AVATAR_WIDTH'])
				&& intval($this->parameters['AVATAR_HEIGHT'])
				&& intval($this->parameters['AVATAR_WIDTH'])
			)
			{
				$this->destinationParams['THUMBNAIL_SIZE_WIDTH'] = intval($this->parameters['AVATAR_WIDTH']);
				$this->destinationParams['THUMBNAIL_SIZE_HEIGHT'] = intval($this->parameters['AVATAR_HEIGHT']);
			}
		}
		return $this->destinationParams;
	}
	private function checkUser()
	{
		if(!is_object(User::get()))
		{
			throw new \Bitrix\Main\SystemException('Global user is not defined');
		}
	}
}