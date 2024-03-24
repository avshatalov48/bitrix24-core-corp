import { Event, Loc, Tag, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { Backlog } from './backlog';
export class Header extends EventEmitter
{
	constructor(backlog: Backlog)
	{
		super(backlog);

		this.setEventNamespace('BX.Tasks.Scrum.BacklogHeader');

		this.backlog = backlog;

		this.epicButtonLocked = false;
		this.taskButtonLocked = false;

		this.node = null;

		this.epicButton = null;
		this.taskButton = null;
	}

	render(): HTMLElement
	{
		const uiEpicClasses = 'ui-btn ui-btn-sm ui-btn-light-border ui-btn-themes ui-btn-round ui-btn-no-caps';
		const uiTaskClasses = 'ui-btn ui-btn-sm ui-btn-success ui-btn-round ui-btn-no-caps ';

		this.node = Tag.render`
			<div class="tasks-scrum__content-header">

				<div class="tasks-scrum__name-container">
					<div class="tasks-scrum__title">
						${Loc.getMessage('TASKS_SCRUM_BACKLOG_TITLE')}
					</div>
					${this.renderTaskCounterLabel(this.backlog.getNumberTasks())}
				</div>

				<button class="tasks-scrum__backlog-btn ${uiEpicClasses} ui-btn-icon-add">
					${Loc.getMessage('TASKS_SCRUM_BACKLOG_HEADER_EPIC')}
				</button>
				<button class="tasks-scrum__backlog-btn ${uiTaskClasses} ui-btn-icon-add">
					${Loc.getMessage('TASKS_SCRUM_BACKLOG_HEADER_TASK')}
				</button>

			</div>
		`;

		const buttons = this.node.querySelectorAll('button');

		this.epicButton = buttons.item(0);
		this.taskButton = buttons.item(1);

		Event.bind(buttons.item(0), 'click', this.onEpicClick.bind(this));
		Event.bind(buttons.item(1), 'click', this.onTaskClick.bind(this));

		return this.node;
	}

	updateTaskCounter(value: string)
	{
		Dom.replace(
			this.node.querySelector('.tasks-scrum__backlog-tasks'),
			this.renderTaskCounterLabel(value)
		);
	}

	renderTaskCounterLabel(value: string)
	{
		return Tag.render`
			<div class="tasks-scrum__backlog-tasks">
				${Loc.getMessage('TASKS_SCRUM_BACKLOG_HEADER_TASK_COUNTER').replace('#value#', value)}
			</div>
		`;
	}

	unLockEpicButton()
	{
		this.epicButtonLocked = false;

		Dom.removeClass(this.epicButton, 'ui-btn-wait');
	}

	lockEpicButton()
	{
		this.epicButtonLocked = true;

		Dom.addClass(this.epicButton, 'ui-btn-wait');
	}

	unLockTaskButton()
	{
		this.taskButtonLocked = false;

		Dom.removeClass(this.taskButton, 'ui-btn-wait');
	}

	lockTaskButton()
	{
		this.taskButtonLocked = true;

		Dom.addClass(this.taskButton, 'ui-btn-wait');
	}

	onEpicClick()
	{
		if (this.epicButtonLocked)
		{
			return;
		}

		this.lockEpicButton();

		this.emit('epicClick');
	}

	onTaskClick()
	{
		if (this.taskButtonLocked)
		{
			return;
		}

		this.lockTaskButton();

		this.emit('taskClick');
	}
}
