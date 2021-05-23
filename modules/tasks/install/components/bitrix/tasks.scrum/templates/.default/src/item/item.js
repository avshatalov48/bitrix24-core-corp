import {Type, Dom, Event, Tag, Text, Loc, Runtime} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {Label} from 'ui.label';
import {MessageBox} from 'ui.dialogs.messagebox';
import {Dialog} from 'ui.entity-selector';

import {DiskManager} from '../service/disk.manager';

import {ActionsPanel} from './task/actions.panel';
import {TaskCounts} from './task/taskcounts';

import {StoryPoints} from '../utility/story.points';

import '../css/item.css';

type EpicInfoType = {
	color: string
}

export type EpicType = {
	id: number,
	name: string,
	description: string,
	info: EpicInfoType
}

type AllowedActions = {
	task_edit: boolean,
	task_remove: boolean,
}

export type Responsible = {
	id: number,
	name: string,
	pathToUser: string,
	photo: {
		src: string
	}
}

type ItemInfo = {
	color?: string,
	borderColor?: string
}

export type ItemParams = {
	itemId: number|string,
	tmpId: string,
	name: string,
	itemType?: string,
	sort?: number,
	info?: ItemInfo,
	entityId?: number,
	entityType?: string,
	parentId?: number,
	sourceId?: number,
	parentSourceId?: number,
	responsible?: Responsible,
	storyPoints?: string,
	completed?: 'Y' | 'N',
	allowedActions?: AllowedActions,
	epic?: EpicType,
	tags?: Array,
	isParentTask?: 'Y' | 'N',
	subTasksCount?: number,
	isLinkedTask?: 'Y' | 'N',
	parentTaskId?: number,
	isSubTask?: 'Y' | 'N'
};

//todo single responsibility principle
export class Item extends EventEmitter
{
	constructor(params: ItemParams)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Item');

		this.setItemParams(params);

