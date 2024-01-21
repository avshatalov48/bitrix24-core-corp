import { Loc, Tag, Text, Event, ajax as Ajax } from 'main.core';
import { Popup } from 'main.popup';
import { Button, ButtonIcon, ButtonState } from 'ui.buttons';
import { App } from '../../app';

type MobileAppInstallPopupOptions = {
	sendersConfig: Array,
	phoneNumbers: Array,
	userId: number,
	root: App,
}

class MobileAppInstallPopup
{
	constructor(options: MobileAppInstallPopupOptions)
	{
		this.initSenders(options.sendersConfig);
		this.phoneNumbers = options.phoneNumbers;
		this.userId = options.userId;
		this.root = options.root;

		this.selectedPhoneNumber = this.phoneNumbers?.[0] ?? null;
		this.selectedSender = this.senders?.[0] ?? null;

		/** @type Popup  */
		this.mainPopup = null;
		/** @type Button */
		this.sendButton = null;
		this.sendersMenu = null;
		this.phoneMenu = null;
	}

	initSenders(sendersData)
	{
		this.sendersConfig = sendersData.find((item) => item.code === 'sms_provider');
		this.senders = this.sendersConfig?.smsSenders;
	}

	render()
	{
		const popupContent = this.getPopupContent();

		const linkButton = new Button({
			id: 'copy-install-link',
			color: Button.Color.LINK,
			round: true,
			icon: ButtonIcon.COPY,
			text: Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_BTN_LINK'),
		});

		this.sendButton = new Button({
			state: (this.selectedPhoneNumber && this.selectedSender) ? null : ButtonState.DISABLED,
			color: Button.Color.PRIMARY,
			round: true,
			text: Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_BTN_SEND'),
			onclick: () => {
				this.sendButton.setClocking();
				this.sendSms();
			},
		});

		this.mainPopup = new Popup({
			className: 'salescenter-popup-mobile',
			overlay: true,
			content: popupContent,
			closeIcon: true,
			maxWidth: 666,
			buttons: [
				this.sendButton,
				linkButton,
			],
			events: {
				onClose: () => {
					this.root.closeApplication();
				},
			},
		});

		this.mainPopup.show();

		this.bindCopyLink(linkButton);

		this.bindSelectors();
	}

	sendSms()
	{
		Ajax.runAction('salescenter.terminalResponsible.sendLinkToMobileApp', {
			data: {
				userId: this.userId,
				phone: this.selectedPhoneNumber,
				senderId: this.selectedSender.id,
				entity: {
					entityTypeId: this.root.ownerTypeId,
					entityId: this.root.ownerId,
				},
			},
		}).then((result) => {
			this.mainPopup.close();
			this.root.closeApplication();
		});
	}

	bindCopyLink(linkButton)
	{
		BX.clipboard.bindCopyClick(linkButton.getContainer(), {
			text: this.root.options.mobileAppLink,
		});
	}

	bindSelectors()
	{
		this.bindSendersSelector();
		this.bindPhoneSelector();
	}

	bindSendersSelector()
	{
		const sendersSelector = document.getElementById('senders-selector');

		if (this.selectedSender)
		{
			Event.bind(sendersSelector, 'click', () => {
				const menuItems = [];
				this.senders.forEach((sender) => {
					menuItems.push({
						text: Text.encode(sender.name),
						onclick: () => {
							this.selectedSender = sender;
							sendersSelector.firstChild.innerText = Text.encode(this.selectedSender.name);
							this.sendersMenu?.close();
						},
					});
				});

				BX.PopupMenu.show(
					'sender-menu',
					sendersSelector,
					menuItems,
					{
						offsetTop: 0,
						offsetLeft: 36,
						angle: { position: 'top', offset: 0 },
					},
				);

				this.sendersMenu = BX.PopupMenu.getCurrentMenu();
			});
		}
		else
		{
			Event.bind(sendersSelector, 'click', () => {
				BX.SidePanel.Instance.open(
					this.sendersConfig.connectUrl,
					{
						events: {
							onClose: () => {
								this.refresh();
							},
						},
					},
				);
			});
		}
	}

	bindPhoneSelector()
	{
		const phoneSelector = document.getElementById('phone-selector');

		if (this.selectedPhoneNumber)
		{
			Event.bind(phoneSelector, 'click', () => {
				const menuItems = [];
				this.phoneNumbers.forEach((phoneNumber) => {
					menuItems.push({
						text: Text.encode(phoneNumber),
						onclick: () => {
							this.selectedPhoneNumber = phoneNumber;
							phoneSelector.firstChild.innerText = Text.encode(this.selectedPhoneNumber);
							this.phoneMenu?.close();
						},
					});
				});

				BX.PopupMenu.show(
					'phone-menu',
					phoneSelector,
					menuItems,
					{
						offsetTop: 0,
						offsetLeft: 36,
						angle: { position: 'top', offset: 0 },
					},
				);

				this.phoneMenu = BX.PopupMenu.getCurrentMenu();
			});
		}
		else
		{
			Event.bind(phoneSelector, 'click', () => {
				BX.SidePanel.Instance.open(
					`/company/personal/user/${this.userId}/`,
					{
						events: {
							onClose: () => {
								this.refresh();
							},
						},
					},
				);
			});
		}
	}

