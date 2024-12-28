/**
 * @module crm/timeline/scheduler/providers/sharing
 */
jn.define('crm/timeline/scheduler/providers/sharing', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Alert, confirmDefaultAction } = require('alert');
	const { Haptics } = require('haptics');
	const { NotifyManager } = require('notify-manager');
	const { Icon } = require('assets/icons');
	const { EventEmitter } = require('event-emitter');
	const { get } = require('utils/object');
	const { copyToClipboard } = require('utils/copy');
	const { showEmailBanner } = require('communication/email-menu');
	const { getFeatureRestriction, tariffPlanRestrictionsReady } = require('tariff-plan-restriction');

	const { TypeId } = require('crm/type');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const { SettingsMenu } = require('crm/timeline/scheduler/providers/sharing/settings-menu');
	const { Layout } = require('crm/timeline/scheduler/providers/sharing/layout');

	const featureId = 'crm_event_sharing';

	let Sharing = null;
	let SharingContext = null;
	let NotificationServiceConsent = null;

	try
	{
		Sharing = require('calendar/sharing').Sharing;
		SharingContext = require('calendar/sharing').SharingContext;
	}
	catch (e)
	{
		console.warn(e, 'Calendar extensions not found');

		return;
	}

	try
	{
		NotificationServiceConsent = require('imconnector/consents/notification-service').NotificationServiceConsent;
	}
	catch (e)
	{
		console.warn(e, 'Imconnector extensions not found');

		NotificationServiceConsent = null;
	}

	/**
	 * @class TimelineSchedulerSharingProvider
	 */
	class TimelineSchedulerSharingProvider extends TimelineSchedulerBaseProvider
	{
		constructor(props)
		{
			super(props);

			this.config = {};
			this.smsConfig = {};
			this.communications = [];
			this.communication = null;
			this.senders = [];
			this.sender = null;

			this.sharing = new Sharing({ type: SharingContext.CRM });

			this.customEventEmitter = EventEmitter.createWithUid(Random.getString());

			this.openSettingsMenu = this.openSettingsMenu.bind(this);
			this.onSendButtonClick = this.onSendButtonClick.bind(this);
			this.onCopyLinkButtonClick = this.onCopyLinkButtonClick.bind(this);
			this.onContactSelect = this.onContactSelect.bind(this);
			this.onChangeSender = this.onChangeSender.bind(this);
			this.connectMailbox = this.connectMailbox.bind(this);
			this.onChangeSenderFrom = this.onChangeSenderFrom.bind(this);
			this.onRuleSave = this.onRuleSave.bind(this);
		}

		get entityId()
		{
			return this.props.entity.id;
		}

		get entityTypeId()
		{
			return this.props.entity.typeId;
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.bindEvents();
			this.fetchSettings().then(() => this.initSharingDialog());
		}

		componentWillUnmount()
		{
			this.unbindEvents();
		}

		bindEvents()
		{
			this.customEventEmitter.on('CalendarSharing:RuleSave', this.onRuleSave);
		}

		unbindEvents()
		{
			this.customEventEmitter.off('CalendarSharing:RuleSave', this.onRuleSave);
		}

		onRuleSave()
		{
			const ajaxData = {
				linkHash: this.config.link.hash,
				ownerId: this.entityId,
				ownerTypeId: this.entityTypeId,
			};

			BX.ajax.runAction('crm.api.timeline.calendar.sharing.onRuleUpdated', {
				data: ajaxData,
			}).catch((response) => {
				// eslint-disable-next-line no-undef
				void ErrorNotifier.showError(response?.errors[0]?.message);
			});
		}

		fetchSettings()
		{
			const data = {
				entityTypeId: this.entityTypeId,
				entityId: this.entityId,
			};

			return new Promise((resolve, reject) => {
				BX.ajax.runAction('crm.timeline.calendar.sharing.getConfig', { data })
					.then((response) => {
						this.setDefaultConfig(response.data);

						resolve(this.config);
					})
					.catch((response) => {
						// eslint-disable-next-line no-undef
						ErrorNotifier.showError(response.errors[0].message);

						reject(response.errors);
					})
				;
			});
		}

		setDefaultConfig(data)
		{
			const { config, smsConfig } = data;

			this.config = config;
			this.smsConfig = smsConfig;
			this.dealContacts = config.contacts;

			this.setSenders();
			this.setCommunications();
		}

		initSharingDialog()
		{
			// eslint-disable-next-line promise/catch-or-return
			this.sharing.initCrm(this.entityTypeId, this.entityId).then(() => {
				this.sharingLayoutRef?.setParams({
					readOnly: !this.config.isResponsible,
					sharing: this.sharing,
				});
			});
		}

		setSenders()
		{
			this.senders = this.config.communicationChannels.map((sender) => ({...sender, canUse: sender.fromList.length > 0}));
			this.sender = this.chooseChannel(this.config.selectedChannelId);

			if (this.sender)
			{
				this.sender.fromId = this.getSenderFromId(this.sender);
			}
		}

		chooseChannel(selectedId)
		{
			const activeChannels = this.senders.filter((channel) => this.isChannelAvailable(channel));

			if (selectedId && Type.isArrayFilled(activeChannels))
			{
				return activeChannels.find((channel) => channel.id === selectedId) ?? activeChannels[0];
			}

			const availableChannels = this.senders.filter((channel) => channel.contacts.length > 0);

			return availableChannels?.[0];
		}

		setCommunications()
		{
			const contacts = this.sender?.contacts ?? [];

			this.communications = contacts.filter((contact) => contact.entityId && contact.entityTypeId && contact.value && contact.name)
				.sort((a, b) => a.entityId - b.entityId) // sort by id
				.sort((a, b) => a.entityTypeId - b.entityTypeId); // sort company last

			this.communication = contacts.find((contact) => {
				return contact.entityTypeId === this.communication?.entityTypeId && contact.entityId === this.communication?.entityId;
			}) ?? this.communications[0];
		}

		onContactSelect({ communication })
		{
			this.communication = this.communications.find((contact) => contact.id === communication.id);
		}

		onChangeSender({ sender, fromId, communication })
		{
			this.sender = this.getSender(sender.id);
			this.sender.fromId = fromId;

			this.setCommunications();

			this.onContactSelect({ communication });
		}

		onChangeSenderFrom({ fromId })
		{
			this.sender.fromId = fromId;
		}

		static getId()
		{
			return 'sharing';
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_MENU_FULL_TITLE');
		}

		static getMenuShortTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_MENU_TITLE');
		}

		static getMenuIcon()
		{
			if (getFeatureRestriction(featureId).isRestricted())
			{
				return Icon.LOCK;
			}

			return Icon.CALENDAR_WITH_SLOTS;
		}

		static getDefaultPosition()
		{
			return 4;
		}

		static isAvailableInMenu(context = {})
		{
			if (!context.detailCard)
			{
				return false;
			}

			const detailCardParams = context.detailCard.getComponentParams();
			const entityTypeId = get(detailCardParams, 'entityTypeId', 0);
			const isCalendarSharingEnabled = get(detailCardParams, 'isCalendarSharingEnabled', true);
			const hasNewRestrictions = getFeatureRestriction(featureId).code() !== '';

			if (hasNewRestrictions)
			{
				return entityTypeId === TypeId.Deal;
			}

			return entityTypeId === TypeId.Deal && isCalendarSharingEnabled;
		}

		static isSupported(context = {})
		{
			return true;
		}

		static getBackdropParams()
		{
			return {
				hideNavigationBar: true,
				showOnTop: false,
				horizontalSwipeAllowed: false,
				onlyMediumPosition: true,
				mediumPositionPercent: undefined,
				mediumPositionHeight: 550,
				helpUrl: helpdesk.getArticleUrl('18313490'),
			};
		}

		static async open(data)
		{
			const { isRestricted, showRestriction } = getFeatureRestriction(featureId);

			if (isRestricted())
			{
				showRestriction();
			}
			else
			{
				super.open(data);
			}
		}

		render()
		{
			return new Layout({
				layoutWidget: this.layout,
				sharing: this.sharing,
				onSettingsClick: this.openSettingsMenu,
				onSendButtonClick: this.onSendButtonClick,
				onCopyLinkButtonClick: this.onCopyLinkButtonClick,
				customEventEmitter: this.customEventEmitter,
				ref: (ref) => {
					this.sharingLayoutRef = ref;
				},
			});
		}

		openSettingsMenu()
		{
			if (this.showWarnings())
			{
				return;
			}

			const { entity } = this.props;

			const menu = new SettingsMenu({
				entity,
				layout: this.layout,
				areCommunicationChannelsAvailable: this.hasChannels(),
				currentCommunication: this.communication,
				senders: this.senders.filter((sender) => sender.contacts.length > 0),
				currentSender: this.sender,
				contactCenterUrl: this.smsConfig.contactCenterUrl,
				onContactSelect: this.onContactSelect,
				onChangeSender: this.onChangeSender,
				connectMailbox: this.connectMailbox,
				onChangeSenderFrom: this.onChangeSenderFrom,
			});

			menu.show(this.layout);
		}

		onSendButtonClick()
		{
			if (this.showWarnings())
			{
				return;
			}

			if (
				this.config.isNotificationsAvailable
				&& this.isSenderBitrix24(this.sender)
				&& NotificationServiceConsent
			)
			{
				this.showConsentAndSend();

				return;
			}

			void this.sendLink();
		}

		onCopyLinkButtonClick()
		{
			void this.copyLink();
		}

		isSenderBitrix24(sender)
		{
			return sender?.id === 'bitrix24';
		}

		showConsentAndSend()
		{
			const consent = new NotificationServiceConsent();

			// eslint-disable-next-line promise/catch-or-return
			consent.open(this.props.layout).then((result) => {
				if (result)
				{
					if (!this.sender)
					{
						this.sender = this.getSender('bitrix24');
						if (this.sender)
						{
							this.sender.canUse = true;
							this.sender.fromId = this.getSenderFromId(this.sender);
						}
						else
						{
							this.showWarningNoCommunicationChannels();

							return;
						}
					}

					void this.sendLink();
				}
				else
				{
					Haptics.notifyWarning();

					// eslint-disable-next-line no-undef
					Notify.showUniqueMessage(
						Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_B24_AGREEMENT_NOTIFY'),
						null,
						{ time: 5 },
					);
				}
			});
		}

		getSender(senderId)
		{
			return this.senders.find((sender) => sender.id === senderId);
		}

		getSenderFromId(sender)
		{
			const fromList = BX.prop.getArray(sender, 'fromList', []);
			if (Type.isArrayFilled(fromList))
			{
				return fromList[0].id;
			}

			return null;
		}

		async copyLink()
		{
			await NotifyManager.showLoadingIndicator();

			const link = await this.getSharingLink();

			this.sendLinkAction({ isCopy: true, linkHash: link.hash }).then((response) => {
				NotifyManager.hideLoadingIndicator(true);
				Haptics.notifySuccess();
				this.onActivityCreate(response);
				this.close(() => copyToClipboard(link.url, Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_LINK_COPIED')));
			}).catch((error) => {
				NotifyManager.hideLoadingIndicator(false);
				Haptics.notifyFailure();
				// eslint-disable-next-line no-undef
				void ErrorNotifier.showError(error.errors[0].message);
			});
		}

		async getSharingLink()
		{
			if (this.getMemberIds().length > 1)
			{
				return this.saveJointLink();
			}

			return this.config.link;
		}

		async saveJointLink()
		{
			const response = await BX.ajax.runAction('crm.api.timeline.calendar.sharing.generateJointSharingLink', {
				data: {
					memberIds: this.getMemberIds(),
					entityId: this.entityId,
					entityTypeId: this.entityTypeId,
				},
			});

			return response.data;
		}

		async sendLink()
		{
			await NotifyManager.showLoadingIndicator();

			this.sendLinkAction().then((response) => {
				NotifyManager.hideLoadingIndicator(true);
				Haptics.notifySuccess();
				this.onActivityCreate(response);
				this.close();
			}).catch((response) => {
				NotifyManager.hideLoadingIndicator(false);
				Haptics.notifyFailure();
				// eslint-disable-next-line no-undef
				void ErrorNotifier.showError(response.errors[0].message);
			});
		}

		sendLinkAction({ isCopy, linkHash } = {})
		{
			const data = {
				ownerId: this.entityId,
				ownerTypeId: this.entityTypeId,
				ruleArray: this.getSharingRule(),
				memberIds: this.getMemberIds(),
			};

			let action;
			if (!isCopy && this.isChannelAvailable(this.sender))
			{
				action = 'crm.api.timeline.calendar.sharing.sendLink';
				data.contactId = this.communication.entityId || null;
				data.contactTypeId = this.communication.entityTypeId || null;
				data.channelId = this.sender.id || null;
				data.senderId = this.sender.fromId || null;
			}
			else
			{
				action = 'crm.api.timeline.calendar.sharing.onLinkCopied';
				data.linkHash = linkHash ?? this.config.link.hash;
			}

			return BX.ajax.runAction(action, { data });
		}

		getSharingRule()
		{
			const settings = this.sharing.getModel().getSettings();
			if (settings)
			{
				const slotSize = settings.rule.slotSize;
				const ranges = settings.rule.ranges.map((range) => ({
					from: range.from,
					to: range.to,
					weekdays: range.getWeekDays(),
				}));

				return { ranges, slotSize };
			}

			return this.config.link.rule;
		}

		getMemberIds()
		{
			return this.sharing.getModel().getMembers()?.map((member) => member.id) ?? [];
		}

		showWarnings()
		{
			if (!this.hasDealContacts())
			{
				this.showWarningNoContacts();

				return true;
			}

			if (!this.isChannelAvailable(this.sender) && this.hasPhoneWithoutChannels())
			{
				this.showWarningNoCommunicationChannels();

				return true;
			}

			if (!this.isChannelAvailable(this.sender) && this.hasEmailWithoutChannels())
			{
				void this.connectMailbox();

				return true;
			}

			return false;
		}

		hasDealContacts()
		{
			return Type.isArrayFilled(this.dealContacts);
		}

		hasChannels()
		{
			return Type.isArrayFilled(this.senders);
		}

		hasPhoneWithoutChannels()
		{
			if (!this.sender)
			{
				return true;
			}

			const phoneContacts = this.dealContacts.filter((contact) => contact.typeId === 'PHONE');
			const phoneChannels = this.senders.filter((channel) => channel.typeId === 'PHONE');
			const channelUnavailable = this.sender.typeId === 'PHONE' && !this.isChannelAvailable(this.sender);

			return Type.isArrayFilled(phoneContacts) && !Type.isArrayFilled(phoneChannels) || channelUnavailable;
		}

		hasEmailWithoutChannels()
		{
			if (!this.sender)
			{
				return true;
			}

			const mailContacts = this.dealContacts.filter((contact) => contact.typeId === 'EMAIL');
			const mailChannels = this.senders.filter((channel) => channel.typeId === 'EMAIL');
			const channelUnavailable = this.sender.typeId === 'EMAIL' && !this.isChannelAvailable(this.sender);

			return Type.isArrayFilled(mailContacts) && !Type.isArrayFilled(mailChannels) || channelUnavailable;
		}

		isChannelAvailable(channel)
		{
			return Type.isArrayFilled(channel?.fromList) && Type.isArrayFilled(channel?.contacts);
		}

		showWarningNoContacts()
		{
			Alert.alert(
				Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_NO_CONTACT_WARNING_TITLE'),
				Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_NO_CONTACT_WARNING_DESC_V2'),
			);
		}

		showWarningNoCommunicationChannels()
		{
			confirmDefaultAction({
				title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_NO_CHANNEL_SMS_WARNING_TITLE'),
				description: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_NO_CHANNEL_SMS_WARNING_DESC'),
				cancelButtonText: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_OK'),
				actionButtonText: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_CONNECT'),
				onAction: () => qrauth.open({
					redirectUrl: this.smsConfig.contactCenterUrl,
					layout: this.layout,
					analyticsSection: 'crm',
				}),
			});
		}

		connectMailbox(layoutWidget = null)
		{
			return new Promise((resolve) => {
				showEmailBanner(layoutWidget ?? this.layout, async () => {
					await this.fetchSettings();

					resolve(this.senders);
				});
			});
		}
	}

	const icons = {
		menu: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.98645 17.06H9.21182L8.23981 19.1428H4.98755C4.43616 19.1428 3.98866 18.6762 3.98866 18.1012V7.68492C3.98466 7.63075 3.98267 7.57763 3.98267 7.5245C3.98466 6.45996 4.81375 5.59958 5.83462 5.60166H6.98534V6.12247C6.98534 6.98494 7.6556 7.68492 8.48369 7.68492C9.31177 7.68492 9.98203 6.98494 9.98203 6.12247V5.60166H13.0353V6.12247C13.0353 6.98494 13.7066 7.68492 14.5337 7.68492C15.3608 7.68492 16.032 6.98494 16.032 6.12247V5.60166H17.2856C18.3364 5.66832 19.1505 6.586 19.1315 7.68492V11.4391L17.1337 9.77429V8.78601H5.98645V17.06ZM9.21787 5.91661V4.77081C9.21987 4.34791 8.89423 4.00314 8.48868 4.00001C8.08313 3.99793 7.75149 4.33854 7.7495 4.7604V4.77081V5.91661C7.7495 6.33951 8.07813 6.6822 8.48368 6.6822C8.88924 6.6822 9.21787 6.33951 9.21787 5.91661ZM15.2268 5.8845V4.79809C15.2268 4.3981 14.9161 4.0752 14.5336 4.0752C14.151 4.0752 13.8403 4.3981 13.8403 4.79809V5.88346C13.8403 6.28241 14.149 6.60635 14.5326 6.60739C14.9161 6.60739 15.2268 6.28345 15.2268 5.8845ZM8.27853 10.6141C8.00239 10.6141 7.77853 10.838 7.77853 11.1141V12.0038C7.77853 12.28 8.00239 12.5038 8.27853 12.5038H9.16828C9.44442 12.5038 9.66828 12.28 9.66828 12.0038V11.1141C9.66828 10.838 9.44442 10.6141 9.16828 10.6141H8.27853ZM10.6132 11.1514C10.6132 10.8752 10.837 10.6514 11.1132 10.6514H12.0029C12.279 10.6514 12.5029 10.8752 12.5029 11.1514V12.0411C12.5029 12.3173 12.279 12.5411 12.0029 12.5411H11.1132C10.837 12.5411 10.6132 12.3173 10.6132 12.0411V11.1514ZM16.4789 11.6589C16.4789 11.4794 16.709 11.3847 16.8534 11.5048L21.9011 15.7052C22.033 15.815 22.033 16.0063 21.9011 16.1161L16.8534 20.3165C16.709 20.4366 16.4789 20.3419 16.4789 20.1624V17.4554C16.4516 17.4656 16.4217 17.4712 16.3905 17.4712C13.4666 17.4715 10.9312 19.396 9.88984 20.3121C9.73029 20.4525 9.46783 20.3301 9.51593 20.1314C9.92287 18.4505 11.4514 14.1046 16.3961 13.9748C16.4252 13.974 16.4531 13.9786 16.4789 13.9875V11.6589Z" fill="#525C69"/></svg>',
	};

	setTimeout(() => tariffPlanRestrictionsReady(), 2000);

	module.exports = { TimelineSchedulerSharingProvider };
});
