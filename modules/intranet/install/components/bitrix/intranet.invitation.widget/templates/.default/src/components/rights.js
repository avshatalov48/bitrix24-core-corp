import {Menu} from "main.popup";
import {Vue} from "ui.vue";
import {Event} from "main.core";

export const RightsComponent = {
	props: [
		"isCrurrentUserAdmin",
	],
	computed: {
		localize(state)
		{
			return Vue.getFilteredPhrases('INTRANET_INVITATION_WIDGET_');
		}
	},
	methods: {
		showPopup(e)
		{
			Event.EventEmitter.emit('BX.Intranet.InvitationWidget:stopPopupMouseOut');

			this.getInvitationRightSetting().then((type) => {
				let menuItems = [
					{
						text: this.localize.INTRANET_INVITATION_WIDGET_SETTING_ALL_INVITE,
						className: type === 'all' ? 'menu-popup-item-accept' : '',
						onclick: () => {
							this.saveInvitationRightSetting('all');
							this.popupMenu.close();
						}
					},
					{
						text: this.localize.INTRANET_INVITATION_WIDGET_SETTING_ADMIN_INVITE,
						className: type === 'admin' ? 'menu-popup-item-accept' : '',
						onclick: () => {
							this.saveInvitationRightSetting('admin');
							this.popupMenu.close();
						}
					}
				];

				this.popupMenu = new Menu({
					bindElement: e.target,
					items: menuItems,
					offsetLeft: 10,
					offsetTop: 0,
					angle: true,
					className: "license-right-popup-menu"
				});

				this.popupMenu.show();
			});
		},
		saveInvitationRightSetting(type)
		{
			BX.ajax.runAction("intranet.invitationwidget.saveInvitationRight", {
				data: {
					type: type
				}
			});
		},
		getInvitationRightSetting()
		{
			return new Promise((resolve, reject) => {
				BX.ajax.runAction("intranet.invitationwidget.getInvitationRight", {
					data: {},
				}).then((response) => {
					resolve(response.data);
				});
			});
		}
	},
	template: `
		<div class="license-widget-item-menu" @click="showPopup"></div>
	`,
};