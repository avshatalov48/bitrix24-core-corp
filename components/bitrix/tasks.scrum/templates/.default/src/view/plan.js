import {Type, Event} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {Loader} from 'main.loader';

import {View} from './view';

import {Backlog} from '../entity/backlog/backlog';
import {Sprint} from '../entity/sprint/sprint';
import {SprintSidePanel} from '../entity/sprint/sprint.side.panel';
import {Entity} from '../entity/entity';
import {Item} from '../item/item';
import {Decomposition} from '../item/task/decomposition';

import {SidePanel} from '../service/side.panel';
import {PULL as Pull} from 'pull.client';

import {DomBuilder} from '../utility/dom.builder';
import {EntityStorage} from '../utility/entity.storage';
import {FilterHandler} from '../utility/filter.handler';
import {Epic} from '../utility/epic';
import {TagSearcher} from '../utility/tag.searcher';
import {ProjectSidePanel} from '../utility/project.side.panel';
import {PullSprint} from '../utility/pull.sprint';
import {PullItem} from '../utility/pull.item';
import {PullEpic} from '../utility/pull.epic';
import {SprintMover} from '../utility/sprint.mover';
import {ItemMover} from '../utility/item.mover';
import {ItemStyleDesigner} from '../utility/item.style.designer';
import {SubTasksManager} from '../utility/subtasks.manager';

import type {BacklogParams} from '../entity/backlog/backlog';
import type {SprintParams} from '../entity/sprint/sprint';
import type {EpicType} from '../item/item';
import type {Views} from './view';

import '../css/base.css';

type Responsible = {
	name: string,
	pathToUser: string,
	photo: {
		src: string
	}
}

type Params = {
	pathToTask: string,
	defaultSprintDuration: number,
	activeSprintId: number,
	backlog: BacklogParams,
	sprints: Array<SprintParams>,
	views: Views,
	tags: {
		epic: EpicType,
		task: Array
	},
	defaultResponsible: Responsible
}

