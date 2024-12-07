import { TodoEditorV2 } from 'crm.activity.todo-editor-v2';
import { TodoNotificationSkip } from 'crm.activity.todo-notification-skip';
import { TodoNotificationSkipMenu } from 'crm.activity.todo-notification-skip-menu';
import { Event, Loc, Tag, Cache } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Popup, PopupManager } from 'main.popup';
import { Button, ButtonColor, ButtonState } from 'ui.buttons';

import './todo-create-notification.css';

declare type TodoCreateNotificationParams = {
	entityTypeId: number,
	entityId: number,
	entityStageId: string,
	stageIdField: string,
	finalStages: Array<string>,
	skipPeriod: ?string,
	analytics?: Object,
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
	#toDoEditor: ?TodoEditorV2 = null;
	#skipProvider: TodoNotificationSkip = null;
	#skipMenu: ?TodoNotificationSkipMenu = null;
	#sliderIsMinimizing: boolean = false;
	#analytics: Object = null;
	#refs: typeof(Cache.MemoryCache) = new Cache.MemoryCache();

	constructor(params: TodoCreateNotificationParams)
	{
		this.#entityTypeId = params.entityTypeId;
		this.#entityId = params.entityId;
		this.#entityStageId = params.entityStageId;
		this.#stageIdField = params.stageIdField;
		this.#finalStages = params.finalStages;
		this.#isSkipped = Boolean(params.skipPeriod);
		this.#analytics = params.analytics ?? {};

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

	#isSliderMinimizeAvailable(): boolean
	{
		return Object.hasOwn(BX.SidePanel.Slider.prototype, 'minimize')
			&& Object.hasOwn(BX.SidePanel.Slider.prototype, 'isMinimizing')
		;
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

		if (this.#finalStages.includes(this.#entityStageId))
		{
			return; // element has final stage
		}

		this.#sliderIsMinimizing = this.#isSliderMinimizeAvailable() && sliderEvent.getSlider()?.isMinimizing();
		sliderEvent.denyAction();

		setTimeout(async () => {
			this.#showTodoCreationNotification();
		}, 100);
	}

	#onEntityUpdate(event: BaseEvent): void
	{
		const [eventParams] = event.getCompatData();
		if (
			Object.hasOwn(eventParams, 'entityData')
			&& Object.hasOwn(eventParams.entityData, this.#stageIdField)
		)
		{
			this.#entityStageId = eventParams.entityData[this.#stageIdField];
		}
	}

	#onEntityDelete(event: BaseEvent): void
	{
		const [eventParams] = event.getCompatData();
		if (
			Object.hasOwn(eventParams, 'id')
			&& Text.toString(eventParams.id) === Text.toString(this.#entityId)
		)
		{
			this.#allowCloseSlider = true;
		}
	}

	#onEntityModelChange(event: BaseEvent): void
	{
		const [model, eventParams] = event.getCompatData();

		if (eventParams.fieldName === this.#stageIdField)
		{
			this.#entityStageId = model.getStringField(this.#stageIdField, this.#entityStageId);
		}
	}

	#onSkippedPeriodChange(period: string): void
	{
		this.#isSkipped = Boolean(period);
	}

	#onToolbarMenuBuild(event: BaseEvent): void
	{
		const [, { items }] = event.getData();
		items.push({ delimiter: true });
		for (const skipItem of this.#skipMenu.getItems())
		{
			items.push(skipItem);
		}
	}

	#onSaveHotkeyPressed(): void
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

		this.getTodoEditor().cancel({
			analytics: {
				...this.#analytics,
				element: TodoEditorV2.AnalyticsElement.skipPeriodButton,
				notificationSkipPeriod: period,
			},
		});

		this.#skipProvider.saveSkippedPeriod(period).then(() => {
			this.#isSkipped = Boolean(period);
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

		this.getTodoEditor().save().then((result) => {
			this.#revertButtonsState();

			if (!(Object.hasOwn(result, 'errors') && result.errors.length > 0))
			{
				this.#allowCloseSlider = true;
				this.#closePopup();
				this.#getSliderInstance()?.close();
			}
		}).catch(() => {
			this.#revertButtonsState();
		});
	}

	#cancel(): void
	{
		void this.getTodoEditor().cancel({
			analytics: {
				...this.#analytics,
				element: TodoEditorV2.AnalyticsElement.cancelButton,
			},
		}).then(() => {
			this.#closePopup();
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
		if (this.#isSliderMinimizeAvailable() && this.#sliderIsMinimizing)
		{
			this.#getSliderInstance()?.minimize();

			return;
		}

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

			const { innerWidth } = window;

			this.#popup = PopupManager.create({
				id: `todo-create-confirm-${this.#entityTypeId}-${this.#entityId}`,
				closeIcon: false,
				padding: popupPaddingNumberValue,
				overlay: {
					opacity: 40,
					backgroundColor: popupOverlayColor,
				},
				content: this.#getPopupContent(),
				minWidth: 537,
				width: Math.round(innerWidth * 0.45),
				maxWidth: 737,
				events: {
					onClose: this.#closeSlider.bind(this),
					onFirstShow: () => {
						this.getTodoEditor().show();
						this.getTodoEditor().setFocused();
					},
				},
				className: 'crm-activity__todo-create-notification-popup',
			});
		}

		this.#popup.show();

		setTimeout(() => {
			this.getTodoEditor().setFocused();
		}, 10);

		setTimeout(() => {
			this.#popup.setClosingByEsc(true);

			Event.bind(document, 'keyup', (event) => {
				if (event.key === 'Escape')
				{
					void this.getTodoEditor().cancel({
						analytics: {
							...this.#analytics,
							element: TodoEditorV2.AnalyticsElement.cancelButton,
						},
					});
				}
			});
		}, 300);
	}

	#getPopupContent(): HTMLElement
	{
		return this.#refs.remember('content', () => {
			const buttonsContainer = Tag.render`
				<div class="crm-activity__todo-create-notification_footer">
					<div class="crm-activity__todo-create-notification_buttons-container">
						<button 
							class="ui-btn ui-btn-xs ui-btn-primary ui-btn-round"
							onclick="${this.#saveTodo.bind(this)}"
						>
							${Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_OK_BUTTON_V2')}
						</button>
						<button
							class="ui-btn ui-btn-xs ui-btn-link"
							onclick="${this.#cancel.bind(this)}"
						>
							${Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_CANCEL_BUTTON_V2')}
						</button>
					</div>
					${this.#getPreparedForV2NotificationSkipButton().render()}
				</div>
			`;

			return Tag.render`
				<div>
					<div class="crm-activity__todo-create-notification_title --v2">
						${this.#getNotificationTitle()}
					</div>
					<div>
						${this.#getTodoEditorContainer()}
					</div>
					${buttonsContainer}
				</div>
			`;
		});
	}

	#getTodoEditorContainer(): HTMLElement
	{
		return this.#refs.remember('editor', () => {
			return Tag.render`<div></div>`;
		});
	}

	#getNotificationTitle(): string
	{
		let code = null;

		switch (this.#entityTypeId)
		{
			case BX.CrmEntityType.enumeration.lead:
				code = 'CRM_ACTIVITY_TODO_NOTIFICATION_TITLE_V2_LEAD';
				break;
			case BX.CrmEntityType.enumeration.deal:
				code = 'CRM_ACTIVITY_TODO_NOTIFICATION_TITLE_V2_DEAL';
				break;
			default:
				code = 'CRM_ACTIVITY_TODO_NOTIFICATION_TITLE_V2';
		}

		return Loc.getMessage(code);
	}

	#getPreparedForV2NotificationSkipButton(): Button
	{
		return this.#createNotificationSkipButton()
			.setNoCaps()
			.addClass('crm-activity__todo-create-notification_skip-button')
		;
	}

	getTodoEditor(): TodoEditorV2
	{
		if (this.#toDoEditor !== null)
		{
			return this.#toDoEditor;
		}

		const params = {
			container: this.#getTodoEditorContainer(),
			ownerTypeId: this.#entityTypeId,
			ownerId: this.#entityId,
			currentUser: this.#timeline.getCurrentUser(),
			pingSettings: this.#timeline.getPingSettings(),
			events: {
				onSaveHotkeyPressed: this.#onSaveHotkeyPressed.bind(this),
				onChangeUploaderContainerSize: this.#onChangeUploaderContainerSize.bind(this),
			},
			borderColor: TodoEditorV2.BorderColor.PRIMARY,
		};

		params.calendarSettings = this.#timeline.getCalendarSettings();
		params.colorSettings = this.#timeline.getColorSettings();
		params.defaultDescription = '';
		params.analytics = this.#analytics;

		this.#toDoEditor = new TodoEditorV2(params);

		return this.#toDoEditor;
	}

	#createNotificationSkipButton(): Button
	{
		return new Button({
			text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_V2'),
			color: ButtonColor.LINK,
			id: SKIP_BUTTON_ID,
			dropdown: true,
			menu: {
				closeByEsc: true,
				items: this.#getSkipMenuItems(),
				minWidth: 233,
			},
		});
	}

	#getSkipMenuItems(): Array
	{
		return [
			{
				id: 'day',
				text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_DAY'),
				onclick: this.#onSkipMenuItemSelect.bind(this, 'day'),
			},
			{
				id: 'week',
				text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_WEEK'),
				onclick: this.#onSkipMenuItemSelect.bind(this, 'week'),
			},
			{
				id: 'month',
				text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOR_MONTH'),
				onclick: this.#onSkipMenuItemSelect.bind(this, 'month'),
			},
			{
				id: 'forever',
				text: Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_FOREVER'),
				onclick: this.#onSkipMenuItemSelect.bind(this, 'forever'),
			},
		];
	}

	#showCancelNotificationInParentWindow()
	{
		if (top.BX && top.BX.Runtime)
		{
			const entityTypeId = this.#entityTypeId;
			void top.BX.Runtime.loadExtension('crm.activity.todo-notification-skip')
				.then((exports) => {
					const skipProvider = new exports.TodoNotificationSkip({
						entityTypeId,
					});
					skipProvider.showCancelPeriodNotification();
				})
			;
		}
	}
}
