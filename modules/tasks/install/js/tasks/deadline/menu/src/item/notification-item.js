import { Type, Loc, Extension, Dom, ajax as Ajax } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { MenuItem } from 'main.popup';
import { PULL as Pull } from 'pull.client';
import { Item } from './item';

type SkipPeriodChangedParams = {
	period: string,
};

export class NotificationItem extends Item
{
	MENU_ID: string = 'skip-deadline-notification-menu';
	HINT_ID: string = 'skip-deadline-notification-hint';
	TITLE: string = 'notification';

	#PULL_EVENT: string = 'skip_deadline_notification_period_changed';
	#SUBITEMS_CLASS_NAME: string = 'menu-popup-skip-deadline-item';

	#subItems: MenuItem[];
	#skipPeriod: string;

	constructor()
	{
		super();

		this.#subscribeToPull();
	}

	getSubItems(): MenuItem[]
	{
		const onclick = this.#onMenuItemClick.bind(this);

		return [
			new MenuItem({
				text: Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_DELIMITER'),
				delimiter: true,
				className: this.#SUBITEMS_CLASS_NAME,
			}),
			new MenuItem({
				dataset: { id: `${this.MENU_ID}-always`, analytics: 'daily_remind' },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_DONT_SKIP'),
				value: '',
				className: this.#SUBITEMS_CLASS_NAME,
				onclick,
			}),
			new MenuItem({
				dataset: { id: `${this.MENU_ID}-day`, analytics: 'no_remind_today' },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_SKIP_FOR_DAY'),
				value: 'day',
				className: this.#SUBITEMS_CLASS_NAME,
				onclick,
			}),
			new MenuItem({
				dataset: { id: `${this.MENU_ID}-week`, analytics: 'no_remind_current_week' },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_SKIP_FOR_WEEK'),
				value: 'week',
				className: this.#SUBITEMS_CLASS_NAME,
				onclick,
			}),
			new MenuItem({
				dataset: { id: `${this.MENU_ID}-month`, analytics: 'no_remind_current_month' },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_SKIP_FOR_MONTH'),
				value: 'month',
				className: this.#SUBITEMS_CLASS_NAME,
				onclick,
			}),
			new MenuItem({
				dataset: { id: `${this.MENU_ID}-forever`, analytics: 'never_remind' },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_NOTIFICATION_ITEM_SKIP_FOREVER'),
				value: 'forever',
				className: this.#SUBITEMS_CLASS_NAME,
				onclick,
			}),
		];
	}

	refreshIcons(subItems: MenuItem[], subMenu: Menu): void
	{
		const subItemsFilled = Type.isArrayFilled(subItems) || !Type.isUndefined(this.#subItems);
		if (!subItemsFilled)
		{
			return;
		}

		if (Type.isArrayFilled(subItems))
		{
			this.#subItems = subItems;
		}

		this.#subItems.forEach((item: MenuItem): void => {
			const node = item.getLayout().item;
			if (!Type.isElementNode(node))
			{
				return;
			}

			const currentSkipPeriod = item.value;
			if (currentSkipPeriod === this.skipPeriod)
			{
				Dom.addClass(node, this.ACCEPTED_ITEM_CLASS);

				return;
			}

			Dom.removeClass(node, this.ACCEPTED_ITEM_CLASS);
		});
	}

	#onMenuItemClick(event: BaseEvent, item: MenuItem): void
	{
		const skipPeriod = item.value;
		if (!Type.isString(skipPeriod))
		{
			return;
		}

		if (skipPeriod === this.#skipPeriod)
		{
			return;
		}

		this.#skipPeriod = skipPeriod;

		const subMenu = item.getMenuWindow();
		const subItems = subMenu.getMenuItems();
		this.refreshIcons(subItems, subMenu);

		Ajax.runAction('tasks.deadline.Notification.skip', {
			data: {
				notificationData: {
					skipPeriod,
				},
			},
			analytics: {
				tool: 'tasks',
				category: 'task_operations',
				event: 'click_settings',
				type: 'task',
				c_section: 'tasks',
				c_sub_section: 'deadline_management',
				c_element: item.dataset?.analytics,
			},
		})
			.catch((error): void => this.onPromiseError(error))
		;
	}

	#subscribeToPull(): void
	{
		Pull.subscribe({
			moduleId: 'tasks',
			command: this.#PULL_EVENT,
			callback: this.#onNotificationPeriodChanged.bind(this),
		});
	}

	#onNotificationPeriodChanged(params: SkipPeriodChangedParams): void
	{
		const period = params.period;

		if (!Type.isString(period) || this.#skipPeriod === period)
		{
			return;
		}

		this.#skipPeriod = period;
	}

	get skipPeriod(): string
	{
		if (!Type.isUndefined(this.#skipPeriod))
		{
			return this.#skipPeriod;
		}

		const settings = Extension.getSettings('tasks.deadline.menu');
		this.#skipPeriod = String(settings.get('skipDeadlineNotificationPeriod'));

		return this.#skipPeriod;
	}
}
