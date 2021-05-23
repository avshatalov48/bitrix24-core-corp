import {Loc, Dom, Tag, Type, Text, Event} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {KanbanComponent} from './kanban.component';
import type {AjaxComponentParams} from './kanban.component';

import './css/base.css';

type Column = {
	id: number,
	name: string,
	sort: number,
	color: string,
	total: number,
	type: string
}

type Item = {
	id: number,
	parentId: number,
	columnId: number
}

type ParentTask = {
	id: number,
	name: string,
	completed: 'Y' | 'N',
	storyPoints: string,
	parentId: number,
	columnId: number,
	columns: Array<Column>,
	items: Array<Item>
}

type Params = {
	signedParameters: string,
	filterId: string,
	defaultPresetId: string,
	ajaxComponentPath: string,
	ajaxComponentParams: AjaxComponentParams,
	siteTemplateId: string,
	sprintSelected: boolean,
	parentTasks: {
		[id: number]: ParentTask
	}
}

type KanbanParams = {
	columns: Object,
	items: Object,
	pathToTask: string,
	pathToTaskCreate: string,
	pathToUser: string,
	addItemInSlider: boolean,
	newTaskOrder: string,
	setClientDate: boolean,
	admins: Object,
	ownerId: number,
	groupId: number,
	parentTaskId?: number,
	parentTaskCompleted?: boolean
}

type ApplyFilterResponse = {
	columns: Object,
	items: Object,
	parentTasks: {
		[id: number]: ParentTask
	}
}

export class KanbanManager
{
	constructor(params: Params)
	{
		this.filterId = params.filterId;
		this.siteTemplateId = params.siteTemplateId;
		this.ajaxComponentPath = params.ajaxComponentPath;
		this.ajaxComponentParams = params.ajaxComponentParams;
		this.sprintSelected = params.sprintSelected;

		this.kanban = null;
		this.kanbanGroupedByParentTasks = new Map();

		this.parentTasks = params.parentTasks;

		this.kanbanComponent = new KanbanComponent({
			filterId: params.filterId,
			defaultPresetId: params.defaultPresetId,
			ajaxComponentPath: params.ajaxComponentPath,
			ajaxComponentParams: params.ajaxComponentParams
		});

		EventEmitter.subscribe('BX.Main.Filter:apply', (event: BaseEvent) => {
			const [filterId, values, filterInstance, promise, params] = event.getCompatData();
			this.onApplyFilter(filterId, values, filterInstance, promise, params);
		});
	}

	drawKanban(renderTo: HTMLElement, params: KanbanParams)
	{
		if (!this.sprintSelected)
		{
			this.showNotSprintMessage(renderTo);
			return;
		}

		this.inputRenderTo = renderTo;
		this.inputKanbanParams = params;

		this.drawKanbanWithoutGrouping(renderTo, params);
		this.drawKanbanInGroupingMode(renderTo, params);
		this.fillNeighborKanbans();

		this.adjustGroupHeadersWidth();
		EventEmitter.subscribe(this.kanban, 'Kanban.Grid:onColumnAddedAsync', () => {
			this.adjustGroupHeadersWidth();
		});
		EventEmitter.subscribe(this.kanban, 'Kanban.Grid:onColumnRemovedAsync', () => {
			setTimeout(() => {
				this.adjustGroupHeadersWidth();
			}, 200);
		});
	}

	getKanban()
	{
		return this.kanban;
	}

	getKanbansGroupedByParentTasks(): Map
	{
		return this.kanbanGroupedByParentTasks;
	}

	drawKanbanInGroupingMode(renderTo: HTMLElement, params: KanbanParams)
	{
		Dom.addClass(renderTo, 'tasks-scrum-kanban');

		if (this.isParentTaskGrouping())
		{
			for (const parentTaskId in this.parentTasks)
			{
				const kanbanNode = this.createParentTaskKanbanNode(this.parentTasks[parentTaskId]);
				const parentTaskCompleted = (this.parentTasks[parentTaskId]['completed'] === 'Y');
				if (parentTaskCompleted)
				{
					this.setTextDecorationToParentTaskName(kanbanNode);
				}
				Dom.append(kanbanNode, renderTo);

				const tickButtonNode = kanbanNode.querySelector('.tasks-scrum-kanban-group-header-tick');
				Event.bind(tickButtonNode, 'click', () => {
					this.toggleGroupingVisibility(kanbanNode);
				});

				const container = kanbanNode.querySelector('.tasks-scrum-kanban-container');
				this.drawKanbanGroupedByParentTasks(parentTaskId, container, params);
			}
		}
	}

