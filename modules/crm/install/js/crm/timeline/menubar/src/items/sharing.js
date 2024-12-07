import { Layout, SettingsModel } from 'calendar.sharing.interface';
import { Analytics } from 'calendar.sharing.analytics';
import { ConditionChecker, Types as SenderTypes } from 'crm.messagesender';
import { Dom, Event, Loc, Tag, Text, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { MenuManager } from 'main.popup';
import { Button, ButtonColor, ButtonSize } from 'ui.buttons';
import { Guide } from 'ui.tour';
import { Loader } from 'main.loader';
import Context from '../context';
import WithEditor from './witheditor';

const DataLoadStatus = Object.freeze({
	loaded: 'loaded',
	notLoaded: 'notLoaded',
	loading: 'loading',
});

/** @memberof BX.Crm.Timeline.MenuBar */
export default class Sharing extends WithEditor
{
	#layout: {
		root: HTMLElement,
		loader: HTMLElement,
		wrap: Layout,
		menuBarItem: HTMLElement,
		sendButton: HTMLElement,
		settingsButton: HTMLElement,
	};

	#settingsModel: SettingsModel;

	/**
	 * @override
	 */
	initialize(context: Context, settings: ?Object): void
	{
		this.#layout = {};

		this.dataLoadStatus = DataLoadStatus.notLoaded;

		super.initialize(context, settings);

		if (this.supportsLayout())
		{
			this.#bindEvents();
		}
	}

	#bindEvents()
	{
		EventEmitter.subscribe('CalendarSharing:LinkCopied', ({ data: { hash } }) => this.onLinkCopied(hash));
		EventEmitter.subscribe('CalendarSharing:RuleUpdated', () => this.onRuleUpdated());
		EventEmitter.subscribe('BX.Crm.MessageSender.ReceiverRepository:OnReceiversChanged', this.onContactsChangedHandler.bind(this));
		Event.bind(window, 'beforeunload', () => this.#settingsModel?.save());
	}

	/**
	 * @override
	 */
	activate()
	{
		if (this.supportsLayout())
		{
			super.activate();
			this.#sendOpenFormAnalytics();
		}
		else
		{
			BX.UI?.InfoHelper?.show('limit_crm_calendar_free_slots');
		}
	}

	#sendOpenFormAnalytics()
	{
		Analytics.sendPopupOpened(Analytics.contexts.crm);
	}

	/**
	 * @override
	 */
	deactivate()
	{
		super.deactivate();
		this.#layout.wrap?.reset();
		this.setLocked(false);
	}

	/**
	 * @override
	 */
	supportsLayout(): Boolean
	{
		return this.getSetting('isAvailable') && this.getEntityId() > 0;
	}

	/**
	 * @override
	 */
	onShow()
	{
		super.onShow();

		if (this.dataLoadStatus !== DataLoadStatus.notLoaded)
		{
			return;
		}

		this.loadData().then((isSuccess) => {
			if (isSuccess)
			{
				this.#render();
			}
		});
	}

	async loadData(): Promise
	{
		this.dataLoadStatus = DataLoadStatus.loading;
		const action = 'crm.api.timeline.calendar.sharing.getConfig';
		const data = {
			entityTypeId: this.getEntityTypeId(),
			entityId: this.getEntityId(),
		};

		return BX.ajax.runAction(action, { data }).then((response) => {
			if (response?.data?.config)
			{
				this.setConfig(response.data.config);
				this.dataLoadStatus = DataLoadStatus.loaded;

				return true;
			}

			return false;
		}, (error) => {
			console.error(error);

			return false;
		});
	}

	setConfig(config)
	{
		this.link = config.link;
		this.isResponsible = config.isResponsible;
		this.isNotificationsAvailable = config.isNotificationsAvailable;
		this.dealContacts = config.contacts;

		this.setCommunicationChannels(config.communicationChannels, config.selectedChannelId);

		this.#settingsModel = new SettingsModel({
			context: 'crm',
			linkHash: this.link.hash,
			sharingUrl: this.link.url,
			userInfo: config.userInfo,
			rule: this.link.rule,
			calendarSettings: config.calendarSettings,
			collapsed: false,
		});
	}

	/**
	 * @override
	 */
	save(): Promise
	{
		return this.#sendLinkAction();
	}

	/**
	 * @override
	 */
	createLayout(): HTMLElement
	{
		this.#layout.menuBarItem = document.querySelector('.crm-entity-stream-section-menu [data-id=sharing]');

		this.#layout.root = Tag.render`
			<div class="crm-entity-stream-content-sharing crm-entity-stream-content-wait-detail --hidden">
				${this.#renderLoader()}
				<div class="crm-entity-stream-calendar-sharing-btn-container">
					${this.renderSendButton()}
					${this.renderCopyButton()}
					${this.renderCancelButton()}
				</div>
			</div>
		`;

		return this.#layout.root;
	}

	#renderLoader(): HTMLElement
	{
		this.#layout.loader = Tag.render`
			<div class="crm-entity-stream-content-sharing-loader"></div>
		`;

		new Loader().show(this.#layout.loader);

		return this.#layout.loader;
	}

	#render(): HTMLElement
	{
		this.#layout.wrap = new Layout({
			readOnly: !this.isResponsible,
			settingsModel: this.#settingsModel,
			externalIcon: this.createSettingsButton(),
		});

		const wrapNode = this.#layout.wrap.render();
		this.#layout.loader.replaceWith(wrapNode);

		return wrapNode;
	}

	createSettingsButton()
	{
		this.#layout.settingsButton = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-icon"></div>
		`;
		this.updateSettingsButton();

		Event.bind(this.#layout.settingsButton, 'click', () => this.onSettingsButtonClick());

		return this.#layout.settingsButton;
	}

	updateSettingsButton()
	{
		if (this.hasContacts())
		{
			this.#layout.settingsButton.style.display = '';
		}
		else
		{
			this.#layout.settingsButton.style.display = 'none';
		}
	}

	renderSendButton()
	{
		this.#layout.sendButton = new Button({
			text: Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_SEND_BUTTON_MSGVER_2'),
			size: ButtonSize.EXTRA_SMALL,
			color: ButtonColor.PRIMARY,
			round: true,
			onclick: () => this.onSendButtonClick(),
		}).render();

		this._saveButton = this.#layout.sendButton;

		return this.#layout.sendButton;
	}

	renderCopyButton()
	{
		return new Button({
			text: Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_COPY_BUTTON'),
			size: ButtonSize.EXTRA_SMALL,
			color: ButtonColor.LIGHT_BORDER,
			round: true,
			onclick: () => this.copyLink(),
		}).render();
	}

	renderCancelButton()
	{
		const cancelButton = new Button({
			text: Loc.getMessage('CRM_TIMELINE_CANCEL_BTN'),
			size: ButtonSize.EXTRA_SMALL,
			color: ButtonColor.LINK,
			onclick: () => this.onCancelButtonClick(),
		}).render();

		this._cancelButton = cancelButton;

		return cancelButton;
	}

	onSettingsButtonClick()
	{
		this.showSettingsPopup();
	}

	async onSendButtonClick()
	{
		if (!this.hasDealContacts())
		{
			this.showWarningNoContact();

			return;
		}

		if (!this.isChannelAvailable(this.channel) && this.hasPhoneWithoutChannels())
		{
			this.showWarningNoCommunicationChannels();

			return;
		}

		if (!this.isChannelAvailable(this.channel) && this.hasEmailWithoutChannels())
		{
			this.connectMailbox();

			return;
		}

		if (
			this.isNotificationsAvailable
			&& this.isChannelBitrix24(this.channel)
		)
		{
			const isApproved = await this.isBitrix24Approved();

			if (isApproved)
			{
				this.onSaveButtonClick();

				return;
			}
			else
			{
				this.showWarningNoCommunicationChannels();

				return;
			}
		}

		this.onSaveButtonClick();
	}

	onLinkCopied(linkHash)
	{
		void this.#sendLinkAction({
			isActionCopy: true,
			linkHash,
		});
	}

	onRuleUpdated()
	{
		this.onRuleUpdatedAction();
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
		return MenuManager.create({
			id: 'crm-calendar-sharing-settings',
			bindElement: this.#layout.settingsButton,
			items: this.getSettingsMenuItems(),
		});
	}

	getSettingsMenuItems()
	{
		const items = [this.getSharingReceiverItem()];

		if (this.hasChannels())
		{
			items.push(this.getSharingChannelsItem());
		}

		if (this.isChannelAvailable(this.channel))
		{
			items.push(this.getSharingSenderItem());
		}

		return items;
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
			items: this.channels.filter((channel) => channel.contacts.length > 0).map((channel) => {
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
		const isSelected = contact.entityId === this.contact.entityId && contact.entityTypeId === this.contact.entityTypeId && contact.value === this.contact.value;
		const itemHtml = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${Text.encode(`${contact.name} (${contact.value})`)}</div>
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
		const isSelected = channel.id === this.channel.id && this.isChannelAvailable(channel);

		const itemHtml = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${Text.encode(channel.name)}</div>
			</div>
		`;
		channel.check = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-check-icon ${isSelected ? '--show' : ''}"></div>
		`;
		itemHtml.append(channel.check);

		return {
			html: itemHtml,
			className: (channel.fromList.length <= 0) ? 'crm-timeline-popup-menu-item-disabled menu-popup-no-icon' : '',
			onclick: () => {
				if (channel.fromList.length <= 0)
				{
					this.connectMailbox();

					return;
				}

				Dom.removeClass(this.channel.check, '--show');
				Dom.addClass(channel.check, '--show');
				this.setChannel(channel);
			},
		};
	}

	connectMailbox()
	{
		BX.SidePanel.Instance.open('/mail/');

		//TODO: replace this workaround with subscribing for onPullEvent-mail "mailbox_created"
		const onMailSliderClose = () => {
			const previous = BX.SidePanel.Instance.openSliders[BX.SidePanel.Instance.getOpenSlidersCount() - 2];
			if (previous.url.includes('/crm/'))
			{
				this.updateChannels();

				top.BX.Event.EventEmitter.unsubscribe('SidePanel.Slider:onClose', onMailSliderClose);
			}
		};

		top.BX.Event.EventEmitter.subscribe('SidePanel.Slider:onClose', onMailSliderClose);
	}

	updateChannels()
	{
		const data = {
			entityTypeId: this.getEntityTypeId(),
			entityId: this.getEntityId(),
		};

		BX.ajax.runAction('crm.timeline.calendar.sharing.getConfig', { data })
			.then((response) => {
				this.setCommunicationChannels(response.data.config.communicationChannels, this.channel.id);
			})
		;
	}

	getFromMenuItem(from)
	{
		const isSelected = from.id === this.currentFrom.id;
		const itemHtml = Tag.render`
			<div class="crm-entity-stream-calendar-sharing-settings-check">
				<div>${Text.encode(from.name)}</div>
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
				<div>${Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CUSTOM_COMMUNICATION_CHANNELS_WARNING_TITLE_1').replaceAll('/marketplace/', Loc.getMessage('MARKET_BASE_PATH'))}</div>
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
		});
	}

	showWarningNoContact()
	{
		const title = Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CONTACT_WARNING_TITLE');
		const text = Loc.getMessage('CRM_TIMELINE_CALENDAR_SHARING_NO_CONTACT_WARNING_TEXT_V2');
		const noContactWarningGuide = this.getWarningGuide(title, text);
		noContactWarningGuide.showNextStep();
	}

	async #sendLinkAction({ isActionCopy, linkHash } = {})
	{
		const data = {
			ownerId: this.getEntityId(),
			ownerTypeId: this.getEntityTypeId(),
			ruleArray: this.#settingsModel.getRule().toArray(),
			memberIds: this.#settingsModel.getMemberIds(),
		};

		let action;
		if (!isActionCopy && this.isChannelAvailable(this.channel))
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
			data.linkHash = linkHash ?? this.link.hash;
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

	async copyLink()
	{
		this.setLocked(true);
		const link = await this.#getSharingLink();
		this.#layout.wrap.copyLink(link.url, link.hash);
		this.#sendCopyAnalytics();
	}

	#sendCopyAnalytics()
	{
		const params = {
			peopleCount: this.#settingsModel.getMemberIds().length,
			ruleChanges: this.#settingsModel.getChanges(),
		};

		const type = this.#settingsModel.getMemberIds().length === 1
			? Analytics.linkTypes.solo
			: Analytics.linkTypes.multiple
		;

		Analytics.sendLinkCopied(this.#settingsModel.getContext(), type, params);
	}

	async #getSharingLink(): Promise
	{
		if (this.#settingsModel.getMemberIds().length === 1)
		{
			return {
				url: this.#settingsModel.getSharingUrl(),
				hash: this.#settingsModel.getLinkHash(),
			};
		}

		return await this.saveJointLink();
	}

	async saveJointLink(): Promise
	{
		const response = await BX.ajax.runAction('crm.api.timeline.calendar.sharing.generateJointSharingLink', {
			data: {
				memberIds: this.#settingsModel.getMemberIds(),
				entityId: this.getEntityId(),
				entityTypeId: this.getEntityTypeId(),
			},
		});

		return response.data;
	}

	onRuleUpdatedAction()
	{
		return BX.ajax.runAction('crm.api.timeline.calendar.sharing.onRuleUpdated', {
			data: {
				linkHash: this.link.hash,
				ownerId: this.getEntityId(),
				ownerTypeId: this.getEntityTypeId(),
			},
		}).then((response) => {}, (error) => {
			console.error(error);
			return false;
		});
	}

	onContactsChangedHandler(event)
	{
		const { item, current } = event.getData();

		const isCurrentDeal = this.getEntityTypeId() === item?.entityTypeId && this.getEntityId() === item?.entityId;
		if (!isCurrentDeal || !Type.isArray(current) || !Type.isArray(this.channels))
		{
			return;
		}

		const contacts = current.map((receiver) => ({
			id: receiver.address.id,
			entityId: receiver.addressSource.entityId,
			entityTypeId: receiver.addressSource.entityTypeId,
			name: receiver.addressSourceData?.title,
			value: receiver.address.value,
			valueType: receiver.address.valueType,
			typeId: receiver.address.typeId,
		}));

		this.dealContacts = contacts;

		const phoneContacts = contacts.filter((receiver) => receiver.typeId === 'PHONE');
		const mailContacts = contacts.filter((receiver) => receiver.typeId === 'EMAIL');

		this.channels.forEach((channel) => {
			if (channel.typeId === 'PHONE')
			{
				channel.contacts = phoneContacts;
			}
			if (channel.typeId === 'EMAIL')
			{
				channel.contacts = mailContacts;
			}
		});

		this.setChannel(this.chooseChannel(this.channel?.id));

		this.updateSettingsButton();
		if (this.isSettingsPopupShown())
		{
			this.showSettingsPopup();
		}
	}

	setContacts(contacts)
	{
		this.contacts = contacts.filter(contact => contact.entityId && contact.entityTypeId && contact.value && contact.name)
			.sort((a, b) => a.entityId - b.entityId) // sort by id
			.sort((a, b) => a.entityTypeId - b.entityTypeId); // sort company last

		this.contact = contacts.find((contact) => {
			return contact.entityTypeId === this.contact?.entityTypeId && contact.entityId === this.contact?.entityId;
		}) ?? this.contacts[0];
	}

	setCommunicationChannels(channels, selectedId)
	{
		this.channels = channels || [];

		this.setChannel(this.chooseChannel(selectedId));
	}

	chooseChannel(selectedId)
	{
		const activeChannels = this.channels.filter((channel) => this.isChannelAvailable(channel));

		if (selectedId && Type.isArrayFilled(activeChannels))
		{
			return activeChannels.find((channel) => channel.id === selectedId) ?? activeChannels[0];
		}

		const availableChannels = this.channels.filter((channel) => channel.contacts.length > 0);

		return availableChannels?.[0];
	}

	setChannel(channel)
	{
		if (!channel)
		{
			return;
		}

		this.channel = channel;

		this.setContacts(this.channel.contacts);

		if (this.channel && this.channel.fromList)
		{
			this.currentFromList = this.channel.fromList;
			this.currentFrom = this.channel.fromList[0];
		}

		if (this.settingsMenu)
		{
			for (const item of this.getSettingsMenuItems())
			{
				this.settingsMenu.removeMenuItem(item.id);
				this.settingsMenu.addMenuItem(item);
			}
		}
	}

	hasContacts()
	{
		return Type.isArrayFilled(this.contacts);
	}

	hasDealContacts()
	{
		return Type.isArrayFilled(this.dealContacts);
	}

	hasChannels()
	{
		return Type.isArrayFilled(this.channels);
	}

	hasPhoneWithoutChannels()
	{
		if (!this.channel)
		{
			return true;
		}

		const phoneContacts = this.dealContacts.filter((contact) => contact.typeId === 'PHONE');
		const phoneChannels = this.channels.filter((channel) => channel.typeId === 'PHONE');
		const channelUnavailable = this.channel.typeId === 'PHONE' && !this.isChannelAvailable(this.channel);

		return Type.isArrayFilled(phoneContacts) && !Type.isArrayFilled(phoneChannels) || channelUnavailable;
	}

	hasEmailWithoutChannels()
	{
		if (!this.channel)
		{
			return true;
		}

		const mailContacts = this.dealContacts.filter((contact) => contact.typeId === 'EMAIL');
		const mailChannels = this.channels.filter((channel) => channel.typeId === 'EMAIL');
		const channelUnavailable = this.channel.typeId === 'EMAIL' && !this.isChannelAvailable(this.channel);

		return Type.isArrayFilled(mailContacts) && !Type.isArrayFilled(mailChannels) || channelUnavailable;
	}

	isChannelAvailable(channel)
	{
		return Type.isArrayFilled(channel?.fromList) && Type.isArrayFilled(channel?.contacts);
	}

	isChannelBitrix24(channel)
	{
		return channel.id === SenderTypes.bitrix24;
	}

	async isBitrix24Approved()
	{
		return await ConditionChecker.checkIsApproved({
			senderType: SenderTypes.bitrix24,
		});
	}

	getWarningGuide(title, text)
	{
		const warningGuide = new Guide({
			simpleMode: true,
			onEvents: true,
			steps: [
				{
					target: this.#layout.sendButton,
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
		guidePopup.setWidth(430);
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

				if (guidePopup.getContentContainer().getBoundingClientRect().top > this.#layout.sendButton.getBoundingClientRect().top)
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
}
