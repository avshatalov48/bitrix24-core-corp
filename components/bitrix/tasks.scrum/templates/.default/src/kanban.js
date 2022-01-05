import {RequestSender} from './request.sender';
import {Event, Dom, Type} from 'main.core';
import {SprintStats} from './sprint.stats';
import {Sprint} from './sprint';
import {SprintPopup} from './sprint.popup';

export class Kanban
{
	constructor(options)
	{
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

			this.tabs = options.tabs;

			this.itemsInFinishStage = new Map();

			this.initDomNodes();
			this.createSprintStats();
			this.bindHandlers();
		}
	}

	initDomNodes()
	{
		this.sprintStatsContainer = document.getElementById('tasks-scrum-active-sprint-stats');
		this.completeSprintButtonNode = document.getElementById('tasks-scrum-actions-complete-sprint');
	}

	createSprintStats()
	{
		this.sprintStats = new SprintStats(this.sprint);
		this.sprintStats.setKanbanMode();
		Dom.append(this.sprintStats.createStats(), this.sprintStatsContainer);
		this.sprintStats.onAfterAppend();
	}

	bindHandlers()
	{
		Event.bind(this.completeSprintButtonNode, 'click', this.onCompleteSprint.bind(this));

		if (window.Kanban)
		{
			this.onKanbanRender(window.Kanban);
			BX.addCustomEvent(window.Kanban, 'Kanban.Grid:onItemMoved', this.onItemMoved.bind(this));
		}
	}

	onCompleteSprint()
	{
		const sprintPopup = new SprintPopup({
			sprints: this.sprints
		});

		sprintPopup.showCompletePopup(this.sprint).then((requestData) => {
			this.requestSender.completeSprint(requestData).then((response) => {
				location.href = this.tabs['planning'].url;
			}).catch((response) => {});
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
	 * @param {BX.Tasks.Kanban.Item} item
	 * @param {BX.Tasks.Kanban.Column} targetColumn
	 * @param {BX.Tasks.Kanban.Item} [beforeItem]
	 * @returns {void}
	 */
	onItemMoved(item, targetColumn, beforeItem)
	{
		if (targetColumn.type === this.finishStatus)
		{
			if (!this.itemsInFinishStage.has(item.getId()))
			{
				this.updateStatsAfterMovedToFinish(item, this.sprint);
			}
		}
		else
		{
			if (this.itemsInFinishStage.has(item.getId()))
			{
				this.updateStatsAfterMovedFromFinish(item, this.sprint);
			}
		}
	}

	updateStatsAfterMovedToFinish(item, sprint)
	{
		this.itemsInFinishStage.set(item.getId(), item.getStoryPoints());
		this.sprintStats.updateActiveStats(item.getStoryPoints());

		sprint.setCompletedStoryPoints(sprint.getCompletedStoryPoints() + parseFloat(item.getStoryPoints()));
		sprint.setUnCompletedStoryPoints(sprint.getUnCompletedStoryPoints() - parseFloat(item.getStoryPoints()));

		sprint.setCompletedTasks(sprint.getCompletedTasks() + 1);
		sprint.setUnCompletedTasks(sprint.getUnCompletedTasks() - 1);
	}

	updateStatsAfterMovedFromFinish(item, sprint)
	{
		this.itemsInFinishStage.delete(item.getId());
		this.sprintStats.updateActiveStats(item.getStoryPoints(), false);

		sprint.setCompletedStoryPoints(sprint.getCompletedStoryPoints() - parseFloat(item.getStoryPoints()));
		sprint.setUnCompletedStoryPoints(sprint.getUnCompletedStoryPoints() + parseFloat(item.getStoryPoints()));

		sprint.setCompletedTasks(sprint.getCompletedTasks() - 1);
		sprint.setUnCompletedTasks(sprint.getUnCompletedTasks() + 1);
	}
}