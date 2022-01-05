import {Loc, Dom, Tag, Type, Event, Text} from 'main.core';
import {MessageBox} from 'ui.dialogs.messagebox';
import {RequestSender} from './request.sender';
import {Backlog} from './backlog';
import {Sprint} from './sprint';
import {Entity} from './entity';
import {Item} from './item';
import {Kanban} from './kanban';
import {Popup, Menu} from 'main.popup';
import {Button} from 'ui.buttons';
import {Draggable} from 'ui.draganddrop.draggable';
import {SprintDate} from './sprint.date';
import {SprintPopup} from './sprint.popup';
import {SidePanel} from './side.panel';
import {Epic} from './epic';
import {TagSearcher} from './tag.searcher';
import {Decomposition} from './decomposition';
import {Filter} from './filter';

import './css/base.css';

export class Scrum
{
	constructor(options)
	{
		this.defaultSprintDuration = options.defaultSprintDuration;
		this.pathToTask = options.pathToTask;

		this.requestSender = new RequestSender({
			signedParameters: options.signedParameters,
			debugMode: options.debugMode
		});

		this.activeSprintId = parseInt(options.activeSprintId, 10);
		this.tabs = options.tabs;
		this.activeTab = options.activeTab;

		if (this.activeTab === 'activeSprint')
		{
			this.kanban = new Kanban(options);
		}
		else
		{
			this.backlog = new Backlog(options.backlog);

			this.sprints = new Map();
			options.sprints.forEach((sprintData) => {
				sprintData.defaultSprintDuration = this.defaultSprintDuration;
				const sprint = new Sprint(sprintData);
				this.sprints.set(sprint.getId(), sprint);
			});

			this.sidePanel = new SidePanel();

			this.tagSearcher = new TagSearcher({
				requestSender: this.requestSender
			});
			Object.values(options.tags.epic).forEach((epic) => {
				this.tagSearcher.addEpicToSearcher(epic);
			});
			Object.values(options.tags.task).forEach((tagName) => {
				this.tagSearcher.addTagToSearcher(tagName);
			});

			this.epic = new Epic({
				entity: this.backlog,
				requestSender: this.requestSender,
				sidePanel: this.sidePanel
			});
			this.epic.subscribe('onAfterCreateEpic', (baseEvent) => {
				const response = baseEvent.getData();
				this.tagSearcher.addEpicToSearcher(response.data);
			});

			this.filter = new Filter({
				filterId: options.filterId,
				scrumManager: this,
				requestSender: this.requestSender
			});

			this.bindHandlers();
		}
	}

	renderTo(container)
	{
		if (!Type.isDomNode(container))
		{
			throw new Error('Scrum: HTMLElement for Scrum not found');
		}

		this.scrumContainer = container;

		Dom.append(this.backlog.render(), this.scrumContainer);
		this.backlog.onAfterAppend();

		Dom.append(this.renderSprintsContainer(), this.scrumContainer);
		this.sprints.forEach((sprint) => {
			sprint.onAfterAppend();
		});

		this.sprintCreatingButtonNode = document.getElementById(this.sprintCreatingButtonNodeId);
		this.sprintCreatingDropZoneNode = document.getElementById(this.sprintCreatingDropZoneNodeId);
		this.sprintListNode = document.getElementById(this.sprintListNodeId);

		Event.bind(this.sprintCreatingButtonNode, 'click', this.createSprint.bind(this));

		this.setDraggable();
	}

	setDraggable()
	{
		const itemContainers = [];
		itemContainers.push(this.backlog.getListItemsNode());
		if (this.sprintCreatingDropZoneNode)
		{
			itemContainers.push(this.sprintCreatingDropZoneNode);
		}
		this.sprints.forEach((sprint) => {
			if (!sprint.isDisabled())
			{
				itemContainers.push(sprint.getListItemsNode());
			}
		});
		this.draggableItems = new Draggable({
			container: itemContainers,
			draggable: '.tasks-scrum-item',
			dragElement: '.tasks-scrum-dragndrop',
			type: Draggable.DROP_PREVIEW,
		});
		this.draggableItems.subscribe('end', (baseEvent) => {
			const dragEndEvent = baseEvent.getData();
			this.onItemMove(dragEndEvent);
		});

		this.draggableSprints = new Draggable({
			container: this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list'),
			draggable: '.tasks-scrum-sprint',
			dragElement: '.tasks-scrum-sprint-dragndrop',
			type: Draggable.DROP_PREVIEW,
		});
		this.draggableSprints.subscribe('end', (baseEvent) => {
			const dragEndEvent = baseEvent.getData();
			this.onSprintMove(dragEndEvent);
		});
	}

