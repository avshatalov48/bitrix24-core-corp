/**
 * @module crm/timeline/scheduler/providers/sms
 */
jn.define('crm/timeline/scheduler/providers/sms', (require, exports, module) => {
	const { Loc } = require('loc');
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
	const { Type } = require('type');

	const MAX_SMS_LENGTH = 200;

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
			return '<svg width="31" height="31" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M7.75618 7.79004C6.32944 7.79004 5.17284 8.94665 5.17285 10.3734L5.17294 20.6003C5.17295 22.0271 6.32954 23.1837 7.75627 23.1837H9.93142V25.9041C9.93142 26.3726 10.5049 26.5992 10.8252 26.2573L13.7039 23.1837H23.3893C24.8161 23.1837 25.9727 22.027 25.9727 20.6003L25.9726 10.3734C25.9726 8.94662 24.816 7.79004 23.3892 7.79004H7.75618ZM11.1814 17.4841C11.3614 17.2089 11.4514 16.8939 11.4514 16.5392C11.4514 16.1531 11.3555 15.8329 11.1638 15.5786C10.9721 15.3243 10.6297 15.0863 10.1367 14.8646C9.62286 14.6298 9.30789 14.4681 9.19182 14.3794C9.07574 14.2907 9.0177 14.1903 9.0177 14.0781C9.0177 13.9738 9.06335 13.8864 9.15465 13.816C9.24594 13.7455 9.39201 13.7103 9.59286 13.7103C9.98152 13.7103 10.4432 13.8329 10.9779 14.0781L11.4514 12.8847C10.8358 12.6109 10.2319 12.4739 9.63982 12.4739C8.96944 12.4739 8.44255 12.6213 8.0591 12.916C7.67566 13.2108 7.48394 13.6216 7.48394 14.1485C7.48394 14.4302 7.52894 14.6741 7.61893 14.8802C7.70892 15.0863 7.84717 15.2689 8.03367 15.428C8.22018 15.5871 8.49993 15.7514 8.87294 15.921C9.28507 16.1062 9.53874 16.2288 9.63395 16.2888C9.72915 16.3487 9.79828 16.4081 9.84132 16.4668C9.88436 16.5255 9.90588 16.5939 9.90588 16.6722C9.90588 16.7974 9.8524 16.8998 9.74546 16.9793C9.63851 17.0589 9.47027 17.0987 9.24073 17.0987C8.97466 17.0987 8.68252 17.0563 8.36429 16.9715C8.04606 16.8867 7.73957 16.7687 7.44482 16.6174V17.9947C7.72392 18.1277 7.99259 18.221 8.25082 18.2744C8.50906 18.3279 8.82859 18.3546 9.20942 18.3546C9.6659 18.3546 10.0637 18.2783 10.4028 18.1257C10.7419 17.9731 11.0014 17.7593 11.1814 17.4841ZM13.6583 14.1559L14.8282 18.2759H16.2993L17.4536 14.1637H17.4888C17.4575 14.7846 17.4399 15.1745 17.4359 15.3336C17.432 15.4927 17.4301 15.6375 17.4301 15.7679V18.2759H18.8504V12.5556H16.7884L15.6185 16.617H15.5872L14.3939 12.5556H12.3358V18.2759H13.7052V15.7914C13.7052 15.4027 13.6778 14.8576 13.6231 14.1559H13.6583ZM23.8241 16.5392C23.8241 16.8939 23.7341 17.2089 23.5541 17.4841C23.3741 17.7593 23.1146 17.9731 22.7755 18.1257C22.4364 18.2783 22.0386 18.3546 21.5821 18.3546C21.2013 18.3546 20.8818 18.3279 20.6235 18.2744C20.3653 18.221 20.0966 18.1277 19.8175 17.9947V16.6174C20.1123 16.7687 20.4188 16.8867 20.737 16.9715C21.0552 17.0563 21.3474 17.0987 21.6134 17.0987C21.843 17.0987 22.0112 17.0589 22.1182 16.9793C22.2251 16.8998 22.2786 16.7974 22.2786 16.6722C22.2786 16.5939 22.2571 16.5255 22.214 16.4668C22.171 16.4081 22.1019 16.3487 22.0067 16.2888C21.9115 16.2288 21.6578 16.1062 21.2457 15.921C20.8727 15.7514 20.5929 15.5871 20.4064 15.428C20.2199 15.2689 20.0816 15.0863 19.9917 14.8802C19.9017 14.6741 19.8567 14.4302 19.8567 14.1485C19.8567 13.6216 20.0484 13.2108 20.4318 12.916C20.8153 12.6213 21.3422 12.4739 22.0125 12.4739C22.6047 12.4739 23.2085 12.6109 23.8241 12.8847L23.3507 14.0781C22.8159 13.8329 22.3542 13.7103 21.9656 13.7103C21.7647 13.7103 21.6187 13.7455 21.5274 13.816C21.4361 13.8864 21.3904 13.9738 21.3904 14.0781C21.3904 14.1903 21.4485 14.2907 21.5645 14.3794C21.6806 14.4681 21.9956 14.6298 22.5094 14.8646C23.0024 15.0863 23.3448 15.3243 23.5365 15.5786C23.7282 15.8329 23.8241 16.1531 23.8241 16.5392Z" fill="#767C87"/></svg>';
		}

		static getMenuPosition()
		{
			return 400;
		}

		static isSupported()
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
				maxHeight: 1000,
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
			this.onChangeSenderCallback = this.onChangeSenderCallback.bind(this);
			this.onChangePhoneCallback = this.onChangePhoneCallback.bind(this);
			this.onPhoneSelectCallback = this.onPhoneSelectCallback.bind(this);
			this.onPhoneAddedSuccessCallback = this.onPhoneAddedSuccessCallback.bind(this);
			this.debouncedTextChange = debounce((text) => this.onTextChange(text), 50, this);
		}

		componentDidMount()
		{
			super.componentDidMount();

			this.fetchSettings();
			this.focus();
			this.refreshSendButton();
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
				})
			;
		}

		setDefaultConfig(smsConfig)
		{
			this.smsConfig = smsConfig;

			const communications = BX.prop.getArray(smsConfig.config, 'communications', []);
			const sender = this.getDefaultSender(smsConfig);

			const newState = {
				communications,
				sender,
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
						this.renderTextField(),
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

			return View(
				{
					style: styles.clientSelector,
				},
				new ClientsSelector({
					name,
					phone: (phone ? phone.valueFormatted : null),
					showSkeleton: !this.isFetchedConfig,
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

		onPhoneAddedSuccessCallback({ entityTypeId, values }, multiFields)
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
			communications[0].entityTypeName = CrmType.resolveNameById(entityTypeId);
			communications[0].phones = [phoneTypeInfo];

			this.setState({
				communications,
				toPhoneId: phoneId,
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
					ShimmerView(
						{ animating: true },
						View({
							style: {
								height: 22,
								width: 22,
								borderRadius: 11,
								backgroundColor: '#DFE0E3',
							},
						}),
					),
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
					style: styles.settingsIcon,
					svg: {
						content: icons.settings,
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
			this.setState({
				sender,
				fromPhoneId: phoneId,
			});
		}

		onChangePhoneCallback({ phoneId })
		{
			this.setState({
				fromPhoneId: phoneId,
			});
		}

		renderTextField()
		{
			const hasRemainingLetters = (this.remainingLetters >= 0);

			return View(
				{
					style: styles.textFieldContainer,
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
					ref: (ref) => this.counterRef = ref,
				}),
				View(
					{
						style: styles.textFieldWrapper,
					},
					Textarea({
						ref: (ref) => this.textInputRef = ref,
						text: this.state.text,
						style: styles.textField,
						placeholder: Loc.getMessage('M_CRM_TIMELINE_SCHEDULER_SMS_PLACEHOLDER'),
						placeholderTextColor: '#BDC1C6',
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

			return (
				Type.isStringFilled(text)
				&& Type.isObjectLike(sender)
				&& this.getPhoneById(toPhoneId)
				&& Type.isStringFilled(fromPhoneId)
			);
		}

		send()
		{
			return new Promise((resolve, reject) => {
				const { text, sender, toPhoneId, fromPhoneId, entityTypeId, entityId } = this.state;
				const phone = this.getPhoneById(toPhoneId);

				const data = {
					ownerTypeId: this.entity.typeId,
					ownerId: this.entity.id,
					params: {
						senderId: sender.id,
						from: fromPhoneId,
						to: phone.value,
						body: text,
						entityTypeId,
						entityId,
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
		corner: '<svg width="15" height="16" viewBox="0 0 15 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M33.8847 35.25H2C1.30964 35.25 0.75 34.6904 0.75 34V4.56797C0.75 3.47827 2.04714 2.91035 2.84785 3.64947L34.7325 33.0815C35.5687 33.8533 35.0226 35.25 33.8847 35.25Z" fill="white" stroke="#7FDEFC" stroke-width="1.5"/></svg>',
		settings: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.9404 17.3642C11.9906 17.1729 12.1394 17.0245 12.3275 16.9635C12.6609 16.8554 12.9837 16.7238 13.2938 16.5704C13.4687 16.484 13.6756 16.4852 13.844 16.5836L14.9534 17.2314C15.1411 17.341 15.3764 17.3321 15.5488 17.1998C16.169 16.7238 16.7247 16.1681 17.2007 15.548C17.333 15.3756 17.3419 15.1402 17.2323 14.9526L16.5844 13.8431C16.486 13.6747 16.4848 13.4677 16.5712 13.2929C16.7245 12.9828 16.8562 12.66 16.9643 12.3266C17.0253 12.1385 17.1737 11.9898 17.3649 11.9396L18.5829 11.6197C18.7947 11.564 18.9555 11.3891 18.9828 11.1718C19.0838 10.3676 19.0791 9.58085 18.9808 8.8234C18.9528 8.60748 18.7925 8.43426 18.5819 8.37895L17.3499 8.05536C17.1606 8.00567 17.0132 7.85943 16.9511 7.67391C16.8422 7.34843 16.711 7.03312 16.5595 6.72979C16.472 6.55467 16.4728 6.34682 16.5715 6.17778L17.2136 5.07837C17.3233 4.89038 17.3142 4.6546 17.1814 4.48208C16.7012 3.85812 16.1416 3.29847 15.5176 2.81829C15.3451 2.68553 15.1093 2.6764 14.9213 2.78617L13.822 3.42817C13.6529 3.52689 13.4451 3.52771 13.27 3.44021C12.9666 3.28864 12.6513 3.15749 12.3258 3.04857C12.1403 2.98649 11.994 2.83901 11.9443 2.6498L11.6208 1.41794C11.5655 1.20736 11.3923 1.04708 11.1764 1.01906C10.4188 0.920765 9.63198 0.91615 8.82785 1.01722C8.61056 1.04453 8.43563 1.20525 8.38 1.41706L8.05683 2.64754C8.00766 2.83473 7.86381 2.98094 7.6816 3.04619C7.37687 3.15533 7.0748 3.2914 6.77905 3.45072C6.60114 3.54657 6.38583 3.54919 6.21133 3.44729L5.16661 2.83719C4.97481 2.72518 4.73382 2.73735 4.56026 2.87597C3.94159 3.37009 3.37094 3.94076 2.87683 4.55944C2.73822 4.733 2.72605 4.97398 2.83806 5.16579L3.44812 6.21043C3.55003 6.38494 3.5474 6.60026 3.45154 6.77817C3.29219 7.07394 3.1561 7.37604 3.04694 7.6808C2.98168 7.863 2.83548 8.00684 2.6483 8.056L1.41801 8.37912C1.20618 8.43475 1.04547 8.60969 1.01816 8.82699C0.917115 9.63115 0.921755 10.418 1.02008 11.1756C1.0481 11.3915 1.20837 11.5647 1.41896 11.62L2.6505 11.9434C2.83971 11.9931 2.9872 12.1394 3.04927 12.3249C3.1582 12.6505 3.28937 12.9658 3.44098 13.2693C3.52848 13.4444 3.52766 13.6522 3.42894 13.8213L2.78713 14.9203C2.67735 15.1083 2.68649 15.344 2.81925 15.5166C3.29944 16.1406 3.85911 16.7003 4.4831 17.1805C4.65562 17.3133 4.8914 17.3224 5.07939 17.2126L6.17847 16.5708C6.34752 16.4721 6.55536 16.4712 6.73048 16.5587C7.03387 16.7103 7.34925 16.8415 7.67479 16.9504C7.86032 17.0125 8.00656 17.16 8.05626 17.3492L8.37977 18.5809C8.43508 18.7915 8.60829 18.9518 8.8242 18.9798C9.58168 19.0781 10.3685 19.0828 11.1726 18.9818C11.3899 18.9545 11.5649 18.7938 11.6205 18.5819L11.9404 17.3642ZM10.9041 14.0246C7.92762 14.6576 5.34194 12.0712 5.97509 9.09529C6.27451 7.68811 7.68856 6.27396 9.09574 5.97449C12.0717 5.34108 14.6584 7.92694 14.0252 10.9036C13.6931 12.4648 12.4653 13.6926 10.9041 14.0246Z" fill="#DFE0E3"/></svg>',
		warning: '<svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7.78925 4.20218H10.5268L10.2109 10.116H8.10512L7.78925 4.20218Z" fill="#525C69"/><path d="M9.15776 14.2528C10.0869 14.2528 10.8401 13.4996 10.8401 12.5705C10.8401 11.6414 10.0869 10.8881 9.15776 10.8881C8.22863 10.8881 7.47543 11.6414 7.47543 12.5705C7.47543 13.4996 8.22863 14.2528 9.15776 14.2528Z" fill="#525C69"/><path fill-rule="evenodd" clip-rule="evenodd" d="M9.14886 18.1317C14.1099 18.1317 18.1317 14.1099 18.1317 9.14886C18.1317 4.18777 14.1099 0.166016 9.14886 0.166016C4.18777 0.166016 0.166016 4.18777 0.166016 9.14886C0.166016 14.1099 4.18777 18.1317 9.14886 18.1317ZM9.14886 15.9977C12.9314 15.9977 15.9977 12.9314 15.9977 9.14887C15.9977 5.36636 12.9314 2.30004 9.14886 2.30004C5.36636 2.30004 2.30004 5.36636 2.30004 9.14887C2.30004 12.9314 5.36636 15.9977 9.14886 15.9977Z" fill="#525C69"/></svg>',
	};

	const styles = {
		container: {
			flexDirection: 'column',
			flex: 1,
			backgroundColor: '#EEF2F4',
		},
		containerInner: {
			flex: 1,
		},
		wrapper: (maxHeight) => ({
			flex: 1,
			padding: 14,
			backgroundColor: '#FFFFFF',
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
			width: 20,
			height: 20,
		},
		textFieldContainer: {
			paddingTop: 9,
			flex: 1,
			marginHorizontal: 2,
		},
		textFieldCorner: {
			width: 15,
			height: 16,
			backgroundColor: '#FFFFFF',
			marginBottom: -2,
			zIndex: 10,
		},
		counter: (hasRemainingLetters) => {
			return {
				position: 'absolute',
				backgroundColor: '#FFFFFF',
				zIndex: 10,
				color: hasRemainingLetters ? '#BDC1C6' : '#D0011B',
				paddingHorizontal: 6,
				fontSize: 13,
				right: 12,
				top: 15,
				textAlign: 'center',
			};
		},
		textFieldWrapper: {
			borderColor: '#7FDEFC',
			borderWidth: 1.5,
			zIndex: 1,
			borderTopRightRadius: 6,
			borderBottomRightRadius: 6,
			borderBottomLeftRadius: 6,
			flex: 1,
		},
		textField: {
			color: '#333333',
			fontSize: 16,
			paddingHorizontal: 16,
			paddingVertical: 14,
		},
	};

	module.exports = { TimelineSchedulerSmsProvider };
});
