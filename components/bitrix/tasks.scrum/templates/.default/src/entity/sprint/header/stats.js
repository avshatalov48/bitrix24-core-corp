import {Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Sprint} from '../sprint';
import {StatsBuilder} from '../stats/stats.builder';

export class Stats extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Stats');

		this.sprint = sprint;

		this.node = null;
	}

	render(): HTMLElement
	{
		const stats = StatsBuilder.build(this.sprint);

		this.node = Tag.render`
			<div class="tasks-scrum__content--event-params">
				${stats.render()}
			</div>
		`;

		return this.node;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}
}