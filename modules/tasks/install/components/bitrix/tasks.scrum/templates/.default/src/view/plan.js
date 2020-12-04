import {Loc, Dom, Tag, Type, Event} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {Loader} from 'main.loader';
import {RequestSender} from '../utility/request.sender';
import {Backlog} from '../entity/backlog/backlog';
import {Sprint} from '../entity/sprint/sprint';
import {Entity} from '../entity/entity';
import {Item} from '../item/item';
import {Menu} from 'main.popup';
import {Draggable} from 'ui.draganddrop.draggable';
import {SprintSidePanel} from '../entity/sprint/sprint.side.panel';
import {SidePanel} from '../service/side.panel';
import {Epic} from '../item/epic/epic';
import {TagSearcher} from '../utility/tag.searcher';
import {Decomposition} from '../item/task/decomposition';
import {Filter} from '../service/filter';
import {MessageBox} from 'ui.dialogs.messagebox';

import '../css/base.css';

type Responsible = {
	name: string,
	pathToUser: string,
	photo: {
		src: string
	}
}

type Params = {
	signedParameters: string,
	debugMode: string,
	views: {
		plan: {
			name: string,
			url: string,
			active: boolean
		},
		activeSprint: {
			name: string,
			url: string,
			active: boolean
		},
		completedSprint: {
			name: string,
			url: string,
			active: boolean
		}
	},
	defaultResponsible: Responsible
}

