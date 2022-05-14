import {Event, Loc, Tag, Dom} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Sprint} from '../sprint';

export class Button extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Button');

		this.sprint = sprint;

		this.disabled = false;

		this.node = null;
	}

	render(): HTMLElement
	{
		if (this.sprint.isCompleted())
		{
			return this.node;
		}

		const disableUiClass = (this.isAccessDenied() ? 'ui-btn-disabled' : '');

		this.node = Tag.render`
			<div
				class="tasks-scrum__sprint--btn-run ${this.getUiClasses()} ${disableUiClass}"
				title="${this.getButtonText()}"
			>
				<span class="tasks-scrum__sprint--btn-run-text">${this.getButtonText()}</span>
			</div>
		`;

		Event.bind(this.node, 'click', this.onClick.bind(this));

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	onClick()
	{
		if (this.disabled || this.isAccessDenied())
		{
			return;
		}

		this.emit('click');
	}

	getUiClasses(): string
	{
		return 'ui-btn ui-btn-sm ui-btn-primary ui-btn-round ui-btn-no-caps';
	}

	getButtonText(): string
	{
		return Loc.getMessage('TASKS_SCRUM_SPRINT_HEADER_BUTTON_' + this.sprint.getStatus().toUpperCase());
	}

	disable()
	{
		this.disabled = true;

		if (this.node)
		{
			Dom.addClass(this.node, 'ui-btn-disabled');
		}
	}

	unDisable()
	{
		this.disabled = false;

		if (this.node && !this.isAccessDenied())
		{
			Dom.removeClass(this.node, 'ui-btn-disabled');
		}
	}

	isAccessDenied(): boolean
	{
		return (
			this.sprint.isActive() && !this.sprint.canComplete()
			|| this.sprint.isPlanned() && !this.sprint.canStart()
		);
	}
}
