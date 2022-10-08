import {Kanban} from 'main.kanban';
import {Type, ajax as Ajax, Loc, Text, Event} from 'main.core';
import 'ui.buttons';
import 'ui.notification';
import 'ui.fonts.opensans';
import {PullManager} from './pullmanager';
import Item from './item';
import Column from './column';
import {PopupWindowManager, PopupMenuWindow} from 'main.popup';
import {FieldsPopup} from 'rpa.fieldspopup';

export default class Grid extends Kanban.Grid
{
	getTypeId(): number
	{
		return Text.toInteger(this.getData().typeId);
	}

	getUserId(): number
	{
		return Text.toInteger(this.getData().userId);
	}

	isCreateItemRestricted(): boolean
	{
		return (this.getData().isCreateItemRestricted === true);
	}

	bindEvents()
	{
		BX.addCustomEvent(this, "Kanban.DropZone:onBeforeItemCaptured", this.onBeforeItemCaptured.bind(this));
		BX.addCustomEvent(this, "Kanban.DropZone:onBeforeItemRestored", this.onBeforeItemRestored.bind(this));

		BX.addCustomEvent("Kanban.Column:render", (column) =>
		{
			if(column.getGrid() === this && column instanceof Column)
			{
				column.onAfterRender.apply(column);
			}
		});

		BX.addCustomEvent("BX.Main.Filter:apply", this.onApplyFilter.bind(this));

		BX.addCustomEvent(this, "Kanban.Grid:onColumnLoadAsync", (promises) =>
		{
			promises.push((column) =>
			{
				return this.getColumnItems(column);
			});
		});
		BX.addCustomEvent(this, "Kanban.Grid:onBeforeItemMoved", this.saveItemState);
		BX.addCustomEvent(this, "Kanban.Grid:onItemMoved", this.onItemMoved);
		BX.addCustomEvent(this, "Kanban.Grid:onColumnUpdated", this.onColumnUpdated);
		BX.addCustomEvent(this, "Kanban.Grid:onColumnMoved", this.onColumnMoved);
		BX.addCustomEvent(this, "Kanban.Grid:onColumnAddedAsync", (promises) =>
		{
			promises.push((column) =>
			{
				return this.addStage(column);
			});
		});
		BX.addCustomEvent(this, "Kanban.Grid:onColumnRemovedAsync", (promises) =>
		{
			promises.push((column) =>
			{
				return this.removeStage(column);
			});
		});

		BX.addCustomEvent(window, 'BX.UI.EntityEditorSection:onOpenChildMenu', this.onOpenSelectFieldMenu.bind(this));

		BX.addCustomEvent('SidePanel.Slider:onMessage', (message) => {
			if(message.getEventId() === 'userfield-list-update')
			{
				this.onApplyFilter();
			}
		});

		this.pullManager = new PullManager(this);
	}

	onBeforeItemCaptured(dropZoneEvent: BX.Kanban.DropZoneEvent)
	{
		Event.EventEmitter.emit('BX.Rpa.Kanban.Grid:onBeforeItemCapturedStart', [this, dropZoneEvent]);
		const item = dropZoneEvent.getItem();
		if(!(item instanceof Item))
		{
			return;
		}
		if(!dropZoneEvent.isActionAllowed())
		{
			return;
		}
		const dropZone = dropZoneEvent.getDropZone();
		if(dropZone.getId() === 'delete')
		{
			if(!item.isDeletable())
			{
				dropZoneEvent.denyAction();
				return;
			}
			if(this.deleteCommand && !this.deleteCommand.isCompleted())
			{
				this.deleteCommand.run();
			}
			this.deleteCommand = new Command(item, (commandItem) =>
			{
				this.deleteItem(commandItem);
			}, (commandItem) =>
			{
				this.unhideItem(commandItem);
			});
			this.deleteCommand.start(dropZone.getDropZoneArea().getDropZoneTimeout());
		}
		else if(dropZone.getData().isColumn === true)
		{
			dropZoneEvent.denyAction();
			const targetColumn = this.getColumn(dropZone.getId());
			if(!targetColumn)
			{
				item.saveCurrentState();
				this.hideItem(item);
			}
			else
			{
				this.moveItem(item, targetColumn);
			}
			this.moveItemToStage(item, dropZone.getId(), item.getColumn());
		}
	}

