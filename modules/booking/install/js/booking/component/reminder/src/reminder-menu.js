import { MenuManager } from 'main.popup';
import type { MenuItem } from 'main.popup';

import { reminderValues } from './reminder-values';
import type { ReminderMenuItem } from './types';

export const ReminderMenu = {
	name: 'ReminderManu',
	props: {
		bindElement: {
			type: HTMLElement,
			required: true,
		},
		shown: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['select'],
	computed: {
		id(): string
		{
			return `booking-reminder-${Math.round(Math.random() * 1_000_000)}`;
		},
		menuItems(): ReminderMenuItem[]
		{
			return reminderValues;
		},
	},
	watch: {
		shown: {
			handler(shown: boolean): void
			{
				if (shown)
				{
					this.reminderMenu.show();
				}
				else
				{
					this.reminderMenu.close();
				}
			},
		},
	},
	created(): void
	{
		this.reminderMenu = MenuManager.create({
			id: this.id,
			bindElement: this.bindElement,
			targetContainer: this.$root.$el.querySelector('.resource-creation-wizard__wrapper'),
			closeByEsc: true,
			autoHide: true,
			zIndex: 3200,
			offsetTop: 0,
			offsetLeft: 9,
			angle: true,
			cacheable: false,
		});

		this.menuItems.forEach((menuItem) => {
			this.reminderMenu.addMenuItem({
				text: menuItem.label,
				dataset: { ...menuItem },
				onclick: (event: PointerEvent, item: MenuItem): void => {
					this.$emit('select', item.dataset);
				},
			});
		});

		this.reminderMenu.show();
	},
	unmounted(): void
	{
		MenuManager.destroy(this.id);
	},
	template: `
		<div></div>
	`,
};