export class Plan extends View
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Plan');

		this.pathToTask = params.pathToTask;
		this.defaultResponsible = params.defaultResponsible;
		this.activeSprintId = parseInt(params.activeSprintId, 10);
		this.views = params.views;

		this.entityStorage = new EntityStorage();
		this.entityStorage.addBacklog(Backlog.buildBacklog(params.backlog));
		params.sprints.forEach((sprintData) => {
			sprintData.defaultSprintDuration = params.defaultSprintDuration;
			const sprint = Sprint.buildSprint(sprintData);
			this.entityStorage.addSprint(sprint);
		});

		this.sidePanel = new SidePanel();

		this.filterHandler = new FilterHandler({
			filter: this.filter,
			requestSender: this.requestSender,
			entityStorage: this.entityStorage
		});

		this.tagSearcher = new TagSearcher();
		Object.values(params.tags.epic).forEach((epic) => {
			this.tagSearcher.addEpicToSearcher(epic);
		});
		Object.values(params.tags.task).forEach((tagName) => {
			this.tagSearcher.addTagToSearcher(tagName);
		});

		this.domBuilder = new DomBuilder({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			defaultSprintDuration: params.defaultSprintDuration
		});
		this.domBuilder.subscribe('beforeCreateSprint', (baseEvent: BaseEvent) => {
			const requestData = baseEvent.getData();
			this.pullSprint.addTmpIdToSkipAdding(requestData.tmpId);
		});
		this.domBuilder.subscribe('createSprint', (baseEvent: BaseEvent) => {
			this.bindHandlers(baseEvent.getData());
		});
		this.domBuilder.subscribe('createSprintNode', (baseEvent: BaseEvent) => {
			this.bindHandlers(baseEvent.getData());
		});

		this.sprintMover = new SprintMover({
			requestSender: this.requestSender,
			domBuilder: this.domBuilder,
			entityStorage: this.entityStorage
		});

		this.subTasksCreator = new SubTasksManager({
			requestSender: this.requestSender,
			domBuilder: this.domBuilder
		});

		this.itemMover = new ItemMover({
			requestSender: this.requestSender,
			domBuilder: this.domBuilder,
			entityStorage: this.entityStorage,
			subTasksCreator: this.subTasksCreator
		});

		this.itemStyleDesigner = new ItemStyleDesigner({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage
		});

		this.epic = new Epic({
			entity: this.entityStorage.getBacklog(),
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			sidePanel: this.sidePanel,
			filter: this.filter,
			tagSearcher: this.tagSearcher
		});

		this.pullSprint = new PullSprint({
			requestSender: this.requestSender,
			domBuilder: this.domBuilder,
			entityStorage: this.entityStorage,
			groupId: this.groupId
		});
		this.pullItem = new PullItem({
			requestSender: this.requestSender,
			domBuilder: this.domBuilder,
			entityStorage: this.entityStorage,
			tagSearcher: this.tagSearcher,
			itemMover: this.itemMover,
			subTasksCreator: this.subTasksCreator,
			counters: this.counters,
			currentUserId: this.getCurrentUserId()
		});
		this.pullEpic = new PullEpic({
			requestSender: this.requestSender,
			domBuilder: this.domBuilder,
			entityStorage: this.entityStorage,
			epic: this.epic,
		});

		this.bindHandlers();

		this.subscribeToPull();
	}

	renderTo(container: HTMLElement)
	{
		super.renderTo(container);

		this.domBuilder.renderTo(container);
	}

	subscribeToPull()
	{
		Pull.subscribe(this.pullSprint);
		Pull.subscribe(this.pullItem);
		Pull.subscribe(this.pullEpic);
	}

	bindHandlers(newSprint)
	{
		this.teamSpeedChartButtonContainerNode = document.getElementById('tasks-scrum-team-speed-button-container');
		Event.bind(this.teamSpeedChartButtonContainerNode, 'click', this.onShowTeamSpeedChart.bind(this));

		const createTaskItem = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			const entity = baseEvent.getTarget();
			const inputObject = data.inputObject;
			const inputValue = data.value;

			const newItem = this.createItem('task', inputValue);

			this.pullItem.addTmpIdsToSkipAdding(newItem.getItemId());

			this.fillItemBeforeCreation(entity, newItem, inputValue);

			this.domBuilder.appendItemAfterItem(newItem.render(), inputObject.getNode());
			newItem.onAfterAppend(entity.getListItemsNode());

			newItem.setParentId(inputObject.getEpicId());
			inputObject.setEpicId(0);

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
			const updateData = baseEvent.getData();
			this.pullItem.addIdToSkipUpdating(updateData.itemId);
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
			this.itemMover.moveItem(data.item, data.button);
		};
		const onMoveToSprint = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			const entityFrom = baseEvent.getTarget();
			this.itemMover.moveToAnotherEntity(entityFrom, data.item, null, data.button);
			if (this.entityStorage.getSprintsAvailableForFilling(entityFrom).size <= 1)
			{
				this.domBuilder.remove(data.button.parentNode);
			}
		};
		const onMoveToBacklog = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			this.itemMover.moveToAnotherEntity(data.sprint, data.item, this.entityStorage.getBacklog());
		};
		const onAttachFilesToTask = (baseEvent: BaseEvent) => {
			const data = baseEvent.getData();
			this.pullItem.addIdToSkipUpdating(data.item.getItemId());
			this.requestSender.attachFilesToTask({
				taskId: data.item.getSourceId(),
				itemId: data.item.getItemId(),
				entityId: data.item.getEntityId(),
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
						entityId: groupModeItem.getEntityId(),
						itemType: groupModeItem.getItemType(),
						sourceId: groupModeItem.getSourceId()
					});
					this.pullItem.addIdToSkipRemoving(groupModeItem.getItemId());
				});
				this.requestSender.batchRemoveItem({
					items: items,
					sortInfo: this.itemMover.calculateSort(entity.getListItemsNode())
				}).catch((response) => {
					this.requestSender.showErrorAlert(response);
				});
				entity.deactivateGroupMode();
			}
			else
			{
				this.pullItem.addIdToSkipRemoving(baseEvent.getData().getItemId());
				this.requestSender.removeItem({
					itemId: baseEvent.getData().getItemId(),
					entityId: baseEvent.getData().getEntityId(),
					itemType: baseEvent.getData().getItemType(),
					sourceId: baseEvent.getData().getSourceId(),
					sortInfo: this.itemMover.calculateSort(entity.getListItemsNode())
				}).catch((response) => {
					this.requestSender.showErrorAlert(response);
				});
			}
		};
		const onStartSprint = (baseEvent: BaseEvent) => {
			const sprint = baseEvent.getTarget();
			const sprintSidePanel = new SprintSidePanel({
				sprints: this.entityStorage.getSprints(),
				sidePanel: this.sidePanel,
				requestSender: this.requestSender,
				views: this.views
			});
			sprintSidePanel.showStartSidePanel(sprint);
		};
		const onCompleteSprint = (baseEvent: BaseEvent) => {
			const sprint = baseEvent.getTarget();
			const sprintSidePanel = new SprintSidePanel({
				sprints: this.entityStorage.getSprints(),
				sidePanel: this.sidePanel,
				requestSender: this.requestSender,
				views: this.views
			});
			sprintSidePanel.showCompleteSidePanel(sprint);
		};
		const onShowSprintBurnDownChart = (baseEvent: BaseEvent) => {
			const sprint = baseEvent.getTarget();
			const sprintSidePanel = new SprintSidePanel({
				sprints: this.entityStorage.getSprints(),
				sidePanel: this.sidePanel,
				requestSender: this.requestSender,
				views: this.views
			});
			sprintSidePanel.showBurnDownChart(sprint);
		};
		const onChangeTaskResponsible = (baseEvent: BaseEvent) => {
			this.pullItem.addIdToSkipUpdating(baseEvent.getData().getItemId());
			this.requestSender.changeTaskResponsible({
				itemId: baseEvent.getData().getItemId(),
				itemType: baseEvent.getData().getItemType(),
				sourceId: baseEvent.getData().getSourceId(),
				responsible: baseEvent.getData().getResponsible(),
			}).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		};
		const onRemoveSprint = (baseEvent: BaseEvent) => {
			const sprint = baseEvent.getTarget();
			this.pullSprint.addIdToSkipRemoving(sprint.getId());
			this.requestSender.removeSprint({
				sprintId: sprint.getId(),
				sortInfo: this.sprintMover.calculateSprintSort()
			}).then((response) => {
				this.entityStorage.removeSprint(sprint.getId());
			}).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		};
		const onChangeSprintName = (baseEvent: BaseEvent) => {
			const requestData = baseEvent.getData();
			this.pullSprint.addIdToSkipUpdating(requestData.sprintId);
			this.requestSender.changeSprintName(requestData).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		};
		const onChangeSprintDeadline = (baseEvent: BaseEvent) => {
			const requestData = baseEvent.getData();
			this.pullSprint.addIdToSkipUpdating(requestData.sprintId);
			this.requestSender.changeSprintDeadline(requestData).catch((response) => {
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
				this.epic.onAfterUpdateEpic(updatedEpicInfo);
			});
		};
		const onOpenDefinitionOfDone = (baseEvent: BaseEvent) => {
			const entity = baseEvent.getTarget();
			const projectSidePanel = new ProjectSidePanel({
				sidePanel: this.sidePanel,
				requestSender: this.requestSender
			});
			projectSidePanel.showDefinitionOfDone(entity);
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
							taskId: groupModeItem.getSourceId(),
							itemId: groupModeItem.getItemId()
						});
					});
					this.requestSender.batchAttachTagToTask({
						tasks: tasks,
						entityId: entity.getId(),
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
						itemId: item.getItemId(),
						entityId: entity.getId(),
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
							taskId: groupModeItem.getSourceId(),
							itemId: groupModeItem.getItemId()
						});
					});
					this.requestSender.batchDeattachTagToTask({
						tasks: tasks,
						entityId: entity.getId(),
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
						itemId: item.getItemId(),
						entityId: entity.getId(),
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
						this.pullItem.addIdToSkipUpdating(groupModeItem.getItemId());
					});
					this.requestSender.batchUpdateItemEpic({
						items: items,
						entityId: entity.getId(),
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
					this.pullItem.addIdToSkipUpdating(item.getItemId());
					this.requestSender.updateItemEpic({
						itemId: item.getItemId(),
						entityId: entity.getId(),
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

			const decomposition = new Decomposition({
				entity: entity,
				itemStyleDesigner: this.itemStyleDesigner,
				subTasksCreator: this.subTasksCreator
			});
			decomposition.subscribe('tagsSearchOpen', onTagsSearchOpen);
			decomposition.subscribe('tagsSearchClose', onTagsSearchClose);
			decomposition.subscribe('createItem', (innerBaseEvent) => {
				const inputValue = innerBaseEvent.getData();
				const decomposedItems = decomposition.getDecomposedItems();
				const lastDecomposedItem = Array.from(decomposedItems).pop();

				const newItem = this.createItem(parentItem.getItemType(), inputValue);

				this.pullItem.addTmpIdsToSkipAdding(newItem.getItemId());
				this.pullItem.addIdToSkipUpdating(parentItem.getItemId());

				newItem.setParentEntity(entity.getId(), entity.getEntityType());
				newItem.setParentId(parentItem.getParentId());
				newItem.setParentSourceId(parentItem.getSourceId());
				newItem.setEpic(parentItem.getEpic());
				newItem.setTags(parentItem.getTags());
				newItem.setResponsible(decomposition.getResponsible());
				if (decomposition.isBacklogDecomposition())
				{
					parentItem.setLinkedTask('Y');

					newItem.setSort(lastDecomposedItem.getSort() + 1);
					newItem.setInfo({
						borderColor: decomposition.getBorderColor()
					});
					newItem.setLinkedTask('Y');
				}
				else
				{
					parentItem.setParentTask('Y');
					parentItem.setSubTasksCount(parentItem.getSubTasksCount() + 1);
					parentItem.updateParentTaskNodes();

					newItem.setSort(decomposition.getSubTasks(parentItem).length + 1);
					newItem.setSubTask('Y');
					newItem.setParentTaskId(parentItem.getSourceId());
					newItem.setParentTask('N');
				}

				this.domBuilder.appendItemAfterItem(
					newItem.render(),
					decomposition.getLastDecomposedItemNode(parentItem)
				);
				newItem.onAfterAppend(entity.getListItemsNode());

				decomposition.addDecomposedItem(newItem);

				this.sendRequestToCreateTask(entity, newItem, inputValue).then((response) => {
					this.fillItemAfterCreation(newItem, response.data);
					response.data.tags.forEach((tag) => {
						this.tagSearcher.addTagToSearcher(tag);
					});
					entity.setItem(newItem);
					if (!decomposition.isBacklogDecomposition())
					{
						this.subTasksCreator.addSubTask(parentItem, newItem);
					}
				});
			});
			decomposition.subscribe('updateParentItem', onUpdateItem);

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
			if (entity.getId() !== this.entityStorage.getBacklog().getId())
			{
				this.entityStorage.getBacklog().deactivateGroupMode();
			}
			this.entityStorage.getSprints().forEach((sprint) => {
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
			const listPosition = this.domBuilder.getPosition(listItemsNode);
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
					this.domBuilder.append(item.render(), listItemsNode);
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
			sprint.subscribe('showSprintBurnDownChart', onShowSprintBurnDownChart);
			sprint.subscribe('toggleSubTasks', this.onToggleSubTasks.bind(this));
		};

		if (newSprint)
		{
			subscribeToSprint(newSprint);
			return;
		}

		this.entityStorage.getBacklog().subscribe('createTaskItem', createTaskItem);
		this.entityStorage.getBacklog().subscribe('updateItem', onUpdateItem);
		this.entityStorage.getBacklog().subscribe('showTask', onShowTask);
		this.entityStorage.getBacklog().subscribe('moveItem', onMoveItem);
		this.entityStorage.getBacklog().subscribe('moveToSprint', onMoveToSprint);
		this.entityStorage.getBacklog().subscribe('removeItem', onRemoveItem);
		this.entityStorage.getBacklog().subscribe('changeTaskResponsible', onChangeTaskResponsible);
		this.entityStorage.getBacklog().subscribe('openAddEpicForm', onOpenAddEpicForm);
		this.entityStorage.getBacklog().subscribe('openListEpicGrid', onOpenListEpicGrid);
		this.entityStorage.getBacklog().subscribe('openDefinitionOfDone', onOpenDefinitionOfDone);
		this.entityStorage.getBacklog().subscribe('attachFilesToTask', onAttachFilesToTask);
		this.entityStorage.getBacklog().subscribe('showTagSearcher', onShowTagSearcher);
		this.entityStorage.getBacklog().subscribe('showEpicSearcher', onShowEpicSearcher);
		this.entityStorage.getBacklog().subscribe('startDecomposition', onStartDecomposition);
		this.entityStorage.getBacklog().subscribe('tagsSearchOpen', onTagsSearchOpen);
		this.entityStorage.getBacklog().subscribe('tagsSearchClose', onTagsSearchClose);
		this.entityStorage.getBacklog().subscribe('epicSearchOpen', onEpicSearchOpen);
		this.entityStorage.getBacklog().subscribe('epicSearchClose', onEpicSearchClose);
		this.entityStorage.getBacklog().subscribe('filterByEpic', onFilterByEpic);
		this.entityStorage.getBacklog().subscribe('filterByTag', onFilterByTag);
		this.entityStorage.getBacklog().subscribe('activateGroupMode', onActivateGroupMode);
		this.entityStorage.getBacklog().subscribe('deactivateGroupMode', onDeactivateGroupMode);

		this.epic.subscribe('filterByTag', onFilterByTag);

		this.entityStorage.getSprints().forEach((sprint) => {
			subscribeToSprint(sprint);
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

	sendRequestToCreateTask(entity: Entity, item: Item, value: String): Promise
	{
		const requestData = {
			'tmpId': item.getItemId(),
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
			'responsible': item.getResponsible(),
			'info': item.getInfo(),
			'sortInfo': this.itemMover.calculateSort(entity.getListItemsNode()),
			'isActiveSprint': ((entity.getEntityType() === 'sprint' && entity.isActive()) ? 'Y' : 'N')
		};
		return this.requestSender.createTask(requestData);
	}

	fillItemBeforeCreation(entity: Entity, item: Item, value: string)
	{
		item.setParentEntity(entity.getId(), entity.getEntityType());
		item.setSort(1);
		item.setResponsible(this.defaultResponsible);

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

	onToggleSubTasks(baseEvent: BaseEvent)
	{
		const sprint = baseEvent.getTarget();
		const item = baseEvent.getData();

		this.subTasksCreator.toggleSubTasks(sprint, item);
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
			this.epic.onAfterRemoveEpic(epicInfo);
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	onShowTeamSpeedChart() //todo move to class
	{
		const projectSidePanel = new ProjectSidePanel({
			sidePanel: this.sidePanel,
			requestSender: this.requestSender
		});
		projectSidePanel.showTeamSpeedChart();
	}
}