	drawKanbanWithoutGrouping(renderTo: HTMLElement, params: KanbanParams)
	{
		this.kanban = new BX.Tasks.Kanban.Grid(this.getKanbanParams(renderTo, params));

		this.kanban.draw();
	}

	drawKanbanGroupedByParentTasks(parentTaskId: number, renderTo: HTMLElement, params: KanbanParams)
	{
		parentTaskId = parseInt(parentTaskId, 10);

		params.columns = this.parentTasks[parentTaskId]['columns'];
		params.items = this.parentTasks[parentTaskId]['items'];
		params.parentTaskId = parentTaskId;
		params.parentTaskCompleted = (this.parentTasks[parentTaskId]['completed'] === 'Y');

		const kanban = new BX.Tasks.Kanban.Grid(this.getKanbanParams(renderTo, params));

		kanban.draw();

		if (params.parentTaskCompleted)
		{
			const container = kanban.getRenderToContainer().closest('.tasks-scrum-parent-task-kanban');
			this.downGroupingVisibility(container);
		}

		EventEmitter.subscribe(kanban, 'Kanban.Grid:onCompleteParentTask', () => {
			this.onCompleteParentTask(kanban);
		});

		EventEmitter.subscribe(kanban, 'Kanban.Grid:onRenewParentTask', () => {
			this.onRenewParentTask(kanban);
		});

		this.kanbanGroupedByParentTasks.set(parentTaskId, kanban);
	}

	fillNeighborKanbans()
	{
		this.addNeighborKanban(this.kanban);

		this.kanbanGroupedByParentTasks.forEach((parentTaskKanban) => {
			this.addNeighborKanban(parentTaskKanban);
		});
	}

	addNeighborKanban(kanban)
	{
		this.kanban.addNeighborGrid(kanban);

		this.kanbanGroupedByParentTasks.forEach((parentTaskKanban) => {
			parentTaskKanban.addNeighborGrid(kanban);
		});
	}

	getKanbanParams(renderTo: HTMLElement, params: KanbanParams): Object
	{
		return {
			isGroupingMode: true,
			parentTaskId: (params.parentTaskId ? params.parentTaskId : 0),
			parentTaskCompleted: (params.parentTaskCompleted ? params.parentTaskCompleted : false),
			renderTo: renderTo,
			itemType: 'BX.Tasks.Kanban.Item',
			columnType: 'BX.Tasks.Kanban.Column',
			canAddColumn: true,
			canEditColumn: true,
			canRemoveColumn: true,
			canSortColumn: true,
			canAddItem: true,
			canSortItem: true,
			bgColor: this.siteTemplateId,
			columns: params.columns,
			items: params.items,
			data: {
				kanbanType: 'K',
				ajaxHandlerPath: this.ajaxComponentPath,
				pathToTask: params.pathToTask,
				pathToTaskCreate: params.pathToTaskCreate,
				pathToUser: params.pathToUser,
				addItemInSlider: params.addItemInSlider,
				params: this.ajaxComponentParams,
				gridId: this.filterId,
				newTaskOrder: params.newTaskOrder,
				setClientDate: params.setClientDate,
				clientDate: BX.date.format(BX.date.convertBitrixFormat(Loc.getMessage('FORMAT_DATE'))),
				clientTime: BX.date.format(BX.date.convertBitrixFormat(Loc.getMessage('FORMAT_DATETIME'))),
				rights: {
					canAddColumn: true,
					canEditColumn: true,
					canRemoveColumn: true,
					canSortColumn: true,
					canAddItem: true,
					canSortItem: true
				},
				admins: params.admins
			},
			messages: {
				ITEM_TITLE_PLACEHOLDER: Loc.getMessage('KANBAN_ITEM_TITLE_PLACEHOLDER'),
				COLUMN_TITLE_PLACEHOLDER: Loc.getMessage('KANBAN_COLUMN_TITLE_PLACEHOLDER')
			},
			ownerId: params.ownerId,
			groupId: params.groupId,
			isSprintView: 'Y'
		};
	}

