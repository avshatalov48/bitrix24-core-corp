import { ajax, AjaxError, AjaxResponse, Dom, Loc, Type, Uri } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { EditForm } from 'tasks.flow.edit-form';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import { FeaturePromotersRegistry } from 'ui.info-helper';
import { QueueManager } from 'pull.queuemanager';
import { TeamPopup } from 'tasks.flow.team-popup';
import { TaskQueue } from 'tasks.flow.task-queue';
import { Clue } from 'tasks.clue';
import { Manual } from 'ui.manual';

type Params = {
	gridId: number,
	currentUserId: number,
	currentUrl: string,
	isAhaShownOnMyTasksColumn: boolean,
	flowLimitCode: string,
};

type PullItem = {
	id: string,
	data: {
		id: number,
		action: string,
		actionParams: {
			TASK_ID: number,
			FLOW_ID?: number,
		},
	}
}

export class Grid
{
	#params: Params;

	#grid: BX.Main.grid;

	#clueMyTasks: Clue = null;
	#rowIdForMyTasksAhaMoment = null;

	#notificationList: Set = new Set();

	#addedFlowId: ?number = null;

	constructor(params: Params)
	{
		this.#params = params;

		this.#grid = BX.Main.gridManager.getById(this.#params.gridId).instance;

		this.instantPullHandlers = {
			comment_read_all: this.#commentReadAll,
			flow_add: this.#onFlowAdd,
			flow_update: this.#onFlowUpdate,
			flow_delete: this.#onFlowDelete,
		};

		this.delayedPullFlowHandlers = {};

		this.delayedPullTasksHandlers = {
			comment_add: this.#onFlowUpdate,
			task_add: this.#onFlowUpdate,
			task_update: this.#onFlowUpdate,
			task_view: this.#onFlowUpdate,
			task_remove: this.#onFlowUpdate,
		};

		this.#subscribeToPull();
		this.#subscribeToGridEvents();

		this.#clearAnalyticsParams();
		this.#activateHint();
		this.#showFlowCreationWizard();
	}

	activateFlow(flowId: number): void
	{
		// eslint-disable-next-line promise/catch-or-return
		ajax.runAction(
			'tasks.flow.Flow.activate',
			{
				data: {
					flowId,
				},
			},
		).then(() => {});
	}

	removeFlow(flowId: number)
	{
		const message = new MessageBox({
			message: Loc.getMessage('TASKS_FLOW_LIST_CONFIRM_REMOVE_MESSAGE'),
			buttons: MessageBoxButtons.OK_CANCEL,
			okCaption: Loc.getMessage('TASKS_FLOW_LIST_CONFIRM_REMOVE_BUTTON'),
			popupOptions: {
				id: `tasks-flow-remove-confirm-${flowId}`,
			},
			onOk: () => {
				message.close();

				this.#updateRow(flowId, 'remove');
			},
			onCancel: () => {
				message.close();
			},
		});

		message.show();
	}

	showTeam(flowId: number, bindElement?: HTMLElement): void
	{
		TeamPopup.showInstance({ flowId, bindElement });
	}

	showTaskQueue(flowId: number, type: string, bindElement?: HTMLElement): void
	{
		TaskQueue.showInstance({ flowId, type, bindElement });
	}

