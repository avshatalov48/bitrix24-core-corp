import { ajax as Ajax, Type } from 'main.core';
import { BaseEvent } from "main.core.events";
import { Dialog } from 'ui.entity-selector';
import { UI } from 'ui.notification';
import { FileUploaderPopup } from 'crm.activity.file-uploader-popup';
import { SettingsPopup as TodoEditorSettingsPopup } from 'crm.activity.settings-popup';
import { Calendar as SettingsPopupCalendar } from 'crm.activity.settings-popup';
import { Ping as SettingsPopupPing } from 'crm.activity.settings-popup';
import { SectionSettings } from 'crm.activity.todo-editor';

import { Base } from './base';
import ConfigurableItem from '../configurable-item';

export class ToDo extends Base
{
	#settingsPopup: ?Popup = null;
	#responsibleUserSelectorDialog: ?Dialog = null;

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData} = actionParams;

		if (actionType !== 'jsEvent')
		{
			return;
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

		if (action === 'Activity:ToDo:ShowSettings' && actionData)
		{
			this.#showSettingsPopup(item, actionData);
		}

		if (action === 'Activity:ToDo:ChangeResponsible' && actionData)
		{
			this.#showResponsibleUserSelector(item, actionData);
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

	#showSettingsPopup(item, actionData): void
	{
		this.#settingsPopup = new TodoEditorSettingsPopup({
			sections: this.#getSectionSettings(),
			fetchSettingsPath: 'crm.activity.todo.fetchSettings',
			ownerTypeId: actionData['ownerTypeId'],
			ownerId: actionData['ownerId'],
			id: actionData['entityId'],
			onSave: this.#onSavePopupSettings.bind(this),
		});

		this.#settingsPopup.show();
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

	#onSavePopupSettings(ownerTypeId: Number, ownerId: Number, id: Number, settings: Array[]): void
	{
		const data = {
			ownerTypeId,
			ownerId,
			id,
			settings
		};

		Ajax
			.runAction('crm.activity.todo.updateSettings', { data })
			.catch(response => {
				UI.Notification.Center.notify({
					content: response.errors[0].message,
					autoHideDelay: 5000,
				});

				throw response;
			});
	}

	#getSectionSettings(): ReadonlyArray<SectionSettings>
	{
		const ping = {
			id: SettingsPopupPing.methods.getId(),
			component: SettingsPopupPing,
			active: true,
			showToggleSelector: false,
		};

		const calendar = {
			id: SettingsPopupCalendar.methods.getId(),
			component: SettingsPopupCalendar,
			active: false,
		};

		if (this.#settingsPopup)
		{
			const settings = this.#settingsPopup.getSettings();
			if (settings.ping)
			{
				const pingSettings = settings.ping;
				ping.params = {
					selectedItems: pingSettings.selectedItems,
				};
			}

			if (settings.calendar)
			{
				const calendarSettings = settings.calendar;
				calendar.params = {
					from: calendarSettings.from,
					to: calendarSettings.to,
					duration: calendarSettings.duration,
				}
				calendar.active = true;
			}
		}

		return [
			ping,
			calendar,
		];
	}

	#getDefaultCalendarParams(): Object
	{
		const fromDate = new Date();
		const from = fromDate.getTime() / 1000;
		const duration = 3600;
		const to = from + duration;

		return  {
			from,
			duration,
			to,
		}
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
}