//todo single responsibility principle
//todo add ItemMover, ItemSorter
export class Plan
{
	constructor(options: Params)
	{
		this.defaultSprintDuration = options.defaultSprintDuration;
		this.pathToTask = options.pathToTask;

		this.requestSender = new RequestSender({
			signedParameters: options.signedParameters,
			debugMode: options.debugMode
		});

		this.activeSprintId = parseInt(options.activeSprintId, 10);
		this.views = options.views;

		this.backlog = Backlog.buildBacklog(options.backlog);

		this.sprints = new Map();
		options.sprints.forEach((sprintData) => {
			sprintData.defaultSprintDuration = this.defaultSprintDuration;
			const sprint = Sprint.buildSprint(sprintData);
			this.sprints.set(sprint.getId(), sprint);
		});

		this.sidePanel = new SidePanel();

		this.tagSearcher = new TagSearcher();
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
			const epic = response.data;
			this.tagSearcher.addEpicToSearcher(epic);
			this.filter.addItemToListTypeField('EPIC', {
				NAME: epic.name.trim(),
				VALUE: String(epic.id)
			});
		});

		this.filter = new Filter({
			filterId: options.filterId,
			scrumManager: this,
			requestSender: this.requestSender
		});
		this.filter.subscribe('applyFilter', this.onApplyFilter.bind(this));

		this.defaultResponsible = options.defaultResponsible;

		this.bindHandlers();
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
			draggable: '.tasks-scrum-item-drag', // todo add tmp class
			dragElement: '.tasks-scrum-item',
			type: Draggable.DROP_PREVIEW,
			delay: 200
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
		const createTaskItem = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			const entity = baseEvent.getTarget();
			const inputObject = data.inputObject;
			const inputValue = data.value;

			const newItem = this.createItem('task', inputValue);
			newItem.setParentEntity(entity.getId(), entity.getEntityType());
			newItem.setSort(1);

			this.appendItem(newItem, entity.getListItemsNode(), inputObject.getNode());

			this.fillItemBeforeCreation(newItem, inputValue);

			newItem.setParentId(inputObject.getEpicId());

			this.sendRequestToCreateTask(entity, newItem, inputValue).then((response) => {
				this.fillItemAfterCreation(newItem, response.data);
				response.data.tags.forEach((tag) => {
					this.tagSearcher.addTagToSearcher(tag);
				});
				entity.setItem(newItem);
			}).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		};
		const onUpdateItem = (baseEvent: BaseEvent) => {
			this.requestSender.updateItem(baseEvent.getData()).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		};
		const onShowTask = (baseEvent: BaseEvent) => {
			const item = baseEvent.getData();
			this.sidePanel.openSidePanelByUrl(this.pathToTask.replace('#task_id#', item.getSourceId()));
		};
		const onMoveItem = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			this.moveItem(data.item, data.button);
		};
		const onMoveToSprint = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			this.moveToAnotherEntity(baseEvent.getTarget(), data.item, null, data.button);
			if (this.sprints.size <= 1)
			{
				Dom.remove(data.button.parentNode);
			}
		};
		const onMoveToBacklog = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			this.moveToAnotherEntity(data.sprint, data.item, this.backlog);
		};
		const onAttachFilesToTask = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			this.requestSender.attachFilesToTask({
				taskId: data.item.getSourceId(),
				attachedIds: data.attachedIds,
			}).then((response) => {
				data.item.updateIndicators(response.data);
			}).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		};
		const onRemoveItem = (baseEvent: BaseEvent) => {
			const entity = baseEvent.getTarget();
			if (entity.isGroupMode())
			{
				const items = [];
				entity.getGroupModeItems().forEach((groupModeItem: Item) => {
					items.push({
						itemId: groupModeItem.getItemId(),
						itemType: groupModeItem.getItemType(),
						sourceId: groupModeItem.getSourceId()
					});
				});
				this.requestSender.batchRemoveItem({
					items: items,
					sortInfo: this.calculateSort(entity.getListItemsNode())
				}).catch((response) => {
					this.requestSender.showErrorAlert(response);
				});
				entity.deactivateGroupMode();
			}
			else
			{
				this.requestSender.removeItem({
					itemId: baseEvent.getData().getItemId(),
					itemType: baseEvent.getData().getItemType(),
					sourceId: baseEvent.getData().getSourceId(),
					sortInfo: this.calculateSort(entity.getListItemsNode())
				}).catch((response) => {
					this.requestSender.showErrorAlert(response);
				});
			}
		};
		const onStartSprint = (baseEvent: BaseEvent) => {
			const sprint = baseEvent.getTarget();
			const sprintSidePanel = new SprintSidePanel({
				sprints: this.sprints,
				sidePanel: this.sidePanel,
				requestSender: this.requestSender,
				views: this.views
			});
			sprintSidePanel.showStartSidePanel(sprint);
		};
		const onCompleteSprint = (baseEvent: BaseEvent) => {
			const sprint = baseEvent.getTarget();
			const sprintSidePanel = new SprintSidePanel({
				sprints: this.sprints,
				sidePanel: this.sidePanel,
				requestSender: this.requestSender,
				views: this.views
			});
			sprintSidePanel.showCompleteSidePanel(sprint);
		};
		const onChangeTaskResponsible = (baseEvent: BaseEvent) => {
			this.requestSender.changeTaskResponsible({
				itemId: baseEvent.getData().getItemId(),
				itemType: baseEvent.getData().getItemType(),
				sourceId: baseEvent.getData().getSourceId(),
				responsible: baseEvent.getData().gerResponsible(),
			}).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		};
		const onRemoveSprint = (baseEvent: BaseEvent) => {
			const sprint = baseEvent.getTarget();
			this.sprints.delete(sprint.getId());
			this.requestSender.removeSprint({
				sprintId: sprint.getId(),
				sortInfo: this.calculateSprintSort()
			}).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		};
		const onChangeSprintName = (baseEvent: BaseEvent) => {
			this.requestSender.changeSprintName(baseEvent.getData()).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		};
		const onChangeSprintDeadline = (baseEvent: BaseEvent) => {
			this.requestSender.changeSprintDeadline(baseEvent.getData()).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		};
		const onOpenAddEpicForm = (baseEvent: BaseEvent) => {
			this.epic.openAddForm();
		};
		const onOpenListEpicGrid = (baseEvent: BaseEvent) => {
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
		const onShowTagSearcher = (baseEvent: BaseEvent) => {
			//todo refactor and test
			const entity = baseEvent.getTarget();
			const data = baseEvent.getData();
			const item = data.item;
			const actionsPanelButton = data.button;
			this.tagSearcher.showTagsDialog(item, actionsPanelButton);
			this.tagSearcher.unsubscribeAll('attachTagToTask');
			this.tagSearcher.subscribe('attachTagToTask', (innerBaseEvent) => {
				const tag = innerBaseEvent.getData();
				if (entity.isGroupMode())
				{
					const tasks = [];
					entity.getGroupModeItems().forEach((groupModeItem: Item) => {
						tasks.push({
							taskId: groupModeItem.getSourceId()
						});
					});
					this.requestSender.batchAttachTagToTask({
						tasks: tasks,
						tag: tag
					}).then((response) => {
						entity.getGroupModeItems().forEach((groupModeItem: Item) => {
							const currentTags = groupModeItem.getTags();
							currentTags.push(tag);
							groupModeItem.setEpicAndTags(groupModeItem.getEpic(), currentTags);
						});
					}).catch((response) => {
						this.requestSender.showErrorAlert(response);
					});
				}
				else
				{
					const currentTags = item.getTags();
					this.requestSender.attachTagToTask({
						taskId: item.getSourceId(),
						tag: tag
					}).then((response) => {
						currentTags.push(tag);
						item.setEpicAndTags(item.getEpic(), currentTags);
					}).catch((response) => {
						this.requestSender.showErrorAlert(response);
					});
				}
			});
			this.tagSearcher.unsubscribeAll('deAttachTagToTask');
			this.tagSearcher.subscribe('deAttachTagToTask', (innerBaseEvent) => {
				const tag = innerBaseEvent.getData();
				if (entity.isGroupMode())
				{
					const tasks = [];
					entity.getGroupModeItems().forEach((groupModeItem: Item) => {
						tasks.push({
							taskId: groupModeItem.getSourceId()
						});
					});
					this.requestSender.batchDeattachTagToTask({
						tasks: tasks,
						tag: tag
					}).then((response) => {
						entity.getGroupModeItems().forEach((groupModeItem: Item) => {
							const currentTags = groupModeItem.getTags();
							currentTags.splice(currentTags.indexOf(tag), 1);
							groupModeItem.setEpicAndTags(groupModeItem.getEpic(), currentTags);
						});
					}).catch((response) => {
						this.requestSender.showErrorAlert(response);
					});
				}
				else
				{
					const currentTags = item.getTags();
					this.requestSender.deAttachTagToTask({
						taskId: item.getSourceId(),
						tag: tag
					}).then((response) => {
						currentTags.splice(currentTags.indexOf(tag), 1);
						item.setEpicAndTags(item.getEpic(), currentTags);
					}).catch((response) => {
						this.requestSender.showErrorAlert(response);
					});
				}
			});
			this.tagSearcher.unsubscribeAll('hideTagDialog');
			this.tagSearcher.subscribe('hideTagDialog', (innerBaseEvent) => {
				if (entity.isGroupMode())
				{
					entity.deactivateGroupMode();
				}
			});
		};
		const onShowEpicSearcher = (baseEvent: BaseEvent) => {
			const entity = baseEvent.getTarget();
			const data = baseEvent.getData();
			const item = data.item;
			const actionsPanelButton = data.button;
			this.tagSearcher.showEpicDialog(item, actionsPanelButton);
			this.tagSearcher.unsubscribeAll('updateItemEpic');
			this.tagSearcher.subscribe('updateItemEpic', (innerBaseEvent) => {
				if (entity.isGroupMode())
				{
					const items = [];
					entity.getGroupModeItems().forEach((groupModeItem: Item) => {
						items.push({
							itemId: groupModeItem.getItemId()
						});
					});
					this.requestSender.batchUpdateItemEpic({
						items: items,
						epicId: innerBaseEvent.getData()
					}).then((response) => {
						entity.getGroupModeItems().forEach((groupModeItem: Item) => {
							if (Type.isArray(response.data.epic))
							{
								groupModeItem.setParentId(0);
								groupModeItem.setEpicAndTags(null, null);
							}
							else
							{
								groupModeItem.setParentId(response.data.epic.id);
								groupModeItem.setEpicAndTags(response.data.epic, groupModeItem.getTags());
							}
						});
						entity.deactivateGroupMode();
					}).catch((response) => {
						this.requestSender.showErrorAlert(response);
					});
				}
				else
				{
					this.requestSender.updateItemEpic({
						itemId: item.getItemId(),
						epicId: innerBaseEvent.getData()
					}).then((response) => {
						if (Type.isArray(response.data.epic))
						{
							item.setParentId(0);
							item.setEpicAndTags(null, null);
						}
						else
						{
							item.setParentId(response.data.epic.id);
							item.setEpicAndTags(response.data.epic, item.getTags());
						}
					}).catch((response) => {
						this.requestSender.showErrorAlert(response);
					});
				}
			});
		};
		const onStartDecomposition = (baseEvent: BaseEvent) => {
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
		const onTagsSearchOpen = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			const inputObject = data.inputObject;
			const enteredHashTagName = data.enteredHashTagName;
			this.tagSearcher.showTagsSearchDialog(inputObject, enteredHashTagName);
		};
		const onTagsSearchClose = () => {
			this.tagSearcher.closeTagsSearchDialog();
		};
		const onEpicSearchOpen = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			const inputObject = data.inputObject;
			const enteredHashEpicName = data.enteredHashEpicName;
			this.tagSearcher.showEpicSearchDialog(inputObject, enteredHashEpicName);
		};
		const onEpicSearchClose = () => {
			this.tagSearcher.closeEpicSearchDialog();
		};
		const onFilterByEpic = (baseEvent: BaseEvent) => {
			const epicId = baseEvent.getData();
			const currentValue = this.filter.getValueFromField({name: 'EPIC', value: ''});
			if (String(epicId) === String(currentValue))
			{
				this.filter.setValueToField({name: 'EPIC', value: ''});
			}
			else
			{
				this.filter.setValueToField({name: 'EPIC', value: String(epicId)});
			}
			this.filter.scrollToSearchContainer();
		};
		const onFilterByTag = (baseEvent: BaseEvent) => {
			const tag = baseEvent.getData();
			const currentValue = this.filter.getValueFromField({name: 'TAG', value: ''});
			if (String(tag) === String(currentValue))
			{
				this.filter.setValueToField({name: 'TAG', value: ''});
			}
			else
			{
				this.filter.setValueToField({name: 'TAG', value: String(tag)});
			}
			this.filter.scrollToSearchContainer();
		};
		const onActivateGroupMode = (baseEvent: BaseEvent) => {
			const entity = baseEvent.getTarget();
			if (entity.getId() !== this.backlog.getId())
			{
				this.backlog.deactivateGroupMode();
			}
			this.sprints.forEach((sprint) => {
				if (entity.getId() !== sprint.getId())
				{
					sprint.deactivateGroupMode();
				}
			});
			entity.getItems().forEach((item: Item) => {
				item.activateGroupMode();
			});
		};
		const onDeactivateGroupMode = (baseEvent: BaseEvent) => {
			const entity = baseEvent.getTarget();
			entity.getItems().forEach((item: Item) => {
				item.deactivateGroupMode();
			});
		};
		const onGetSprintCompletedItems = (baseEvent: BaseEvent) => {
			const sprint = baseEvent.getTarget();
			const listItemsNode = sprint.getListItemsNode();
			const listPosition = Dom.getPosition(listItemsNode);
			const loader = new Loader({
				target: listItemsNode,
				size: 60,
				mode: 'inline',
				color: '#eaeaea',
				offset: {
					left: `${(listPosition.width / 2 - 30)}px`
				}
			});
			loader.show();
			this.requestSender.getSprintCompletedItems({
				sprintId: sprint.getId()
			}).then((response) => {
				const itemsData = response.data;
				itemsData.forEach((itemData) => {
					const item = new Item(itemData);
					item.setDisableStatus(sprint.isDisabled());
					Dom.append(item.render(), listItemsNode);
					sprint.setItem(item);
				});
				loader.hide();
			}).catch((response) => {
				loader.hide();
				this.requestSender.showErrorAlert(response);
			});
		};

		const subscribeToSprint = (sprint) => {
			sprint.subscribe('createTaskItem', createTaskItem);
			sprint.subscribe('updateItem', onUpdateItem);
			sprint.subscribe('showTask', onShowTask);
			sprint.subscribe('moveItem', onMoveItem);
			sprint.subscribe('moveToSprint', onMoveToSprint);
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
			sprint.subscribe('filterByEpic', onFilterByEpic);
			sprint.subscribe('filterByTag', onFilterByTag);
			sprint.subscribe('activateGroupMode', onActivateGroupMode);
			sprint.subscribe('deactivateGroupMode', onDeactivateGroupMode);
			sprint.subscribe('getSprintCompletedItems', onGetSprintCompletedItems);
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
		this.backlog.subscribe('filterByEpic', onFilterByEpic);
		this.backlog.subscribe('filterByTag', onFilterByTag);
		this.backlog.subscribe('activateGroupMode', onActivateGroupMode);
		this.backlog.subscribe('deactivateGroupMode', onDeactivateGroupMode);

		this.epic.subscribe('filterByTag', onFilterByTag);

		this.sprints.forEach((sprint) => {
			subscribeToSprint(sprint);
		});
	}

	renderSprintsContainer(): HTMLElement
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

		return this.requestSender.createSprint(data).then((response) => {
			const sprint = Sprint.buildSprint({
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
			sprint.getNode().scrollIntoView(true);
			this.sprints.set(sprint.getId(), sprint);
			this.bindHandlers(sprint);
			this.draggableItems.addContainer(sprint.getListItemsNode());
			return sprint;
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	createItem(itemType: String, value: String): Item
	{
		//todo test

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
			'storyPoints': item.getStoryPoints().getPoints(),
			'tags': item.getTags(),
			'epic': item.getEpic(),
			'parentSourceId': item.getParentSourceId(),
			'sortInfo': this.calculateSort(entity.getListItemsNode()),
			'isActiveSprint': ((entity.getEntityType() === 'sprint' && entity.isActive()) ? 'Y' : 'N')
		};
		return this.requestSender.createTask(requestData);
	}

	fillItemBeforeCreation(item: Item, value: string)
	{
		const tags = TagSearcher.getHashTagNamesFromText(value);
		const epicName = TagSearcher.getHashEpicNamesFromText(value).pop();
		let inputEpic = null;
		if (epicName)
		{
			inputEpic = this.tagSearcher.getEpicByName(epicName.trim());
		}
		if (inputEpic || tags.length > 0)
		{
			item.setEpicAndTags(inputEpic, tags);
		}
		item.setResponsible(this.defaultResponsible);
	}

	fillItemAfterCreation(item: Item, responseData: Object): Item
	{
		//todo test

		item.setItemId(responseData.itemId);
		if (!Type.isArray(responseData.epic) || responseData.tags.length > 0)
		{
			item.setEpicAndTags(responseData.epic, responseData.tags);
		}
		item.setResponsible(responseData.responsible);
		item.setSourceId(responseData.sourceId);
		item.setAllowedActions(responseData.allowedActions);
	}

	moveToAnotherEntity(entityFrom: Entity, item: Item, targetEntity: ?Entity, bindButton?: HTMLElement)
	{
		const isTargetSprintEntity = (Type.isNull(targetEntity));

		const sprints = (isTargetSprintEntity ? this.getAvailableSprintsToMove() : null);

		if (entityFrom.isGroupMode())
		{
			if (isTargetSprintEntity)
			{
				if (sprints.size > 1)
				{
					this.showListSprintsToMove(entityFrom, item, bindButton);
				}
				else
				{
					if (sprints.size === 0)
					{
						this.createSprint().then((sprint: Sprint) => {
							this.moveToWithGroupMode(entityFrom, sprint, item, true, false);
						});
					}
					else
					{
						sprints.forEach((sprint: Sprint) => {
							this.moveToWithGroupMode(entityFrom, sprint, item, true, false);
						});
					}
				}
			}
			else
			{
				if (entityFrom.isActive())
				{
					MessageBox.confirm(
						Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASKS_FROM_ACTIVE'),
						(messageBox) => {
							messageBox.close();
							this.moveToWithGroupMode(entityFrom, targetEntity, item, false, false);
						},
						Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_MOVE'),
					);
				}
				else
				{
					this.moveToWithGroupMode(entityFrom, targetEntity, item, false, false);
				}
			}
		}
		else
		{
			if (isTargetSprintEntity)
			{
				if (sprints.size > 1)
				{
					this.showListSprintsToMove(entityFrom, item, bindButton);
				}
				else
				{
					if (entityFrom.isActive())
					{
						MessageBox.confirm(
							Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE'),
							(messageBox) => {
								messageBox.close();
								if (sprints.size === 0)
								{
									this.createSprint().then((sprint: Sprint) => {
										this.moveTo(entityFrom, sprint, item);
									});
								}
								else
								{
									sprints.forEach((sprint: Sprint) => {
										this.moveTo(entityFrom, sprint, item);
									});
								}
							},
							Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_MOVE'),
						);
					}
					else
					{
						if (sprints.size === 0)
						{
							this.createSprint().then((sprint: Sprint) => {
								this.moveTo(entityFrom, sprint, item);
							});
						}
						else
						{
							sprints.forEach((sprint: Sprint) => {
								this.moveTo(entityFrom, sprint, item);
							});
						}
					}
				}
			}
			else
			{
				if (entityFrom.isActive())
				{
					MessageBox.confirm(
						Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE'),
						(messageBox) => {
							messageBox.close();
							this.moveTo(entityFrom, targetEntity, item, false);
						},
						Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_MOVE'),
					);
				}
				else
				{
					this.moveTo(entityFrom, targetEntity, item, false);
				}
			}
		}
	}

	getAvailableSprintsToMove(): Set<Sprint>
	{
		const sprints = new Set();
		this.sprints.forEach((sprint: Sprint) => {
			if (!sprint.isCompleted())
			{
				sprints.add(sprint);
			}
		});
		return sprints;
	}

	moveToWithGroupMode(entityFrom: Entity, entityTo: Entity, item: Item, after = true, update = true)
	{
		//todo test
		const groupModeItems = entityFrom.getGroupModeItems();
		const sortedItems = [...groupModeItems.values()].sort((first: Item, second: Item) => {
			if (after)
			{
				if (first.getSort() > second.getSort()) return 1;
				if (first.getSort() < second.getSort()) return -1;
			}
			else
			{
				if (first.getSort() < second.getSort()) return 1;
				if (first.getSort() > second.getSort()) return -1;
			}
		});
		const items = [];
		sortedItems.forEach((groupModeItem) => {
			this.moveTo(entityFrom, entityTo, groupModeItem, after, update);
			items.push({
				itemId: groupModeItem.getItemId(),
				itemType: groupModeItem.getItemType(),
				entityId: entityTo.getId(),
				fromActiveSprint: ((entityFrom.getEntityType() === 'sprint' && entityFrom.isActive()) ? 'Y' : 'N'),
				toActiveSprint: ((entityTo.getEntityType() === 'sprint' && entityTo.isActive()) ? 'Y' : 'N')
			});
		});
		this.requestSender.batchUpdateItem({
			items: items,
			sortInfo: {
				...this.calculateSort(entityFrom.getListItemsNode(), true),
				...this.calculateSort(entityTo.getListItemsNode(), true)
			}
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
		entityFrom.deactivateGroupMode();
	}

	moveTo(entityFrom: Entity, entityTo: Entity, item: Item, after = true, update = true)
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

		if (update)
		{
			this.onMoveItemUpdate(entityFrom, entityTo, item);
		}
	}

	onMoveItemUpdate(entityFrom: Entity, entityTo: Entity, item: Item)
	{
		this.requestSender.updateItem({
			itemId: item.getItemId(),
			itemType: item.getItemType(),
			entityId: entityTo.getId(),
			fromActiveSprint: ((entityFrom.getEntityType() === 'sprint' && entityFrom.isActive()) ? 'Y' : 'N'),
			toActiveSprint: ((entityTo.getEntityType() === 'sprint' && entityTo.isActive()) ? 'Y' : 'N'),
			sortInfo: {
				...this.calculateSort(entityFrom.getListItemsNode(), true),
				...this.calculateSort(entityTo.getListItemsNode(), true)
			}
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	moveItemFromEntityToEntity(item: Item, entityFrom: Entity, entityTo: Entity)
	{
		if (entityFrom.isActive())
		{
			entityFrom.subtractTotalStoryPoints(item);
		}

		if (entityTo.isActive())
		{
			entityTo.addTotalStoryPoints(item);
		}

		entityFrom.removeItem(item);
		item.setParentEntity(entityTo.getId(), entityTo.getEntityType());
		item.setDisableStatus(false);
		entityTo.setItem(item);
	}

	showListSprintsToMove(entityFrom: Entity, item: Item, button: HTMLElement)
	{
		const id = `item-sprint-action-${entityFrom.getEntityType() + entityFrom.getId() + item.itemId}`;

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
			if (!sprint.isCompleted() && !this.isSameSprint(entityFrom, sprint))
			{
				this.moveToSprintMenu.addMenuItem({
					text: sprint.getName(),
					onclick: (event, menuItem) => {
						if (entityFrom.isGroupMode())
						{
							if (entityFrom.isActive())
							{
								MessageBox.confirm(
									Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASKS_FROM_ACTIVE'),
									(messageBox) => {
										messageBox.close();
										this.moveToWithGroupMode(entityFrom, sprint, item, true, false);
									},
									Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_MOVE'),
								);
							}
							else
							{
								this.moveToWithGroupMode(entityFrom, sprint, item, true, false);
							}
						}
						else
						{
							if (entityFrom.isActive())
							{
								MessageBox.confirm(
									Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_MOVE_TASK_FROM_ACTIVE'),
									(messageBox) => {
										messageBox.close();
										this.moveTo(entityFrom, sprint, item);
									},
									Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_MOVE'),
								);
							}
							else
							{
								this.moveTo(entityFrom, sprint, item);
							}
						}
						menuItem.getMenuWindow().close();
					}
				});
			}
		});

		this.moveToSprintMenu.show();
	}

	isSameSprint(first: Sprint, second: Sprint): boolean
	{
		return (first.getEntityType() === 'sprint' && first.getId() === second.getId());
	}

	calculateSort(container, moveToAnotherEntity = false)
	{
		//todo test

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
					sort: sort
				};
				if (moveToAnotherEntity)
				{
					listSortInfo[itemId].entityId = container.dataset.entityId;
				}
				itemNode.dataset.sort = sort;
			}
			sort++;
		});

		return listSortInfo;
	}

	calculateSprintSort(increment = 0)
	{
		//todo test

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
				}).catch((response) => {
					this.requestSender.showErrorAlert(response);
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
					sortInfo: this.calculateSort(endContainer, true)
				}).catch((response) => {
					this.requestSender.showErrorAlert(response);
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
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
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

	findEntityByEntityId(entityId): Entity
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
		//todo test

		const entity = this.findEntityByItemId(item.getItemId());

		const listToMove = [];

		if (!entity.isFirstItem(item))
		{
			listToMove.push({
				text: Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_UP'),
				onclick: (event, menuItem) => {
					if (entity.isGroupMode())
					{
						const groupModeItems = entity.getGroupModeItems();
						const sortedItems = [...groupModeItems.values()].sort((first: Item, second: Item) => {
							if (first.getSort() < second.getSort()) return 1;
							if (first.getSort() > second.getSort()) return -1;
						});
						sortedItems.forEach((groupModeItem) => {
							this.moveItemToUp(groupModeItem, entity.getListItemsNode(), entity.hasInput(), false);
						});
						this.requestSender.updateItemSort({
							sortInfo: this.calculateSort(entity.getListItemsNode())
						}).catch((response) => {
							this.requestSender.showErrorAlert(response);
						});
						entity.deactivateGroupMode();
					}
					else
					{
						this.moveItemToUp(item, entity.getListItemsNode(), entity.hasInput());
					}
					menuItem.getMenuWindow().close();
				}
			});
		}
		if (!entity.isLastItem(item))
		{
			listToMove.push({
				text: Loc.getMessage('TASKS_SCRUM_ITEM_ACTIONS_MOVE_DOWN'),
				onclick: (event, menuItem) => {
					if (entity.isGroupMode())
					{
						const groupModeItems = entity.getGroupModeItems();
						const sortedItems = [...groupModeItems.values()].sort((first: Item, second: Item) => {
							if (first.getSort() > second.getSort()) return 1;
							if (first.getSort() < second.getSort()) return -1;
						});
						sortedItems.forEach((groupModeItem) => {
							this.moveItemToDown(groupModeItem, entity.getListItemsNode(), false);
						});
						this.requestSender.updateItemSort({
							sortInfo: this.calculateSort(entity.getListItemsNode())
						}).catch((response) => {
							this.requestSender.showErrorAlert(response);
						});
						entity.deactivateGroupMode();
					}
					else
					{
						this.moveItemToDown(item, entity.getListItemsNode());
					}
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

	moveItemToUp(item, listItemsNode, entityWithInput = true, updateSort = true)
	{
		if (entityWithInput)
		{
			this.appendItemAfterItem(item.getItemNode(), listItemsNode.firstElementChild);
		}
		else
		{
			Dom.insertBefore(item.getItemNode(), listItemsNode.firstElementChild);
		}

		if (updateSort)
		{
			this.updateItemsSort(item, listItemsNode);
		}
	}

	moveItemToDown(item, listItemsNode, updateSort = true)
	{
		Dom.append(item.getItemNode(), listItemsNode);

		if (updateSort)
		{
			this.updateItemsSort(item, listItemsNode);
		}
	}

	updateItemsSort(item: Item, listItemsNode: HTMLElement)
	{
		this.requestSender.updateItem({
			itemId: item.getItemId(),
			sortInfo: {
				...this.calculateSort(listItemsNode)
			}
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	getBacklog(): Backlog
	{
		return this.backlog;
	}

	getSprints(): Map
	{
		return this.sprints;
	}

	getAllItems(): Map
	{
		//todo test
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
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	fadeOutAll()
	{
		this.backlog.fadeOut();
		this.sprints.forEach((sprint: Sprint) => {
			if (!sprint.isCompleted())
			{
				sprint.fadeOut();
			}
		});
	}

	fadeInAll()
	{
		this.backlog.fadeIn();
		this.sprints.forEach((sprint: Sprint) => {
			if (!sprint.isCompleted())
			{
				sprint.fadeIn();
			}
		});
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
			tags: itemData.tags,
			allowedActions: itemData.allowedActions
		});
	}

	onApplyFilter(baseEvent: BaseEvent)
	{
		const filterInfo = baseEvent.getData();

		this.fadeOutAll();

		this.requestSender.applyFilter().then(response => {
			const filteredItemsData = response.data;
			this.getAllItems().forEach((item) => {
				const entity = this.findEntityByEntityId(item.getEntityId());
				if (!entity.isCompleted())
				{
					entity.removeItem(item);
					item.removeYourself();
				}
			});
			filteredItemsData.forEach((itemData) => {
				const item = this.createTaskItemByItemData(itemData);
				const entity = this.findEntityByEntityId(item.getEntityId());
				if (!entity.isCompleted())
				{
					Dom.append(item.render(), entity.getListItemsNode());
					entity.setItem(item);
					item.onAfterAppend(entity.getListItemsNode());
				}
			});
			filterInfo.promise.fulfill();
			this.fadeInAll();
			this.getBacklog().updateStoryPoints();
			this.getSprints().forEach((sprint: Sprint) => {
				if (!sprint.isCompleted())
				{
					sprint.updateStoryPoints();
				}
			});
		}).catch((response) => {
			filterInfo.promise.reject();
			this.fadeInAll();
			this.requestSender.showErrorAlert(response);
		});
	}
}