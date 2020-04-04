<?php
namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\MobileApp\Janative\Utils;


class Task implements Tabable
{
	/**
	 * @var \Bitrix\Mobile\Context $context
	 */
	private $context;

	public function isAvailable()
	{
		if (\CModule::IncludeModule("socialnetwork"))
		{
			$arUserActiveFeatures = \CSocNetFeatures::getActiveFeatures(SONET_ENTITY_USER, $this->context->userId);
			$arSocNetFeaturesSettings = \CSocNetAllowed::getAllowedFeatures();

			return
				array_key_exists("tasks", $arSocNetFeaturesSettings) &&
				array_key_exists("allowed", $arSocNetFeaturesSettings["tasks"]) &&
				in_array(SONET_ENTITY_USER, $arSocNetFeaturesSettings["tasks"]["allowed"]) &&
				is_array($arUserActiveFeatures) &&
				in_array("tasks", $arUserActiveFeatures);
		}

		return false;
	}

	private function getDataInternal()
	{
		if ((\Bitrix\MobileApp\Mobile::getPlatform() == "ios" && \Bitrix\MobileApp\Mobile::getSystemVersion() < 11) || \Bitrix\MobileApp\Mobile::getApiVersion() < 28)
		{
			return [
				"sort" => 400,
				"imageName" => "task",
				"badgeCode" => "tasks",
				"page" => ["url" => $this->context->siteDir . "mobile/tasks/snmrouter/"],
			];
		}
		else
		{
			$defaultViewType = Option::get('tasks', 'view_type', 'view_all');

			return [
				"sort" => 400,
				"imageName" => "task",
				"badgeCode" => "tasks",
				"component" => [
					"name" => "JSStackComponent",
					"title" => GetMessage("MD_COMPONENT_TASKS_LIST"),
					"componentCode" => "tasks.list",
					"scriptPath" => \Bitrix\MobileApp\Janative\Manager::getComponentPath("tasks.list"),
					"rootWidget" => [
						'name' => 'tasks.list',
						'settings' => [
							'useSearch' => true,
							'objectName' => 'list',
							'menuSections' => [
								['id' => "presets"],
								['id' => "counters", 'itemTextColor' => "#f00"]
							],
							'menuItems' => [
								[
									'id' => "view_all",
									'title' => Loc::getMessage('TASKS_ROLE_VIEW_ALL'),
									'sectionCode' => 'presets',
									'showAsTitle' => true, 'badgeCount' => 0
								],
								['id' => "view_role_responsible", 'title' => Loc::getMessage('TASKS_ROLE_RESPONSIBLE'), 'sectionCode' => 'presets', 'showAsTitle' => true, 'badgeCount' => 0],
								['id' => "view_role_accomplice", 'title' => Loc::getMessage('TASKS_ROLE_ACCOMPLICE'), 'sectionCode' => 'presets', 'showAsTitle' => true, 'badgeCount' => 0],
								['id' => "view_role_auditor", 'title' => Loc::getMessage('TASKS_ROLE_AUDITOR'), 'sectionCode' => 'presets', 'showAsTitle' => true, 'badgeCount' => 0],
								['id' => "view_role_originator", 'title' => Loc::getMessage('TASKS_ROLE_ORIGINATOR'), 'sectionCode' => 'presets', 'showAsTitle' => true, 'badgeCount' => 0]
							],
							'filter' => $defaultViewType
						]
					],


					"params" => [
						"COMPONENT_CODE" => "tasks.list",
						"USER_ID" => $this->context->userId,
						"SITE_ID" => $this->context->siteId,
						"LANGUAGE_ID" => LANGUAGE_ID,
						"SITE_DIR" => $this->context->siteDir,
						"PATH_TO_TASK_ADD" => $this->context->siteDir . "mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#",
						"MESSAGES" => [

						]
					]
				]
			];
		}
	}

	public function getData()
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		return $this->getDataInternal();
	}

	/**
	 * @return boolean
	 */
	public function shouldShowInMenu()
	{
		return true;
	}

	/**
	 * @return null|array
	 */
	public function getMenuData()
	{
		$data = $this->getDataInternal();
		$result = [
			"title"=>$this->getTitle(),
			"useLetterImage" => true,
			"color" => "#fabb3f",
			"imageUrl" => "favorite/icon-tasks.png",
		];;

		if($data["component"])
		{
			$result["params"]= [
				"onclick"=>\Bitrix\Mobile\Tab\Utils::getComponentJSCode($data["component"]),
				"counter"=>"tasks_total",
			];
		}
		else if($data["page"])
		{
			$result["params"]= $data["page"];
			$result["params"]["counter"] = "tasks_total";
		}

		return $result;
	}

	public function canBeRemoved()
	{
		return true;
	}

	/**
	 * @return integer
	 */
	public function defaultSortValue()
	{
		return 400;
	}

	public function canChangeSort()
	{
		return true;
	}

	public function getTitle()
	{
		return Loc::getMessage("TAB_NAME_TASKS_LIST");
	}

	public function setContext($context)
	{
		$this->context = $context;
	}
}
