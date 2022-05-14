import {Dom} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {SidePanel} from '../service/side.panel';

import {View, Views} from './view';
import {CompleteSprintButton} from './header/complete.sprint.button';
import {RobotButton} from './header/robot.button';

import {SprintSidePanel} from '../entity/sprint/sprint.side.panel';

import {StatsBuilder} from '../entity/sprint/stats/stats.builder';

type Params = {
	activeSprintId: number,
	views: Views,
	taskLimitExceeded: 'Y' | 'N',
	canUseAutomation: 'Y' | 'N',
	canCompleteSprint: 'Y' | 'N'
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
			this.bindHandlers();
		}

		this.sidePanel = new SidePanel();
	}

	renderSprintStatsTo(container: HTMLElement)
	{
		super.renderSprintStatsTo(container);

		// todo ger data for sprint from server
		if (this.sprint)
		{
			this.stats = StatsBuilder.build(this.sprint);
			this.stats.setKanbanStyle();

			Dom.append(this.stats.render(), container);
		}
	}

	renderRightElementsTo(container: HTMLElement)
	{
		super.renderRightElementsTo(container);

		if (!this.existActiveSprint())
		{
			return;
		}

		if (this.canCompleteSprint) // todo change to can robot access
		{
			const robotButton = new RobotButton({
				sidePanel: this.sidePanel,
				groupId: this.getCurrentGroupId(),
				isTaskLimitsExceeded: this.isTaskLimitsExceeded(),
				canUseAutomation: this.isCanUseAutomation()
			});

			Dom.append(robotButton.render(), container);
		}

		const completeSprintButton = new CompleteSprintButton({
			canCompleteSprint: this.canCompleteSprint
		});
		completeSprintButton.subscribe('completeSprint', this.onCompleteSprint.bind(this));

		Dom.append(completeSprintButton.render(), container);

		Dom.addClass(container, '--without-bg');
	}

	setParams(params: Params)
	{
		this.activeSprintId = parseInt(params.activeSprintId, 10);

		this.setTaskLimitsExceeded(params.taskLimitExceeded);
		this.setCanUseAutomation(params.canUseAutomation);

		this.views = params.views;

		this.canCompleteSprint = (params.canCompleteSprint === 'Y');
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
		return this.activeSprintId > 0;
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
		EventEmitter.subscribe(kanban, 'Kanban.Grid:onItemMoved', (event: BaseEvent) => {
			const [kanbanItem, targetColumn, beforeItem] = event.getCompatData();
			this.onItemMoved(kanbanItem, targetColumn, beforeItem);
		});
	}

	onCompleteSprint(baseEvent: BaseEvent)
	{
		const sprintSidePanel = new SprintSidePanel({
			groupId: this.groupId,
			sidePanel: this.sidePanel,
			views: this.views
		});
		sprintSidePanel.showCompletionForm();

		sprintSidePanel.subscribe('showTask', (innerBaseEvent: BaseEvent) => {
			this.sidePanel.openSidePanelByUrl(this.getPathToTask().replace('#task_id#', innerBaseEvent.getData()));
		});
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
		// todo update stats
		//this.stats.setSprintData(this.sprint);
	}
}