import { ajax as Ajax, Loc, Reflection, Type } from 'main.core';
import { UI } from 'ui.notification';
import SimpleAction from './simpleaction';

const NAMESPACE = Reflection.namespace('BX.CRM.Kanban.Actions');

type Params = {
	ids: Number[];
	showNotify?: boolean;
	applyFilterAfterAction?: boolean;
}

export default class DeleteAction
{
	#grid: BX.CRM.Kanban.Grid;
	#dropZone: BX.CRM.Kanban.DropZone;
	#deletedItems: BX.CRM.Kanban.Item[] = null;
	#ids: Number[] = [];
	#showNotify: boolean;
	#applyFilterAfterAction: boolean;
	#action: Class<SimpleAction> = SimpleAction;

	constructor(grid: BX.CRM.Kanban.Grid, params: Params)
	{
		this.#grid = grid;

		if (!Type.isArrayFilled(params.ids))
		{
			throw new Error('Param ids must be filled array');
		}

		this.#ids = params.ids;
		this.#showNotify = (
			Type.isBoolean(params.showNotify)
				? params.showNotify
				: true
		);
		this.#applyFilterAfterAction = (
			Type.isBoolean(params.applyFilterAfterAction)
				? params.applyFilterAfterAction
				: false
		);
	}

	setDropZone(dropZone: BX.CRM.Kanban.DropZone): DeleteAction
	{
		this.#dropZone = dropZone;

		return this;
	}

	execute(): void
	{
		const actionParams = {
			action: 'delete',
			id: this.#ids,
		};

		(new this.#action(this.#grid, actionParams))
			.showNotify(this.#showNotify)
			.applyFilterAfterAction(this.#applyFilterAfterAction)
			.execute()
			.then(
				(response) => this.#onResolve(response),
				(response) => this.#onReject(response),
			)
			.catch(() => {
				this.#showActionError();
			})
		;
	}

	#onResolve(response: Object)
	{
		const dropZone = this.#dropZone;

		if (dropZone)
		{
			this.#prepareDropZone();
		}

		this.#prepareGrid();
		this.#unHideUndeletedItems(response);
		this.#showResult(response);
	}

	#getDeletedItems(): BX.CRM.Kanban.Item[]
	{
		if (this.#deletedItems === null)
		{
			const grid = this.#grid;
			const ids = this.#ids;

			ids.forEach((id) => {
				const item = grid.getItem(id);
				if (item)
				{
					if (this.#deletedItems === null)
					{
						this.#deletedItems = [];
					}
					this.#deletedItems.push(item);
				}
			});
		}

		return this.#deletedItems;
	}

	#prepareDropZone(): void
	{
		const dropZone = this.#dropZone;

		dropZone.empty();
		dropZone.getDropZoneArea().hide();

		dropZone.droppedItems = [];
	}

	#prepareGrid(): void
	{
		const grid = this.#grid;

		grid.dropZonesShow = false;

		grid.resetMultiSelectMode();
		grid.resetActionPanel();
		grid.resetDragMode();
	}

	#unHideUndeletedItems(data: Object): void
	{
		const deletedItems = this.#getDeletedItems();
		const { deletedIds, errors } = data;

		const undeletedItems = deletedItems.filter((item) => !deletedIds.includes(Number(item.getId())));

		if (Type.isArrayFilled(undeletedItems))
		{
			undeletedItems.forEach((item) => this.#restoreItemInColumn(item));

			errors.forEach(({ message: content, data: { id } }) => {
				UI.Notification.Center.notify({
					content,
					actions: [{
						title: Loc.getMessage('CRM_KANBAN_OPEN_ITEM'),
						events: {
							click: () => {
								BX.fireEvent(this.#grid.getItem(id).link, 'click');
							},
						},
					}],
				});
			});
		}
	}

	#showResult(data: Object): void
	{
		const deletedItems = this.#getDeletedItems();
		const { deletedIds } = data;

		const removedItems = deletedItems.filter((item) => deletedIds.includes(Number(item.getId())));
		if (!Type.isArrayFilled(removedItems))
		{
			return;
		}

		const balloonOptions = {
			content: this.#getDeleteTitle(removedItems),
		};

		const grid = this.#grid;
		if (grid.getTypeInfoParam('isRecyclebinEnabled'))
		{
			balloonOptions.actions = [{
				title: Loc.getMessage('CRM_KANBAN_DELETE_CANCEL'),
				events: {
					click: () => this.#onDeletionCancelClick(balloon, removedItems),
				},
			}];
		}

		const balloon = UI.Notification.Center.notify(balloonOptions);
	}

	#getDeleteTitle(removedItems: BX.CRM.Kanban.Item[]): string
	{
		const ids = this.#ids;

		if (ids.length === 1)
		{
			return Loc.getMessage(
				'CRM_KANBAN_DELETE_SUCCESS',
				{ '#ELEMENT_NAME#': removedItems[0].getData().name },
			);
		}

		const difference = (ids.length - removedItems.length);
		if (difference === 0)
		{
			return Loc.getMessage('CRM_KANBAN_DELETE_SUCCESS_MULTIPLE');
		}

		return Loc.getMessage(
			'CRM_KANBAN_DELETE_SUCCESS_MULTIPLE_WITH_ERRORS',
			{
				'#COUNT#': difference,
			},
		);
	}

	#onDeletionCancelClick(balloon, removedItems: BX.CRM.Kanban.Item[]): void
	{
		balloon.close();

		const grid = this.#grid;
		const entityIds = this.#ids;
		const { entityTypeInt: entityTypeId } = grid.getData();

		Ajax.runComponentAction(
			'bitrix:crm.kanban',
			'restore',
			{
				mode: 'ajax',
				data: {
					entityIds,
					entityTypeId,
				},
			},
		).then(
			({ data }) => {
				if (!Type.isPlainObject(data))
				{
					return;
				}

				const ids = Object.values(data).filter((id) => Type.isNumber(id));

				if (Type.isArrayFilled(ids))
				{
					this
						.#grid
						.loadNew(ids, false, true, true, true)
						.then(
							(response) => {
								const autoHideDelay = 6000;
								UI.Notification.Center.notify({
									content: Loc.getMessage('CRM_KANBAN_DELETE_RESTORE_SUCCESS'),
									autoHideDelay,
								});
							},
							() => {
								this.#showActionError();
							},
						)
						.catch(() => {
							this.#showActionError();
						});
				}
			},
			(response) => this.#onReject(response),
		).catch(() => {
			this.#showActionError();
		});
	}

	#showActionError(): void
	{
		UI.Notification.Center.notify({
			content: Loc.getMessage('CRM_KANBAN_ACTION_ERROR'),
		});
	}

	#restoreItemInColumn(item: BX.CRM.Kanban.Item): void
	{
		const lastPosition = item.getLastPosition();

		if (!lastPosition.columnId)
		{
			return;
		}

		const data = item.getData();

		data.columnId = lastPosition.columnId;
		data.targetId = lastPosition.targetId;

		const grid = this.#grid;
		const price = parseFloat(data.price);
		grid.getColumn(item.columnId).incPrice(price);

		grid.updateItem(item.getId(), data);
		grid.unhideItem(item);
	}

	#onReject(response: Object): void
	{
		const { message: content } = response.errors[0];

		UI.Notification.Center.notify({
			content,
		});
	}
}

NAMESPACE.DeleteAction = DeleteAction;