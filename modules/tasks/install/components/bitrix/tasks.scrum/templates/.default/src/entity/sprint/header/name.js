import {Dom, Event, Loc, Tag, Text} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {MessageBox} from 'ui.dialogs.messagebox';

import {Sprint} from '../sprint';

import {Date} from './date';
import {Stats} from './stats';

export class Name extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Name');

		this.sprint = sprint;

		this.date = null;
		this.stats = null;

		this.node = null;
	}

	setDate(sprint: Sprint)
	{
		const date = new Date(sprint);

		if (this.date)
		{
			Dom.replace(this.date.getNode(), date.render());
		}

		this.date = date;

		this.date.subscribe('changeSprintDeadline', (baseEvent) => {
			this.emit('changeSprintDeadline', baseEvent.getData());
		});
	}

	getDate(): ?Date
	{
		return this.date;
	}

	setStats(sprint: Sprint)
	{
		const stats = new Stats(sprint);

		if (this.stats)
		{
			Dom.replace(this.stats.getNode(), stats.render());
		}

		this.stats = stats;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__name-container">
				${this.renderEditInput()}
				<div class="tasks-scrum__title">
					${Text.encode(this.sprint.getName())}
				</div>
				${this.renderEdit()}
				${this.renderRemove()}
				${this.date ? this.date.render() : ''}
				${this.stats ? this.stats.render() : ''}
		`;

		const titleNode = this.node.querySelector('.tasks-scrum__title');
		const editNode = this.node.querySelector('.tasks-scrum__sprint--edit');

		Event.bind(titleNode, 'click', () => {
			this.emit('editClick', this.node.querySelector('.tasks-scrum__title-editing-input'))
		});
		Event.bind(editNode, 'click', () => {
			this.emit('editClick', this.node.querySelector('.tasks-scrum__title-editing-input'))
		});

		if (this.sprint.isPlanned())
		{
			const removeNode = this.node.querySelector('.tasks-scrum__sprint--remove');

			Event.bind(removeNode, 'click', () => {
				if (this.sprint.isEmpty())
				{
					this.emit('removeSprint');
				}
				else
				{
					MessageBox.confirm(
						Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_SPRINT'),
						(messageBox) => {
							this.emit('removeSprint');
							messageBox.close();
						},
						Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'),
					);
				}
			});
		}

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	renderEditInput(): HTMLElement
	{
		const uiClasses = 'ui-ctl ui-ctl-sm ui-ctl-textbox ui-ctl-underline ui-ctl-no-padding';

		return Tag.render`
			<div class="tasks-scrum__title-editing ${uiClasses}">
				<input
					type="text"
					class="tasks-scrum__title-editing-input ui-ctl-element"
					value="${Text.encode(this.sprint.getName())}"
				>
			</div>
		`;
	}

	renderEdit(): ?HTMLElement
	{
		if (!this.sprint.isCompleted())
		{
			return Tag.render`<div class="tasks-scrum__sprint--edit"></div>`;
		}
		else
		{
			return '';
		}
	}

	renderRemove(): ?HTMLElement
	{
		if (this.sprint.isPlanned())
		{
			return Tag.render`<div class="tasks-scrum__sprint--remove"></div>`;
		}
		else
		{
			return '';
		}
	}
}