	showFlowLimit(): void
	{
		FeaturePromotersRegistry.getPromoter({ code: this.#params.flowLimitCode }).show();
	}

	showNotificationHint(notificationId: string, textHint: string): void
	{
		if (!this.#notificationList.has(notificationId))
		{
			BX.UI.Notification.Center.notify({
				id: notificationId,
				content: textHint,
				width: 'auto',
			});

			this.#notificationList.add(notificationId);

			EventEmitter.subscribeOnce(
				'UI.Notification.Balloon:onClose',
				(baseEvent: BaseEvent) => {
					const closingBalloon = baseEvent.getTarget();
					if (closingBalloon.getId() === notificationId)
					{
						this.#notificationList.delete(notificationId);
					}
				},
			);
		}
	}

	showGuide(demoSuffix: 'Y' | 'N'): void
	{
		Manual.show({
			manualCode: 'flows',
			urlParams: {
				utm_source: 'portal',
				utm_medium: 'referral',
			},
			analytics: {
				tool: 'tasks',
				category: 'flows',
				event: 'flow_guide_view',
				c_section: 'tasks',
				c_sub_section: 'flows_grid',
				c_element: 'guide_button',
				p1: `isDemo_${demoSuffix}`,
			},
		});
	}

	#reload(): void
	{
		this.#grid.reload(this.#params.currentUrl);
	}

	#updateRow(flowId: number, action: string): void
	{
		this.#grid.updateRow(
			flowId,
			{
				action,
				currentPage: this.#grid.getCurrentPage(),
			},
			this.#params.currentUrl,
			this.#afterRowUpdated.bind(this),
		);
	}

	#removeRow(rowId: number): void
	{
		this.#grid.removeRow(rowId);
	}

	#isRowExist(rowId: number): boolean
	{
		return this.#getRowById(rowId) !== null;
	}

	#isFirstPage(): boolean
	{
		return this.#grid.getCurrentPage() === 1;
	}

	#getRowById(rowId: number): BX.Grid.Row
	{
		return this.#grid.getRows().getById(rowId);
	}

	#getFirstRowId(): ?number
	{
		return this.#grid.getRows().getFirst().getId();
	}

	#getCell(rowId: number, columnId: string): ?HTMLElement
	{
		return this.#getRowById(rowId).getCellById(columnId);
	}

	#subscribeToPull(): void
	{
		new QueueManager({
			loadItemsDelay: 300,
			moduleId: 'tasks',
			userId: this.#params.currentUserId,
			additionalData: {},
			events: {
				onBeforePull: (event) => {
					this.#onBeforePull(event);
				},
				onPull: (event) => {
					this.#onPull(event);
				},
			},
			callbacks: {
				onBeforeQueueExecute: (items) => {
					return this.#onBeforeQueueExecute(items);
				},
				onQueueExecute: (items) => {
					return this.#onQueueExecute(items);
				},
				onReload: () => {
					this.#onReload();
				},
			},
		});
	}

	#subscribeToGridEvents(): void
	{
		EventEmitter.subscribe('Grid::updated', () => {
			this.#activateHint();
			this.#highlightAddedFlow();
		});
	}

	#onBeforePull(event): void
	{
		const { pullData: { command, params } } = event.data;

		if (this.instantPullHandlers[command])
		{
			const flowId = this.#recognizeFlowId(params);

			this.instantPullHandlers[command].apply(this, [params, flowId]);
		}
	}

	#onPull(event): void
	{
		const { pullData: { command, params }, promises } = event.data;

		if (Object.keys(this.delayedPullFlowHandlers).includes(command))
		{
			const flowId = this.#recognizeFlowId(params);

			if (flowId)
			{
				promises.push(
					Promise.resolve({
						data: {
							id: flowId,
							action: command,
							actionParams: params,
						},
					}),
				);
			}
		}

		if (Object.keys(this.delayedPullTasksHandlers).includes(command))
		{
			const taskId = this.#recognizeTaskId(params);

			if (taskId)
			{
				promises.push(
					Promise.resolve({
						data: {
							id: taskId,
							action: command,
							actionParams: params,
						},
					}),
				);
			}
		}
	}

	#onBeforeQueueExecute(items: Array<PullItem>): Promise
	{
		return Promise.resolve();
	}

	async #onQueueExecute(items: Array<PullItem>): Promise
	{
		const flowItems = this.#identifyFlowItems(items);
		const taskItems = this.#identifyTaskItems(items);

		if (taskItems.length === 0)
		{
			return this.#executeQueue(flowItems, this.delayedPullFlowHandlers);
		}

		let mapIds: {} = await this.#getMapIds(this.#getEntityIds(taskItems));
		const taskRemoveItem = this.#findTaskRemoveAction(taskItems);
		if (taskRemoveItem)
		{
			mapIds = this.#addTaskRemoveItemToMap(taskRemoveItem, mapIds);
		}

		const convertedTaskItems = this.#convertTaskItems(taskItems, mapIds);

		const taskAddItem = this.#findTaskAddAction(convertedTaskItems);
		if (taskAddItem && this.#isCurrentUserCreatorOfTheTask(taskAddItem))
		{
			const { data: { id } } = taskAddItem;

			this.#rowIdForMyTasksAhaMoment = id;
		}

		const allItems = [...flowItems, ...convertedTaskItems];

		return this.#executeQueue(
			this.#uniqueItems(allItems),
			{ ...this.delayedPullFlowHandlers, ...this.delayedPullTasksHandlers },
		);
	}

	#getMapIds(taskIds: Array): Promise
	{
		return new Promise((resolve) => {
			// eslint-disable-next-line promise/catch-or-return
			ajax.runComponentAction(
					'bitrix:tasks.flow.list',
					'getMapIds',
					{
						mode: 'class',
						data: { taskIds },
					},
				)
				.then((response: AjaxResponse) => {
					resolve(Type.isArray(response.data) ? {} : response.data);
				})
				.catch((error: AjaxError) => {
					this.#consoleError('getMapIds', error);
				})
			;
		});
	}

	#onReload(event) {}

	#executeQueue(items: Array<PullItem>, handlers: {}): Promise
	{
		return new Promise((resolve, reject) => {
			items.forEach((item: PullItem) => {
				const { data: { action, actionParams, id } } = item;

				if (handlers[action])
				{
					handlers[action].apply(
						this,
						[actionParams, id],
					);
				}
			});

			resolve();
		});
	}

	#commentReadAll(): void
	{
		this.#reload();
	}

	#onFlowAdd(data, flowId: number): void
	{
		if (this.#isRowExist(flowId))
		{
			return;
		}

		if (this.#isFirstPage())
		{
			this.#addedFlowId = flowId;

			this.#reload();
		}
	}

	#onFlowUpdate(data, flowId: number): void
	{
		if (!this.#isRowExist(flowId))
		{
			return;
		}

		this.#updateRow(flowId, 'update');
	}

	#onFlowDelete(data, flowId: number): void
	{
		if (!this.#isRowExist(flowId))
		{
			return;
		}

		this.#removeRow(flowId);
	}

	#afterRowUpdated(id, data, grid, response)
	{
		if (this.#rowIdForMyTasksAhaMoment)
		{
			if (this.#clueMyTasks && this.#clueMyTasks.isShown())
			{
				const bindElement = this.#getBindElementForAhaOnCell(
					this.#rowIdForMyTasksAhaMoment,
					'MY_TASKS',
					'.tasks-flow__list-my-tasks span',
				);

				if (bindElement)
				{
					this.#clueMyTasks.adjustPosition(bindElement);
				}
				else
				{
					this.#clueMyTasks.close();
				}
			}

			if (
				this.#params.isAhaShownOnMyTasksColumn === false
				&& this.#clueMyTasks === null
			)
			{
				this.#showAhaOnMyTasksColumn(this.#rowIdForMyTasksAhaMoment);
			}
		}
	}

	#recognizeFlowId(pullData): number
	{
		if ('FLOW_ID' in pullData)
		{
			return parseInt(pullData.FLOW_ID, 10);
		}

		return 0;
	}

	#recognizeTaskId(pullData): number
	{
		if ('TASK_ID' in pullData)
		{
			return parseInt(pullData.TASK_ID, 10);
		}

		if ('taskId' in pullData)
		{
			return parseInt(pullData.taskId, 10);
		}

		if (
			'entityXmlId' in pullData
			&& pullData.entityXmlId.indexOf('TASK_') === 0
		)
		{
			return parseInt(pullData.entityXmlId.slice(5), 10);
		}

		return 0;
	}

	#getEntityIds(pullItems: Array<PullItem>): Array
	{
		const entityIds = [];

		pullItems.forEach((item: PullItem) => {
			const { data: { id } } = item;
			entityIds.push(id);
		});

		return entityIds;
	}

	#identifyFlowItems(pullItems: Array<PullItem>): Array<PullItem>
	{
		return pullItems.filter((item: PullItem) => {
			const { data: { action } } = item;

			return Object.keys(this.delayedPullFlowHandlers).includes(action);
		});
	}

	#identifyTaskItems(pullItems: Array<PullItem>): Array<PullItem>
	{
		return pullItems.filter((item: PullItem) => {
			const { data: { action } } = item;

			return Object.keys(this.delayedPullTasksHandlers).includes(action);
		});
	}

	#convertTaskItems(pullItems: Array<PullItem>, mapIds: {}): Array<PullItem>
	{
		const tasksItems = [];

		// Replace the task id with the flow id.
		pullItems.forEach((item: PullItem) => {
			const { data: { id } } = item;
			if (id in mapIds)
			{
				// eslint-disable-next-line no-param-reassign,unicorn/consistent-destructuring
				item.data.id = mapIds[id];
				tasksItems.push(item);
			}
		});

		return tasksItems;
	}

	#uniqueItems(items: Array<PullItem>): Array<PullItem>
	{
		const uniqueItems = items.reduce((accumulator, currentItem) => {
			if (!accumulator[currentItem.data.id])
			{
				accumulator[currentItem.data.id] = currentItem;
			}

			return accumulator;
		}, {});

		return Object.values(uniqueItems);
	}

	#findTaskAddAction(pullItems: Array<PullItem>): ?PullItem
	{
		return pullItems.find((item: PullItem) => item.data.action === 'task_add');
	}

	#findTaskRemoveAction(pullItems: Array<PullItem>): ?PullItem
	{
		return pullItems.find((item: PullItem) => item.data.action === 'task_remove');
	}

	#addTaskRemoveItemToMap(pullItem: PullItem, mapIds: {}): {}
	{
		// eslint-disable-next-line no-param-reassign
		mapIds[pullItem.data.id] = pullItem.data.actionParams?.FLOW_ID;

		return mapIds;
	}

	#isCurrentUserCreatorOfTheTask(pullItem: PullItem): boolean
	{
		const createdBy = pullItem.data.actionParams?.AFTER?.CREATED_BY;

		return parseInt(createdBy, 10) === parseInt(this.#params.currentUserId, 10);
	}

	#showAhaOnMyTasksColumn(rowId: number)
	{
		const bindElement = this.#getBindElementForAhaOnCell(
			rowId,
			'MY_TASKS',
			'.tasks-flow__list-my-tasks span',
		);

		if (bindElement)
		{
			this.#clueMyTasks = new Clue({
				id: `my_tasks_${this.#params.currentUserId}`,
				autoSave: true,
			});

			this.#clueMyTasks.show(Clue.SPOT.MY_TASKS, bindElement);

			this.#params.isAhaShownOnMyTasksColumn = true;
		}
	}

	#getBindElementForAhaOnCell(rowId: number, columnId: string, selector: string): ?HTMLElement
	{
		return this.#getCell(rowId, columnId)?.querySelector(selector);
	}

	#consoleError(action: string, error: AjaxError)
	{
		// eslint-disable-next-line no-console
		console.error(`BX.Tasks.Flow.Grid: ${action} error`, error);
	}

	#clearAnalyticsParams(): void
	{
		const uri = new Uri(window.location.href);

		const section = uri.getQueryParam('ta_sec');
		if (section)
		{
			uri.removeQueryParam('ta_cat', 'ta_sec', 'ta_sub', 'ta_el', 'p1', 'p2', 'p3', 'p4', 'p5');

			window.history.replaceState(null, null, uri.toString());
		}
	}

	#activateHint(): void
	{
		BX.UI.Hint.init(this.#grid.getContainer());
	}

	#highlightAddedFlow(): void
	{
		if (this.#addedFlowId !== null && this.#isRowExist(this.#addedFlowId))
		{
			const rowNode: HTMLElement = this.#getRowById(this.#addedFlowId).getNode();

			Dom.addClass(rowNode, 'tasks-flow__list-flow-highlighted');

			this.#addedFlowId = null;
		}
	}

	#showFlowCreationWizard(): void
	{
		const uri = new Uri(window.location.href);

		const demoFlowId = uri.getQueryParam('demo_flow');
		if (demoFlowId)
		{
			uri.removeQueryParam('demo_flow');

			window.history.replaceState(null, null, uri.toString());

			EditForm.createInstance({ flowId: demoFlowId, demoFlow: 'Y' });
		}

		const createFlow = uri.getQueryParam('create_flow');
		if (createFlow)
		{
			uri.removeQueryParam('create_flow');

			window.history.replaceState(null, null, uri.toString());

			EditForm.createInstance({ guideFlow: 'Y' });
		}
	}
}