	onBeforeItemRestored(dropZoneEvent: BX.Kanban.DropZoneEvent)
	{
		const item = dropZoneEvent.getItem();
		if(!(item instanceof Item))
		{
			return;
		}
		const dropZone = dropZoneEvent.getDropZone();
		if(dropZone.getId() === 'delete')
		{
			if(this.deleteCommand)
			{
				this.deleteCommand.cancel();
				this.deleteCommand = null;
			}
		}
	}

	saveItemState(dropEvent)
	{
		dropEvent.getItem().saveCurrentState();
	}

	getFirstColumn(): ?Column
	{
		const columns = this.getColumns();
		if(columns.length > 0)
		{
			return columns[0];
		}

		return null;
	}

	onItemMoved(item: Item, targetColumn: Column, beforeItem: Item, skipHandler)
	{
		const itemPreviousState = item.getCurrentState();
		// moving in the same column
		if(parseInt(item.getStageId()) === parseInt(targetColumn.getId()))
		{
			if(
				(!beforeItem && item.getCurrentState().nextItemId === 0) ||
				(beforeItem && parseInt(beforeItem.getId()) === parseInt(item.getCurrentState().nextItemId))
			)
			{
				// skip moving on the same place
				this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
				return;
			}
			// save sorting
			item.saveCurrentState().saveSort().catch((response) =>
			{
				this.onItemMoveError(item, response, itemPreviousState);
			});
			return;
		}
		// check permissions and next stage
		const previousColumn = this.getColumn(item.getStageId());
		//const isPossibleNextStagesIncludesTargetColumn = previousColumn.getPossibleNextStages().includes(targetColumn.getId());
		//sorry but for now we do not check possible next stages
		const isPossibleNextStagesIncludesTargetColumn = true;
		/*if(!isPossibleNextStagesIncludesTargetColumn && previousColumn.canMoveTo() && item.getMovedBy() === this.getUserId() && targetColumn.getPossibleNextStages().includes(previousColumn.getId()))
		{
			// item is moving back - no editor just moving
			item.saveCurrentState().savePosition().catch((response) =>
			{
				this.onItemMoveError(item, response, itemPreviousState);
			});
		}
		else */if(previousColumn.isCanMoveFrom() && isPossibleNextStagesIncludesTargetColumn)
		{
			this.moveItemToStage(item, targetColumn.getId(), previousColumn);
		}
		else if(!previousColumn.isCanMoveFrom() && isPossibleNextStagesIncludesTargetColumn)
		{
			BX.UI.Notification.Center.notify({
				content: Loc.getMessage('RPA_KANBAN_MOVE_PERMISSION_NOTIFY').replace('#STAGE#', Text.encode(previousColumn.getName()))
			});
			this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
		}
		else if(previousColumn.isCanMoveFrom() && !isPossibleNextStagesIncludesTargetColumn)
		{
			BX.UI.Notification.Center.notify({
				content: Loc.getMessage('RPA_KANBAN_MOVE_WRONG_STAGE_NOTIFY').replace('#STAGE_FROM#', Text.encode(previousColumn.getName())).replace('#STAGE_TO#', Text.encode(targetColumn.getName()))
			});
			this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
		}
		else
		{
			BX.UI.Notification.Center.notify({
				content: Loc.getMessage('RPA_KANBAN_MOVE_ITEM_PERMISSION_NOTIFY').replace('#ITEM#', Text.encode(item.getName())).replace('#STAGE#', Text.encode(previousColumn.getName()))
			});
			this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
		}
	}

