import { Loc, Tag } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup, PopupManager } from 'main.popup';
import { TodoEditor } from 'crm.activity.todo-editor';
import { Button, ButtonColor, ButtonState, CancelButton, SaveButton } from 'ui.buttons';
import { TodoNotificationSkip } from 'crm.activity.todo-notification-skip';
import { TodoNotificationSkipMenu } from 'crm.activity.todo-notification-skip-menu';

import './todo-create-notification.css';

declare type TodoCreateNotificationParams = {
	entityTypeId: number,
	entityId: number,
	entityStageId: string,
	stageIdField: string,
	finalStages: Array<string>,
	skipPeriod: ?string,
}

const SAVE_BUTTON_ID = 'save';
const CANCEL_BUTTON_ID = 'cancel';
const SKIP_BUTTON_ID = 'skip';

export class TodoCreateNotification
{
	#timeline: ?Object = null;
	#entityTypeId: string = null;
	#entityId: string = null;
	#entityStageId: string = null;
	#stageIdField: string = null;
	#finalStages: Array<string> = null;

	#allowCloseSlider: boolean = false;
	#isSkipped: boolean = false;
	#popup: ?Popup = null;
	#toDoEditor: ?TodoEditor = null;
	#skipProvider: TodoNotificationSkip = null;
	#skipMenu: ?TodoNotificationSkipMenu = null;

