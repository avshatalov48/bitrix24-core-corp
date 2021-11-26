import {Type, Dom, Loc} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {Loader} from 'main.loader';

import {MessageBox} from 'ui.dialogs.messagebox';

import {ScrumDod} from 'tasks.scrum.dod';

import {View} from './view';
import {TeamSpeedButton} from './header/team.speed.button';

import {Backlog} from '../entity/backlog/backlog';
import {Sprint} from '../entity/sprint/sprint';
import {SprintSidePanel} from '../entity/sprint/sprint.side.panel';
import {Entity} from '../entity/entity';
import {Item} from '../item/item';
import {Decomposition} from '../item/task/decomposition';
import {DodSidePanel} from '../dod/side.panel';
import {TeamSpeedSidePanel} from '../team.speed/side.panel';

import {SidePanel} from '../service/side.panel';
import {PULL as Pull} from 'pull.client';

import {DomBuilder} from '../utility/dom.builder';
import {EntityStorage} from '../utility/entity.storage';
import {EntityCounters} from '../utility/entity.counters';
import {FilterHandler} from '../utility/filter.handler';
import {Epic} from '../utility/epic';
import {TagSearcher} from '../utility/tag.searcher';
import {PullSprint} from '../pull/pull.sprint';
import {PullItem} from '../pull/pull.item';
import {PullEpic} from '../pull/pull.epic';
import {SprintMover} from '../utility/sprint.mover';
import {ItemMover} from '../utility/item.mover';
import {ItemStyleDesigner} from '../utility/item.style.designer';
import {SubTasksManager} from '../utility/subtasks.manager';

