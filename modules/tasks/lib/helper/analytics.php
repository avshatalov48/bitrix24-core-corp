<?php

namespace Bitrix\Tasks\Helper;

use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\Intranet\User;
use Bitrix\Tasks\Ui\Filter;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;

class Analytics extends Common
{
	protected static ?array $instance = null;

	public const TOOL = 'tasks';

	public const TASK_CATEGORY = 'task_operations';
	public const CATEGORY = 'tasks';
	public const COMMENT_CATEGORY = 'comments_operations';
	public const FLOW_CATEGORY = 'flows';
	public const LEAD_CATEGORY = 'lead';

	public const TASK_TYPE = 'task';
	public const COMMENT_TYPE = 'comment';
	public const NOTIFICATION_TYPE = 'notification';

	public const STATUS_SUCCESS = 'success';
	public const STATUS_ERROR = 'error';

	public const EVENT = [
		'task_create' => 'task_create',
		'task_view' => 'task_view',
		'task_complete' => 'task_complete',
		'comment_add' => 'comment_add',
		'status_summary_add' => 'status_summary_add',
		'subtask_add' => 'subtask_add',
		'overdue_counters_on' => 'overdue_counters_on',
		'comments_counters_on' => 'comments_counters_on',
		'flow_create_start' => 'flow_create_start',
		'flow_create_finish' => 'flow_create_finish',
		'flow_edit_start' => 'flow_edit_start',
		'flow_edit_finish' => 'flow_edit_finish',
		'flows_view' => 'flows_view',
		'lead_view' => 'lead_view',
		'tasks_projects_view' => 'tasks_projects_view',
		'add_viewer' => 'add_viewer',
		'add_coexecutor' => 'add_coexecutor',
		'task_update' => 'task_update',
		'click_create' => 'click_create',
		'assignee_change' => 'assignee_change',
		'deadline_set' => 'deadline_set',
		'add_checklist' => 'add_checklist',
		'task_create_with_checklist' => 'task_create_with_checklist',
		'click_task_link' => 'click_task_link',
		'task_delegation' => 'task_delegation',
		'notification_sent' => 'notification_sent',
	];

	public const SECTION = [
		'tasks' => 'tasks',
		'lead' => 'lead',
		'project' => 'project',
		'scrum' => 'scrum',
		'crm' => 'crm',
		'feed' => 'feed',
		'chat' => 'chat',
		'mail' => 'mail',
		'calendar' => 'calendar',
		'user' => 'user',
		'comment' => 'comment',
		'flows' => 'flows',
		'collab' => 'collab',
		'onboarding_notification' => 'onboarding_notification',
	];

	public const SUB_SECTION = [
		'list' => 'list',
		'kanban' => 'kanban',
		'deadline' => 'deadline',
		'planner' => 'planner',
		'calendar' => 'calendar',
		'gantt' => 'gantt',
		'task_card' => 'task_card',
		'efficiency' => 'efficiency',
		'lead' => 'lead',
		'deal' => 'deal',
		'contact' => 'contact',
		'company' => 'company',
		'flows' => 'flows',
		'flows_grid' => 'flows_grid',
		'group_card' => 'group_card',
		'flow_guide' => 'flow_guide',
		'copilot_advice' => 'copilot_advice',
		'existing' => 'existing',
	];

	public const ELEMENT = [
		'create_button' => 'create_button',
		'edit_button' => 'edit_button',
		'save_changes_button' => 'save_changes_button',
		'quick_button' => 'quick_button',
		'left_menu' => 'left_menu',
		'horizontal_menu' => 'horizontal_menu',
		'widget_menu' => 'widget_menu',
		'title_click' => 'title_click',
		'view_button' => 'view_button',
		'context_menu' => 'context_menu',
		'comment_context_menu' => 'comment_context_menu',
		'send_button' => 'send_button',
		'checkbox' => 'checkbox',
		'complete_button' => 'complete_button',
		'flows_grid_button' => 'flows_grid_button',
		'flow_popup' => 'flow_popup',
		'flow_selector' => 'flow_selector',
		'section_button' => 'section_button',
		'my_tasks_column' => 'my_tasks_column',
		'create_demo_button' => 'create_demo_button',
		'guide_button' => 'guide_button',
		'viewer_button' => 'viewer_button',
		'coexecutor_button' => 'coexecutor_button',
		'change_button' => 'change_button',
		'deadline_field' => 'deadline_field',
		'checklist_button' => 'checklist_button',
		'task_link' => 'task_link',
		'delegation_button' => 'delegation_button',
	];