		this.groupMode = false;
		this.previewMode = false;
	}

	setItemParams(params: ItemParams)
	{
		this.setItemNode();

		this.setItemId(params.itemId);
		this.setTmpId(params.tmpId);
		this.setName(params.name);
		this.setItemType(params.itemType);
		this.setSort(params.sort);
		this.setEntityId(params.entityId);
		this.setEntityType(params.entityType);
		this.setParentId(params.parentId);
		this.setSourceId(params.sourceId);
		this.setParentSourceId(params.parentSourceId);
		this.setResponsible(params.responsible);
		this.setStoryPoints(params.storyPoints);
		this.setInfo(params.info);

		this.setCompleted(params.completed);
		this.setDisableStatus(this.isCompleted());

		this.setAllowedActions(params.allowedActions);
		this.setEpic(params.epic);
		this.setTags(params.tags);

		this.setParentTask(params.isParentTask);
		this.setSubTasksCount(params.subTasksCount);
		this.setLinkedTask(params.isLinkedTask);
		this.setParentTaskId(params.parentTaskId);
		this.setSubTask(params.isSubTask);

		this.setTaskCounts(params);
	}

	static buildItem(params: ItemParams): Item
	{
		return new Item(params);
	}

	setItemId(itemId: number|string)
	{
		this.itemId = (
			Type.isInteger(itemId) ? parseInt(itemId, 10) :
				(Type.isString(itemId) && itemId) ? itemId : Text.getRandom()
		);
		if (this.isNodeCreated())
		{
			this.getItemNode().dataset.itemId = this.itemId;
		}
	}

	getItemId()
	{
		return this.itemId;
	}

	setTmpId(tmpId: string)
	{
		this.tmpId = (Type.isString(tmpId) ? tmpId : '');
	}

	getTmpId(): string
	{
		return this.tmpId;
	}

	setName(name: string)
	{
		this.name = (Type.isString(name) ? name : 'Name');

		if (this.isNodeCreated())
		{
			const nameNode = this.getItemNode().querySelector('.tasks-scrum-item-name-field');
			nameNode.querySelector('.ui-ctl-element').textContent = Text.encode(this.name);
		}
	}

	getName(): string
	{
		return this.name;
	}

	setItemType(type: string)
	{
		this.itemType = (Type.isString(type) ? type : 'task');
	}

	getItemType()
	{
		return this.itemType;
	}

	setSort(sort: number)
	{
		this.setPreviousSort(this.sort);

		this.sort = (Type.isInteger(sort) ? parseInt(sort, 10) : 0);

		if (this.isNodeCreated())
		{
			Dom.attr(this.getItemNode(), 'data-sort', this.sort);
		}
	}

	getSort(): number
	{
		return this.sort;
	}

	setPreviousSort(sort: number)
	{
		this.previousSort = (Type.isInteger(sort) ? parseInt(sort, 10) : 0);
	}

	getPreviousSort(): number
	{
		return this.previousSort;
	}

	setEntityId(entityId: number)
	{
		this.entityId = (Type.isInteger(entityId) ? parseInt(entityId, 10) : 0);
	}

	getEntityId(): number
	{
		return this.entityId;
	}

	setEntityType(entityType: string)
	{
		this.entityType = (new Set(['backlog', 'sprint']).has(entityType) ? entityType : 'backlog');
	}

	getEntityType(): string
	{
		return this.entityType;
	}

	setParentId(parentId: number)
	{
		this.parentId = (Type.isInteger(parentId) ? parseInt(parentId, 10) : 0);
	}

	getParentId()
	{
		return this.parentId;
	}

	setSourceId(sourceId: number)
	{
		this.sourceId = (Type.isInteger(sourceId) ? parseInt(sourceId, 10) : 0);
	}

	getSourceId()
	{
		return this.sourceId;
	}

	setParentSourceId(sourceId: number)
	{
		this.parentSourceId = (Type.isInteger(sourceId) ? parseInt(sourceId, 10) : 0);
	}

	getParentSourceId(): number
	{
		return this.parentSourceId;
	}

	setResponsible(responsible: Responsible)
	{
		this.responsible = (Type.isPlainObject(responsible) ? responsible : null);

		if (this.responsible && this.isNodeCreated())
		{
			this.updateResponsible();
		}
	}

	getResponsible(): Responsible
	{
		return this.responsible;
	}

	setStoryPoints(storyPoints: string)
	{
		if (!this.storyPoints)
		{
			this.storyPoints = new StoryPoints();
		}

		this.storyPoints.setPoints(storyPoints);

		if (this.isNodeCreated())
		{
			const storyPointsNode = this.getItemNode().querySelector('.tasks-scrum-item-story-points');
			storyPointsNode.querySelector('.ui-ctl-element').textContent = Text.encode(
				this.getStoryPoints().getPoints()
			);

			this.sendEventToUpdateStoryPoints();
		}
	}

	getStoryPoints(): StoryPoints
	{
		return this.storyPoints;
	}

	setCompleted(value: string)
	{
		this.completed = (value === 'Y');

		if (this.isNodeCreated())
		{
			this.updateCompletedStatus();
		}
	}

	setAllowedActions(allowedActions: AllowedActions)
	{
		this.allowedActions = (Type.isPlainObject(allowedActions) ? allowedActions : {});
	}

	setEpic(epic: EpicType)
	{
		this.epic = (Type.isPlainObject(epic) ? epic : null);
	}

	getEpic(): EpicType
	{
		return this.epic;
	}

	setTags(tags)
	{
		this.tags = (Type.isArray(tags) ? tags : []);
	}

	getTags(): Array
	{
		return this.tags;
	}

	setParentTask(value: string)
	{
		this.parentTask = (value === 'Y');
	}

	isParentTask(): boolean
	{
		return this.parentTask;
	}

	setSubTasksCount(count: number)
	{
		this.subTasksCount = (Type.isInteger(count) ? parseInt(count, 10) : 0);
	}

	getSubTasksCount(): number
	{
		return this.subTasksCount;
	}

	setLinkedTask(value: string)
	{
		this.linkedTask = (value === 'Y');
	}

	isLinkedTask(): boolean
	{
		return this.linkedTask;
	}

	setParentTaskId(id: number)
	{
		this.parentTaskId = (Type.isInteger(id) ? parseInt(id, 10) : 0);
	}

	getParentTaskId(): number
	{
		return this.parentTaskId;
	}

	setSubTask(value: string)
	{
		this.subTask = (value === 'Y');
	}

	isSubTask(): boolean
	{
		return this.subTask;
	}

	setTaskCounts(params: ItemParams)
	{
		this.taskCounts = (this.itemType === 'task' ? new TaskCounts(params) : null);
	}

	getTaskCounts(): TaskCounts|null
	{
		return this.taskCounts;
	}

	setInfo(info: ?ItemInfo)
	{
		if (Type.isUndefined(info))
		{
			this.info = {
				color: '',
				borderColor: ''
			};

			return;
		}

		this.info = info;

		this.setBorderColor(this.info.borderColor);
	}

	getInfo(): ?ItemInfo
	{
		return this.info;
	}

	setBorderColor(color: string)
	{
		this.info.borderColor = (Type.isString(color) ? color : '');

		if (this.isNodeCreated())
		{
			if (this.getBorderColor())
			{
				Dom.style(this.getItemNode(), 'border', '2px solid ' + this.getBorderColor());
			}
			else
			{
				Dom.style(this.getItemNode(), 'border', null);
			}
		}
	}

	getBorderColor(): string
	{
		return (Type.isString(this.info.borderColor) ? this.info.borderColor : '');
	}

	isCompleted(): boolean
	{
		return this.completed;
	}

	updateCompletedStatus()
	{
		const nameNode = this.getItemNode().querySelector('.tasks-scrum-item-name');
		const nameTextNode = nameNode.querySelector('.ui-ctl-element');

		if (this.isCompleted())
		{
			Dom.style(nameTextNode, 'textDecoration', 'line-through');
		}
		else
		{
			Dom.style(nameTextNode, 'textDecoration', null);
		}

		this.sendEventToUpdateStoryPoints();
	}

	isDisabled(): boolean
	{
		return this.disableStatus;
	}

	setMoveActivity(value: boolean)
	{
		this.moveActivity = Boolean(value);
	}

	isMovable(): boolean
	{
		return this.moveActivity;
	}

	setDisableStatus(status: boolean)
	{
		this.disableStatus = Boolean(status);

		if (!this.isNodeCreated())
		{
			return;
		}

		if (status)
		{
			this.hideNode(this.getItemNode().querySelector('.tasks-scrum-dragndrop'));
		}
		else
		{
			this.showNode(this.getItemNode().querySelector('.tasks-scrum-dragndrop'));
		}
	}

	activateGroupMode()
	{
		this.groupMode = true;

		if (!this.isNodeCreated())
		{
			return;
		}

		const groupModeContainer = this.getItemNode().querySelector('.tasks-scrum-item-group-mode-container');
		const groupModeCheckbox = groupModeContainer.querySelector('input');
		groupModeCheckbox.checked = false;

		Event.bind(groupModeCheckbox, 'change', (event) => {
			Dom.toggleClass(this.getItemNode(), 'tasks-scrum-item-group-mode');
			if (this.getItemNode().classList.contains('tasks-scrum-item-group-mode'))
			{
				this.emit('addItemToGroupMode');
			}
			else
			{
				this.emit('removeItemFromGroupMode');
			}
			this.showActionsPanel(event);
		});

		this.showNode(groupModeContainer);

		this.deactivateDragNDrop();
	}

	deactivateGroupMode()
	{
		if (!this.isNodeCreated())
		{
			return;
		}

		Dom.removeClass(this.getItemNode(), 'tasks-scrum-item-group-mode');

		const groupModeContainer = this.getItemNode().querySelector('.tasks-scrum-item-group-mode-container');
		this.hideNode(groupModeContainer);

		Event.unbindAll(groupModeContainer.querySelector('input'));

		this.groupMode = false;

		this.activateDragNDrop();
	}

	isGroupMode(): boolean
	{
		return this.groupMode;
	}

	activatePreviewMode()
	{
		this.previewMode = true;
	}

	isPreviewMode(): boolean
	{
		return this.previewMode;
	}

	getPreviewVersion(): Item
	{
		const previewItem = Runtime.clone(this);
		previewItem.setItemId();
		previewItem.itemNode = null;
		if (previewItem.taskCounts)
		{
			previewItem.taskCounts = new TaskCounts(previewItem);
		}
		previewItem.activatePreviewMode();
		return previewItem;
	}

	activateDecompositionMode(color: string)
	{
		this.decompositionMode = true;

		if (this.getBorderColor() === '')
		{
			this.setBorderColor(color);
		}

		if (this.getBorderColor())
		{
			Dom.style(this.getItemNode(), 'border', '2px solid ' + this.getBorderColor());
		}

		this.deactivateDragNDrop();
	}

	deactivateDecompositionMode()
	{
		this.decompositionMode = false;

		if (this.getBorderColor() === '')
		{
			this.setBorderColor();
			Dom.style(this.getItemNode(), 'border', null);
		}

		this.activateDragNDrop();
	}

	activateDragNDrop()
	{
		if (this.isNodeCreated())
		{
			if (!this.getItemNode().classList.contains('tasks-scrum-item-drag'))
			{
				Dom.addClass(this.getItemNode(), 'tasks-scrum-item-drag');
			}
		}
	}

	deactivateDragNDrop()
	{
		if (this.isNodeCreated())
		{
			Dom.removeClass(this.getItemNode(), 'tasks-scrum-item-drag');
		}
	}

	isDecompositionMode(): boolean
	{
		return this.decompositionMode;
	}

	setItemNode(node?: HTMLElement)
	{
		try
		{
			this.itemNode = ((node instanceof HTMLElement) ? node : null);
		}
		catch (e)
		{
			this.itemNode = null;
		}
	}

	getItemNode(): HTMLElement|null
	{
		return this.itemNode;
	}

	isNodeCreated(): boolean
	{
		return (this.itemNode !== null);
	}

	setParentEntity(entityId: number, entityType: string)
	{
		this.setEntityId(entityId);
		this.setEntityType(entityType);
	}

	isEditAllowed(): boolean
	{
		return Boolean(this.allowedActions['task_edit']);
	}

	isRemoveAllowed(): boolean
	{
		return Boolean(this.allowedActions['task_remove']);
	}

	updateYourself(tmpItem: Item)
	{
		if (tmpItem.getName() !== this.getName())
		{
			this.setName(tmpItem.getName());
		}
		if (tmpItem.getEntityId() !== this.getEntityId())
		{
			this.setEntityId(tmpItem.getEntityId());
		}
		if (tmpItem.getResponsible().id !== this.getResponsible().id)
		{
			this.setResponsible(tmpItem.getResponsible());
		}
		if (tmpItem.getStoryPoints().getPoints() !== this.getStoryPoints().getPoints())
		{
			this.setStoryPoints(tmpItem.getStoryPoints().getPoints());
		}
		if (this.getTaskCounts() && tmpItem.getTaskCounts())
		{
			this.getTaskCounts().updateIndicators({
				attachedFilesCount: tmpItem.getTaskCounts().getAttachedFilesCount(),
				checkListComplete: tmpItem.getTaskCounts().getCheckListComplete(),
				checkListAll: tmpItem.getTaskCounts().getCheckListAll(),
				newCommentsCount: tmpItem.getTaskCounts().getNewCommentsCount(),
			});
		}
		if (tmpItem.isCompleted() !== this.isCompleted())
		{
			this.setCompleted(tmpItem.isCompleted() ? 'Y' : 'N')
		}

		this.setParentId(tmpItem.getParentId());
		this.setEpicAndTags(tmpItem.getEpic(), tmpItem.getTags());
		this.setInfo(tmpItem.getInfo());

		this.setParentTask(tmpItem.isParentTask() ? 'Y' : 'N');
		this.setSubTasksCount(tmpItem.getSubTasksCount());
		this.setLinkedTask(tmpItem.isLinkedTask() ? 'Y' : 'N');
		this.setParentTaskId(tmpItem.getParentTaskId());
		this.setSubTask(tmpItem.isSubTask() ? 'Y' : 'N');

		if (this.isNodeCreated())
		{
			this.updateParentTaskNodes();
		}
	}

	removeYourself()
	{
		Dom.remove(this.getItemNode());
		this.setItemNode();
	}

	render(): HTMLElement
	{
		let itemClassName = 'tasks-scrum-item';
		if (this.isSubTask())
		{
			itemClassName += ' tasks-scrum-subtask-item'
		}

		this.itemNode = Tag.render`
			<div data-item-id="${Text.encode(this.itemId)}" data-sort=
				"${Text.encode(this.sort)}" class="${itemClassName}">
				<div class="tasks-scrum-item-inner">
					<div class="tasks-scrum-item-group-mode-container">
						<input type="checkbox">
					</div>
					<div class="tasks-scrum-item-name">
						${this.renderName()}
						${(this.taskCounts && this.itemType === 'task' ? this.taskCounts.renderIndicators() : '')}
					</div>
					<div class="tasks-scrum-item-params">
						${this.renderSubTasksCounter()}
						${this.renderResponsible()}
						${this.renderStoryPoints()}
						${this.renderSubTasksTick()}
					</div>
				</div>
				${this.renderTags()}
			</div>
		`;

		if (this.isNodeCreated() && this.getBorderColor())
		{
			Dom.style(this.itemNode, 'border', '2px solid ' + this.getBorderColor());
		}

		return this.itemNode;
	}

	renderName(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-item-name-field ui-ctl ui-ctl-xs ui-ctl-textbox ui-ctl-no-border">
				<div class="ui-ctl-element" contenteditable="false">
					${Text.encode(this.name)}
				</div>
			</div>
		`;
	}

	renderResponsible(): HTMLElement
	{
		return Tag.render`<div class="ui-icon ui-icon-common-user tasks-scrum-item-responsible"><i></i></div>`;
	}

	renderStoryPoints(): HTMLElement
	{
		return Tag.render`
			<div class="tasks-scrum-item-story-points">
				<div class="tasks-scrum-item-story-points-field ui-ctl ui-ctl-xs ui-ctl-textbox ui-ctl-auto ui-ctl-no-border">
					<div class="ui-ctl-element" contenteditable="false">
						${Text.encode(this.getStoryPoints().getPoints())}
					</div>
				</div>
			</div>
		`;
	}

	renderSubTasksCounter(): HTMLElement
	{
		if (!this.isParentTask())
		{
			return '';
		}

		const subTasksCounter = Tag.render`
			<div class="tasks-scrum-item-subtasks-btn">
				<span class="tasks-scrum-item-subtasks-icon"></span>
				<span class="tasks-scrum-item-subtasks-count">
					${this.getSubTasksCount()}
				</span>
			</div>
		`;

		Event.bind(subTasksCounter, 'click', () => {
			this.emit('showTask');
		});

		return subTasksCounter;
	}

	renderSubTasksTick(): HTMLElement
	{
		if (!this.isParentTask())
		{
			return '';
		}

		if (this.getEntityType() === 'backlog')
		{
			return '';
		}

		const subTasksTick = Tag.render`
			<div class="tasks-scrum-item-subtasks-tick">
				<div class="ui-btn ui-btn-sm ui-btn-light ui-btn-icon-angle-down"></div>
			</div>
		`;

		Event.bind(subTasksTick, 'click', () => {
			if (!this.isDecompositionMode())
			{
				this.emit('toggleSubTasks');
			}
		});

		return subTasksTick;
	}

	toggleSubTasksTick()
	{
		if (!this.isNodeCreated())
		{
			return;
		}

		const subTasksTick = this.getItemNode().querySelector('.tasks-scrum-item-subtasks-tick');

		if (subTasksTick)
		{
			subTasksTick.firstElementChild.classList.toggle('ui-btn-icon-angle-up');
			subTasksTick.firstElementChild.classList.toggle('ui-btn-icon-angle-down');
		}
	}

	renderTags(): ?HTMLElement|string
	{
		if (this.epic === null && this.tags.length === 0)
		{
			return '';
		}
		return Tag.render`
			<div class="tasks-scrum-item-tags-container">
				${this.getEpicTag()}
				${this.getListTagNodes()}
			</div>
		`;
	}

	getEpicTag(): ?HTMLElement
	{
		if (this.epic === null)
		{
			return '';
		}

		const getContrastYIQ = (hexcolor) => {
			if (!hexcolor)
			{
				hexcolor = Label.Color.DEFAULT;
			}
			hexcolor = hexcolor.replace('#', '');
			const r = parseInt(hexcolor.substr(0, 2), 16);
			const g = parseInt(hexcolor.substr(2, 2), 16);
			const b = parseInt(hexcolor.substr(4, 2), 16);
			const yiq = ((r * 299 ) + (g* 587 ) + (b * 114)) / 1000;
			return (yiq >= 128) ? 'black' : 'white';
		};

		const epicLabel = new Label({
			text: this.epic.name,
			color: Label.Color.DEFAULT,
			size: Label.Size.MD,
			customClass: 'tasks-scrum-item-epic-label'
		});
		const container = epicLabel.getContainer();
		const innerLabel = container.querySelector('.ui-label-inner');

		const contrast = getContrastYIQ(this.epic.info.color);
		if (contrast === 'white')
		{
			Dom.style(innerLabel, 'color', '#ffffff');
		}
		else
		{
			Dom.style(innerLabel, 'color', '#525c69');
		}
		Dom.style(container, 'backgroundColor', this.epic.info.color);

		Event.bind(container, 'click', (event) => {
			if (this.isGroupMode())
			{
				this.clickToGroupModeCheckbox();
				this.showActionsPanel(event);
				return;
			}
			this.emit('filterByEpic', this.epic.id);
		});

		return container;
	}

	getListTagNodes(): HTMLElement
	{
		return this.tags.map((tag) => {
			const tagLabel = new Label({
				text: tag,
				color: Label.Color.TAG_LIGHT,
				fill: true,
				size: Label.Size.SM,
				customClass: ''
			});
			const container = tagLabel.getContainer();
			Event.bind(container, 'click', (event) => {
				if (this.isGroupMode())
				{
					this.clickToGroupModeCheckbox();
					this.showActionsPanel(event);
					return;
				}
				this.emit('filterByTag', tag);
			});
			return container;
		});
	}

	// todo remove all method
	onAfterAppend(container)
	{
		this.setItemNode(container.querySelector('[data-item-id="'+this.itemId+'"]'));

		if (!this.isNodeCreated())
		{
			return;
		}

		if (this.taskCounts)
		{
			this.taskCounts.onAfterAppend();
		}

		this.updateResponsible();

		if (this.isPreviewMode())
		{
			Event.bind(this.getItemNode(), 'click', () => this.emit('showTask'));
			return;
		}

		if (!this.isDecompositionMode())
		{
			this.activateDragNDrop();
		}

		if (this.isSubTask())
		{
			this.deactivateDragNDrop();
		}

		Event.unbindAll(this.getItemNode());
		Event.bind(this.getItemNode(), 'click', this.onItemClick.bind(this));

		const nameNode = this.getItemNode().querySelector('.tasks-scrum-item-name');
		Event.unbindAll(nameNode);
		Event.bind(nameNode, 'click', this.onNameClick.bind(this));

		const responsibleNode = this.getItemNode().querySelector('.tasks-scrum-item-responsible');
		Event.unbindAll(responsibleNode);
		Event.bind(responsibleNode, 'click', this.onResponsibleClick.bind(this));

		const storyPointsNode = this.getItemNode().querySelector('.tasks-scrum-item-story-points');
		Event.unbindAll(storyPointsNode);
		Event.bind(storyPointsNode, 'click', this.onStoryPointsClick.bind(this));

		if (this.isCompleted())
		{
			const nameTextNode = nameNode.querySelector('.ui-ctl-element');
			Dom.style(nameTextNode, 'textDecoration', 'line-through');
		}

		this.updateParentTaskNodes();
	}

	isShowIndicators(): Boolean
	{
		return Boolean(this.attachedFilesCount || this.checkListAll || this.newCommentsCount);
	}

	updateParentTaskNodes()
	{
		if (this.isParentTask())
		{
			const subTasksCounterNode = this.getItemNode().querySelector('.tasks-scrum-item-subtasks-btn');
			if (subTasksCounterNode)
			{
				Dom.replace(subTasksCounterNode, this.renderSubTasksCounter());
			}
			else
			{
				const paramsNode = this.getItemNode().querySelector('.tasks-scrum-item-params');
				const subTasksCounterNode = this.renderSubTasksCounter();
				Dom.insertBefore(subTasksCounterNode, paramsNode.firstElementChild);
			}

			const subTasksTickNode = this.getItemNode().querySelector('.tasks-scrum-item-subtasks-tick');
			if (subTasksTickNode)
			{
				const newSubTasksTickNode = this.renderSubTasksTick();
				if (newSubTasksTickNode)
				{
					Dom.replace(subTasksTickNode, newSubTasksTickNode);
				}
				else
				{
					Dom.remove(subTasksTickNode);
				}
			}
			else
			{
				const paramsNode = this.getItemNode().querySelector('.tasks-scrum-item-params');
				const newSubTasksTickNode = this.renderSubTasksTick();
				Dom.append(newSubTasksTickNode, paramsNode);
			}
		}
		else
		{
			const subTasksCounterNode = this.getItemNode().querySelector('.tasks-scrum-item-subtasks-btn');
			if (subTasksCounterNode)
			{
				Dom.remove(subTasksCounterNode);
			}
			const subTasksTickNode = this.getItemNode().querySelector('.tasks-scrum-item-subtasks-tick');
			if (subTasksTickNode)
			{
				Dom.remove(subTasksTickNode);
			}
		}
	}

	onItemClick(event)
	{
		if (this.isClickOnEditableName(event))
		{
			return;
		}

		const ignoreTagList = new Set(['I', 'SPAN', 'INPUT']);
		if (ignoreTagList.has(event.target.tagName))
		{
			return;
		}

		if (event.target.closest('.tasks-scrum-item-subtasks-tick'))
		{
			return;
		}

		this.clickToGroupModeCheckbox();

		this.showActionsPanel(event);
	}

	isClickOnEditableName(event): boolean
	{
		return (this.isEditAllowed() && event.target.classList.contains('ui-ctl-element'));
	}

	onNameClick(event)
	{
		if (this.isGroupMode())
		{
			this.clickToGroupModeCheckbox();
			this.showActionsPanel(event);
			return;
		}

		if (!this.isEditAllowed())
		{
			return;
		}

		const targetNode = event.target;
		if (Dom.hasClass(targetNode, 'ui-ctl-element') && targetNode.contentEditable === 'true')
		{
			return;
		}

		if (!Dom.hasClass(targetNode, 'ui-ctl-element') || this.isDisabled())
		{
			return;
		}

		const nameNode = event.currentTarget;
		const borderNode = nameNode.querySelector('.ui-ctl');
		const valueNode = nameNode.querySelector('.ui-ctl-element');
		valueNode.textContent = valueNode.textContent.trim();
		const oldValue = valueNode.textContent;

		Dom.addClass(this.getItemNode(), 'tasks-scrum-item-edit-mode');
		Dom.toggleClass(borderNode, 'ui-ctl-no-border');
		valueNode.contentEditable = 'true';

		this.deactivateDragNDrop();

		this.placeCursorAtEnd(valueNode);

		Event.bind(valueNode, 'keydown', this.blockEnterInput.bind(valueNode));

		Event.bindOnce(valueNode, 'blur', () => {
			Event.unbind(valueNode, 'keydown', this.blockEnterInput.bind(valueNode));

			Dom.removeClass(this.getItemNode(), 'tasks-scrum-item-edit-mode');
			Dom.addClass(borderNode, 'ui-ctl-no-border');
			valueNode.contentEditable = 'false';

			this.activateDragNDrop();

			const newValue = valueNode.textContent.trim();
			if (oldValue === newValue)
			{
				return;
			}
			this.emit('updateItem', {
				itemId: this.getItemId(),
				entityId: this.getEntityId(),
				itemType: this.getItemType(),
				name: newValue
			});
			this.name = newValue;
		}, true);
	}

	clickToGroupModeCheckbox()
	{
		if (this.isGroupMode())
		{
			const groupModeContainer = this.getItemNode().querySelector('.tasks-scrum-item-group-mode-container');
			const groupModeCheckbox = groupModeContainer.querySelector('input');
			groupModeCheckbox.click();
		}
	}

	onResponsibleClick(event)
	{
		if (this.isGroupMode())
		{
			this.clickToGroupModeCheckbox();
			this.showActionsPanel(event);
			return;
		}

		if (this.isDisabled())
		{
			return;
		}

		const responsibleNode = event.currentTarget;

		const dialog = new Dialog({
			targetNode: responsibleNode,
			enableSearch: true,
			context: 'TASKS',
			events: {
				'Item:onSelect': (event) => {
					dialog.hide();
					const selectedItem = event.getData().item;
					this.responsible = {
						id: selectedItem.getId(),
						name: selectedItem.getTitle(),
						photo: {
							src: selectedItem.getAvatar()
						}
					};
					this.updateResponsible();
					this.emit('changeTaskResponsible');

				},
			},
			entities: [
				{
					id: 'user',
					options: {
						inviteEmployeeLink: false
					}
				},
				{
					id: 'department'
				}
			]
		});

		dialog.show();
	}

	onStoryPointsClick(event)
	{
		if (this.isGroupMode())
		{
			this.clickToGroupModeCheckbox();
			this.showActionsPanel(event);
			return;
		}

		if (this.isDisabled())
		{
			return;
		}

		const storyPointsNode = event.currentTarget;
		const borderNode = storyPointsNode.querySelector('.ui-ctl');
		const valueNode = storyPointsNode.querySelector('.ui-ctl-element');
		valueNode.textContent = valueNode.textContent.trim();
		const oldValue = valueNode.textContent.trim();

		if (valueNode.contentEditable === 'true')
		{
			return;
		}

		Dom.toggleClass(borderNode, 'ui-ctl-no-border');
		valueNode.contentEditable = 'true';

		this.placeCursorAtEnd(valueNode);

		Event.bind(valueNode, 'keydown', this.blockEnterInput.bind(valueNode));

		Event.bindOnce(valueNode, 'blur', () => {
			Event.unbind(valueNode, 'keydown', this.blockEnterInput.bind(valueNode));

			Dom.toggleClass(borderNode, 'ui-ctl-no-border');
			valueNode.contentEditable = 'false';

			const newValue = valueNode.textContent.trim();
			if (newValue && oldValue === newValue)
			{
				valueNode.textContent = oldValue;
				return;
			}

			this.setStoryPoints(newValue);

			this.emit('updateItem', {
				itemId: this.getItemId(),
				entityId: this.getEntityId(),
				itemType: this.getItemType(),
				storyPoints: newValue
			});
			this.sendEventToUpdateStoryPoints();
		}, true);
	}

	sendEventToUpdateStoryPoints()
	{
		this.emit('updateStoryPoints');
	}

	blockEnterInput(event)
	{
		if (event.isComposing || event.keyCode === 13)
		{
			this.blur();
			return;
		}
	};

	showActionsPanel(event)
	{
		if (this.actionsPanel && this.actionsPanel.isShown() && !this.isGroupMode())
		{
			return;
		}

		if (event)
		{
			event.stopPropagation();
		}

		this.actionsPanel = new ActionsPanel({
			bindElement: this.getItemNode(),
			itemList: {
				task: {
					activity: (this.itemType === 'task' && !this.isGroupMode()),
					callback: () => {
						this.emit('showTask');
						this.actionsPanel.destroy();
					},
				},
				attachment: {
					activity: (!this.isDisabled() && this.isEditAllowed() && !this.isGroupMode()),
					callback: (event) => {
						const diskManager = new DiskManager({
							ufDiskFilesFieldName: 'UF_TASK_WEBDAV_FILES'
						});
						diskManager.subscribeOnce('onFinish', (baseEvent) => {
							this.emit('attachFilesToTask', baseEvent.getData());
							this.actionsPanel.destroy();
						});
						diskManager.showAttachmentMenu(event.currentTarget);
					},
				},
				move: {
					activity: (!this.decompositionMode && this.isMovable() && !this.isSubTask()),
					callback: (event) => {
						this.emit('move', event.currentTarget);
					},
				},
				sprint: {
					activity: (!this.isDisabled() && !this.decompositionMode && !this.isSubTask()),
					callback: (event) => {
						this.emit('moveToSprint', event.currentTarget);
					},
				},
				backlog: {
					activity: (this.entityType === 'sprint' && !this.decompositionMode && !this.isSubTask()),
					callback: () => {
						this.emit('moveToBacklog');
						this.actionsPanel.destroy();
					},
				},
				tags: {
					activity: true,
					callback: (event) => {
						this.emit('showTagSearcher', event.currentTarget);
					},
				},
				epic: {
					activity: true,
					callback: (event) => {
						this.emit('showEpicSearcher', event.currentTarget);
					},
				},
				decomposition: {
					activity: (
						!this.isDisabled()
						&& !this.decompositionMode
						&& !this.isGroupMode()
						&& !this.isSubTask()
					),
					callback: (event) => {
						this.emit('startDecomposition');
						this.actionsPanel.destroy();
					},
				},
				remove: {
					activity: this.isRemoveAllowed(),
					callback: () => {
						const message = (this.isGroupMode() ? Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TASKS') :
							Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TASK'));
						MessageBox.confirm(
							message,
							(messageBox) => {
								this.emit('remove');
								messageBox.close();
								this.actionsPanel.destroy();
							},
							Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'),
						);
					},
				},
			}
		});

		this.actionsPanel.showPanel();
	}

	getCurrentActionsPanel(): ?ActionsPanel
	{
		if (this.actionsPanel && this.actionsPanel.isShown())
		{
			return this.actionsPanel;
		}
		else
		{
			return null
		}
	}

	setEpicAndTags(epic, tags: Array)
	{
		this.epic = (Type.isPlainObject(epic) || epic === null ? epic : this.epic);
		this.tags = (Type.isArray(tags) ? tags : this.tags);

		this.updateTagsContainer();
	}

	updateTagsContainer()
	{
		if (!this.getItemNode())
		{
			return;
		}

		const newContainer = Tag.render`
			<div class="tasks-scrum-item-tags-container">
				${this.getEpicTag()}
				${this.getListTagNodes()}
			</div>
		`;

		const tagsContainerNode = this.getItemNode().querySelector('.tasks-scrum-item-tags-container');
		if (tagsContainerNode)
		{
			Dom.replace(tagsContainerNode, newContainer);
		}
		else
		{
			Dom.append(newContainer, this.getItemNode());
		}
	}

	updateResponsible()
	{
		const responsibleNode = this.getItemNode().querySelector('.tasks-scrum-item-responsible');

		if (!responsibleNode)
		{
			return;
		}

		Dom.attr(responsibleNode, 'title', this.responsible.name);
		if (this.responsible.photo && this.responsible.photo.src)
		{
			Dom.style(responsibleNode.firstElementChild, 'backgroundImage', 'url("'+this.responsible.photo.src+'")');
		}
		else
		{
			Dom.style(responsibleNode.firstElementChild, 'backgroundImage', null);
		}
	}

	placeCursorAtEnd(node)
	{
		node.focus();
		const selection = window.getSelection();
		const range = document.createRange();
		range.selectNodeContents(node);
		range.collapse(false);
		selection.removeAllRanges();
		selection.addRange(range);
	};

	updateIndicators(data: Object)
	{
		if (this.taskCounts)
		{
			this.taskCounts.updateIndicators(data);
		}
	}

	showNode(node)
	{
		Dom.style(node, 'display', 'block');
	}

	hideNode(node)
	{
		Dom.style(node, 'display', 'none');
	}
}