	constructor(params: TodoCreateNotificationParams)
	{
		this.#entityTypeId = params.entityTypeId;
		this.#entityId = params.entityId;
		this.#entityStageId = params.entityStageId;
		this.#stageIdField = params.stageIdField;
		this.#finalStages = params.finalStages;
		this.#isSkipped = !!params.skipPeriod;

		if (BX.CrmTimelineManager)
		{
			this.#timeline = BX.CrmTimelineManager.getDefault();
		}

		this.#bindEvents();

		this.#skipProvider = new TodoNotificationSkip({
			entityTypeId: this.#entityTypeId,
			onSkippedPeriodChange: this.#onSkippedPeriodChange.bind(this),
		});
		this.#skipMenu = new TodoNotificationSkipMenu({
			entityTypeId: this.#entityTypeId,
			selectedValue: params.skipPeriod,
		});
	}

	#bindEvents(): void
	{
		if (this.#getSliderInstance())
		{
			EventEmitter.subscribe(this.#getSliderInstance(), 'SidePanel.Slider:onClose', this.#onCloseSlider.bind(this));
			EventEmitter.subscribe('Crm.EntityModel.Change', this.#onEntityModelChange.bind(this));
			EventEmitter.subscribe('onCrmEntityUpdate', this.#onEntityUpdate.bind(this));
			EventEmitter.subscribe('onCrmEntityDelete', this.#onEntityDelete.bind(this));
		}
		EventEmitter.subscribe('Crm.InterfaceToolbar.MenuBuild', this.#onToolbarMenuBuild.bind(this));
	}

	#getSliderInstance(): BX.SidePanel.Slider | null
	{
		if (top.BX && top.BX.SidePanel)
		{
			const slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
			if (slider && slider.isOpen())
			{
				return slider;
			}
		}

		return null;
	}

	#onCloseSlider(event: BaseEvent): void
	{
		if (this.#allowCloseSlider || this.#isSkipped)
		{
			return;
		}

		const [sliderEvent] = event.getCompatData();

		if (sliderEvent.getSlider() !== top.BX.SidePanel.Instance.getSliderByWindow(window))
		{
			return;
		}
		if (!sliderEvent.isActionAllowed())
		{
			return; // editor has unsaved fields
		}

		if (!this.#timeline || this.#timeline.hasScheduledItems())
		{
			return; // timeline already has scheduled activities
		}

		if (this.#finalStages.indexOf(this.#entityStageId) > -1)
		{
			return; // element has final stage
		}

		sliderEvent.denyAction();

		setTimeout(() => {
			this.#showTodoCreationNotification();
		}, 100);
	}

	#onEntityUpdate(event: BaseEvent): void
	{
		const [eventParams] = event.getCompatData();
		if (eventParams.hasOwnProperty('entityData') && eventParams.entityData.hasOwnProperty(this.#stageIdField))
		{
			this.#entityStageId = eventParams.entityData[this.#stageIdField];
		}

	}

	#onEntityDelete(event: BaseEvent): void
	{
		const [eventParams] = event.getCompatData();
		if (eventParams.hasOwnProperty('id') && eventParams.id == this.#entityId)
		{
			this.#allowCloseSlider = true;
		}
	}

	#onEntityModelChange(event: BaseEvent): void
	{
		const [model, eventParams]  = event.getCompatData();

		if (eventParams.fieldName === this.#stageIdField)
		{
			this.#entityStageId = model.getStringField(this.#stageIdField, this.#entityStageId);
		}
	}

	#onSkippedPeriodChange(period: string): void
	{
		this.#isSkipped = !!period;
	}

	#onToolbarMenuBuild(event: BaseEvent): void
	{
		const [, {items}] = event.getData();
		items.push({ delimiter: true });
		for (const skipItem of this.#skipMenu.getItems())
		{
			items.push(skipItem);
		}
	}

	#onChangeDescription(event: BaseEvent): void
	{
		const {description} = event.getData();
		const saveButton = this.#popup?.getButton(SAVE_BUTTON_ID);
		if (!description.length && !saveButton.getState())
		{
			saveButton.setState(ButtonState.DISABLED);
		}
		else if (description.length && saveButton.getState() === ButtonState.DISABLED)
		{
			saveButton.setState(null);
		}
	}

	#onSaveHotkeyPressed(event: BaseEvent): void
	{
		const saveButton = this.#popup?.getButton(SAVE_BUTTON_ID);
		if (!saveButton.getState()) // if save button is not disabled
		{
			this.#saveTodo();
		}
	}

	#onChangeUploaderContainerSize()
	{
		if (this.#popup)
		{
			this.#popup.adjustPosition();
		}
	}

	#onSkipMenuItemSelect(period): void
	{
		this.#popup?.getButton(SKIP_BUTTON_ID)?.getMenuWindow()?.close();

		this.#popup?.getButton(SAVE_BUTTON_ID)?.setState(ButtonState.DISABLED);
		this.#popup?.getButton(CANCEL_BUTTON_ID)?.setState(ButtonState.DISABLED);
		this.#popup?.getButton(SKIP_BUTTON_ID)?.setState(ButtonState.WAITING);

		this.#skipProvider.saveSkippedPeriod(period).then(() => {
			this.#isSkipped = !!period;
			this.#skipMenu.setSelectedValue(period);
			this.#revertButtonsState();
			this.#allowCloseSlider = true;
			this.#showCancelNotificationInParentWindow();
			this.#getSliderInstance()?.close();
		}).catch(() => {
			this.#revertButtonsState();
		});
	}

	#saveTodo(): void
	{
		this.#popup?.getButton(SAVE_BUTTON_ID)?.setState(ButtonState.WAITING);
		this.#popup?.getButton(CANCEL_BUTTON_ID)?.setState(ButtonState.DISABLED);
		this.#popup?.getButton(SKIP_BUTTON_ID)?.setState(ButtonState.DISABLED);

		this.#toDoEditor.save().then((result) => {
			this.#revertButtonsState();
			if (!(result.hasOwnProperty('errors') && result.errors.length))
			{
				this.#allowCloseSlider = true;
				this.#closePopup();
				this.#getSliderInstance()?.close();
			}
		}).catch(() => {
			this.#revertButtonsState();
		});
	}

	#revertButtonsState()
	{
		this.#popup?.getButton(SAVE_BUTTON_ID)?.setState(null);
		this.#popup?.getButton(CANCEL_BUTTON_ID)?.setState(null);
		this.#popup?.getButton(SKIP_BUTTON_ID)?.setState(null);
	}

	#closePopup(): void
	{
		this.#popup?.close();
	}

	#closeSlider(): void
	{
		this.#allowCloseSlider = true;
		this.#getSliderInstance()?.close();
	}

	#showTodoCreationNotification(): void
	{
		if (!this.#popup)
		{
			const htmlStyles = getComputedStyle(document.documentElement);
			const popupPadding = htmlStyles.getPropertyValue('--ui-space-inset-sm');
			const popupPaddingNumberValue = parseFloat(popupPadding) || 12;
			const popupOverlayColor = htmlStyles.getPropertyValue('--ui-color-base-solid') || '#000000';

			this.#popup = PopupManager.create({
				id: 'todo-create-confirm-' + this.#entityTypeId + '-' + this.#entityId,
				closeIcon: true,
				padding: popupPaddingNumberValue,
				overlay: {
					opacity: 40,
					backgroundColor: popupOverlayColor,
				},
				content: this.#getPopupContent(),
				buttons: this.#getPopupButtons(),
				width: 545,
				events: {
					onClose: this.#closeSlider.bind(this)
				},
				className: 'crm-activity__todo-create-notification-popup'
			});
		}

		this.#popup.show();

		setTimeout(() => {
			this.#toDoEditor.setFocused();
		}, 10);

		setTimeout(() => {
			this.#popup.setClosingByEsc(true);
		}, 300);
	}

	#getPopupTitle(): string
	{
		return Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_TITLE');
	}

	#getPopupDescription(): string
	{
		let messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DESCRIPTION';
		switch (this.#entityTypeId)
		{
			case BX.CrmEntityType.enumeration.lead:
				messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DESCRIPTION_LEAD';
				break;
			case BX.CrmEntityType.enumeration.deal:
				messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_DESCRIPTION_DEAL';
				break;
		}

		return Loc.getMessage(messagePhrase);
	}

	#getPopupContent(): HTMLElement
	{
		const editorContainer = Tag.render`<div></div>`;

		const content = Tag.render`<div class="crm-activity__todo-create-notification">
			<div class="crm-activity__todo-create-notification_title">${this.#getPopupTitle()}</div>
			<div class="crm-activity__todo-create-notification_content">
				<div class="crm-activity__todo-create-notification_description">${this.#getPopupDescription()}</div>
				${editorContainer}
			</div>
		</div>`;

		this.#toDoEditor = new TodoEditor({
			container: editorContainer,
			ownerTypeId: this.#entityTypeId,
			ownerId: this.#entityId,
			currentUser: this.#timeline.getCurrentUser(),
			events: {
				onChangeDescription: this.#onChangeDescription.bind(this),
				onSaveHotkeyPressed: this.#onSaveHotkeyPressed.bind(this),
				onChangeUploaderContainerSize: this.#onChangeUploaderContainerSize.bind(this),
			},
			borderColor: TodoEditor.BorderColor.PRIMARY,
		});
		this.#toDoEditor.show();

		return content;
	}

	#getPopupButtons(): Array<Button>
	{
		return [
			new SaveButton({
				id: SAVE_BUTTON_ID,
				round: true,
				state: this.#toDoEditor.getDescription() ? null : ButtonState.DISABLED,
				events: {
					click: this.#saveTodo.bind(this),
				},
			}),
			new CancelButton({
				text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_CANCEL'),
				color: ButtonColor.LIGHT_BORDER,
				id: CANCEL_BUTTON_ID,
				round: true,
				events: {
					click: this.#closePopup.bind(this),
				},
			}),
			new Button({
				text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP'),
				color: ButtonColor.LINK,
				id: SKIP_BUTTON_ID,
				dropdown: true,
				menu: {
					closeByEsc: true,
					items: this.#getSkipMenuItems(),
					minWidth: 233,
				},
			}),
		]
	}

	#getSkipMenuItems(): Array
	{
		const menuItems = [];

		menuItems.push({
			id: 'day',
			text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_DAY'),
			onclick: this.#onSkipMenuItemSelect.bind(this, 'day')
		});
		menuItems.push({
			id: 'week',
			text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_WEEK'),
			onclick: this.#onSkipMenuItemSelect.bind(this, 'week')
		});
		menuItems.push({
			id: 'month',
			text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_MONTH'),
			onclick: this.#onSkipMenuItemSelect.bind(this, 'month')
		});
		menuItems.push({
			id: 'forever',
			text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOREVER'),
			onclick: this.#onSkipMenuItemSelect.bind(this, 'forever')
		});

		return menuItems;
	}

	#showCancelNotificationInParentWindow()
	{
		if (top.BX && top.BX.Runtime)
		{
			const entityTypeId = this.#entityTypeId;
			top.BX.Runtime.loadExtension('crm.activity.todo-notification-skip').then((exports) => {
				const skipProvider = new exports.TodoNotificationSkip({
					entityTypeId
				});
				skipProvider.showCancelPeriodNotification();
			});
		}
	}
}
