import {Event, Tag} from 'main.core';
import {EventEmitter} from 'main.core.events';

import {Sprint} from '../../sprint';

export class ChartIcon extends EventEmitter
{
	constructor(sprint: Sprint)
	{
		super(sprint);

		this.setEventNamespace('BX.Tasks.Scrum.Sprint.Header.Info.ChartIcon');

		this.sprint = sprint;

		this.node = null;
	}

	render(): HTMLElement
	{
		const uiChartStyles = 'ui-btn ui-btn-xs ui-btn-light ui-btn-round';

		this.node = Tag.render`
			<div class="tasks-scrum__sprint--btn-burn-down-chart">
				<div class="tasks-scrum__sprint--icon-burn-down-chart ${uiChartStyles}"></div>
			</div>
		`;

		Event.bind(this.node, 'click', this.onClick.bind(this));

		return this.node;
	}

	onClick()
	{
		this.emit('click');
	}
}