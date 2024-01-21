import { TodoNotificationSkipMenu } from 'crm.activity.todo-notification-skip-menu';
import { TodoPingSettingsMenu } from 'crm.activity.todo-ping-settings-menu';
import { Restriction } from 'crm.kanban.restriction';
import { SettingsController, Type as SortType } from 'crm.kanban.sort';
import { ajax as Ajax, Collections, Extension, Loc, Reflection, Text, Type } from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Menu, MenuItem, MenuItemOptions } from 'main.popup';
import { SortController as GridSortController } from './grid/sort-controller';
import { Params } from './params';
import { requireClass, requireClassOrNull, requireStringOrNull } from './params-handling';

const EntityType = Reflection.getClass('BX.CrmEntityType');

const CHECKED_CLASS = 'menu-popup-item-accept';
const NOT_CHECKED_CLASS = 'menu-popup-item-none';

type AISettings = {autostartOperationTypes: number[], autostartTranscriptionOnlyOnFirstCallWithRecording: boolean};

/**
 * @memberOf BX.Crm
 */
export class SettingsButtonExtender
{
	#entityTypeId: number;
	#categoryId: ?number;
	#pingSettings: Object;
	#rootMenu: Menu;
	#targetItemId: ?string;
	#kanbanController: ?SettingsController;
	#restriction: ?Restriction;
	#gridController: ?GridSortController = null;

	#todoSkipMenu: TodoNotificationSkipMenu;
	#todoPingSettingsMenu: TodoPingSettingsMenu;

	#isSetSortRequestRunning: boolean = false;
	#smartActivityNotificationSupported: boolean = false;

	#aiAutostartSettings: null | AISettings = null;
	#isSetAiSettingsRequestRunning: boolean = false;

	#extensionSettings: Collections.SettingsCollection = Extension.getSettings('crm.settings-button-extender');

	constructor(params: Params)
	{
		this.#entityTypeId = Text.toInteger(params.entityTypeId);
		this.#categoryId = Type.isInteger(params.categoryId) ? params.categoryId : null;
		this.#pingSettings = Type.isPlainObject(params.pingSettings) ? params.pingSettings : {};
		this.#smartActivityNotificationSupported = Text.toBoolean(params.smartActivityNotificationSupported);

		if (EntityType && !EntityType.isDefined(this.#entityTypeId))
		{
			throw new Error(`Provided entityTypeId is invalid: ${this.#entityTypeId}`);
		}

		this.#rootMenu = requireClass(params.rootMenu, Menu, 'params.rootMenu');
		this.#targetItemId = requireStringOrNull(params.targetItemId, 'params.targetItemId');

		this.#kanbanController = requireClassOrNull(params.controller, SettingsController, 'params.controller');
		this.#restriction = requireClassOrNull(params.restriction, Restriction, 'params.restriction');

		if (Reflection.getClass('BX.Main.grid') && params.grid)
		{
			this.#gridController = new GridSortController(this.#entityTypeId, params.grid);
		}

		this.#todoSkipMenu = new TodoNotificationSkipMenu({
			entityTypeId: this.#entityTypeId,
			selectedValue: requireStringOrNull(params.todoCreateNotificationSkipPeriod, 'params.todoCreateNotificationSkipPeriod'),
		});

		if (Object.keys(this.#pingSettings).length > 0)
		{
			this.#todoPingSettingsMenu = new TodoPingSettingsMenu({
				entityTypeId: this.#entityTypeId,
				settings: this.#pingSettings,
			});
		}

		const aiSettingsJson = requireStringOrNull(params.aiAutostartSettings, 'params.aiAutostartSettings');
		if (Type.isStringFilled(aiSettingsJson))
		{
			const candidate = JSON.parse(aiSettingsJson);
			if (Type.isPlainObject(candidate))
			{
				this.#aiAutostartSettings = candidate;
			}
		}

		this.#bindEvents();
	}