	bindHandlers(newSprint)
	{
		const createTaskItem = (baseEvent) => {
			const data = baseEvent.getData();
			const entity = baseEvent.getTarget();
			const inputObject = data.inputObject;
			const inputValue = data.value;

			const newItem = this.createItem('task', inputValue);
			newItem.setParentEntity(entity.getId(), entity.getEntityType());
			newItem.setSort(1);

			this.appendItem(newItem, entity.getListItemsNode(), inputObject.getNode());

			newItem.setParentId(inputObject.getEpicId());

			this.sendRequestToCreateTask(entity, newItem, inputValue).then((response) => {
				this.fillItemAfterCreation(newItem, response.data);
				response.data.tags.forEach((tag) => {
					this.tagSearcher.addTagToSearcher(tag);
				});
				entity.setItem(newItem);
			});
		};
		const onUpdateItem = (baseEvent) => {
			this.requestSender.updateItem(baseEvent.getData());
		};
		const onShowTask = (baseEvent) => {
			const item = baseEvent.getData();
			this.sidePanel.openSidePanelByUrl(this.pathToTask.replace('#task_id#', item.sourceId));
		};
		const onMoveItem = (baseEvent) => {
			const data = baseEvent.getData();
			this.moveItem(data.item, data.button);
		};
		const onMoveToSprint = (baseEvent) => {
			const data = baseEvent.getData();
			this.moveToSprint(data.item, data.button);
		};
		const onMoveToBacklog = (baseEvent) => {
			const data = baseEvent.getData();
			this.moveToBacklog(data.sprint, data.item);
		};
		const onAttachFilesToTask = (baseEvent) => {
			const data = baseEvent.getData();
			this.requestSender.attachFilesToTask({
				taskId: data.item.getSourceId(),
				attachedIds: data.attachedIds,
			}).then((response) => {
				data.item.updateIndicators(response.data);
			});
		};
		const onRemoveItem = (baseEvent) => {
			this.requestSender.removeItem({
				itemId: baseEvent.getData().getItemId(),
				itemType: baseEvent.getData().getItemType(),
				sourceId: baseEvent.getData().getSourceId(),
				sortInfo: this.calculateSort(baseEvent.getTarget().getListItemsNode())
			});
		};
		const onStartSprint = (baseEvent) => {
			this.showStartSprintPopup(baseEvent.getTarget());
		};
		const onCompleteSprint = (baseEvent) => {
			const sprint = baseEvent.getTarget();
			const sprintPopup = new SprintPopup({
				sprints: this.sprints
			});
			sprintPopup.showCompletePopup(sprint).then((requestData) => {
				this.requestSender.completeSprint(requestData).then((response) => {
					location.reload();
				}).catch((response) => {});
			});
		};
		const onChangeTaskResponsible = (baseEvent) => {
			this.requestSender.changeTaskResponsible({
				itemId: baseEvent.getData().getItemId(),
				itemType: baseEvent.getData().getItemType(),
				sourceId: baseEvent.getData().getSourceId(),
				responsible: baseEvent.getData().gerResponsible(),
			});
		};
		const onRemoveSprint = (baseEvent) => {
			const sprint = baseEvent.getTarget();
			this.sprints.delete(sprint.getId());
			this.requestSender.removeSprint({
				sprintId: sprint.getId(),
				sortInfo: this.calculateSprintSort()
			});
		};
		const onChangeSprintName = (baseEvent) => {
			this.requestSender.changeSprintName(baseEvent.getData());
		};
		const onChangeSprintDeadline = (baseEvent) => {
			this.requestSender.changeSprintDeadline(baseEvent.getData());
		};
		const onOpenAddEpicForm = (baseEvent) => {
			this.epic.openAddForm();
		};
		const onOpenListEpicGrid = (baseEvent) => {
			this.epic.openEpicsList();
			this.epic.subscribe('onAfterEditEpic', (innerBaseEvent) => {
				const response = innerBaseEvent.getData();
				const updatedEpicInfo = response.data;
				const oldEpicInfo = this.epic.getCurrentEpic();
				this.getAllItems().forEach((item) => {
					const itemEpic = item.getEpic();
					if (itemEpic && itemEpic.name === oldEpicInfo.name)
					{
						item.setEpicAndTags(updatedEpicInfo);
					}
				});
				this.tagSearcher.removeEpicFromSearcher(oldEpicInfo);
				this.tagSearcher.addEpicToSearcher(updatedEpicInfo);
			});
		};
		const onShowTagSearcher = (baseEvent) => {
			const data = baseEvent.getData();
			const item = data.item;
			const actionsPanelButton = data.button;
			this.tagSearcher.showTagsDialog(item, actionsPanelButton);
		};
		const onShowEpicSearcher = (baseEvent) => {
			const data = baseEvent.getData();
			const item = data.item;
			const actionsPanelButton = data.button;
			this.tagSearcher.showEpicDialog(item, actionsPanelButton);
		};
		const onStartDecomposition = (baseEvent) => {
			const entity = baseEvent.getTarget();
			const parentItem = baseEvent.getData();

			const decomposition = new Decomposition();
			decomposition.subscribe('tagsSearchOpen', onTagsSearchOpen);
			decomposition.subscribe('tagsSearchClose', onTagsSearchClose);
			decomposition.subscribe('createItem', (innerBaseEvent) => {
				const inputValue = innerBaseEvent.getData();
				const decomposedItems = decomposition.getDecomposedItems();
				const lastDecomposedItem = Array.from(decomposedItems).pop();

				const newItem = this.createItem(parentItem.getItemType(), inputValue);
				newItem.setParentEntity(entity.getId(), entity.getEntityType());
				newItem.setParentId(parentItem.getParentId());
				newItem.setParentSourceId(parentItem.getSourceId());
				newItem.setSort(lastDecomposedItem.getSort() + 1);
				newItem.setTags(parentItem.getTags());

				this.appendItem(newItem, entity.getListItemsNode(), lastDecomposedItem.getItemNode());

				decomposition.addDecomposedItem(newItem);

				this.sendRequestToCreateTask(entity, newItem, inputValue).then((response) => {
					this.fillItemAfterCreation(newItem, response.data);
					response.data.tags.forEach((tag) => {
						this.tagSearcher.addTagToSearcher(tag);
					});
					entity.setItem(newItem);
				});
			});

			decomposition.decomposeItem(parentItem);
		};
		const onTagsSearchOpen = (baseEvent) => {
			const data = baseEvent.getData();
			const inputObject = data.inputObject;
			const enteredHashTagName = data.enteredHashTagName;
			this.tagSearcher.showTagsSearchDialog(inputObject, enteredHashTagName);
		};
		const onTagsSearchClose = () => {
			this.tagSearcher.closeTagsSearchDialog();
		};
		const onEpicSearchOpen = (baseEvent) => {
			const data = baseEvent.getData();
			const inputObject = data.inputObject;
			const enteredHashEpicName = data.enteredHashEpicName;
			this.tagSearcher.showEpicSearchDialog(inputObject, enteredHashEpicName);
		};
		const onEpicSearchClose = () => {
			this.tagSearcher.closeEpicSearchDialog();
		};

		const subscribeToSprint = (sprint) => {
			sprint.subscribe('createTaskItem', createTaskItem);
			sprint.subscribe('updateItem', onUpdateItem);
			sprint.subscribe('showTask', onShowTask);
			sprint.subscribe('moveItem', onMoveItem);
			sprint.subscribe('moveToBacklog', onMoveToBacklog);
			sprint.subscribe('removeItem', onRemoveItem);
			sprint.subscribe('startSprint', onStartSprint);
			sprint.subscribe('completeSprint', onCompleteSprint);
			sprint.subscribe('changeTaskResponsible', onChangeTaskResponsible);
			sprint.subscribe('removeSprint', onRemoveSprint);
			sprint.subscribe('changeSprintName', onChangeSprintName);
			sprint.subscribe('changeSprintDeadline', onChangeSprintDeadline);
			sprint.subscribe('attachFilesToTask', onAttachFilesToTask);
			sprint.subscribe('showTagSearcher', onShowTagSearcher);
			sprint.subscribe('showEpicSearcher', onShowEpicSearcher);
			sprint.subscribe('startDecomposition', onStartDecomposition);
			sprint.subscribe('tagsSearchOpen', onTagsSearchOpen);
			sprint.subscribe('tagsSearchClose', onTagsSearchClose);
			sprint.subscribe('epicSearchOpen', onEpicSearchOpen);
			sprint.subscribe('epicSearchClose', onEpicSearchClose);
		};

		if (newSprint)
		{
			subscribeToSprint(newSprint);
			return;
		}

		this.backlog.subscribe('createTaskItem', createTaskItem);
		this.backlog.subscribe('updateItem', onUpdateItem);
		this.backlog.subscribe('showTask', onShowTask);
		this.backlog.subscribe('moveItem', onMoveItem);
		this.backlog.subscribe('moveToSprint', onMoveToSprint);
		this.backlog.subscribe('removeItem', onRemoveItem);
		this.backlog.subscribe('changeTaskResponsible', onChangeTaskResponsible);
		this.backlog.subscribe('openAddEpicForm', onOpenAddEpicForm);
		this.backlog.subscribe('openListEpicGrid', onOpenListEpicGrid);
		this.backlog.subscribe('attachFilesToTask', onAttachFilesToTask);
		this.backlog.subscribe('showTagSearcher', onShowTagSearcher);
		this.backlog.subscribe('showEpicSearcher', onShowEpicSearcher);
		this.backlog.subscribe('startDecomposition', onStartDecomposition);
		this.backlog.subscribe('tagsSearchOpen', onTagsSearchOpen);
		this.backlog.subscribe('tagsSearchClose', onTagsSearchClose);
		this.backlog.subscribe('epicSearchOpen', onEpicSearchOpen);
		this.backlog.subscribe('epicSearchClose', onEpicSearchClose);

		this.sprints.forEach((sprint) => {
			subscribeToSprint(sprint);
		});
	}

