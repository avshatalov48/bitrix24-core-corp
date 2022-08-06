import {Event, Tag, Type} from 'main.core';
import {EventEmitter} from 'main.core.events';

export type TaskCounter = {
	color: string,
	value: number
}

export class Comments extends EventEmitter
{
	constructor(taskCounter?: TaskCounter)
	{
		super(taskCounter);

		this.setEventNamespace('BX.Tasks.Scrum.Item.Comments');

		if (Type.isUndefined(taskCounter) || Type.isNull(taskCounter))
		{
			taskCounter = {
				color: '',
				value: 0
			};
		}

		this.taskCounter = taskCounter;

		this.node = null;
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__item--comment-counter ${this.taskCounter.value ? '--visible' : ''}">
				<div class='ui-counter ${this.taskCounter.color}'>
					<div class='ui-counter-inner'>${parseInt(this.taskCounter.value, 10)}</div>
				</div>
			</div>
		`;

		Event.bind(this.node, 'click', this.onClick.bind(this));

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	getValue(): TaskCounter
	{
		return this.taskCounter;
	}

	onClick()
	{
		this.emit('click');
	}
}