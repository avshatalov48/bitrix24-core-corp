import { Type, Loc, Extension, Dom, ajax as Ajax } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { Menu, MenuItem } from 'main.popup';
import { PULL as Pull } from 'pull.client';
import { Item } from './item';

type DefaultDeadlineChangedParams = {
	deadline: number,
};

export class DefaultItem extends Item
{
	MENU_ID: string = 'default-deadline-menu';
	HINT_ID: string = 'default-deadline-hint';
	TITLE: string = 'default';

	#PULL_EVENT: string = 'default_deadline_changed';
	#SECONDS_IN_DAY: number = 86400;
	#SECONDS_IN_WEEK: number = 7 * this.#SECONDS_IN_DAY;
	#SUBITEMS_CLASS_NAME: string = 'menu-popup-default-deadline-item';

	#subMenu: Menu;
	#subItems: MenuItem[];
	#defaultDeadline: number;
	#isDeadlineNotificationAvailable: boolean;

	constructor(isDeadlineNotificationAvailable: boolean = true)
	{
		super();

		this.#isDeadlineNotificationAvailable = isDeadlineNotificationAvailable;

		this.#subscribeToPull();
	}

	getSubItems(): MenuItem[]
	{
		const onclick = this.#onMenuItemClick.bind(this);

		const subItems = [
			new MenuItem({
				dataset: { id: `${this.MENU_ID}-day`, analytics: 'one_day_button' },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_1_DAY'),
				value: this.#SECONDS_IN_DAY,
				isExactTime: false,
				className: this.#SUBITEMS_CLASS_NAME,
				onclick,
			}),
			new MenuItem({
				dataset: { id: `${this.MENU_ID}-three-days`, analytics: 'three_days_button' },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_3_DAYS'),
				value: 3 * this.#SECONDS_IN_DAY,
				isExactTime: false,
				className: this.#SUBITEMS_CLASS_NAME,
				onclick,
			}),
			new MenuItem({
				dataset: { id: `${this.MENU_ID}-week`, analytics: 'one_week_button' },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_1_WEEK'),
				value: this.#SECONDS_IN_WEEK,
				isExactTime: false,
				className: this.#SUBITEMS_CLASS_NAME,
				onclick,
			}),
			new MenuItem({
				dataset: { id: `${this.MENU_ID}-two-weeks`, analytics: 'two_weeks_button' },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_2_WEEKS'),
				value: 2 * this.#SECONDS_IN_WEEK,
				isExactTime: false,
				className: this.#SUBITEMS_CLASS_NAME,
				onclick,
			}),
			new MenuItem({
				dataset: { id: `${this.MENU_ID}-empty`, analytics: 'no_deadline' },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_EMPTY'),
				value: 0,
				isExactTime: false,
				className: this.#SUBITEMS_CLASS_NAME,
				onclick,
			}),
		];

		if (this.#isDeadlineNotificationAvailable)
		{
			subItems.push(new MenuItem({
				dataset: { id: `${this.MENU_ID}-custom` },
				text: Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_CUSTOM'),
				className: this.#SUBITEMS_CLASS_NAME,
			}));
		}

		return subItems;
	}

	refreshIcons(subItems: MenuItem[], subMenu: Menu): void
	{
		const subItemsFilled = Type.isArrayFilled(subItems) || !Type.isUndefined(this.#subItems);
		const subMenuFilled = !Type.isUndefined(subMenu) || !Type.isUndefined(this.#subMenu);
		if (!subItemsFilled || !subMenuFilled)
		{
			return;
		}

		if (Type.isArrayFilled(subItems))
		{
			this.#subItems = subItems;
		}

		if (!Type.isUndefined(subMenu))
		{
			this.#subMenu = subMenu;
		}

		let isDefaultDeadlineFound = false;
		let customItem = null;

		this.#subItems.forEach((item: MenuItem): void => {
			const node = item.getLayout().item;
			if (!Type.isElementNode(node))
			{
				return;
			}

			const currentDeadline = item.value;

			if (Type.isUndefined(currentDeadline))
			{
				customItem = item;
			}
			else if (currentDeadline === this.defaultDeadline)
			{
				Dom.addClass(node, this.ACCEPTED_ITEM_CLASS);
				isDefaultDeadlineFound = true;

				return;
			}

			Dom.removeClass(node, this.ACCEPTED_ITEM_CLASS);
		});

		if (!Type.isNull(customItem))
		{
			const customItemClass = isDefaultDeadlineFound ? this.DISABLED_ITEM_CLASS : this.ACCEPTED_ITEM_CLASS;

			Dom.addClass(customItem.getLayout().item, customItemClass);

			return;
		}

		if (isDefaultDeadlineFound)
		{
			return;
		}

		customItem = new MenuItem({
			dataset: { id: `${this.MENU_ID}-custom` },
			text: Loc.getMessage('TASKS_DEADLINE_MENU_DEFAULT_ITEM_CUSTOM'),
			className: `${this.#SUBITEMS_CLASS_NAME} ${this.ACCEPTED_ITEM_CLASS}`,
		});
		this.#subMenu.addMenuItem(customItem);
	}

	#onMenuItemClick(event: BaseEvent, item: MenuItem): void
	{
		const deadline = item.value;
		if (!Type.isNumber(deadline))
		{
			return;
		}

		if (deadline === this.#defaultDeadline)
		{
			return;
		}

		this.#defaultDeadline = deadline;

		const subMenu = item.getMenuWindow();
		const subItems = subMenu.getMenuItems();
		this.refreshIcons(subItems, subMenu);

		Ajax.runAction('tasks.deadline.Deadline.setDefault', {
			data: {
				deadlineData: {
					default: deadline,
					isExactTime: item.isExactTime ? 'Y' : 'N',
				},
			},
			analytics: {
				tool: 'tasks',
				category: 'task_operations',
				event: 'click_settings',
				type: 'task',
				c_section: 'tasks',
				c_sub_section: 'default_deadline_selected',
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
			callback: this.#onDefaultDeadlineChanged.bind(this),
		});
	}

	#onDefaultDeadlineChanged(params: DefaultDeadlineChangedParams): void
	{
		const deadline = params.deadline;

		if (!Type.isNumber(deadline) || this.#defaultDeadline === deadline)
		{
			return;
		}

		this.#defaultDeadline = deadline;
	}

	get defaultDeadline(): number
	{
		if (!Type.isUndefined(this.#defaultDeadline))
		{
			return this.#defaultDeadline;
		}

		const settings = Extension.getSettings('tasks.deadline.menu');
		this.#defaultDeadline = Number(settings.get('defaultDeadline'));

		return this.#defaultDeadline;
	}
}