	onClickGroup(item: HTMLElement, mode: string)
	{
		this.kanbanComponent.onClickGroup(item, mode);
	}

	onClickSort(item: HTMLElement, order?: string)
	{
		this.kanbanComponent.onClickSort(item, order);
	}

	onApplyFilter(filterId, values, filterInstance, promise, params)
	{
		this.fadeOutKanbans();

		this.kanban.ajax({
			action: 'applyFilter'
		}, (data: ApplyFilterResponse) => {
			this.refreshKanban(this.kanban, data);
			if (this.existsTasksGroupedBySubTasks(data))
			{
				this.refreshParentTasksKanbans(data);
			}
			else
			{
				this.kanbanGroupedByParentTasks.forEach((parentTaskKanban) => {
					this.hideParentTaskKanban(parentTaskKanban);
				});
			}
			this.adjustGroupHeadersWidth();
			this.fadeInKanbans();
		}, (error) => {
			this.fadeInKanbans();
		});
	}

	refreshKanban(kanban, data)
	{
		kanban.removeItems();
		kanban.loadData(data);
	}

	refreshParentTasksKanbans(data: ApplyFilterResponse)
	{
		const parentTasksToRefresh = [];
		const parentTasksToCreate = [];

		Object.entries(data.parentTasks).forEach((parentTask) => {
			const [parentTaskId, parentTaskData] = parentTask;
			if (this.kanbanGroupedByParentTasks.has(parseInt(parentTaskId, 10)))
			{
				parentTasksToRefresh.push(parentTaskData);
			}
			else
			{
				parentTasksToCreate.push(parentTaskData);
			}
		});

		this.kanbanGroupedByParentTasks.forEach((parentTaskKanban, parentTaskId) => {
			if (!data.parentTasks[parentTaskId])
			{
				this.hideParentTaskKanban(parentTaskKanban);
			}
		});

		parentTasksToRefresh.forEach((parentTaskData: ParentTask) => {
			const parentTaskKanban = this.kanbanGroupedByParentTasks.get(parseInt(parentTaskData.id, 10));
			this.refreshParentTaskKanban(parentTaskKanban, parentTaskData);
		});

		parentTasksToCreate.forEach((parentTaskData: ParentTask) => {
			this.createParentTaskKanban(parentTaskData);
		});
	}

	refreshParentTaskKanban(kanban, data: ParentTask)
	{
		kanban.removeItems();

		const container = kanban.getInnerContainer().closest('.tasks-scrum-parent-task-kanban');
		this.showElement(container);
		this.upGroupingVisibility(container);

		kanban.loadData(data);

		if (data['completed'] === 'Y')
		{
			this.downGroupingVisibility(container);
		}
	}

	createParentTaskKanban(parentTaskData: ParentTask)
	{
		this.parentTasks[parentTaskData.id] = parentTaskData;

		const kanbanNode = this.createParentTaskKanbanNode(parentTaskData);
		const parentTaskCompleted = (parentTaskData['completed'] === 'Y');
		if (parentTaskCompleted)
		{
			this.setTextDecorationToParentTaskName(kanbanNode);
		}
		Dom.append(kanbanNode, this.inputRenderTo);

		const tickButtonNode = kanbanNode.querySelector('.tasks-scrum-kanban-group-header-tick');
		Event.bind(tickButtonNode, 'click', () => {
			this.toggleGroupingVisibility(kanbanNode);
		});

		const container = kanbanNode.querySelector('.tasks-scrum-kanban-container');
		this.drawKanbanGroupedByParentTasks(parentTaskData.id, container, this.inputKanbanParams);
	}

	hideParentTaskKanban(kanban)
	{
		kanban.removeItems();

		const container = kanban.getInnerContainer().closest('.tasks-scrum-parent-task-kanban');

		this.hideElement(container);
	}

	fadeOutKanbans()
	{
		this.kanban.fadeOut();

		this.kanbanGroupedByParentTasks.forEach((parentTaskKanban) => {
			parentTaskKanban.fadeOut();
		});
	}

	fadeInKanbans()
	{
		this.kanban.fadeIn();

		this.kanbanGroupedByParentTasks.forEach((parentTaskKanban) => {
			parentTaskKanban.fadeIn();
		});
	}

