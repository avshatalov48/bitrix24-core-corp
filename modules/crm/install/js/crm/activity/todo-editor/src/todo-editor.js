import { ajax as Ajax, Dom, Loc, Reflection, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { BitrixVue } from 'ui.vue3';
import { TodoEditor as TodoEditorComponent } from './components/todo-editor';
import { UI } from 'ui.notification';
import { DateTimeFormat } from 'main.date';
import { DatetimeConverter } from 'crm.timeline.tools';
import { TodoEditorBorderColor } from './enums/border-color';
import { FileUploader as TodoEditorFileUploader } from 'crm.activity.file-uploader';
import {
	Calendar as SettingsPopupCalendar,
	SettingsPopup as TodoEditorSettingsPopup
} from 'crm.activity.settings-popup';
import { Ping as SettingsPopupPing } from 'crm.activity.settings-popup';

import './todo-editor.css';

declare type TodoEditorParams = {
	container: HTMLElement,
	deadline?: Date,
	defaultDescription?: string,
	events?: { [event: string]: (event) => {} },
	ownerId: number,
	currentUser?: Object,
	ownerTypeId: number,
	borderColor: string,
	enableCalendarSync: boolean,
	popupMode: boolean,
};

type TodoEditorAdditionalButton = {
	id: string,
	icon: string,
	description: string,
	action: () => {},
}

export type SectionSettings = {
	id: string,
	active: boolean,
	component: Object,
	params: Object,
}

export const TodoEditorMode = {
	ADD: 'add',
	UPDATE: 'update',
}

/**
 * @memberOf BX.Crm.Activity
 */
export class TodoEditor
{
/**
 * @event onFocus
 * @event onChangeDescription
 * @event onSaveHotkeyPressed
 */
	static BorderColor = TodoEditorBorderColor;

	#container: HTMLElement = null;
	#layoutApp = null;
	#layoutComponent = null;
	#loadingPromise: ?Promise = null;

	#mode: String = TodoEditorMode.ADD;
	#ownerTypeId: Number = null;
	#ownerId: Number = null;
	#currentUser: Object = null;
	#defaultDescription: String = null;
	#deadline: Date = null;
	#parentActivityId: Number = null;
	#borderColor = '';
	#activityId: Number = null;
	#eventEmitter: EventEmitter = null;
	#fileUploader: TodoEditorFileUploader = null;
	#settingsPopup: TodoEditorSettingsPopup = null;
	#settings: Object = {};
	#enableCalendarSync: boolean = false;
	#popupMode: boolean = false;

	constructor(params: TodoEditorParams)
	{
		if (!Type.isDomNode(params.container))
		{
			throw new Error('TodoEditor container must be a DOM Node');
		}
		this.#container = params.container;
		this.#borderColor =
			this.#isValidBorderColor(params.borderColor)
			? params.borderColor
			: TodoEditor.BorderColor.DEFAULT
		;
		Dom.addClass(this.#container, this.#getClassname());

		if (!Type.isNumber(params.ownerTypeId))
		{
			throw new Error('OwnerTypeId must be set');
		}
		this.#ownerTypeId = params.ownerTypeId;

		if (!Type.isNumber(params.ownerId))
		{
			throw new Error('OwnerId must be set');
		}
		this.#ownerId = params.ownerId;

		if (!Type.isObject(params.currentUser))
		{
			throw new Error('Current user must be set');
		}
		this.#currentUser = params.currentUser;

		this.#defaultDescription = Type.isString(params.defaultDescription) ? params.defaultDescription : this.#getDefaultDescription();

		this.#deadline = Type.isDate(params.deadline) ? params.deadline : null;

		if (!this.#deadline)
		{
			this.setDefaultDeadLine(false);
		}

		this.#eventEmitter = new EventEmitter;
		this.#eventEmitter.setEventNamespace('Crm.Activity.TodoEditor');

		if (Type.isObject(params.events))
		{
			for (const eventName in params.events)
			{
				if (Type.isFunction(params.events[eventName]))
				{
					this.#eventEmitter.subscribe(eventName, params.events[eventName])
				}
			}
		}

		this.#enableCalendarSync = Type.isBoolean(params.enableCalendarSync) ? params.enableCalendarSync : false;
		this.#popupMode = Type.isBoolean(params.popupMode) ? params.popupMode : false;
	}

	setMode(mode: String): self
	{
		if (!Object.values(TodoEditorMode).some(value => value === mode) )
		{
			throw new Error(`Unknown TodoEditor mode ${mode}`);
		}

		this.#mode = mode;

		return this;
	}

	show(): void
	{
		this.#layoutApp = BitrixVue.createApp(TodoEditorComponent, {
			deadline: this.#deadline,
			defaultDescription: this.#defaultDescription,
			onFocus: this.#onInputFocus.bind(this),
			onChangeDescription: this.#onChangeDescription.bind(this),
			onSaveHotkeyPressed: this.#onSaveHotkeyPressed.bind(this),
			additionalButtons: this.#getAdditionalButtons(),
			popupMode: this.#popupMode,
			currentUser: this.#currentUser,
		});

		this.#layoutComponent = this.#layoutApp.mount(this.#container);
	}

	#getAdditionalButtons(): ReadonlyArray<TodoEditorAdditionalButton>
	{
		const buttons = [];

		if (this.#enableCalendarSync)
		{
			buttons.push(this.#getSettingsButton());
		}

		buttons.push(this.#getFileUploaderButton());

		return buttons;
	}

	#getSettingsButton(): TodoEditorAdditionalButton
	{
		return {
			id: 'settings',
			icon: 'settings',
			description: Loc.getMessage('CRM_ACTIVITY_TODO_SETTINGS_BUTTON_HINT'),
			action: this.#onSettingsButtonClick.bind(this),
		}
	}

	#onSettingsButtonClick(): void
	{
		if (!this.#settingsPopup)
		{
			this.#settingsPopup = new TodoEditorSettingsPopup({
				onSettingsChange: this.onSettingsChange.bind(this),
				sections: this.#getSectionSettings(),
				settings: this.#settings,
			});
		}

		if (this.#layoutComponent)
		{
			this.#settingsPopup.syncSettings(this.#layoutComponent.getData());
		}

		this.#settingsPopup.show();
	}

	onSettingsChange(settings: Object): void
	{
		this.setSettings(settings);

		if (settings?.calendar)
		{
			this.setDeadlineFromTimestamp(settings['calendar'].from);
		}
	}

	setSettings(settings: Object = {}): void
	{
		this.#settings = settings;
	}

	#getSettingsSection(name: String): ?Object
	{
		return (this.#settings[name] || null);
	}

	#getFileUploaderButton(): TodoEditorAdditionalButton
	{
		return {
			id: 'file-uploader',
			icon: 'attach',
			description: Loc.getMessage('CRM_ACTIVITY_TODO_UPLOAD_FILE_BUTTON_HINT'),
			action: this.#onFileUploadButtonClick.bind(this),
		}
	}

	save(): Promise
	{
		if (this.#loadingPromise)
		{
			return this.#loadingPromise;
		}

		// wrap BX.Promise in native js promise
		this.#loadingPromise = new Promise((resolve, reject) => {
			this.#getSaveActionData().then(data => {
				Ajax
					.runAction(this.#getSaveActionPath(), { data })
					.then(resolve)
					.catch(reject)
				;
			})
		}).catch((response) => {
			UI.Notification.Center.notify({
				content: response.errors[0].message,
				autoHideDelay: 5000,
			});

			//so that on error returned promise is marked as rejected
			throw response;
		}).finally(() => {
			this.#loadingPromise = null;
		});

		return this.#loadingPromise;
	}

	#getSaveActionData(): Promise
	{
		return new Promise(resolve => {
			this.#getSaveActionDataSettings().then(settings => {
				const userData = this.#layoutComponent.getData();
				const data = {
					ownerTypeId: this.#ownerTypeId,
					ownerId: this.#ownerId,
					description: userData.description,
					responsibleId: userData.responsibleUserId,
					deadline: DateTimeFormat.format(DatetimeConverter.getSiteDateTimeFormat(), userData.deadline),
					parentActivityId: this.#parentActivityId,
					fileTokens: this.#fileUploader ? this.#fileUploader.getServerFileIds() : [],
					settings,
				}

				if (this.#mode === TodoEditorMode.UPDATE)
				{
					data.id = this.#activityId;
				}

				resolve(data);
			});
		});
	}

	#getSaveActionPath(): String
	{
		return (this.#mode === TodoEditorMode.ADD ? 'crm.activity.todo.add' : 'crm.activity.todo.update');
	}

	#getSaveActionDataSettings(): Promise
	{
		if (this.#settingsPopup)
		{
			return this.#syncAndGetSaveActionDataSettingsFromPopup();
		}

		return this.#getSaveActionDefaultDataSettings();
	}

	#syncAndGetSaveActionDataSettingsFromPopup(): Promise
	{
		if (this.#layoutComponent)
		{
			this.#settingsPopup.syncSettings(this.#layoutComponent.getData());
		}

		// must first work out vue reactivity in nested components
		return new Promise(resolve => {
			setTimeout(()=>{
				resolve(this.#settingsPopup.getSettings());
			}, 0);
		});
	}

	#getSaveActionDefaultDataSettings(): Promise
	{
		const result = [];

		this.#getSectionSettings().forEach(section => {
			if (!section.active)
			{
				return;
			}

			const sectionSettings = section.params;
			sectionSettings.id = section.id;
			result.push(sectionSettings);
		});

		return Promise.resolve(result);
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
				ping.active = true;
				ping.showToggleSelector = false;
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

		if (!ping.params)
		{
			ping.params = this.#getDefaultPingParams();
		}

		if (!calendar.params)
		{
			calendar.params = this.#getDefaultCalendarParams();
			calendar.active = false;
		}

		return [
			ping,
			calendar,
		];
	}

	#getDefaultCalendarParams(): Object
	{
		const fromDate = this.getDeadline() || this.#deadline;
		const from = fromDate.getTime() / 1000;
		const duration = 3600;
		const to = from + duration;

		return  {
			from,
			duration,
			to,
		}
	}

	#getDefaultPingParams(): Object
	{
		// TODO: get real default values from server-side
		return  {
			selectedItems: ['at_the_time_of_the_onset', 'in_15_minutes']
		}
	}

	getDeadline(): ?Date
	{
		return this.#layoutComponent?.getData()['deadline'] ?? null;
	}

	getDescription(): String
	{
		return this.#layoutComponent?.getData()['description'] ?? '';
	}

	setParentActivityId(activityId: Number): self
	{
		this.#parentActivityId = activityId;

		return this;
	}

	setActivityId(activityId: Number): self
	{
		this.#activityId = activityId;

		return this;
	}

	setDeadline(deadLine: String): self
	{
		const value = DateTimeFormat.parse(deadLine);
		if (Type.isDate(value))
		{
			this.#layoutComponent.setDeadline(value);
			this.#deadline = value;
		}

		return this;
	}

	setDeadlineFromTimestamp(timestamp: Number): self
	{
		const value = new Date(timestamp * 1000);
		this.#layoutComponent.setDeadline(value);
		this.#deadline = value;

		return this;
	}

	setDefaultDeadLine(isNeedUpdateLayout: Boolean = true): self
	{
		let defaultDate = BX.parseDate(Loc.getMessage('CRM_TIMELINE_TODO_EDITOR_DEFAULT_DATETIME'));
		if (Type.isDate(defaultDate))
		{
			this.#deadline = defaultDate;
		}
		else
		{
			this.#deadline = new Date();
			this.#deadline.setMinutes(0);
			this.#deadline.setTime(this.#deadline.getTime() + 60 * 60 * 1000); // next hour
		}

		if (isNeedUpdateLayout)
		{
			this.#layoutComponent.setDeadline(this.#deadline);
		}

		return this;
	}

	setFocused(): void
	{
		this.#layoutComponent.setTextareaFocused();
	}

	setDescription(description: String): self
	{
		this.#layoutComponent.setDescription(description);

		return this;
	}

	clearValue(): Promise
	{
		this.#layoutComponent.clearDescription();
		this.#parentActivityId = null;
		this.setDefaultDeadLine();
		this.#layoutComponent.resetResponsibleUserToDefault();
		Dom.removeClass(this.#container, '--is-edit');
		if (this.#fileUploader)
		{
			Dom.removeClass(this.#fileUploader.getContainer(), '--is-displayed');
		}

		this.#fileUploader = null;
		this.#settingsPopup = null;

		return new Promise((resolve) => {
			setTimeout(resolve, 10);
		});
	}

	resetToDefaults(): Promise
	{
		this.#layoutComponent.setDescription(this.#getDefaultDescription());
		this.setDefaultDeadLine();
		this.#layoutComponent.resetResponsibleUserToDefault();

		Dom.removeClass(this.#container, '--is-edit');
		if (this.#fileUploader)
		{
			Dom.removeClass(this.#fileUploader.getContainer(), '--is-displayed');
		}

		this.#fileUploader = null;
		this.#settingsPopup = null;

		return new Promise((resolve) => {
			setTimeout(resolve, 10);
		});
	}

	#getDefaultDescription(): String
	{
		let messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DEFAULT_TEXT';
		switch (this.#ownerTypeId)
		{
			case BX.CrmEntityType.enumeration.deal:
				messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DEFAULT_TEXT_DEAL';
		}

		return Loc.getMessage(messagePhrase);
	}

	#onInputFocus(): void
	{
		Dom.addClass(this.#container, '--is-edit');
		this.#eventEmitter.emit('onFocus');
	}

	#onChangeDescription(description): void
	{
		const event = new BaseEvent({
			data: {
				description
			},
		});

		this.#eventEmitter.emit('onChangeDescription', event);
	}

	#onSaveHotkeyPressed(): void
	{
		this.#eventEmitter.emit('onSaveHotkeyPressed');
	}

	#isValidBorderColor(borderColor: string): boolean
	{
		return Type.isString(borderColor) && TodoEditor.BorderColor[borderColor.toUpperCase()];
	}

	#getClassname(): string
	{
		return `crm-activity__todo-editor --border-${this.#borderColor}`;
	}

	#onFileUploadButtonClick(): void
	{
		this.initFileUploader();
	}

	setStorageElementIds(ids: Array): void
	{
		this.initFileUploader(ids);
	}

	initFileUploader(files: Array = []): void
	{
		if (!this.#fileUploader)
		{
			this.#fileUploader = new TodoEditorFileUploader({
				baseContainer: this.#container,
				events: {
					'File:onRemove': (event) => {
						this.#eventEmitter.emit('onChangeUploaderContainerSize');
					},
					'onUploadStart': (event) => {
						this.#eventEmitter.emit('onChangeUploaderContainerSize');
					},
					// TODO: not implemented yet
					//		'File:onComplete'
					//		'onUploadComplete'
				},
				ownerId: this.#ownerId,
				ownerTypeId: this.#ownerTypeId,
				activityId: this.#activityId,
				files,
			});
		}

		const fileUploaderContainer = this.#fileUploader.getContainer();
		const displayedClass = '--is-displayed';

		if (files && !Dom.hasClass(fileUploaderContainer, displayedClass))
		{
			Dom.addClass(fileUploaderContainer, displayedClass);
		}
		else
		{
			Dom.toggleClass(fileUploaderContainer, displayedClass);
		}

		this.#eventEmitter.emit('onChangeUploaderContainerSize');
	}
}

const namespace = Reflection.namespace('BX.Crm.Activity');

namespace.TodoEditor = TodoEditor;
