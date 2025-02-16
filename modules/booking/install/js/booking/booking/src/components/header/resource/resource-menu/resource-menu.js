import { limit } from 'booking.lib.limit';
import { mapGetters } from 'ui.vue3.vuex';
import { Event, Loc } from 'main.core';
import { MenuManager, Menu, Popup } from 'main.popup';
import { MessageBox, MessageBoxButtons } from 'ui.dialogs.messagebox';
import 'ui.hint';
import { Model } from 'booking.const';
import { hideResources } from 'booking.lib.resources';
import { ResourceCreationWizard } from 'booking.resource-creation-wizard';
import { resourceService } from 'booking.provider.service.resources-service';

import './resource-menu.css';

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
						await this.deleteResource(this.resourceId);
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
		async deleteResource(resourceId: number): Promise<void>
		{
			const confirmed = await this.confirmDelete(resourceId);

			if (confirmed)
			{
				await resourceService.delete(resourceId);
			}
		},
		async confirmDelete(resourceId: number): Promise<boolean>
		{
			const disabled = await resourceService.hasBookings(resourceId);

			return new Promise((resolve) => {
				const messageBox = MessageBox.create({
					message: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_DELETE'),
					yesCaption: Loc.getMessage('BOOKING_RESOURCE_CONFIRM_DELETE_YES'),
					modal: true,
					buttons: MessageBoxButtons.YES_CANCEL,
					onYes: () => {
						messageBox.close();
						resolve(true);
					},
					onCancel: () => {
						messageBox.close();
						resolve(false);
					},
				});

				if (disabled)
				{
					const popup: Popup = messageBox.getPopupWindow();
					popup.subscribe('onAfterShow', () => {
						const yesButton = messageBox.getYesButton();
						yesButton.setDisabled(true);

						Event.bind(yesButton.getContainer(), 'mouseenter', () => {
							this.hint.show(
								yesButton.getContainer(),
								Loc.getMessage('BOOKING_RESOURCE_CONFIRM_DELETE_HINT'),
								true,
							);
						});

						Event.bind(yesButton.getContainer(), 'mouseleave', () => {
							this.hint.hide(yesButton.getContainer());
						});
					});
				}

				messageBox.show();
			});
		},
	},
	template: `
		<button ref="menu-button" class="ui-icon-set --more" @click="openMenu"></button>
	`,
};