	moveItemToStage(item: Item, targetColumnId: number, previousColumn: Column)
	{
		const itemPreviousState = item.getCurrentState();
		if(item.hasEmptyMandatoryFields(previousColumn))
		{
			if(!previousColumn.canAddItems())
			{
				this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
				Kanban.Utils.showErrorDialog(Loc.getMessage('RPA_KANBAN_MOVE_EMPTY_MANDATORY_FIELDS_ERROR'), false);
				return;
			}
			item.showEditor(targetColumnId).then((response) =>
			{
				this.onEditorSave(item, response);
			}).catch((response) =>
			{
				this.onItemMoveError(item, response, itemPreviousState);
			});
		}
		else
		{
			const targetColumn = this.getColumn(targetColumnId);
			if(targetColumn)
			{
				item.saveCurrentState();
			}
			else
			{
				item.setStageId(targetColumnId);
			}
			item.savePosition().catch((response) =>
			{
				let isShowEditor = false;
				let isShowTasks = false;
				let isTasksError = false;
				response.errors.forEach((error) =>
				{
					if(error.code && error.code === 'RPA_MANDATORY_FIELD_EMPTY')
					{
						// show editor in case we missed some empty mandatory field
						isShowEditor = true;
					}
					else if(error.code && error.code === 'RPA_ITEM_USER_HAS_TASKS')
					{
						isShowTasks = true;
					}
					else if(error.code && error.code === 'RPA_ITEM_TASKS_NOT_COMPLETED')
					{
						isTasksError = true;
					}
				});
				if(isShowEditor)
				{
					if(!previousColumn.canAddItems())
					{
						BX.UI.Notification.Center.notify({
							content: Loc.getMessage('RPA_KANBAN_MOVE_ITEM_PERMISSION_NOTIFY').replace('#ITEM#', Text.encode(item.getName())).replace('#STAGE#', Text.encode(previousColumn.getName()))
						});
						this.onItemMoveError(item, null, itemPreviousState);
						return;
					}
					item.showEditor(targetColumnId).then((response) =>
					{
						if(response.cancel === true)
						{
							this.onItemMoveError(item, null, itemPreviousState);
						}
					}).catch((response) =>
					{
						this.onItemMoveError(item, response, itemPreviousState);
					});
				}
				else if(isShowTasks)
				{
					item.showTasks().then((response) =>
					{
						// move back
						if(response.isCompleted !== true)
						{
							this.onItemMoveError(item, null, itemPreviousState);
						}
						else
						{
							item.update(response);
							if(!item.moveToActualColumn())
							{
								item.render();
							}
						}
					}).catch((response) =>
					{
						this.onItemMoveError(item, response, itemPreviousState);
					})
				}
				else if(isTasksError)
				{
					BX.UI.Notification.Center.notify({
						content: Loc.getMessage('RPA_KANBAN_MOVE_ITEM_HAS_TASKS_ERROR')
					});
					this.onItemMoveError(item, null, itemPreviousState);
				}
				else
				{
					this.onItemMoveError(item, response, itemPreviousState);
				}
			});
		}
	}

	onItemMoveError(item: Item, response: Object = null, previousState: Object = null)
	{
		if(previousState)
		{
			item.restoreState(previousState);
			if(!item.isVisible())
			{
				this.unhideItem(item);
			}
			this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
		}
		if(response)
		{
			this.showErrorFromResponse(response);
		}
	}

	onEditorSave(item: Item, response: Object)
	{
		if(response.cancel === true)
		{
			this.moveItem(item, item.getStageId(), item.getCurrentState().nextItemId);
		}
		else
		{

		}
	}

	showErrorFromResponse(response: Object, fatal = false)
	{
		let errors = null;
		if(Type.isPlainObject(response) && response.errors && Type.isArray(response.errors))
		{
			errors = response.errors;
		}
		else if(Type.isArray(response))
		{
			errors = response;
		}
		let message = '';
		if(Type.isArray(errors))
		{
			errors.forEach((error) =>
			{
				message += Text.encode(error.message) + "\n";
			});
		}
		else
		{
			message = 'Unknown error';
		}
		Kanban.Utils.showErrorDialog(message, fatal);
	}

	onColumnUpdated(column: Column)
	{
		this.startProgress();
		Ajax.runAction('rpa.stage.update', {
			analyticsLabel: 'rpaKanbanStageUpdate',
			data: {
				id: column.getId(),
				fields: {
					name: column.getName(),
					color: column.getColor(),
				},
				eventId: this.pullManager.registerRandomEventId(),
			},
			getParameters: {
				context: 'kanban',
			}
		}).then((response) =>
		{
			this.stopProgress();
			column.update(response.data);
		}).catch((response) =>
		{
			this.stopProgress();
			this.showErrorFromResponse(response, true);
		});
	}

