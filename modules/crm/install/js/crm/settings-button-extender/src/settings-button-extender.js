import { TodoNotificationSkipMenu } from 'crm.activity.todo-notification-skip-menu';
import { TodoPingSettingsMenu } from 'crm.activity.todo-ping-settings-menu';
import { Restriction } from 'crm.kanban.restriction';
import { SettingsController, Type as SortType } from 'crm.kanban.sort';
import {
	ajax as Ajax,
	type Collections,
	Extension,
	Loc,
	Reflection,
	Text,
	Type,
	userOptions as UserOptions,
} from 'main.core';
import { BaseEvent, EventEmitter } from 'main.core.events';
import { Menu, MenuItem, MenuItemOptions } from 'main.popup';
import { Dialog } from 'ui.entity-selector';
import { SortController as GridSortController } from './grid/sort-controller';
import {
	requireClass,
	requireClassOrNull,
	requireStringOrNull,
	requireArrayOfString,
} from './params-handling';

const EntityType = Reflection.getClass('BX.CrmEntityType');

const CHECKED_CLASS = 'menu-popup-item-accept';
const NOT_CHECKED_CLASS = 'menu-popup-item-none';

const COPILOT_LANGUAGE_ID_SAVE_REQUEST_DELAY = 750;
const COPILOT_LANGUAGE_SELECTOR_POPUP_WIDTH = 300;

const AUTOSTART_CALL_DIRECTION_INCOMING = 1;
const AUTOSTART_CALL_DIRECTION_OUTGOING = 2;

type AISettings = {
	autostartOperationTypes: number[],
	autostartTranscriptionOnlyOnFirstCallWithRecording: boolean,
	autostartCallDirections: number[],
};

