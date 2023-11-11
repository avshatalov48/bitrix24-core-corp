<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Project\Helper;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\MobileApp\Mobile;
use Bitrix\Socialnetwork\Component\WorkgroupList;

class Task implements Tabable
{
	private Context $context;

	/**
	 * @throws LoaderException
	 * @throws \Exception
	 */
	public function getData(): ?array
	{
		if (!$this->isAvailable())
		{
			return null;
		}

		return $this->getDataInternal();
	}

	/**
	 * @throws \Exception
	 */
	public function getMenuData(): ?array
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
		else if (!empty($data['page']))
		{
			$result['params'] = $data['page'];
			$result['params']['counter'] = 'tasks_total';
		}

		return $result;
	}

	/**
	 * @throws LoaderException
	 */
	public function isAvailable(): bool
	{
		if (Loader::includeModule('socialnetwork'))
		{
			$userActiveFeatures = \CSocNetFeatures::getActiveFeatures(SONET_ENTITY_USER, $this->context->userId);
			$socNetFeatures = \CSocNetAllowed::getAllowedFeatures();

			return
				array_key_exists('tasks', $socNetFeatures)
				&& array_key_exists('allowed', $socNetFeatures['tasks'])
				&& in_array(SONET_ENTITY_USER, $socNetFeatures['tasks']['allowed'])
				&& is_array($userActiveFeatures)
				&& in_array('tasks', $userActiveFeatures)
			;
		}

		return false;
	}

	/**
	 * @throws \Exception
	 */
	private function getDataInternal(): array
	{
		return [
			'sort' => 400,
			'imageName' => 'task',
			'badgeCode' => 'tasks',
			'id' => 'tasks',
			'component' => $this->getTabsComponent(),
		];
	}

	/**
	 * @throws \Exception
	 */
	private function getTabsComponent(): array
	{
		$taskListTab = [
			'id' => 'tasks.list',
			'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_TASKS'),
			'component' => [
				'name' => 'JSStackComponent',
				'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_HEADER'),
				'componentCode' => 'tasks.list',
				'scriptPath' => Manager::getComponentPath('tasks:tasks.list'),
				'rootWidget' => $this->getTaskListRootWidget(),
				'params' => [
					'COMPONENT_CODE' => 'tasks.list',
					'USER_ID' => $this->context->userId,
					'SITE_ID' => $this->context->siteId,
					'SITE_DIR' => $this->context->siteDir,
					'LANGUAGE_ID' => LANGUAGE_ID,
					'PATH_TO_TASK_ADD' => "{$this->context->siteDir}mobile/tasks/snmrouter/?routePage=#action#&TASK_ID=#taskId#",
					'PROJECT_NEWS_PATH_TEMPLATE' => Helper::getProjectNewsPathTemplate([
						'siteDir' => $this->context->siteDir,
					]),
					'PROJECT_CALENDAR_WEB_PATH_TEMPLATE' => Helper::getProjectCalendarWebPathTemplate([
						'siteId' => $this->context->siteId,
						'siteDir' => $this->context->siteDir,
					]),
					'MESSAGES' => [],
					'IS_TABS_MODE' => true,
				],
			],
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
					'PROJECT_NEWS_PATH_TEMPLATE' => Helper::getProjectNewsPathTemplate([
						'siteDir' => $this->context->siteDir,
					]),
					'PROJECT_CALENDAR_WEB_PATH_TEMPLATE' => Helper::getProjectCalendarWebPathTemplate([
						'siteId' => $this->context->siteId,
						'siteDir' => $this->context->siteDir,
					]),
					'MODE' => WorkgroupList::MODE_TASKS_PROJECT,
				],
			],
		];
		$scrumTab = [
			'id' => 'tasks.scrum.list',
			'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_SCRUM'),
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
					'PROJECT_NEWS_PATH_TEMPLATE' => Helper::getProjectNewsPathTemplate([
						'siteDir' => $this->context->siteDir,
					]),
					'PROJECT_CALENDAR_WEB_PATH_TEMPLATE' => Helper::getProjectCalendarWebPathTemplate([
						'siteId' => $this->context->siteId,
						'siteDir' => $this->context->siteDir,
					]),
					'MODE' => WorkgroupList::MODE_TASKS_SCRUM,
				],
			],
			'selectable' => false,
		];
		$showScrumList = (Option::get('tasksmobile', 'showScrumList', 'N') === 'Y');
		if ($showScrumList)
		{
			unset($scrumTab['selectable']);
		}
		$efficiencyTab = [
			'id' => 'tasks.efficiency',
			'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_EFFICIENCY'),
			'selectable' => false,
		];

		return [
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
						'items' => [
							$taskListTab,
							$projectListTab,
							$scrumTab,
							$efficiencyTab,
						],
					],
				],
			],
			'params' => [
				'COMPONENT_CODE' => 'tasks.tabs',
				'USER_ID' => $this->context->userId,
				'SITE_ID' => $this->context->siteId,
				'SHOW_SCRUM_LIST' => $showScrumList,
			],
		];
	}

	private function getTaskListRootWidget(): array
	{
		// if (Mobile::getApiVersion() >= 49)
		// {
		// 	return [
		// 		'name' => 'layout',
		// 		'settings' => [
		// 			'objectName' => 'layout',
		// 			'useSearch' => true,
		// 			'useLargeTitleMode' => true,
		// 		],
		// 	];
		// }

		return [
			'name' => 'tasks.list',
			'settings' => [
				'objectName' => 'list',
				'useSearch' => true,
				'useLargeTitleMode' => true,
				'emptyListMode' => true,
			],
		];
	}

	public function getId(): string
	{
		return 'tasks';
	}

	public function getTitle(): ?string
	{
		return Loc::getMessage('TAB_NAME_TASKS_LIST_SHORT');
	}

	public function getShortTitle(): ?string
	{
		return Loc::getMessage('TAB_NAME_TASKS_LIST_SHORT');
	}

	public function shouldShowInMenu(): bool
	{
		return true;
	}

	public function canBeRemoved(): bool
	{
		return true;
	}

	public function canChangeSort(): bool
	{
		return true;
	}

	public function defaultSortValue(): int
	{
		return 400;
	}

	/**
	 * @param Context $context
	 * @return void
	 */
	public function setContext($context): void
	{
		$this->context = $context;
	}

	public function getIconId(): string
	{
		return 'task';
	}
}
