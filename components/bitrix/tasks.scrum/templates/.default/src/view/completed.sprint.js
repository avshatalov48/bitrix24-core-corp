import {Event, Text} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {SidePanel} from '../service/side.panel';

import {Sprint} from '../entity/sprint/sprint';
import {SprintSidePanel} from '../entity/sprint/sprint.side.panel';

import {View} from './view';

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

		this.initDomNodes();
		this.bindHandlers();
		this.createTitle();
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

	initDomNodes()
	{
		this.chartSprintButtonNode = document.getElementById('tasks-scrum-completed-sprint-chart');
	}

	bindHandlers()
	{
		EventEmitter.subscribe('onTasksGroupSelectorChange', this.onSprintSelectorChange.bind(this));

		Event.bind(this.chartSprintButtonNode, 'click', this.onShowSprintBurnDownChart.bind(this));
	}

	createTitle()
	{
		this.titleContainer = document.getElementById('tasks-scrum-completed-sprint-title');
		this.titleContainer.textContent = Text.encode(this.completedSprint.getName());
	}

	onSprintSelectorChange(event: BaseEvent)
	{
		const [currentSprint] = event.getCompatData()

		this.completedSprint = this.findSprintBySprintId(currentSprint.sprintId);

		this.titleContainer.textContent = Text.encode(currentSprint.name);
	}

	onShowSprintBurnDownChart()
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