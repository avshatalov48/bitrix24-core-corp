import {Type, Loc, Dom, ajax as Ajax} from 'main.core';
import {BaseEvent} from 'main.core.events';
import {Loader} from 'main.loader';
import {Menu} from 'main.popup';

import {MessageBox} from 'ui.dialogs.messagebox';

import {ShortView} from 'ui.short-view';

import {DiskManager} from '../../service/disk.manager';

import {View, Views} from '../view';

import {PlanBuilder} from './plan.builder';

import {Entity} from '../../entity/entity';
import {ActionPanel} from '../../entity/action.panel';
import {EntityStorage} from '../../entity/entity.storage';
import {SearchItems} from '../../entity/search.items';
import {Backlog, BacklogParams} from '../../entity/backlog/backlog';
import {Header as BacklogHeader} from '../../entity/backlog/header';
import {Sprint, SprintParams} from '../../entity/sprint/sprint';
import {SprintMover} from '../../entity/sprint/sprint.mover';
import {SprintSidePanel} from '../../entity/sprint/sprint.side.panel';

import {Item, ItemParams} from '../../item/item';
import {SubTasks} from '../../item/task/sub.tasks';
import {ItemMover} from '../../item/item.mover';
import {ItemDesigner} from '../../item/item.designer';

import {Epic} from '../../epic/epic';

import {PULL as Pull} from 'pull.client';

import {PullSprint} from '../../pull/pull.sprint';
import {PullItem} from '../../pull/pull.item';
import {PullEpic} from '../../pull/pull.epic';

import {TaskCounters} from '../../counters/task.counters';
import {EntityCounters} from '../../counters/entity.counters';

import {FilterHandler} from '../../utility/filter.handler';
import {Input} from '../../utility/input';
import {TagSearcher} from '../../utility/tag.searcher';
import {Decomposition} from '../../utility/decomposition';
import {Tool} from '../../utility/tool';

import type {EpicType} from '../../item/task/epic';
import type {ResponsibleType} from '../../item/task/responsible';

import type {ShowLinkedTasksResponse} from '../../response';
import { PullTag } from '../../pull/pull.tag';
import { sendData } from 'ui.analytics';

type Params = {
	pathToTask: string,
	pathToTaskCreate: string,
	pathToBurnDown: string,
	defaultSprintDuration: number,
	activeSprintId: number,
	backlog: BacklogParams,
	sprints: Array<SprintParams>,
	views: Views,
	defaultResponsible: ResponsibleType,
	pageSize: number,
	isShortView: 'Y' | 'N',
	displayPriority: string,
	mandatoryExists: 'Y' | 'N',
	isExactSearchApplied: 'Y' | 'N'
}

