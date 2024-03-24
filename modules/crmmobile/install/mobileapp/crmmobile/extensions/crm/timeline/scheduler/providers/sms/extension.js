/**
 * @module crm/timeline/scheduler/providers/sms
 */
jn.define('crm/timeline/scheduler/providers/sms', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { WidgetHeaderButton } = require('layout/ui/widget-header-button');
	const { TimelineSchedulerBaseProvider } = require('crm/timeline/scheduler/providers/base');
	const { Textarea } = require('crm/timeline/ui/textarea');
	const { ClientsSelector } = require('crm/timeline/scheduler/providers/sms/clients-selector');
	const { getEntityMessage } = require('crm/loc');
	const { CommunicationSelector } = require('crm/communication/communication-selector');
	const { Type: CrmType, TypeId } = require('crm/type');
	const { SendersSelector } = require('crm/timeline/ui/senders-selector');
	const { Haptics } = require('haptics');
	const { WarningBlock } = require('layout/ui/warning-block');
	const { MultiFieldDrawer, MultiFieldType } = require('crm/multi-field-drawer');
	const { clone, get } = require('utils/object');
	const { debounce } = require('utils/function');
	const { Line } = require('utils/skeleton');
	const { Type } = require('type');
	const { settings } = require('assets/common');
	const { ContextMenu } = require('layout/ui/context-menu');

	const MAX_SMS_LENGTH = 200;
	const MAX_HEIGHT = 1000;

	/**
	 * @class TimelineSchedulerSmsProvider
	 */
	class TimelineSchedulerSmsProvider extends TimelineSchedulerBaseProvider
	{
		static getId()
		{
			return 'sms';
		}

		static getTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_TITLE_2');
		}

		static getMenuTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_MENU_FULL_TITLE');
		}

		static getMenuShortTitle()
		{
			return Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_MENU_TITLE');
		}

		static getMenuIcon()
		{
			return `<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.75618 7.79004C6.32944 7.79004 5.17284 8.94665 5.17285 10.3734L5.17294 20.6003C5.17295 22.0271 6.32954 23.1837 7.75627 23.1837H9.93142V25.9041C9.93142 26.3726 10.5049 26.5992 10.8252 26.2573L13.7039 23.1837H23.3893C24.8161 23.1837 25.9727 22.027 25.9727 20.6003L25.9726 10.3734C25.9726 8.94662 24.816 7.79004 23.3892 7.79004H7.75618ZM11.1814 17.4841C11.3614 17.2089 11.4514 16.8939 11.4514 16.5392C11.4514 16.1531 11.3555 15.8329 11.1638 15.5786C10.9721 15.3243 10.6297 15.0863 10.1367 14.8646C9.62286 14.6298 9.30789 14.4681 9.19182 14.3794C9.07574 14.2907 9.0177 14.1903 9.0177 14.0781C9.0177 13.9738 9.06335 13.8864 9.15465 13.816C9.24594 13.7455 9.39201 13.7103 9.59286 13.7103C9.98152 13.7103 10.4432 13.8329 10.9779 14.0781L11.4514 12.8847C10.8358 12.6109 10.2319 12.4739 9.63982 12.4739C8.96944 12.4739 8.44255 12.6213 8.0591 12.916C7.67566 13.2108 7.48394 13.6216 7.48394 14.1485C7.48394 14.4302 7.52894 14.6741 7.61893 14.8802C7.70892 15.0863 7.84717 15.2689 8.03367 15.428C8.22018 15.5871 8.49993 15.7514 8.87294 15.921C9.28507 16.1062 9.53874 16.2288 9.63395 16.2888C9.72915 16.3487 9.79828 16.4081 9.84132 16.4668C9.88436 16.5255 9.90588 16.5939 9.90588 16.6722C9.90588 16.7974 9.8524 16.8998 9.74546 16.9793C9.63851 17.0589 9.47027 17.0987 9.24073 17.0987C8.97466 17.0987 8.68252 17.0563 8.36429 16.9715C8.04606 16.8867 7.73957 16.7687 7.44482 16.6174V17.9947C7.72392 18.1277 7.99259 18.221 8.25082 18.2744C8.50906 18.3279 8.82859 18.3546 9.20942 18.3546C9.6659 18.3546 10.0637 18.2783 10.4028 18.1257C10.7419 17.9731 11.0014 17.7593 11.1814 17.4841ZM13.6583 14.1559L14.8282 18.2759H16.2993L17.4536 14.1637H17.4888C17.4575 14.7846 17.4399 15.1745 17.4359 15.3336C17.432 15.4927 17.4301 15.6375 17.4301 15.7679V18.2759H18.8504V12.5556H16.7884L15.6185 16.617H15.5872L14.3939 12.5556H12.3358V18.2759H13.7052V15.7914C13.7052 15.4027 13.6778 14.8576 13.6231 14.1559H13.6583ZM23.8241 16.5392C23.8241 16.8939 23.7341 17.2089 23.5541 17.4841C23.3741 17.7593 23.1146 17.9731 22.7755 18.1257C22.4364 18.2783 22.0386 18.3546 21.5821 18.3546C21.2013 18.3546 20.8818 18.3279 20.6235 18.2744C20.3653 18.221 20.0966 18.1277 19.8175 17.9947V16.6174C20.1123 16.7687 20.4188 16.8867 20.737 16.9715C21.0552 17.0563 21.3474 17.0987 21.6134 17.0987C21.843 17.0987 22.0112 17.0589 22.1182 16.9793C22.2251 16.8998 22.2786 16.7974 22.2786 16.6722C22.2786 16.5939 22.2571 16.5255 22.214 16.4668C22.171 16.4081 22.1019 16.3487 22.0067 16.2888C21.9115 16.2288 21.6578 16.1062 21.2457 15.921C20.8727 15.7514 20.5929 15.5871 20.4064 15.428C20.2199 15.2689 20.0816 15.0863 19.9917 14.8802C19.9017 14.6741 19.8567 14.4302 19.8567 14.1485C19.8567 13.6216 20.0484 13.2108 20.4318 12.916C20.8153 12.6213 21.3422 12.4739 22.0125 12.4739C22.6047 12.4739 23.2085 12.6109 23.8241 12.8847L23.3507 14.0781C22.8159 13.8329 22.3542 13.7103 21.9656 13.7103C21.7647 13.7103 21.6187 13.7455 21.5274 13.816C21.4361 13.8864 21.3904 13.9738 21.3904 14.0781C21.3904 14.1903 21.4485 14.2907 21.5645 14.3794C21.6806 14.4681 21.9956 14.6298 22.5094 14.8646C23.0024 15.0863 23.3448 15.3243 23.5365 15.5786C23.7282 15.8329 23.8241 16.1531 23.8241 16.5392Z" fill="${AppTheme.colors.base3}"/></svg>`;
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
			const isCompany = entityTypeId === TypeId.Company;
			const isContact = entityTypeId === TypeId.Contact;
			const isClientEnabled = get(detailCardParams, 'isClientEnabled', false);

			return isCompany || isContact || isClientEnabled;
		}

		static isSupported(context = {})
		{
			return true;
		}

		static getBackdropParams()
		{
			return {
				showOnTop: false,
				onlyMediumPosition: true,
				mediumPositionPercent: 90,
			};
		}

		constructor(props)
		{
			super(props);

			this.state = {
				text: this.getInitialText(),
				toName: null,
				toPhoneId: null,
				fromPhoneId: null,
				entityTypeId: null,
				entityId: null,
				communications: null,
				sender: null,
				maxHeight: MAX_HEIGHT,
				templateId: null,
			};

			this.textInputRef = null;
			this.counterRef = null;
			this.sendersSelector = null;

			this.smsConfig = {};
			this.isFetchedConfig = false;

			this.sendButton = new WidgetHeaderButton({
				widget: this.layout,
				text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SEND'),
				loadingText: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SEND_PROGRESS'),
				disabled: !this.isSendAllowed(),
				onClick: () => this.send(),
			});

			this.openContactsSelector = this.openContactsSelector.bind(this);
			this.openSendersSelector = this.openSendersSelector.bind(this);
			this.addTemplatesToSender = this.addTemplatesToSender.bind(this);
			this.onChangeSenderCallback = this.onChangeSenderCallback.bind(this);
			this.onChangePhoneCallback = this.onChangePhoneCallback.bind(this);
			this.onPhoneSelectCallback = this.onPhoneSelectCallback.bind(this);
			this.onPhoneAddedSuccessCallback = this.onPhoneAddedSuccessCallback.bind(this);
			this.openTemplateSelector = this.openTemplateSelector.bind(this);
			this.refreshSendButton = this.refreshSendButton.bind(this);
			this.setDefaultConfig = this.setDefaultConfig.bind(this);
			this.onKeyboardToggleHandler = this.onKeyboardToggle.bind(this);
			this.debouncedTextChange = debounce((text) => this.onTextChange(text), 50, this);
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.fetchSettings();
			this.focus();
			this.refreshSendButton();

			Keyboard.on(Keyboard.Event.WillHide, this.onKeyboardToggleHandler);
			Keyboard.on(Keyboard.Event.WillShow, this.onKeyboardToggleHandler);
		}

		componentWillUnmount()
		{
			Keyboard.off(Keyboard.Event.WillHide, this.onKeyboardToggleHandler);
			Keyboard.off(Keyboard.Event.WillShow, this.onKeyboardToggleHandler);

			super.componentWillUnmount();
		}

		onKeyboardToggle()
		{
			this.setState({
				maxHeight: MAX_HEIGHT,
			});
		}

		fetchSettings()
		{
			const { entity } = this.props;
			const ajaxParameters = {
				entityTypeId: entity.typeId,
				entityId: entity.id,
			};

			this.isFetchedConfig = false;

			BX.ajax.runAction('crm.activity.sms.getConfig', { data: ajaxParameters })
				.then(({ data }) => {
					this.isFetchedConfig = true;
					this.setDefaultConfig(data);
				})
				.catch((response) => {
					void ErrorNotifier.showError(response.errors[0].message);
				});
		}

		fetchTemplatesList()
		{
			const { id: senderId } = this.state.sender;
			const ajaxParameters = { senderId };

			BX.ajax.runAction('crm.activity.sms.getTemplates', { data: ajaxParameters })
				.then(({ data }) => {
					if (data.templates.length === 0)
					{
						this.setState({
							sender: this.getFirstAvailableNotTemplateBasedSender(),
							templateId: null,
						}, () => {
							this.sendersSelector.close();
							this.sendersSelector = null;

							void ErrorNotifier.showError(Loc.getMessage(
								'M_CRM_TIMELINE_SCHEDULER_SMS_EMPTY_TEMPLATES_LIST',
							));
						});
					}
					else
					{
						this.addTemplatesToSender(senderId, data.templates);
					}
				})
				.catch((response) => {
					void ErrorNotifier.showError(response.errors[0].message);
				})
			;
		}

		getFirstAvailableNotTemplateBasedSender()
		{
			return this.smsConfig.config.senders.find((sender) => sender.canUse && !sender.templatesBased);
		}

		addTemplatesToSender(senderId, templates)
		{
			const sender = this.findSenderById(senderId, this.smsConfig.config.senders);

			this.setState({
				sender: {
					...sender,
					templates,
				},
				templateId: 0,
			}, () => {
				sender.templates = templates;

				this.refreshSendButton();
			});
		}

		setDefaultConfig(smsConfig)
		{
			this.smsConfig = smsConfig;

			const communications = BX.prop.getArray(smsConfig.config, 'communications', []);
			const sender = this.getDefaultSender(smsConfig);

			const newState = {
				communications,
				sender,
				templateId: (this.isSenderTemplatesBased(sender) ? 0 : null),
			};

			this.appendDefaultFromPhoneId(newState, sender);
			this.appendFirstCommunicationData(newState, communications);

			this.setState(newState, () => {
				this.refreshSendButton();
				this.focus();
			});
		}

		appendDefaultFromPhoneId(data, sender)
		{
			const { defaults } = this.smsConfig.config;
			const fromList = BX.prop.getArray(sender, 'fromList', []);

			if (fromList.length > 0)
			{
				data.fromPhoneId = (
					defaults && fromList.some((item) => item.id === defaults.from)
						? defaults.from
						: fromList[0].id
				);
			}
		}

		appendFirstCommunicationData(data, communications)
		{
			if (communications.length > 0)
			{
				const communication = communications[0];
				data.toPhoneId = (
					Array.isArray(communication.phones)
					&& communication.phones.length > 0
					&& communication.phones[0].id
				);
				data.toName = communication.caption;
				data.entityId = communication.entityId;
				data.entityTypeId = communication.entityTypeId;
			}
		}

		getDefaultSender(data)
		{
			const { config } = data;
			const senderId = get(config, 'defaults.senderId', null);

			if (senderId)
			{
				return this.findSenderById(senderId, config.senders);
			}

			return this.findFirstAvailableSender(config.senders);
		}

		findSenderById(id, senders)
		{
			return senders.find((sender) => sender.id === id);
		}

		findFirstAvailableSender(senders)
		{
			return senders.find((sender) => sender.canUse);
		}

		getInitialText()
		{
			return BX.prop.getString(this.props.context, 'initialText', '');
		}

		focus()
		{
			if (this.textInputRef)
			{
				this.textInputRef.focus();
			}
		}

		render()
		{
			const { toName, maxHeight } = this.state;
			const { typeId: entityTypeId } = this.props.entity;

			const showNoNameWarningBlock = this.isFetchedConfig && !Type.isStringFilled(toName);
			const showNoProviderWarningBlock = this.isFetchedConfig && Type.isStringFilled(toName) && !this.hasProvider;

			return View(
				{
					style: styles.container,
					resizableByKeyboard: true,
				},
				View(
					{
						style: styles.containerInner,
					},
					showNoNameWarningBlock && this.renderWarningBlock({
						title: getEntityMessage('M_CRM_TIMELINE_SCHEDULER_SMS_NO_CLIENT_TITLE', entityTypeId),
						description: getEntityMessage('M_CRM_TIMELINE_SCHEDULER_SMS_NO_CLIENT_TEXT', entityTypeId),
					}),
					showNoProviderWarningBlock && this.renderWarningBlock({
						title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SETTINGS_EMPTY_PROVIDER_TITLE'),
						description: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SETTINGS_EMPTY_PROVIDER_TEXT'),
						layout: this.props.layout,
						redirectUrl: this.smsConfig.contactCenterUrl,
						redirectUrlTitle: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_SETTINGS_CONTACT_CENTER'),
					}),
					View(
						{
							style: styles.wrapper(maxHeight),
							onLayout: ({ height }) => this.setMaxHeight(height),
						},
						this.renderSettingsSection(),
						this.renderBody(),
					),
				),
			);
		}

		setMaxHeight(height)
		{
			const { maxHeight } = this.state;
			const newMaxHeight = Math.ceil(Math.min(height, maxHeight));

			if (newMaxHeight < maxHeight)
			{
				this.setState({ maxHeight: newMaxHeight });
			}
		}

		renderWarningBlock({ title, description, redirectUrl, redirectUrlTitle, layout })
		{
			return View(
				{
					style: styles.warningBlock,
				},
				new WarningBlock({
					title,
					description,
					redirectUrl,
					redirectUrlTitle,
					layout,
				}),
			);
		}

		renderSettingsSection()
		{
			return View(
				{
					style: styles.settingsSection,
				},
				this.renderSettingsIcon(),
				this.renderClientSelector(),
			);
		}

		renderClientSelector()
		{
			const { toName: name, toPhoneId: phoneId } = this.state;

			const phone = this.getPhoneById(phoneId);
			const hasOnlyManyClientsWithoutPhones = this.hasOnlyManyClientsWithoutPhones();

			return View(
				{
					style: styles.clientSelector,
				},
				new ClientsSelector({
					name,
					phone: (phone ? phone.valueFormatted : null),
					showSkeleton: !this.isFetchedConfig,
					hasOnlyManyClientsWithoutPhones,
					onOpenClientsWithoutPhonesSelector: this.openClientsWithoutPhonesSelector.bind(this),
					onOpenSelector: this.openContactsSelector.bind(this),
					onAddPhone: this.addPhoneToContact.bind(this),
				}),
			);
		}

		getPhoneById(id)
		{
			const { communications } = this.state;

			if (!communications)
			{
				return null;
			}

			const phones = [];
			communications.forEach((communication) => {
				if (Array.isArray(communication.phones))
				{
					phones.push(...communication.phones);
				}
			});

			return phones.find((item) => item.id === id);
		}

		hasOnlyManyClientsWithoutPhones()
		{
			const { communications } = this.state;

			if (!communications)
			{
				return false;
			}

			let clientsWithPhones = 0;
			communications.forEach((communication) => {
				if (Array.isArray(communication.phones) && communication.phones.length > 0)
				{
					clientsWithPhones++;
				}
			});

			const totalClients = communications.length;

			return (totalClients > 1 && clientsWithPhones === 0);
		}

		openClientsWithoutPhonesSelector()
		{
			const menu = new ContextMenu(this.getMenuConfig());

			void menu.show(this.props.layout);
		}

		getMenuConfig()
		{
			return {
				testId: 'TimelineSmsClientWithoutPhonesSelector',
				actions: this.getClientWithoutPhonesMenuActions(),
				params: {
					shouldResizeContent: true,
					showCancelButton: true,
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_CLIENTS_SELECTOR_TITLE'),
				},
			};
		}

		getClientWithoutPhonesMenuActions()
		{
			const { communications, entityTypeId, entityId } = this.state;

			const items = [];
			communications.forEach((communication) => {
				const {
					entityTypeId: communicationEntityTypeId,
					entityId: communicationEntityId,
					caption: title,
				} = communication;

				items.push({
					id: `client-${communicationEntityTypeId}-${communicationEntityId}`,
					title,
					isSelected: (
						entityTypeId === communicationEntityTypeId
						&& entityId === communicationEntityId
					),
					onClickCallback: () => {
						return new Promise((resolve) => {
							this.setState({
								entityTypeId: communicationEntityTypeId,
								entityId: communicationEntityId,
								toName: title,
							}, resolve);
						});
					},
				});
			});

			return items;
		}

		openContactsSelector()
		{
			const { layout, entity: { typeId, id } } = this.props;
			const ownerInfo = {
				ownerId: id,
				ownerTypeName: CrmType.resolveNameById(typeId),
			};

			const { communications, toPhoneId } = this.state;

			CommunicationSelector.show({
				layout,
				communications,
				ownerInfo,
				typeId,
				selectedPhoneId: toPhoneId,
				onPhoneSelectCallback: this.onPhoneSelectCallback,
			});
		}

		onPhoneSelectCallback(data)
		{
			this.setState({
				toName: data.title,
				toPhoneId: data.phone.id,
				entityId: data.id,
				entityTypeId: CrmType.resolveIdByName(data.type),
			});

			return Promise.resolve();
		}

		addPhoneToContact()
		{
			const { entityId, entityTypeId } = this.state;

			const multiFieldDrawer = new MultiFieldDrawer({
				entityTypeId,
				entityId,
				fields: [MultiFieldType.PHONE],
				onSuccess: this.onPhoneAddedSuccessCallback,
				warningBlock: {
					description: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_PHONE_DRAWER_WARNING_TEXT'),
				},
			});

			multiFieldDrawer.show(this.props.layout);
		}

		onPhoneAddedSuccessCallback({ entityTypeId, entityId, values }, multiFields)
		{
			const phonesObject = BX.prop.getObject(values, 'PHONE', null);
			if (phonesObject === null)
			{
				return;
			}

			const phones = Object.values(phonesObject);
			if (phones.length === 0)
			{
				return;
			}

			const { VALUE: phoneNumber, ID: phoneId, VALUE_TYPE: type } = phones[0];

			if (!Type.isStringFilled(phoneNumber))
			{
				return;
			}

			if (!multiFields || !multiFields.PHONE)
			{
				return;
			}

			const phoneTypeInfo = this.findTypeInfo(
				multiFields.PHONE,
				type || 'WORK',
				phoneNumber,
				phoneId,
			);

			const communications = clone(this.state.communications);
			const communication = communications.find((item) => (
				item.entityId === entityId && item.entityTypeId === entityTypeId
			));

			communication.phones = [phoneTypeInfo];

			this.setState({
				communications,
				toPhoneId: phoneId,
				toName: communication.caption,
			});
		}

		findTypeInfo(phoneTypes, selectedType, phoneNumber, phoneId)
		{
			if (phoneTypes.hasOwnProperty(selectedType))
			{
				return {
					id: phoneId,
					type: selectedType,
					typeLabel: phoneTypes[selectedType],
					value: phoneNumber,
					valueFormatted: phoneNumber,
				};
			}

			return null;
		}

		renderSettingsIcon()
		{
			if (!this.isFetchedConfig)
			{
				return View(
					{
						style: styles.settingsIconContainer,
					},
					Line(22, 22),
				);
			}

			if (!this.hasProvider)
			{
				return null;
			}

			return View(
				{
					style: styles.settingsIconContainer,
				},
				Image({
					tintColor: AppTheme.colors.base5,
					style: styles.settingsIcon,
					svg: {
						content: settings(),
					},
					onClick: this.openSendersSelector,
				}),
			);
		}

		openSendersSelector()
		{
			if (!this.sendersSelector)
			{
				const { fromPhoneId: currentPhoneId, sender: currentSender } = this.state;
				const { smsConfig, onChangeSenderCallback, onChangePhoneCallback } = this;

				this.sendersSelector = new SendersSelector({
					currentPhoneId,
					currentSender,
					senders: smsConfig.config.senders,
					contactCenterUrl: smsConfig.contactCenterUrl,
					onChangeSenderCallback,
					onChangePhoneCallback,
				});
			}

			this.sendersSelector.show(this.layout);
		}

		onChangeSenderCallback({ sender, phoneId })
		{
			const isSenderTemplatesBased = this.isSenderTemplatesBased(sender);

			this.setState({
				sender,
				fromPhoneId: phoneId,
				templateId: isSenderTemplatesBased ? 0 : null,
				maxHeight: MAX_HEIGHT,
			}, () => {
				if (isSenderTemplatesBased && !this.isSenderHasTemplates(sender))
				{
					this.fetchTemplatesList();
				}
				else
				{
					this.refreshSendButton();
				}
			});
		}

		onChangePhoneCallback({ phoneId })
		{
			this.setState({
				fromPhoneId: phoneId,
			});
		}

		renderBody()
		{
			if (this.isSenderTemplatesBased())
			{
				if (this.isSenderHasTemplates())
				{
					return View(
						{
							style: styles.bodyContainer,
						},
						this.renderTemplate(),
					);
				}

				return View(
					{
						style: styles.bodyContainer,
					},
					View(
						{
							style: styles.templateContainer,
						},
						Line(120, 14),
						Line(160, 18, 10),
						Line('100%', 1, 8, 8, 12),
						Line('100%', 60, 0, 0, 12),
					),
				);
			}

			return this.renderTextField();
		}

		renderTemplate()
		{
			const template = this.getCurrentTemplate();

			if (!template)
			{
				return null;
			}

			return View(
				{
					style: styles.templateContainer,
				},
				View(
					{
						style: styles.templateTitleContainer,
					},
					View(
						{},
						Text({
							style: styles.templateTitleLabel,
							text: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_CURRENT_TEMPLATE'),
						}),
						Text({
							style: styles.templateTitle,
							text: template.TITLE,
							numberOfLines: 1,
							ellipsize: 'end',
						}),
					),
					Image({
						style: styles.templateSelectorIcon,
						svg: {
							content: `<svg width="23" height="22" viewBox="0 0 23 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.66406 5.74707H6.4974V7.5804H4.66406V5.74707ZM8.33073 5.74707H19.3307V7.5804H8.33073V5.74707ZM8.33073 14.9137H19.3307V16.7471H8.33073V14.9137ZM4.66406 14.9137H6.4974V16.7471H4.66406V14.9137ZM8.33073 10.3304H19.3307V12.1637H8.33073V10.3304ZM4.66406 10.3304H6.4974V12.1637H4.66406V10.3304Z" fill="${AppTheme.colors.base3}"/></svg>`,
						},
						onClick: this.openTemplateSelector,
					}),
				),
				View(
					{},
					Text({
						style: styles.templatePreview,
						text: template.PREVIEW,
					}),
				),

				// @todo use scrollView instead of view
				/* ScrollView(
					{
						resizableByKeyboard: true,
						showsVerticalScrollIndicator: true,
						safeArea: {
							bottom: true,
							top: true,
							left: true,
							right: true,
						},
					},
					View(
						{},
						Text({
							style: styles.templatePreview,
							text: template.PREVIEW,
						}),
					),
				), */
			);
		}

		getCurrentTemplate()
		{
			const { sender: { templates }, templateId } = this.state;

			if (!this.isSenderTemplatesBased())
			{
				return null;
			}

			if (Array.isArray(templates) && templates[templateId])
			{
				return templates[templateId];
			}

			return null;
		}

		isSenderTemplatesBased(sender = null)
		{
			if (sender === null)
			{
				sender = this.state.sender;
			}

			return (sender && sender.isTemplatesBased);
		}

		isSenderHasTemplates(sender = null)
		{
			if (sender === null)
			{
				sender = this.state.sender;
			}

			return (sender && Array.isArray(sender.templates) && sender.templates.length > 0);
		}

		openTemplateSelector()
		{
			const { sender, templateId } = this.state;
			const { templates } = sender;

			const actions = [];
			templates.forEach((template, index) => {
				actions.push({
					id: index,
					title: template.TITLE,
					subTitle: '',
					onClickCallback: () => {
						this.setState({
							templateId: index,
						});
					},
					isSelected: templateId === index,
				});
			});

			const templateSelector = new ContextMenu({
				testId: 'SMS_TEMPLATE_SELECTOR',
				actions,
				params: {
					title: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_TEMPLATE_SELECTOR_TITLE'),
				},
				layoutWidget: this.layout,
			});

			templateSelector.show(this.props.layout);
		}

		renderTextField()
		{
			const hasRemainingLetters = (this.remainingLetters >= 0);

			return View(
				{
					style: styles.bodyContainer,
				},
				Image({
					style: styles.textFieldCorner,
					svg: {
						content: icons.corner,
					},
				}),
				Text({
					style: styles.counter(hasRemainingLetters),
					text: this.remainingLetters.toString(),
					ref: (ref) => {
						this.counterRef = ref;
					},
				}),
				View(
					{
						style: styles.textFieldWrapper,
					},
					Textarea({
						ref: (ref) => {
							this.textInputRef = ref;
						},
						text: this.state.text,
						style: styles.textField,
						placeholder: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_PLACEHOLDER'),
						placeholderTextColor: AppTheme.colors.base5,
						onChange: this.debouncedTextChange,
					}),
				),
			);
		}

		get remainingLetters()
		{
			return MAX_SMS_LENGTH - this.state.text.length;
		}

		onTextChange(text)
		{
			this.setState({ text }, () => this.refreshSendButton());
		}

		refreshSendButton()
		{
			if (this.isSendAllowed())
			{
				this.sendButton.enable();
			}
			else
			{
				this.sendButton.disable();
			}
		}

		isSendAllowed()
		{
			const { text, sender, toPhoneId, fromPhoneId } = this.state;

			if (!Type.isObjectLike(sender) || !this.getPhoneById(toPhoneId))
			{
				return false;
			}

			if (this.isSenderTemplatesBased())
			{
				return (this.getCurrentTemplate() && Type.isNumber(fromPhoneId));
			}

			return (Type.isStringFilled(text) && Type.isStringFilled(fromPhoneId));
		}

		send()
		{
			return new Promise((resolve, reject) => {
				const { text, sender, toPhoneId, fromPhoneId, entityTypeId, entityId } = this.state;
				const phone = this.getPhoneById(toPhoneId);
				const template = this.getCurrentTemplate();

				const data = {
					ownerTypeId: this.entity.typeId,
					ownerId: this.entity.id,
					params: {
						entityTypeId,
						entityId,
						senderId: sender.id,
						from: fromPhoneId,
						to: phone.value,
						body: template ? template.PREVIEW : text,
						template: template ? template.ID : null,
					},
				};

				BX.ajax.runAction('crm.activity.sms.send', { data })
					.then((response) => {
						resolve(response);
						Haptics.notifySuccess();
						this.onActivityCreate(response);
						this.close();
					})
					.catch((response) => {
						void ErrorNotifier.showError(response.errors[0].message);
						reject(response);
					});
			});
		}

		get hasProvider()
		{
			if (!this.isFetchedConfig)
			{
				return false;
			}

			return this.smsConfig.config.senders.some((sender) => sender.canUse);
		}
	}

	const icons = {
		corner: `<svg width="15" height="16" viewBox="0 0 15 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M33.8847 35.25H2C1.30964 35.25 0.75 34.6904 0.75 34V4.56797C0.75 3.47827 2.04714 2.91035 2.84785 3.64947L34.7325 33.0815C35.5687 33.8533 35.0226 35.25 33.8847 35.25Z" fill="${AppTheme.colors.bgContentPrimary}" stroke="${AppTheme.colors.accentExtraAqua}" stroke-width="1.5"/></svg>`,
		warning: `<svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.78925 4.20218H10.5268L10.2109 10.116H8.10512L7.78925 4.20218Z" fill="AppTheme.colors.base2"/><path d="M9.15776 14.2528C10.0869 14.2528 10.8401 13.4996 10.8401 12.5705C10.8401 11.6414 10.0869 10.8881 9.15776 10.8881C8.22863 10.8881 7.47543 11.6414 7.47543 12.5705C7.47543 13.4996 8.22863 14.2528 9.15776 14.2528Z" fill="${AppTheme.colors.base2}"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.14886 18.1317C14.1099 18.1317 18.1317 14.1099 18.1317 9.14886C18.1317 4.18777 14.1099 0.166016 9.14886 0.166016C4.18777 0.166016 0.166016 4.18777 0.166016 9.14886C0.166016 14.1099 4.18777 18.1317 9.14886 18.1317ZM9.14886 15.9977C12.9314 15.9977 15.9977 12.9314 15.9977 9.14887C15.9977 5.36636 12.9314 2.30004 9.14886 2.30004C5.36636 2.30004 2.30004 5.36636 2.30004 9.14887C2.30004 12.9314 5.36636 15.9977 9.14886 15.9977Z" fill="${AppTheme.colors.base2}"/></svg>`,
	};

	const styles = {
		container: {
			flexDirection: 'column',
			flex: 1,
		},
		containerInner: {
			flex: 1,
		},
		wrapper: (maxHeight) => ({
			flex: 1,
			padding: 14,
			backgroundColor: AppTheme.colors.bgContentPrimary,
			borderTopLeftRadius: 12,
			borderTopRightRadius: 12,
			maxHeight,
		}),
		warningBlock: {
			marginBottom: 12,
		},
		settingsSection: {
			flexDirection: 'row-reverse',
			justifyContent: 'space-between',
		},
		clientSelector: {
			paddingTop: 2,
			paddingHorizontal: 4,
			flex: 1,
		},
		settingsIconContainer: {
			marginTop: 10,
			width: 30,
			height: 30,
			justifyContent: 'center',
			alignItems: 'center',
			marginBottom: 2,
		},
		settingsIcon: {
			width: 24,
			height: 24,
		},
		bodyContainer: {
			paddingTop: 9,
			flex: 1,
			marginHorizontal: 2,
			paddingBottom: 9,
		},
		templateContainer: {
			padding: 18,
			borderWidth: 1.4,
			borderRadius: 12,
			borderColor: AppTheme.colors.base6,
		},
		templateTitleContainer: {
			flexDirection: 'row',
			justifyContent: 'space-between',
			alignContent: 'center',
			borderBottomWidth: 1,
			borderBottomColor: AppTheme.colors.base3,
			paddingBottom: 10,
			marginBottom: 10,
		},
		templateTitleLabel: {
			fontSize: 14,
			color: AppTheme.colors.base3,
		},
		templateTitle: {
			fontSize: 18,
			color: AppTheme.colors.base3,
			marginTop: 4,
		},
		templateSelectorIcon: {
			marginRight: 4,
			width: 23,
			height: 22,
			alignSelf: 'center',
		},
		templatePreview: {
			color: AppTheme.colors.base3,
			fontSize: 14,
		},
		textFieldCorner: {
			width: 15,
			height: 16,
			backgroundColor: AppTheme.colors.bgContentPrimary,
			marginBottom: -2,
			zIndex: 10,
		},
		counter: (hasRemainingLetters) => {
			return {
				position: 'absolute',
				backgroundColor: AppTheme.colors.bgContentPrimary,
				zIndex: 10,
				color: hasRemainingLetters ? AppTheme.colors.base5 : AppTheme.colors.accentSoftElementRed1,
				paddingHorizontal: 6,
				fontSize: 13,
				right: 12,
				top: 15,
				textAlign: 'center',
			};
		},
		textFieldWrapper: {
			borderColor: AppTheme.colors.accentExtraAqua,
			borderWidth: 1.5,
			zIndex: 1,
			borderTopRightRadius: 6,
			borderBottomRightRadius: 6,
			borderBottomLeftRadius: 6,
			flex: 1,
		},
		textField: {
			color: AppTheme.colors.base1,
			fontSize: 16,
			paddingHorizontal: 16,
			paddingVertical: 14,
		},
	};

	module.exports = { TimelineSchedulerSmsProvider };
});
