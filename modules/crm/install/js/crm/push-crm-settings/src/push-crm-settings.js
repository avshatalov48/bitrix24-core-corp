import { Menu, MenuItem, MenuItemOptions } from "main.popup";
import { SettingsController, Type as SortType } from "crm.kanban.sort";
import { Restriction } from "crm.kanban.restriction";
import { BaseEvent, EventEmitter } from "main.core.events";
import { Loc, Reflection, Text } from "main.core";
import { TodoNotificationSkipMenu } from "crm.activity.todo-notification-skip-menu";
import { Params } from "./params";
import { requireClass, requireClassOrNull, requireStringOrNull } from "./params-handling";
import { SortController as GridSortController } from "./grid/sort-controller";

const EntityType = Reflection.getClass('BX.CrmEntityType');

const CHECKED_CLASS = 'menu-popup-item-accept';
const NOT_CHECKED_CLASS = 'menu-popup-item-none';

/**
 * @memberOf BX.Crm
 */
export class PushCrmSettings
{
	#entityTypeId: number;
	#rootMenu: Menu;
	#targetItemId: ?string;
	#kanbanController: ?SettingsController;
	#restriction: ?Restriction;
	#gridController: ?GridSortController = null;

	#todoSkipMenu: TodoNotificationSkipMenu;

	#isSetSortRequestRunning: boolean = false;
	#smartActivityNotificationSupported: boolean = false;

	constructor(params: Params)
	{
		this.#entityTypeId = Text.toInteger(params.entityTypeId);
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

		this.#bindEvents();
	}

	#bindEvents(): void
	{
		const onPopupShowHandler = (event: BaseEvent) => {
			const popup = event.getTarget();
			if (popup.getId() !== this.#rootMenu.getId())
			{
				return;
			}

			// process this event with the intended target only once
			EventEmitter.unsubscribe(EventEmitter.GLOBAL_TARGET, 'onPopupShow', onPopupShowHandler);

			if (!this.#shouldShowPushCrmSettings())
			{
				return;
			}

			const item: MenuItem = this.#rootMenu.addMenuItem(
				{
					text: Loc.getMessage('CRM_PUSH_CRM_SETTINGS_MENU_ITEM_TEXT'),
					// if we provide no items, submenu will not be created. and onShow will never be emitted.
					items: [
						{
							id: 'stub',
						}
					],
				},
				this.#targetItemId,
			);

			item.subscribe('SubMenu:onShow', (event) => {
				const target: MenuItem = event.getTarget();

				for (const itemOptionsToAdd of this.#getItems())
				{
					target.getSubMenu()?.addMenuItem(itemOptionsToAdd);
				}

				target.getSubMenu()?.removeMenuItem('stub');
			});
		};

		EventEmitter.subscribe(EventEmitter.GLOBAL_TARGET, 'onPopupShow', onPopupShowHandler);
	}

	#shouldShowPushCrmSettings(): boolean
	{
		return this.#getItems().length > 0;
	}

	#getItems(): MenuItemOptions[]
	{
		const items = [];

		if (this.#shouldShowLastActivitySortToggle())
		{
			items.push(this.#getLastActivitySortToggle());
		}

		if (this.#shouldShowTodoSkipMenu())
		{
			items.push(...this.#todoSkipMenu.getItems());
		}

		return items;
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
			text: Loc.getMessage('CRM_PUSH_CRM_SETTINGS_SORT_TOGGLE_TEXT'),
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
}
