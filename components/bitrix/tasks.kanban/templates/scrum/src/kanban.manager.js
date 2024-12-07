import {Loc, Dom, Tag, Type, Text, Event, Runtime} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';

import {PULL as Pull} from 'pull.client';

import {PullItem, UpdateParams} from './pull.item';

import {KanbanComponent, AjaxComponentParams} from './kanban.component';

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
	isVisibilitySubtasks: 'Y' | 'N',
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
	isActiveSprint: boolean,
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
	kanbanHeader: boolean,
	parentTaskId?: number,
	parentTaskName?: string,
	parentTaskCompleted?: boolean
}

type Response = {
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
		this.groupId = parseInt(params.groupId, 10);
		this.filterId = params.filterId;
		this.siteTemplateId = params.siteTemplateId;
		this.ajaxComponentPath = params.ajaxComponentPath;
		this.ajaxComponentParams = params.ajaxComponentParams;
		this.sprintSelected = params.sprintSelected;
		this.isActiveSprint = params.isActiveSprint;

		this.kanbanHeader = null;
		this.kanban = null;
		this.kanbanGroupedByParentTasks = new Map();

		this.parentTasks = params.parentTasks;

		this.kanbanComponent = new KanbanComponent({
			filterId: params.filterId,
			defaultPresetId: params.defaultPresetId,
			ajaxComponentPath: params.ajaxComponentPath,
			ajaxComponentParams: params.ajaxComponentParams
		});

		this.pullItem = new PullItem({
			groupId: this.groupId
		});
		this.pullItem.subscribe('itemUpdated', this.onItemUpdated.bind(this));

		EventEmitter.subscribe('BX.Main.Filter:apply', (event: BaseEvent) => {
			const [filterId, values, filterInstance, promise, params] = event.getCompatData();
			this.onApplyFilter(filterId, values, filterInstance, promise, params);
		});

		EventEmitter.subscribe('onTasksGroupSelectorChange', this.onChangeSprint.bind(this));

		Pull.subscribe(this.pullItem);
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

		this.drawKanbanHeader(renderTo, params);
		this.drawKanbanWithoutGrouping(renderTo, params);
		this.drawKanbanInGroupingMode(renderTo, params);
		this.fillNeighborKanbans();
		this.updateHeaderColumns();

		this.adjustGroupHeadersWidth();
		EventEmitter.subscribe(this.kanbanHeader, 'Kanban.Grid:onColumnAddedAsync', () => {
			this.adjustGroupHeadersWidth();
		});
		EventEmitter.subscribe(this.kanbanHeader, 'Kanban.Grid:onColumnRemovedAsync', () => {
			setTimeout(() => {
				this.adjustGroupHeadersWidth();
			}, 200);
		});
	}

	getKanbanHeader()
	{
		return this.kanbanHeader;
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
				if (this.parentTasks[parentTaskId]['isVisibilitySubtasks'] === 'N')
				{
					delete this.parentTasks[parentTaskId];

					if (!this.kanban.getItem(parentTaskId))
					{
						this.kanban.refreshTask(parentTaskId);
					}

					continue;
				}

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
				this.drawKanbanGroupedByParentTasks(this.parentTasks[parentTaskId], container, params);
			}
		}
	}

	drawKanbanHeader(renderTo: HTMLElement, params: KanbanParams)
	{
		const headerParams = Runtime.clone(params);

		headerParams.kanbanHeader = true;
		headerParams.items = [];

		this.kanbanHeader = new BX.Tasks.Kanban.Grid(this.getKanbanParams(renderTo, headerParams));

		this.kanbanHeader.draw();
	}

	drawKanbanWithoutGrouping(renderTo: HTMLElement, params: KanbanParams)
	{
		this.kanban = new BX.Tasks.Kanban.Grid(this.getKanbanParams(renderTo, params));

		EventEmitter.subscribe(
			this.kanban,
			'Kanban.Grid:onAddParentTask',
			(baseEvent: BaseEvent) => {
				const parentTask: ParentTask = baseEvent.getData();
				this.createParentTaskKanban(parentTask);
				this.fillNeighborKanbans();
				this.adjustGroupHeadersWidth();
				this.kanban.addItemsFromQueue();
			}
		);

		this.kanban.draw();
	}

	drawKanbanGroupedByParentTasks(parentTask: ParentTask, renderTo: HTMLElement, params: KanbanParams)
	{
		const parentTaskId = parseInt(parentTask.id, 10);

		const headerParams = Runtime.clone(params);

		headerParams.columns = parentTask.columns;
		headerParams.items = parentTask.items;
		headerParams.parentTaskId = parentTaskId;
		headerParams.parentTaskName = parentTask.name;
		headerParams.parentTaskCompleted = (parentTask.completed === 'Y');

		const kanban = new BX.Tasks.Kanban.Grid(this.getKanbanParams(renderTo, headerParams));

		kanban.draw();

		if (headerParams.parentTaskCompleted && !kanban.hasItemInProgress())
		{
			const container = kanban.getRenderToContainer().closest('.tasks-scrum-parent-task-kanban');
			this.downGroupingVisibility(container);
		}

		EventEmitter.subscribe(
			kanban,
			'Kanban.Grid:onCompleteParentTask',
			() => {
				this.onCompleteParentTask(kanban);
			}
		);

		EventEmitter.subscribe(
			kanban,
			'Kanban.Grid:onRenewParentTask',
			() => {
				this.onRenewParentTask(kanban);
			}
		);

		EventEmitter.subscribe(
			kanban,
			'Kanban.Grid:onProceedParentTask',
			() => {
				this.onProceedParentTask(kanban);
			}
		);

		EventEmitter.subscribe(
			kanban,
			'Kanban.Grid:onAddItemInProgress',
			(baseEvent: BaseEvent) => {
				const container = kanban.getRenderToContainer().closest('.tasks-scrum-parent-task-kanban');
				this.upGroupingVisibility(container);
			}
		);

		this.kanbanGroupedByParentTasks.set(parentTaskId, kanban);
	}

	fillNeighborKanbans()
	{
		this.addNeighborKanban(this.kanbanHeader);

		this.addNeighborKanban(this.kanban);

		this.kanbanGroupedByParentTasks.forEach((parentTaskKanban) => {
			this.addNeighborKanban(parentTaskKanban);
		});
	}

	cleanNeighborKanbans()
	{
		this.kanbanHeader.cleanNeighborGrids();

		this.kanban.cleanNeighborGrids();

		this.kanbanGroupedByParentTasks
			.forEach((parentTaskKanban) => {
				this.removeParentTaskKanban(parentTaskKanban);
				parentTaskKanban.cleanNeighborGrids();
			})
		;

		this.kanbanGroupedByParentTasks.clear();
	}

	updateHeaderColumns()
	{
		this.kanbanHeader.updateTotals();
	}

	addNeighborKanban(kanban)
	{
		this.kanbanHeader.addNeighborGrid(kanban);

		this.kanban.addNeighborGrid(kanban);

		this.kanbanGroupedByParentTasks.forEach((parentTaskKanban) => {
			parentTaskKanban.addNeighborGrid(kanban);
		});
	}

	getKanbanParams(renderTo: HTMLElement, params: KanbanParams): Object
	{
		return {
			isGroupingMode: true,
			gridHeader: params.kanbanHeader,
			parentTaskId: (params.parentTaskId ? params.parentTaskId : 0),
			parentTaskName: (params.parentTaskName ? params.parentTaskName : ''),
			parentTaskCompleted: (params.parentTaskCompleted ? params.parentTaskCompleted : false),
			renderTo: renderTo,
			itemType: 'BX.Tasks.Kanban.Item',
			columnType: 'BX.Tasks.Kanban.Column',
			canAddColumn: this.isActiveSprint,
			canEditColumn: this.isActiveSprint,
			canRemoveColumn: this.isActiveSprint,
			canSortColumn: this.isActiveSprint,
			canAddItem: (!params.addItemInSlider && this.isActiveSprint),
			canSortItem: this.isActiveSprint,
			bgColor: this.siteTemplateId,
			columns: params.columns,
			items: params.items,
			addItemTitleText: Loc.getMessage('KANBAN_QUICK_TASK'),
			addDraftItemInfo: Loc.getMessage('KANBAN_QUICK_TASK_ITEM_INFO'),
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
					canAddColumn: this.isActiveSprint,
					canEditColumn: this.isActiveSprint,
					canRemoveColumn: this.isActiveSprint,
					canSortColumn: this.isActiveSprint,
					canAddItem: this.isActiveSprint,
					canSortItem: this.isActiveSprint
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

	onClickSort(item: HTMLElement, order?: string)
	{
		this.kanbanComponent.onClickSort(item, order);
	}

	onApplyFilter(filterId, values, filterInstance, promise, params)
	{
		this.fadeOutKanbans();

		this.kanban.ajax('applyFilter', {}).then(
			(response) => {
				const data = response.data;

				if (this.kanban.differentColumnCount(data))
				{
					this.kanbanHeader.getColumns().forEach((column) => this.kanbanHeader.removeColumn(column));
					this.kanban.getColumns().forEach((column) => this.kanban.removeColumn(column));
					this.refreshKanban(this.kanbanHeader, data);
				}

				this.refreshKanban(this.kanban, data);
				this.cleanNeighborKanbans();
				if (this.existsTasksGroupedBySubTasks(data))
				{
					Object.entries(data.parentTasks)
						.forEach((parentTask) => {
							const [, parentTaskData] = parentTask;
							this.createParentTaskKanban(parentTaskData);
						})
					;
				}
				this.fillNeighborKanbans();
				this.adjustGroupHeadersWidth();
				this.fadeInKanbans();
			},
			(response) => {
				this.fadeInKanbans();
			});
	}

	onChangeSprint(baseEvent: BaseEvent)
	{
		const [currentGroup] = baseEvent.getCompatData();

		const gridData = this.kanban.getData();
		gridData.params.SPRINT_ID = currentGroup.sprintId;

		this.kanban.setData(gridData);

		this.kanban.ajax('changeSprint', {}).then(
			(response) => {
				const data = response.data;
				this.cleanNeighborKanbans();
				this.kanbanHeader.getColumns().forEach((column) => this.kanbanHeader.removeColumn(column));
				this.kanban.getColumns().forEach((column) => this.kanban.removeColumn(column));

				this.refreshKanban(this.kanbanHeader, data);
				this.refreshKanban(this.kanban, data);

				if (this.existsTasksGroupedBySubTasks(data))
				{
					Object.entries(data.parentTasks)
						.forEach((parentTask) => {
							const [, parentTaskData] = parentTask;
							this.createParentTaskKanban(parentTaskData);
						})
					;
				}
				this.fillNeighborKanbans();
				this.adjustGroupHeadersWidth();
			}, (error) => {}
		);
	}

	onItemUpdated(baseEvent: BaseEvent)
	{
		const data: UpdateParams = baseEvent.getData();

		if (this.groupId !== data.groupId)
		{
			return;
		}

		const taskId = data.sourceId;

		if (this.kanban.hasItem(taskId))
		{
			this.kanban.refreshTask(taskId);
		}
		else
		{
			this.kanbanGroupedByParentTasks.forEach(
				(parentTaskKanban) => {
					if (parentTaskKanban.hasItem(taskId))
					{
						parentTaskKanban.refreshTask(taskId);
					}
				}
			);
		}
	}

	refreshKanban(kanban, data)
	{
		kanban.resetPaginationPage();
		kanban.removeItems();
		kanban.loadData(data);
	}

	refreshParentTasksKanbans(data: Response)
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
		kanban.resetPaginationPage();
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
		if (parentTaskData.isVisibilitySubtasks === 'N')
		{
			if (!this.kanban.getItem(parentTaskData.id))
			{
				this.kanban.refreshTask(parentTaskData.id);
			}

			return;
		}

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
		this.drawKanbanGroupedByParentTasks(parentTaskData, container, this.inputKanbanParams);
	}

	hideParentTaskKanban(kanban)
	{
		kanban.removeItems();

		const container = kanban.getInnerContainer().closest('.tasks-scrum-parent-task-kanban');

		this.hideElement(container);
	}

	removeParentTaskKanban(kanban)
	{
		Dom.remove(kanban.getInnerContainer().closest('.tasks-scrum-parent-task-kanban'));
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
		const message = this.isActiveSprint
			? Loc.getMessage('KANBAN_NO_ACTIVE_SPRINT')
			: Loc.getMessage('KANBAN_NO_COMPLETED_SPRINT')
		;

		Dom.append(Tag.render`
			<div class="tasks-kanban__start">
					${message}
			</div>
		`, renderTo);
	}

	isParentTaskGrouping(): boolean
	{
		return (!Type.isArray(this.parentTasks));
	}

	existsTasksGroupedBySubTasks(response: Response): boolean
	{
		return (!Type.isArray(response.parentTasks));
	}

	showElement(element: HTMLElement)
	{
		Dom.style(element, 'display', 'block');
	}

	hideElement(element: HTMLElement)
	{
		Dom.style(element, 'display', 'none');
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
			Dom.style(groupHeader, 'width', this.kanbanHeader.getColumnsWidth());
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

		Dom.toggleClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-up');
		Dom.toggleClass(tickButtonNode.firstElementChild, 'ui-btn-icon-angle-down');

		if (Dom.style(container, 'display') !== 'none')
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

	onProceedParentTask(kanban)
	{
		if (this.kanbanGroupedByParentTasks.has(kanban.getParentTaskId()))
		{
			this.removeParentTaskKanban(kanban);

			this.kanbanGroupedByParentTasks.delete(kanban.getParentTaskId());
		}
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

	getTaskUrl(taskId: number): string
	{
		return this.inputKanbanParams.pathToTask.replace('#task_id#', taskId);
	}
}