export class Plan extends View
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Scrum.Plan');

		this.pathToTask = params.pathToTask;
		this.pathToTaskCreate = params.pathToTaskCreate;
		this.pathToBurnDown = params.pathToBurnDown;
		this.mandatoryExists = params.mandatoryExists === 'Y';
		this.defaultResponsible = params.defaultResponsible;
		this.pageSize = parseInt(params.pageSize, 10);
		this.activeSprintId = parseInt(params.activeSprintId, 10);
		this.views = params.views;
		this.isShortView = params.isShortView;
		this.displayPriority = params.displayPriority;
		this.isExactSearchApplied = params.isExactSearchApplied;

		this.entityStorage = new EntityStorage();
		this.entityStorage.addBacklog(Backlog.buildBacklog(params.backlog));
		params.sprints.forEach((sprintData) => {
			sprintData.defaultSprintDuration = params.defaultSprintDuration;
			sprintData.isShortView = params.isShortView;
			const sprint = Sprint.buildSprint(sprintData);
			this.entityStorage.addSprint(sprint);
		});

		this.entityCounters = new EntityCounters({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage
		});

		this.taskCounters = new TaskCounters({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			filter: this.filter,
			userId: params.userId,
			groupId: params.groupId,
			isOwnerCurrentUser: params.isOwnerCurrentUser
		});

		this.tagSearcher = new TagSearcher({
			requestSender: this.requestSender,
			filter: this.filter,
			groupId: params.groupId,
			tagsAreConverting: params.tagsAreConverting,
		});

		this.planBuilder = new PlanBuilder({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			defaultSprintDuration: params.defaultSprintDuration,
			pageNumberToCompletedSprints: params.pageNumberToCompletedSprints,
			displayPriority: params.displayPriority,
			isShortView: params.isShortView,
			mandatoryExists: params.mandatoryExists,
			isExactSearchApplied: params.isExactSearchApplied,
			pageSize: this.pageSize
		});
		this.planBuilder.subscribe('beforeCreateSprint', (baseEvent: BaseEvent) => {
			const requestData = baseEvent.getData();
			this.pullSprint.addTmpIdToSkipAdding(requestData.tmpId);
		});
		this.planBuilder.subscribe(
			'createSprint',
			(baseEvent: BaseEvent) => {
				const sprint: Sprint = baseEvent.getData();
				this.subscribeToSprint(sprint);
				this.itemMover.addSprintContainers(sprint);
			}
		);
		this.planBuilder.subscribe(
			'createSprintNode',
			(baseEvent: BaseEvent) => {
				const sprint: Sprint = baseEvent.getData();
				this.subscribeToSprint(sprint);
				this.itemMover.addSprintContainers(sprint);
			}
		);
		this.planBuilder.subscribe('sprintsScroll', this.onActionPanelScroll.bind(this));

		this.searchItems = new SearchItems({
			planBuilder: this.planBuilder,
			entityStorage: this.entityStorage
		});

		this.sprintMover = new SprintMover({
			requestSender: this.requestSender,
			planBuilder: this.planBuilder,
			entityStorage: this.entityStorage
		});

		this.itemMover = new ItemMover({
			requestSender: this.requestSender,
			planBuilder: this.planBuilder,
			entityStorage: this.entityStorage,
			entityCounters: this.entityCounters
		});

		this.itemDesigner = new ItemDesigner({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage
		});

		this.filterHandler = new FilterHandler({
			filter: this.filter,
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			planBuilder: this.planBuilder,
			pageSize: this.pageSize
		});

		this.epic = new Epic({
			groupId: this.groupId,
			sidePanel: this.sidePanel,
			entityStorage: this.entityStorage,
			filter: this.filter,
			tagSearcher: this.tagSearcher
		});
		this.pullTag = new PullTag({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			groupId: this.groupId
		});
		this.pullSprint = new PullSprint({
			requestSender: this.requestSender,
			planBuilder: this.planBuilder,
			entityStorage: this.entityStorage,
			groupId: this.getCurrentGroupId()
		});
		this.pullItem = new PullItem({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			entityCounters: this.entityCounters,
			tagSearcher: this.tagSearcher,
			itemMover: this.itemMover,
			currentUserId: this.getCurrentUserId(),
			groupId: this.getCurrentGroupId()
		});
		this.pullEpic = new PullEpic({
			requestSender: this.requestSender,
			entityStorage: this.entityStorage,
			epic: this.epic,
		});

		this.input = new Input();
		this.input.subscribe('createTaskItem', this.onCreateTaskItem.bind(this));
		this.input.subscribe('tagsSearchOpen', this.onTagsSearchOpen.bind(this));
		this.input.subscribe('tagsSearchClose', this.onTagsSearchClose.bind(this));
		this.input.subscribe('epicSearchOpen', this.onEpicSearchOpen.bind(this));
		this.input.subscribe('epicSearchClose', this.onEpicSearchClose.bind(this));
		this.input.subscribe('render', this.onRenderInput.bind(this));
		this.input.subscribe('remove', this.onRemoveInput.bind(this));

		this.actionPanel = null;
		this.responsibleDialog = null;

		this.bindHandlers();

		this.subscribeToPull();
	}

	renderTo(container: HTMLElement)
	{
		super.renderTo(container);

		this.planBuilder.renderTo(container);
	}

	renderRightElementsTo(container: HTMLElement)
	{
		super.renderRightElementsTo(container);

		const shortView = new ShortView({
			isShortView: this.isShortView
		});

		shortView.renderTo(container);
		shortView.subscribe('change', this.onChangeShortView.bind(this));
	}

	setDisplayPriority(value: string)
	{
		super.setDisplayPriority(value);

		this.planBuilder.setWidthPriority(value);

		this.requestSender.saveDisplayPriority({
			value: value
		})
			.then((response) => {})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	changeShortView(isShortView: 'Y' | 'N')
	{
		super.changeShortView(isShortView);

		this.planBuilder.setShortView(isShortView);

		this.destroyActionPanel();

		const entities = this.entityStorage.getAllEntities();

		entities
			.forEach((entity: Entity) => {
				if (!entity.isCompleted())
				{
					entity.deactivateGroupMode();
					entity.fadeOut();
				}
			})
		;

		this.requestSender.saveShortView({
			isShortView: isShortView,
		})
			.then((response) => {
				entities
					.forEach((entity: Entity) => {
						if (!entity.isCompleted())
						{
							entity.setShortView(isShortView);
							entity.adjustListItemsWidth();
							entity.fadeIn();
						}
					})
				;
			})
			.catch((response) => {
				entities
					.forEach((entity: Entity) => {
						if (!entity.isCompleted())
						{
							entity.fadeIn();
						}
					})
				;
				this.requestSender.showErrorAlert(response);
			});
	}

	subscribeToPull()
	{
		Pull.subscribe(this.pullSprint);
		Pull.subscribe(this.pullItem);
		Pull.subscribe(this.pullEpic);
		Pull.subscribe(this.pullTag);
	}

	bindHandlers()
	{
		this.entityStorage.getBacklog().subscribe('showInput', this.onShowBacklogInput.bind(this));
		this.entityStorage.getBacklog().subscribe('openAddTaskForm', this.onOpenAddTaskForm.bind(this));
		this.entityStorage.getBacklog().subscribe('updateItem', this.onUpdateItem.bind(this));
		this.entityStorage.getBacklog().subscribe('showTask', this.onShowTask.bind(this));
		this.entityStorage.getBacklog().subscribe('destroyActionPanel', this.onDestroyActionPanel.bind(this));
		this.entityStorage.getBacklog().subscribe('changeTaskResponsible', this.onChangeTaskResponsible.bind(this));
		this.entityStorage.getBacklog().subscribe('onShowResponsibleDialog', this.onShowResponsibleDialog.bind(this));
		this.entityStorage.getBacklog().subscribe('openAddEpicForm', this.onOpenEpicForm.bind(this));
		this.entityStorage.getBacklog().subscribe('tagsSearchOpen', this.onTagsSearchOpen.bind(this));
		this.entityStorage.getBacklog().subscribe('tagsSearchClose', this.onTagsSearchClose.bind(this));
		this.entityStorage.getBacklog().subscribe('epicSearchOpen', this.onEpicSearchOpen.bind(this));
		this.entityStorage.getBacklog().subscribe('epicSearchClose', this.onEpicSearchClose.bind(this));
		this.entityStorage.getBacklog().subscribe('filterByEpic', this.onFilterByEpic.bind(this));
		this.entityStorage.getBacklog().subscribe('filterByTag', this.onFilterByTag.bind(this));
		this.entityStorage.getBacklog().subscribe('loadItems', (baseEvent: BaseEvent) => {
			const entity: Entity = baseEvent.getTarget();
			this.planBuilder.loadItems(entity);
		});
		this.entityStorage.getBacklog().subscribe('toggleActionPanel', this.onToggleActionPanel.bind(this));
		this.entityStorage.getBacklog().subscribe('showLinked', this.onShowLinked.bind(this));
		this.entityStorage.getBacklog().subscribe('itemsScroll', this.onActionPanelScroll.bind(this));
		this.entityStorage.getBacklog().subscribe('showBlank', this.onShowBlank.bind(this));
		this.entityStorage.getBacklog().subscribe('deactivateGroupMode', this.onDeactivateGroupMode.bind(this));

		this.entityStorage.getSprints().forEach((sprint) => this.subscribeToSprint(sprint));

		this.epic.subscribe('filterByTag', this.onFilterByTag.bind(this));

		this.itemMover.subscribe('dragStart', () => {
			this.destroyActionPanel();
			if (this.searchItems.isActive())
			{
				this.searchItems.stop();
			}
		});

		document.onkeydown = this.onDocumentKeyDown.bind(this);
		document.onclick = this.onDocumentClick.bind(this);
	}

	subscribeToSprint(sprint: Sprint)
	{
		sprint.subscribe('showInput', this.onShowSprintInput.bind(this));
		sprint.subscribe('createSprint', this.onCreateSprint.bind(this));
		sprint.subscribe('updateItem', this.onUpdateItem.bind(this));
		sprint.subscribe('getSubTasks', this.onGetSubTasks.bind(this));
		sprint.subscribe('showTask', this.onShowTask.bind(this));
		sprint.subscribe('destroyActionPanel', this.onDestroyActionPanel.bind(this));
		sprint.subscribe('startSprint', this.onStartSprint.bind(this));
		sprint.subscribe('completeSprint', this.onCompleteSprint.bind(this));
		sprint.subscribe('changeTaskResponsible', this.onChangeTaskResponsible.bind(this));
		sprint.subscribe('onShowResponsibleDialog', this.onShowResponsibleDialog.bind(this));
		sprint.subscribe('removeSprint', this.onRemoveSprint.bind(this));
		sprint.subscribe('changeSprintName', this.onChangeSprintName.bind(this));
		sprint.subscribe('changeSprintDeadline', this.onChangeSprintDeadline.bind(this));
		sprint.subscribe('tagsSearchOpen', this.onTagsSearchOpen.bind(this));
		sprint.subscribe('tagsSearchClose', this.onTagsSearchClose.bind(this));
		sprint.subscribe('epicSearchOpen', this.onEpicSearchOpen.bind(this));
		sprint.subscribe('epicSearchClose', this.onEpicSearchClose.bind(this));
		sprint.subscribe('filterByEpic', this.onFilterByEpic.bind(this));
		sprint.subscribe('filterByTag', this.onFilterByTag.bind(this));
		sprint.subscribe('getSprintCompletedItems', this.onGetSprintCompletedItems.bind(this));
		sprint.subscribe('showSprintBurnDownChart', this.onShowSprintBurnDownChart.bind(this));
		sprint.subscribe('showSprintCreateMenu', this.onOpenSprintAddMenu.bind(this));
		sprint.subscribe('loadItems', (baseEvent: BaseEvent) => {
			const entity: Entity = baseEvent.getTarget();
			this.planBuilder.loadItems(entity);
		});
		sprint.subscribe('toggleActionPanel', this.onToggleActionPanel.bind(this));
		sprint.subscribe('showLinked', this.onShowLinked.bind(this));
		sprint.subscribe('showDropzone', this.onShowDropZone.bind(this));
		sprint.subscribe('toggleVisibilityContent', this.onToggleVisibilityContent.bind(this));
		sprint.subscribe('deactivateGroupMode', this.onDeactivateGroupMode.bind(this));
	}

	showErrorAlert(message: string)
	{
		MessageBox.alert(message, Loc.getMessage('TASKS_SCRUM_ERROR_TITLE_POPUP'));
	}

	onDocumentKeyDown(event)
	{
		event = event || window.event;

		if (this.searchItems.isActive())
		{
			if (event.key === 'ArrowUp' || event.key === 'ArrowLeft')
			{
				event.preventDefault();

				this.searchItems.moveToPrev();
			}

			if (event.key === 'ArrowDown' || event.key === 'ArrowRight')
			{
				event.preventDefault();

				this.searchItems.moveToNext();
			}
		}

		if (event.key === 'Escape')
		{
			let prevented = false;

			if (this.searchItems.isActive())
			{
				prevented = true;

				this.searchItems.stop();
			}

			if (this.actionPanel)
			{
				prevented = true;

				this.destroyActionPanel();

				this.entityStorage.getAllEntities()
					.forEach((entity: Entity) => {
						entity.deactivateGroupMode();
					})
				;
			}

			if (prevented)
			{
				event.stopImmediatePropagation();
			}
		}
	}

	onDocumentClick(event)
	{
		event = event || window.event;

		if (this.searchItems.isActive() && !this.searchItems.isClickInside(event.target))
		{
			this.searchItems.stop();
		}

		if (this.diskManager && !this.diskManager.isClickInside(event.target))
		{
			this.diskManager.closeAttachmentMenu();
			this.diskManager = null;
		}
	}

	onShowBacklogInput(baseEvent: BaseEvent)
	{
		const backlog: Backlog = baseEvent.getTarget();
		const header: ?BacklogHeader = baseEvent.getData();

		this.showInput(backlog).then(() => {
			if (!Type.isPlainObject(header))
			{
				header.unLockTaskButton();
			}
		});
	}

	onShowSprintInput(baseEvent: BaseEvent)
	{
		const sprint: Sprint = baseEvent.getTarget();

		if (sprint.isHideContent())
		{
			sprint.subscribeOnce('toggleVisibilityContent', this.showInput.bind(this, sprint));
			sprint.toggleVisibilityContent(sprint.getContentContainer());
		}
		else
		{
			this.showInput(sprint);
		}
	}

	onOpenAddTaskForm(baseEvent: BaseEvent)
	{
		const header: ?BacklogHeader = baseEvent.getData();

		this.sidePanel.subscribeOnce('onLoadSidePanel', () => {
			if (!Type.isPlainObject(header))
			{
				header.unLockTaskButton();
			}
		});

		this.sidePanel.openSidePanelByUrl(this.pathToTaskCreate.replace('#task_id#', 0));
	}

	onCreateSprint()
	{
		this.planBuilder.createSprint();
	}

	onRenderInput(baseEvent: BaseEvent)
	{
		this.deactivateDraggable();

		const input: Input = baseEvent.getTarget();

		const entity = input.getEntity();

		entity.hideBlank();
		entity.hideDropzone();
		entity.hideEmptySearchStub();
	}

	onRemoveInput(baseEvent: BaseEvent)
	{
		this.activateDraggable();

		const input: Input = baseEvent.getTarget();

		const entity = input.getEntity();

		if (!input.isTaskCreated() && entity.isEmpty())
		{
			if (entity.isBacklog())
			{
				if (this.entityStorage.existsAtLeastOneItem())
				{
					if (entity.isExactSearchApplied())
					{
						entity.showEmptySearchStub();
					}
					else
					{
						entity.showDropzone();
					}
				}
				else
				{
					entity.showBlank();
				}
			}
			else
			{
				if (entity.getNumberTasks() > 0 && entity.isExactSearchApplied())
				{
					entity.showEmptySearchStub();
				}
				else
				{
					entity.showDropzone();
				}
			}
		}


		if (this.decomposition)
		{
			this.decomposition.getParentItem().deactivateDecompositionMode();
			this.decomposition = null;
		}

		entity.adjustListItemsWidth();
	}

	onCreateTaskItem(baseEvent: BaseEvent)
	{
		const input: Input = baseEvent.getTarget();

		const entity = input.getEntity();

		let newItem: Item = null;

		try
		{
			newItem = this.createItem(input);
		}
		catch (error)
		{
			this.showErrorAlert(error.message);

			input.removeYourself();

			return;
		}

		if (this.decomposition)
		{
			const parentItem: Item = this.decomposition.getParentItem();

			this.pullItem.addIdToSkipUpdating(parentItem.getId());

			newItem.setParentEntity(entity.getId(), entity.getEntityType());
			newItem.setParentTaskId(parentItem.getSourceId());
			newItem.setEpic(parentItem.getEpic().getValue());
			newItem.setTags(parentItem.getTags().getValue());
			newItem.setResponsible(parentItem.getResponsible().getValue());

			if (entity.isBacklog())
			{
				parentItem.setLinkedTask('Y');
				parentItem.updateBorderColor();

				newItem.setLinkedTask('Y');
				newItem.setBorderColor(parentItem.getBorderColor());
				newItem.setSort(parentItem.getSort() + this.decomposition.getNumberDecompositionsPerformed());

				Dom.insertBefore(newItem.render(), input.getNode());
			}
			else
			{
				newItem.setSubTask('Y');
				newItem.setSort(parentItem.getSort() + (parentItem.getSubTasksCount() + 1));
				newItem.setParentTaskId(parentItem.getSourceId());
				newItem.setParentTask('N');
			}

			this.decomposition.addNumberDecompositionsPerformed();
		}
		else
		{
			newItem.setEpic(input.getEpic());

			input.setEpic(null);

			newItem.setParentEntity(entity.getId(), entity.getEntityType());
			newItem.setSort(1);
			newItem.setResponsible(this.defaultResponsible);

			if (entity.isEmpty())
			{
				Dom.insertBefore(newItem.render(), entity.getLoaderNode());
			}
			else
			{
				Dom.insertBefore(newItem.render(), entity.getFirstItemNode(this.input));
			}
		}

		this.pullItem.addTmpIdToSkipAdding(newItem.getId());
		this.pullItem.addTmpIdToSkipSorting(newItem.getId());

		this.sendRequestToCreateTask(entity, newItem).then((response) => {

			this.fillItemAfterCreation(newItem, response.data);

			entity.setItem(newItem);

			this.updateEntityCounters(entity);

			if (this.decomposition)
			{
				const parentItem: Item = this.decomposition.getParentItem();

				if (!entity.isBacklog())
				{
					const subTasks = parentItem.getSubTasks();

					subTasks.addTask(newItem);

					if (!subTasks.isShown())
					{
						entity.appendNodeAfterItem(subTasks.render(), parentItem.getNode());
					}

					const subTaskInfo = {};
					subTaskInfo[newItem.getSourceId()] = {
						sourceId: newItem.getSourceId(),
						completed: 'N',
						storyPoints: ''
					};

					parentItem.setSubTasksInfo({...parentItem.getSubTasksInfo(), ...subTaskInfo})
					parentItem.setParentTask('Y');

					parentItem.showSubTasks();
				}
			}

			input.unDisable();
			input.focus();

			entity.adjustListItemsWidth();
			this.planBuilder.adjustSprintListWidth();
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

	onGetSubTasks(baseEvent: BaseEvent)
	{
		const sprint: Sprint = baseEvent.getTarget();
		const subTasks: SubTasks = baseEvent.getData();

		sprint.appendNodeAfterItem(subTasks.render(), subTasks.getParentItem().getNode());

		this.requestSender.getSubTaskItems({
			entityId: sprint.getId(),
			taskId: subTasks.getParentItem().getSourceId()
		})
			.then((response) => {
				response.data.forEach((itemParams: ItemParams) => {
					const subTaskItem = Item.buildItem(itemParams);
					sprint.setItem(subTaskItem);
					subTasks.addTask(subTaskItem);
				});
				subTasks.getParentItem().showSubTasks();
				sprint.deactivateSubTaskLoading(subTasks.getParentItem());
				this.planBuilder.adjustSprintListWidth();
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	onChangeTaskResponsible(baseEvent: BaseEvent)
	{
		const item: Item = baseEvent.getData();

		this.pullItem.addIdToSkipUpdating(item.getId());

		this.requestSender.changeTaskResponsible({
			itemId: item.getId(),
			sourceId: item.getSourceId(),
			responsible: item.getResponsible().getValue(),
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	onShowResponsibleDialog(baseEvent: BaseEvent)
	{
		this.responsibleDialog = baseEvent.getData();
	}

	onOpenEpicForm(baseEvent: BaseEvent)
	{
		const header: BacklogHeader = baseEvent.getData();

		this.epic.openAddForm().then(() => {
			header.unLockEpicButton();
		});
	}

	onStartSprint(baseEvent: BaseEvent)
	{
		const sprint = baseEvent.getTarget();

		const sprintSidePanel = new SprintSidePanel({
			sidePanel: this.sidePanel,
			groupId: this.groupId,
			views: this.views
		});

		sprintSidePanel.showStartForm(sprint);
	}

	onCompleteSprint()
	{
		const sprintSidePanel = new SprintSidePanel({
			sidePanel: this.sidePanel,
			groupId: this.groupId,
			views: this.views
		});

		sprintSidePanel.showCompletionForm();

		sprintSidePanel.subscribe('showTask', (innerBaseEvent: BaseEvent) => {
			this.sidePanel.openSidePanelByUrl(this.getPathToTask().replace('#task_id#', innerBaseEvent.getData()));
		});
	}

	onRemoveSprint(baseEvent: BaseEvent)
	{
		const sprint: Sprint = baseEvent.getTarget();

		this.pullSprint.addIdToSkipRemoving(sprint.getId());

		this.requestSender.removeSprint({
			sprintId: sprint.getId(),
			sortInfo: this.sprintMover.calculateSprintSort()
		})
		.then(() => {

			if (!sprint.isEmpty())
			{
				[...sprint.getItems().values()]
					.map((item: Item) => {
						sprint.addItemToGroupMode(item);
					})
				;

				this.itemMover.moveToWithGroupMode(sprint, this.entityStorage.getBacklog(), null, false, false);
			}

			this.destroyActionPanel();

			sprint.removeYourself();

			this.entityStorage.removeSprint(sprint.getId());

			this.planBuilder.adjustSprintListWidth();
		})
		.catch((response) => {
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
		const sprint: Sprint = baseEvent.getTarget();
		const requestData = baseEvent.getData();

		this.pullSprint.addIdToSkipUpdating(requestData.sprintId);
		this.requestSender.changeSprintDeadline(requestData)
			.then((response) => {
				const sprintData = response.data;
				sprint.setDateStartFormatted(sprintData.dateStartFormatted);
				sprint.setDateEndFormatted(sprintData.dateEndFormatted);
				sprint.setDateStart(sprintData.dateStart);
				sprint.setDateEnd(sprintData.dateEnd);
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	onGetSprintCompletedItems(baseEvent: BaseEvent)
	{
		const sprint = baseEvent.getTarget();
		const listItemsNode = sprint.getListItemsNode();
		const listPosition = Dom.getPosition(listItemsNode);

		Dom.style(sprint.getContentContainer(), 'height', 'auto');

		const loader = new Loader({
			size: 60,
			mode: 'inline',
			color: '#eaeaea',
			offset: {
				left: `${(listPosition.width / 2 - 30)}px`
			}
		});

		loader.show(); // todo promise wtf

		this.requestSender.getSprintCompletedItems({
			sprintId: sprint.getId()
		}).then((response) => {
			loader.hide();
			if (response.data.length > 0)
			{
				response.data.forEach((itemParams: ItemParams) => {
					const item = Item.buildItem(itemParams);
					if (!sprint.getItems().has(item.getId()))
					{
						item.setDisableStatus(sprint.isDisabled());
						if (item.isParentTask())
						{
							item.setToggle(false);
							item.setParentTask(false);
						}

						sprint.appendItemToList(item);
						sprint.setItem(item);
					}
				});
			}
			else
			{
				sprint.showBlank();
			}
			sprint.showContent(sprint.getContentContainer());
		}).catch((response) => {
			loader.hide();
			this.requestSender.showErrorAlert(response);
		});
	}

	onShowSprintBurnDownChart(baseEvent: BaseEvent)
	{
		const sprint = baseEvent.getTarget();

		const sprintSidePanel = new SprintSidePanel({
			sidePanel: this.sidePanel,
			groupId: this.groupId,
			views: this.views,
			pathToBurnDown: this.pathToBurnDown
		});

		sprintSidePanel.showBurnDownChart(sprint);
	}

	onOpenSprintAddMenu(baseEvent: BaseEvent)
	{
		const entity = baseEvent.getTarget();
		const button = baseEvent.getData().getNode();

		if (this.sprintAddMenu)
		{
			this.sprintAddMenu.getPopupWindow().destroy();
			this.sprintAddMenu = null;

			return;
		}

		const buttonRect = button.getBoundingClientRect();

		this.sprintAddMenu = new Menu({
			id: 'tasks-scrum-sprint-add-menu',
			bindElement: button,
			closeByEsc : true,
			angle: {
				position: 'top',
				offset: 78
			},
			offsetLeft: buttonRect.width - 67
		});

		this.sprintAddMenu.addMenuItem({
			text: Loc.getMessage('TASKS_SCRUM_BACKLOG_SPRINT_FIRST_ADD'),
			onclick: (event, menuItem) => {
				this.onShowSprintInput((new BaseEvent()).setTarget(entity));
				menuItem.getMenuWindow().close();
			}
		});
		this.sprintAddMenu.addMenuItem({
			text: Loc.getMessage('TASKS_SCRUM_BACKLOG_SPRINT_SECOND_ADD'),
			onclick: (event, menuItem) => {
				this.planBuilder.createSprint();
				menuItem.getMenuWindow().close();
			}
		});

		this.sprintAddMenu.getPopupWindow().subscribe('onClose', () => {
			this.sprintAddMenu.getPopupWindow().destroy();
			this.sprintAddMenu = null;
		});
		this.sprintAddMenu.getPopupWindow().subscribe('onShow', () => {
			const angle = this.sprintAddMenu.getMenuContainer().querySelector('.popup-window-angly');
			Dom.style(angle, 'pointerEvents', 'none');
		});

		this.sprintAddMenu.show();
	}

	onShowTask(baseEvent: BaseEvent)
	{
		const item = baseEvent.getData();

		this.sidePanel.openSidePanelByUrl(this.pathToTask.replace('#task_id#', item.getSourceId()));
	}

	onDestroyActionPanel(baseEvent: BaseEvent)
	{
		const item = baseEvent.getData();

		this.destroyActionPanel();

		(this.entityStorage.findEntityByItemId(item.getId())).deactivateGroupMode();
	}

	onTagsSearchOpen(baseEvent: BaseEvent)
	{
		this.tagSearcher.showTagsSearchDialog(this.input, baseEvent.getData());
	}

	onTagsSearchClose()
	{
		this.tagSearcher.closeTagsSearchDialog();
	}

	onFilterByTag(baseEvent: BaseEvent)
	{
		const tag = baseEvent.getData();
		const currentValue = this.filter.getValueFromField({name: 'TAG', value: ''});
		const newValue = (String(tag) === String(currentValue) ? [] : [String(tag)]);

		this.filter.setValuesToField([
			{
				name: 'TAG',
				value: newValue,
			},
			{
				name: 'TAG_label',
				value: newValue,
			},
		]);
		this.filter.scrollToSearchContainer();
	}

	onEpicSearchOpen(baseEvent: BaseEvent)
	{
		this.tagSearcher.showEpicSearchDialog(this.input, baseEvent.getData());
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

	onChangeShortView(baseEvent: BaseEvent)
	{
		const isShortView = baseEvent.getData();

		this.changeShortView(isShortView);
	}

	onToggleActionPanel(baseEvent: BaseEvent)
	{
		const item = baseEvent.getData();
		const entity = baseEvent.getTarget();

		if (this.actionPanel)
		{
			const repeatedClick = (this.actionPanel.getItem().getId() === item.getId());

			this.destroyActionPanel();

			if (repeatedClick || entity.hasItemInGroupMode(item))
			{
				this.deactivateGroupMode(entity, item);

				return;
			}
		}

		this.activateGroupMode(entity, item);

		this.showActionPanel(entity, item);

		if (this.searchItems.isActive())
		{
			this.searchItems.updateCurrentIndexByItem(item);
		}
	}

	onShowLinked(baseEvent: BaseEvent)
	{
		const baseItem = baseEvent.getData();

		if (this.searchItems.isActive())
		{
			this.searchItems.stop();

			return;
		}

		this.destroyActionPanel();

		const containerPosition = Dom.getPosition(this.planBuilder.getScrumContainer());

		window.scrollTo({ top: containerPosition.top, behavior: 'smooth' });

		const loader = new Loader({
			target: this.planBuilder.getScrumContainer(),
			offset: {
				top: `${containerPosition.top / 2}px`
			}
		});

		loader.show();

		this.searchItems.deactivateGroupMode();
		this.searchItems.fadeOutAll();

		this.requestSender.showLinkedTasks({
			taskId: baseItem.getSourceId(),
		})
			.then((response: ShowLinkedTasksResponse) => {
				const itemsData: Array<ItemParams> = response.data;

				const items = new Set();
				const itemIds = [];
				itemsData.forEach((itemParams: ItemParams) => {
					const item = Item.buildItem(itemParams);
					const entity = this.entityStorage.findEntityByEntityId(item.getEntityId());
					if (entity)
					{
						if (!entity.isCompleted() && !entity.hasItem(item))
						{
							item.setShortView(entity.getShortView());
							entity.appendItemToList(item);
							entity.setItem(item);
						}

						items.add(item);
						itemIds.push(item.getId());
					}
				});

				this.itemDesigner.updateBorderColor(items);

				if (items.size > 0)
				{
					this.searchItems.start(baseItem, itemIds);
				}
				else
				{
					this.searchItems.fadeInAll();
				}

				loader.hide();
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);

				loader.hide();
			})
		;
	}

	onActionPanelScroll()
	{
		if (this.actionPanel)
		{
			this.actionPanel.calculatePanelTopPosition();
		}

		if (this.diskManager)
		{
			this.diskManager.closeAttachmentMenu();
			this.diskManager = null;
		}

		if (this.itemMover.hasActionPanelDialog())
		{
			this.itemMover.closeActionPanelDialogs();
		}

		if (this.tagSearcher.hasActionPanelDialog())
		{
			this.tagSearcher.closeActionPanelDialogs();
		}

		if (this.responsibleDialog && this.responsibleDialog.isOpen())
		{
			this.responsibleDialog.hide();
		}
	}

	onShowBlank(baseEvent: BaseEvent)
	{
		const backlog: Backlog = baseEvent.getTarget();

		setTimeout(() => {
			if (!backlog.isEmpty())
			{
				return;
			}
			if (this.entityStorage.existsAtLeastOneItem())
			{
				if (backlog.isExactSearchApplied())
				{
					backlog.showEmptySearchStub();
				}
				else
				{
					backlog.showDropzone();
				}
			}
			else
			{
				backlog.showBlank();
			}
		}, 200);
	}

	onDeactivateGroupMode(baseEvent: BaseEvent)
	{
		this.destroyActionPanel();
	}

	onShowDropZone(baseEvent: BaseEvent)
	{
		const sprint: Sprint = baseEvent.getTarget();

		setTimeout(() => {
			if (!sprint.isEmpty())
			{
				return;
			}
			if (sprint.getNumberTasks() > 0 && sprint.isExactSearchApplied())
			{
				sprint.showEmptySearchStub();
			}
			else
			{
				sprint.showDropzone();
			}
		}, 200);
	}

	onToggleVisibilityContent(baseEvent: BaseEvent)
	{
		const sprint: Sprint = baseEvent.getTarget();

		this.planBuilder.adjustSprintListWidth();

		if (!sprint.isCompleted())
		{
			this.requestSender.saveSprintVisibility({
				sprintId: sprint.getId(),
				isHidden: sprint.isHideContent() ? 'Y' : 'N'
			});
		}
	}

	showInput(entity: Entity, bindNode: ?HTMLElement): Promise
	{
		return new Promise((resolve) => {
			if (this.input.isExists())
			{
				this.input.submit();

				if (this.input.hasEntity(entity))
				{
					resolve();
				}
				else
				{
					this.input.subscribeOnce('unDisable', () => {
						this.input.removeYourself();

						this.renderInput(entity, bindNode);

						resolve();
					});
				}
			}
			else
			{
				this.renderInput(entity, bindNode);

				resolve();
			}
		});
	}

	renderInput(entity: Entity, bindNode: ?HTMLElement)
	{
		this.input.setEntity(entity);

		if (Type.isElementNode(bindNode))
		{
			this.input.setBindNode(bindNode);

			Dom.insertAfter(this.input.render(), bindNode);
		}
		else
		{
			this.input.cleanBindNode();

			if (entity.isEmpty())
			{
				Dom.insertBefore(this.input.render(), entity.getLoaderNode());
			}
			else
			{
				Dom.insertBefore(this.input.render(), entity.getFirstItemNode(this.input));
			}
		}

		entity.adjustListItemsWidth();

		this.input.focus();

		this.scrollToInput();
	}

	scrollToInput()
	{
		const entity: Entity = this.input.getEntity();

		if (entity.isBacklog())
		{
			const scrollContainer = entity.getListItemsNode();

			const topPosition = Dom.getRelativePosition(this.input.getInputNode(), scrollContainer).top;

			scrollContainer.scrollTo({
				top: scrollContainer.scrollTop + topPosition - 100,
				behavior: 'smooth'
			});
		}
		else
		{
			const sprintsContainer = this.planBuilder.getSprintsContainer()

			const topPosition = Dom.getRelativePosition(this.input.getInputNode(), sprintsContainer).top;

			sprintsContainer.scrollTo({
				top: sprintsContainer.scrollTop + topPosition - 100,
				behavior: 'smooth'
			});
		}
	}

	showActionPanel(entity: Entity, item: Item)
	{
		const stopSearch = () => {
			if (this.searchItems.isActive())
			{
				this.searchItems.stop();
			}
		}

		const isMultipleAction = (entity.getGroupModeItems().size > 1);

		//todo maybe will cool move list actions to item scope
		this.actionPanel = new ActionPanel({
			entity: entity,
			item: item,
			itemList: {
				task: {
					activity: true,
					disable : isMultipleAction,
					callback: () => {
						this.onShowTask(
							new BaseEvent({
								data: item
							})
						);
						this.destroyActionPanel();
						entity.deactivateGroupMode();
						sendData({
							tool: 'tasks',
							category: 'task_operations',
							event: 'task_view',
							type: 'task',
							c_section: 'scrum',
							c_element: 'context_menu',
						});
					},
				},
				attachment: {
					activity: true,
					disable : !item.isEditAllowed(),
					callback: (event) => {
						if (this.diskManager)
						{
							this.diskManager.closeAttachmentMenu();
							this.diskManager = null;

							return;
						}
						const groupItems = entity.getGroupModeItems();
						this.diskManager = new DiskManager({
							targetElement: event.currentTarget
						});
						this.diskManager.subscribe('onFinish', (baseEvent) => {
							this.attachFilesToTask(entity, groupItems, baseEvent.getData());
						});
						this.diskManager.showAttachmentMenu(event.currentTarget);
						this.actionPanel.subscribe('onDestroy', () => {
							if (this.diskManager)
							{
								this.diskManager.closeAttachmentMenu();
								this.diskManager = null;
							}
						});
					},
				},
				dod: {
					activity: true,
					disable : isMultipleAction,
					callback: () => {
						this.showDod(item);
						this.destroyActionPanel();
						entity.deactivateGroupMode();
					},
				},
				move: {
					activity: true,
					disable : (!item.isMovable()),
					callback: (event) => {
						this.moveItem(item, event.currentTarget);
						stopSearch();
					},
				},
				sprint: {
					activity: true,
					disable : false,
					multiple : (this.entityStorage.getSprintsAvailableForFilling(entity).size > 1),
					callback: (event) => {
						this.moveToSprint(entity, item, event.currentTarget);
						stopSearch();
					},
				},
				backlog: {
					activity: true,
					disable : (item.getEntityType() !== 'sprint'),
					callback: () => {
						this.moveToBacklog(entity, item);
						this.destroyActionPanel();
						stopSearch();
					},
				},
				tags: {
					activity: true,
					disable : !item.isEditAllowed(),
					callback: (event) => {
						if (this.tagSearcher.hasActionPanelDialog())
						{
							this.tagSearcher.closeActionPanelDialogs();

							return;
						}
						this.showTagSearcher(entity, item, event.currentTarget);
					},
				},
				epic: {
					activity: true,
					disable : !item.isEditAllowed(),
					callback: (event) => {
						if (this.tagSearcher.hasActionPanelDialog())
						{
							this.tagSearcher.closeActionPanelDialogs();

							return;
						}
						this.showEpicSearcher(entity, item, event.currentTarget);
					},
				},
				decomposition: {
					activity: true,
					disable : (isMultipleAction),
					callback: () => {
						if (entity.isBacklog())
						{
							this.startBacklogDecomposition(entity, item);
						}
						else
						{
							this.startSprintDecomposition(entity, item);
						}
						this.destroyActionPanel();
						entity.deactivateGroupMode();
						stopSearch();
					},
				},
				remove: {
					activity: true,
					disable : (!item.isRemoveAllowed()),
					callback: () => {
						MessageBox.confirm(
							Loc.getMessage('TASKS_SCRUM_CONFIRM_TEXT_REMOVE_TASKS'),
							(messageBox) => {
								messageBox.close();
								this.removeGroupItems(entity).then(() => {
									entity.getGroupModeItems().forEach((groupModeItem: Item) => {
										entity.removeItem(groupModeItem);
										this.deactivateGroupMode(entity, groupModeItem);
										groupModeItem.removeYourself();
									});
									this.updateEntityCounters(entity);
								});
							},
							Loc.getMessage('TASKS_SCRUM_BUTTON_TEXT_REMOVE'),
						);
						this.destroyActionPanel();
						stopSearch();
					},
				},
			}
		});

		this.actionPanel.subscribe('unSelect', () => {
			this.destroyActionPanel();
			entity.deactivateGroupMode();
		});

		this.actionPanel.show();
	}

	activateDraggable()
	{
		this.entityStorage.getAllItems().forEach((item: Item) => item.setDisableStatus(false));
	}

	deactivateDraggable()
	{
		this.entityStorage.getAllItems().forEach((item: Item) => item.setDisableStatus(true));
	}

	activateGroupMode(entity: Entity, item?: Item)
	{
		if (item)
		{
			entity.addItemToGroupMode(item);
		}

		if (entity.isGroupMode())
		{
			return;
		}

		entity.activateGroupMode();

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
	}

	deactivateGroupMode(entity: Entity, item?: Item)
	{
		if (item)
		{
			entity.removeItemFromGroupMode(item);
		}

		if (!entity.isGroupMode())
		{
			return;
		}

		const groupModeItems = entity.getGroupModeItems();
		if (groupModeItems.size === 0)
		{
			entity.deactivateGroupMode();
		}
		else
		{
			this.showActionPanel(entity, Array.from(groupModeItems.values()).pop());
		}
	}

	createItem(input: Input): Item
	{
		const epic = input.getEpic();

		const value = input.getInputNode().value.trim();

		const valueWithoutEpic =
			epic
				? value.replace(new RegExp('@' + Tool.escapeRegex(epic.name) + '$', 'g'), '')
				: value
		;

		const item = Item.buildItem({
			id: '',
			name: valueWithoutEpic,
			groupId: this.getCurrentGroupId(),
			pathToTask: this.pathToTask
		});

		item.setShortView(this.isShortView);

		return item;
	}

	sendRequestToCreateTask(entity: Entity, item: Item): Promise
	{
		const requestData = {
			'tmpId': item.getId(),
			'name': item.getName().getValue(),
			'entityId': item.getEntityId(),
			'entityType': entity.getEntityType(),
			'epicId': item.getEpic().getValue().id,
			'sort': item.getSort(),
			'storyPoints': item.getStoryPoints().getValue().getPoints(),
			'parentTaskId': item.getParentTaskId(),
			'responsible': item.getResponsible().getValue(),
			'info': item.getInfo(),
			'sortInfo': this.itemMover.calculateSort(entity.getListItemsNode())
		};
		return this.requestSender.createTask(requestData);
	}

	fillItemAfterCreation(item: Item, responseData: Object): Item
	{
		item.setId(responseData.id);
		item.setEpic(responseData.epic);
		item.setTags(responseData.tags);
		item.setResponsible(responseData.responsible);
		item.setSourceId(responseData.sourceId);
		item.setAllowedActions(responseData.allowedActions);

		item.setName(item.getName().getValue());
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

	destroyActionPanel()
	{
		if (this.actionPanel)
		{
			this.actionPanel.destroy();
		}

		this.actionPanel = null;
	}

	getActionPanel(): ?ActionPanel
	{
		return this.actionPanel;
	}

	attachFilesToTask(entity: Entity, groupItems: Map<number, Item>, attachedIds: Array<string>)
	{
		if (attachedIds.length === 0)
		{
			return;
		}

		const itemIds = [];

		groupItems.forEach((item: Item) => {
			this.pullItem.addIdToSkipUpdating(item.getId());

			itemIds.push(item.getId());
		});

		this.requestSender.attachFilesToTask({
			itemIds: itemIds,
			attachedIds: attachedIds,
		})
			.then((response) => {
				groupItems.forEach((item: Item) => {
					item.setFiles(response.data.attachedFilesCount[item.getId()]);
				});
				this.destroyActionPanel();
				entity.deactivateGroupMode();
			})
			.catch((response) => {
				this.requestSender.showErrorAlert(response);
			})
		;
	}

	showDod(item: Item)
	{
		this.sidePanel.showByExtension('Dod', {
			view: 'list',
			groupId: this.groupId,
			taskId: item.getSourceId(),
			skipNotifications: true
		});
	}

	moveItem(item: Item, bindButton: HTMLElement)
	{
		this.itemMover.moveItem(item, bindButton);
	}

	moveToSprint(entityFrom: Entity, item: Item, bindButton: HTMLElement)
	{
		this.itemMover.moveToAnotherEntity(entityFrom, item, null, bindButton);

		if (this.entityStorage.getSprintsAvailableForFilling(entityFrom).size <= 1)
		{
			this.destroyActionPanel();
		}
	}

	moveToBacklog(sprint: Sprint, item: Item)
	{
		this.itemMover.moveToAnotherEntity(sprint, item, this.entityStorage.getBacklog());
	}

	showTagSearcher(entity: Entity, item: Item, bindButton: HTMLElement)
	{
		this.tagSearcher.showTagsDialog(item, bindButton);

		this.tagSearcher.unsubscribeAll('attachTagToTask');
		this.tagSearcher.subscribe('attachTagToTask', (innerBaseEvent) => {
			const tag = innerBaseEvent.getData();

			const itemIds = [];
			entity.getGroupModeItems().forEach((groupModeItem: Item) => {
				this.pullItem.addIdToSkipUpdating(groupModeItem.getId());
				itemIds.push(groupModeItem.getId());
			});
			this.requestSender.updateTaskTags({
				itemIds: itemIds,
				tag: tag
			}).then((response) => {
				entity.getGroupModeItems().forEach((groupModeItem: Item) => {
					const currentTags = groupModeItem.getTags().getValue();
					if (!currentTags.includes(tag))
					{
						currentTags.push(tag);
					}
					groupModeItem.setTags(currentTags);
				});
			}).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});

		});

		this.tagSearcher.unsubscribeAll('deAttachTagToTask');
		this.tagSearcher.subscribe('deAttachTagToTask', (innerBaseEvent) => {
			const tag = innerBaseEvent.getData();

			const itemIds = [];
			entity.getGroupModeItems().forEach((groupModeItem: Item) => {
				this.pullItem.addIdToSkipUpdating(groupModeItem.getId());
				itemIds.push(groupModeItem.getId());
			});
			this.requestSender.removeTaskTags({
				itemIds: itemIds,
				tag: tag
			}).then((response) => {
				entity.getGroupModeItems().forEach((groupModeItem: Item) => {
					const currentTags = groupModeItem.getTags().getValue();
					currentTags.splice(currentTags.indexOf(tag), 1);
					groupModeItem.setTags(currentTags);
				});
			}).catch((response) => {
				this.requestSender.showErrorAlert(response);
			});
		});

		this.tagSearcher.unsubscribeAll('hideTagDialog');
		this.tagSearcher.subscribe('hideTagDialog', () => {
			if (!this.tagSearcher.isEpicDialogShown())
			{
				this.destroyActionPanel();
				entity.deactivateGroupMode();
			}
		});
	}

	showEpicSearcher(entity: Entity, item: Item, bindButton: HTMLElement)
	{
		this.tagSearcher.showEpicDialog(item, bindButton);

		this.tagSearcher.unsubscribeAll('updateItemEpic');
		this.tagSearcher.subscribe('updateItemEpic', (innerBaseEvent: BaseEvent) => {
			const itemIds = [];
			const epic: ?EpicType = innerBaseEvent.getData();

			entity.getGroupModeItems()
				.forEach((groupModeItem: Item) => {
					groupModeItem.setEpic(epic);
					itemIds.push(groupModeItem.getId());
					this.pullItem.addIdToSkipUpdating(groupModeItem.getId());
				})
			;

			this.requestSender.updateItemEpics({
				itemIds: itemIds,
				epicId: epic ? epic.id : 0,
			})
				.then((response) => {})
				.catch((response) => {
					this.requestSender.showErrorAlert(response);
				})
			;
		});

		this.tagSearcher.unsubscribeAll('hideEpicDialog');
		this.tagSearcher.subscribe('hideEpicDialog', () => {
			this.destroyActionPanel();
			entity.deactivateGroupMode();
		});
	}

	removeGroupItems(entity: Entity): Promise
	{
		const itemIds = [];

		entity.getGroupModeItems().forEach((groupModeItem: Item) => {

			itemIds.push(groupModeItem.getId());

			this.pullItem.addIdToSkipRemoving(groupModeItem.getId());
		});

		return this.requestSender.removeItems({
			itemIds: itemIds,
			sortInfo: this.itemMover.calculateSort(entity.getListItemsNode())
		}).catch((response) => {
			this.requestSender.showErrorAlert(response);
		});
	}

	startBacklogDecomposition(entity: Entity, parentItem: Item)
	{
		this.decomposition = new Decomposition({
			parentItem: parentItem
		});

		if (!parentItem.isLinkedTask())
		{
			this.itemDesigner.getRandomColorForItemBorder()
				.then((randomColor: string) => {
					parentItem.setBorderColor(randomColor);
				})
			;
		}

		this.showInput(entity, parentItem.getNode());
	}

	startSprintDecomposition(entity: Entity, parentItem: Item)
	{
		this.decomposition = new Decomposition({
			parentItem: parentItem
		});

		parentItem.activateDecompositionMode();

		const renderInputAfterSubTasks = (subTasks: SubTasks) => {
			if (!subTasks.isShown())
			{
				entity.appendNodeAfterItem(subTasks.render(), parentItem.getNode());
			}
			parentItem.showSubTasks();

			this.showInput(entity, subTasks.getNode());
		}

		if (parentItem.isParentTask())
		{
			const subTasks = parentItem.getSubTasks();

			if (subTasks.isEmpty())
			{
				this.requestSender.getSubTaskItems({
					entityId: entity.getId(),
					taskId: parentItem.getSourceId()
				})
					.then((response) => {
						response.data.forEach((itemParams: ItemParams) => {
							const subTaskItem = Item.buildItem(itemParams);
							entity.setItem(subTaskItem);
							subTasks.addTask(subTaskItem);
						});
						renderInputAfterSubTasks(subTasks);
					})
					.catch((response) => {
						this.requestSender.showErrorAlert(response);
					})
				;
			}
			else
			{
				renderInputAfterSubTasks(subTasks);
			}
		}
		else
		{
			this.showInput(entity, parentItem.getNode());
		}
	}
}