import type {BacklogParams} from '../entity/backlog/backlog';
import type {SprintParams} from '../entity/sprint/sprint';
import type {EpicType, ItemParams} from '../item/item';
import type {Views} from './view';
import {Counters} from '../counters/counters';

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

		this.entityCounters = new EntityCounters({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage
		});

		this.counters = new Counters({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			filter: this.filter,
			userId: params.userId,
			groupId: params.groupId,
			isOwnerCurrentUser: params.isOwnerCurrentUser
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
			defaultSprintDuration: params.defaultSprintDuration,
			pageNumberToCompletedSprints: params.pageNumberToCompletedSprints
		});
		this.domBuilder.subscribe('beforeCreateSprint', (baseEvent: BaseEvent) => {
			const requestData = baseEvent.getData();
			this.pullSprint.addTmpIdToSkipAdding(requestData.tmpId);
		});
		this.domBuilder.subscribe(
			'createSprint',
			(baseEvent: BaseEvent) => this.subscribeToSprint(baseEvent.getData())
		);
		this.domBuilder.subscribe(
			'createSprintNode',
			(baseEvent: BaseEvent) => this.subscribeToSprint(baseEvent.getData())
		);

		this.sprintMover = new SprintMover({
			requestSender: this.requestSender,
			domBuilder: this.domBuilder,
			entityStorage: this.entityStorage
		});

		this.subTasksCreator = new SubTasksManager({
			requestSender: this.requestSender,
			domBuilder: this.domBuilder
		});

		this.filterHandler = new FilterHandler({
			filter: this.filter,
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			subTasksCreator: this.subTasksCreator
		});

		this.itemMover = new ItemMover({
			requestSender: this.requestSender,
			domBuilder: this.domBuilder,
			entityStorage: this.entityStorage,
			entityCounters: this.entityCounters,
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
			groupId: this.getCurrentGroupId()
		});
		this.pullItem = new PullItem({
			requestSender: this.requestSender,
			domBuilder: this.domBuilder,
			entityStorage: this.entityStorage,
			entityCounters: this.entityCounters,
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

		this.itemDod = new ScrumDod({
			groupId: this.groupId
		});

		this.bindHandlers();

		this.subscribeToPull();
	}

	renderTo(container: HTMLElement)
	{
		super.renderTo(container);

		this.domBuilder.renderTo(container);
	}

	renderButtonsTo(container: HTMLElement)
	{
		super.renderButtonsTo(container);

		const teamSpeedButton = new TeamSpeedButton();
		teamSpeedButton.subscribe('click', this.onShowTeamSpeedChart.bind(this));

		Dom.append(teamSpeedButton.render(), container);
	}

	subscribeToPull()
	{
		Pull.subscribe(this.pullSprint);
		Pull.subscribe(this.pullItem);
		Pull.subscribe(this.pullEpic);
	}

	bindHandlers()
	{
		this.entityStorage.getBacklog().subscribe('createTaskItem', this.onCreateTaskItem.bind(this));
		this.entityStorage.getBacklog().subscribe('updateItem', this.onUpdateItem.bind(this));
		this.entityStorage.getBacklog().subscribe('showTask', this.onShowTask.bind(this));
		this.entityStorage.getBacklog().subscribe('moveItem', this.onMoveItem.bind(this));
		this.entityStorage.getBacklog().subscribe('moveToSprint', this.onMoveToSprint.bind(this));
		this.entityStorage.getBacklog().subscribe('removeItem', this.onRemoveItem.bind(this));
		this.entityStorage.getBacklog().subscribe('changeTaskResponsible', this.onChangeTaskResponsible.bind(this));
		this.entityStorage.getBacklog().subscribe('openAddEpicForm', this.onOpenAddEpicForm.bind(this));
		this.entityStorage.getBacklog().subscribe('openListEpicGrid', this.onOpenListEpicGrid.bind(this));
		this.entityStorage.getBacklog().subscribe('openDefinitionOfDone', this.onOpenDefinitionOfDone.bind(this));
		this.entityStorage.getBacklog().subscribe('attachFilesToTask', this.onAttachFilesToTask.bind(this));
		this.entityStorage.getBacklog().subscribe('showDod', this.onShowDod.bind(this));
		this.entityStorage.getBacklog().subscribe('showTagSearcher', this.onShowTagSearcher.bind(this));
		this.entityStorage.getBacklog().subscribe('showEpicSearcher', this.onShowEpicSearcher.bind(this));
		this.entityStorage.getBacklog().subscribe('startDecomposition', this.onStartDecomposition.bind(this));
		this.entityStorage.getBacklog().subscribe('tagsSearchOpen', this.onTagsSearchOpen.bind(this));
		this.entityStorage.getBacklog().subscribe('tagsSearchClose', this.onTagsSearchClose.bind(this));
		this.entityStorage.getBacklog().subscribe('epicSearchOpen', this.onEpicSearchOpen.bind(this));
		this.entityStorage.getBacklog().subscribe('epicSearchClose', this.onEpicSearchClose.bind(this));
		this.entityStorage.getBacklog().subscribe('filterByEpic', this.onFilterByEpic.bind(this));
		this.entityStorage.getBacklog().subscribe('filterByTag', this.onFilterByTag.bind(this));
		this.entityStorage.getBacklog().subscribe('activateGroupMode', this.onActivateGroupMode.bind(this));
		this.entityStorage.getBacklog().subscribe('deactivateGroupMode', this.onDeactivateGroupMode.bind(this));
		this.entityStorage.getBacklog().subscribe('loadItems', this.onLoadItems.bind(this));

		this.entityStorage.getSprints().forEach((sprint) => this.subscribeToSprint(sprint));

		this.epic.subscribe('filterByTag', this.onFilterByTag.bind(this));
	}

	subscribeToSprint(sprint: Sprint)
	{
		sprint.subscribe('createTaskItem', this.onCreateTaskItem.bind(this));
		sprint.subscribe('updateItem', this.onUpdateItem.bind(this));
		sprint.subscribe('showTask', this.onShowTask.bind(this));
		sprint.subscribe('moveItem', this.onMoveItem.bind(this));
		sprint.subscribe('moveToSprint', this.onMoveToSprint.bind(this));
		sprint.subscribe('moveToBacklog', this.onMoveToBacklog.bind(this));
		sprint.subscribe('removeItem', this.onRemoveItem.bind(this));
		sprint.subscribe('startSprint', this.onStartSprint.bind(this));
		sprint.subscribe('completeSprint', this.onCompleteSprint.bind(this));
		sprint.subscribe('changeTaskResponsible', this.onChangeTaskResponsible.bind(this));
		sprint.subscribe('removeSprint', this.onRemoveSprint.bind(this));
		sprint.subscribe('changeSprintName', this.onChangeSprintName.bind(this));
		sprint.subscribe('changeSprintDeadline', this.onChangeSprintDeadline.bind(this));
		sprint.subscribe('attachFilesToTask', this.onAttachFilesToTask.bind(this));
		sprint.subscribe('showDod', this.onShowDod.bind(this));
		sprint.subscribe('showTagSearcher', this.onShowTagSearcher.bind(this));
		sprint.subscribe('showEpicSearcher', this.onShowEpicSearcher.bind(this));
		sprint.subscribe('startDecomposition', this.onStartDecomposition.bind(this));
		sprint.subscribe('tagsSearchOpen', this.onTagsSearchOpen.bind(this));
		sprint.subscribe('tagsSearchClose', this.onTagsSearchClose.bind(this));
		sprint.subscribe('epicSearchOpen', this.onEpicSearchOpen.bind(this));
		sprint.subscribe('epicSearchClose', this.onEpicSearchClose.bind(this));
		sprint.subscribe('filterByEpic', this.onFilterByEpic.bind(this));
		sprint.subscribe('filterByTag', this.onFilterByTag.bind(this));
		sprint.subscribe('activateGroupMode', this.onActivateGroupMode.bind(this));
		sprint.subscribe('deactivateGroupMode', this.onDeactivateGroupMode.bind(this));
		sprint.subscribe('getSprintCompletedItems', this.onGetSprintCompletedItems.bind(this));
		sprint.subscribe('showSprintBurnDownChart', this.onShowSprintBurnDownChart.bind(this));
		sprint.subscribe('toggleSubTasks', this.onToggleSubTasks.bind(this));
		sprint.subscribe('loadItems', this.onLoadItems.bind(this));
	}

	showErrorAlert(message: string)
	{
		MessageBox.alert(message, Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP'));
	}

	onCreateTaskItem(baseEvent: BaseEvent)
	{
		const data = baseEvent.getData();
		const entity = baseEvent.getTarget();
		const inputObject = data.inputObject;
		const inputValue = data.value;

		let newItem = null;

		try
		{
			newItem = this.createItem('task', inputValue);
		}
		catch (error)
		{
			this.showErrorAlert(error.message);

			return;
		}

		this.pullItem.addTmpIdsToSkipAdding(newItem.getItemId());

		this.fillItemBeforeCreation(entity, newItem, inputValue);

		this.domBuilder.appendItemAfterItem(newItem.render(), inputObject.getNode());
		newItem.onAfterAppend(entity.getListItemsNode());

		newItem.setParentId(inputObject.getEpicId());
		inputObject.setEpicId(0);

		this.sendRequestToCreateTask(entity, newItem).then((response) => {
			this.fillItemAfterCreation(newItem, response.data);
			response.data.tags.forEach((tag) => {
				this.tagSearcher.addTagToSearcher(tag);
			});
			entity.setItem(newItem);
			this.updateEntityCounters(entity);
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	onUpdateItem(baseEvent: BaseEvent)
	{
		const entity = baseEvent.getTarget();
		const updateData = baseEvent.getData();

		this.pullItem.addIdToSkipUpdating(updateData.itemId);

		this.requestSender.updateItem(baseEvent.getData())
			.then(() => {
				const isStoryPointsUpdated = (!Type.isUndefined(updateData.storyPoints));
				if (isStoryPointsUpdated)
				{
					this.updateEntityCounters(entity);
				}
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	onRemoveItem(baseEvent: BaseEvent)
	{
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
	}

	onMoveItem(baseEvent: BaseEvent)
	{
		const data = baseEvent.getData();
		this.itemMover.moveItem(data.item, data.button);
	}

	onMoveToSprint(baseEvent: BaseEvent)
	{
		const data = baseEvent.getData();
		const entityFrom = baseEvent.getTarget();
		this.itemMover.moveToAnotherEntity(entityFrom, data.item, null, data.button);
		if (this.entityStorage.getSprintsAvailableForFilling(entityFrom).size <= 1)
		{
			this.domBuilder.remove(data.button.parentNode);
		}
	}

	onMoveToBacklog(baseEvent: BaseEvent)
	{
		const data = baseEvent.getData();
		this.itemMover.moveToAnotherEntity(data.sprint, data.item, this.entityStorage.getBacklog());
	}

	onChangeTaskResponsible(baseEvent: BaseEvent)
	{
		this.pullItem.addIdToSkipUpdating(baseEvent.getData().getItemId());
		this.requestSender.changeTaskResponsible({
			itemId: baseEvent.getData().getItemId(),
			itemType: baseEvent.getData().getItemType(),
			sourceId: baseEvent.getData().getSourceId(),
			responsible: baseEvent.getData().getResponsible(),
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	onStartSprint(baseEvent: BaseEvent)
	{
		const sprint = baseEvent.getTarget();
		const sprintSidePanel = new SprintSidePanel({
			sprints: this.entityStorage.getSprints(),
			sidePanel: this.sidePanel,
			requestSender: this.requestSender,
			views: this.views
		});
		sprintSidePanel.showStartSidePanel(sprint);
	}

	onCompleteSprint(baseEvent: BaseEvent)
	{
		const sprint = baseEvent.getTarget();
		const sprintSidePanel = new SprintSidePanel({
			sprints: this.entityStorage.getSprints(),
			sidePanel: this.sidePanel,
			requestSender: this.requestSender,
			views: this.views
		});
		sprintSidePanel.showCompleteSidePanel(sprint);
	}

	onRemoveSprint(baseEvent: BaseEvent)
	{
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
	}

	onChangeSprintName(baseEvent: BaseEvent)
	{
		const requestData = baseEvent.getData();
		this.pullSprint.addIdToSkipUpdating(requestData.sprintId);
		this.requestSender.changeSprintName(requestData).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	onChangeSprintDeadline(baseEvent: BaseEvent)
	{
		const requestData = baseEvent.getData();
		this.pullSprint.addIdToSkipUpdating(requestData.sprintId);
		this.requestSender.changeSprintDeadline(requestData).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	onGetSprintCompletedItems(baseEvent: BaseEvent)
	{
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
	}

	onShowSprintBurnDownChart(baseEvent: BaseEvent)
	{
		const sprint = baseEvent.getTarget();
		const sprintSidePanel = new SprintSidePanel({
			sprints: this.entityStorage.getSprints(),
			sidePanel: this.sidePanel,
			requestSender: this.requestSender,
			views: this.views
		});
		sprintSidePanel.showBurnDownChart(sprint);
	}

	onShowTask(baseEvent: BaseEvent)
	{
		const item = baseEvent.getData();
		this.sidePanel.openSidePanelByUrl(this.pathToTask.replace('#task_id#', item.getSourceId()));
	}

	onAttachFilesToTask(baseEvent: BaseEvent)
	{
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
	}

	onShowDod(baseEvent: BaseEvent)
	{
		const item = baseEvent.getData();

		this.itemDod.skipNotificationPopups();

		this.itemDod.showList(item.getSourceId())
			.then(() => {})
			.catch(() => {})
		;
	}

	onToggleSubTasks(baseEvent: BaseEvent)
	{
		const sprint = baseEvent.getTarget();
		const item = baseEvent.getData();

		this.subTasksCreator.toggleSubTasks(sprint, item);
	}

	onShowTagSearcher(baseEvent: BaseEvent)
	{
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
	}

	onShowEpicSearcher(baseEvent: BaseEvent)
	{
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
	}

	onStartDecomposition(baseEvent: BaseEvent)
	{
		const entity = baseEvent.getTarget();
		const parentItem = baseEvent.getData();

		const decomposition = new Decomposition({
			entity: entity,
			itemStyleDesigner: this.itemStyleDesigner,
			subTasksCreator: this.subTasksCreator
		});
		decomposition.subscribe('tagsSearchOpen', this.onTagsSearchOpen.bind(this));
		decomposition.subscribe('tagsSearchClose', this.onTagsSearchClose.bind(this));
		decomposition.subscribe('createItem', (innerBaseEvent) => {
			const inputValue = innerBaseEvent.getData();
			const decomposedItems = decomposition.getDecomposedItems();
			const lastDecomposedItem = Array.from(decomposedItems).pop();

			let newItem = null;

			try
			{
				newItem = this.createItem(parentItem.getItemType(), inputValue);
			}
			catch (error)
			{
				this.showErrorAlert(error.message);

				return;
			}

			this.pullItem.addTmpIdsToSkipAdding(newItem.getItemId());
			this.pullItem.addIdToSkipUpdating(parentItem.getItemId());

			newItem.setParentEntity(entity.getId(), entity.getEntityType());
			newItem.setParentId(parentItem.getParentId());
			newItem.setParentTaskId(parentItem.getSourceId());
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
				newItem.setSort(decomposition.getSubTasks(parentItem).length + 1);
				newItem.setSubTask('Y');
				newItem.setParentTaskId(parentItem.getSourceId());
				newItem.setParentTask('N');

				if (decomposition.isFirstDecomposition())
				{
					newItem.setStoryPoints(parentItem.getStoryPoints().getPoints());
				}

				parentItem.setParentTask('Y');
				parentItem.setSubTasksCount(parentItem.getSubTasksCount() + 1);
				parentItem.updateParentTaskNodes();
			}

			this.domBuilder.appendItemAfterItem(
				newItem.render(),
				decomposition.getLastDecomposedItemNode(parentItem)
			);
			newItem.onAfterAppend(entity.getListItemsNode());

			decomposition.addDecomposedItem(newItem);

			this.sendRequestToCreateTask(entity, newItem).then((response) => {
				this.fillItemAfterCreation(newItem, response.data);
				response.data.tags.forEach((tag) => {
					this.tagSearcher.addTagToSearcher(tag);
				});
				entity.setItem(newItem);
				this.updateEntityCounters(entity);
				if (!decomposition.isBacklogDecomposition())
				{
					if (lastDecomposedItem.getItemId() === parentItem.getItemId())
					{
						parentItem.updateSubTasksPoints(newItem.getSourceId(), newItem.getStoryPoints());
						parentItem.setStoryPoints('');
					}

					this.subTasksCreator.addSubTask(parentItem, newItem);
				}
			});
		});
		decomposition.subscribe('updateParentItem', this.onUpdateItem.bind(this));

		decomposition.decomposeItem(parentItem);
	}

	onTagsSearchOpen(baseEvent: BaseEvent)
	{
		const data = baseEvent.getData();
		const inputObject = data.inputObject;
		const enteredHashTagName = data.enteredHashTagName;
		this.tagSearcher.showTagsSearchDialog(inputObject, enteredHashTagName);
	}

	onTagsSearchClose()
	{
		this.tagSearcher.closeTagsSearchDialog();
	}

	onFilterByTag(baseEvent: BaseEvent)
	{
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
	}

	onEpicSearchOpen(baseEvent: BaseEvent)
	{
		const data = baseEvent.getData();
		const inputObject = data.inputObject;
		const enteredHashEpicName = data.enteredHashEpicName;
		this.tagSearcher.showEpicSearchDialog(inputObject, enteredHashEpicName);
	}

	onEpicSearchClose()
	{
		this.tagSearcher.closeEpicSearchDialog();
	}

	onFilterByEpic(baseEvent: BaseEvent)
	{
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
	}

	onActivateGroupMode(baseEvent: BaseEvent)
	{
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
	}

	onDeactivateGroupMode(baseEvent: BaseEvent)
	{
		const entity = baseEvent.getTarget();
		entity.getItems().forEach((item: Item) => {
			item.deactivateGroupMode();
		});
	}

	onLoadItems(baseEvent: BaseEvent)
	{
		const entity = baseEvent.getTarget();

		entity.setActiveLoadItems(true);

		const loader = entity.showItemsLoader();

		const requestData = {
			entityId: entity.getId(),
			pageNumber: entity.getPageNumberItems() + 1
		};

		this.requestSender.getItems(requestData)
			.then((response) => {
				const items = response.data;
				if (Type.isArray(items) && items.length)
				{
					entity.incrementPageNumberItems();
					entity.setActiveLoadItems(false);

					this.createItemsInEntity(entity, items);
				}
				loader.hide();
			})
			.catch((response) => {
				loader.hide();
				entity.setActiveLoadItems(false);
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	createItemsInEntity(entity: Entity, items: Array)
	{
		items.forEach((itemData: ItemParams) => {
			const item = new Item(itemData);
			item.setEntityType(entity.getEntityType());
			if (!this.entityStorage.findItemByItemId(item.getItemId()))
			{
				this.domBuilder.append(item.render(), entity.getListItemsNode());
				item.onAfterAppend(entity.getListItemsNode());
				entity.setItem(item);
			}
		});
	}

	onShowTeamSpeedChart(baseEvent: BaseEvent)
	{
		const teamSpeedSidePanel = new TeamSpeedSidePanel({
			sidePanel: this.sidePanel,
			requestSender: this.requestSender
		});
		teamSpeedSidePanel.showTeamSpeedChart();
	}

	onOpenAddEpicForm(baseEvent: BaseEvent)
	{
		this.epic.openAddForm();
	}

	onOpenListEpicGrid(baseEvent: BaseEvent)
	{
		this.epic.openEpicsList();
		this.epic.subscribe('onAfterEditEpic', (innerBaseEvent) => {
			const response = innerBaseEvent.getData();
			const updatedEpicInfo = response.data;
			this.epic.onAfterUpdateEpic(updatedEpicInfo);
		});
	}

	onOpenDefinitionOfDone(baseEvent: BaseEvent)
	{
		const entity = baseEvent.getTarget();

		const sidePanel = new DodSidePanel({
			sidePanel: this.sidePanel,
			requestSender: this.requestSender
		});

		sidePanel.showSettingsPanel(entity);
	}

	createItem(itemType: string, value: string): Item
	{
		const valueWithoutTags = value
			.replace(new RegExp(TagSearcher.tagRegExp,'g'), '')
			.replace(new RegExp(TagSearcher.epicRegExp,'g'), '');

		return new Item({
			'itemId': '',
			'itemType': itemType,
			'name': valueWithoutTags
		});
	}

	sendRequestToCreateTask(entity: Entity, item: Item): Promise
	{
		const requestData = {
			'tmpId': item.getItemId(),
			'itemType': item.getItemType(),
			'name': item.getName(),
			'entityId': item.getEntityId(),
			'entityType': entity.getEntityType(),
			'parentId': item.getParentId(),
			'sort': item.getSort(),
			'storyPoints': item.getStoryPoints().getPoints(),
			'tags': item.getTags(),
			'epic': item.getEpic(),
			'parentTaskId': item.getParentTaskId(),
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

	updateEntityCounters(sourceEntity: Entity, endEntity?: Entity)
	{
		const entities = new Map();

		entities.set(sourceEntity.getId(), sourceEntity);
		if (endEntity)
		{
			entities.set(endEntity.getId(), endEntity);
		}

		this.entityCounters.updateCounters(entities);
	}
}