export type SettingsButtonExtenderParams = {
	entityTypeId: number,
	categoryId: ?number,
	aiAutostartSettings: ?string, // json
	aiCopilotLanguageId: ?string,
	pingSettings: Object,
	rootMenu: Menu,
	todoCreateNotificationSkipPeriod: ?string,
	targetItemId: ?string,
	expandsBehindThan: Array<string>;
	controller: ?SettingsController,
	restriction: ?Restriction,
	grid: ?BX.Main.grid,
};

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
	#expandsBehindThan: Array<string>;
	#kanbanController: ?SettingsController;
	#restriction: ?Restriction;
	#gridController: ?GridSortController = null;

	#todoSkipMenu: TodoNotificationSkipMenu;
	#todoPingSettingsMenu: TodoPingSettingsMenu;

	#isSetSortRequestRunning: boolean = false;
	#smartActivityNotificationSupported: boolean = false;

	#aiAutostartSettings: null | AISettings = null;
	#aiCopilotLanguageId: null | string = null;
	#isSetAiSettingsRequestRunning: boolean = false;

	#extensionSettings: Collections.SettingsCollection = Extension.getSettings('crm.settings-button-extender');

	constructor(params: SettingsButtonExtenderParams)
	{
		this.#entityTypeId = Text.toInteger(params.entityTypeId);
		this.#categoryId = Type.isInteger(params.categoryId) ? params.categoryId : null;
		this.#pingSettings = Type.isPlainObject(params.pingSettings) ? params.pingSettings : {};
		this.#expandsBehindThan = requireArrayOfString(params.expandsBehindThan ?? [], 'params.expandsBehindThan');
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

		this.#aiCopilotLanguageId = params.aiCopilotLanguageId;

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

			let targetItemId = this.#resolveEarlyTargetId();
			for (const item of items.reverse()) // new item is *prepended* on top of target item, therefore reverse
			{
				const newItem = this.#rootMenu.addMenuItem(
					item,
					targetItemId,
				);

				if (newItem)
				{
					targetItemId = newItem.getId();
					createdMenuItemIds.push(newItem.getId());
				}
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

	#resolveEarlyTargetId(): ?string
	{
		const items = this.#rootMenu.getMenuItems();
		const earlyItem = items.find((item: MenuItem) => this.#expandsBehindThan.includes(item.getId()));

		return earlyItem?.getId() ?? this.#targetItemId;
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

			// eslint-disable-next-line init-declarations
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

	#closeMenuWindow(event: PointerEvent, item: MenuItem): void
	{
		item.getMenuWindow()?.close();
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
		const showInfoHelper = this.#getInfoHelper();
		const menuItems = [];
		if (Type.isPlainObject(this.#aiAutostartSettings))
		{
			const autoCallItems = [];
			const isTranscriptionAutoStarted = this.#aiAutostartSettings
				?.autostartOperationTypes
				?.includes(this.#getTranscribeAIOperationType())
			;
			const isOnlyFirst = this.#aiAutostartSettings?.autostartTranscriptionOnlyOnFirstCallWithRecording;
			const isOnlyIncoming = this.#aiAutostartSettings?.autostartCallDirections?.length === 1
				&& this.#aiAutostartSettings?.autostartCallDirections?.includes(AUTOSTART_CALL_DIRECTION_INCOMING)
			;
			const isOnlyOutgoing = this.#aiAutostartSettings?.autostartCallDirections?.length === 1
				&& this.#aiAutostartSettings?.autostartCallDirections?.includes(AUTOSTART_CALL_DIRECTION_OUTGOING)
			;
			const isAIHasPackages = this.#extensionSettings.get('isAIHasPackages');

			autoCallItems.push({
				text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS_PROCESSING_FIRST_INCOMING_MSGVER_1'),
				className: isTranscriptionAutoStarted && isAIHasPackages && isOnlyFirst && isOnlyIncoming
					? CHECKED_CLASS
					: NOT_CHECKED_CLASS,
				onclick: showInfoHelper ?? this.#handleCoPilotMenuItemClick.bind(this, 'firstCall'),
			}, {
				text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS_PROCESSING_INCOMING'),
				className: isTranscriptionAutoStarted && isAIHasPackages && isOnlyIncoming && !isOnlyFirst
					? CHECKED_CLASS
					: NOT_CHECKED_CLASS,
				onclick: showInfoHelper ?? this.#handleCoPilotMenuItemClick.bind(this, 'allCalls'), // all incoming
			}, {
				text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS_PROCESSING_OUTGOING'),
				className: isTranscriptionAutoStarted && isAIHasPackages && isOnlyOutgoing && !isOnlyFirst
					? CHECKED_CLASS
					: NOT_CHECKED_CLASS,
				onclick: showInfoHelper ?? this.#handleCoPilotMenuItemClick.bind(this, 'outgoingCalls'),
			}, {
				text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS_PROCESSING_ALL_MSGVER_1'),
				className: isTranscriptionAutoStarted && isAIHasPackages && !isOnlyIncoming && !isOnlyOutgoing && !isOnlyFirst
					? CHECKED_CLASS
					: NOT_CHECKED_CLASS,
				onclick: showInfoHelper ?? this.#handleCoPilotMenuItemClick.bind(this, 'allIncomingOutgoingCalls'),
			}, {
				text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_MANUAL_CALLS_PROCESSING_MSGVER_1'),
				className: isTranscriptionAutoStarted && isAIHasPackages
					? NOT_CHECKED_CLASS
					: CHECKED_CLASS,
				onclick: showInfoHelper ?? this.#handleCoPilotMenuItemClick.bind(this, 'manual'),
			});

			menuItems.push({
				text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_AUTO_CALLS'),
				disabled: this.#isSetAiSettingsRequestRunning,
				items: autoCallItems,
			});
		}

		if (Type.isStringFilled(this.#aiCopilotLanguageId))
		{
			menuItems.push({
				text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_LANGUAGE_MSGVER_1'),
				onclick: this.#getInfoHelper(true) ?? this.#handleCoPilotLanguageSelect.bind(this),
			});
		}

		if (menuItems.length === 0)
		{
			return null;
		}

		return {
			text: Loc.getMessage('CRM_SETTINGS_BUTTON_EXTENDER_COPILOT_IN_CALLS'),
			disabled: this.#isSetAiSettingsRequestRunning,
			items: menuItems,
		};
	}

	#handleCoPilotMenuItemClick(
		action: 'firstCall' | 'allCalls' | 'outgoingCalls' | 'allIncomingOutgoingCalls' | 'manual',
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
				this.#aiAutostartSettings.autostartCallDirections = [AUTOSTART_CALL_DIRECTION_INCOMING];

				break;

			case 'allCalls': // all incoming
				this.#aiAutostartSettings.autostartOperationTypes = this.#getAllOperationTypes();
				this.#aiAutostartSettings.autostartTranscriptionOnlyOnFirstCallWithRecording = false;
				this.#aiAutostartSettings.autostartCallDirections = [AUTOSTART_CALL_DIRECTION_INCOMING];

				break;

			case 'outgoingCalls':
				this.#aiAutostartSettings.autostartOperationTypes = this.#getAllOperationTypes();
				this.#aiAutostartSettings.autostartTranscriptionOnlyOnFirstCallWithRecording = false;
				this.#aiAutostartSettings.autostartCallDirections = [AUTOSTART_CALL_DIRECTION_OUTGOING];

				break;

			case 'allIncomingOutgoingCalls':
				this.#aiAutostartSettings.autostartOperationTypes = this.#getAllOperationTypes();
				this.#aiAutostartSettings.autostartTranscriptionOnlyOnFirstCallWithRecording = false;
				this.#aiAutostartSettings.autostartCallDirections = [
					AUTOSTART_CALL_DIRECTION_INCOMING,
					AUTOSTART_CALL_DIRECTION_OUTGOING,
				];

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

	#handleCoPilotLanguageSelect(event: PointerEvent): void
	{
		const languageSelector = new Dialog({
			targetNode: event.target,
			multiple: false,
			showAvatars: false,
			dropdownMode: true,
			compactView: true,
			enableSearch: true,
			context: `COPILOT-LANGUAGE-SELECTOR-${this.#entityTypeId}-${this.#categoryId}`,
			width: COPILOT_LANGUAGE_SELECTOR_POPUP_WIDTH,
			tagSelectorOptions: {
				textBoxWidth: '100%',
			},
			preselectedItems: [
				['copilot_language', this.#aiCopilotLanguageId],
			],
			entities: [{
				id: 'copilot_language',
				options: {
					entityTypeId: this.#entityTypeId,
					categoryId: this.#categoryId,
				},
			}],
			events: {
				'Item:onSelect': (selectEvent: BaseEvent): void => {
					const item = selectEvent.getData().item;
					const languageId = item.id.toLowerCase();
					if (!Type.isStringFilled(languageId))
					{
						throw new Error('Language ID is not defined');
					}

					setTimeout(() => {
						let optionName = `ai_config_${this.#entityTypeId}`;
						if (Type.isInteger(this.#categoryId))
						{
							optionName += `_${this.#categoryId}`;
						}

						UserOptions.save('crm', optionName, 'languageId', languageId);

						this.#aiCopilotLanguageId = languageId;
					}, COPILOT_LANGUAGE_ID_SAVE_REQUEST_DELAY);
				},
			},
		});

		languageSelector.show();
	}

	#getAllOperationTypes(): number[]
	{
		return this.#extensionSettings.get('allAIOperationTypes').map((id) => Text.toInteger(id));
	}

	#getTranscribeAIOperationType(): number
	{
		return Text.toInteger(this.#extensionSettings.get('transcribeAIOperationType'));
	}

	#getInfoHelper(skipPackagesCheck: boolean = false): ?Function
	{
		if (skipPackagesCheck)
		{
			if (this.#extensionSettings.get('isAIEnabledInGlobalSettings'))
			{
				return null;
			}

			return (): void => {
				if (Reflection.getClass('BX.UI.InfoHelper.show'))
				{
					BX.UI.InfoHelper.show(this.#extensionSettings.get('aiDisabledSliderCode'));
				}
			};
		}

		if (
			this.#extensionSettings.get('isAIEnabledInGlobalSettings')
			&& this.#extensionSettings.get('isAIHasPackages')
		)
		{
			return null;
		}

		return (): void => {
			if (Reflection.getClass('BX.UI.InfoHelper.show'))
			{
				if (!this.#extensionSettings.get('isAIEnabledInGlobalSettings'))
				{
					BX.UI.InfoHelper.show(this.#extensionSettings.get('aiDisabledSliderCode'));
				}
				else if (!this.#extensionSettings.get('isAIHasPackages'))
				{
					BX.UI.InfoHelper.show(this.#extensionSettings.get('aiPackagesEmptySliderCode'));
				}
			}
		};
	}
}
