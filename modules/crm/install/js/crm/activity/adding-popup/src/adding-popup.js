import { TodoEditorMode, TodoEditorV2 } from 'crm.activity.todo-editor-v2';
import { ajax as Ajax, Loc, Tag, Text, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import type { PopupOptions } from 'main.popup';
import { Popup } from 'main.popup';
import { ButtonColor, ButtonSize, CancelButton, SaveButton } from 'ui.buttons';
import { UI } from 'ui.notification';

import './adding-popup.css';

type Context = {
	analytics: Object;
}

/**
 * @event onSave
 * @event onClose
 */
export class AddingPopup
{
	#entityId: Number = null;
	#entityTypeId: Number = null;
	#currentUser: Object = null;
	#pingSettings: Object = null;
	#calendarSettings: Object = null;
	#colorSettings: Object = null;
	#popup: ?Popup = null;
	#popupContainer: HTMLElement = null;
	#popupToDoEditorContainer: HTMLElement = null;
	#todoEditor: TodoEditorV2 | null = null;
	#eventEmitter: EventEmitter = null;
	#context: Context = {};

	constructor(entityTypeId: Number, entityId: Number, currentUser: Object, settings: Object, params: Object)
	{
		this.#entityId = Text.toInteger(entityId);
		this.#entityTypeId = Text.toInteger(entityTypeId);
		this.#currentUser = currentUser;

		this.#eventEmitter = new EventEmitter();
		this.#eventEmitter.setEventNamespace('Crm.Activity.AddingPopup');

		if (Type.isObject(settings))
		{
			this.#pingSettings = settings.pingSettings ?? null;
			this.#calendarSettings = settings.calendarSettings ?? null;
			this.#colorSettings = settings.colorSettings ?? null;
		}

		if (!Type.isPlainObject(params))
		{
			// eslint-disable-next-line no-param-reassign
			params = {};
		}

		if (Type.isObject(params.events))
		{
			for (const eventName in params.events)
			{
				if (Type.isFunction(params.events[eventName]))
				{
					this.#eventEmitter.subscribe(eventName, params.events[eventName]);
				}
			}
		}

		if (Type.isPlainObject(params.context))
		{
			this.#context = params.context;
		}
	}

	async show(mode: String = TodoEditorMode.ADD)
	{
		const popup = this.#createPopupIfNotExists();

		if (popup.isShown())
		{
			return;
		}

		if (!this.#popupToDoEditorContainer.hasChildNodes())
		{
			await this.#createToDoEditor();

			popup.setButtons([
				new SaveButton({
					id: 'save',
					color: ButtonColor.PRIMARY,
					size: ButtonSize.EXTRA_SMALL,
					round: true,
					events: {
						click: this.#saveAndClose.bind(this),
					},
				}),
				new CancelButton({
					id: 'cancel',
					size: ButtonSize.EXTRA_SMALL,
					round: true,
					events: {
						click: () => popup.close(),
					},
				}),
			]);

			popup.subscribeOnce('onFirstShow', (event) => {
				event.target.getZIndexComponent().setZIndex(1400);
				this.#todoEditor.show();
			});
			popup.subscribe('onAfterShow', () => {
				this.#actualizePopupLayout();
				this.#todoEditor.setFocused();
			});
			popup.subscribe('onAfterClose', () => {
				void this.#todoEditor.resetToDefaults().then(() => {
					this.#eventEmitter.emit('onClose');
				});
			});
			popup.subscribe('onShow', () => {
				const { mode: todoEditorMode, activity } = popup.params;
				if (todoEditorMode === TodoEditorMode.UPDATE && activity)
				{
					this.#todoEditor
						.setMode(todoEditorMode)
						.setActivityId(activity.id)
						.setDescription(activity.description)
						.setDeadline(activity.deadline)
					;
				}
			});
		}

		this.#prepareAndShowPopup(popup, mode);
	}

	async #createToDoEditor(): TodoEditorV2
	{
		// just created, initialize
		const params = {
			container: this.#popupToDoEditorContainer,
			ownerTypeId: this.#entityTypeId,
			ownerId: this.#entityId,
			currentUser: this.#currentUser,
			pingSettings: this.#pingSettings,
			events: {
				onSaveHotkeyPressed: this.#onEditorSaveHotkeyPressed.bind(this),
				onChangeUploaderContainerSize: this.#onChangeUploaderContainerSize.bind(this),
				onFocus: this.#onFocus.bind(this),
			},
			popupMode: true,
		};

		const analytics = this.#context?.analytics ?? {};
		const section = analytics.c_section ?? null;
		const subSection = analytics.c_sub_section ?? null;

		params.calendarSettings = this.#calendarSettings;
		params.colorSettings = this.#colorSettings;
		params.defaultDescription = '';
		params.analytics = {
			section,
			subSection,
		};

		this.#todoEditor = new TodoEditorV2(params);
	}

	#prepareAndShowPopup(popup: Popup, mode: String = TodoEditorMode.ADD): void
	{
		// eslint-disable-next-line no-param-reassign
		popup.params.mode = mode;
		if (mode === TodoEditorMode.ADD)
		{
			popup.show();

			return;
		}

		if (mode === TodoEditorMode.UPDATE)
		{
			void this.#fetchNearActivity().then((data) => {
				if (data)
				{
					// eslint-disable-next-line no-param-reassign
					popup.params.activity = data;
					popup.show();
				}
			});

			return;
		}

		console.error('Wrong TodoEditor mode');
	}

	#fetchNearActivity(): Promise
	{
		const data = {
			ownerTypeId: this.#entityTypeId,
			ownerId: this.#entityId,
		};

		return new Promise((resolve, reject) => {
			Ajax.runAction('crm.activity.todo.getNearest', { data })
				.then(({ data: responseData }) => resolve(responseData))
				.catch((response) => {
					UI.Notification.Center.notify({
						content: response.errors[0].message,
						autoHideDelay: 5000,
					});
					reject();
				});
		});
	}

	#createPopupIfNotExists(): Popup
	{
		if (!this.#popup || this.#popup.isDestroyed())
		{
			this.#popupToDoEditorContainer = Tag.render`<div></div>`;
			this.#popupContainer = Tag.render`
				<div class="crm-activity-adding-popup-container">
					${this.#getPopupTitle()}
					${this.#popupToDoEditorContainer}
				</div>
			`;

			this.#popup = new Popup(this.#getPopupParams());
		}

		return this.#popup;
	}

	#getPopupTitle(): ?HTMLDivElement
	{
		return Tag.render`
			<div class="crm-activity-adding-popup-title">
				${Loc.getMessage('CRM_ACTIVITY_ADDING_POPUP_TITLE')}
			</div>
		`;
	}

	#getPopupParams(): PopupOptions
	{
		const { innerWidth } = window;

		return {
			id: `kanban_planner_menu_${this.#entityId}`,
			content: this.#popupContainer,
			cacheable: false,
			isScrollBlock: true,
			className: 'crm-activity-adding-popup',
			closeByEsc: true,
			closeIcon: false,
			padding: 16,
			minWidth: 537,
			width: Math.round(innerWidth * 0.45),
			maxWidth: 737,
			minHeight: 150,
			maxHeight: 482,
			overlay: {
				opacity: 50,
			},
		};
	}

	#saveAndClose(): void
	{
		if (this.#popup)
		{
			const saveButton = this.#popup.getButton('save');
			if (saveButton.getState())
			{
				return; // button is disabled
			}
			saveButton?.setWaiting(true);
			this.#todoEditor.save()
				.then(() => {
					this.#popup.close();
					this.#eventEmitter.emit('onSave');
				})
				.catch(() => {})
				.finally(() => saveButton?.setWaiting(false))
			;
		}
	}

	#actualizePopupLayout(description): void
	{
		if (this.#popup && this.#popup.isShown())
		{
			this.#eventEmitter.emit('onActualizePopupLayout', { entityId: this.#entityId });

			this.#popup.adjustPosition({
				forceBindPosition: true,
			});
		}
	}

	#onEditorSaveHotkeyPressed()
	{
		this.#saveAndClose();
	}

	#onChangeUploaderContainerSize()
	{
		if (this.#popup)
		{
			this.#eventEmitter.emit('onActualizePopupLayout', { entityId: this.#entityId });
			this.#popup.adjustPosition();
		}
	}

	#onFocus()
	{
		setTimeout(() => {
			const popup = this.#createPopupIfNotExists();

			popup.adjustPosition({
				forceBindPosition: true,
			});
		}, 0);
	}
}
