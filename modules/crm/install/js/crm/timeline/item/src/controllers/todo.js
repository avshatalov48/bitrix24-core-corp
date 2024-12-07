import { FileUploaderPopup } from 'crm.activity.file-uploader-popup';
import { ajax as Ajax, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Dialog } from 'ui.entity-selector';
import { UI } from 'ui.notification';
import { SidePanel } from 'ui.sidepanel';
import ConfigurableItem from '../configurable-item';
import type { ActionParams } from './base';
import { Base } from './base';

export class ToDo extends Base
{
	#responsibleUserSelectorDialog: ?Dialog = null;

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData} = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'ColorSelector:Change' && actionData)
		{
			this.#runUpdateColorAction(item, actionData);
		}

		if (action === 'EditableDescription:StartEdit')
		{
			item.highlightContentBlockById('description', true);
		}

		if (action === 'EditableDescription:FinishEdit')
		{
			item.highlightContentBlockById('description', false);
		}

		if (action === 'Activity:ToDo:AddFile' && actionData)
		{
			this.#showFileUploaderPopup(item, actionData);
		}

		if (action === 'Activity:ToDo:ChangeResponsible' && actionData)
		{
			this.#showResponsibleUserSelector(item, actionData);
		}

		if (action === 'Activity:ToDo:Repeat' && actionData)
		{
			this.#emitRepeatTodo(item, actionData);
		}

		if (action === 'Activity:ToDo:Update' && actionData)
		{
			this.#emitUpdateTodo(item, actionData);
		}

		if (action === 'Activity:ToDo:ShowCalendar' && actionData)
		{
			this.#showCalendar(item, actionData);
		}

		if (action === 'Activity:ToDo:Client:Click' && actionData)
		{
			this.#openClient(actionData.entityId, actionData.entityTypeId);
		}

		if (action === 'Activity:ToDo:User:Click' && actionData)
		{
			this.#openUser(actionData.userId);
		}
	}

	#showFileUploaderPopup(item, actionData): void
	{
		const isValidParams = Type.isNumber(actionData.entityId)
			&& Type.isNumber(actionData.entityTypeId)
			&& Type.isNumber(actionData.ownerId)
			&& Type.isNumber(actionData.ownerTypeId);

		if (!isValidParams)
		{
			return;
		}

		actionData.files = actionData.files.split(',').filter(id => Type.isNumber(id));

		const fileList = item.getLayoutContentBlockById('fileList');
		if (fileList)
		{
			fileList.showFileUploaderPopup(actionData);
		}
		else
		{
			const popup = new FileUploaderPopup(actionData);
			popup.show();
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (item.getType() === 'Activity:ToDo');
	}

	#showResponsibleUserSelector(item, actionData): void
	{
		const isValidParams = Type.isNumber(actionData.id)
			&& Type.isNumber(actionData.ownerId)
			&& Type.isNumber(actionData.ownerTypeId)
			&& Type.isNumber(actionData.responsibleId)
		;

		if (!isValidParams)
		{
			return;
		}

		this.#responsibleUserSelectorDialog = new Dialog({
			id: 'responsible-user-selector-dialog-' + actionData.id,
			targetNode: item.getLayoutFooterMenu().$el,
			context: 'CRM_ACTIVITY_TODO_RESPONSIBLE_USER',
			multiple: false,
			dropdownMode: true,
			showAvatars: true,
			enableSearch: true,
			width: 450,
			entities: [{
				id: 'user',
			}],
			preselectedItems: [
				['user', actionData.responsibleId],
			],
			undeselectedItems: [
				['user', actionData.responsibleId],
			],
			events: {
				'Item:onSelect': (event: BaseEvent): void => {
					const selectedItem = event.getData().item.getDialog().getSelectedItems()[0];
					if (selectedItem)
					{
						this.#runResponsibleUserAction(actionData.id, actionData.ownerId, actionData.ownerTypeId, selectedItem.getId());
					}
				},
				'Item:onDeselect': (event: BaseEvent): void => {
					setTimeout(() => {
						const selectedItems = this.#responsibleUserSelectorDialog.getSelectedItems();
						if (selectedItems.length === 0)
						{
							this.#responsibleUserSelectorDialog.hide();
							this.#runResponsibleUserAction(actionData.id, actionData.ownerId, actionData.ownerTypeId, actionData.responsibleId);
						}
					}, 100);
				},
			},
		});

		this.#responsibleUserSelectorDialog.show();
	}

	#emitRepeatTodo(item: Object, actionData: Object): void
	{
		EventEmitter.emit('crm:timeline:todo:repeat', actionData);
	}

	#emitUpdateTodo(item: Object, actionData: Object): void
	{
		EventEmitter.emit('crm:timeline:todo:update', actionData);
	}

	#runUpdateColorAction(item, actionData): void
	{
		const { id, ownerTypeId, ownerId } = item.getDataPayload();
		const { colorId } = actionData;

		const isValidParams = (
			Type.isNumber(id)
			&& Type.isNumber(ownerId)
			&& Type.isNumber(ownerTypeId)
			&& Type.isStringFilled(colorId)
		);

		if (!isValidParams)
		{
			return;
		}

		const data = {
			ownerTypeId,
			ownerId,
			id,
			colorId,
		};

		Ajax
			.runAction('crm.activity.todo.updateColor', { data })
			.catch((response) => {
				UI.Notification.Center.notify({
					content: response.errors[0].message,
					autoHideDelay: 5000,
				});

				throw response;
			});
	}

	#showCalendar(item: Object, actionData: Object): void
	{
		const { calendarEventId, entryDateFrom, timezoneOffset } = actionData;

		if (!window.top.BX.Calendar)
		{
			// eslint-disable-next-line no-console
			console.warn('BX.Calendar not found');

			return;
		}

		new window.top.BX.Calendar.SliderLoader(
			calendarEventId,
			{
				entryDateFrom,
				timezoneOffset,
				calendarContext: null,
			},
		).show();
	}

	#runResponsibleUserAction(id: Number, ownerId: Number, ownerTypeId: Number, responsibleId: Number): void
	{
		const data = {
			ownerTypeId,
			ownerId,
			id,
			responsibleId
		};

		Ajax
			.runAction('crm.activity.todo.updateResponsibleUser', { data })
			.catch(response => {
				UI.Notification.Center.notify({
					content: response.errors[0].message,
					autoHideDelay: 5000,
				});

				throw response;
			});
	}

	#openClient(entityId: Number, entityTypeId: Number): void
	{
		if (SidePanel.Instance)
		{
			const entityTypeName = BX.CrmEntityType.resolveName(entityTypeId).toLowerCase();
			const path = `/crm/${entityTypeName}/details/${entityId}/`;

			SidePanel.Instance.open(path);
		}
	}

	#openUser(userId: Number): void
	{
		if (SidePanel.Instance)
		{
			const path = `/company/personal/user/${userId}/`;

			SidePanel.Instance.open(path);
		}
	}
}
