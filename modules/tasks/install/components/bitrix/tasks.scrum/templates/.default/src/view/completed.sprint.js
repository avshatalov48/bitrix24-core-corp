import {Event, Text} from 'main.core';
import {Sprint} from '../entity/sprint/sprint';
import {SprintSidePanel} from '../entity/sprint/sprint.side.panel';
import {SidePanel} from '../service/side.panel';
import {RequestSender} from '../utility/request.sender';

type CurrentSprint = {
	sprintId: number,
	name: string
}

type Params = {
	completedSprint: Sprint,
	signedParameters: string,
	views: {
		plan: {
			name: string,
			url: string,
			active: boolean
		},
		activeSprint: {
			name: string,
			url: string,
			active: boolean
		},
		completedSprint: {
			name: string,
			url: string,
			active: boolean
		}
	},
	sprints: Array
}

export class CompletedSprint
{
	constructor(params: Params)
	{
		this.completedSprint = params.completedSprint;

		this.requestSender = new RequestSender({
			signedParameters: params.signedParameters,
		});
		this.sidePanel = new SidePanel();

		this.sprints = new Map();
		params.sprints.forEach((sprintData) => {
			const sprint = Sprint.buildSprint(sprintData);
			this.sprints.set(sprint.getId(), sprint);
		});
		this.views = params.views;

		this.initDomNodes();
		this.bindHandlers();
		this.createTitle();
	}

	initDomNodes()
	{
		this.chartSprintButtonNode = document.getElementById('tasks-scrum-completed-sprint-chart');
	}

	bindHandlers()
	{
		/* eslint-disable */
		BX.addCustomEvent('onTasksGroupSelectorChange', this.onSprintSelectorChange.bind(this));
		/* eslint-enable */

		Event.bind(this.chartSprintButtonNode, 'click', this.onShowSprintBurnDownChart.bind(this));
	}

	createTitle()
	{
		this.titleContainer = document.getElementById('tasks-scrum-completed-sprint-title');
		this.titleContainer.textContent = Text.encode(this.completedSprint.getName());
	}

	onSprintSelectorChange(currentSprint: CurrentSprint)
	{
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