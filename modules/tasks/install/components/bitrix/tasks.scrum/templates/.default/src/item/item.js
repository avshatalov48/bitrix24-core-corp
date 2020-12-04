import {Type, Dom, Event, Tag, Text, Loc, Runtime} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {ActionsPanel} from './task/actions.panel';
import {Label} from 'ui.label';
import {DiskManager} from '../service/disk.manager';
import {TaskCounts} from './task/taskcounts';
import {MessageBox} from 'ui.dialogs.messagebox';
import {StoryPoints} from '../utility/story.points';

import '../css/item.css';

type EpicInfoType = {
	color: string
}

type EpicType = {
	id: number,
	name: string,
	description: string,
	info: EpicInfoType
}

type AllowedActions = {
	task_edit: string,
	task_remove: string,
}

type Responsible = {
	name: string,
	pathToUser: string,
	photo: {
		src: string
	}
}

type itemParams = {
	itemId: number|string,
	name: string,
	itemType?: string,
	sort?: number,
	entityId?: number,
	entityType?: string,
	parentId?: number,
	sourceId?: number,
	parentSourceId?: number,
	storyPoints?: string,
	responsible?: Responsible,
	completed?: string,
	allowedActions?: AllowedActions,
	epic?: EpicType,
	tags?: Array
};

//todo single responsibility principle
export class Item extends EventEmitter
{
	constructor(item: itemParams = {})
	{
		super(item);

		this.setEventNamespace('BX.Tasks.Scrum.Item');

		this.itemId = (item.itemId ? item.itemId : Text.getRandom());
		this.name = item.name;
		this.itemType = item.itemType;
		this.sort = Type.isInteger(item.sort) ? parseInt(item.sort, 10) : 0;
		this.entityId = Type.isInteger(item.entityId) ? parseInt(item.entityId, 10) : 0;
		this.entityType = item.entityType;
		this.parentId = Type.isInteger(item.parentId) ? parseInt(item.parentId, 10) : 0;
		this.sourceId = Type.isInteger(item.sourceId) ? parseInt(item.sourceId, 10) : 0;
		this.parentSourceId = Type.isInteger(item.parentSourceId) ? parseInt(item.parentSourceId, 10) : 0;

		this.storyPoints = new StoryPoints();
		this.storyPoints.setPoints(item.storyPoints);

		this.responsible = (Type.isPlainObject(item.responsible) ? item.responsible : {});
		this.completed = (item.completed === 'Y');

		this.epic = (Type.isPlainObject(item.epic) ? item.epic : null);
		this.tags = (Type.isArray(item.tags) ? item.tags : []);

		this.actionsMap = {
			taskEdit: 'task_edit',
			taskRemove: 'task_remove'
		};
		this.setAllowedActions(item.allowedActions);

		this.taskCounts = (this.itemType === 'task' ? new TaskCounts(item) : null); //todo create taskChild

		this.itemNode = null;

		this.groupMode = false;
		this.previewMode = false;
	}

	setDisableStatus(status: Boolean)
	{
		this.disableStatus = status;
		if (this.itemNode)
		{
			if (status)
			{
				this.hideNode(this.itemNode.querySelector('.tasks-scrum-dragndrop'));
			}
			else
			{
				this.showNode(this.itemNode.querySelector('.tasks-scrum-dragndrop'));
			}
		}
	}

