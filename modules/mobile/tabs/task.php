<?php
namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Query\Filter;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\MobileApp\Mobile;

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
		$apiVersion = Mobile::getApiVersion();

		if ($apiVersion < 28 || (Mobile::getPlatform() == "ios" && Mobile::getSystemVersion() < 11))
		{
			return [
				"sort" => 400,
				"imageName" => "task",
				"badgeCode" => "tasks",
				"page" => ["url" => "{$this->context->siteDir}mobile/tasks/snmrouter/"],
			];
		}

		$data = [
			"sort" => 400,
			"imageName" => "task",
			"badgeCode" => "tasks",
			"id" => "tasks",
			"component" => [
				"name" => "JSStackComponent",
				"title" => GetMessage("MD_COMPONENT_TASKS_LIST"),
				"componentCode" => "tasks.list",
				"scriptPath" => Manager::getComponentPath("tasks.list"),
				"rootWidget" => [
					"name" => "tasks.list",
					"settings" => [
						"useSearch" => true,
						"useLargeTitleMode" => true,
						"objectName" => "list",
					],
				],
				"params" => [
					"COMPONENT_CODE" => "tasks.list",
					"USER_ID" => $this->context->userId,
					"SITE_ID" => $this->context->siteId,
					"SITE_DIR" => $this->context->siteDir,
					"LANGUAGE_ID" => LANGUAGE_ID,
					"PATH_TO_TASK_ADD" => "{$this->context->siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#",
					"MIN_SEARCH_SIZE" => Filter\Helper::getMinTokenSize(),
					"MESSAGES" => [],
				],
			],
		];

		if ($apiVersion >= 40)
		{
			$data["component"]["rootWidget"]["settings"]["inputPanel"] = [
				"action" => 0,
				"callback" => 0,
				"useImageButton" => true,
				"useAudioMessages" => true,
				"smileButton" => [],
				"message" => [
					"placeholder" => Loc::getMessage("TAB_TASKS_INPUT_PANEL_NEW_TASK"),
				],
				"attachButton" => [
					"items" => [
						[
							"id" => "disk",
							"name" => Loc::getMessage("TAB_TASKS_INPUT_PANEL_B24_DISK"),
							"dataSource" => [
								"multiple" => true,
								"url" => "/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId={$this->context->userId}",
							],
						],
					],
				],
				"attachFileSettings" => [
					"resize" => [
						"targetWidth" => -1,
						"targetHeight" => -1,
						"sourceType" => 1,
						"encodingType" => 0,
						"mediaType" => 2,
						"allowsEdit" => false,
						"saveToPhotoAlbum" => true,
						"cameraDirection" => 0,
					],
					"maxAttachedFilesCount" => 100,
				],
			];
		}

		return $data;
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

	public function getShortTitle()
	{
		return Loc::getMessage("TAB_NAME_TASKS_LIST_SHORT");
	}

	public function getId()
	{
		return "tasks";
	}
}
