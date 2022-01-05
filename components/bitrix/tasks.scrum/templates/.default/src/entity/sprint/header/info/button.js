import {Event, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Sprint} from '../../sprint';

export class Button extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Info.Button');

		this.sprint = sprint;

		this.node = null;
	}

	render(): HTMLElement
	{
		const uiBtnStyles = 'ui-btn ui-btn-xs ui-btn-light ui-btn-round ui-btn-icon-add';

		this.node = Tag.render`
			<button class="tasks-scrum__sprint--btn-add ${uiBtnStyles}"></button>
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
		this.emit('click');
	}
}