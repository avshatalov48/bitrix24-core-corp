import {Event, Loc, Tag, Dom} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Backlog} from './backlog';

export class Header extends EventEmitter
{
	constructor(backlog: Backlog)
	{
		super(backlog);

		this.setEventNamespace('BX.Tasks.Scrum.BacklogHeader');

		this.backlog = backlog;

		this.node = null;
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

		Event.bind(buttons.item(0), 'click', this.onEpicClick.bind(this, buttons.item(0)));
		Event.bind(buttons.item(1), 'click', this.onTaskClick.bind(this));

		//todo use it from project scrum button
		//this.emit('openListEpicGrid');
		//this.emit('openDefinitionOfDone');

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

	onEpicClick(button: HTMLElement)
	{
		this.emit('epicClick', button);
	}

	onTaskClick()
	{
		this.emit('taskClick');
	}
}
