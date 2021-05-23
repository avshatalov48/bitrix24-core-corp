import {Event, Dom, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {SidePanel} from '../service/side.panel';

import {View} from './view';

import {Sprint} from '../entity/sprint/sprint';
import {SprintSidePanel} from '../entity/sprint/sprint.side.panel';
import {StatsHeaderBuilder} from '../entity/sprint/stats.header.builder';
import {Item} from '../item/item';

import type {SprintParams} from '../entity/sprint/sprint';
import type {Views} from './view';

type Params = {
	pathToTask: string,
	views: Views,
	activeSprint: SprintParams,
	sprints: Array<SprintParams>
}

export class ActiveSprint extends View
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.ActiveSprint');

		this.setParams(params);

		if (this.existActiveSprint())
		{
			this.finishStatus = this.getActiveSprintParams().finishStatus;

			this.sprint = new Sprint(this.getActiveSprintParams());

			this.itemsInFinishStage = new Map();

			this.initDomNodes();
			this.createSprintStats();
			this.bindHandlers();
		}

		this.sidePanel = new SidePanel();
	}

	setParams(params: Params)
	{
		this.setPathToTask(params.pathToTask);
		this.setActiveSprintParams(params.activeSprint);

		this.sprints = new Map();
		params.sprints.forEach((sprintData) => {
			const sprint = new Sprint(sprintData);
			this.sprints.set(sprint.getId(), sprint);
		});

		this.views = params.views;
	}

	setPathToTask(pathToTask: string)
	{
		this.pathToTask = (Type.isString(pathToTask) ? pathToTask : '');
	}

	getPathToTask(): string
	{
		return this.pathToTask;
	}

	setActiveSprintParams(params: SprintParams)
	{
		this.activeSprint = (Type.isPlainObject(params) ? params : null);
	}

	getActiveSprintParams(): SprintParams
	{
		return this.activeSprint;
	}

	existActiveSprint(): boolean
	{
		return (this.activeSprint !== null);
	}

	initDomNodes()
	{
		this.sprintStatsContainer = document.getElementById('tasks-scrum-active-sprint-stats');
		const buttonsContainer = document.getElementById('tasks-scrum-actions-complete-sprint');
		this.chartSprintButtonNode = buttonsContainer.firstElementChild;
		this.completeSprintButtonNode = buttonsContainer.lastElementChild;
	}

	createSprintStats()
	{
		this.statsHeader = StatsHeaderBuilder.build(this.sprint);
		this.statsHeader.setKanbanStyle();
		Dom.append(this.statsHeader.render(), this.sprintStatsContainer);
	}

	bindHandlers()
	{
		Event.bind(this.chartSprintButtonNode, 'click', this.onShowSprintBurnDownChart.bind(this));
		Event.bind(this.completeSprintButtonNode, 'click', this.onCompleteSprint.bind(this));

		// eslint-disable-next-line
		const kanbanManager = BX.Tasks.Scrum.Kanban;
		if (kanbanManager)
		{
			this.bindKanbanHandlers(kanbanManager.getKanban());
			kanbanManager.getKanbansGroupedByParentTasks().forEach((kanban) => {
				this.bindKanbanHandlers(kanban);
			});
		}
	}

	bindKanbanHandlers(kanban)
	{
		this.onKanbanRender(kanban);

		EventEmitter.subscribe(kanban, 'Kanban.Grid:onItemMoved', (event: BaseEvent) => {
			const [kanbanItem, targetColumn, beforeItem] = event.getCompatData();
			this.onItemMoved(kanbanItem, targetColumn, beforeItem);
		});
	}

	onCompleteSprint()
	{
		const sprintSidePanel = new SprintSidePanel({
			sprints: this.sprints,
			sidePanel: this.sidePanel,
			requestSender: this.requestSender,
			views: this.views
		});
		sprintSidePanel.showCompleteSidePanel(this.sprint);

		this.sprint.subscribe('showTask', (baseEvent) => {
			const item = baseEvent.getData();
			this.sidePanel.openSidePanelByUrl(this.getPathToTask().replace('#task_id#', item.getSourceId()));
		});
	}

	/**
	 * Handles Kanban render.
	 * @param {BX.Tasks.Kanban.Grid} kanbanGrid
	 * @returns {void}
	 */
	onKanbanRender(kanbanGrid)
	{
		const items = kanbanGrid.getItems();
		const hasOwnProperty = Object.prototype.hasOwnProperty;
		for (let itemId in kanbanGrid.getItems())
		{
			if (hasOwnProperty.call(items, itemId))
			{
				const item = items[itemId];
				if (item.getColumn().getType() === this.finishStatus)
				{
					this.itemsInFinishStage.set(itemId, '');
				}
			}
		}
	}

	/**
	 * Hook on item moved.
	 * @param {BX.Tasks.Kanban.Item} kanbanItem
	 * @param {BX.Tasks.Kanban.Column} targetColumn
	 * @param {BX.Tasks.Kanban.Item} [beforeItem]
	 * @returns {void}
	 */
	onItemMoved(kanbanItem, targetColumn, beforeItem)
	{
		if (targetColumn.type === this.finishStatus)
		{
			if (!this.itemsInFinishStage.has(kanbanItem.getId()))
			{
				this.updateStatsAfterMovedToFinish(kanbanItem, this.sprint);
			}
		}
		else
		{
			if (this.itemsInFinishStage.has(kanbanItem.getId()))
			{
				this.updateStatsAfterMovedFromFinish(kanbanItem, this.sprint);
			}
		}

		this.statsHeader.updateStats(this.sprint);
	}

	updateStatsAfterMovedToFinish(kanbanItem, sprint: Sprint)
	{
		this.itemsInFinishStage.set(kanbanItem.getId(), kanbanItem.getStoryPoints());

		sprint.getTotalCompletedStoryPoints().addPoints(kanbanItem.getStoryPoints());
		sprint.getTotalUncompletedStoryPoints().subtractPoints(kanbanItem.getStoryPoints());

		sprint.setCompletedTasks(sprint.getCompletedTasks() + 1);
		sprint.setUncompletedTasks(sprint.getUncompletedTasks() - 1);

		sprint.getItems().forEach((scrumItem: Item) => {
			if (scrumItem.getSourceId() === parseInt(kanbanItem.getId(), 10))
			{
				scrumItem.setCompleted('Y');
			}
		});
	}

	updateStatsAfterMovedFromFinish(kanbanItem, sprint: Sprint)
	{
		this.itemsInFinishStage.delete(kanbanItem.getId());

		sprint.getTotalCompletedStoryPoints().subtractPoints(kanbanItem.getStoryPoints());
		sprint.getTotalUncompletedStoryPoints().addPoints(kanbanItem.getStoryPoints());

		sprint.setCompletedTasks(sprint.getCompletedTasks() - 1);
		sprint.setUncompletedTasks(sprint.getUncompletedTasks() + 1);

		sprint.getItems().forEach((scrumItem: Item) => {
			if (scrumItem.getSourceId() === parseInt(kanbanItem.getId(), 10))
			{
				scrumItem.setCompleted('N');
			}
		});
	}

	onShowSprintBurnDownChart()
	{
		const sprintSidePanel = new SprintSidePanel({
			sidePanel: this.sidePanel,
			requestSender: this.requestSender,
			views: this.views
		});
		sprintSidePanel.showBurnDownChart(this.sprint);
	}
}