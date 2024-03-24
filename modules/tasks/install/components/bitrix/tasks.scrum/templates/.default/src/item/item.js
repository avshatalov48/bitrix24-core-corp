import {Type, Dom, Event, Tag, Text} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Dialog} from 'ui.entity-selector';
import {Toggle} from './task/toggle';
import {Name} from './task/name';
import {Checklist} from './task/checklist';
import {Files} from './task/files';
import {Comments, TaskCounter} from './task/comments';
import {Epic, EpicType} from './task/epic';
import {Tags} from './task/tags';
import {Responsible, ResponsibleType} from './task/responsible';
import {StoryPoints} from './task/story.points';
import {SubTasks} from './task/sub.tasks';
import { sendData } from 'ui.analytics';

import 'main.polyfill.intersectionobserver';

import '../css/item.css';

type AllowedActions = {
	task_edit: boolean,
	task_remove: boolean
}

type ItemInfo = {
	color?: string,
	borderColor?: string
}
type SubTaskInfo = {
	sourceId: number,
	completed: 'Y' | 'N',
	storyPoints: string
}

type SubTasksInfo = {
	[sourceId: number]: SubTaskInfo
}

export type ItemParams = {
	id: number | string,
	tmpId: string,
	name: string,
	groupId: number,
	checkListComplete: number,
	checkListAll: number,
	attachedFilesCount: number,
	taskCounter: TaskCounter,
	epic?: EpicType,
	tags?: Array<string>,
	responsible?: ResponsibleType,
	storyPoints?: string,
	entityId?: number,
	entityType?: string,
	parentId?: number,
	sourceId?: number,
	completed?: 'Y' | 'N',
	sort?: number,
	allowedActions?: AllowedActions,
	info?: ItemInfo,
	isParentTask?: 'Y' | 'N',
	isLinkedTask?: 'Y' | 'N',
	parentTaskId?: number,
	isSubTask?: 'Y' | 'N',
	subTasksInfo?: SubTasksInfo,
	isImportant?: 'Y' | 'N',
	pathToTask: string
};

export class Item extends EventEmitter
{
	constructor(params: ItemParams)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Item');

		this.groupMode = false;
		this.decompositionMode = false;

		this.node = null;

		this.toggle = null;
		this.name = null;
		this.checklist = null;
		this.files = null;
		this.comments = null;
		this.epic = null;
		this.tags = null;
		this.responsible = null;
		this.storyPoints = null;
		this.subTasks = null;

		this.setItemParams(params);

