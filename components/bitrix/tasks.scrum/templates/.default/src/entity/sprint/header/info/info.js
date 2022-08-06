import {Tag} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {Sprint} from '../../sprint';

import {ChartIcon} from './chart.icon';
import {Counters} from './counters';
import {Button} from './button';

export class Info extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Info');

		this.sprint = sprint;

		this.node = null;

		this.chartIcon = new ChartIcon(sprint);
		this.counters = new Counters(sprint);
		this.button = new Button(sprint);
	}

	render(): HTMLElement
	{
		this.node = Tag.render`
			<div class="tasks-scrum__sprint--info">
				${this.chartIcon.render()}
				${this.counters.render()}
				${this.button.render()}
			</div>
		`;

		this.chartIcon.subscribe('click', () => this.emit('showBurnDownChart'));
		this.button.subscribe(
			'click',
			(baseEvent: BaseEvent) => this.emit('showCreateMenu', baseEvent.getTarget())
		);

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