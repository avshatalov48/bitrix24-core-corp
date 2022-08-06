import {Dom, Tag, Loc} from 'main.core';

import {Entity} from './entity';

export class Blank
{
	constructor(entity: Entity)
	{
		this.entity = entity;

		this.node = null;
	}

	render(): HTMLElement
	{
		if (this.entity.isBacklog())
		{
			return this.renderBacklog();
		}
		else if (this.entity.isCompleted())
		{
			return this.renderCompletedSprint();
		}
	}

	renderBacklog(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__backlog-empty">
				<div class="tasks-scrum__backlog-empty--icon"></div>
				<div class="tasks-scrum__backlog-empty--title">
					${Loc.getMessage('TASKS_SCRUM_BACKLOG_BLANK_1')}
				</div>
				<div class="tasks-scrum__backlog-empty--subtitle">
					${Loc.getMessage('TASKS_SCRUM_BACKLOG_BLANK_2')}
				</div>
				<div class="tasks-scrum__backlog-empty--text">
					${Loc.getMessage('TASKS_SCRUM_BACKLOG_BLANK_3')}
				</div>
				<div class="tasks-scrum__backlog-empty--text">
					${Loc.getMessage('TASKS_SCRUM_BACKLOG_BLANK_4')}
				</div>
			</div>
		`;

		return this.node;
	}

	renderCompletedSprint(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__sprints--completed-empty">
				${Loc.getMessage('TASKS_SCRUM_COMPLETED_SPRINT_EMPTY')}
			</div>
		`;

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	removeNode()
	{
		Dom.remove(this.node);

		this.node = null;
	}
}