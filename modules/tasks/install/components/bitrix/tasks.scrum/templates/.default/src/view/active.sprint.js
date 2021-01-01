import {RequestSender} from '../utility/request.sender';
import {Event, Dom, Type} from 'main.core';
import {Sprint} from '../entity/sprint/sprint';
import {SprintSidePanel} from '../entity/sprint/sprint.side.panel';
import {SidePanel} from '../service/side.panel';
import {Item} from '../item/item';
import {StatsHeaderBuilder} from '../entity/sprint/stats.header.builder';

export class ActiveSprint
{
	constructor(options)
	{
		this.pathToTask = options.pathToTask;

		this.activeSprintData = (Type.isPlainObject(options.activeSprintData) ? options.activeSprintData : null);
		if (this.activeSprintData)
		{
			this.requestSender = new RequestSender({
				signedParameters: options.signedParameters,
			});

			this.finishStatus = this.activeSprintData.finishStatus;

			this.sprint = new Sprint(this.activeSprintData);

			this.sprints = new Map();
			options.sprints.forEach((sprintData) => {
				const sprint = new Sprint(sprintData);
				this.sprints.set(sprint.getId(), sprint);
			});

			this.views = options.views;

			this.itemsInFinishStage = new Map();

			this.initDomNodes();
			this.createSprintStats();
			this.bindHandlers();
		}

		this.sidePanel = new SidePanel();
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

		if (window.Kanban)
		{
			this.onKanbanRender(window.Kanban);
			BX.addCustomEvent(window.Kanban, 'Kanban.Grid:onItemMoved', this.onItemMoved.bind(this));
		}
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
			this.sidePanel.openSidePanelByUrl(this.pathToTask.replace('#task_id#', item.getSourceId()));
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
		sprint.setUnCompletedTasks(sprint.getUnCompletedTasks() - 1);

		sprint.getItems().forEach((scrumItem: Item) => {
			if (scrumItem.getSourceId() === parseInt(kanbanItem.getId(), 10))
			{
				scrumItem.setCompleted(true);
			}
		});
	}

	updateStatsAfterMovedFromFinish(kanbanItem, sprint: Sprint)
	{
		this.itemsInFinishStage.delete(kanbanItem.getId());

		sprint.getTotalCompletedStoryPoints().subtractPoints(kanbanItem.getStoryPoints());
		sprint.getTotalUncompletedStoryPoints().addPoints(kanbanItem.getStoryPoints());

		sprint.setCompletedTasks(sprint.getCompletedTasks() - 1);
		sprint.setUnCompletedTasks(sprint.getUnCompletedTasks() + 1);

		sprint.getItems().forEach((scrumItem: Item) => {
			if (scrumItem.getSourceId() === parseInt(kanbanItem.getId(), 10))
			{
				scrumItem.setCompleted(false);
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