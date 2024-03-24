<?php

namespace Bitrix\Tasks\Helper;

use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\ArgumentException;
use Bitrix\Tasks\Ui\Filter;

class Analytics extends Common
{
	public const TOOL = 'tasks';
	public const TASK_CATEGORY = 'task_operations';
	public const COMMENT_CATEGORY = 'comments_operations';
	public const TASK_TYPE = 'task';
	public const COMMENT_TYPE = 'comment';
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
	];

	public const SECTION = [
		'tasks' => 'tasks',
		'project' => 'project',
		'scrum' => 'scrum',
		'crm' => 'crm',
		'feed' => 'feed',
		'chat' => 'chat',
		'mail' => 'mail',
		'calendar' => 'calendar',
		'user' => 'user',
		'comment' => 'comment',
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
	];

	public const ELEMENT = [
		'create_button' => 'create_button',
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
		string $event,
		?string $section,
		?string $element,
		?string $subSection,
		bool $status
	): void
	{
		if (!in_array($event, [self::EVENT['task_create'], self::EVENT['subtask_add']], true))
		{
			return;
		}

		$analyticsEvent = new AnalyticsEvent(
			$event,
			self::TOOL,
			self::TASK_CATEGORY,
		);

		$this->sendAnalytics($analyticsEvent, $section, $element, $subSection, $status);
	}

	/**
	 * @param string $section
	 * @param string|null $element
	 * @param string|null $subSection
	 * @return void
	 * @throws ArgumentException
	 */
	public function onTaskView(string $section, ?string $element = null, ?string $subSection = null): void
	{
		$analyticsEvent = new AnalyticsEvent(
			self::EVENT['task_view'],
			self::TOOL,
			self::TASK_CATEGORY,
		);

		$this->sendAnalytics($analyticsEvent, $section, $element, $subSection);
	}

	/**
	 * @return void
	 * @throws ArgumentException
	 */
	public function onCommentAdd(): void
	{
		$analyticsEvent = new AnalyticsEvent(
			self::EVENT['comment_add'],
			self::TOOL,
			self::COMMENT_CATEGORY
		);

		$this->sendAnalytics(
			$analyticsEvent,
			self::SECTION['tasks'],
			self::ELEMENT['send_button'],
			self::SUB_SECTION['task_card'],
			true,
			self::COMMENT_TYPE
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
		string $type = self::TASK_TYPE
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

		$analyticsEvent->send();
	}
}