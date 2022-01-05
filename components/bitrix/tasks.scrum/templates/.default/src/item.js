import {Type, Dom, Event, Tag, Text, Loc} from 'main.core';
import {EventEmitter} from 'main.core.events';
import {ActionsPanel} from './actions.panel';
import {Label} from 'ui.label';
import {DiskManager} from './disk.manager';
import {TaskCounts} from './taskcounts';
import {MessageBox} from 'ui.dialogs.messagebox';

import './css/item.css';

export class Item extends EventEmitter
{
	constructor(item)
	{
		super(item);

		this.setEventNamespace('BX.Tasks.Scrum.Item');

		this.itemNode = null;

		this.itemId = (item.itemId ? item.itemId : Text.getRandom());
		this.itemType = item.itemType;
		this.name = item.name;
		this.responsible = (item.responsible ? item.responsible : '');
		this.entityId = item.entityId;
		this.entityType = item.entityType;
		this.parentId = (item.parentId ? item.parentId : 0);
		this.sort = item.sort;
		this.storyPoints = (Type.isUndefined(item.storyPoints) ? '' : item.storyPoints);
		this.sourceId = (item.sourceId ? item.sourceId : 0);
		this.parentSourceId = (item.parentSourceId ? item.parentSourceId : 0);

		if (this.itemType === 'task')
		{
			this.taskCounts = new TaskCounts(item);
		}

		this.completed = (item.completed === 'Y');

		this.epic = (Type.isPlainObject(item.epic) ? item.epic : null);
		this.tags = (Type.isArray(item.tags) ? item.tags : []);
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

	setMoveActivity(value: Boolean)
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

	isDisabled()
	{
		return Boolean(this.disableStatus);
	}

	getItemId()
	{
		return this.itemId;
	}

	getItemType()
	{
		return this.itemType;
	}

	getItemNode()
	{
		return this.itemNode;
	}

	getStoryPoints()
	{
		return this.storyPoints;
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

	removeYourself()
	{
		Dom.remove(this.itemNode);
	}

	render(): HTMLElement
	{
		const dragnDropNode = (this.isDisabled() ? '' : '<div class="tasks-scrum-dragndrop"></div>');
		return Tag.render`
			<div data-item-id="${Text.encode(this.itemId)}" data-sort=
				"${Text.encode(this.sort)}" class="tasks-scrum-item">
				${dragnDropNode}
				<div class="tasks-scrum-item-inner">
					<div class="tasks-scrum-item-name">
						<div class="tasks-scrum-item-name-field ui-ctl ui-ctl-xs ui-ctl-textbox ui-ctl-no-border">
							<div class="ui-ctl-element" contenteditable="false">
								${Text.encode(this.name)}
							</div>
						</div>
						${this.taskCounts.createIndicators()}
					</div>
					<div class="tasks-scrum-item-params">
						<div class="ui-icon ui-icon-common-user tasks-scrum-item-responsible"><i></i></div>
						<div class="tasks-scrum-item-story-points">
							<div class="tasks-scrum-item-story-points-field ui-ctl ui-ctl-xs ui-ctl-textbox ui-ctl-auto ui-ctl-no-border">
								<div class="ui-ctl-element" contenteditable="false">
									${Text.encode(this.storyPoints)}
								</div>
							</div>
						</div>
					</div>
				</div>
				${this.getTagsContainer()}
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
			size: Label.Size.SM,
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
			return tagLabel.getContainer();
		});
	}

	getTagsContainer(): ?HTMLElement
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

	onAfterAppend(container)
	{
		this.itemNode = container.querySelector('[data-item-id="'+this.itemId+'"]');

		const nameNode = this.itemNode.querySelector('.tasks-scrum-item-name');
		Event.bind(nameNode, 'click', this.onNameClick.bind(this));

		const tagsNode = this.itemNode.querySelector('.tasks-scrum-item-tags-container');
		Event.bind(tagsNode, 'click', this.onTagsClick.bind(this));

		const responsibleNode = this.itemNode.querySelector('.tasks-scrum-item-responsible');
		Event.bind(responsibleNode, 'click', this.onResponsibleClick.bind(this));

		const storyPointsNode = this.itemNode.querySelector('.tasks-scrum-item-story-points');
		Event.bind(storyPointsNode, 'click', this.onStoryPointsClick.bind(this));

		if (this.completed)
		{
			const nameTextNode = nameNode.querySelector('.ui-ctl-element');
			Dom.style(nameTextNode, 'textDecoration', 'line-through');
			this.setDisableStatus(true);
		}

		this.updateResponsible();

		if (this.taskCounts)
		{
			this.taskCounts.onAfterAppend();
		}
	}

	isShowIndicators(): Boolean
	{
		return this.attachedFilesCount || this.checkListAll || this.newCommentsCount;
	}

	onNameClick(event)
	{
		const targetNode = event.target;
		if (Dom.hasClass(targetNode, 'ui-ctl-element') && targetNode.contentEditable === 'true')
		{
			return;
		}

		this.showActionsPanel(event);

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

	onTagsClick(event)
	{
		this.showActionsPanel(event);
	}

	onResponsibleClick(event)
	{
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
			this.emit('updateItem', {
				itemId: this.itemId,
				itemType: this.itemType,
				storyPoints: newValue
			});
			this.emit('updateStoryPoints', {
				oldValue: this.storyPoints,
				newValue: newValue
			});
			this.storyPoints = newValue;
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
		if (this.actionsPanel && this.actionsPanel.isShown())
		{
			return;
		}
		event.stopPropagation();

		this.actionsPanel = new ActionsPanel({
			bindElement: this.itemNode,
			itemList: {
				task: {
					activity: (this.itemType === 'task'),
					callback: () => {
						this.emit('showTask');
						this.actionsPanel.destroy();
					},
				},
				attachment: {
					activity: (this.itemType === 'task' && !this.disableStatus),
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
					activity: (this.entityType === 'backlog' && !this.disableStatus && !this.decompositionMode),
					callback: (event) => {
						this.emit('moveToSprint', event.currentTarget);
					},
				},
				backlog: {
					activity: (this.entityType === 'sprint' && !this.disableStatus && !this.decompositionMode),
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
					activity: (!this.disableStatus && !this.decompositionMode),
					callback: (event) => {
						this.emit('startDecomposition');
						this.actionsPanel.destroy();
					},
				},
				remove: {
					activity: (!this.decompositionMode),
					callback: () => {
						MessageBox.confirm(
							Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TASK'),
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

	setItemId(itemId)
	{
		this.itemId = (Type.isInteger(itemId) ? parseInt(itemId, 10) : 0);
		this.itemNode.dataset.itemId = this.itemId;
	}

	setEpicAndTags(epic, tags)
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

	getEpic()
	{
		return this.epic;
	}

	gerResponsible()
	{
		return this.responsible;
	}

	setResponsible(responsible)
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

		Event.bind(newContainer, 'click', this.onTagsClick.bind(this));
	}

	updateResponsible()
	{
		const responsibleNode = this.itemNode.querySelector('.tasks-scrum-item-responsible');

		if (!responsibleNode)
		{
			return;
		}

		if (this.entityType === 'backlog')
		{
			responsibleNode.style.display = 'none';
		}
		else if (this.entityType === 'sprint')
		{
			responsibleNode.style.display = 'block';
		}

		Dom.attr(responsibleNode, 'title', this.responsible.name);
		if (this.responsible.photo && this.responsible.photo.src)
		{
			Dom.style(responsibleNode.firstElementChild, 'backgroundImage', 'url('+this.responsible.photo.src+')');
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
