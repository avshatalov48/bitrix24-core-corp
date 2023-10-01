import { Tag, Event, Loc, Type, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { MenuManager } from "main.popup";
import WithEditor from "./witheditor";
import { Guide } from "ui.tour";
import Context from "../context";
import { DialogNew } from 'calendar.sharing.interface';

/** @memberof BX.Crm.Timeline.MenuBar */
export default class Sharing extends WithEditor
{
	HELPDESK_CODE = 17502612;

	/**
	 * @override
	 */
	initialize(context: Context, settings: ?Object): void
	{
		const config = settings.config;

		this.link = config.link;

		this.setContacts(config.contacts);

		this.isNotificationsAvailable = config.isNotificationsAvailable;

		this.areCommunicationChannelsAvailable = config.areCommunicationChannelsAvailable;
		if (this.areCommunicationChannelsAvailable)
		{
			this.setCommunicationChannels(config.communicationChannels, config.selectedChannelId);
		}

		this.doPayAttentionToNewFeature = config.doPayAttentionToNewFeature;

		super.initialize(context, settings);

		if (this.getSetting('isAvailable'))
		{
			this.bindEvents();
		}
	}

	activate()
	{
		if (this.getSetting('isAvailable'))
		{
			this.setVisible(true);
		}
		else
		{
			BX.UI?.InfoHelper?.show('limit_crm_calendar_free_slots');
		}
	}

	supportsLayout(): Boolean
	{
		return this.getSetting('isAvailable');
	}

	bindEvents()
	{
		EventEmitter.subscribe('CalendarSharing:LinkCopied', () => this.onLinkCopied());
		EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', this.onContactsChangedHandler.bind(this));
	}

	/**
	 * @override
	 */
	doInitialize()
	{
		if (this.doPayAttentionToNewFeature)
		{
			this.payAttentionToNewFeature();
			BX.ajax.runAction('crm.api.timeline.calendar.sharing.disableOptionPayAttentionToNewCrmSharingFeature');
		}
	}

	/**
	 * @override
	 */
	createLayout(): HTMLElement
	{
		this.DOM = {
			menuBarItem: document.querySelector('.crm-entity-stream-section-menu [data-id=sharing]'),
		};

		return Tag.render`
			<div class="crm-entity-stream-content-sharing --hidden">
				<div id="_sharing_content_container">
					<div class="crm-entity-stream-calendar-sharing-container">
						<div class="crm-entity-stream-calendar-sharing-main">
							<div class="crm-entity-stream-calendar-sharing-icon"></div>
							<div class="crm-entity-stream-calendar-sharing-info">
								<div class="crm-entity-stream-calendar-sharing-header">
									${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_INFO_TITLE')}
								</div>
								<div class="crm-entity-stream-calendar-sharing-info-item">
									<div class="crm-entity-stream-calendar-sharing-info-item-icon"></div>
									<div class="crm-entity-stream-calendar-sharing-info-item-text">
										${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_INFO_ITEM_1')}
									</div>
								</div>
								<div class="crm-entity-stream-calendar-sharing-info-item">
									<div class="crm-entity-stream-calendar-sharing-info-item-icon"></div>
									<div class="crm-entity-stream-calendar-sharing-info-item-text">
										${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_INFO_ITEM_2')}
									</div>
								</div>
								<div class="crm-entity-stream-calendar-sharing-info-btn-settings">
									<div class="crm-entity-stream-calendar-sharing-info-icon-qr"></div>
									${this.createConfigureSlotsButton()}
								</div>
							</div>
							${this.createSettingsButton()}
						</div>
					</div>
				</div>
				<div class="crm-entity-stream-calendar-sharing-btn-container">
					${this.createSendButton()}
					${this.createCancelButton()}
					${this.createMoreInfoButton()}
				</div>
			</div>
		`;
	}

	createConfigureSlotsButton()
	{
		this.DOM.configureSlotsButton = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-info-btn-settings-text">
				${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_CONFIGURE_SLOTS')}
			</div>
		`;
		Event.bind(this.DOM.configureSlotsButton, 'click', () => this.onConfigureSlotsButtonClick());

		return this.DOM.configureSlotsButton;
	}

	createSettingsButton()
	{
		this.DOM.settingsButton = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-icon"></div>
		`;
		this.updateSettingsButton();

		Event.bind(this.DOM.settingsButton, 'click', () => this.onSettingsButtonClick());

		return this.DOM.settingsButton;
	}

	updateSettingsButton()
	{
		if (this.isContactAvailable())
		{
			this.DOM.settingsButton.style.display = '';
		}
		else
		{
			this.DOM.settingsButton.style.display = 'none';
		}
	}

	createSendButton()
	{
		this.DOM.sendButton = Tag.render`
			<button class="ui-btn ui-btn-xs ui-btn-primary">
				${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_SEND_BUTTON')}
			</button>
		`;
		Event.bind(this.DOM.sendButton, 'click', () => this.onSendButtonClick());

		this._saveButton = this.DOM.sendButton;

		return this.DOM.sendButton;
	}

	createCancelButton()
	{
		this.DOM.cancelButton = Tag.render`
			<span class="ui-btn ui-btn-xs ui-btn-link">
				${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_CANCEL_BUTTON')}
			</span>
		`;
		Event.bind(this.DOM.cancelButton, 'click', () => this.onCancelButtonClick());

		this._cancelButton = this.DOM.cancelButton;

		return this.DOM.cancelButton;
	}

	createMoreInfoButton()
	{
		this.DOM.moreInfoButton = Tag.render`
			<span class="crm-entity-stream-calendar-sharing-more-btn">
				${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_MORE_INFO_BUTTON')}
			</span>
		`;
		Event.bind(this.DOM.moreInfoButton, 'click', () => this.onMoreInfoButtonClick());

		return this.DOM.moreInfoButton;
	}

	onConfigureSlotsButtonClick()
	{
		this.showConfigureSlotsPopup();
	}

	onSettingsButtonClick()
	{
		this.showSettingsPopup();
	}

	onSendButtonClick()
	{
		if (!this.isContactAvailable())
		{
			this.showWarningNoContact();
			return;
		}

		if (!this.areCommunicationChannelsAvailable)
		{
			this.showWarningNoCommunicationChannels();
			return;
		}

		this.onSaveButtonClick();
	}

	onLinkCopied()
	{
		this.saveLinkAction({
			isActionCopy: true,
		});
	}

	onMoreInfoButtonClick()
	{
		this.openHelpDesk();
	}

	showConfigureSlotsPopup()
	{
		if (!this.newDialog)
		{
			this.newDialog = new DialogNew({
				bindElement: this.DOM.configureSlotsButton,
				sharingUrl: this.link.url,
				context: "crm",
			});
		}
		this.newDialog.show();
	}

	showSettingsPopup()
	{
		if (this.settingsMenu)
		{
			this.settingsMenu.destroy();
		}
		this.settingsMenu = this.getSettingsMenu();
		this.settingsMenu.show();
	}

	isSettingsPopupShown()
	{
		return this.settingsMenu?.popupWindow.isShown();
	}

	getSettingsMenu()
	{
		const items = [this.getSharingReceiverItem()];

		if (this.areCommunicationChannelsAvailable && this.isChannelsAvailable())
		{
			items.push(this.getSharingChannelsItem());
		}

		if (this.currentFromList)
		{
			items.push(this.getSharingSenderItem());
		}

		return MenuManager.create({
			id: 'crm-calendar-sharing-settings',
			bindElement: this.DOM.settingsButton,
			items: items
		});
	}

	getSharingReceiverItem()
	{
		return {
			id: 'sharing_receiver',
			text: Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_RECEIVER'),
			items: this.contacts.map((contact) => {
				return this.getContactMenuItem(contact);
			})
		};
	}

	getSharingChannelsItem()
	{
		return {
			id: 'sharing_channels',
			text: Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_COMMUNICATION_CHANNELS'),
			items: this.channels.map((channel) => {
				return this.getChannelMenuItem(channel);
			}),
		}
	}

	getSharingSenderItem()
	{
		return {
			id: 'sharing_sender',
			text: Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_SENDER'),
			items: this.currentFromList.map((from) => {
				return this.getFromMenuItem(from);
			}),
		};
	}

	getContactMenuItem(contact)
	{
		const isSelected = contact.entityId === this.contact.entityId && contact.entityTypeId === this.contact.entityTypeId;
		const itemHtml = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${contact.name} (${contact.phone})</div>
			</div>
		`;
		contact.check = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-check-icon ${isSelected ? '--show' : ''}"></div>
		`;
		itemHtml.append(contact.check);

		return {
			html: itemHtml,
			onclick: () => {
				Dom.removeClass(this.contact.check, '--show');
				Dom.addClass(contact.check, '--show');
				this.contact = contact;
			},
		};
	}

	getChannelMenuItem(channel)
	{
		const isSelected = channel.id === this.channel.id;
		const itemHtml = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${channel.name}</div>
			</div>
		`;
		channel.check = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-check-icon ${isSelected ? '--show' : ''}"></div>
		`;
		itemHtml.append(channel.check);

		return {
			html: itemHtml,
			onclick: () => {
				Dom.removeClass(this.channel.check, '--show');
				Dom.addClass(channel.check, '--show');
				this.channel = channel;

				this.updateSenderList();
			},
		};
	}

	getFromMenuItem(from)
	{
		const isSelected = from.id === this.currentFrom.id;
		const itemHtml = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${from.name}</div>
			</div>
		`;
		from.check = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-check-icon ${isSelected ? '--show' : ''}"></div>
		`;
		itemHtml.append(from.check);

		return {
			html: itemHtml,
			onclick: () => {
				Dom.removeClass(this.currentFrom.check, '--show');
				Dom.addClass(from.check, '--show');
				this.currentFrom = from;
			},
		};
	}

	showWarningNoCommunicationChannels()
	{
		let title;
		let text;

		if (this.isNotificationsAvailable)
		{
			title = Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_COMMUNICATION_CHANNELS_WARNING_TITLE');
			text = `
				<div>${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_COMMUNICATION_CHANNELS_WARNING_TEXT_1')}</div>
				</br>
				<div>${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_COMMUNICATION_CHANNELS_WARNING_TEXT_2')}</div>
			`;
		}
		else
		{
			title = Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CUSTOM_COMMUNICATION_CHANNELS_WARNING_TITLE');
			text = `
				<div>${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CUSTOM_COMMUNICATION_CHANNELS_WARNING_TITLE_1')}</div>
				</br>
				<div>${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_COMMUNICATION_CHANNELS_WARNING_TEXT_2')}</div>
			`;
		}

		const noCommunicationChannelsWarningGuide = this.getWarningGuide(title, text);
		noCommunicationChannelsWarningGuide.showNextStep();

		const guidePopup = noCommunicationChannelsWarningGuide.getPopup();
		const guideContentContainer = guidePopup.getContentContainer();
		const openConfigurationButton = guideContentContainer.querySelector('span[data-role=crm-timeline-calendar-sharing_open-configure-slots]');
		openConfigurationButton.addEventListener('click', () => {
			guidePopup.close();
			this.showConfigureSlotsPopup();
		});
	}

	showWarningNoContact()
	{
		const title = Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CONTACT_WARNING_TITLE');
		const text = Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CONTACT_WARNING_TEXT');
		const noContactWarningGuide = this.getWarningGuide(title, text);
		noContactWarningGuide.showNextStep();
	}

	updateSenderList()
	{
		this.currentFromList = this.channel.fromList;
		this.currentFrom = this.channel.fromList[0];

		if (this.settingsMenu)
		{
			this.settingsMenu.removeMenuItem('sharing_sender');
			const item = this.getSharingSenderItem();
			this.settingsMenu.addMenuItem(item);
		}
	}

	copyLink(link)
	{
		BX.clipboard.copy(link);
		BX.UI.Notification.Center.notify({
			content: Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_COPY_LINK_NOTIFICATION'),
		});
		EventEmitter.emit('CalendarSharing:LinkCopied');
	}

	/**
	 * @override
	 */
	save(): Promise
	{
		return this.saveLinkAction();
	}

	saveLinkAction(options = { isActionCopy: false })
	{
		let action;
		let data = {
			ownerId: this.getEntityId(),
			ownerTypeId: this.getEntityTypeId(),
		};

		if (this.isContactAvailable() && this.isChannelsAvailable() && !options.isActionCopy)
		{
			action = 'crm.api.timeline.calendar.sharing.sendLink';
			data.contactId = this.contact.entityId || null;
			data.contactTypeId = this.contact.entityTypeId || null;
			data.channelId = this.channel.id || null;
			data.senderId = this.currentFrom.id || null;
		}
		else
		{
			action = 'crm.api.timeline.calendar.sharing.onLinkCopied';
			data.linkHash = this.link.hash;
		}

		return BX.ajax.runAction(action, { data }).then((response) => {
			if (response.data)
			{
				this.emitFinishEditEvent();
				return true;
			}

			return false;
		}, (error) => {
			console.error(error);
			return false;
		});
	}

	onContactsChangedHandler(event)
	{
		const { item, current } = event.getData();

		const isCurrentDeal = this.getEntityTypeId() === item?.entityTypeId && this.getEntityId() === item?.entityId;
		if (!isCurrentDeal || !Type.isArray(current))
		{
			return;
		}

		const phoneReceivers = current.filter(receiver => receiver.address.typeId === 'PHONE');

		const contacts = [];
		const contactsHashes = [];
		for (const receiver of phoneReceivers)
		{
			if (contactsHashes.includes(receiver.addressSource.hash))
			{
				continue;
			}
			contactsHashes.push(receiver.addressSource.hash);

			contacts.push({
				entityId: receiver.addressSource.entityId,
				entityTypeId: receiver.addressSource.entityTypeId,
				name: receiver.addressSourceData?.title,
				phone: receiver.address.value,
			});
		}

		this.setContacts(contacts);

		this.updateSettingsButton();
		if (this.isSettingsPopupShown())
		{
			this.showSettingsPopup();
		}
	}

	setContacts(contacts)
	{
		this.contacts = contacts.filter(contact => contact.entityId && contact.entityTypeId && contact.phone && contact.name)
			.sort((a, b) => a.entityId - b.entityId) // sort by id
			.sort((a, b) => a.entityTypeId - b.entityTypeId); // sort company last

		this.contact = contacts.find((contact) => {
			return contact.entityTypeId === this.contact?.entityTypeId && contact.entityId === this.contact?.entityId;
		}) ?? this.contacts[0];
	}

	setCommunicationChannels(channels, selectedId)
	{
		this.channels = channels || [];

		if (selectedId)
		{
			this.channel = channels.find((channel) => {
				return channel.id === selectedId
			}) ?? this.channels[0];
		}
		else
		{
			this.channel = this.channels ? this.channels[0] : null;
		}

		if (this.channel && this.channel.fromList)
		{
			this.currentFromList = this.channel.fromList;
			this.currentFrom = this.channel.fromList[0];
		}
	}

	isContactAvailable()
	{
		return Type.isArrayFilled(this.contacts);
	}

	isChannelsAvailable()
	{
		return Type.isArrayFilled(this.channels);
	}

	openHelpDesk()
	{
		if (top.BX.Helper)
		{
			top.BX.Helper.show(`redirect=detail&code=${this.HELPDESK_CODE}`);
		}
	}

	getWarningGuide(title, text)
	{
		const warningGuide = new Guide({
			simpleMode: true,
			onEvents: true,
			steps: [
				{
					target: this.DOM.sendButton,
					title,
					text,
					condition: {
						top: false,
						bottom: true,
						color: 'warning',
					},
				},
			],
		});
		const guidePopup = warningGuide.getPopup();
		Dom.addClass(guidePopup.popupContainer, 'crm-calendar-sharing-configure-slots-popup-ui-tour-animate');
		guidePopup.setWidth(390);
		const guideContent = guidePopup.getContentContainer().firstElementChild;
		const offsetFromCloseIcon = parseInt(getComputedStyle(guidePopup.closeIcon)['width']);
		const existingPadding = parseInt(getComputedStyle(guideContent)['paddingRight']);
		guidePopup.getContentContainer().style.paddingRight = (offsetFromCloseIcon - existingPadding) + 'px';
		guidePopup.setAutoHide(true);

		guidePopup.subscribe('onAfterShow', () => {
			setTimeout(() => {
				const arrowContainer = guidePopup.angle.element;
				const arrow = arrowContainer.firstElementChild;
				arrow.style.border = '2px solid var(--ui-color-text-warning, #ffa900)';

				if (guidePopup.getContentContainer().getBoundingClientRect().top > this.DOM.sendButton.getBoundingClientRect().top)
				{
					const condition = guidePopup.getContentContainer().querySelector('.ui-tour-popup-condition-bottom');
					condition.className = 'ui-tour-popup-condition-top';
					arrowContainer.style.top = '-20px';
				}
				else
				{
					arrowContainer.style.bottom = '-18px';
				}
			}, 0);
		});

		return warningGuide;
	}

	payAttentionToNewFeature()
	{
		const guide = this.getGuide();
		const pulsar = this.getPulsar();

		setTimeout(() => {
			guide.showNextStep();
			pulsar.show();
		}, 1000);
	}

	getGuide()
	{
		const guide = new Guide({
			simpleMode: true,
			onEvents: true,
			steps: [
				{
					target: this.DOM.menuBarItem,
					title: Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_PAY_ATTENTION_TO_NEW_FEATURE_TITLE'),
					text: Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_PAY_ATTENTION_TO_NEW_FEATURE_TEXT'),
					article: this.HELPDESK_CODE,
					condition: {
						top: true,
						bottom: false,
						color: 'primary',
					},
				},
			],
		});
		const guidePopup = guide.getPopup();
		Dom.addClass(guidePopup.popupContainer, 'crm-calendar-sharing-configure-slots-popup-ui-tour-animate');
		guidePopup.setWidth(400);
		guidePopup.getContentContainer().style.paddingRight = getComputedStyle(guidePopup.closeIcon)['width'];

		return guide;
	}

	getPulsar()
	{
		const pulsar = new BX.SpotLight({
			targetElement: this.DOM.menuBarItem,
			targetVertex: 'middle-center',
		});
		pulsar.bindEvents({
			'onTargetEnter': () => pulsar.close(),
		});

		return pulsar;
	}
}
