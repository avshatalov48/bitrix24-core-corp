import {ajax as Ajax, Dom, Loc, Reflection, Type} from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { BitrixVue } from 'ui.vue3';
import { TodoEditor as TodoEditorComponent } from './components/todo-editor';
import { UI } from 'ui.notification';
import { DateTimeFormat } from 'main.date';
import { DatetimeConverter } from 'crm.timeline.tools';
import { TodoEditorBorderColor } from './enums/border-color';
import { FileUploader as TodoEditorFileUploader } from 'crm.activity.file-uploader';

import './todo-editor.css';

declare type TodoEditorParams = {
	container: HTMLElement,
	deadline?: Date,
	defaultDescription?: string,
	events?: { [event: string]: (event) => {} },
	ownerId: number,
	ownerTypeId: number,
	borderColor: string,
	popupMode: boolean
};

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
	#defaultDescription: String = null;
	#deadline: Date = null;
	#parentActivityId: Number = null;
	#borderColor = '';
	#activityId: Number = null;
	#eventEmitter: EventEmitter = null;
	#fileUploader: TodoEditorFileUploader = null;
	#isPopupMode: boolean = false;

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

		this.#defaultDescription = Type.isString(params.defaultDescription) ? params.defaultDescription : this.#getDefaultDescription();

		this.#deadline = Type.isDate(params.deadline) ? params.deadline : null;

		if (!this.#deadline)
		{
			this.setDefaultDeadLine(false);
		}

		if (params.popupMode === true)
		{
			this.#isPopupMode = true;
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

	show()
	{
		this.#layoutApp = BitrixVue.createApp(TodoEditorComponent, {
			deadline: this.#deadline,
			defaultDescription: this.#defaultDescription,
			onFocus: this.#onInputFocus.bind(this),
			onChangeDescription: this.#onChangeDescription.bind(this),
			onSaveHotkeyPressed: this.#onSaveHotkeyPressed.bind(this),
			additionalButtons: [{
				id: 'file-uploader',
				icon: 'attach',
				description: Loc.getMessage('CRM_ACTIVITY_TODO_UPLOAD_FILE_BUTTON_HINT'),
				action: this.#onFileUploadButtonClick.bind(this),
			}],
			popupMode: this.#isPopupMode
		});
		this.#layoutComponent = this.#layoutApp.mount(this.#container);
	}

	save(): Promise
	{
		if (this.#loadingPromise)
		{
			return this.#loadingPromise;
		}

		const data = this.#getSaveActionData();

		// wrap BX.Promise in native js promise
		this.#loadingPromise = new Promise((resolve, reject) => {
			Ajax
				.runAction(this.#getSaveActionPath(), { data })
				.then(resolve)
				.catch(reject)
			;

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

	#getSaveActionData(): Object
	{
		const userData = this.#layoutComponent.getData();
		const data = {
			ownerTypeId: this.#ownerTypeId,
			ownerId: this.#ownerId,
			description: userData.description,
			deadline: DateTimeFormat.format(DatetimeConverter.getSiteDateTimeFormat(), userData.deadline),
			parentActivityId: this.#parentActivityId,
			fileTokens: this.#fileUploader ? this.#fileUploader.getServerFileIds() : []
		}

		if (this.#mode === TodoEditorMode.UPDATE)
		{
			data.id = this.#activityId;
		}

		return data;
	}

	#getSaveActionPath(): String
	{
		return (this.#mode === TodoEditorMode.ADD ? 'crm.activity.todo.add' : 'crm.activity.todo.update');
	}

	getDeadline(): ?Date
	{
		return this.#layoutComponent.getData()['deadline'] ?? null;
	}

	getDescription(): String
	{
		return this.#layoutComponent.getData()['description'] ?? '';
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
		let value = BX.parseDate(deadLine);
		if (Type.isDate(value))
		{
			this.#layoutComponent.setDeadline(value);
			this.#deadline = value;
		}

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
		Dom.removeClass(this.#container, '--is-edit');
		if (this.#fileUploader) {
			Dom.removeClass(this.#fileUploader.getContainer(), '--is-displayed');
		}

		this.#fileUploader = null;

		return new Promise((resolve) => {
			setTimeout(resolve, 10);
		});
	}

	resetToDefaults(): Promise
	{
		this.#layoutComponent.setDescription(this.#getDefaultDescription());
		this.setDefaultDeadLine();
		Dom.removeClass(this.#container, '--is-edit');
		if (this.#fileUploader)
		{
			Dom.removeClass(this.#fileUploader.getContainer(), '--is-displayed');
		}

		this.#fileUploader = null;

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