	/**
	 * @return string
	 */
	public function getViewStateName(): string
	{
		Filter\Task::setUserId($this->getUserId());
		if ($this->getGroupId())
		{
			Filter\Task::setGroupId($this->getGroupId());
		}

		$state = Filter\Task::listStateInit()?->getState();

		$viewState = !empty($state) ? $state['VIEW_SELECTED']['CODENAME'] : null;

		return match ($viewState)
		{
			'VIEW_MODE_GANTT' => 'gantt',
			'VIEW_MODE_PLAN' => 'planner',
			'VIEW_MODE_TIMELINE' => 'deadline',
			'VIEW_MODE_CALENDAR' => 'calendar',
			'VIEW_MODE_KANBAN' => 'kanban',
			default => 'list',
		};
	}

	/**
	 * @param string $event
	 * @param string|null $section
	 * @param string|null $element
	 * @param string|null $subSection
	 * @param bool $status
	 * @return void
	 * @throws ArgumentException
	 */
	public function onTaskCreate(
		string $category,
		string $event,
		?string $section,
		?string $element,
		?string $subSection,
		bool $status,
		array $params = [],
	): void
	{
		if (!in_array(
			$event,
			[
				self::EVENT['task_create'],
				self::EVENT['subtask_add'],
				self::EVENT['task_create_with_checklist'],
			],
			true)
		)
		{
			return;
		}

		$analyticsEvent = new AnalyticsEvent(
			$event,
			self::TOOL,
			$category,
		);

		$this->sendAnalytics(
			$analyticsEvent,
			$section,
			$element,
			$subSection,
			$status,
			self::TASK_TYPE,
			$params,
		);
	}

	/**
	 * @param string $section
	 * @param string|null $element
	 * @param string|null $subSection
	 * @return void
	 * @throws ArgumentException
	 */
	public function onTaskView(
		string $section,
		?string $element = null,
		?string $subSection = null,
		array $params = [],
		?string $event = null,
		?string $type = null,
	): void
	{
		$analyticsEvent = new AnalyticsEvent(
			$event ?? self::EVENT['task_view'],
			self::TOOL,
			self::TASK_CATEGORY,
		);

		$this->sendAnalytics(
			$analyticsEvent,
			$section,
			$element,
			$subSection,
			true,
			$type ?? self::TASK_TYPE,
			$params,
		);
	}

	public function onTaskListView(
		string $event,
		?string $section = null,
		?string $element = null,
		?string $subSection = null,
		array $params = [],
	): void
	{
		$analyticsEvent = new AnalyticsEvent(
			$event,
			self::TOOL,
			self::CATEGORY,
		);

		$this->sendAnalytics(
			$analyticsEvent,
			$section,
			$element,
			$subSection,
			true,
			self::TASK_TYPE,
			$params,
		);
	}

	public function onTaskUpdate(
		string $event,
		?string $section = null,
		?string $element = null,
		?string $subSection = null,
		array $params = [],
	): void
	{
		$analyticsEvent = new AnalyticsEvent(
			$event,
			self::TOOL,
			self::TASK_CATEGORY,
		);

		$this->sendAnalytics(
			$analyticsEvent,
			$section,
			$element,
			$subSection,
			true,
			self::TASK_TYPE,
			$params,
		);
	}

	public function onTaskCreateClick(
		?string $section = null,
		?string $element = null,
		?string $subSection = null,
		array $params = [],
	): void
	{
		$analyticsEvent = new AnalyticsEvent(
			self::EVENT['click_create'],
			self::TOOL,
			self::TASK_CATEGORY,
		);

		$this->sendAnalytics(
			analyticsEvent: $analyticsEvent,
			section: $section,
			element: $element,
			subSection: $subSection,
			params: $params,
		);
	}

	public function getTaskContext(int $taskId): string
	{
		$task = TaskRegistry::getInstance()->getObject($taskId);

		if (!$task)
		{
			return self::SECTION['tasks'];
		}

		if ($task->getFlowId() > 0)
		{
			return self::SECTION['flows'];
		}

		if ($task->isScrum())
		{
			return self::SECTION['scrum'];
		}

		if ($task->isInGroup())
		{
			return self::SECTION['project'];
		}

		if ($task->isCrm())
		{
			return self::SECTION['crm'];
		}

		if ($task->isCollab())
		{
			return self::SECTION['collab'];
		}

		if ($task->isDescriptionInBbcodeFilled())
		{
			return self::SECTION['chat'];
		}

		return self::SECTION['tasks'];
	}

