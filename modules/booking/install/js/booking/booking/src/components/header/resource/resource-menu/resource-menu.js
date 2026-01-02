import { Event } from 'main.core';
import { MenuManager, Menu } from 'main.popup';
import { mapGetters } from 'ui.vue3.vuex';
import 'ui.hint';

import { Model } from 'booking.const';
import { limit } from 'booking.lib.limit';
import { hideResources } from 'booking.lib.resources';
import { ResourceCreationWizard } from 'booking.resource-creation-wizard';
import { RemoveResource } from 'booking.lib.remove-resource';

import './resource-menu.css';

// @vue/component
export const ResourceMenu = {
	name: 'ResourceMenu',
	props: {
		resourceId: {
			type: Number,
			required: true,
		},
	},
	data(): { menuPopup: Menu | null }
	{
		return {
			menuPopup: null,
		};
	},
	computed: {
		...mapGetters({
			favoritesIds: `${Model.Favorites}/get`,
			isEditingBookingMode: `${Model.Interface}/isEditingBookingMode`,
			isFeatureEnabled: `${Model.Interface}/isFeatureEnabled`,
			isFilterMode: `${Model.Filter}/isFilterMode`,
		}),
		popupId(): string
		{
			return `resource-menu-${this.resourceId || 'new'}`;
		},
	},
	created(): void
	{
		this.hint = BX.UI.Hint.createInstance({
			popupParameters: {},
		});
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

			if (this.isFilterMode)
			{
				return;
			}

			const menuButton = this.$refs['menu-button'];
			this.menuPopup = MenuManager.create(
				this.popupId,
				menuButton,
				this.getMenuItems(),
				{
					className: 'booking-resource-menu-popup',
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
		getMenuItems(): Array
		{
			return [
				// {
				// 	html: `<span>${this.loc('BOOKING_RESOURCE_MENU_ADD_BOOKING')}</span>`,
				// 	onclick: () => this.destroy(),
				// },
				{
					html: `<span>${this.loc('BOOKING_RESOURCE_MENU_EDIT_RESOURCE')}</span>`,
					className: (
						this.isFeatureEnabled
							? 'menu-popup-item menu-popup-no-icon'
							: 'menu-popup-item --lock'
					),
					onclick: async () => {
						if (!this.isFeatureEnabled)
						{
							limit.show();

							return;
						}
						const wizard = new ResourceCreationWizard();
						this.editResource(this.resourceId, wizard);
						this.destroy();
					},
				},
				// {
				// 	html: `<span>${this.loc('BOOKING_RESOURCE_MENU_EDIT_NOTIFY')}</span>`,
				// 	onclick: () => this.destroy(),
				// },
				// {
				// 	html: `<span>${this.loc('BOOKING_RESOURCE_MENU_CREATE_COPY')}</span>`,
				// 	onclick: () => this.destroy(),
				// },
				// {
				// 	html: '<span></span>',
				// 	disabled: true,
				// 	className: 'menu-item-divider',
				// },
				{
					html: `<span>${this.loc('BOOKING_RESOURCE_MENU_HIDE')}</span>`,
					onclick: async () => {
						this.destroy();
						await this.hideResource(this.resourceId);
					},
				},
				{
					html: `<span class="alert-text">${this.loc('BOOKING_RESOURCE_MENU_DELETE')}</span>`,
					onclick: async () => {
						this.destroy();
						await this.removeResource(this.resourceId);
					},
				},
			];
		},
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
		async editResource(resourceId: number, wizard: ResourceCreationWizard): void
		{
			wizard.open(resourceId);
		},
		async removeResource(resourceId: number): Promise<void>
		{
			await (new RemoveResource(resourceId)).run();
		},
		async hideResource(resourceId: number): Promise<void>
		{
			const ids = [...this.favoritesIds];
			const index = this.favoritesIds.indexOf(resourceId);
			if (index === -1)
			{
				return;
			}

			ids.splice(index, 1);

			await hideResources(ids);
		},
	},
	template: `
		<button ref="menu-button" class="ui-icon-set --more" @click="openMenu"></button>
	`,
};