	/**
	 * @returns {HTMLElement}
	 */
	renderSprintsContainer()
	{
		const createCreatingButton = () => {
			this.sprintCreatingButtonNodeId = 'tasks-scrum-sprint-creating-button';
			return Tag.render`
				<div id="${this.sprintCreatingButtonNodeId}" class="tasks-scrum-sprint-create ui-btn ui-btn-md ui-btn-themes ui-btn-light-border ui-btn-icon-add">
					<span>${Loc.getMessage('TASKS_SCRUM_SPRINT_ADD')}</span>
				</div>
			`;
		};

		const createCreatingDropZone = () => {
			if (this.sprints.size)
			{
				return '';
			}
			this.sprintCreatingDropZoneNodeId = 'tasks-scrum-sprint-creating-drop-zone';
			return Tag.render`
				<div id="${this.sprintCreatingDropZoneNodeId}">
					<label class="ui-ctl ui-ctl-file-drop tasks-scrum-sprint-sprint-add-drop">
						<div class="ui-ctl-label-text">
							<small>${Loc.getMessage('TASKS_SCRUM_SPRINT_ADD_DROP')}</small>
						</div>
					</label>
				</div>
			`;
		};

		const createSprintsList = () => {
			this.sprintListNodeId = 'tasks-scrum-sprint-list';
			return Tag.render`
				<div id="${this.sprintListNodeId}" class="tasks-scrum-sprint-list">
					<div class="tasks-scrum-sprint-active-list">
						${[...this.sprints.values()].map((sprint) => {
							if (sprint.isActive())
							{
								return sprint.render();
							}
							else
							{
								return '';
							}
						})}
					</div>
					<div class="tasks-scrum-sprint-planned-list">
						${[...this.sprints.values()].map((sprint) => {
							if (sprint.isPlanned())
							{
								return sprint.render();
							}
							else
							{
								return '';
							}
						})}
					</div>
					<div class="tasks-scrum-sprint-completed-list">
						${[...this.sprints.values()].map((sprint) => {
							if (sprint.isCompleted())
							{
								return sprint.render();
							}
							else
							{
								return '';
							}
						})}
					</div>
				</div>
			`;
		};

		return Tag.render`
			<div class="tasks-scrum-sprints">
				${createCreatingButton()}
				${createCreatingDropZone()}
				${createSprintsList()}
			</div>
		`;
	}