	addStage(column: Column): BX.Promise
	{
		this.startProgress();
		const previousColumn = this.getPreviousColumnSibling(column);
		const previousColumnId = previousColumn ? previousColumn.getId() : 0;

		const promise = new BX.Promise();
		Ajax.runAction('rpa.stage.add', {
			analyticsLabel: 'rpaKanbanStageAdd',
			data: {
				fields: {
					name: column.getName(),
					color: column.getColor(),
					previousStageId: previousColumnId,
					typeId: this.getTypeId(),
				},
				eventId: this.pullManager.registerRandomEventId(),
			},
			getParameters: {
				context: 'kanban',
			}
		}).then((response) =>
		{
			promise.fulfill(this.transformColumnActionResponseToColumnOptions(response));
			this.stopProgress();
		}).catch((response) =>
		{
			const error = response.errors.pop().message;
			promise.reject(error);
			this.stopProgress();
		});

		return promise;
	}

	removeStage(column: Column): BX.promise
	{
		const promise = new BX.Promise();
		this.startProgress();
		Ajax.runAction('rpa.stage.delete', {
			analyticsLabel: 'rpaKanbanStageDelete',
			data: {
				id: column.getId(),
			},
			getParameters: {
				context: 'kanban',
			}
		}).then(() =>
		{
			this.stopProgress();
			promise.fulfill();
		}).catch((response) =>
		{
			this.stopProgress();
			const error = response.errors.pop().message;
			column.enableDragging();
			column.getContainer().classList.remove("main-kanban-column-edit-mode");
			promise.reject(error);
		});

		return promise;
	}

	onColumnMoved(column: Column)
	{
		const previousColumn = this.getPreviousColumnSibling(column);
		const previousColumnId = previousColumn ? previousColumn.getId() : 0;

		Ajax.runAction('rpa.stage.update', {
			analyticsLabel: 'rpaKanbanStageMove',
			data: {
				id: column.getId(),
				fields: {
					previousStageId: previousColumnId,
				},
				eventId: this.pullManager.registerRandomEventId(),
			},
			getParameters: {
				context: 'kanban',
			}
		}).then((response) =>
		{
			const wasFirst = column.isFirstColumn();
			let isFirst = true;
			column.update(response.data);
			if(column.isFirstColumn() && wasFirst)
			{
				return;
			}
			if(column.isFirstColumn() || wasFirst)
			{
				this.getColumns().forEach((renderedColumn) =>
				{
					if(renderedColumn !== column)
					{
						renderedColumn.setIsFirstColumn((wasFirst && isFirst));
						isFirst = false;
					}
					renderedColumn.rerenderSubtitle();
				});
			}
			this.getFirstColumn().rerenderSubtitle();
		}).catch((response) =>
		{
			this.showErrorFromResponse(response, true);
		});
	}

	transformColumnActionResponseToColumnOptions(response: Object): Object
	{
		return {
			id: response.data.stage.id,
			name: response.data.stage.name,
			color: response.data.stage.color,
			total: response.data.stage.total,
			data: response.data.stage,
		};
	}

	getColumnItems(column: Column): BX.Promise
	{
		const page = column.getPagination().page + 1;
		const size = this.getData().pageSize;
		const promise = new BX.Promise();
		Ajax.runComponentAction('bitrix:rpa.kanban', 'getColumn', {
			mode: 'class',
			analyticsLabel: 'rpaKanbanPagination',
			signedParameters: this.getData().signedParameters,
			data: {
				stageId: column.getId(),
			},
			navigation: {page, size},
		}).then((response) =>
		{
			let items = [];
			response.data.items.forEach((itemData) =>
			{
				items.push({
					id: itemData.id,
					columnId: itemData.stageId,
					name: itemData.name,
					data: itemData,
				});
			});

			promise.fulfill(items);
		}).catch((response) =>
		{
			const error = response.errors.pop().message;
			promise.reject(error);
		});

		return promise;
	}

	insertItem(item: Item)
	{
		if(!(item instanceof Item))
		{
			return;
		}
		let beforeItem = null;
		const newColumn = this.getColumn(item.getStageId());
		if(newColumn)
		{
			beforeItem = newColumn.getFirstItem();
			this.moveItem(item, item.getStageId(), beforeItem);
			item.processPermissions();
		}
		else
		{
			this.removeItem(item);
		}
	}

