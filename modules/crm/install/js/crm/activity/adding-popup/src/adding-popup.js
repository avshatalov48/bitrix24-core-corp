import {Text, Type} from "main.core";
import {PopupManager, Popup} from "main.popup";
import {SaveButton, CancelButton, ButtonColor, ButtonSize, ButtonState} from "ui.buttons";
import {TodoEditor} from "crm.activity.todo-editor";
import {BaseEvent, EventEmitter} from "main.core.events";

import "./adding-popup.css"

/**
 * @event onSave
 * @event onClose
 */
export class AddingPopup
{
	#entityId: Number = null;
	#entityTypeId: Number = null;
	#popup: ?Popup = null;
	#todoEditor: ?TodoEditor = null;
	#eventEmitter: EventEmitter = null;

	constructor(entityTypeId: Number, entityId: Number, params: Object)
	{
		this.#entityId = Text.toInteger(entityId);
		this.#entityTypeId = Text.toInteger(entityTypeId);

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

	show(bindElement: HTMLElement)
	{
		const popup = this.#createPopupIfNotExists();
		popup.setBindElement(bindElement);
		if (popup.isShown())
		{
			return;
		}

		if (!popup.getContentContainer().hasChildNodes())
		{
			// just created, initialize
			this.#todoEditor = new TodoEditor({
				container: popup.getContentContainer(),
				ownerTypeId: this.#entityTypeId,
				ownerId: this.#entityId,
				events: {
					onChangeDescription: this.#onChangeEditorDescription.bind(this),
					onSaveHotkeyPressed: this.#onEditorSaveHotkeyPressed.bind(this)
				}
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
				this.#todoEditor.resetToDefaults();
				this.#eventEmitter.emit('onClose');
			});
		}
		popup.show();
	}

	#createPopupIfNotExists(): Popup
	{
		if (!this.#popup)
		{
			this.#popup = PopupManager.create({
				id: `kanban_planner_menu_${this.#entityId}`,
				overlay: {
					opacity: 0,
				},
				isScrollBlock: true,
				className: 'crm-activity-adding-popup',
				closeByEsc: true,
				closeIcon: false,
				angle: {
					offset: 27,
				},
				minWidth: 500,
				padding: 16,
				minHeight: 150,
			});
		}

		return this.#popup;
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
}
