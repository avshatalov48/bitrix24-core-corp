<?php
namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Config\Option;
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

		if ($apiVersion < 28 || (Mobile::getPlatform() === 'ios' && Mobile::getSystemVersion() < 11))
		{
			return [
				'sort' => 400,
				'imageName' => 'task',
				'badgeCode' => 'tasks',
				'page' => [
					'url' => "{$this->context->siteDir}mobile/tasks/snmrouter/",
				],
			];
		}

		$taskListComponent = [
			'name' => 'JSStackComponent',
			'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_HEADER'),
			'componentCode' => 'tasks.list',
			'scriptPath' => Manager::getComponentPath('tasks:tasks.list'),
			'rootWidget' => [
				'name' => 'tasks.list',
				'settings' => [
					'objectName' => 'list',
					'useSearch' => true,
					'useLargeTitleMode' => true,
				],
			],
			'params' => [
				'COMPONENT_CODE' => 'tasks.list',
				'USER_ID' => $this->context->userId,
				'SITE_ID' => $this->context->siteId,
				'SITE_DIR' => $this->context->siteDir,
				'LANGUAGE_ID' => LANGUAGE_ID,
				'PATH_TO_TASK_ADD' => "{$this->context->siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#",
				'PROJECT_NEWS_PATH_TEMPLATE' => \Bitrix\Mobile\Project\Helper::getProjectNewsPathTemplate([
					'siteDir' => $this->context->siteDir,
				]),
				'PROJECT_CALENDAR_WEB_PATH_TEMPLATE' => \Bitrix\Mobile\Project\Helper::getProjectCalendarWebPathTemplate([
					'siteId' => $this->context->siteId,
					'siteDir' => $this->context->siteDir,
				]),
				'MIN_SEARCH_SIZE' => Filter\Helper::getMinTokenSize(),
				'MESSAGES' => [],
			],
		];
		if ($apiVersion >= 40)
		{
			$taskListComponent['rootWidget']['settings']['emptyListMode'] = true;
			$taskListComponent['rootWidget']['settings']['inputPanel'] = [
				'action' => 0,
				'callback' => 0,
				'useImageButton' => true,
				'useAudioMessages' => true,
				'smileButton' => [],
				'message' => [
					'placeholder' => Loc::getMessage('TAB_TASKS_INPUT_PANEL_NEW_TASK'),
				],
				'attachButton' => [
					'items' => [
						[
							'id' => 'disk',
							'name' => Loc::getMessage('TAB_TASKS_INPUT_PANEL_B24_DISK'),
							'dataSource' => [
								'multiple' => true,
								'url' => "/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId={$this->context->userId}",
							],
						],
					],
				],
				'attachFileSettings' => [
					'resize' => [
						'targetWidth' => -1,
						'targetHeight' => -1,
						'sourceType' => 1,
						'encodingType' => 0,
						'mediaType' => 2,
						'allowsEdit' => false,
						'saveToPhotoAlbum' => true,
						'cameraDirection' => 0,
					],
					'maxAttachedFilesCount' => 100,
				],
			];
		}

		$resultComponent = $taskListComponent;
		if ($apiVersion >= 41)
		{
			$taskListComponent['params']['IS_TABS_MODE'] = true;
			$taskListTab = [
				'id' => 'tasks.list',
				'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_TASKS'),
				'component' => $taskListComponent,
			];
			$projectListTab = [
				'id' => 'tasks.project.list',
				'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_PROJECTS'),
				'component' => [
					'name' => 'JSStackComponent',
					'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_HEADER'),
					'componentCode' => 'tasks.project.list',
					'scriptPath' => Manager::getComponentPath('tasks:tasks.project.list'),
					'rootWidget' => [
						'name' => 'tasks.list',
						'settings' => [
							'objectName' => 'list',
							'useSearch' => true,
							'useLargeTitleMode' => true,
							'emptyListMode' => true,
						],
					],
					'params' => [
						'COMPONENT_CODE' => 'tasks.project.list',
						'SITE_ID' => $this->context->siteId,
						'SITE_DIR' => $this->context->siteDir,
						'USER_ID' => $this->context->userId,
						'PROJECT_NEWS_PATH_TEMPLATE' => \Bitrix\Mobile\Project\Helper::getProjectNewsPathTemplate([
							'siteDir' => $this->context->siteDir,
						]),
						'PROJECT_CALENDAR_WEB_PATH_TEMPLATE' => \Bitrix\Mobile\Project\Helper::getProjectCalendarWebPathTemplate([
							'siteId' => $this->context->siteId,
							'siteDir' => $this->context->siteDir,
						]),
						'MIN_SEARCH_SIZE' => Filter\Helper::getMinTokenSize(),
					],
				],
			];

			$scrumTab = [
				'id' => 'tasks.scrum.list',
				'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_SCRUM'),
				'selectable' => false,
			];
			$efficiencyTab = [
				'id' => 'tasks.efficiency',
				'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_EFFICIENCY'),
				'selectable' => false,
			];

			$tabs = [
				$taskListTab,
				$projectListTab,
				$efficiencyTab,
			];

			array_splice($tabs, 2, 0, [$scrumTab]);

			$resultComponent = [
				'name' => 'JSStackComponent',
				'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_HEADER'),
				'componentCode' => 'tasks.tabs',
				'scriptPath' => Manager::getComponentPath('tasks:tasks.tabs'),
				'rootWidget' => [
					'name' => 'tabs',
					'settings' => [
						'objectName' => 'tabs',
						'grabTitle' => true,
						'grabButtons' => true,
						'grabSearch' => true,
						'tabs' => [
							'items' => $tabs,
						],
					],
				],
				'params' => [
					'COMPONENT_CODE' => 'tasks.tabs',
					'USER_ID' => $this->context->userId,
					'SITE_ID' => $this->context->siteId,
				],
			];
		}

		return [
			'sort' => 400,
			'imageName' => 'task',
			'badgeCode' => 'tasks',
			'id' => 'tasks',
			'component' => $resultComponent,
		];
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
		$result = [
			'title' => $this->getTitle(),
			'useLetterImage' => true,
			'color' => '#fabb3f',
			'imageUrl' => 'favorite/icon-tasks.png',
		];

		$data = $this->getDataInternal();
		if (!empty($data['component']))
		{
			$result['params']= [
				'onclick' => "BX.postComponentEvent('taskbackground::taskList::open', [{ownerId: {$this->context->userId}}], 'background');",
				'counter' => 'tasks_total',
			];
		}
		elseif (!empty($data['page']))
		{
			$result['params'] = $data['page'];
			$result['params']['counter'] = 'tasks_total';
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
		return Loc::getMessage("TAB_NAME_TASKS_LIST_SHORT");
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
