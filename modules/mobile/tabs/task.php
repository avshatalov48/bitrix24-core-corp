<?php

namespace Bitrix\Mobile\AppTabs;

use Bitrix\Extranet\Service\ServiceContainer;
use Bitrix\Intranet\Settings\Tools\ToolsManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Mobile\Context;
use Bitrix\Mobile\Project\Helper;
use Bitrix\Mobile\Tab\Tabable;
use Bitrix\Mobile\Tab\Utils;
use Bitrix\MobileApp\Janative\Manager;
use Bitrix\Socialnetwork\Component\WorkgroupList;
use Bitrix\Socialnetwork\Helper\Feature;
use Bitrix\TasksMobile\Provider\TariffPlanRestrictionProvider;
use Bitrix\TasksMobile\Settings;

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
			'params' => [
				'id' => 'tasks_tabs',
			],
			'imageName' => $this->getIconId(),
		];

		$data = $this->getDataInternal();
		if (!empty($data['component']))
		{
			$result['params']['onclick'] = Utils::getComponentJSCode($data['component']);
			$result['params']['counter'] =' tasks_total';
		}
		elseif (!empty($data['page']))
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
		if (!Loader::includeModule('tasks') || !Loader::includeModule('tasksmobile'))
		{
			return false;
		}

		if (Loader::includeModule('socialnetwork'))
		{
			$userActiveFeatures = \CSocNetFeatures::getActiveFeatures(SONET_ENTITY_USER, $this->context->userId);
			$socNetFeatures = \CSocNetAllowed::getAllowedFeatures();

			return (
				$this->isToolAvailable('tasks')
				&& array_key_exists('tasks', $socNetFeatures)
				&& array_key_exists('allowed', $socNetFeatures['tasks'])
				&& in_array(SONET_ENTITY_USER, $socNetFeatures['tasks']['allowed'])
				&& is_array($userActiveFeatures)
				&& in_array('tasks', $userActiveFeatures)
			);
		}

		return false;
	}

	private function isToolAvailable(string $toolId): bool
	{
		if (Loader::includeModule('intranet'))
		{
			return ToolsManager::getInstance()->checkAvailabilityByToolId($toolId);
		}

		return true;
	}

	/**
	 * @throws \Exception
	 */
	private function getDataInternal(): array
	{
		return [
			'sort' => 400,
			'imageName' => $this->getIconId(),
			'badgeCode' => 'tasks',
			'id' => 'tasks',
			'component' => ($this->isCollaber() ? $this->getTasksDashboardComponent() : $this->getTabsComponent()),
		];
	}

	/**
	 * @throws \Exception
	 */
	private function getTabsComponent(): array
	{
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
					'useLargeTitleMode' => true,
					'tabs' => [
						'items' => array_values(
							array_filter([
								$this->getTaskListTab(),
								$this->getProjectListTab(),
								$this->getFlowListTab(),
								$this->getScrumListTab(),
								$this->getEfficiencyTab(),
							]),
						),
					],
				],
			],
			'params' => [
				'COMPONENT_CODE' => 'tasks.tabs',
				'USER_ID' => $this->context->userId,
				'SITE_ID' => $this->context->siteId,
				'SHOW_SCRUM_LIST' => Option::get('tasksmobile', 'showScrumList', 'N') === 'Y',
				'TAB_CODES' => [
					'TASKS' => 'tasks.dashboard',
					'FLOW' => 'tasks.flow.list',
					'PROJECTS' => 'tasks.project.list',
					'SCRUM' => 'tasks.scrum.list',
					'EFFICIENCY' => 'tasks.efficiency',
				],
			],
		];
	}

	private function getTaskListTab(): array
	{
		return [
			'id' => 'tasks.dashboard',
			'testId' => 'tasks_list',
			'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_TASKS'),
			'component' => $this->getTasksDashboardComponent(),
		];
	}

	private function getTasksDashboardComponent(): array
	{
		return [
			'name' => 'JSStackComponent',
			'title' => (
				$this->isCollaber()
					? Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_TASKS')
					: Loc::getMessage('TAB_TASKS_NAVIGATION_HEADER')
			),
			'componentCode' => 'tasks.dashboard',
			'scriptPath' => Manager::getComponentPath('tasks:tasks.dashboard'),
			'settings' => [
				'preload' => true,
			],
			'rootWidget' => [
				'name' => 'layout',
				'settings' => [
					'objectName' => 'layout',
					'useSearch' => true,
					'useLargeTitleMode' => true,
				],
			],
			'params' => [
				'COMPONENT_CODE' => 'tasks.dashboard',
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
				'IS_TABS_MODE' => !$this->isCollaber(),
				'IS_ROOT_COMPONENT' => $this->isCollaber(),
			],
		];
	}

	private function getProjectListTab(): ?array
	{
		if (!$this->isToolAvailable('projects'))
		{
			return null;
		}

		$isProjectRestricted = (
			!Feature::isFeatureEnabled(Feature::PROJECTS_GROUPS)
			&& !Feature::canTurnOnTrial(Feature::PROJECTS_GROUPS)
		);

		return [
			'id' => 'tasks.project.list',
			'testId' => 'tasks_project',
			'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_PROJECTS'),
			'icon' => ($isProjectRestricted ? 'lock' : null),
			'selectable' => !$isProjectRestricted,
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
	}

	private function getFlowListTab(): ?array
	{
		if (!Settings::getInstance()->isTaskFlowAvailable())
		{
			return null;
		}

		return [
			'id' => 'tasks.flow.list',
			'testId' => 'tasks_flow',
			'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_FLOW'),
			'component' => [
				'name' => 'JSStackComponent',
				'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_HEADER'),
				'componentCode' => 'tasks.flow.list',
				'scriptPath' => Manager::getComponentPath('tasks:tasks.flow.list'),
				'rootWidget' => [
					'name' => 'layout',
					'settings' => [
						'objectName' => 'layout',
						'useSearch' => true,
						'useLargeTitleMode' => true,
					],
				],
				'params' => [
					'COMPONENT_CODE' => 'tasks.flow.list',
					'USER_ID' => $this->context->userId,
					'SITE_ID' => $this->context->siteId,
					'SITE_DIR' => $this->context->siteDir,
					'LANGUAGE_ID' => LANGUAGE_ID,
				],
			],
		];
	}

	private function getScrumListTab(): ?array
	{
		if (!$this->isToolAvailable('scrum'))
		{
			return null;
		}

		return [
			'id' => 'tasks.scrum.list',
			'testId' => 'tasks_scrum',
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
			'selectable' => Option::get('tasksmobile', 'showScrumList', 'N') === 'Y',
		];
	}

	private function getEfficiencyTab(): ?array
	{
		if (!$this->isToolAvailable('effective'))
		{
			return null;
		}

		return [
			'id' => 'tasks.efficiency',
			'testId' => 'tasks_efficiency',
			'title' => Loc::getMessage('TAB_TASKS_NAVIGATION_TAB_EFFICIENCY'),
			'icon' => (TariffPlanRestrictionProvider::isEfficiencyRestricted() ? 'lock' : null),
			'selectable' => false,
		];
	}

	private function isCollaber(): bool
	{
		return (
			Loader::includeModule('extranet')
			&& ServiceContainer::getInstance()->getCollaberService()->isCollaberById($this->context->userId)
		);
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
		return $this->isToolAvailable('tasks');
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
		return 'circle_check';
	}
}
