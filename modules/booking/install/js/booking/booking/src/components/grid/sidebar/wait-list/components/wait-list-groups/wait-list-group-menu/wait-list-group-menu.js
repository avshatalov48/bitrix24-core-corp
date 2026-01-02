// @vue/component

import { Event, Text } from 'main.core';
import { MenuManager, Menu } from 'main.popup';

import { useDeleteWaitListGroup } from './composables/use-delete-wait-list-group';

export const WaitListGroupMenu = {
	name: 'WaitListGroupMenu',
	props: {
		waitListGroup: {
			type: Object,
			required: true,
		},
	},
	setup(): { menuPopup: Menu | null, deleteWaitListGroup: (number[]) => Promise<void> }
	{
		const menuPopup = null;
		const { deleteWaitListGroup } = useDeleteWaitListGroup();

		return {
			menuPopup,
			deleteWaitListGroup,
		};
	},
	computed: {
		popupId(): string
		{
			return `wait-list-group-menu-${Text.getRandom(4)}`;
		},
	},
	unmounted(): void
	{
		if (this.menuPopup)
		{
			this.destroy();
		}
	},
	methods: {
		openMenu(): void
		{
			if (this.menuPopup?.popupWindow?.isShown())
			{
				this.destroy();

				return;
			}

			const menuButton = this.$refs['menu-button'];
			this.menuPopup = MenuManager.create(
				this.popupId,
				menuButton,
				this.getMenuItems(),
				{
					className: 'booking--wait-list--wait-list-group-menu-popup',
					closeByEsc: true,
					autoHide: true,
					offsetTop: -3,
					offsetLeft: menuButton.offsetWidth - 6,
					angle: true,
					cacheable: true,
					events: {
						onDestroy: () => this.unbindScrollEvent(),
					},
				},
			);
			this.menuPopup.show();
			this.bindScrollEvent();
		},
		getMenuItems(): Array<Object>
		{
			return [
				{
					html: `<span>${this.loc('BOOKING_BOOKING_WAIT_LIST_GROUP_DELETE')}</span>`,
					onclick: async () => {
						this.destroy();
						await this.deleteWaitListGroup(
							this.waitListGroup.items.map(({ id }) => id),
						);
					},
				},
			];
		},
		// async deleteWaitListGroup(): Promise<void>
		// {
		// 	const waitListItemsIds = this.waitListGroup.items.map(({ id }) => id);
		// 	await waitListService.deleteList(waitListItemsIds);
		// },
		destroy(): void
		{
			MenuManager.destroy(this.popupId);
			this.unbindScrollEvent();
		},
		bindScrollEvent(): void
		{
			Event.bind(document, 'scroll', this.adjustPosition, { capture: true });
		},
		unbindScrollEvent(): void
		{
			Event.unbind(document, 'scroll', this.adjustPosition, { capture: true });
		},
		adjustPosition(): void
		{
			this.menuPopup?.popupWindow?.adjustPosition();
		},
	},
	template: `
		<button ref="menu-button" class="ui-icon-set --more" @click="openMenu"></button>
	`,
};
