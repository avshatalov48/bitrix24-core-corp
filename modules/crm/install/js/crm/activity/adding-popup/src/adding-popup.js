import { ajax as Ajax, Tag, Text, Type } from "main.core";
import { Popup } from "main.popup";
import { ButtonColor, ButtonSize, ButtonState, CancelButton, SaveButton } from "ui.buttons";
import { TodoEditor, TodoEditorMode } from "crm.activity.todo-editor";
import { BaseEvent, EventEmitter } from "main.core.events";
import { UI } from 'ui.notification';

import "./adding-popup.css"

/**
 * @event onSave
 * @event onClose
 */
export class AddingPopup
{
	#entityId: Number = null;
	#entityTypeId: Number = null;
	#currentUser: Object = null;
	#popup: ?Popup = null;
	#popupContainer: HTMLElement = null;
	#todoEditor: ?TodoEditor = null;
	#eventEmitter: EventEmitter = null;

	constructor(entityTypeId: Number, entityId: Number, currentUser: Object, params: Object)
	{
		this.#entityId = Text.toInteger(entityId);
		this.#entityTypeId = Text.toInteger(entityTypeId);
		this.#currentUser = currentUser;

		this.#eventEmitter = new EventEmitter;
		this.#eventEmitter.setEventNamespace('Crm.Activity.AddingPopup');
		
		if (!Type.isPlainObject(params))
		{
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
	}

	show(bindElement: HTMLElement, mode: String = TodoEditorMode.ADD)
	{
		const popup = this.#createPopupIfNotExists();
		popup.setBindElement(bindElement);
		if (popup.isShown())
		{
			return;
		}

		if (!this.#popupContainer.hasChildNodes())
		{
			// just created, initialize
			this.#todoEditor = new TodoEditor({
				container: this.#popupContainer,
				ownerTypeId: this.#entityTypeId,
				ownerId: this.#entityId,
				currentUser: this.#currentUser,
				events: {
					onChangeDescription: this.#onChangeEditorDescription.bind(this),
					onSaveHotkeyPressed: this.#onEditorSaveHotkeyPressed.bind(this),
					onChangeUploaderContainerSize: this.#onChangeUploaderContainerSize.bind(this),
				},
				popupMode: true
			});

			popup.setButtons([
				new SaveButton({
					id: 'save',
					color: ButtonColor.PRIMARY,
					size: ButtonSize.EXTRA_SMALL,
					round: true,
					events: {
						click: this.#saveAndClose.bind(this),
					}
				}),
				new CancelButton({
					id: 'cancel',
					size: ButtonSize.EXTRA_SMALL,
					round: true,
					events: {
						click: () => popup.close(),
					}
				}),
			]);

			popup.subscribeOnce('onFirstShow', () => this.#todoEditor.show());
			popup.subscribe('onAfterShow', () => {
				this.#actualizePopupLayout(this.#todoEditor.getDescription());
				this.#todoEditor.setFocused();
			});
			popup.subscribe('onAfterClose', () => {
				this.#todoEditor.resetToDefaults().then(()=>{
					this.#eventEmitter.emit('onClose');
				});
			});
			popup.subscribe('onShow', () => {
				const { mode, activity } = popup.params;
				if (mode === TodoEditorMode.UPDATE && activity)
				{
					this.#todoEditor
						.setMode(mode)
						.setActivityId(activity.id)
						.setDescription(activity.description)
						.setDeadline(activity.deadline)
					;

					if (Type.isArrayFilled(activity.storageElementIds))
					{
						this.#todoEditor.setStorageElementIds(activity.storageElementIds);
					}
				}
			});
		}

		this.#prepareAndShowPopup(popup, mode);
	}

	#prepareAndShowPopup(popup: Popup, mode: String = TodoEditorMode.ADD): void
	{
		popup.params.mode = mode;
		if (mode === TodoEditorMode.ADD)
		{
			popup.show();
			return;
		}

		if (mode === TodoEditorMode.UPDATE)
		{
			this.#fetchNearActivity().then(data => {
				if (data)
				{
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
			Ajax.runAction('crm.activity.todo.getNearest', {data})
				.then(({data}) => resolve(data))
				.catch(response => {
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
			this.#popupContainer = Tag.render`<div class="crm-activity-adding-popup-container"></div>`;
			this.#popup = new Popup({
				id: `kanban_planner_menu_${this.#entityId}`,
				overlay: {
					opacity: 0,
				},
				content: this.#popupContainer,
				cacheable: false,
				isScrollBlock: true,
				className: 'crm-activity-adding-popup',
				closeByEsc: true,
				closeIcon: false,
				angle: {
					offset: 27,
				},
				padding: 16,
				minWidth: 500,
				maxWidth: 550,
				minHeight: 150,
				maxHeight: 400,
			});
		}

		return this.#popup;
	}

	bindPopup(bindElement: HTMLElement): void
	{
		if (!this.#popup)
		{
			return;
		}

		if (bindElement !== this.#popup.bindElement)
		{
			this.#popup.setBindElement(bindElement);
		}
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

	#actualizePopupLayout(description): void{
		if (this.#popup && this.#popup.isShown())
		{
			this.#eventEmitter.emit('onActualizePopupLayout', { entityId: this.#entityId });

			this.#popup.adjustPosition({
				forceBindPosition: true,
			});

			const saveButton = this.#popup.getButton('save');

			if (!description.length && saveButton && !saveButton.getState())
			{
				saveButton.setState(ButtonState.DISABLED);
			}
			else if (description.length && saveButton && saveButton.getState() === ButtonState.DISABLED)
			{
				saveButton.setState(null);
			}
		}
	}

	#onChangeEditorDescription(event: BaseEvent)
	{
		const {description} = event.getData();
		this.#actualizePopupLayout(description);
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
}