	public function getViewersCountParameter(int $taskId): string
	{
		$task = TaskRegistry::getInstance()->getObject($taskId);

		$viewersCount = 0;

		if ($task)
		{
			$viewersCount = count($task->getMembersIdsByRole('U'));
		}

		return 'viewersCount_' . $viewersCount;
	}

	public function getCoexecutorsCountParameter(int $taskId): string
	{
		$task = TaskRegistry::getInstance()->getObject($taskId);

		$coexecutorsCount = 0;

		if ($task)
		{
			$coexecutorsCount = count($task->getMembersIdsByRole('A'));
		}

		return 'coexecutorsCount_' . $coexecutorsCount;
	}

	public function getCollabIdFromTask(int $taskId): int
	{
		$task = TaskRegistry::getInstance()->getObject($taskId);

		if ($task?->isCollab())
		{
			return $task->getGroupId();
		}

		return 0;
	}

	public function onFlowCreate(
		string $event,
		string $section,
		?string $element = null,
		?string $subSection = null,
		array $params = []
	): void
	{
		$availableEvents = [
			'flow_create_start',
			'flow_create_finish',
		];
		if (!in_array($event, $availableEvents, true))
		{
			return;
		}

		$analyticsEvent = new AnalyticsEvent(
			$event,
			self::TOOL,
			self::FLOW_CATEGORY,
		);

		$this->sendAnalytics(
			$analyticsEvent,
			$section,
			$element,
			$subSection,
			true,
			self::TASK_TYPE,
			$params,
		);
	}

	public function onFlowEdit(
		string $event,
		string $section,
		?string $element = null,
		?string $subSection = null,
		array $params = []
	): void
	{
		$availableEvents = [
			'flow_edit_start',
			'flow_edit_finish',
		];
		if (!in_array($event, $availableEvents, true))
		{
			return;
		}

		$analyticsEvent = new AnalyticsEvent(
			$event,
			self::TOOL,
			self::FLOW_CATEGORY,
		);

		$this->sendAnalytics(
			$analyticsEvent,
			$section,
			$element,
			$subSection,
			true,
			self::TASK_TYPE,
			$params,
		);
	}

	public function onFlowsView(
		string $section,
		?string $element = null,
		?string $subSection = null,
		array $params = []
	): void
	{
		$analyticsEvent = new AnalyticsEvent(
			self::EVENT['flows_view'],
			self::TOOL,
			self::FLOW_CATEGORY,
		);

		$this->sendAnalytics(
			$analyticsEvent,
			$section,
			$element,
			$subSection,
			true,
			self::TASK_TYPE,
			$params,
		);
	}

	/**
	 * @return void
	 * @throws ArgumentException
	 */
	public function onCommentAdd(string $section = self::SECTION['tasks'], array $params = []): void
	{
		$analyticsEvent = new AnalyticsEvent(
			self::EVENT['comment_add'],
			self::TOOL,
			self::COMMENT_CATEGORY
		);

		$this->sendAnalytics(
			$analyticsEvent,
			$section,
			self::ELEMENT['send_button'],
			self::SUB_SECTION['task_card'],
			true,
			self::COMMENT_TYPE,
			$params
		);
	}

	/**
	 * @param string|null $element
	 * @return void
	 * @throws ArgumentException
	 */
	public function onStatusSummaryAdd(?string $element = null): void
	{
		$analyticsEvent = new AnalyticsEvent(
			self::EVENT['status_summary_add'],
			self::TOOL,
			self::TASK_CATEGORY,
		);

		if (empty($element))
		{
			$element = self::ELEMENT['checkbox'];
		}

		$this->sendAnalytics(
			$analyticsEvent,
			self::SECTION['tasks'],
			$element,
			self::SUB_SECTION['task_card'],
		);
	}

	/**
	 * @param string $section
	 * @param string|null $subSection
	 * @param string|null $element
	 * @return void
	 * @throws ArgumentException
	 */
	public function onTaskComplete(string $section, ?string $subSection = null, ?string $element = null): void
	{
		$analyticsEvent = new AnalyticsEvent(
			self::EVENT['task_complete'],
			self::TOOL,
			self::TASK_CATEGORY
		);

		$this->sendAnalytics($analyticsEvent, $section, $element, $subSection);
	}

