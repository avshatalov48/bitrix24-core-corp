import {Dom, Type} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {SidePanel} from '../service/side.panel';

import {View} from './view';
import {ActiveSprintActionButton} from './header/active.sprint.action.button';
import {RobotButton} from './header/robot.button';

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
	sprints: Array<SprintParams>,
	taskLimitExceeded: 'Y' | 'N',
	canUseAutomation: 'Y' | 'N'
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

			this.bindHandlers();
		}

		this.sidePanel = new SidePanel();
	}

	renderSprintStatsTo(container: HTMLElement)
	{
		super.renderSprintStatsTo(container);

		this.statsHeader = StatsHeaderBuilder.build(this.sprint);
		this.statsHeader.setKanbanStyle();

		Dom.append(this.statsHeader.render(), container);
	}

	renderButtonsTo(container: HTMLElement)
	{
		super.renderButtonsTo(container);

		if (!this.existActiveSprint())
		{
			return;
		}

		const robotButton = new RobotButton({
			sidePanel: this.sidePanel,
			groupId: this.getCurrentGroupId(),
			isTaskLimitsExceeded: this.isTaskLimitsExceeded(),
			canUseAutomation: this.isCanUseAutomation()
		});

		const activeSprintActionButton = new ActiveSprintActionButton();
		activeSprintActionButton.subscribe('completeSprint', this.onCompleteSprint.bind(this));
		activeSprintActionButton.subscribe('showBurnDownChart', this.onShowSprintBurnDownChart.bind(this));

		Dom.append(robotButton.render(), container);
		Dom.append(activeSprintActionButton.render(), container);
	}

	setParams(params: Params)
	{
		this.setPathToTask(params.pathToTask);
		this.setActiveSprintParams(params.activeSprint);
		this.setTaskLimitsExceeded(params.taskLimitExceeded);
		this.setCanUseAutomation(params.canUseAutomation);

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

	setTaskLimitsExceeded(limitExceeded: string)
	{
		this.limitExceeded = (limitExceeded === 'Y');
	}

	isTaskLimitsExceeded(): boolean
	{
		return this.limitExceeded;
	}

	setCanUseAutomation(canUseAutomation: string)
	{
		this.canUseAutomation = (canUseAutomation === 'Y');
	}

	isCanUseAutomation(): boolean
	{
		return this.canUseAutomation;
	}

	existActiveSprint(): boolean
	{
		return (this.activeSprint !== null);
	}

	bindHandlers()
	{
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

	onCompleteSprint(baseEvent: BaseEvent)
	{
		const sprintSidePanel = new SprintSidePanel({
			sprints: this.sprints,
			sidePanel: this.sidePanel,
			requestSender: this.requestSender,
			views: this.views
		});
		sprintSidePanel.showCompleteSidePanel(this.sprint);

		this.sprint.subscribe('showTask', (innerBaseEvent: BaseEvent) => {
			const item = innerBaseEvent.getData();
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

		sprint.getCompletedStoryPoints().addPoints(kanbanItem.getStoryPoints());
		sprint.getUncompletedStoryPoints().subtractPoints(kanbanItem.getStoryPoints());

		sprint.setCompletedTasks(sprint.getCompletedTasks() + 1);
		sprint.setUncompletedTasks(sprint.getUncompletedTasks() - 1);

		sprint.getItems().forEach((scrumItem: Item) => {
			if (scrumItem.getSourceId() === parseInt(kanbanItem.getId(), 10))
			{
				scrumItem.setCompleted('Y');

				if (scrumItem.isSubTask())
				{
					const parentItem = sprint.getItemBySourceId(scrumItem.getParentTaskId());
					if (parentItem)
					{
						const subTasksItems = sprint.getItemsByParentTaskId(parentItem.getSourceId());
						const unCompletedItem = [...subTasksItems.values()].find((item: Item) => !item.isCompleted());
						if (!unCompletedItem)
						{
							parentItem.setCompleted('Y');
						}
					}
				}
			}
		});
	}

	updateStatsAfterMovedFromFinish(kanbanItem, sprint: Sprint)
	{
		this.itemsInFinishStage.delete(kanbanItem.getId());

		sprint.getCompletedStoryPoints().subtractPoints(kanbanItem.getStoryPoints());
		sprint.getUncompletedStoryPoints().addPoints(kanbanItem.getStoryPoints());

		sprint.setCompletedTasks(sprint.getCompletedTasks() - 1);
		sprint.setUncompletedTasks(sprint.getUncompletedTasks() + 1);

		sprint.getItems().forEach((scrumItem: Item) => {
			if (scrumItem.getSourceId() === parseInt(kanbanItem.getId(), 10))
			{
				scrumItem.setCompleted('N');

				if (scrumItem.isSubTask())
				{
					const parentItem = sprint.getItemBySourceId(scrumItem.getParentTaskId());
					if (parentItem)
					{
						const subTasksItems = sprint.getItemsByParentTaskId(parentItem.getSourceId());
						const unCompletedItem = [...subTasksItems.values()].find((item: Item) => !item.isCompleted());
						if (unCompletedItem)
						{
							parentItem.setCompleted('N');
						}
					}
				}
			}
		});
	}

	onShowSprintBurnDownChart(baseEvent: BaseEvent)
	{
		const sprintSidePanel = new SprintSidePanel({
			sidePanel: this.sidePanel,
			requestSender: this.requestSender,
			views: this.views
		});
		sprintSidePanel.showBurnDownChart(this.sprint);
	}
}