	createSprint()
	{
		Dom.remove(this.sprintCreatingDropZoneNode);

		const countSprints = this.sprints.size;
		const title = Loc.getMessage('TASKS_SCRUM_SPRINT_NAME').replace('%s', countSprints + 1);
		const storyPoints = 0;
		const dateStart = Math.floor(Date.now() / 1000);
		const dateEnd = (Math.floor(Date.now() / 1000) + parseInt(this.defaultSprintDuration, 10));

		const sprintListNode = this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list');

		const data = {
			name: title,
			sort: 1,
			dateStart: dateStart,
			dateEnd: dateEnd,
			sortInfo: this.calculateSprintSort(1)
		};

		return this.requestSender.createSprint(data)
			.then((response) => {
				const sprint = new Sprint({
					id: response.data.sprintId,
					name: title,
					sort: 1,
					dateStart: dateStart,
					dateEnd: dateEnd,
					storyPoints: storyPoints
				});
				if (sprintListNode.children.length)
				{
					Dom.insertBefore(sprint.render(), sprintListNode.firstElementChild);
				}
				else
				{
					Dom.insertBefore(sprint.render(), sprintListNode);
				}
				sprint.onAfterAppend();
				sprint.getSprintNode().scrollIntoView(true);
				this.sprints.set(sprint.getId(), sprint);
				this.bindHandlers(sprint);
				this.draggableItems.addContainer(sprint.getListItemsNode());
				return sprint;
			}).catch((response) => {});
	}

