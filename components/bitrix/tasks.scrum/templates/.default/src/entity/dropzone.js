import {Event, Dom, Tag, Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Entity} from './entity';

export class Dropzone extends EventEmitter
{
	constructor(entity: Entity)
	{
		super(entity);

		this.setEventNamespace('BX.Tasks.Scrum.Dropzone');

		this.entity = entity;

		this.mandatoryExists = false;

		this.node = null;
	}

	render(): HTMLElement
	{
		if (this.entity.isBacklog())
		{
			return this.renderBacklog();
		}
		else
		{
			return this.renderSprint();
		}
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

	renderBacklog(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__content-empty --empty-backlog" data-entity-id="${this.entity.getId()}">
				${Loc.getMessage('TASKS_SCRUM_BACKLOG_DROPZONE_1')}
			</div>
		`;

		Event.bind(
			this.node,
			'click',
			() => this.emit('createTask')
		);

		return this.node;
	}

	renderSprint(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__content-empty" data-entity-id="${this.entity.getId()}">
				<div class="tasks-scrum__content-empty--title">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_BLANK_1')}
				</div>
				${this.renderSprintEmptyText()}
			</div>
		`;

		if (!this.mandatoryExists)
		{
			Event.bind(
				this.node.querySelector('.tasks-scrum__content-empty--btn-create'),
				'click',
				() => this.emit('createTask')
			);
		}

		return this.node;
	}

	renderSprintEmptyText(): HTMLElement
	{
		if (this.mandatoryExists)
		{
			return Tag.render`
				<div class="tasks-scrum__content-empty--text">
					${Loc.getMessage('TASKS_SCRUM_SPRINT_BLANK_4')}
				</div>
			`;
		}
		else
		{
			return Tag.render`
				<div class="tasks-scrum__content-empty--text">
					<span class="tasks-scrum__content-empty--btn-create">
						${Loc.getMessage('TASKS_SCRUM_SPRINT_BLANK_2')}
					</span> ${Loc.getMessage('TASKS_SCRUM_SPRINT_BLANK_3')}
				</div>
			`;
		}
	}

	setMandatory()
	{
		this.mandatoryExists = true;
	}
}