		this.shortView = 'Y';
	}

	setItemParams(params: ItemParams)
	{
		this.setId(params.id);
		this.setGroupId(params.groupId);
		this.setTmpId(params.tmpId);
		this.setSort(params.sort);
		this.setEntityId(params.entityId);
		this.setEntityType(params.entityType);
		this.setSourceId(params.sourceId);
		this.setInfo(params.info);

		this.setSubTasksInfo(params.subTasksInfo);
		this.setParentTask(params.isParentTask);
		this.setLinkedTask(params.isLinkedTask);
		this.setParentTaskId(params.parentTaskId);
		this.setSubTask(params.isSubTask);
		this.setCompleted(params.completed);
		this.setDisableStatus(false);
		this.setAllowedActions(params.allowedActions);
		this.setImportant(params.isImportant);

		this.pathToTask = params.pathToTask;
	}

	static buildItem(params: ItemParams): Item
	{
		const item = new Item(params);

		item.setToggle(item.isParentTask());
		item.setName(params.name);
		item.setChecklist(
			params.checkListComplete,
			params.checkListAll
		);
		item.setFiles(params.attachedFilesCount);
		item.setComments(params.taskCounter);
		item.setEpic(params.epic);
		item.setTags(params.tags);
		item.setResponsible(params.responsible);

		if (!item.isSubTask())
		{
			item.setStoryPoints(params.storyPoints);
		}

		item.setSubTasks();

		return item;
	}

	setToggle(visible: boolean)
	{
		const toggle = new Toggle({ visible });

		if (this.toggle)
		{
			Dom.replace(this.toggle.getNode(), toggle.render());
		}

		this.toggle = toggle;

		this.toggle.subscribe('show', this.onShowToggle.bind(this));
		this.toggle.subscribe('hide', this.onHideToggle.bind(this));
	}

	getToggle(): Toggle
	{
		return this.toggle;
	}

	setName(inputName: string)
	{
		const name = new Name({
			name: inputName,
			isCompleted: this.isCompleted(),
			isImportant: this.isImportant(),
			pathToTask: this.pathToTask,
			sourceId: this.getSourceId()
		});

		if (this.name)
		{
			Dom.replace(this.name.getNode(), name.render());
		}

		this.name = name;

		this.name.subscribe('click', () => {
			this.emit('showTask');
			this.sendAnalytics('task_view', 'title_click');
		});
		this.name.subscribe('urlClick', () => {
			this.emit('destroyActionPanel');
			this.sendAnalytics('task_view', 'title_click');
		});
	}

	getName(): Name
	{
		return this.name;
	}

	setChecklist(complete: number, all: number)
	{
		const checklist = new Checklist({ complete, all });

		if (this.checklist)
		{
			Dom.replace(this.checklist.getNode(), checklist.render());
		}

		this.checklist = checklist;

		this.checklist.subscribe('click', () => this.emit('showTask'));
	}

	getChecklist(): Checklist
	{
		return this.checklist;
	}

	setFiles(count: number)
	{
		const files = new Files(count);

		if (this.files)
		{
			Dom.replace(this.files.getNode(), files.render());
		}

		this.files = files;

		this.files.subscribe('click', () => this.emit('showTask'));
	}

	getFiles(): Checklist
	{
		return this.files;
	}

	setComments(taskCounter: TaskCounter)
	{
		const comments = new Comments(taskCounter);

		if (this.comments)
		{
			Dom.replace(this.comments.getNode(), comments.render());
		}

		this.comments = comments;

		this.comments.subscribe('click', () => this.emit('showTask'));
	}

	getComments(): Comments
	{
		return this.comments;
	}

	setEpic(inputEpic?: EpicType)
	{
		const epic = new Epic(inputEpic);

		if (this.epic)
		{
			Dom.replace(this.epic.getNode(), this.isShortView() ? epic.render() : epic.renderFullView());
		}

		this.epic = epic;

		this.updateTagsVisibility();

		this.epic.subscribe('click', () => this.emit('filterByEpic', this.epic.getId()));
	}

	getEpic(): Epic
	{
		return this.epic;
	}

	setTags(inputTags?: Array<string>)
	{
		const tags = new Tags(inputTags);

		if (this.tags)
		{
			if (this.getNode()) //todo
			{
				this.replaceTags(tags);
			}
		}

		this.tags = tags;

		this.updateTagsVisibility();

		this.tags.subscribe('click', (baseEvent: BaseEvent) => this.emit('filterByTag', baseEvent.getData()));
	}

	getTags(): Tags
	{
		return this.tags;
	}

	setShortView(value: string)
	{
		this.shortView = (value === 'Y' ? 'Y' : 'N');

		if (this.getNode())
		{
			Dom.replace(this.getNode(), this.render());
		}
	}

	getShortView(): 'Y' | 'N'
	{
		return this.shortView;
	}

	isShortView(): boolean
	{
		return this.shortView === 'Y';
	}

	setResponsible(inputResponsible: ResponsibleType)
	{
		const responsible = new Responsible(inputResponsible);

		if (this.responsible)
		{
			Dom.replace(this.responsible.getNode(), responsible.render());
		}

		this.responsible = responsible;

		this.responsible.subscribe('click', this.onResponsibleClick.bind(this));
	}

	getResponsible(): Responsible
	{
		return this.responsible;
	}

	setStoryPoints(inputStoryPoints: string)
	{
		const storyPoints = new StoryPoints(inputStoryPoints);

		if (this.storyPoints)
		{
			Dom.replace(this.storyPoints.getNode(), storyPoints.render());
		}

		if (this.isDisabled())
		{
			storyPoints.disable();
		}

		this.storyPoints = storyPoints;

		this.storyPoints.subscribe('setStoryPoints', this.onSetStoryPoints.bind(this));
	}

	getStoryPoints(): StoryPoints
	{
		return this.storyPoints;
	}

	setSubTasks()
	{
		this.subTasks = new SubTasks(this);

		this.subTasks.subscribe('click', () => this.emit('showTask'));
	}

	getSubTasks(): ?SubTasks
	{
		return this.subTasks;
	}

	setId(id: number | string)
	{
		this.id = (
			Type.isInteger(id) ? parseInt(id, 10) :
				(Type.isString(id) && id) ? id : Text.getRandom()
		);

		if (this.getNode())
		{
			this.getNode().dataset.id = this.id;
		}
	}

	getId(): number | string
	{
		return this.id;
	}

	setGroupId(id: number)
	{
		this.groupId = (Type.isInteger(id) ? parseInt(id, 10) : 0);
	}

	getGroupId(): number
	{
		return this.groupId;
	}

	setTmpId(tmpId: string)
	{
		this.tmpId = (Type.isString(tmpId) ? tmpId : '');
	}

	getTmpId(): string
	{
		return this.tmpId;
	}

	setSort(sort: number)
	{
		this.setPreviousSort(this.sort);

		this.sort = (Type.isInteger(sort) ? parseInt(sort, 10) : 0);

		if (this.getNode())
		{
			Dom.attr(this.getNode(), 'data-sort', this.sort);
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

		this.updateBorderColor();
	}

	getEntityType(): string
	{
		return this.entityType;
	}

	setSourceId(sourceId: number)
	{
		this.sourceId = (Type.isInteger(sourceId) ? parseInt(sourceId, 10) : 0);
	}

	getSourceId(): number
	{
		return this.sourceId;
	}

	setCompleted(value: string)
	{
		const completed = (value === 'Y');

		if (this.name)
		{
			this.name.setCompleted(completed);

			if (completed)
			{
				this.name.strikeOut();
			}
			else
			{
				this.name.unStrikeOut();
			}
		}

		this.completed = completed;
	}

	setAllowedActions(allowedActions: AllowedActions)
	{
		this.allowedActions = (Type.isPlainObject(allowedActions) ? allowedActions : {});
	}

	setImportant(isImportant: 'Y' | 'N')
	{
		this.important = (isImportant === 'Y');
	}

	isImportant(): boolean
	{
		return this.important;
	}

	setSubTasksInfo(subTasksInfo: ?SubTasksInfo)
	{
		this.subTasksInfo = subTasksInfo;
	}

	getSubTasksInfo(): ?SubTasksInfo
	{
		return this.subTasksInfo;
	}

	getSubTasksCount(): number
	{
		if (!this.getSubTasksInfo())
		{
			return 0;
		}

		return Object.keys(this.getSubTasksInfo()).length;
	}

	setParentTask(value: string)
	{
		this.parentTask = (value === 'Y');

		if (this.getNode())
		{
			this.setToggle(this.isParentTask());

			if (this.isParentTask())
			{
				Dom.addClass(this.getNode(), '--parent-tasks');

				if (this.getSubTasksCount() > 1)
				{
					Dom.addClass(this.getNode(), '--many');
				}
				else
				{
					Dom.removeClass(this.getNode(), '--many');
				}
			}
			else
			{
				Dom.removeClass(this.getNode(), '--parent-tasks');
			}
		}
	}

	isParentTask(): boolean
	{
		return this.parentTask;
	}

	setLinkedTask(value: string)
	{
		this.linkedTask = (value === 'Y');

		if (this.getNode())
		{
			if (this.isLinkedTask() && !this.isSubTask())
			{
				Dom.addClass(this.getNode(), '--linked');
			}
			else
			{
				Dom.removeClass(this.getNode(), '--linked');
			}
		}
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

		if (this.getNode())
		{
			if (this.isSubTask())
			{
				Dom.addClass(this.getNode(), '--subtasks');
			}
			else
			{
				Dom.removeClass(this.getNode(), '--subtasks');
			}
		}
	}

	isSubTask(): boolean
	{
		return this.subTask;
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
	}

	getInfo(): ?ItemInfo
	{
		return this.info;
	}

	setBorderColor(color: string)
	{
		this.info.borderColor = (Type.isString(color) ? color : '');

		this.updateBorderColor();
	}

	getBorderColor(): string
	{
		return (Type.isString(this.info.borderColor) ? this.info.borderColor : '');
	}

	updateBorderColor()
	{
		if (this.isLinkedTask() && !this.isSubTask() && this.getNode() && this.getBorderColor() !== '')
		{
			const colorNode = this.getNode().querySelector('.tasks-scrum__item--link');

			Dom.style(colorNode, 'backgroundColor', this.getBorderColor());

			switch (this.getEntityType())
			{
				case 'backlog':
					Dom.style(this.getNode().querySelector('.tasks-scrum__item--bg'), 'backgroundColor', this.getBorderColor());
					break;
				case 'sprint':
					Dom.style(this.getNode(), 'borderLeft', null);
					break;
			}
		}
	}

	isCompleted(): boolean
	{
		return this.completed;
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

		if (this.storyPoints)
		{
			if (this.isDisabled())
			{
				this.storyPoints.disable();
			}
			else
			{
				this.storyPoints.unDisable();
			}
		}
	}

	activateGroupMode()
	{
		Dom.addClass(this.getNode(), '--checked');
	}

	deactivateGroupMode()
	{
		Dom.removeClass(this.getNode(), '--checked');
	}

	activateLinkedMode()
	{
		Dom.addClass(this.getNode(), '--linked-mode');
	}

	deactivateLinkedMode()
	{
		Dom.removeClass(this.getNode(), '--linked-mode');
		Dom.removeClass(this.getNode(), '--linked-mode-current');
	}

	activateCurrentLinkedMode()
	{
		Dom.addClass(this.getNode(), '--linked-mode-current');
	}

	deactivateCurrentLinkedMode()
	{
		Dom.removeClass(this.getNode(), '--linked-mode-current');
	}

	addItemToGroupMode()
	{
		this.groupMode = true;

		Dom.addClass(this.getNode(), ['--group-mode']);

		this.getNode().querySelector('.tasks-scrum__item--group-mode-input').checked = true;
	}

	removeItemFromGroupMode()
	{
		this.groupMode = false;

		Dom.removeClass(this.getNode(), ['--group-mode']);

		this.getNode().querySelector('.tasks-scrum__item--group-mode-input').checked = false;
	}

	isGroupMode(): boolean
	{
		return this.groupMode;
	}

	getNode(): ?HTMLElement
	{
		return this.node;
	}

	showSubTasks()
	{
		Dom.addClass(this.getNode(), '--open');

		this.getSubTasks()
			.show()
			.then(() => {
				this.toggle.show();
			})
		;
	}

	hideSubTasks()
	{
		Dom.removeClass(this.getNode(), '--open');

		this.getSubTasks()
			.hide()
			.then(() => {
				this.toggle.hide();
			})
		;
	}

	cleanSubTasks()
	{
		this.getSubTasks().cleanTasks();
	}

	isShownSubTasks(): boolean
	{
		return this.getSubTasks().isShown();
	}

	activateDecompositionMode()
	{
		this.decompositionMode = true;
	}

	deactivateDecompositionMode()
	{
		this.decompositionMode = false;
	}

	isDecompositionMode(): boolean
	{
		return this.decompositionMode;
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
		this.setToggle(tmpItem.isParentTask());

		if (this.getName().getValue() !== tmpItem.getName().getValue())
		{
			this.setName(tmpItem.getName().getValue());
		}
		if (this.getChecklist().getValue() !== tmpItem.getChecklist().getValue())
		{
			this.setChecklist(
				tmpItem.getChecklist().getComplete(),
				tmpItem.getChecklist().getAll()
			);
		}
		if (this.getFiles().getValue() !== tmpItem.getFiles().getValue())
		{
			this.setFiles(tmpItem.getFiles().getValue());
		}
		if (this.getComments().getValue() !== tmpItem.getComments().getValue())
		{
			this.setComments(tmpItem.getComments().getValue());
		}
		if (this.getEpic().getValue() !== tmpItem.getEpic().getValue())
		{
			this.setEpic(tmpItem.getEpic().getValue());
		}
		if (!this.getTags().isEqualTags(tmpItem.getTags()))
		{
			this.setTags(tmpItem.getTags().getValue());
		}
		if (this.getResponsible().getValue() !== tmpItem.getResponsible().getValue())
		{
			this.setResponsible(tmpItem.getResponsible().getValue());
		}
		if (
			!this.isSubTask()
			&& this.getStoryPoints().getValue().getPoints() !== tmpItem.getStoryPoints().getValue().getPoints()
		)
		{
			this.setStoryPoints(tmpItem.getStoryPoints().getValue().getPoints());
		}
		this.setEntityId(tmpItem.getEntityId());
		if (this.isCompleted() !== tmpItem.isCompleted())
		{
			this.setCompleted(tmpItem.isCompleted() ? 'Y' : 'N');
		}
		if (this.isImportant() !== tmpItem.isImportant())
		{
			this.setImportant(tmpItem.isImportant() ? 'Y' : 'N');
			this.setName(tmpItem.getName().getValue());
		}

		this.setParentTask(tmpItem.isParentTask() ? 'Y' : 'N');
		this.setLinkedTask(tmpItem.isLinkedTask() ? 'Y' : 'N');
		this.setParentTaskId(tmpItem.getParentTaskId());
		this.setSubTask(tmpItem.isSubTask() ? 'Y' : 'N');
	}

	removeYourself()
	{
		Dom.remove(this.node);

		this.node = null;
	}

	render(): HTMLElement
	{
		if (this.isShortView())
		{
			return this.renderShortView();
		}
		else
		{
			return this.renderFullView();
		}
	}

	renderShortView(): HTMLElement
	{
		const typeClass = (this.isParentTask() ? ' --parent-tasks ' : ' ') + (this.isSubTask() ? ' --subtasks ' : '');
		const subClass = this.getSubTasksCount() > 1 ? ' --many ' : '';
		const linkedClass = this.isLinkedTask() && !this.isSubTask() ? ' --linked ' : '';
		const entityClass = '--item-' + this.getEntityType();

		this.node = Tag.render`
			<div
				class="tasks-scrum__item${typeClass}${subClass}${linkedClass}tasks-scrum__item--drag --short-view ${entityClass}"
				data-id="${Text.encode(this.getId())}"
				data-sort="${Text.encode(this.getSort())}"
			>
			<div class="tasks-scrum__item--bg"></div>
				<div class="tasks-scrum__item--link"></div>
				<div class="tasks-scrum__item--info">
					${this.toggle ? this.toggle.render() : ''}
					<div class="tasks-scrum__item--main-info">
						${this.name ? this.name.render() : ''}
						<div class="tasks-scrum__item--tags">
							${this.epic ? this.epic.render() : ''}
							${this.tags ? this.tags.render() : ''}
						</div>
					</div>
					<div class="tasks-scrum__item--entity-content">
						${this.comments ? this.comments.render() : ''}
						<div class="tasks-scrum__item--counter-container">
							${this.files ? this.files.render() : ''}
							${this.checklist ? this.checklist.render() : ''}
						</div>
					</div>
				</div>
				${this.responsible ? this.responsible.render() : ''}
				${!this.isSubTask() && this.storyPoints ? this.storyPoints.render() : ''}
				<div class="tasks-scrum__item--group-mode">
					<input type="checkbox" class="tasks-scrum__item--group-mode-input">
				</div>
				<div class="tasks-scrum__item--substrate"></div>
				<div class="tasks-scrum__item--dragstrate"></div>
			</div>
		`;

		Event.bind(this.node, 'click', this.onItemClick.bind(this));

		this.updateBorderColor();

		return this.node;
	}

	renderFullView(): HTMLElement
	{
		const typeClass = (this.isParentTask() ? ' --parent-tasks ' : ' ') + (this.isSubTask() ? ' --subtasks ' : '');
		const subClass = this.getSubTasksCount() > 1 ? ' --many ' : '';
		const linkedClass = this.isLinkedTask() && !this.isSubTask() ? ' --linked ' : '';
		const entityClass = '--item-' + this.getEntityType();

		this.node = Tag.render`
			<div
				class="tasks-scrum__item${typeClass}${subClass}${linkedClass}tasks-scrum__item--drag --full-view ${entityClass}"
				data-id="${Text.encode(this.getId())}"
				data-sort="${Text.encode(this.getSort())}"
			>
			<div class="tasks-scrum__item--bg"></div>
			<div class="tasks-scrum__item--info-task--basic">
				<div class="tasks-scrum__item--link"></div>
				<div class="tasks-scrum__item--info">
					${this.toggle ? this.toggle.render() : ''}
					<div class="tasks-scrum__item--main-info">
						${this.name ? this.name.render() : ''}
						<div class="tasks-scrum__item--tags">
							${this.epic ? this.epic.renderFullView() : ''}
							${this.tags ? this.tags.render() : ''}
						</div>
					</div>
					${this.comments ? this.comments.render() : ''}
				</div>
			</div>
			<div class="tasks-scrum__item--info-task--details">
				${this.responsible ? this.responsible.render() : ''}

					<div class="tasks-scrum__item--counter-container">
						${this.files ? this.files.render() : ''}
						${this.checklist ? this.checklist.render() : ''}
					</div>

				${!this.isSubTask() && this.storyPoints ? this.storyPoints.render() : ''}
			</div>

				<div class="tasks-scrum__item--group-mode">
					<input type="checkbox" class="tasks-scrum__item--group-mode-input">
				</div>
				<div class="tasks-scrum__item--substrate"></div>
				<div class="tasks-scrum__item--dragstrate"></div>
			</div>
		`;

		this.updateTagsVisibility();

		Event.bind(this.node, 'click', this.onItemClick.bind(this));

		this.updateBorderColor();

		return this.node;
	}

	updateTagsVisibility()
	{
		if (!this.getNode())
		{
			return;
		}

		if (this.epic.getValue().id > 0 || this.tags.getValue().length > 0)
		{
			Dom.addClass(this.getNode().querySelector('.tasks-scrum__item--tags'), '--visible');
		}
		else
		{
			Dom.removeClass(this.getNode().querySelector('.tasks-scrum__item--tags'), '--visible');
		}
	}

	replaceTags(tags: Tags)
	{
		const tagsContainer = this.getNode().querySelector('.tasks-scrum__item--tags');
		const tagsNode = tagsContainer.querySelectorAll('.tasks-scrum__item--hashtag');

		tagsNode.forEach((tagNode: HTMLElement) => Dom.remove(tagNode));

		const tagList = tags.render();

		if (Type.isArray(tagList))
		{
			tagList.forEach((tagNode: HTMLElement) => Dom.append(tagNode, tagsContainer));
		}
		else
		{
			Dom.append(tagList, tagsContainer);
		}
	}

	onItemClick(event)
	{
		const target = event.target;

		if (Dom.hasClass(target, 'tasks-scrum__item--link'))
		{
			this.emit('showLinked');

			return;
		}

		if (this.isDisabled())
		{
			return;
		}

		if (this.toggle && this.hasNode(this.toggle.getNode(), target))
		{
			return;
		}
		if (this.name && this.hasNode(this.name.getNode(), target))
		{
			return;
		}
		if (this.checklist && this.hasNode(this.checklist.getNode(), target))
		{
			return;
		}
		if (this.files && this.hasNode(this.files.getNode(), target))
		{
			return;
		}
		if (this.comments && this.hasNode(this.comments.getNode(), target))
		{
			return;
		}
		if (this.epic && this.hasNode(this.epic.getNode(), target))
		{
			return;
		}
		if (this.tags && this.hasNode(this.tags.getNode(), target))
		{
			return;
		}
		if (this.responsible && this.hasNode(this.responsible.getNode(), target, true))
		{
			return;
		}
		if (this.isSubTask())
		{
			return;
		}
		if (this.storyPoints && this.hasNode(this.storyPoints.getNode(), target))
		{
			return;
		}

		this.emit('toggleActionPanel');
	}

	onResponsibleClick()
	{
		if (this.isGroupMode())
		{
			return;
		}

		if (this.isDisabled())
		{
			return;
		}

		if (this.responsibleDialog && this.responsibleDialog.isOpen())
		{
			this.responsibleDialog.hide();
			this.responsibleDialog = null;

			return;
		}

		const responsible = this.getResponsible().getValue();
		const preselectedItems = responsible ? [['user' , responsible.id]] : [];

		this.responsibleDialog = new Dialog({
			targetNode: this.responsible.getNode(),
			enableSearch: true,
			context: 'TASKS',
			dropdownMode: true,
			preselectedItems: preselectedItems,
			events: {
				'Item:onSelect': (event) => {
					this.responsibleDialog.hide();
					const selectedItem = event.getData().item;
					this.setResponsible({
						id: selectedItem.getId(),
						name: selectedItem.getTitle(),
						photo: {
							src: selectedItem.getAvatar()
						}
					});
					this.emit('changeTaskResponsible');
				},
			},
			entities: [
				{
					id: 'scrum-user',
					options: {
						groupId: this.getGroupId()
					},
					dynamicLoad: true
				},
				{
					id: 'department'
				}
			]
		});

		this.emit('onShowResponsibleDialog', this.responsibleDialog);

		this.responsibleDialog.show();
	}

	onSetStoryPoints(baseEvent: BaseEvent)
	{
		if (this.isDisabled())
		{
			return;
		}

		this.emit('updateItem', {
			itemId: this.getId(),
			entityId: this.getEntityId(),
			storyPoints: baseEvent.getData()
		});

		this.setStoryPoints(baseEvent.getData());
	}

	onShowToggle()
	{
		this.emit('showSubTasks', this.getSubTasks());
	}

	onHideToggle()
	{
		this.hideSubTasks();
	}

	hasNode(parentNode: HTMLElement | Array<HTMLElement>, searchNode: HTMLElement, skipParent = false): boolean
	{
		if (Type.isArray(parentNode))
		{
			const result = parentNode
				.map((node: HTMLElement) => this.hasNode(node, searchNode, skipParent))
				.find((result) => result === true)
			;

			return !Type.isUndefined(result);
		}

		if (!skipParent && searchNode.isEqualNode(parentNode))
		{
			return true;
		}

		const nodes = parentNode.getElementsByTagName('*');

		for (let k = 0; k < nodes.length; k++)
		{
			if (searchNode.isEqualNode(nodes[k]))
			{
				return true;
			}
		}

		return false;
	}

	activateBlinking()
	{
		if (!this.getNode())
		{
			return;
		}

		if (Type.isUndefined(IntersectionObserver))
		{
			return;
		}

		const observer = new IntersectionObserver((entries) =>
			{
				if (entries[0].isIntersecting === true)
				{
					this.blink();

					observer.disconnect();
				}
			},
			{
				threshold: [0]
			}
		);

		observer.observe(this.getNode());
	}

	blink()
	{
		if (!this.getNode())
		{
			return;
		}

		Dom.addClass(this.getNode(), '--blink');
		setTimeout(() => {
			Dom.removeClass(this.getNode(), '--blink');
		}, 300);
	}

	sendAnalytics(event, element)
	{
		const analyticsData = {
			tool: 'tasks',
			category: 'task_operations',
			event: event,
			type: 'task',
			c_section: 'scrum',
			c_element: element,
		};

		sendData(analyticsData);
	}
}
