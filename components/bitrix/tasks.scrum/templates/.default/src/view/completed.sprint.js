import {Dom, Text} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {SidePanel} from '../service/side.panel';

import {Sprint} from '../entity/sprint/sprint';
import {SprintSidePanel} from '../entity/sprint/sprint.side.panel';

import {View} from './view';
import {BurnDownButton} from './header/burn.down.button';

import type {Views} from './view';
import type {SprintParams} from '../entity/sprint/sprint';

type Params = {
	views: Views,
	completedSprint: SprintParams,
	sprints: Array<SprintParams>
}

export class CompletedSprint extends View
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.CompletedSprint');

		this.setParams(params);

		this.bindHandlers();
	}

	renderSprintStatsTo(container: HTMLElement)
	{
		super.renderSprintStatsTo(container);

		this.titleContainer = container;
		this.titleContainer.textContent = Text.encode(this.completedSprint.getName());
	}

	renderButtonsTo(container: HTMLElement)
	{
		super.renderButtonsTo(container);

		const burnDownButton = new BurnDownButton();
		burnDownButton.subscribe('click', this.onShowSprintBurnDownChart.bind(this));

		Dom.append(burnDownButton.render(), container);
	}

	setParams(params: Params)
	{
		this.completedSprint = new Sprint(params.completedSprint);

		this.sidePanel = new SidePanel();

		this.sprints = new Map();
		params.sprints.forEach((sprintData) => {
			const sprint = Sprint.buildSprint(sprintData);
			this.sprints.set(sprint.getId(), sprint);
		});
		this.views = params.views;
	}

	bindHandlers()
	{
		EventEmitter.subscribe('onTasksGroupSelectorChange', this.onSprintSelectorChange.bind(this));
	}

	onSprintSelectorChange(event: BaseEvent)
	{
		const [currentSprint] = event.getCompatData()

		this.completedSprint = this.findSprintBySprintId(currentSprint.sprintId);

		this.titleContainer.textContent = Text.encode(currentSprint.name);
	}

	onShowSprintBurnDownChart(baseEvent: BaseEvent)
	{
		const sprintSidePanel = new SprintSidePanel({
			sidePanel: this.sidePanel,
			requestSender: this.requestSender,
			views: this.views
		});
		sprintSidePanel.showBurnDownChart(this.completedSprint);
	}

	findSprintBySprintId(sprintId: number): Sprint
	{
		return [...this.sprints.values()].find((sprint) => sprint.getId() === parseInt(sprintId, 10));
	}
}