	createItem(itemType: String, value: String): Item
	{
		const valueWithoutTags = value
			.replace(new RegExp('#([^\\s]*)','g'), '')
			.replace(new RegExp('@([^\\s]*)','g'),'');

		return new Item({
			'itemId': '',
			'itemType': itemType,
			'name': valueWithoutTags
		});
	}

	appendItem(item: Item, entityListNode: HTMLElement, bindItemNode: HTMLElement)
	{
		this.appendItemAfterItem(item.render(), bindItemNode);
		item.onAfterAppend(entityListNode);
	}

	sendRequestToCreateTask(entity: Entity, item: Item, value: String): Promise
	{
		const requestData = {
			'itemType': item.getItemType(),
			'name': value,
			'entityId': item.getEntityId(),
			'entityType': entity.getEntityType(),
			'parentId': item.getParentId(),
			'sort': item.getSort(),
			'storyPoints': item.getStoryPoints(),
			'tags': item.getTags(),
			'epic': item.getEpic(),
			'parentSourceId': item.getParentSourceId(),
			'sortInfo': this.calculateSort(entity.getListItemsNode()),
			'isActiveSprint': ((entity.getEntityType() === 'sprint' && entity.isActive()) ? 'Y' : 'N')
		};
		return this.requestSender.createTask(requestData);
	}

	fillItemAfterCreation(item: Item, responseData: Object): Item
	{
		item.setItemId(responseData.itemId);
		item.setEpicAndTags(responseData.epic, responseData.tags);
		item.setResponsible(responseData.responsible);
		item.setSourceId(responseData.sourceId);
	}

	moveToSprint(item, button)
	{
		const getAvailableSprintsToMove = () => {
			const sprints = new Set();
			this.sprints.forEach((sprint) => {
				if (!sprint.isCompleted())
				{
					sprints.add(sprint);
				}
			});
			return sprints;
		};

		const sprints = getAvailableSprintsToMove();

		if (sprints.size > 1)
		{
			this.showMoveToSprintMenu(item, button);
		}
		else
		{
			const moveToNewSprint = () => {
				this.createSprint().then((sprint) => {
					this.moveTo(this.backlog, sprint, item);
				});
			};
			if (sprints.size === 0)
			{
				moveToNewSprint();
			}
			else
			{
				sprints.forEach((sprint) => {
					this.moveTo(this.backlog, sprint, item);
				});
			}
			Dom.remove(button.parentNode);
		}
	}

	moveToBacklog(sprint, item)
	{
		this.moveTo(sprint, this.backlog, item, false);
	}

	moveTo(entityFrom, entityTo, item, after = true)
	{
		const itemNode = item.getItemNode();
		const entityListNode = entityTo.getListItemsNode();
		if (after)
		{
			Dom.append(itemNode, entityListNode);
		}
		else
		{
			this.appendItemAfterItem(itemNode, entityListNode.firstElementChild);
		}

		this.moveItemFromEntityToEntity(item, entityFrom, entityTo);

		this.requestSender.updateItem({
			itemId: item.getItemId(),
			itemType: item.getItemType(),
			entityId: entityTo.getId(),
			fromActiveSprint: ((entityFrom.getEntityType() === 'sprint' && entityFrom.isActive()) ? 'Y' : 'N'),
			toActiveSprint: ((entityTo.getEntityType() === 'sprint' && entityTo.isActive()) ? 'Y' : 'N'),
			sortInfo: {
				...this.calculateSort(entityFrom.getListItemsNode()),
				...this.calculateSort(entityTo.getListItemsNode())
			}
		});
	}

	moveItemFromEntityToEntity(item, entityFrom, entityTo)
	{
		entityFrom.removeItem(item);
		item.setParentEntity(entityTo.getId(), entityTo.getEntityType());
		item.setDisableStatus(false);
		entityTo.setItem(item);
	}