	#bindEvents(): void
	{
		const createdMenuItemIds = [];

		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'onPopupShow', (event: BaseEvent) => {
			const popup = event.getTarget();
			if (popup.getId() !== this.#rootMenu.getId())
			{
				return;
			}

			const items = this.#getItems();
			if (items.length <= 0)
			{
				return;
			}

			while (createdMenuItemIds.length > 0)
			{
				this.#rootMenu.removeMenuItem(createdMenuItemIds.pop());
			}

			let targetItemId = this.#targetItemId;
			for (const item of items.reverse()) // new item is *prepended* on top of target item, therefore reverse
			{
				const newItem = this.#rootMenu.addMenuItem(
					item,
					targetItemId,
				);

				targetItemId = newItem.getId();
				createdMenuItemIds.push(newItem.getId());
			}
		});
	}

	#getItems(): MenuItemOptions[]
	{
		const items = [];

		const pushCrmSettings = this.#getPushCrmSettings();
		if (pushCrmSettings)
		{
			items.push(pushCrmSettings);
		}

		const coPilotSettings = this.#getCoPilotSettings();
		if (coPilotSettings)
		{
			items.push(coPilotSettings);
		}

		return items;
	}

	#getPushCrmSettings(): ?MenuItemOptions
	{
		const pushCrmItems = [];

		if (this.#shouldShowLastActivitySortToggle())
		{
			pushCrmItems.push(this.#getLastActivitySortToggle());
		}

		if (this.#shouldShowTodoSkipMenu())
		{
			pushCrmItems.push(...this.#todoSkipMenu.getItems());
		}

		if (this.#shouldShowTodoPingSettingsMenu())
		{
			pushCrmItems.push(...this.#todoPingSettingsMenu.getItems());
		}

		if (pushCrmItems.length <= 0)
		{
			return null;
		}

		return {
			text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_PUSH_CRM'),
			items: pushCrmItems,
		};
	}

	#shouldShowLastActivitySortToggle(): boolean
	{
		const shouldShowInKanban = (
			this.#kanbanController?.getCurrentSettings().isTypeSupported(SortType.BY_LAST_ACTIVITY_TIME)
			&& this.#restriction?.isSortTypeChangeAvailable()
		);

		return !!(shouldShowInKanban || this.#gridController?.isLastActivitySortSupported());
	}

	#getLastActivitySortToggle(): MenuItemOptions
	{
		return {
			text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_PUSH_CRM_TOGGLE_SORT'),
			disabled: this.#isSetSortRequestRunning,
			className: this.#isLastActivitySortEnabled() ? CHECKED_CLASS : NOT_CHECKED_CLASS,
			onclick: this.#handleLastActivitySortToggleClick.bind(this),
		};
	}

	#isLastActivitySortEnabled(): boolean
	{
		if (this.#kanbanController)
		{
			return this.#kanbanController.getCurrentSettings().getCurrentType() === SortType.BY_LAST_ACTIVITY_TIME;
		}
		if (this.#gridController)
		{
			return this.#gridController.isLastActivitySortEnabled();
		}

		return false;
	}

	#handleLastActivitySortToggleClick(event: PointerEvent, item: MenuItem): void
	{
		item.getMenuWindow()?.getRootMenuWindow()?.close();
		item.disable();

		if (this.#kanbanController)
		{
			if (this.#isSetSortRequestRunning)
			{
				return;
			}

			this.#isSetSortRequestRunning = true;

			const settings = this.#kanbanController.getCurrentSettings();

			let newSortType: string;
			if (settings.getCurrentType() === SortType.BY_LAST_ACTIVITY_TIME)
			{
				// first different type
				newSortType = settings.getSupportedTypes().find(sortType => sortType !== SortType.BY_LAST_ACTIVITY_TIME);
			}
			else
			{
				newSortType = SortType.BY_LAST_ACTIVITY_TIME;
			}

			this.#kanbanController.setCurrentSortType(newSortType)
				.then(() => {})
				.catch(() => {})
				.finally(() => {
					this.#isSetSortRequestRunning = false;
					item.enable();
				})
			;
		}
		else if (this.#gridController)
		{
			this.#gridController.toggleLastActivitySort();
			item.enable();
		}
		else
		{
			console.error('Can not handle last activity toggle click');
		}
	}

	#shouldShowTodoSkipMenu(): boolean
	{
		return this.#smartActivityNotificationSupported;
	}

	#shouldShowTodoPingSettingsMenu(): boolean
	{
		return this.#todoPingSettingsMenu && this.#shouldShowLastActivitySortToggle();
	}

	#getCoPilotSettings(): ?MenuItemOptions
	{
		if (!Type.isPlainObject(this.#aiAutostartSettings))
		{
			return null;
		}

		const isTranscriptionAutostarted = this.#aiAutostartSettings
			?.autostartOperationTypes
			?.includes(this.#getTranscribeAIOperationType())
		;
		const onlyFirstIncoming = this.#aiAutostartSettings?.autostartTranscriptionOnlyOnFirstCallWithRecording;

		let showInfoHelper = null;
		if (!this.#extensionSettings.get('isAIEnabledInGlobalSettings'))
		{
			showInfoHelper = () => {
				if (Reflection.getClass('BX.UI.InfoHelper.show'))
				{
					BX.UI.InfoHelper.show('limit_copilot_off');
				}
			};
		}

		return {
			text: Loc.getMessage('CRM_COMMON_COPILOT'),
			disabled: this.#isSetAiSettingsRequestRunning,
			items: [
				{
					text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS_PROCESSING_FIRST_INCOMING'),
					className:
						isTranscriptionAutostarted && onlyFirstIncoming
							? CHECKED_CLASS
							: NOT_CHECKED_CLASS,
					onclick: showInfoHelper ?? this.#handleCoPilotMenuItemClick.bind(this, 'firstCall'),
				},
				{
					text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS_PROCESSING_ALL'),
					className:
						isTranscriptionAutostarted && !onlyFirstIncoming
							? CHECKED_CLASS
							: NOT_CHECKED_CLASS,
					onclick: showInfoHelper ?? this.#handleCoPilotMenuItemClick.bind(this, 'allCalls'),
				},
				{
					text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_MANUAL_CALLS_PROCESSING'),
					className: isTranscriptionAutostarted ? NOT_CHECKED_CLASS : CHECKED_CLASS,
					onclick: showInfoHelper ?? this.#handleCoPilotMenuItemClick.bind(this, 'manual'),
				},
			],
		};
	}

	#handleCoPilotMenuItemClick(
		action: 'manual' | 'firstCall' | 'allCalls',
		event: PointerEvent,
		menuItem: MenuItem,
	): void
	{
		menuItem.getMenuWindow()?.getRootMenuWindow()?.close();
		menuItem.getMenuWindow()?.getParentMenuItem()?.disable();

		if (this.#isSetAiSettingsRequestRunning)
		{
			return;
		}

		this.#isSetAiSettingsRequestRunning = true;

		// eslint-disable-next-line default-case
		switch (action)
		{
			case 'manual':
				// autostart all except first step
				this.#aiAutostartSettings.autostartOperationTypes = this.#getAllOperationTypes().filter(
					(typeId) => typeId !== this.#getTranscribeAIOperationType(),
				);

				break;

			case 'firstCall':
				this.#aiAutostartSettings.autostartOperationTypes = this.#getAllOperationTypes();
				this.#aiAutostartSettings.autostartTranscriptionOnlyOnFirstCallWithRecording = true;

				break;

			case 'allCalls':
				this.#aiAutostartSettings.autostartOperationTypes = this.#getAllOperationTypes();
				this.#aiAutostartSettings.autostartTranscriptionOnlyOnFirstCallWithRecording = false;

				break;
		}

		Ajax.runAction(
			'crm.settings.ai.saveAutostartSettings',
			{
				json: {
					entityTypeId: this.#entityTypeId,
					categoryId: this.#categoryId,
					settings: this.#aiAutostartSettings,
				},
			},
		).then(({ data }) => {
			this.#aiAutostartSettings = data.settings;

			menuItem.getMenuWindow()?.getParentMenuItem()?.enable();
			this.#isSetAiSettingsRequestRunning = false;
		}).catch(({ errors }) => {
			console.error('Could not save ai settings', errors);

			// refresh settings, we need to know relevant state
			return Ajax.runAction('crm.settings.ai.getAutostartSettings', {
				json: {
					entityTypeId: this.#entityTypeId,
					categoryId: this.#categoryId,
				},
			});
		}).then(({ data }) => {
			this.#aiAutostartSettings = data.settings;

			menuItem.getMenuWindow()?.getParentMenuItem()?.enable();
			this.#isSetAiSettingsRequestRunning = false;
		}).catch(({ errors }) => {
			console.error('Could not fetch ai settings after error in save', errors);

			menuItem.getMenuWindow()?.getParentMenuItem()?.enable();
			this.#isSetAiSettingsRequestRunning = false;
		});
	}

	#getAllOperationTypes(): number[]
	{
		return this.#extensionSettings.get('allAIOperationTypes').map((id) => Text.toInteger(id));
	}

	#getTranscribeAIOperationType(): number
	{
		return Text.toInteger(this.#extensionSettings.get('transcribeAIOperationType'));
	}
}
