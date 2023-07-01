import {Dom, Event, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Sprint} from '../sprint';

export class Tick extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Tick');

		this.sprint = sprint;

		this.node = null;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__sprint--btn-dropdown ui-btn ui-btn-sm ui-btn-icon-angle-down --up"></div>
		`;

		Event.bind(this.node, 'click', this.onClick.bind(this));

		if (this.sprint.isHideContent())
		{
			Dom.removeClass(this.node, '--up');
		}

		return this.node;
	}

	onClick()
	{
		this.emit('click');
	}

	upTick()
	{
		if (!this.node)
		{
			return;
		}

		Dom.addClass(this.node, '--up');
	}

	downTick()
	{
		if (!this.node)
		{
			return;
		}

		Dom.removeClass(this.node, '--up');
	}
}