	onApplyFilter(filterId, values, filterInstance, promise, params)
	{
		if(Type.isPlainObject(params))
		{
			params.autoResolve = false;
		}

		this.startProgress();
		Ajax.runComponentAction('bitrix:rpa.kanban', 'get', {
			analyticsLabel: 'rpaKanbanApplyFilter',
			signedParameters: this.getData().signedParameters,
			mode: 'class',
		}).then((response) =>
		{
			this.stopProgress();
			this.getColumns().forEach((column) =>
			{
				const pagination = column.getPagination();
				if(pagination)
				{
					pagination.page = 1;
				}
			});
			this.getColumns().forEach((column) =>
			{
				this.removeColumn(column);
			});
			this.removeItems();
			this.loadData(response.data.kanban);
			if(!Type.isNil(promise))
			{
				promise.fulfill();
			}
		}).catch((response) =>
		{
			this.stopProgress();
			this.showErrorFromResponse(response);
			if(!Type.isNil(promise))
			{
				promise.reject();
			}
		});
	}

	loadData(json: {
		columns: Array<Column>;
		items: Array<Item>;
		dropZones: Array<Kanban.DropZone>;
		data: {
			users: ?Object
		}
	})
	{
		if(Type.isPlainObject(json.data))
		{
			this.addUsers(json.data.users);
			this.data.fields = json.data.fields;
		}

		super.loadData(json);
	}

	addUsers(users: Object)
	{
		if(Type.isPlainObject(users))
		{
			if(!this.users)
			{
				this.users = new Map();
			}
			Object.keys(users).forEach((userId) =>
			{
				userId = Text.toInteger(userId);
				if(userId > 0)
				{
					this.users.set(userId, users[userId]);
				}
			});
		}
	}

	getUser(userId: number): ?{
		id: number,
		fullName: ?string,
		link: ?string,
		photo: ?string
	}
	{
		if(!this.users)
		{
			this.users = new Map();
		}

		return this.users.get(userId);
	}

	getFields(): Object
	{
		let fields = this.getData().fields;
		if(!fields || !Type.isPlainObject(fields))
		{
			fields = {};
		}

		return fields;
	}

	onOpenSelectFieldMenu(editor, params: Object)
	{
		params.cancel = true;

		const popupId = 'rpa-kanban-column-select-fields-menu-' + this.getTypeId();
		let popup = PopupWindowManager.getPopupById(popupId);
		if(!popup)
		{
			popup = new PopupMenuWindow({
				id: 'rpa-kanban-column-select-fields-menu-' + this.getTypeId(),
				bindElement: params.button,
				items: [
					{
						text: Loc.getMessage('RPA_KANBAN_FIELDS_VIEW_SETTINGS'),
						onclick: this.onSelectFieldsViewSettingsClick.bind(this),
					},
					{
						text: Loc.getMessage('RPA_KANBAN_FIELDS_MODIFY_SETTINGS'),
						onclick: this.onSelectFieldsModifySettingsClick.bind(this),
					}
				],
				autoHide: true,
				closeByEsc: true,
				cacheable: false,
			});
		}
		else
		{
			popup.setBindElement(params.button);
		}

		popup.show();
	}

	onSelectFieldsViewSettingsClick()
	{
		if(!this.canAddColumns())
		{
			return;
		}
		const fields = this.getFields();
		const data = [];
		Object.keys(fields).forEach((fieldName) =>
		{
			data.push({
				title: fields[fieldName].title,
				name: fieldName,
				checked: fields[fieldName].isVisibleOnKanban,
			});
		});
		const fieldsPopup = new FieldsPopup('rpa-kanban-view-' + this.getTypeId(), data, Loc.getMessage('RPA_KANBAN_FIELDS_VIEW_SETTINGS'));
		fieldsPopup.show().then((result: Set|false) =>
		{
			if(result !== false)
			{
				if(this.isProgress())
				{
					return;
				}
				this.startProgress();
				Ajax.runAction('rpa.fields.setVisibilitySettings', {
					analyticsLabel: 'rpaKanbanSaveVisibleFields',
					data: {
						typeId: this.getTypeId(),
						fields: Array.from(result),
						visibility: 'kanban',
					}
				}).then((response) =>
				{
					this.stopProgress();
					Object.keys(this.getFields()).forEach((fieldName) =>
					{
						this.data.fields[fieldName]['isVisibleOnKanban'] = result.has(fieldName);
					});
					this.getColumns().forEach((column) =>
					{
						column.getItems().forEach((item) =>
						{
							item.render();
						});
					});
				}).catch((response) =>
				{
					this.stopProgress();
					this.showErrorFromResponse(response);
				});
			}
		});
	}

