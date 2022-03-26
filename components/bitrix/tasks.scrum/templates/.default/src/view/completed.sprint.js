import {Dom, Type} from 'main.core';
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
	sprints: Array<SprintParams>,
	pathToBurnDown: string
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

	renderRightElementsTo(container: HTMLElement)
	{
		super.renderRightElementsTo(container);

		if (this.completedSprint === null)
		{
			return;
		}

		const burnDownButton = new BurnDownButton();
		burnDownButton.subscribe('click', this.onShowSprintBurnDownChart.bind(this));

		Dom.addClass(container, '--without-bg');

		Dom.append(burnDownButton.render(), container);
	}

	setParams(params: Params)
	{
		if (Type.isArray(params.completedSprint))
		{
			this.completedSprint = null;
		}
		else
		{
			this.completedSprint = new Sprint(params.completedSprint);
		}

		this.sidePanel = new SidePanel();

		this.sprints = new Map();
		params.sprints.forEach((sprintData) => {
			const sprint = Sprint.buildSprint(sprintData);
			this.sprints.set(sprint.getId(), sprint);
		});
		this.views = params.views;

		this.pathToBurnDown = params.pathToBurnDown;
	}

	bindHandlers()
	{
		EventEmitter.subscribe('onTasksGroupSelectorChange', this.onSprintSelectorChange.bind(this));
	}

	onSprintSelectorChange(event: BaseEvent)
	{
		const [currentSprint] = event.getCompatData()

		this.completedSprint = this.findSprintBySprintId(currentSprint.sprintId);
	}

	onShowSprintBurnDownChart(baseEvent: BaseEvent)
	{
		const sprintSidePanel = new SprintSidePanel({
			groupId: this.groupId,
			sidePanel: this.sidePanel,
			views: this.views,
			pathToBurnDown: this.pathToBurnDown
		});
		sprintSidePanel.showBurnDownChart(this.completedSprint);
	}

	findSprintBySprintId(sprintId: number): Sprint
	{
		return [...this.sprints.values()].find((sprint) => sprint.getId() === parseInt(sprintId, 10));
	}
}