	showMoveToSprintMenu(item, button)
	{
		const id = `item-sprint-action-${item.itemId}`;

		if (this.moveToSprintMenu)
		{
			if (this.moveToSprintMenu.getPopupWindow().getId() === id)
			{
				this.moveToSprintMenu.getPopupWindow().setBindElement(button);
				this.moveToSprintMenu.show();
				return;
			}
			this.moveToSprintMenu.getPopupWindow().destroy();
		}

		this.moveToSprintMenu = new Menu({
			id: id,
			bindElement: button
		});

		this.sprints.forEach((sprint) => {
			if (!sprint.isCompleted())
			{
				this.moveToSprintMenu.addMenuItem({
					text: sprint.getName(),
					onclick: (event, menuItem) => {
						this.moveTo(this.backlog, sprint, item);
						menuItem.getMenuWindow().close();
					}
				});
			}
		});

		this.moveToSprintMenu.show();
	}

	calculateSort(container)
	{
		const listSortInfo = {};

		const items = [...container.querySelectorAll('[data-sort]')];
		let sort = 1;
		items.forEach((itemNode) => {
			const itemId = itemNode.dataset.itemId;
			const item = this.findItemByItemId(itemId);
			if (item)
			{
				item.setSort(sort);
				listSortInfo[itemId] = {
					entityId: container.dataset.entityId,
					sort: sort
				};
				itemNode.dataset.sort = sort;
			}
			sort++;
		});

		return listSortInfo;
	}

	calculateSprintSort(increment = 0)
	{
		const listSortInfo = {};

		const container = this.sprintListNode.querySelector('.tasks-scrum-sprint-planned-list');

		const sprints = [...container.querySelectorAll('[data-sprint-sort]')];
		let sort = 1 + increment;
		sprints.forEach((sprintNode) => {
			const sprintId = sprintNode.dataset.sprintId;
			const sprint = this.findEntityByEntityId(sprintId);
			if (sprint)
			{
				sprint.setSort(sort);
				listSortInfo[sprintId] = {
					sort: sort
				};
				sprintNode.dataset.sprintSort = sort;
				sort++;
			}
		});

		return listSortInfo;
	}

	appendItemAfterItem (newNode, item)
	{
		if (item.nextElementSibling)
		{
			Dom.insertBefore(newNode, item.nextElementSibling);
		}
		else
		{
			Dom.append(newNode, item.parentElement);
		}
	};

	showStartSprintPopup(sprint)
	{
		//todo move to sprint.popup
		this.popupId = 'tasks-scrum-start-sprint' + Text.getRandom();

		const sprintDate = new SprintDate(sprint);
		sprintDate.subscribe('changeSprintDeadline', (baseEvent) => {
			const requestData = baseEvent.getData();
			this.requestSender.changeSprintDeadline(baseEvent.getData()).then((response) => {
				if (requestData.hasOwnProperty('dateStart'))
				{
					sprint.updateDateStartNode(requestData.dateStart);
				}
				else if (requestData.hasOwnProperty('dateEnd'))
				{
					sprint.updateDateEndNode(requestData.dateEnd);
				}
			}).catch((response) => {});
		});

		const getPopupContent = () => {
			return Tag.render`
				<div class="tasks-scrum-sprint-start-popup">
					<div class="tasks-scrum-sprint-start-popup-duration">
						<div class="tasks-scrum-sprint-start-popup-content-title">
							${Loc.getMessage('TASKS_SCRUM_SPRINT_START_POPUP_CONTENT_DURATION')}
						</div>
						<div class="tasks-scrum-sprint-start-popup-content-info">
							${Text.encode(sprintDate.createDate(sprint.getDateStart(), sprint.getDateEnd()))}
						</div>
					</div>
					<div class="tasks-scrum-sprint-start-popup-taken">
						<div class="tasks-scrum-sprint-start-popup-content-title">
							${Loc.getMessage('TASKS_SCRUM_SPRINT_START_POPUP_CONTENT_TAKEN')}
						</div>
						<div class="tasks-scrum-sprint-start-popup-content-info">
							${Loc.getMessage('TASKS_SCRUM_SPRINT_START_POPUP_CONTENT_TAKEN_VALUE')
								.replace('#storyPoints#', sprint.getStoryPoints())}
						</div>
					</div>
				</div>
			`;
		};

		const startSprint = () => {
			this.requestSender.startSprint({
				sprintId: sprint.getId()
			}).then((response) => {
				sprint.setStatus('active');
				this.popup.close();
				location.href = this.tabs['activeSprint'].url;
			}).catch((response) => {
				MessageBox.alert(
					response.errors.shift().message,
					Loc.getMessage('TASKS_SCRUM_SPRINT_START_ERROR_TITLE_POPUP')
				);
			});
		};

		this.popup = new Popup(this.popupId,
			null,
			{
				width: 360,
				autoHide: true,
				closeByEsc: true,
				offsetTop: 0,
				offsetLeft: 0,
				closeIcon: true,
				draggable: true,
				resizable: false,
				lightShadow: true,
				cacheable: false,
				titleBar: Loc.getMessage('TASKS_SCRUM_SPRINT_START_TITLE_POPUP').replace('#name#', sprint.getName()),
				content: getPopupContent(),
				buttons: [
					new Button({
						text: Loc.getMessage('TASKS_SCRUM_SPRINT_START_BUTTON_START_POPUP'),
						color: Button.Color.PRIMARY,
						events: {
							click: () => startSprint()
						}
					}),
					new Button({
						text: Loc.getMessage('TASKS_SCRUM_SPRINT_START_BUTTON_CANCEL_POPUP'),
						color: Button.Color.LINK,
						events: {
							click: () => this.popup.close()
						}
					}),
				]
			});

		this.popup.show();

		sprintDate.onAfterAppend();
	}

