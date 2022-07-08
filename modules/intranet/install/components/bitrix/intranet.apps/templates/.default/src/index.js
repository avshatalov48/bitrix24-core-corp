import {Type, Loc, Dom, ajax} from 'main.core';
import {PopupManager} from 'main.popup';

import {CreateButton} from 'ui.buttons';

class AppsWidget
{
	constructor(params)
	{
		this.popup = null;
		this.personalMobile = (Type.isStringFilled(params.personalMobile) ? params.personalMobile : '');

		this.initAppsInstall();
	}

	initAppsInstall()
	{
		const androidIcon = document.querySelector("[data-role='profile-android-app']");
		if (Type.isDomNode(androidIcon))
		{
			androidIcon.addEventListener('click', () => {
				this.showSmsPopup(this.personalMobile);
			});
		}

		const iosIcon = document.querySelector("[data-role='profile-ios-app']");
		if (Type.isDomNode(iosIcon))
		{
			iosIcon.addEventListener('click', () => {
				this.showSmsPopup(this.personalMobile);
			});
		}
	}

	showSmsPopup(personalMobile)
	{
		this.popup = PopupManager.create({
			id: 'intranet-apps-widget-sms-popup',
			className: 'intranet-apps-widget-popup',
			titleBar: Loc.getMessage('INTRANET_APPS_WIDGET_INSTALL'),
			cacheable: false,
			maxWidth: 450,
			contentColor: 'white',
			content:
				Dom.create('div', {
					children: [
						Dom.create('div', {
							props: {
								className: 'intranet-apps-widget-popup-title',
							},
							html: Loc.getMessage('INTRANET_APPS_WIDGET_PHONE'),
						}),
						Dom.create('div', {
							props: {
								className: 'ui-ctl ui-ctl-textbox ui-ctl-wa',
							},
							children: [
								Dom.create('input', {
									props: {
										value: personalMobile,
										className: 'ui-ctl-element',
										type: 'text',
									},
									events: {
										input: (event) => {
											personalMobile = event.target.value;
										}
									}
								})
							]
						}),
						Dom.create('div', {
							props: {
								className: 'intranet-apps-widget-popup-text',
							},
							html: Loc.getMessage('INTRANET_APPS_WIDGET_INSTALL_TEXT'),
						})
					]
				}),
				closeIcon: true,
				contentPadding: 10,
				buttons: [
					new CreateButton({
						text: Loc.getMessage('INTRANET_APPS_WIDGET_SEND'),
						className: 'ui-btn-primary',
						events: {
							click: (button) => {
								button.setWaiting();
								const popup = button.context;

								ajax.runAction('intranet.controller.sms.sendsmsforapp', {
									data: {
										phone: personalMobile,
									}
								}).then(() => {
									popup.close();
								}, () => {
									popup.close();
								});
							}
						}
					})
				],
			}
		);

		this.popup.show()
	}
}

export {
	AppsWidget,
}