	showNotSprintMessage(renderTo: HTMLElement)
	{
		Dom.append(Tag.render`
			<div class="tasks-kanban-start">
				<div class="tasks-kanban-start-title-sm">
					${Loc.getMessage('KANBAN_NO_ACTIVE_SPRINT')}
				</div>
			</div>
		`, renderTo);
	}

	isParentTaskGrouping(): boolean
	{
		return (!Type.isArray(this.parentTasks));
	}

	existsTasksGroupedBySubTasks(response: ApplyFilterResponse): boolean
	{
		return (!Type.isArray(response.parentTasks));
	}

	showElement(element: HTMLElement)
	{
		element.style.display = 'block';
	}

	hideElement(element: HTMLElement)
	{
		element.style.display = 'none';
	}

	createParentTaskKanbanNode(parentTaskData: ParentTask): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-parent-task-kanban">
				<div class="tasks-scrum-kanban-group-header" style="background-color: #eaeaea;">
					<div class="tasks-scrum-kanban-group-header-tick">
						<div class="ui-btn ui-btn-sm ui-btn-light ui-btn-icon-angle-up"></div>
					</div>
					<div class="tasks-scrum-kanban-group-header-name">
						<a href="${this.getTaskUrl(parentTaskData.id)}">${Text.encode(parentTaskData.name)}</a>
					</div>
					<div class="tasks-scrum-kanban-group-header-story-points" title="Story Points">
						${Text.encode(parentTaskData.storyPoints)}
					</div>
				</div>
				<div class="tasks-scrum-kanban-container"></div>
			</div>
		`;
	}

	adjustGroupHeadersWidth()
	{
		const groupHeaders = this.inputRenderTo.querySelectorAll('.tasks-scrum-kanban-group-header');
		groupHeaders.forEach((groupHeader) => {
			groupHeader.style.width = this.kanban.getColumnsWidth();
		});
	}

	upGroupingVisibility(baseContainer: HTMLElement)
	{
		const tickButtonNode = baseContainer.querySelector('.tasks-scrum-kanban-group-header-tick');
		const container = baseContainer.querySelector('.tasks-scrum-kanban-container');

		Dom.removeClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-down');
		Dom.addClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-up');

		this.showElement(container);
	}

	downGroupingVisibility(baseContainer: HTMLElement)
	{
		const tickButtonNode = baseContainer.querySelector('.tasks-scrum-kanban-group-header-tick');
		const container = baseContainer.querySelector('.tasks-scrum-kanban-container');

		Dom.removeClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-up');
		Dom.addClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-down');

		this.hideElement(container);
	}

	toggleGroupingVisibility(baseContainer: HTMLElement)
	{
		const tickButtonNode = baseContainer.querySelector('.tasks-scrum-kanban-group-header-tick');
		const container = baseContainer.querySelector('.tasks-scrum-kanban-container');

		tickButtonNode.firstElementChild.classList.toggle('ui-btn-icon-angle-up');
		tickButtonNode.firstElementChild.classList.toggle('ui-btn-icon-angle-down');

		if (container.style.display !== 'none')
		{
			this.hideElement(container);
		}
		else
		{
			this.showElement(container);
		}

		const gridContainer = baseContainer.querySelector('.main-kanban-grid');
		gridContainer.scrollLeft = this.kanban.getGridContainer().scrollLeft;
	}

	onCompleteParentTask(kanban)
	{
		const container = kanban.getRenderToContainer().closest('.tasks-scrum-parent-task-kanban');

		this.setTextDecorationToParentTaskName(container);
	}

	onRenewParentTask(kanban)
	{
		const container = kanban.getRenderToContainer().closest('.tasks-scrum-parent-task-kanban');

		this.unsetTextDecorationToParentTaskName(container);
	}

	setTextDecorationToParentTaskName(kanbanNode: HTMLElement)
	{
		const parentTaskNameNode = kanbanNode.querySelector('.tasks-scrum-kanban-group-header-name');
		Dom.style(parentTaskNameNode, 'text-decoration', 'line-through');
	}

	unsetTextDecorationToParentTaskName(kanbanNode: HTMLElement)
	{
		const parentTaskNameNode = kanbanNode.querySelector('.tasks-scrum-kanban-group-header-name');
		Dom.style(parentTaskNameNode, 'text-decoration', null);
	}

	getTaskUrl(taskId: number)
	{
		return this.inputKanbanParams.pathToTask.replace('#task_id#', taskId);
	}
}