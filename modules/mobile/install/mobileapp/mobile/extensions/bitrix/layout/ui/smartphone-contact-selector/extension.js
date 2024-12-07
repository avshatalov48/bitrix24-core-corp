/**
 * @module layout/ui/smartphone-contact-selector
 */
jn.define('layout/ui/smartphone-contact-selector', (require, exports, module) => {
	const { contacts } = require('native/contacts');
	const { Alert } = require('alert');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Type } = require('type');
	const { getCountryCode } = require('utils/phone');
	const { checkValueMatchQuery } = require('utils/search');
	const { debounce } = require('utils/function');
	const { Loc } = require('loc');
	const { Feature } = require('feature');
	const { isNil } = require('utils/type');

	const SECTION_CODE = 'main';
	const imageUri = makeLibraryImagePath('person_rounded.svg', 'smartphone-contact-selector');

	class SmartphoneContactSelector
	{
		/**
		 * @params {object} props
		 * @params {boolean} [props.allowMultipleSelection]
		 * @params {layout} [props.parentLayout]
		 * @params {boolean} [props.closeAfterSendButtonClick]
		 * @params {function} [props.onSendButtonClickHandler]
		 * @param {function} [props.onRequestContactsSuccess]
		 * @param {function} [props.onSelectionChanged]
		 */
		constructor(props)
		{
			this.props = props;
			this.selector = null;
			this.allContacts = [];
			this.selectedContacts = [];
			this.isSendButtonLoading = false;

			this.onListFillDebounce = debounce(this.#onListFill, 500, this);
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
			this.selector = await this.parentLayout.openWidget('selector', {
				titleParams: {
					text: Loc.getMessage('CONTACT_SELECTOR_TITLE'),
					type: 'dialog',
				},
				backdrop: {
					mediumPositionPercent: 90,
					horizontalSwipeAllowed: false,
				},
				sendButtonName: Loc.getMessage('CONTACT_SELECTOR_SEND_BUTTON_TEXT'),
			});
			this.selector.setSendButtonEnabled(false);
			this.#initLoadingSelector();
			this.allContacts = await this.#getContacts();
			if (isNil(this.allContacts))
			{
				this.close();

				return;
			}
			const isEmptyItems = !Array.isArray(this.allContacts) || this.allContacts.length === 0;
			if (isEmptyItems)
			{
				this.#setItems([this.#getEmptyResultItem()]);

				return;
			}

			this.#initSelector();
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
				title: Loc.getMessage('CONTACT_SELECTOR_NOTHING_FOUND_ITEM_TEXT'),
				type: 'button',
				sectionCode: SECTION_CODE,
				unselectable: true,
			};
		};

		#initSelector = () => {
			if (this.selector)
			{
				this.selector.enableNavigationBarBorder(false);
				this.selector.setSearchEnabled(true);
				this.selector.allowMultipleSelection(this.allowMultipleSelection);
				this.#setItems(this.#prepareItemsForSelector(this.allContacts));
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
				}
				else
				{
					this.#setItems([this.#getEmptyResultItem()]);
				}
			}
		};

		#setItems = (items) => {
			this.selector?.setItems(items, this.#getNativeSelectorSections());
		};

		getSelectedContacts = () => {
			return this.selectedContacts;
		};

		#onSelectedChangedHandler = ({ items, text, scope }) => {
			if (this.selector)
			{
				this.selector.setSendButtonCounter(items.length);
				this.selector.setSendButtonEnabled(items.length > 0);
				this.selectedContacts = items.map((item) => {
					const targetContact = this.allContacts.find((contact) => contact.id === item.id);
					const phone = item.subtitle;

					return {
						name: targetContact.displayName,
						firstName: targetContact.firstName,
						secondName: targetContact.secondName,
						phone,
						countryCode: getCountryCode(phone),
					};
				});

				if (this.onSelectionChanged)
				{
					this.onSelectionChanged();
				}
			}
		};

		#prepareItemsForSelector = (items) => {
			return items.map((item) => {
				return {
					id: item.id,
					title: item.displayName,
					subtitle: item.phoneNumber,
					useLetterImage: false,
					sectionCode: SECTION_CODE,
					imageUrl: item.avatar ?? imageUri,
				};
			});
		};

		#getNativeSelectorSections = () => {
			return [{ id: SECTION_CODE }];
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
				])
					.catch((error) => {
						console.error(error);
					});

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
				const toOpenSettings = await this.#showNeedToAddContactsAccessMessage();
				if (toOpenSettings)
				{
					Application.openSettings();
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
					[{
						text: Loc.getMessage('CONTACT_SELECTOR_ALERT_OPEN_SETTINGS_BUTTON_TEXT'),
						type: 'default',
						onPress: () => {
							resolve(true);
						},
					},
					{
						text: Loc.getMessage('CONTACT_SELECTOR_ALERT_CANCEL_BUTTON_TEXT'),
						type: 'default',
						onPress: () => {
							resolve(false);
						},
					}],
				);
			});
		};
	}

	module.exports = { SmartphoneContactSelector };
});