	refresh()
	{
		Ajax.runAction('salescenter.terminalResponsible.refreshDataForSendingLink', {
			data: {
				userId: this.userId,
			},
		}).then((result) => {
			this.initSenders(result.data.senders);
			this.phoneNumbers = result.data.phones;

			this.selectedPhoneNumber = this.phoneNumbers?.[0] ?? null;
			this.selectedSender = this.senders?.[0] ?? null;

			this.updateSendButtonState();
			this.updateContent();
		});
	}

	updateSendButtonState()
	{
		this.sendButton.setDisabled(!Boolean(this.selectedPhoneNumber && this.selectedSender));
	}

	updateContent()
	{
		this.mainPopup.setContent(this.getPopupContent());
		this.bindSelectors();
	}

	getPopupContent()
	{
		// all for the sake of localization
		let phoneAndServiceContent = '';
		if (this.selectedSender)
		{
			phoneAndServiceContent = Loc.getMessage(
				'SALESCENTER_TERMINAL_MOBILE_POPUP_SENDER_AND_PHONE',
				{
					'[main]': '<div class="salescenter-popup-mobile__service">',
					'[/main]': '</div>',
					'[number]': '<div class="salescenter-popup-mobile__service-box"><div class="salescenter-popup-mobile__service-name">',
					'#NUMBER#': `
						</div>
						<div class="salescenter-popup-mobile__service-inline" id="phone-selector">
							<div class="salescenter-popup-mobile__service-value">
								${
							this.selectedPhoneNumber
								? Text.encode(this.selectedPhoneNumber)
								: Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_ADD_PHONE')
						}
							</div>
					`,
					'[/number]': `
							${this.selectedPhoneNumber ? '<div class="ui-icon-set --chevron-down"></div>' : ''}
						</div>
						</div>
					`,
					'[service]': '<div class="salescenter-popup-mobile__service-box"><div class="salescenter-popup-mobile__service-name">',
					'#SERVICE#': `
						</div>
						<div class="salescenter-popup-mobile__service-inline" id="senders-selector">
							<div class="salescenter-popup-mobile__service-value">${Text.encode(this.selectedSender.name)}</div>
					`,
					'[/service]': `
							<div class="ui-icon-set --chevron-down"></div>
						</div>
						</div>
					`,
				},
			);
		}
		else
		{
			phoneAndServiceContent = Loc.getMessage(
				'SALESCENTER_TERMINAL_MOBILE_POPUP_SENDER_AND_PHONE_NO_SENDER',
				{
					'[main]': '<div class="salescenter-popup-mobile__service">',
					'[/main]': '</div>',
					'[number]': '<div class="salescenter-popup-mobile__service-box"><div class="salescenter-popup-mobile__service-name">',
					'#NUMBER#': `
						</div>
						<div class="salescenter-popup-mobile__service-inline" id="phone-selector">
							<div class="salescenter-popup-mobile__service-value">
								${
									this.selectedPhoneNumber
										? Text.encode(this.selectedPhoneNumber)
										: Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_ADD_PHONE')
								}
							</div>
					`,
					'[/number]': `
							${this.selectedPhoneNumber ? '<div class="ui-icon-set --chevron-down"></div>' : ''}
							</div>
						</div>
					`,
					'[service]': '<div class="salescenter-popup-mobile__service-box"><div class="salescenter-popup-mobile__service-inline" id="senders-selector"><div class="salescenter-popup-mobile__service-value">',
					'[/service]': `
							</div>
							<div class="ui-icon-set --chevron-down"></div>
						</div>
					`,
				},
			);
		}

		return Tag.render`
			<div class="salescenter-popup-mobile__wrap">
				<div class="salescenter-popup-mobile__icon-box">
					<div class="salescenter-popup-mobile__icon"></div>
				</div>
				<div class="salescenter-popup-mobile__content">
					<div class="salescenter-popup-mobile__title">${Loc.getMessage('SALESCENTER_TERMINAL_MOBILE_POPUP_TITLE')}</div>
					${phoneAndServiceContent}
				</div>
			</div>
		`;
	}
}

export { MobileAppInstallPopup };