	onItemMove(dragEndEvent)
	{
		if (!dragEndEvent.endContainer)
		{
			return;
		}

		const sourceContainer = dragEndEvent.sourceContainer;
		const endContainer = dragEndEvent.endContainer;

		if (endContainer === this.sprintCreatingDropZoneNode)
		{
			const createNewSprintAndMoveItem = () => {
				this.createSprint().then((sprint) => {
					const itemNode = dragEndEvent.source;
					const itemId = itemNode.dataset.itemId;
					const item = this.findItemByItemId(itemId);
					this.moveTo(this.backlog, sprint, item);
				});
			};
			createNewSprintAndMoveItem();
			return;
		}

		const sourceEntityId = parseInt(sourceContainer.dataset.entityId, 10);
		const endEntityId = parseInt(endContainer.dataset.entityId, 10);

		if (sourceEntityId === endEntityId)
		{
			const moveInCurrentContainer = () => {
				this.requestSender.updateItemSort({
					sortInfo: this.calculateSort(sourceContainer)
				});
			};
			moveInCurrentContainer();
		}
		else
		{
			const moveInAnotherContainer = () => {
				const itemNode = dragEndEvent.source;
				const itemId = itemNode.dataset.itemId;
				const item = this.findItemByItemId(itemId);
				const sourceEntity = this.findEntityByEntityId(sourceEntityId);
				const endEntity = this.findEntityByEntityId(endEntityId);
				this.moveItemFromEntityToEntity(item, sourceEntity, endEntity);
				this.requestSender.updateItemSort({
					entityId: endEntity.getId(),
					itemId: item.getItemId(),
					itemType: item.getItemType(),
					fromActiveSprint: ((sourceEntity.getEntityType() === 'sprint' && sourceEntity.isActive()) ? 'Y' : 'N'),
					toActiveSprint: ((endEntity.getEntityType() === 'sprint' && endEntity.isActive()) ? 'Y' : 'N'),
					sortInfo: this.calculateSort(endContainer)
				});
			};
			moveInAnotherContainer();
		}
	}

	onSprintMove(dragEndEvent)
	{
		if (!dragEndEvent.endContainer)
		{
			return;
		}

		this.requestSender.updateSprintSort({
			sortInfo: this.calculateSprintSort()
		});
	}

	findItemByItemId(itemId)
	{
		itemId = parseInt(itemId, 10);

		const backlogItems = this.backlog.getItems();
		if (backlogItems.has(itemId))
		{
			return backlogItems.get(itemId);
		}

		const sprint = [...this.sprints.values()].find((sprint) => sprint.getItems().has(itemId));
		if (sprint)
		{
			return sprint.getItems().get(itemId);
		}

		return null;
	}

	findEntityByItemId(itemId)
	{
		itemId = parseInt(itemId, 10);

		const backlogItems = this.backlog.getItems();
		if (backlogItems.has(itemId))
		{
			return this.backlog;
		}

		return [...this.sprints.values()].find((sprint) => sprint.getItems().has(itemId));
	}

	findEntityByEntityId(entityId)
	{
		entityId = parseInt(entityId, 10);

		if (this.backlog.getId() === entityId)
		{
			return this.backlog;
		}

		return [...this.sprints.values()].find((sprint) => sprint.getId() === entityId);
	}

