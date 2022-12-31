import {MenuItem} from 'main.popup';
import {Loc} from "main.core";
import {TodoNotificationSkip}  from 'crm.activity.todo-notification-skip';

declare type TodoNotificationSkipMenuParams = {
	selectedValue: ?string,
	entityTypeId: number,
}

export class TodoNotificationSkipMenu
{
	#selectedMenuItemId: ?string = null;
	#entityTypeId: number = null;
	#skipProvider: TodoNotificationSkip = null;

	constructor(params: TodoNotificationSkipMenuParams)
	{
		this.#entityTypeId = params.entityTypeId;
		if (params.selectedValue)
		{
			this.#selectedMenuItemId = params.selectedValue;
		}
		this.#skipProvider = new TodoNotificationSkip({
			entityTypeId: this.#entityTypeId,
			onSkippedPeriodChange: this.#onSkippedPeriodChange.bind(this),
		});
	}

	setSelectedValue(value)
	{
		this.#selectedMenuItemId = value;
	}

	#onSkippedPeriodChange(period: string): void
	{
		this.#selectedMenuItemId = period;
	}

	getItems(): Array
	{
		const items = [];
		items.push({
			id: 'askForCreateTodo',
			text: this.#getMenuItemText(),
			className: 'menu-popup-item-none',
			items: this.#getSkipPeriodsMenuItems()
		});

		return items;
	}

	#getMenuItemText(): string
	{
		let messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_MENU_ITEM';
		switch (this.#entityTypeId)
		{
			case BX.CrmEntityType.enumeration.lead:
				messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_MENU_ITEM_LEAD';
				break;
			case BX.CrmEntityType.enumeration.deal:
				messagePhrase = 'CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_MENU_ITEM_DEAL';
				break;
		}

		return Loc.getMessage(messagePhrase);
	}

	#getSkipPeriodsMenuItems(): Array
	{
		const activeClass = 'menu-popup-item-accept';
		const inactiveClass = 'menu-popup-item-none';
		const items = [];
		items.push({
			id: 'activate',
			text:  Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_ACTIVATE'),
			className: this.#selectedMenuItemId ? inactiveClass : activeClass,
			disabled: this.#isLoading(),
			onclick: this.#onSkipMenuItemSelect.bind(this, ''),
		});
		items.push({
			id: 'day',
			text:  Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_PERIOD_DAY'),
			className: this.#selectedMenuItemId === 'day' ? activeClass : inactiveClass,
			disabled: this.#isLoading(),
			onclick: this.#onSkipMenuItemSelect.bind(this, 'day'),
		});
		items.push({
			id: 'week',
			text:  Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_PERIOD_WEEK'),
			className: this.#selectedMenuItemId === 'week' ? activeClass : inactiveClass,
			disabled: this.#isLoading(),
			onclick: this.#onSkipMenuItemSelect.bind(this, 'week'),
		});
		items.push({
			id: 'month',
			text:  Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_PERIOD_MONTH'),
			className: this.#selectedMenuItemId === 'month' ? activeClass : inactiveClass,
			disabled: this.#isLoading(),
			onclick: this.#onSkipMenuItemSelect.bind(this, 'month'),
		});
		items.push({
			id: 'forever',
			text:  Loc.getMessage('CRM_ACTIVITY_TODO_NOTIFICATION_SKIP_PERIOD_FOREVER'),
			className: this.#selectedMenuItemId === 'forever' ? activeClass : inactiveClass,
			disabled: this.#isLoading(),
			onclick: this.#onSkipMenuItemSelect.bind(this, 'forever'),
		});

		return items;
	}

	#isLoading(): boolean
	{
		return this.#selectedMenuItemId === 'loading';
	}

	#onSkipMenuItemSelect(period: string, event: PointerEvent, item: MenuItem): void
	{
		item.getMenuWindow()?.getRootMenuWindow()?.close();

		this.#selectedMenuItemId = 'loading';
		this.#skipProvider.saveSkippedPeriod(period).then(() => {
			this.#selectedMenuItemId = period;
		});
	}
}
