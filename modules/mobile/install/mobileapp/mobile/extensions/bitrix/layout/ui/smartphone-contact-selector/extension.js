/**
 * @module layout/ui/smartphone-contact-selector
 */
jn.define('layout/ui/smartphone-contact-selector', (require, exports, module) => {
	const { contacts } = require('native/contacts');
	const { Alert, ButtonType } = require('alert');
	const { Type } = require('type');
	const { getCountryCode, isPhoneNumber, getFormattedNumber } = require('utils/phone');
	const { checkValueMatchQuery } = require('utils/search');
	const { debounce } = require('utils/function');
	const { Loc } = require('loc');
	const { Feature } = require('feature');
	const { Haptics } = require('haptics');
	const { AvatarClass, AvatarShape, AvatarEntityType } = require('ui-system/blocks/avatar');
	const { Notify } = require('notify');
	const { PhoneInputBox } = require('layout/ui/smartphone-contact-selector/src/phone-input-box');

	const SECTION_CODE = 'main';

	/**
	 * @typedef {Object} SmartphoneContactSelectorProps
	 * @property {layout} [parentLayout]
	 * @property {boolean} [allowMultipleSelection=true]
	 * @property {boolean} [allowPhoneNumberInput=true]
	 * @property {boolean} [closeAfterSendButtonClick=true]
	 * @property {Function} [onSendButtonClickHandler]
	 * @property {Function} [onRequestContactsSuccess]
	 * @property {Function} [onSelectionChanged]
	 * @property {string} [itemImageUri]
	 * @property {AvatarEntityType} [avatarType=AvatarEntityType.USER]
	 *
	 * @class SmartphoneContactSelector
	 */
	class SmartphoneContactSelector
	{
		/**
		 * @param {SmartphoneContactSelectorProps} props
		 */
		constructor(props)
		{
			PropTypes.validate(SmartphoneContactSelector.propTypes, props, 'SmartphoneContactSelector');

			/**
			 * @type {SmartphoneContactSelectorProps}
			 */
			this.props = props;
			this.selector = null;
			this.allContacts = [];
			this.selectedContacts = [];
			this.isSendButtonLoading = false;

			this.onListFillDebounce = debounce(this.#onListFill, 500, this);
		}

		get allowPhoneNumberInput()
		{
			return this.props.allowPhoneNumberInput ?? true;
		}

		get allowMultipleSelection()
		{
			return this.props.allowMultipleSelection ?? true;
		}

		get parentLayout()
		{
			return this.props.parentLayout ?? PageManager;
		}

		get closeAfterSendButtonClick()
		{
			return this.props.closeAfterSendButtonClick ?? true;
		}

		get onSendButtonClickHandler()
		{
			return this.props.onSendButtonClickHandler ?? null;
		}

		get onRequestContactsSuccess()
		{
			return this.props.onRequestContactsSuccess ?? null;
		}

		get onSelectionChanged()
		{
			return this.props.onSelectionChanged ?? null;
		}

		get dismissAlert()
		{
			return this.props.dismissAlert;
		}

		getAvatarType()
		{
			const { avatarType } = this.props;

			return AvatarEntityType.resolve(avatarType, AvatarEntityType.USER);
		}

		getImage()
		{
			const { itemImageUri } = this.props;

			return itemImageUri;
		}

		open = async () => {
			if (Feature.isSmartphoneContactsAPISupported())
			{
				await this.openNewSelector();

				return;
			}

			if (Application.getPlatform() === 'android')
			{
				Feature.showDefaultUnsupportedWidget({}, this.parentLayout);

				return;
			}

			await this.openOldNativeSelector();
		};

		async openNewSelector()
		{
			await Notify.showIndicatorLoading();
			this.allContacts = await this.#getContacts();
			Notify.hideCurrentIndicator();
			if (!Type.isArrayFilled(this.allContacts))
			{
				if (this.allowPhoneNumberInput)
				{
					await PhoneInputBox.open({
						onContinue: this.#onPhoneInputBoxContinue,
						parentLayout: this.parentLayout,
					});
				}
				else
				{
					this.close();
				}

				return;
			}

			this.selector = await this.parentLayout.openWidget('selector', {
				titleParams: {
					text: Loc.getMessage('CONTACT_SELECTOR_TITLE_MSGVER_1'),
					type: 'dialog',
				},
				backdrop: {
					mediumPositionPercent: 90,
					horizontalSwipeAllowed: false,
				},
				sendButtonName: Loc.getMessage('CONTACT_SELECTOR_SEND_BUTTON_TEXT'),
			});
			this.selector.setPlaceholder(Loc.getMessage('CONTACT_SELECTOR_SEARCH_PLACEHOLDER_TEXT'));
			this.selector.setSendButtonEnabled(false);
			this.#initLoadingSelector();
			const isEmptyItems = !Type.isArrayFilled(this.allContacts);
			if (isEmptyItems)
			{
				this.#setItems([this.#getEmptyResultItem()]);

				return;
			}

			this.#initSelector();
		}

		#onPhoneInputBoxContinue = ({ phone, selectorInstance }) => {
			const contact = {
				phone,
				countryCode: getCountryCode(phone, null),
			};

			if (Type.isFunction(this.onSendButtonClickHandler))
			{
				this.onSendButtonClickHandler([contact], selectorInstance);
			}
		};

		#onPreventDismiss = () => {
			if (this.dismissAlert && this.selectedContacts.length > 0)
			{
				this.#showConfirmOnBoxClosing();
			}
			else
			{
				this.selector.close();
			}
		};

		#showConfirmOnBoxClosing()
		{
			Haptics.impactLight();

			Alert.confirm(
				this.dismissAlert.title,
				this.dismissAlert.description,
				[
					{
						type: ButtonType.DESTRUCTIVE,
						text: this.dismissAlert.destructiveButtonText,
						onPress: () => {
							this.selector.close();
						},
					},
					{
						type: ButtonType.DEFAULT,
						text: this.dismissAlert.defaultButtonText,
					},
				],
			);
		}

		async openOldNativeSelector()
		{
			const selectedContacts = await dialogs.showContactList({
				singleChoose: !this.allowMultipleSelection,
			});
			const contactsData = await this.getContactsDetailData(selectedContacts);
			if (contactsData && contactsData.length > 0 && this.onSendButtonClickHandler)
			{
				this.onSendButtonClickHandler(contactsData, this);
			}
		}

		getContactsDetailData = async (selectedContacts) => {
			if (selectedContacts && selectedContacts.length > 0)
			{
				const contactsData = await contacts.getData(selectedContacts.map((contact) => contact.id));

				return contactsData.map((contact) => {
					const phone = contact.phoneNumbers[0].value;

					return {
						name: contact.displayName,
						firstName: contact.firstName,
						secondName: contact.secondName,
						phone,
						countryCode: getCountryCode(phone),
					};
				});
			}

			return [];
		};

		close = () => {
			if (this.selector)
			{
				this.selector.close();
			}
		};

		enableSendButtonLoadingIndicator(enable = true)
		{
			if (this.selector)
			{
				this.isSendButtonLoading = enable;
				this.selector.setSendButtonLoading(enable);
			}
		}

		#initLoadingSelector = () => {
			this.#setItems([this.#getLoadingItem()]);
		};

		#getLoadingItem = () => {
			return {
				id: 'loading',
				title: Loc.getMessage('CONTACT_SELECTOR_LOADING_ITEM_TEXT'),
				type: 'loading',
				unselectable: true,
				sectionCode: SECTION_CODE,
			};
		};

		#getEmptyResultItem = () => {
			return {
				title: Loc.getMessage('CONTACT_SELECTOR_NOTHING_FOUND_ITEM_TEXT_MSGVER_1'),
				type: 'button',
				sectionCode: SECTION_CODE,
				unselectable: true,
			};
		};

		#getValidPhoneResultItem = (phoneNumber) => {
			return {
				id: phoneNumber,
				title: phoneNumber,
				useLetterImage: false,
				sectionCode: SECTION_CODE,
				avatar: this.#getAvatar({}),
			};
		};

		#initSelector = () => {
			if (this.selector)
			{
				this.selector.preventBottomSheetDismiss(true);
				this.selector.enableNavigationBarBorder(false);
				this.selector.setSearchEnabled(true);
				this.selector.allowMultipleSelection(this.allowMultipleSelection);
				this.#setItems(this.#prepareItemsForSelector(this.allContacts));
				this.selector.on('preventDismiss', this.#onPreventDismiss);
				this.selector.on('onSelectedChanged', this.#onSelectedChangedHandler);
				this.selector.on('onListFill', this.onListFillDebounce);
				this.selector.on('send', ({ item, text, scope }) => {
					if (this.selector && !this.isSendButtonLoading)
					{
						this.enableSendButtonLoadingIndicator(true);
						if (this.closeAfterSendButtonClick)
						{
							this.selector.close();
						}

						if (Type.isFunction(this.onSendButtonClickHandler))
						{
							this.onSendButtonClickHandler(this.getSelectedContacts(), this);
						}
					}
				});
			}
		};

		#onListFill = ({ text, scope }) => {
			if (this.selector)
			{
				const targetContacts = text === ''
					? this.#prepareItemsForSelector(this.allContacts)
					: this.#prepareItemsForSelector(
						this.allContacts.filter((contact) => checkValueMatchQuery(text, contact.displayName)),
					);
				if (targetContacts.length > 0)
				{
					this.#setItems(targetContacts);

					return;
				}

				if (isPhoneNumber(text))
				{
					this.#setItems([this.#getValidPhoneResultItem(getFormattedNumber(text))]);

					return;
				}

				this.#setItems([this.#getEmptyResultItem()]);
			}
		};

		#setItems = (items) => {
			this.selector?.setItems(items, this.#getNativeSelectorSections());
		};

		getSelectedContacts = () => {
			return this.selectedContacts;
		};

		#onSelectedChangedHandler = ({ items, text, scope }) => {
			Keyboard.dismiss();
			if (!this.selector)
			{
				return;
			}

			this.selector.setSendButtonCounter(items.length);
			this.selector.setSendButtonEnabled(items.length > 0);
			this.selectedContacts = items.map((item) => {
				const targetContact = this.allContacts.find((contact) => contact.id === item.id);
				if (!targetContact)
				{
					if (isPhoneNumber(item.id))
					{
						const phone = item.id;

						return {
							phone,
							countryCode: getCountryCode(phone),
						};
					}

					return false;
				}
				const phone = item.subtitle;

				return {
					name: targetContact.displayName,
					firstName: targetContact.firstName,
					secondName: targetContact.secondName,
					phone,
					countryCode: getCountryCode(phone, null),
				};
			}).filter(Boolean);

			if (this.onSelectionChanged)
			{
				this.onSelectionChanged();
			}
		};

		#prepareItemsForSelector = (items) => {
			return items.map((item) => ({
				id: item.id,
				title: item.displayName,
				subtitle: item.phoneNumber,
				useLetterImage: false,
				sectionCode: SECTION_CODE,
				avatar: this.#getAvatar(item),
			}));
		};

		#getAvatar({ avatar, displayName })
		{
			return AvatarClass
				.getAvatar({
					name: displayName,
					testId: 'smartphone-contact-selector-avatar',
					shape: AvatarShape.CIRCLE,
					uri: avatar || this.getImage(),
					entityType: this.getAvatarType(),
				})
				.getAvatarNativeProps();
		}

		#getNativeSelectorSections = () => {
			return [{ id: SECTION_CODE }];
		};

		#timer = (ms) => {
			return new Promise((resolve) => {
				setTimeout(resolve, ms);
			});
		};

		#getContacts = async () => {
			if (Type.isFunction(contacts.hasContactListAccess) && contacts.hasContactListAccess())
			{
				const initialContacts = await contacts.getContacts([
					'id',
					'displayName',
					'firstName',
					'secondName',
					'phoneNumbers',
					'avatar',
				]).catch((error) => {
					console.error(error);
				});

				Notify.hideCurrentIndicator();
				const contactsWithSeparatedPhoneNumbers = [];
				if (Array.isArray(initialContacts) && initialContacts.length > 0)
				{
					initialContacts.forEach((item) => {
						if (Array.isArray(item.phoneNumbers) && item.phoneNumbers.length > 0)
						{
							contactsWithSeparatedPhoneNumbers.push(...item.phoneNumbers.map((phoneNumber) => {
								return {
									...item,
									phoneNumber: phoneNumber.value,
									id: `${item.id}_${phoneNumber.value}`,
								};
							}));
						}
					});
				}

				return contactsWithSeparatedPhoneNumbers;
			}

			if (Type.isFunction(contacts.hasDeterminedStatus) && contacts.hasDeterminedStatus())
			{
				if (!this.allowPhoneNumberInput)
				{
					const toOpenSettings = await this.#showNeedToAddContactsAccessMessage();
					if (toOpenSettings)
					{
						Application.openSettings();
					}
				}

				return null;
			}

			if (Type.isFunction(contacts.requestContactsAccess))
			{
				const requestContactsSuccess = await contacts.requestContactsAccess()
					.catch((error) => {
						console.error(error);
					});

				if (requestContactsSuccess)
				{
					this.onRequestContactsSuccess?.();

					return this.#getContacts();
				}

				if (this.allowPhoneNumberInput)
				{
					// need to avoid first time open PhoneInputBox keyboard bug
					await this.#timer(500);

					return null;
				}
			}

			const toOpenSettings = await this.#showNeedToAddContactsAccessMessage();
			if (toOpenSettings)
			{
				Application.openSettings();
			}

			return null;
		};

		#showNeedToAddContactsAccessMessage = () => {
			return new Promise((resolve) => {
				Alert.confirm(
					Loc.getMessage('CONTACT_SELECTOR_NEED_TO_ADD_CONTACT_ACCESS_ALERT_TITLE'),
					Loc.getMessage('CONTACT_SELECTOR_NEED_TO_ADD_CONTACT_ACCESS_ALERT_DESCRIPTION'),
					[
						{
							text: Loc.getMessage('CONTACT_SELECTOR_ALERT_OPEN_SETTINGS_BUTTON_TEXT'),
							type: ButtonType.DEFAULT,
							onPress: () => {
								resolve(true);
							},
						},
						{
							text: Loc.getMessage('CONTACT_SELECTOR_ALERT_CANCEL_BUTTON_TEXT'),
							type: ButtonType.DEFAULT,
							onPress: () => {
								resolve(false);
							},
						},
					],
				);
			});
		};
	}

	SmartphoneContactSelector.defaultProps = {
		allowMultipleSelection: true,
		closeAfterSendButtonClick: true,
	};

	SmartphoneContactSelector.propTypes = {
		allowMultipleSelection: PropTypes.bool,
		closeAfterSendButtonClick: PropTypes.bool,
		parentLayout: PropTypes.object,
		avatarType: PropTypes.instanceOf(AvatarEntityType),
		onSelectionChanged: PropTypes.func,
		onSendButtonClickHandler: PropTypes.func,
		onRequestContactsSuccess: PropTypes.func,
		itemImageUri: PropTypes.string,
	};

	module.exports = {
		SmartphoneContactSelector,
		AvatarEntityType,
	};
});