	public function onLeadView(
		string $section,
		?string $element = null,
		?string $subSection = null,
		array $params = []
	): void
	{
		$analyticsEvent = new AnalyticsEvent(
			self::EVENT['lead_view'],
			self::TOOL,
			self::LEAD_CATEGORY,
		);

		$this->sendAnalytics(
			$analyticsEvent,
			$section,
			$element,
			$subSection,
			true,
			'',
			$params,
		);
	}

	public function onFirstProjectCreation(): void
	{
		$this->logToFile(
			'markShowedStep',
			'firstProjectCreation',
			'0',
			'tourGuide'
		);
	}

	public function onFirstScrumCreation(): void
	{
		$this->logToFile(
			'markShowedStep',
			'firstScrumCreation',
			'0',
			'tourGuide'
		);
	}

	public function onFirstTaskGridCreation(): void
	{
		$this->logToFile(
			'markShowedStep',
			'firstGridTaskCreation',
			'0',
			'tourGuide'
		);
	}

	public function onQrMobile(): void
	{
		$this->logToFile(
			'send',
			'QrMobile',
			0,
			'QrMobile',
			$this->userId
		);
	}

	public function onTaskDelegate(
		string $section,
		bool $status = true,
	): void
	{
		$analyticsEvent = new AnalyticsEvent(
			event: self::EVENT['task_delegation'],
			tool: self::TOOL,
			category: self::TASK_CATEGORY
		);

		$this->sendAnalytics(
			analyticsEvent: $analyticsEvent,
			section: $section,
			element: self::ELEMENT['delegation_button'],
			subSection: self::SUB_SECTION['task_card'],
			status: $status,
			params: [
				'p1' => $this->getIsDemoParameter(),
			]
		);
	}

	public function onTaskOnboardingPingSent(): void
	{
		$analyticsEvent = new AnalyticsEvent(
			event: self::EVENT['notification_sent'],
			tool: self::TOOL,
			category: self::TASK_CATEGORY
		);

		$this->sendAnalytics(
			analyticsEvent: $analyticsEvent,
			section: self::SECTION['onboarding_notification'],
			type: self::NOTIFICATION_TYPE,
		);
	}

	public function logToFile(
		string $action,
		string $tag = '',
		string $label = '',
		string $actionType = '',
		?int $userId = null
	): void
	{
		if (function_exists('AddEventToStatFile'))
		{
			AddEventToStatFile('tasks', $action, $tag, $label, $actionType, $userId);
		}
	}

	public function getUserTypeParameter(): string
	{
		if ($this->userId <= 0)
		{
			return '';
		}

		if (User::isIntranet($this->userId))
		{
			return 'user_intranet';
		}

		if (User::isCollaber($this->userId))
		{
			return 'user_collaber';
		}

		return 'user_extranet';
	}

	public function getCollabParameter(int $collabId): string
	{
		return 'collabId_' . $collabId;
	}

	public function getIsDemoParameter(): string
	{
		$isDemo = (Loader::includeModule('bitrix24') && \CBitrix24::IsDemoLicense()) ? 'Y' : 'N';

		return 'isDemo_' . $isDemo;
	}

	/**
	 * @param AnalyticsEvent $analyticsEvent
	 * @param string|null $section
	 * @param mixed $element
	 * @param mixed $subSection
	 * @param bool $status
	 * @param string $type
	 * @return void
	 * @throws ArgumentException
	 */
	private function sendAnalytics(
		AnalyticsEvent $analyticsEvent,
		?string $section = null,
		?string $element = null,
		?string $subSection = null,
		bool $status = true,
		string $type = self::TASK_TYPE,
		array $params = [],
	): void
	{
		$analyticsEvent
			->setType($type)
			->setStatus($status ? self::STATUS_SUCCESS : self::STATUS_ERROR)
		;

		if (in_array($section, self::SECTION, true))
		{
			$analyticsEvent->setSection($section);
		}
		if (in_array($element, self::ELEMENT, true))
		{
			$analyticsEvent->setElement($element);
		}
		if (in_array($subSection, self::SUB_SECTION, true))
		{
			$analyticsEvent->setSubSection($subSection);
		}

		for ($i = 1; $i <= 5; $i++)
		{
			if (!empty($params['p' . $i]) && is_string($params['p' . $i]))
			{
				$methodName = 'setP' . ($i);
				if (method_exists($analyticsEvent, $methodName))
				{
					$analyticsEvent->$methodName($params['p' . $i]);
				}
			}
		}

		$analyticsEvent->send();
	}
}
