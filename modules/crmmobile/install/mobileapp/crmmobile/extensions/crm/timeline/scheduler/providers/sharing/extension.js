/**
 * @module crm/timeline/scheduler/providers/sharing
 */
jn.define('crm/timeline/scheduler/providers/sharing', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { settings } = require('assets/common');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const { SettingsMenu } = require('crm/timeline/scheduler/providers/sharing/settings-menu');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Alert } = require('alert');
	const { Type: CrmType, TypeId } = require('crm/type');
	const { get } = require('utils/object');
	const { withPressed } = require('utils/color');
	const { Haptics } = require('haptics');
	const { BottomSheet } = require('bottom-sheet');
	const { EventEmitter } = require('event-emitter');
	const isAndroid = Application.getPlatform() === 'android';

	let Sharing = null;
	let SharingContext = null;
	let DialogSharing = null;
	let NotificationServiceConsent = null;

	try
	{
		Sharing = require('calendar/sharing').Sharing;
		SharingContext = require('calendar/sharing').SharingContext;
		DialogSharing = require('calendar/layout/dialog/dialog-sharing').DialogSharing;
	}
	catch (e)
	{
		console.warn(e, 'Calendar extensions not found');

		return null;
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
			this.isLoading = true;

			this.sharing = new Sharing({
				type: SharingContext.CRM,
			});

			// eslint-disable-next-line no-undef
			this.uid = Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.howItWorksRef = null;

			this.openSettingsMenu = this.openSettingsMenu.bind(this);
			this.openSharingDialog = this.openSharingDialog.bind(this);
			this.onSendButtonClick = this.onSendButtonClick.bind(this);
			this.onContactPhoneSelect = this.onContactPhoneSelect.bind(this);
			this.onChangeSender = this.onChangeSender.bind(this);
			this.onChangeSenderPhone = this.onChangeSenderPhone.bind(this);
			this.onRuleSave = this.onRuleSave.bind(this);
		}

		componentDidMount()
		{
			super.componentDidMount();
			this.setSettingsButton();
			this.bindEvents();
			this.fetchSettings();
			this.initSharingDialog();
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

		get entityId()
		{
			return this.props.entity.id;
		}

		get entityTypeId()
		{
			return this.props.entity.typeId;
		}

		get linkHash()
		{
			return this.config.link.hash;
		}

		fetchSettings()
		{
			const data = {
				entityTypeId: this.entityTypeId,
				entityId: this.entityId,
			};

			this.isLoading = true;

			BX.ajax.runAction('crm.timeline.calendar.sharing.getConfig', { data })
				.then((response) => {
					this.isLoading = false;
					this.setDefaultConfig(response.data);
				})
				.catch((response) => {
					// eslint-disable-next-line no-undef
					ErrorNotifier.showError(response.errors[0].message);
				})
			;
		}

		setDefaultConfig(data)
		{
			const { config, smsConfig } = data;

			if (config)
			{
				this.config = config;
			}

			if (smsConfig)
			{
				this.smsConfig = smsConfig;
			}

			this.setSenders();
			this.setCommunications();
		}

		initSharingDialog()
		{
			// eslint-disable-next-line promise/catch-or-return
			this.sharing.initCrm(this.entityTypeId, this.entityId)
				.then(() => {
					if (this.howItWorksRef)
					{
						this.howItWorksRef.animate({ opacity: 1, duration: 300 });
					}
				})
			;
		}

		setSenders()
		{
			this.senders = this.smsConfig.senders.filter((sender) => sender.canUse) || [];

			if (Type.isNil(this.config.selectedChannelId))
			{
				this.sender = Type.isArrayFilled(this.senders) ? this.senders[0] : null;
			}
			else
			{
				this.sender = this.getSender(this.config.selectedChannelId) ?? this.senders[0];
			}

			if (this.sender)
			{
				this.sender.fromPhoneId = this.getSenderFromPhoneId(this.sender);
			}
		}

		isSenderBitrix24(sender)
		{
			return sender?.id === 'bitrix24';
		}

		getSender(senderId)
		{
			return this.senders.find((sender) => {
				return sender.id === senderId;
			});
		}

		getSenderFromPhoneId(sender)
		{
			const fromList = BX.prop.getArray(sender, 'fromList', []);
			if (Type.isArrayFilled(fromList))
			{
				return fromList[0].id;
			}

			return null;
		}

		setCommunications()
		{
			this.communications = this.smsConfig.communications.filter((communication) => communication.entityId
				&& communication.entityTypeId
				&& communication.caption
				&& Type.isArrayFilled(communication.phones));

			this.communication = this.getCommunication(this.communication?.entityTypeId, this.communication?.entityId)
				?? this.communications[0]
			;

			if (this.communication && Type.isArrayFilled(this.communication.phones))
			{
				const phone = this.communication.phones[0];
				this.communication.toPhoneId = phone.id;
				this.communication.toPhoneValue = phone.value;
			}
		}

		getCommunication(entityTypeId, entityId)
		{
			return this.communications.find((communication) => {
				return communication.entityTypeId === entityTypeId && communication.entityId === entityId;
			});
		}

		setSettingsButton()
		{
			const buttons = [];

			buttons.push({
				style: {
					width: 24,
					height: 24,
				},
				svg: {
					content: settings({ color: AppTheme.colors.base4 }),
				},
				type: 'options',
				callback: this.openSettingsMenu,
			});

			this.layout.setRightButtons(buttons);
		}

		openSettingsMenu()
		{
			if (this.isLoading)
			{
				return;
			}

			if (!this.isContactAvailable())
			{
				this.showWarningNoContacts();

				return;
			}

			if (!this.isChannelsAvailable())
			{
				this.showWarningNoCommunicationChannels();

				return;
			}

			const { entity } = this.props;

			const menu = new SettingsMenu({
				entity,
				layout: this.layout,
				areCommunicationChannelsAvailable: this.config.areCommunicationChannelsAvailable && this.isChannelsAvailable(),
				communications: this.communications,
				currentCommunication: this.communication,
				senders: this.senders,
				currentSender: this.sender,
				contactCenterUrl: this.smsConfig.contactCenterUrl,
				onContactPhoneSelect: this.onContactPhoneSelect,
				onChangeSender: this.onChangeSender,
				onChangeSenderPhone: this.onChangeSenderPhone,
			});

			menu.show(this.layout);
		}

		onContactPhoneSelect({ communication })
		{
			this.communication = this.getCommunication(CrmType.resolveIdByName(communication.type), communication.id);
			this.communication.toPhoneId = communication.phone.id || null;
			this.communication.toPhoneValue = communication.phone.value || null;
		}

		onChangeSender({ sender, phoneId })
		{
			this.sender = this.getSender(sender.id);
			this.sender.fromPhoneId = phoneId;
		}

		onChangeSenderPhone({ phoneId })
		{
			this.sender.fromPhoneId = phoneId;
		}

		isContactAvailable()
		{
			return Type.isArrayFilled(this.communications);
		}

		isChannelsAvailable()
		{
			return Type.isArrayFilled(this.senders);
		}

		openSharingDialog()
		{
			if (this.isLoading)
			{
				return;
			}

			const component = new DialogSharing({
				sharing: this.sharing,
				readOnly: !this.config.isResponsible,
				customEventEmitter: this.customEventEmitter,
				onSharing: (fields) => {
					this.sharing.getModel().setFields(fields);
				},
			});

			(new BottomSheet({ component })
				.setMediumPositionPercent(80)
				.setParentWidget(this.layout)
				.disableContentSwipe()
				.open()
				.then((widget) => component.setLayoutWidget(widget)))
				.catch(console.error);
		}

		onSendButtonClick()
		{
			if (!this.isContactAvailable())
			{
				this.showWarningNoContacts();

				return;
			}

			if (
				!this.config.areCommunicationChannelsAvailable
				&& this.config.isNotificationsAvailable
				&& this.isChannelsAvailable()
				&& NotificationServiceConsent
			)
			{
				this.showConsentAndSend();

				return;
			}

			if (!this.config.areCommunicationChannelsAvailable)
			{
				this.showWarningNoCommunicationChannels();

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

			this.send();
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
							this.sender.fromPhoneId = this.getSenderFromPhoneId(this.sender);
						}
						else
						{
							this.showWarningNoCommunicationChannels();

							return;
						}
					}

					this.send();
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

		showWarningNoContacts()
		{
			Alert.alert(
				Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_NO_CONTACT_WARNING_TITLE'),
				Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_NO_CONTACT_WARNING_DESC'),
			);
		}

		showWarningNoCommunicationChannels()
		{
			Alert.alert(
				Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_NO_CHANNEL_SMS_WARNING_TITLE'),
				Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_NO_CHANNEL_SMS_WARNING_DESC'),
			);
		}

		send()
		{
			let action = '';

			const ajaxData = {
				ownerId: this.entityId,
				ownerTypeId: this.entityTypeId,
				ruleArray: this.getSharingRule(),
			};

			if (this.isContactAvailable() && this.isChannelsAvailable())
			{
				action = 'crm.api.timeline.calendar.sharing.sendLink';
				ajaxData.contactId = this.communication.entityId || null;
				ajaxData.contactTypeId = this.communication.entityTypeId || null;
				ajaxData.channelId = this.sender.id || null;
				ajaxData.senderId = this.sender.fromPhoneId || null;
			}
			else
			{
				action = 'crm.api.timeline.calendar.sharing.onLinkCopied';
				ajaxData.linkHash = this.config.link.hash;
			}

			BX.ajax.runAction(action, { data: ajaxData })
				.then((response) => {
					Haptics.notifySuccess();
					this.onActivityCreate(response);
					this.close();
				})
				.catch((response) => {
					// eslint-disable-next-line no-undef
					void ErrorNotifier.showError(response.errors[0].message);
				});
		}

		onRuleSave()
		{
			const ajaxData = {
				linkHash: this.linkHash,
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

		getSharingRule()
		{
			if (this.sharing.getModel()?.settings)
			{
				const settings = this.sharing.getModel().settings;
				const slotSize = settings.rule.slotSize;
				const ranges = [];

				settings.rule.ranges.forEach((range) => {
					ranges.push({
						from: range.from,
						to: range.to,
						weekdays: range.weekDays,
					});
				});

				return {
					ranges,
					slotSize,
				};
			}

			return this.config.link.rule;
		}

		static getId()
		{
			return 'sharing';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_TITLE');
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
			return icons.menu;
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
			const entityTypeId = get(detailCardParams, 'entityTypeId', 0)
			const isCalendarSharingEnabled = get(detailCardParams, 'isCalendarSharingEnabled', true);

			return entityTypeId === TypeId.Deal && isCalendarSharingEnabled;
		}

		static isSupported(context = {})
		{
			return true;
		}

		static getBackdropParams()
		{
			return {
				showOnTop: false,
				horizontalSwipeAllowed: false,
				onlyMediumPosition: true,
				mediumPositionPercent: TimelineSchedulerSharingProvider.getMediumPositionPercent(),
				helpUrl: helpdesk.getArticleUrl('18313490'),
			};
		}

		static getScreenHeight()
		{
			return get(device.screen, 'height', 0);
		}

		static getMediumPositionPercent()
		{
			const height = this.getScreenHeight();

			return (height > 800 ? 65 : 90);
		}

		static getMenuBadges()
		{
			const hideAfterDate = Date.now(2023, 12, 31);
			if (Date.now() > hideAfterDate)
			{
				return [];
			}

			return [
				{
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_BADGE_NEW_TITLE'),
					backgroundColor: AppTheme.colors.accentBrandBlue,
					color: AppTheme.colors.baseWhiteFixed,
				},
			];
		}

		render()
		{
			return View(
				{
					style: styles.container,
				},
				this.renderContent(),
				this.renderSendButton(),
			);
		}

		renderContent()
		{
			return View(
				{
					style: styles.content,
				},
				this.renderSharingIcon(),
				this.renderTitle(),
				this.renderInfoItemContainer(),
				this.renderHowItWorks(),
			);
		}

		renderSharingIcon()
		{
			return Image(
				{
					svg: {
						content: icons.sharing,
					},
					style: styles.sharingIcon,
				},
			);
		}

		renderTitle()
		{
			return View(
				{
					style: styles.titleContainer,
				},
				Text(
					{
						style: styles.title,
						text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_TITLE_TEXT'),
					},
				),
			);
		}

		renderInfoItemContainer()
		{
			return View(
				{
					style: {
						marginTop: 20,
					},
				},
				this.renderInfoItem(Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_DESC_1')),
				this.renderInfoItem(Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_DESC_2')),
			);
		}

		renderInfoItem(text)
		{
			return View(
				{
					style: styles.infoItemContainer,
				},
				Image(
					{
						svg: {
							content: icons.check,
						},
						style: {
							width: 20,
							height: 20,
						},
					},
				),
				Text(
					{
						style: styles.infoItemText,
						text,
					},
				),
			);
		}

		renderHowItWorks()
		{
			return View(
				{
					style: styles.howItWorksContainer,
					onClick: this.openSharingDialog,
					ref: (ref) => {
						this.howItWorksRef = ref;
					},
				},
				Image(
					{
						tintColor: AppTheme.colors.base3,
						svg: {
							content: icons.qrCode,
						},
						style: {
							width: 22,
							height: 22,
						},
					},
				),
				View(
					{
						style: styles.howItWorksTextContainer,
					},
					Text(
						{
							style: styles.howItWorksText,
							text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_HOW_IT_WORKS'),
						},
					),
				),
			);
		}

		renderSendButton()
		{
			return View(
				{
					style: styles.sendButtonOuterContainer,
				},
				View(
					{
						style: styles.sendButtonContainer(true),
						onClick: this.onSendButtonClick,
					},
					Text(
						{
							style: styles.sendButtonText,
							text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SHARING_SEND_LINK'),
						},
					),
					Image(
						{
							tintColor: AppTheme.colors.baseWhiteFixed,
							svg: {
								content: icons.send,
							},
							style: styles.sendButtonImage,
						},
					),
				),
			);
		}
	}

	const styles = {
		container: {
			flexDirection: 'column',
			flex: 1,
		},
		content: {
			flex: 1,
			paddingTop: 5,
			flexDirection: 'column',
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderRadius: 12,
			alignItems: 'center',
		},
		sharingIcon: {
			marginTop: 10,
			width: 123,
			height: 124,
		},
		titleContainer: {
			marginTop: 22,
			paddingHorizontal: isAndroid ? 55 : 70,
		},
		title: {
			fontSize: 18,
			fontWeight: '500',
			textAlign: 'center',
		},
		infoItemContainer: {
			flexDirection: 'row',
			marginTop: 5,
			marginBottom: 5,
			paddingHorizontal: 10,
		},
		infoItemText: {
			marginLeft: 10,
			color: AppTheme.colors.base0,
			fontSize: 15,
			fontWeight: '400',
		},
		howItWorksContainer: {
			flexDirection: 'row',
			alignItems: 'center',
			marginTop: 30,
			opacity: 0,
		},
		howItWorksTextContainer: {
			borderBottomWidth: 1,
			borderBottomColor: AppTheme.colors.base3,
			borderStyle: 'dash',
			borderDashSegmentLength: 3,
			borderDashGapLength: 3,
			marginLeft: 4,
		},
		howItWorksText: {
			color: AppTheme.colors.base3,
			fontSize: 14,
			fontWeight: '400',
		},
		sendButtonOuterContainer: {
			marginTop: 21,
			paddingHorizontal: 47,
			justifyContent: 'center',
			paddingBottom: 40,
		},
		sendButtonContainer: (active) => {
			return {
				borderRadius: 24,
				backgroundColor: (active ? withPressed(AppTheme.colors.accentMainSuccess) : AppTheme.colors.base5),
				paddingVertical: 11,
				paddingHorizontal: 37,
				justifyContent: 'center',
				alignContent: 'center',
				flexDirection: 'row',
			};
		},
		sendButtonText: {
			color: AppTheme.colors.baseWhiteFixed,
			fontWeight: '500',
			fontSize: 17,
			textAlign: 'center',
		},
		sendButtonImage: {
			width: 28,
			height: 28,
		},
	};

	const icons = {
		sharing: '<svg width="123" height="124" viewBox="0 0 123 124" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="61.2215" cy="61.2215" r="53.3552" fill="#ECFAFE" stroke="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M98.201 11.0676C98.202 11.034 98.2119 11.0024 98.2119 10.9688C98.2119 9.25549 96.7653 7.86628 94.9809 7.86628C93.6836 7.86628 92.573 8.60535 92.0582 9.66554C91.6462 9.52029 91.2075 9.4284 90.7431 9.4284C88.687 9.4284 87.0231 10.9876 86.9282 12.939C86.8492 12.9331 86.7721 12.9163 86.6911 12.9163C85.0213 12.9163 83.6686 14.2185 83.6686 15.8251C83.6686 17.4327 85.0213 18.7349 86.6911 18.7349C86.9588 18.7349 87.2138 18.6905 87.4618 18.6282C87.6554 18.6915 87.86 18.7349 88.0763 18.7349H96.2357C96.4037 18.7349 96.5648 18.7073 96.7199 18.6687C96.9452 18.7063 97.1754 18.7349 97.4125 18.7349C99.6446 18.7349 101.454 17.0019 101.454 14.8627C101.454 12.9834 100.056 11.4193 98.201 11.0676Z" fill="#C3F0FF"/><path fill-rule="evenodd" clip-rule="evenodd" d="M41.6371 105.159C41.6371 101.19 38.2781 97.9709 34.1345 97.9709C31.122 97.9709 28.5431 99.6833 27.3477 102.14C26.391 101.803 25.3723 101.59 24.2939 101.59C19.5194 101.59 15.6557 105.203 15.4354 109.724C15.2518 109.71 15.0729 109.671 14.8847 109.671C11.0073 109.671 7.86628 112.689 7.86628 116.411C7.86628 120.136 11.0073 123.153 14.8847 123.153C15.5065 123.153 17.599 123.153 18.1014 123.153H37.0483C37.4384 123.153 39.2303 123.153 39.7809 123.153C44.9639 123.153 49.1649 119.138 49.1649 114.181C49.1649 109.827 45.9183 106.203 41.6118 105.388C41.6141 105.31 41.6371 105.237 41.6371 105.159Z" fill="#C3F0FF"/><path opacity="0.2" d="M112.924 106.136C112.925 106.084 112.941 106.035 112.941 105.983C112.941 103.335 110.69 101.188 107.915 101.188C105.897 101.188 104.169 102.331 103.368 103.969C102.727 103.745 102.045 103.603 101.323 103.603C98.1241 103.603 95.5358 106.012 95.3883 109.028C95.2653 109.019 95.1454 108.993 95.0194 108.993C92.4219 108.993 90.3177 111.006 90.3177 113.488C90.3177 115.973 92.4219 117.985 95.0194 117.985C95.4359 117.985 95.8324 117.917 96.2182 117.821C96.5195 117.918 96.8376 117.985 97.1742 117.985H109.867C110.128 117.985 110.378 117.943 110.62 117.883C110.97 117.941 111.328 117.985 111.697 117.985C115.169 117.985 117.983 115.307 117.983 112.001C117.983 109.097 115.809 106.68 112.924 106.136" stroke="white"/><path opacity="0.6503" d="M19.5533 7.06792C19.5544 7.03127 19.5654 6.99678 19.5654 6.96013C19.5654 5.09108 17.9582 3.57558 15.9754 3.57558C14.534 3.57558 13.3 4.38184 12.728 5.53841C12.2702 5.37996 11.7828 5.27971 11.2668 5.27971C8.98218 5.27971 7.13341 6.98061 7.02802 9.10943C6.94019 9.10297 6.85456 9.08464 6.76454 9.08464C4.90918 9.08464 3.40623 10.5053 3.40623 12.2579C3.40623 14.0116 4.90918 15.4323 6.76454 15.4323C7.06205 15.4323 7.3453 15.3838 7.62085 15.3159C7.83603 15.3849 8.06329 15.4323 8.30371 15.4323H17.3697C17.5563 15.4323 17.7353 15.4021 17.9077 15.3601C18.158 15.401 18.4138 15.4323 18.6772 15.4323C21.1573 15.4323 23.1674 13.5417 23.1674 11.2081C23.1674 9.15794 21.614 7.45165 19.5533 7.06792" stroke="#7FDEFC"/><path d="M17.2834 33.6105C17.2834 31.2408 19.2044 29.3198 21.5741 29.3198H95.1423C97.512 29.3198 99.433 31.2408 99.433 33.6105V88.5752H17.2834V33.6105Z" fill="#58CFF4"/><path d="M21.3334 35.8118C21.3334 34.9536 22.0291 34.2578 22.8874 34.2578H93.8487C94.707 34.2578 95.4027 34.9536 95.4027 35.8118V80.3082C95.4027 81.1665 94.707 81.8622 93.8487 81.8622H22.8874C22.0291 81.8622 21.3334 81.1665 21.3334 80.3082V35.8118Z" fill="#0AE989"/><path d="M21.3334 35.8118C21.3334 34.9536 22.0291 34.2578 22.8874 34.2578H93.8487C94.707 34.2578 95.4027 34.9536 95.4027 35.8118V80.3082C95.4027 81.1665 94.707 81.8622 93.8487 81.8622H22.8874C22.0291 81.8622 21.3334 81.1665 21.3334 80.3082V35.8118Z" fill="white" fill-opacity="0.6"/><path d="M7.86628 87.2284C7.86628 89.2118 9.47413 90.8196 11.4575 90.8196H105.279C107.262 90.8196 108.87 89.2118 108.87 87.2284C108.87 86.9805 108.669 86.7795 108.421 86.7795H8.31518C8.06726 86.7795 7.86628 86.9805 7.86628 87.2284Z" fill="#00ACE2"/><g filter="url(#filter0_d_2497_394620)"><path d="M43.157 48.7574C43.157 48.2051 43.6047 47.7574 44.157 47.7574H56.657C57.0485 47.7574 57.404 47.9858 57.5667 48.342L57.7663 48.779C58.0237 49.3427 58.0053 49.9939 57.7164 50.5422L57.581 50.7991C57.408 51.1274 57.0674 51.3329 56.6963 51.3329H44.157C43.6047 51.3329 43.157 50.8852 43.157 50.3329V48.7574Z" fill="#9DCF00"/></g><g filter="url(#filter1_d_2497_394620)"><path d="M59.6047 48.7574C59.6047 48.2051 60.0524 47.7574 60.6047 47.7574H73.1047C73.4962 47.7574 73.8517 47.9858 74.0143 48.342L74.2139 48.779C74.4714 49.3427 74.4529 49.9939 74.1641 50.5422L74.0287 50.7991C73.8557 51.1274 73.5151 51.3329 73.144 51.3329H60.6047C60.0524 51.3329 59.6047 50.8852 59.6047 50.3329V48.7574Z" fill="#55D0E0"/></g><g filter="url(#filter2_d_2497_394620)"><path d="M76.0523 48.7574C76.0523 48.2051 76.5001 47.7574 77.0523 47.7574H89.5524C89.9439 47.7574 90.2994 47.9858 90.462 48.342L90.6616 48.779C90.919 49.3427 90.9006 49.9939 90.6117 50.5422L90.4763 50.7991C90.3033 51.1274 89.9627 51.3329 89.5916 51.3329H77.0523C76.5001 51.3329 76.0523 50.8852 76.0523 50.3329V48.7574Z" fill="#FFA900"/></g><g filter="url(#filter3_d_2497_394620)"><path d="M25.9942 48.7574C25.9942 48.2051 26.4419 47.7574 26.9942 47.7574H40.1802C40.5653 47.7574 40.9161 47.9784 41.0823 48.3258L41.2825 48.7443C41.5613 49.327 41.5415 50.0085 41.2293 50.574L41.0956 50.8162C40.9196 51.135 40.5843 51.3329 40.2201 51.3329H26.9942C26.4419 51.3329 25.9942 50.8852 25.9942 50.3329V48.7574Z" fill="#2FC6F6"/></g><path opacity="0.4" d="M53.6448 84.3851C53.6448 84.0132 53.9463 83.7117 54.3182 83.7117H62.3985C62.7703 83.7117 63.0718 84.0132 63.0718 84.3851C63.0718 84.757 62.7703 85.0585 62.3984 85.0585H54.3182C53.9463 85.0585 53.6448 84.757 53.6448 84.3851Z" fill="white"/><rect x="25.9942" y="37.7458" width="65.0756" height="6.43605" rx="2" fill="white"/><rect x="25.9942" y="54.9086" width="15.0174" height="8.5814" rx="2" fill="white"/><rect x="43.157" y="54.9086" width="15.0174" height="8.5814" rx="2" fill="white"/><rect x="59.6047" y="54.9086" width="15.0174" height="8.5814" rx="2" fill="white"/><rect x="76.0523" y="54.9086" width="15.0174" height="8.5814" rx="2" fill="white"/><rect x="43.157" y="64.9202" width="15.0174" height="8.5814" rx="2" fill="white"/><path d="M43.157 76.9318C43.157 75.8273 44.0524 74.9318 45.157 74.9318H56.1744C57.279 74.9318 58.1744 75.8273 58.1744 76.9318V81.3679H43.157V76.9318Z" fill="white"/><rect x="25.9942" y="64.9202" width="15.0174" height="8.5814" rx="2" fill="white"/><rect x="28.1395" y="57.0539" width="7.15116" height="1.43023" rx="0.715116" fill="#EFF0F2"/><rect x="45.3023" y="57.0539" width="7.15116" height="1.43023" rx="0.715116" fill="#EFF0F2"/><rect x="61.75" y="57.0539" width="7.15116" height="1.43023" rx="0.715116" fill="#EFF0F2"/><rect x="78.9128" y="57.0539" width="5.00581" height="1.43023" rx="0.715116" fill="#EFF0F2"/><rect x="45.3023" y="67.0655" width="5.00581" height="1.43023" rx="0.715116" fill="#EFF0F2"/><rect x="45.3023" y="77.0772" width="6.43604" height="1.43024" rx="0.715118" fill="#EFF0F2"/><rect x="28.1395" y="67.0655" width="5.00582" height="1.43023" rx="0.715116" fill="#EFF0F2"/><g filter="url(#filter4_d_2497_394620)"><path d="M78 58.2738C78 53.8068 81.6212 50.1857 86.0881 50.1857H108.354C112.821 50.1857 116.442 53.8069 116.442 58.2738V105.2C116.442 109.667 112.821 113.288 108.354 113.288H86.0881C81.6212 113.288 78 109.667 78 105.2V58.2738Z" fill="#00A2D6"/></g><path d="M90.3458 53.0874H85.9229C83.1494 53.0874 80.9011 55.3357 80.9011 58.1091V105.366C80.9011 108.139 83.1494 110.387 85.9229 110.387H108.519C111.292 110.387 113.54 108.139 113.54 105.366V58.1091C113.54 55.3357 111.292 53.0874 108.519 53.0874H104.127C99.538 53.0874 94.9391 53.0874 90.3458 53.0874Z" fill="white"/><rect x="91.8638" y="55.2803" width="11.5545" height="3.00416" rx="1.50208" fill="#C1C7D1"/><rect opacity="0.33" x="89.553" y="107.275" width="15.0208" height="1.15544" rx="0.577722" fill="#434B58"/><path d="M91.837 102.093H90.8743V99.1024C90.8743 99.0199 90.8753 98.9186 90.8774 98.7984C90.8794 98.6758 90.8826 98.5509 90.8867 98.4237C90.8909 98.2941 90.895 98.1774 90.8992 98.0737C90.8763 98.1044 90.8296 98.1574 90.759 98.2328C90.6904 98.3059 90.626 98.3718 90.5658 98.4307L90.0424 98.9079L89.5781 98.2505L91.0456 96.9249H91.837V102.093Z" fill="#2FC6F6"/><path d="M95.4886 102.093H94.5259V99.1024C94.5259 99.0199 94.5269 98.9186 94.529 98.7984C94.5311 98.6758 94.5342 98.5509 94.5383 98.4237C94.5425 98.2941 94.5466 98.1774 94.5508 98.0737C94.5279 98.1044 94.4812 98.1574 94.4106 98.2328C94.342 98.3059 94.2776 98.3718 94.2174 98.4307L93.694 98.9079L93.2297 98.2505L94.6972 96.9249H95.4886V102.093Z" fill="#2FC6F6"/><path d="M96.8689 101.587C96.8689 101.366 96.9218 101.21 97.0278 101.121C97.1358 101.031 97.2656 100.986 97.4172 100.986C97.5647 100.986 97.6914 101.031 97.7973 101.121C97.9054 101.21 97.9594 101.366 97.9594 101.587C97.9594 101.799 97.9054 101.953 97.7973 102.047C97.6914 102.141 97.5647 102.188 97.4172 102.188C97.2656 102.188 97.1358 102.141 97.0278 102.047C96.9218 101.953 96.8689 101.799 96.8689 101.587ZM96.8689 98.6676C96.8689 98.4461 96.9218 98.2905 97.0278 98.201C97.1358 98.1114 97.2656 98.0667 97.4172 98.0667C97.5647 98.0667 97.6914 98.1114 97.7973 98.201C97.9054 98.2905 97.9594 98.4461 97.9594 98.6676C97.9594 98.882 97.9054 99.0364 97.7973 99.1306C97.6914 99.2225 97.5647 99.2685 97.4172 99.2685C97.2656 99.2685 97.1358 99.2225 97.0278 99.1306C96.9218 99.0364 96.8689 98.882 96.8689 98.6676Z" fill="#2FC6F6"/><path d="M100.96 102.093H99.997V99.1024C99.997 99.0199 99.9981 98.9186 100 98.7984C100.002 98.6758 100.005 98.5509 100.009 98.4237C100.014 98.2941 100.018 98.1774 100.022 98.0737C99.9991 98.1044 99.9524 98.1574 99.8818 98.2328C99.8132 98.3059 99.7488 98.3718 99.6886 98.4307L99.1651 98.9079L98.7009 98.2505L100.168 96.9249H100.96V102.093Z" fill="#2FC6F6"/><path d="M104.611 102.093H103.649V99.1024C103.649 99.0199 103.65 98.9186 103.652 98.7984C103.654 98.6758 103.657 98.5509 103.661 98.4237C103.665 98.2941 103.669 98.1774 103.674 98.0737C103.651 98.1044 103.604 98.1574 103.533 98.2328C103.465 98.3059 103.4 98.3718 103.34 98.4307L102.817 98.9079L102.353 98.2505L103.82 96.9249H104.611V102.093Z" fill="#2FC6F6"/><g filter="url(#filter5_d_2497_394620)"><path d="M85.0097 68.9486C85.0097 66.7394 86.8006 64.9486 89.0097 64.9486H106.273C108.482 64.9486 110.273 66.7394 110.273 68.9486V73.9098H85.0097V68.9486Z" fill="#FF7470"/><path d="M85.0097 73.1903H110.273V87.1127C110.273 89.3219 108.482 91.1127 106.273 91.1127H89.0097C86.8006 91.1127 85.0097 89.3219 85.0097 87.1127V73.1903Z" fill="#CEF3FF"/><ellipse opacity="0.5" cx="88.4338" cy="66.6934" rx="0.975829" ry="0.981156" fill="black" fill-opacity="0.269593"/><ellipse opacity="0.5" cx="107.025" cy="66.6934" rx="0.975829" ry="0.981156" fill="black" fill-opacity="0.269593"/><rect x="87.7818" y="63.4899" width="1.30111" height="3.53018" rx="0.303829" fill="#525C69"/><rect x="106.373" y="63.4899" width="1.3011" height="3.53018" rx="0.303829" fill="#525C69"/><path d="M86.7432 88.5866C86.7432 89.1389 87.1909 89.5866 87.7432 89.5866H107.537C108.089 89.5866 108.537 89.1389 108.537 88.5866V74.2151H86.7432V88.5866Z" fill="#F0F0F0"/><path d="M86.7432 87.8813C86.7432 88.161 86.9699 88.3877 87.2496 88.3877H108.03C108.31 88.3877 108.537 88.161 108.537 87.8813V73.1253H86.7432V87.8813Z" fill="white"/><rect opacity="0.161457" x="85.146" y="73.0735" width="24.9915" height="0.814656" fill="#0092C0"/><path fill-rule="evenodd" clip-rule="evenodd" d="M108.536 85.0075H105.465C105.185 85.0075 104.958 85.2342 104.958 85.5139V88.496L108.536 85.0075Z" fill="#F0F0F0"/></g><path fill-rule="evenodd" clip-rule="evenodd" d="M94.3158 76.7596C94.3158 76.6819 94.3788 76.6189 94.4565 76.6189H96.6646C96.7423 76.6189 96.8053 76.6819 96.8053 76.7596V78.9677C96.8053 79.0454 96.7423 79.1084 96.6646 79.1084H94.4565C94.3788 79.1084 94.3158 79.0454 94.3158 78.9677V76.7596ZM89.9382 76.7603C89.9382 76.6826 90.0012 76.6196 90.0789 76.6196H92.287C92.3647 76.6196 92.4277 76.6826 92.4277 76.7603V78.9684C92.4277 79.0461 92.3647 79.1091 92.287 79.1091H90.0789C90.0012 79.1091 89.9382 79.0461 89.9382 78.9684V76.7603ZM98.8365 76.6189C98.7589 76.6189 98.6959 76.6819 98.6959 76.7596V78.9677C98.6959 79.0454 98.7589 79.1084 98.8365 79.1084H101.045C101.122 79.1084 101.185 79.0454 101.185 78.9677V76.7596C101.185 76.6819 101.122 76.6189 101.045 76.6189H98.8365ZM103.072 76.7596C103.072 76.6819 103.135 76.6189 103.213 76.6189H105.421C105.499 76.6189 105.562 76.6819 105.562 76.7596V78.9677C105.562 79.0454 105.499 79.1084 105.421 79.1084H103.213C103.135 79.1084 103.072 79.0454 103.072 78.9677V76.7596ZM90.08 81.3802C90.0023 81.3802 89.9393 81.4431 89.9393 81.5208V83.7289C89.9393 83.8066 90.0023 83.8696 90.08 83.8696H92.2881C92.3658 83.8696 92.4288 83.8066 92.4288 83.7289V81.5208C92.4288 81.4431 92.3658 81.3802 92.2881 81.3802H90.08ZM94.3158 81.5208C94.3158 81.4431 94.3788 81.3802 94.4565 81.3802H96.6646C96.7423 81.3802 96.8053 81.4431 96.8053 81.5208V83.7289C96.8053 83.8066 96.7423 83.8696 96.6646 83.8696H94.4565C94.3788 83.8696 94.3158 83.8066 94.3158 83.7289V81.5208ZM98.8365 81.3802C98.7589 81.3802 98.6959 81.4431 98.6959 81.5208V83.7289C98.6959 83.8066 98.7589 83.8696 98.8365 83.8696H101.045C101.122 83.8696 101.185 83.8066 101.185 83.7289V81.5208C101.185 81.4431 101.122 81.3802 101.045 81.3802H98.8365Z" fill="#A8ADB4"/><defs><filter id="filter0_d_2497_394620" x="40.157" y="45.7574" width="20.79" height="9.57558" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="1"/><feGaussianBlur stdDeviation="1.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.294033 0 0 0 0 0.3875 0 0 0 0.09 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2497_394620"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2497_394620" result="shape"/></filter><filter id="filter1_d_2497_394620" x="56.6047" y="45.7574" width="20.79" height="9.57558" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="1"/><feGaussianBlur stdDeviation="1.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.294033 0 0 0 0 0.3875 0 0 0 0.09 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2497_394620"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2497_394620" result="shape"/></filter><filter id="filter2_d_2497_394620" x="73.0523" y="45.7574" width="20.79" height="9.57558" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="1"/><feGaussianBlur stdDeviation="1.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.294033 0 0 0 0 0.3875 0 0 0 0.09 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2497_394620"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2497_394620" result="shape"/></filter><filter id="filter3_d_2497_394620" x="22.9942" y="45.7574" width="21.4842" height="9.57558" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="1"/><feGaussianBlur stdDeviation="1.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0.294033 0 0 0 0 0.3875 0 0 0 0.09 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2497_394620"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2497_394620" result="shape"/></filter><filter id="filter4_d_2497_394620" x="72" y="48.1857" width="50.4418" height="75.1026" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="4"/><feGaussianBlur stdDeviation="3"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.213032 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2497_394620"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2497_394620" result="shape"/></filter><filter id="filter5_d_2497_394620" x="84.0097" y="63.4899" width="27.2631" height="29.6229" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="1"/><feGaussianBlur stdDeviation="0.5"/><feComposite in2="hardAlpha" operator="out"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.05 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_2497_394620"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_2497_394620" result="shape"/></filter></defs></svg>',
		menu: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.98645 17.06H9.21182L8.23981 19.1428H4.98755C4.43616 19.1428 3.98866 18.6762 3.98866 18.1012V7.68492C3.98466 7.63075 3.98267 7.57763 3.98267 7.5245C3.98466 6.45996 4.81375 5.59958 5.83462 5.60166H6.98534V6.12247C6.98534 6.98494 7.6556 7.68492 8.48369 7.68492C9.31177 7.68492 9.98203 6.98494 9.98203 6.12247V5.60166H13.0353V6.12247C13.0353 6.98494 13.7066 7.68492 14.5337 7.68492C15.3608 7.68492 16.032 6.98494 16.032 6.12247V5.60166H17.2856C18.3364 5.66832 19.1505 6.586 19.1315 7.68492V11.4391L17.1337 9.77429V8.78601H5.98645V17.06ZM9.21787 5.91661V4.77081C9.21987 4.34791 8.89423 4.00314 8.48868 4.00001C8.08313 3.99793 7.75149 4.33854 7.7495 4.7604V4.77081V5.91661C7.7495 6.33951 8.07813 6.6822 8.48368 6.6822C8.88924 6.6822 9.21787 6.33951 9.21787 5.91661ZM15.2268 5.8845V4.79809C15.2268 4.3981 14.9161 4.0752 14.5336 4.0752C14.151 4.0752 13.8403 4.3981 13.8403 4.79809V5.88346C13.8403 6.28241 14.149 6.60635 14.5326 6.60739C14.9161 6.60739 15.2268 6.28345 15.2268 5.8845ZM8.27853 10.6141C8.00239 10.6141 7.77853 10.838 7.77853 11.1141V12.0038C7.77853 12.28 8.00239 12.5038 8.27853 12.5038H9.16828C9.44442 12.5038 9.66828 12.28 9.66828 12.0038V11.1141C9.66828 10.838 9.44442 10.6141 9.16828 10.6141H8.27853ZM10.6132 11.1514C10.6132 10.8752 10.837 10.6514 11.1132 10.6514H12.0029C12.279 10.6514 12.5029 10.8752 12.5029 11.1514V12.0411C12.5029 12.3173 12.279 12.5411 12.0029 12.5411H11.1132C10.837 12.5411 10.6132 12.3173 10.6132 12.0411V11.1514ZM16.4789 11.6589C16.4789 11.4794 16.709 11.3847 16.8534 11.5048L21.9011 15.7052C22.033 15.815 22.033 16.0063 21.9011 16.1161L16.8534 20.3165C16.709 20.4366 16.4789 20.3419 16.4789 20.1624V17.4554C16.4516 17.4656 16.4217 17.4712 16.3905 17.4712C13.4666 17.4715 10.9312 19.396 9.88984 20.3121C9.73029 20.4525 9.46783 20.3301 9.51593 20.1314C9.92287 18.4505 11.4514 14.1046 16.3961 13.9748C16.4252 13.974 16.4531 13.9786 16.4789 13.9875V11.6589Z" fill="#525C69"/></svg>',
		check: `<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 10C20 15.5228 15.5228 20 10 20C4.47715 20 0 15.5228 0 10C0 4.47715 4.47715 0 10 0C15.5228 0 20 4.47715 20 10Z" fill="${AppTheme.colors.accentSoftBlue2}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M8.54786 11.0331L6.46936 8.9336L5 10.4178L8.4679 13.9207L8.46933 13.9192L8.5493 14L15 7.48419L13.5306 6L8.54786 11.0331Z" fill="${AppTheme.colors.accentBrandBlue}"/></svg>`,
		qrCode: '<svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6.875 6.87501H5.59166V5.59167H6.875V6.87501Z" fill="#959CA4"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4.95 3.66667H7.51666C8.22571 3.66667 8.8 4.24096 8.8 4.95001V7.51667C8.8 8.22571 8.22571 8.8 7.51666 8.8H4.95C4.24096 8.8 3.66666 8.22571 3.66666 7.51667V4.95001C3.66666 4.24096 4.24096 3.66667 4.95 3.66667ZM4.95 7.51667H7.51666V4.95001H4.95V7.51667Z" fill="#959CA4"/><path d="M6.875 16.4083H5.59166V15.125H6.875V16.4083Z" fill="#959CA4"/><path fill-rule="evenodd" clip-rule="evenodd" d="M4.95 13.2H7.51666C8.22571 13.2 8.8 13.7743 8.8 14.4833V17.05C8.8 17.759 8.22571 18.3333 7.51666 18.3333H4.95C4.24096 18.3333 3.66666 17.759 3.66666 17.05V14.4833C3.66666 13.7743 4.24096 13.2 4.95 13.2ZM4.95 17.05H7.51666V14.4833H4.95V17.05Z" fill="#959CA4"/><path d="M15.125 6.87501H16.4083V5.59167H15.125V6.87501Z" fill="#959CA4"/><path fill-rule="evenodd" clip-rule="evenodd" d="M17.05 3.66667H14.4833C13.7743 3.66667 13.2 4.24096 13.2 4.95001V7.51667C13.2 8.22571 13.7743 8.8 14.4833 8.8H17.05C17.759 8.8 18.3333 8.22571 18.3333 7.51667V4.95001C18.3333 4.24096 17.759 3.66667 17.05 3.66667ZM17.05 7.51667H14.4833V4.95001H17.05V7.51667Z" fill="#959CA4"/><path d="M11.0593 5.50001C10.824 5.50001 10.6333 5.6907 10.6333 5.92593V6.17409C10.6333 6.40931 10.824 6.60001 11.0593 6.60001H11.3074C11.5426 6.60001 11.7333 6.40931 11.7333 6.17409V5.92593C11.7333 5.6907 11.5426 5.50001 11.3074 5.50001H11.0593Z" fill="#959CA4"/><path d="M10.6333 8.12593C10.6333 7.8907 10.824 7.70001 11.0593 7.70001H11.3074C11.5426 7.70001 11.7333 7.8907 11.7333 8.12593V8.37408C11.7333 8.60931 11.5426 8.8 11.3074 8.8H11.0593C10.824 8.8 10.6333 8.60931 10.6333 8.37408V8.12593Z" fill="#959CA4"/><path d="M11.0593 10.6333C10.824 10.6333 10.6333 10.824 10.6333 11.0593V11.3074C10.6333 11.5426 10.824 11.7333 11.0593 11.7333H11.3074C11.5426 11.7333 11.7333 11.5426 11.7333 11.3074V11.0593C11.7333 10.824 11.5426 10.6333 11.3074 10.6333H11.0593Z" fill="#959CA4"/><path d="M7.7 11.0593C7.7 10.824 7.89069 10.6333 8.12592 10.6333H8.37408C8.60931 10.6333 8.8 10.824 8.8 11.0593V11.3074C8.8 11.5426 8.60931 11.7333 8.37408 11.7333H8.12592C7.89069 11.7333 7.7 11.5426 7.7 11.3074V11.0593Z" fill="#959CA4"/><path d="M5.92592 10.6333C5.69069 10.6333 5.5 10.824 5.5 11.0593V11.3074C5.5 11.5426 5.69069 11.7333 5.92592 11.7333H6.17408C6.40931 11.7333 6.6 11.5426 6.6 11.3074V11.0593C6.6 10.824 6.40931 10.6333 6.17408 10.6333H5.92592Z" fill="#959CA4"/><path d="M13.2 11.0593C13.2 10.824 13.3907 10.6333 13.6259 10.6333H13.8741C14.1093 10.6333 14.3 10.824 14.3 11.0593V11.3074C14.3 11.5426 14.1093 11.7333 13.8741 11.7333H13.6259C13.3907 11.7333 13.2 11.5426 13.2 11.3074V11.0593Z" fill="#959CA4"/><path d="M15.8259 10.6333C15.5907 10.6333 15.4 10.824 15.4 11.0593V11.3074C15.4 11.5426 15.5907 11.7333 15.8259 11.7333H16.0741C16.3093 11.7333 16.5 11.5426 16.5 11.3074V11.0593C16.5 10.824 16.3093 10.6333 16.0741 10.6333H15.8259Z" fill="#959CA4"/><path d="M11.7333 12.1593C11.7333 11.924 11.924 11.7333 12.1593 11.7333H12.4074C12.6426 11.7333 12.8333 11.924 12.8333 12.1593V12.4074C12.8333 12.6426 12.6426 12.8333 12.4074 12.8333H12.1593C11.924 12.8333 11.7333 12.6426 11.7333 12.4074V12.1593Z" fill="#959CA4"/><path d="M14.7259 11.7333C14.4907 11.7333 14.3 11.924 14.3 12.1593V12.4074C14.3 12.6426 14.4907 12.8333 14.7259 12.8333H14.9741C15.2093 12.8333 15.4 12.6426 15.4 12.4074V12.1593C15.4 11.924 15.2093 11.7333 14.9741 11.7333H14.7259Z" fill="#959CA4"/><path d="M13.2 13.2593C13.2 13.024 13.3907 12.8333 13.6259 12.8333H13.8741C14.1093 12.8333 14.3 13.024 14.3 13.2593V13.5074C14.3 13.7426 14.1093 13.9333 13.8741 13.9333H13.6259C13.3907 13.9333 13.2 13.7426 13.2 13.5074V13.2593Z" fill="#959CA4"/><path d="M11.0593 12.8333C10.824 12.8333 10.6333 13.024 10.6333 13.2593V13.5074C10.6333 13.7426 10.824 13.9333 11.0593 13.9333H11.3074C11.5426 13.9333 11.7333 13.7426 11.7333 13.5074V13.2593C11.7333 13.024 11.5426 12.8333 11.3074 12.8333H11.0593Z" fill="#959CA4"/><path d="M11.7333 14.7259C11.7333 14.4907 11.924 14.3 12.1593 14.3H12.4074C12.6426 14.3 12.8333 14.4907 12.8333 14.7259V14.9741C12.8333 15.2093 12.6426 15.4 12.4074 15.4H12.1593C11.924 15.4 11.7333 15.2093 11.7333 14.9741V14.7259Z" fill="#959CA4"/><path d="M13.6259 15.4C13.3907 15.4 13.2 15.5907 13.2 15.8259V16.0741C13.2 16.3093 13.3907 16.5 13.6259 16.5H13.8741C14.1093 16.5 14.3 16.3093 14.3 16.0741V15.8259C14.3 15.5907 14.1093 15.4 13.8741 15.4H13.6259Z" fill="#959CA4"/><path d="M11.7333 17.2926C11.7333 17.0574 11.924 16.8667 12.1593 16.8667H12.4074C12.6426 16.8667 12.8333 17.0574 12.8333 17.2926V17.5408C12.8333 17.776 12.6426 17.9667 12.4074 17.9667H12.1593C11.924 17.9667 11.7333 17.776 11.7333 17.5408V17.2926Z" fill="#959CA4"/><path d="M17.6593 11.7333C17.424 11.7333 17.2333 11.924 17.2333 12.1593V12.4074C17.2333 12.6426 17.424 12.8333 17.6593 12.8333H17.9074C18.1426 12.8333 18.3333 12.6426 18.3333 12.4074V12.1593C18.3333 11.924 18.1426 11.7333 17.9074 11.7333H17.6593Z" fill="#959CA4"/><path d="M17.2333 14.7259C17.2333 14.4907 17.424 14.3 17.6593 14.3H17.9074C18.1426 14.3 18.3333 14.4907 18.3333 14.7259V14.9741C18.3333 15.2093 18.1426 15.4 17.9074 15.4H17.6593C17.424 15.4 17.2333 15.2093 17.2333 14.9741V14.7259Z" fill="#959CA4"/><path d="M14.7259 14.3C14.4907 14.3 14.3 14.4907 14.3 14.7259V14.9741C14.3 15.2093 14.4907 15.4 14.7259 15.4H14.9741C15.2093 15.4 15.4 15.2093 15.4 14.9741V14.7259C15.4 14.4907 15.2093 14.3 14.9741 14.3H14.7259Z" fill="#959CA4"/><path d="M14.3 17.2926C14.3 17.0574 14.4907 16.8667 14.7259 16.8667H14.9741C15.2093 16.8667 15.4 17.0574 15.4 17.2926V17.5408C15.4 17.776 15.2093 17.9667 14.9741 17.9667H14.7259C14.4907 17.9667 14.3 17.776 14.3 17.5408V17.2926Z" fill="#959CA4"/><path d="M15.8259 15.4C15.5907 15.4 15.4 15.5907 15.4 15.8259V16.0741C15.4 16.3093 15.5907 16.5 15.8259 16.5H16.0741C16.3093 16.5 16.5 16.3093 16.5 16.0741V15.8259C16.5 15.5907 16.3093 15.4 16.0741 15.4H15.8259Z" fill="#959CA4"/><path d="M15.4 13.2593C15.4 13.024 15.5907 12.8333 15.8259 12.8333H16.0741C16.3093 12.8333 16.5 13.024 16.5 13.2593V13.5074C16.5 13.7426 16.3093 13.9333 16.0741 13.9333H15.8259C15.5907 13.9333 15.4 13.7426 15.4 13.5074V13.2593Z" fill="#959CA4"/><path d="M11.0593 15.4C10.824 15.4 10.6333 15.5907 10.6333 15.8259V16.0741C10.6333 16.3093 10.824 16.5 11.0593 16.5H11.3074C11.5426 16.5 11.7333 16.3093 11.7333 16.0741V15.8259C11.7333 15.5907 11.5426 15.4 11.3074 15.4H11.0593Z" fill="#959CA4"/></svg>',
		send: '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.1457 16.6134L21.2119 13.5483C22.9043 11.8554 22.9043 9.11044 21.2119 7.41757C19.5186 5.72462 16.774 5.72462 15.0807 7.41757L12.7581 9.74014C13.6858 9.60419 14.6387 9.69679 15.5232 10.0412L16.6132 8.95005C17.4589 8.10498 18.8337 8.10498 19.6784 8.95005C20.5242 9.7951 20.5242 11.17 19.6784 12.0157L16.6132 15.0808C15.7686 15.9254 14.3934 15.9266 13.5482 15.0808C13.2836 14.8161 13.1117 14.4975 13.0132 14.161C12.9978 14.1699 12.9823 14.1785 12.9669 14.187C12.9017 14.2232 12.8366 14.2593 12.7819 14.3145L11.3572 15.7391C11.5404 16.048 11.749 16.3476 12.0156 16.6134C13.7081 18.3067 16.4534 18.3067 18.1457 16.6134ZM12.0155 19.6795L13.1063 18.5884C13.9901 18.9322 14.9433 19.0255 15.8715 18.8888L13.5482 21.2119C11.8553 22.9043 9.11039 22.9043 7.41744 21.2119C5.72466 19.5186 5.72466 16.7744 7.41744 15.0809L10.4825 12.0158C12.1755 10.3228 14.921 10.3228 16.6131 12.0158C16.8799 12.2819 17.0895 12.5804 17.2715 12.8905L15.8469 14.3145C15.7933 14.3682 15.7303 14.4032 15.6664 14.4386C15.6497 14.4479 15.6329 14.4573 15.6163 14.467C15.5168 14.1309 15.3447 13.8124 15.0807 13.5483C14.2356 12.7026 12.8613 12.7026 12.0155 13.5483L8.94999 16.6134C8.10486 17.4589 8.10486 18.8337 8.94999 19.6795C9.7952 20.5242 11.1705 20.5242 12.0155 19.6795Z" fill="white"/></svg>',
	};

	module.exports = { TimelineSchedulerSharingProvider };
});