	onSelectFieldsModifySettingsClick()
	{
		if(!this.canAddColumns())
		{
			return;
		}
		const firstColumn = this.getFirstColumn();
		if(!firstColumn)
		{
			return;
		}
		const editor = firstColumn.getEditor();
		if(!editor)
		{
			return;
		}
		const fields = this.getFields();
		const data = [];
		Object.keys(fields).forEach((fieldName) =>
		{
			if(fields[fieldName].canBeEdited)
			{
				data.push({
					title: fields[fieldName].title,
					name: fieldName,
					checked: !!editor.getControlById(fieldName),
				});
			}
		});
		const fieldsPopup = new FieldsPopup('rpa-kanban-edit-' + this.getTypeId(), data, Loc.getMessage('RPA_KANBAN_FIELDS_MODIFY_SETTINGS'));
		fieldsPopup.show().then((result) =>
		{
			if(!result)
			{
				return;
			}
			if(this.isProgress())
			{
				return;
			}
			this.startProgress();
			Ajax.runAction('rpa.fields.setVisibilitySettings', {
				data: {
					typeId: this.getTypeId(),
					fields: Array.from(result),
					visibility: 'create',
				},
				analyticsLabel: 'rpaKanbanSaveCreateFields',
			}).then((response) =>
			{
				this.stopProgress();
				this.syncEditorFields(editor, result);

			}).catch((response) =>
			{
				this.stopProgress();
				this.showErrorFromResponse(response);
			});
		});
	}

	syncEditorFields(editor: BX.UI.EntityEditor, availableFields: Set)
	{
		const fields = this.getFields();
		const editorMainSection = this.getEditorMainSection(editor);
		if(!editorMainSection)
		{
			return;
		}
		Object.keys(fields).forEach((fieldName) =>
		{
			let control = editor.getControlById(fieldName);
			if(control && !availableFields.has(fieldName))
			{
				editorMainSection.removeChild(control, { enableSaving: false });
			}
			else if(!control && availableFields.has(fieldName))
			{
				let element = editor.getAvailableSchemeElementByName(fieldName);
				if(element)
				{
					let field = editor.createControl(element.getType(), element.getName(), {
						schemeElement: element,
						model: editor._model,
						mode: editor._mode
					});
					if(field)
					{
						editorMainSection.addChild(
							field,
							{
								layout: { forceDisplay: true },
								enableSaving: false
							}
						);
					}
				}
			}
		});
	}

	getEditorMainSection(editor)
	{
		let editorMainSection = editor.getControlById('main');
		if(editorMainSection instanceof BX.UI.EntityEditorColumn)
		{
			editorMainSection = editorMainSection.getChildById('main');
		}

		return editorMainSection;
	}

	isProgress(): boolean
	{
		return (this.progress === true);
	}

	startProgress(): Grid
	{
		this.progress = true;
		this.showLoader().fadeOut();

		return this;
	}

	stopProgress(): Grid
	{
		this.progress = false;
		this.hideLoader().fadeIn();

		return this;
	}

	showLoader(): Grid
	{
		this.getLoader().style.display = 'block';

		return this;
	}

	hideLoader(): Grid
	{
		this.getLoader().style.display = 'none';

		return this;
	}

	deleteItem(item: Item)
	{
		if(this.isProgress())
		{
			return;
		}
		this.startProgress();
		Ajax.runAction('rpa.item.delete', {
			analyticsLabel: 'rpaKanbanItemDelete',
			data: {
				typeId: this.getTypeId(),
				id: item.getId(),
			}
		}).then(() =>
		{
			if(this.getItem(item))
			{
				item.getColumn().removeItem(item);
			}
			this.stopProgress();
		}).catch((response) =>
		{
			this.stopProgress();
			this.showErrorFromResponse(response);
		});
	}
}

class Command
{
	constructor(item: Item, action: Function, restore: ?Function)
	{
		this.item = item;
		this.action = action;
		this.restore = restore;
		this.timeoutId = null;
	}

	start(timeout: number)
	{
		this.timeoutId = setTimeout(this.run.bind(this), timeout);
	}

	run()
	{
		clearTimeout(this.timeoutId);
		this.timeoutId = null;
		if(Type.isFunction(this.action))
		{
			this.action(this.item);
		}
	}

	cancel()
	{
		clearTimeout(this.timeoutId);
		this.timeoutId = null;
		if(Type.isFunction(this.restore))
		{
			this.restore(this.item);
		}
	}

	isCompleted(): boolean
	{
		return (!(this.timeoutId > 0));
	}
}