	moveItem(item: Item, button)
	{
		const entity = this.findEntityByItemId(item.getItemId());

		const listToMove = [];

		if (!entity.isFirstItem(item))
		{
			listToMove.push({
				text: Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_UP'),
				onclick: (event, menuItem) => {
					this.moveItemToUp(item, entity.getListItemsNode(), entity.hasInput());
					menuItem.getMenuWindow().close();
				}
			});
		}
		if (!entity.isLastItem(item))
		{
			listToMove.push({
				text: Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_DOWN'),
				onclick: (event, menuItem) => {
					this.moveItemToDown(item, entity.getListItemsNode());
					menuItem.getMenuWindow().close();
				}
			});
		}

		this.showMoveItemMenu(item, button, listToMove);
	}

	showMoveItemMenu(item, button, listToMove)
	{
		const id = `item-move-${item.itemId}`;

		if (this.moveItemMenu)
		{
			this.moveItemMenu.getPopupWindow().destroy();
		}

		this.moveItemMenu = new Menu({
			id: id,
			bindElement: button
		});

		listToMove.forEach((item) => {
			this.moveItemMenu.addMenuItem(item);
		});

		this.moveItemMenu.show();
	}

	moveItemToUp(item, listItemsNode, entityWithInput = true)
	{
		if (entityWithInput)
		{
			this.appendItemAfterItem(item.getItemNode(), listItemsNode.firstElementChild);
		}
		else
		{
			Dom.insertBefore(item.getItemNode(), listItemsNode.firstElementChild);
		}

		this.requestSender.updateItem({
			itemId: item.getItemId(),
			sortInfo: {
				...this.calculateSort(listItemsNode)
			}
		});

	}

	moveItemToDown(item, listItemsNode)
	{
		Dom.append(item.getItemNode(), listItemsNode);

		this.requestSender.updateItem({
			itemId: item.getItemId(),
			sortInfo: {
				...this.calculateSort(listItemsNode)
			}
		});
	}

	getAllItems(): Map
	{
		let items = new Map(this.backlog.getItems());

		[...this.sprints.values()].map((sprint) => items = new Map([...items, ...sprint.getItems()]));

		return items;
	}

	openEpicEditForm(epicId)
	{
		this.epic.openEditForm(epicId);
	}

	openEpicViewForm(epicId)
	{
		this.epic.openViewForm(epicId);
	}

	removeEpic(epicId)
	{
		this.requestSender.removeItem({
			itemId: epicId,
			itemType: 'epic'
		}).then((response) => {
			const epicInfo = response.data;
			this.getAllItems().forEach((item) => {
				const itemEpic = item.getEpic();
				if (itemEpic && itemEpic.name === epicInfo.name)
				{
					item.setEpicAndTags(null);
				}
			});
			this.tagSearcher.removeEpicFromSearcher(epicInfo);
			this.sidePanel.reloadTopSidePanel();
		});
	}

	fadeOutAll()
	{
		this.backlog.fadeOut();
		this.sprints.forEach((sprint) => {
			sprint.fadeOut();
		});
	}

	fadeInAll()
	{
		this.backlog.fadeIn();
		this.sprints.forEach((sprint) => {
			sprint.fadeIn();
		});
	}

	removeItemFromEntities(item: Item)
	{
		this.backlog.removeItem(item);
		this.sprints.forEach((sprint) => {
			sprint.removeItem(item);
		});
		item.removeYourself();
	}

	createTaskItemByItemData(itemData: Object): Item
	{
		return new Item({
			itemId: itemData.itemId,
			itemType: itemData.itemType,
			name: itemData.name,
			entityId: itemData.entityId,
			entityType: itemData.entityType,
			parentId: itemData.parentId,
			sort: itemData.sort,
			storyPoints: itemData.storyPoints,
			sourceId: itemData.sourceId,
			completed: itemData.completed,
			responsible: itemData.responsible,
			attachedFilesCount: itemData.attachedFilesCount,
			checkListComplete: itemData.checkListComplete,
			checkListAll: itemData.checkListAll,
			newCommentsCount: itemData.newCommentsCount,
			epic: itemData.epic,
			tags: itemData.tags
		});
	}

	appendNewItemToEntity(newItem: Item)
	{
		const entity = this.findEntityByEntityId(newItem.getEntityId());
		Dom.append(newItem.render(), entity.getListItemsNode());
		entity.setItem(newItem);
		newItem.onAfterAppend(entity.getListItemsNode());
	}
}