	activateGroupMode()
	{
		const groupModeContainer = this.itemNode.querySelector('.tasks-scrum-item-group-mode-container');
		const groupModeCheckbox = groupModeContainer.querySelector('input');
		groupModeCheckbox.checked = false;

		Event.bind(groupModeCheckbox, 'change', (event) => {
			Dom.toggleClass(this.itemNode, 'tasks-scrum-item-group-mode');
			if (this.itemNode.classList.contains('tasks-scrum-item-group-mode'))
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

		this.groupMode = true;

		// todo tmp block drag
		Dom.removeClass(this.itemNode, 'tasks-scrum-item-drag');
	}

	deactivateGroupMode()
	{
		if (!this.itemNode)
		{
			return;
		}

		Dom.removeClass(this.itemNode, 'tasks-scrum-item-group-mode');

		const groupModeContainer = this.itemNode.querySelector('.tasks-scrum-item-group-mode-container');
		this.hideNode(groupModeContainer);

		Event.unbindAll(groupModeContainer.querySelector('input'));

		this.groupMode = false;

		// todo tmp block drag
		if (!this.itemNode.classList.contains('tasks-scrum-item-drag'))
		{
			Dom.addClass(this.itemNode, 'tasks-scrum-item-drag');
		}
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

	setCompleted(value: boolean)
	{
		this.completed = Boolean(value);
	}

	isCompleted(): boolean
	{
		return this.completed;
	}

	setMoveActivity(value: boolean)
	{
		this.moveActivity = value;
	}

	activateDecompositionMode()
	{
		this.decompositionMode = true;
		Dom.addClass(this.itemNode, 'tasks-scrum-item-decomposition-mode');
	}

	deactivateDecompositionMode()
	{
		this.decompositionMode = false;
		Dom.removeClass(this.itemNode, 'tasks-scrum-item-decomposition-mode');
	}

	isDisabled(): boolean
	{
		return Boolean(this.disableStatus);
	}

	getItemId()
	{
		return this.itemId;
	}

	getName(): string
	{
		return this.name;
	}

	getItemType()
	{
		return this.itemType;
	}

	getItemNode(): HTMLElement|null
	{
		return this.itemNode;
	}

	getStoryPoints(): StoryPoints
	{
		return this.storyPoints;
	}

	setStoryPoints(storyPoints: string)
	{
		this.storyPoints.setPoints(storyPoints);
	}

	getSort()
	{
		return this.sort;
	}

	setSort(sort)
	{
		this.sort = parseInt(sort, 10);
	}

	getParentId()
	{
		return this.parentId;
	}

	setParentId(parentId)
	{
		this.parentId = parseInt(parentId, 10);
	}

	getSourceId()
	{
		return this.sourceId;
	}

	setSourceId(sourceId)
	{
		this.sourceId = parseInt(sourceId, 10);
	}

	getParentSourceId(): Number
	{
		return this.parentSourceId;
	}

	setParentSourceId(sourceId)
	{
		this.parentSourceId = parseInt(sourceId, 10);
	}

	setParentEntity(entityId, entityType)
	{
		this.entityId = entityId;
		this.entityType = entityType;
	}

	getEntityId(): Number
	{
		return this.entityId;
	}

	setAllowedActions(allowedActions: AllowedActions)
	{
		this.allowedActions = (Type.isPlainObject(allowedActions) ? allowedActions : {});
	}

	removeYourself()
	{
		Dom.remove(this.itemNode);
		this.itemNode = null
	}

	render(): HTMLElement
	{
		return Tag.render`
		<div data-item-id="${Text.encode(this.itemId)}" data-sort="${Text.encode(this.sort)}" class="tasks-scrum-item">
			<div class="tasks-scrum-item-inner">
				<div class="tasks-scrum-item-group-mode-container">
					<input type="checkbox">
				</div>
				<div class="tasks-scrum-item-name">
					${this.renderName()}
					${(this.taskCounts && this.itemType === 'task' ? this.taskCounts.renderIndicators() : '')}
				</div>
				<div class="tasks-scrum-item-params">
					${this.renderResponsible()}
					${this.renderStoryPoints()}
				</div>
			</div>
			${this.renderTags()}
		</div>
		`;
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

	onAfterAppend(container)
	{
		this.itemNode = container.querySelector('[data-item-id="'+this.itemId+'"]');
		if (!this.itemNode)
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
			Event.bind(this.itemNode, 'click', () => this.emit('showTask'));
			return;
		}

		// todo tmp block drag
		if (!this.itemNode.classList.contains('tasks-scrum-item-drag'))
		{
			Dom.addClass(this.itemNode, 'tasks-scrum-item-drag');
		}

		Event.unbindAll(this.itemNode);
		Event.bind(this.itemNode, 'click', this.onItemClick.bind(this));

		const nameNode = this.itemNode.querySelector('.tasks-scrum-item-name');
		Event.unbindAll(nameNode);
		Event.bind(nameNode, 'click', this.onNameClick.bind(this));

		const responsibleNode = this.itemNode.querySelector('.tasks-scrum-item-responsible');
		Event.unbindAll(responsibleNode);
		Event.bind(responsibleNode, 'click', this.onResponsibleClick.bind(this));

		const storyPointsNode = this.itemNode.querySelector('.tasks-scrum-item-story-points');
		Event.unbindAll(storyPointsNode);
		Event.bind(storyPointsNode, 'click', this.onStoryPointsClick.bind(this));

		if (this.completed)
		{
			const nameTextNode = nameNode.querySelector('.ui-ctl-element');
			Dom.style(nameTextNode, 'textDecoration', 'line-through');
			this.setDisableStatus(true);
		}
	}

	isShowIndicators(): Boolean
	{
		return Boolean(this.attachedFilesCount || this.checkListAll || this.newCommentsCount);
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

		this.clickToGroupModeCheckbox();

		this.showActionsPanel(event);
	}

	isClickOnEditableName(event): boolean
	{
		return (event.target.classList.contains('ui-ctl-element') && this.allowedActions[this.actionsMap.taskEdit]);
	}

	onNameClick(event)
	{
		if (this.isGroupMode())
		{
			this.clickToGroupModeCheckbox();
			this.showActionsPanel(event);
			return;
		}

		if (!this.allowedActions[this.actionsMap.taskEdit])
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

		Dom.addClass(this.itemNode, 'tasks-scrum-item-edit-mode');
		Dom.toggleClass(borderNode, 'ui-ctl-no-border');
		valueNode.contentEditable = 'true';

		this.placeCursorAtEnd(valueNode);

		Event.bind(valueNode, 'keydown', this.blockEnterInput.bind(valueNode));

		Event.bindOnce(valueNode, 'blur', () => {
			Event.unbind(valueNode, 'keydown', this.blockEnterInput.bind(valueNode));

			Dom.removeClass(this.itemNode, 'tasks-scrum-item-edit-mode');
			Dom.addClass(borderNode, 'ui-ctl-no-border');
			valueNode.contentEditable = 'false';

			const newValue = valueNode.textContent.trim();
			if (oldValue === newValue)
			{
				return;
			}
			this.emit('updateItem', {
				itemId: this.itemId,
				itemType: this.itemType,
				name: newValue
			});
			this.name = newValue;
		}, true);
	}

	clickToGroupModeCheckbox()
	{
		if (this.isGroupMode())
		{
			const groupModeContainer = this.itemNode.querySelector('.tasks-scrum-item-group-mode-container');
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

		const selector = new BX.Tasks.Integration.Socialnetwork.NetworkSelector({
			scope: responsibleNode,
			id: 'tasks-scrum-change-responsible-' + this.itemId,
			mode: 'user',
			query: false,
			useSearch: true,
			useAdd: false,
			parent: this,
			popupOffsetLeft: 10
		});
		selector.bindEvent('item-selected', (data) => {
			this.responsible = {
				id: data.id,
				name: data.nameFormatted,
				photo: {
					src: data.avatar
				}
			};
			this.updateResponsible();
			selector.close();
			this.emit('changeTaskResponsible');
		});

		selector.open();
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
				itemId: this.itemId,
				itemType: this.itemType,
				storyPoints: newValue
			});
			this.emit('updateStoryPoints');
			this.emit('updateActiveSprintStoryPoints');
		}, true);
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
			bindElement: this.itemNode,
			itemList: {
				task: {
					activity: (this.itemType === 'task' && !this.isGroupMode()),
					callback: () => {
						this.emit('showTask');
						this.actionsPanel.destroy();
					},
				},
				attachment: {
					activity: (
						!this.disableStatus &&
						this.allowedActions[this.actionsMap.taskEdit] &&
						!this.isGroupMode()
					),
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
					activity: (!this.decompositionMode && this.moveActivity),
					callback: (event) => {
						this.emit('move', event.currentTarget);
					},
				},
				sprint: {
					activity: (!this.disableStatus && !this.decompositionMode),
					callback: (event) => {
						this.emit('moveToSprint', event.currentTarget);
					},
				},
				backlog: {
					activity: (this.entityType === 'sprint' && !this.decompositionMode),
					callback: () => {
						this.emit('moveToBacklog');
						this.actionsPanel.destroy();
					},
				},
				tags: {
					activity: (!this.disableStatus && !this.decompositionMode),
					callback: (event) => {
						this.emit('showTagSearcher', event.currentTarget);
					},
				},
				epic: {
					activity: (!this.disableStatus && !this.decompositionMode),
					callback: (event) => {
						this.emit('showEpicSearcher', event.currentTarget);
					},
				},
				decomposition: {
					activity: (!this.disableStatus && !this.decompositionMode && !this.isGroupMode()),
					callback: (event) => {
						this.emit('startDecomposition');
						this.actionsPanel.destroy();
					},
				},
				remove: {
					activity: (!this.decompositionMode && this.allowedActions[this.actionsMap.taskRemove]),
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

	setItemId(itemId: number|string)
	{
		this.itemId = (
			Type.isInteger(itemId) ? parseInt(itemId, 10) :
			Type.isString(itemId) ? itemId : Text.getRandom()
		);
		if (this.itemNode)
		{
			this.itemNode.dataset.itemId = this.itemId;
		}
	}

	setEpicAndTags(epic, tags: Array)
	{
		this.epic = (Type.isPlainObject(epic) || epic === null ? epic : this.epic);
		this.tags = (Type.isArray(tags) ? tags : this.tags);

		this.updateTagsContainer();
	}

	setTags(tags)
	{
		this.tags = (Type.isArray(tags) ? tags : []);
	}

	getTags()
	{
		return this.tags;
	}

	getEpic(): EpicType
	{
		return this.epic;
	}

	gerResponsible()
	{
		return this.responsible;
	}

	setResponsible(responsible: Responsible)
	{
		this.responsible = responsible;
		this.updateResponsible();
	}

	updateTagsContainer()
	{
		const newContainer = Tag.render`
			<div class="tasks-scrum-item-tags-container">
				${this.getEpicTag()}
				${this.getListTagNodes()}
			</div>
		`;

		const tagsContainerNode = this.itemNode.querySelector('.tasks-scrum-item-tags-container');
		if (tagsContainerNode)
		{
			Dom.replace(tagsContainerNode, newContainer);
		}
		else
		{
			Dom.append(newContainer, this.itemNode);
		}
	}

	updateResponsible()
	{
		const responsibleNode = this.itemNode.querySelector('.tasks-scrum-item-responsible');

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
