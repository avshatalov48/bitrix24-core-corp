/**
 * @module intranet/invite-new/src/tab/phone
 */
jn.define('intranet/invite-new/src/tab/phone', (require, exports, module) => {
	const { BaseTab } = require('intranet/invite-new/src/tab/base');
	const { EmployeeStatus } = require('intranet/enum');
	const { openNameChecker } = require('intranet/invite-new/src/name-checker');
	const { Loc } = require('loc');
	const { Color, Indent, Component } = require('tokens');
	const { Alert } = require('alert');
	const { DepartmentChooser } = require('intranet/invite-new/src/department-chooser');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { Text3 } = require('ui-system/typography/text');
	const { H3 } = require('ui-system/typography/heading');
	const { SmartphoneContactSelector } = require('layout/ui/smartphone-contact-selector');
	const { showErrorMessage } = require('intranet/invite-new/src/error');

	class PhoneTab extends BaseTab
	{
		get multipleInvite()
		{
			return this.props.multipleInvite ?? true;
		}

		get analytics()
		{
			return this.props.analytics ?? {};
		}

		get creatorEmailConfirmed()
		{
			return this.props.creatorEmailConfirmed ?? true;
		}

		get onInviteSentHandler()
		{
			return this.props.onInviteSentHandler ?? null;
		}

		get onInviteError()
		{
			return this.props.onInviteError ?? null;
		}

		renderTabContent()
		{
			if (this.creatorEmailConfirmed)
			{
				return View(
					{
						style: {
							flex: 1,
							width: '100%',
						},
					},
					this.renderGraphics('phone'),
					this.renderDepartmentCard(),
				);
			}

			return this.renderAdminNotConfirmedEmailTabContent();
		}

		renderAdminNotConfirmedEmailTabContent()
		{
			return View(
				{
					style: {
						flex: 1,
						width: '100%',
					},
				},
				this.renderGraphics('admin-email-not-confirmed'),
				this.renderAdminNotConfirmedEmailCard(),
			);
		}

		renderAdminNotConfirmedEmailCard()
		{
			return Card(
				{
					testId: `${this.testId}-admin-email-not-confirmed-card`,
					border: false,
					style: {
						paddingVertical: Component.cardPaddingB.toNumber(),
						paddingHorizontal: Component.cardPaddingLr.toNumber(),
					},
					design: CardDesign.SECONDARY,
				},
				H3({
					testId: `${this.testId}-admin-email-not-confirmed-card-title`,
					text: Loc.getMessage('INTRANET_ADMIN_EMAIL_NOT_CONFIRMED_CARD_TITLE'),
					style: {
						paddingHorizontal: Indent.L.toNumber(),
						textAlign: 'center',
					},
				}),
				Text3({
					testId: `${this.testId}-admin-email-not-confirmed-card-text`,
					text: Loc.getMessage('INTRANET_ADMIN_EMAIL_NOT_CONFIRMED_CARD_TEXT'),
					color: Color.base1,
					style: {
						paddingHorizontal: Indent.L.toNumber(),
						textAlign: 'center',
					},
				}),
			);
		}

		renderButton()
		{
			if (this.creatorEmailConfirmed)
			{
				return View(
					{
						style: {
							width: '100%',
						},
					},
					Button({
						testId: `${this.testId}-open-contacts-list-button`,
						text: Loc.getMessage('INTRANET_OPEN_CONTACTS_LIST_BUTTON_TEXT'),
						design: ButtonDesign.FILLED,
						size: ButtonSize.L,
						stretched: true,
						style: {
							width: '100%',
							paddingHorizontal: Indent.XL4.toNumber(),
							marginBottom: Indent.XL.toNumber(),
							marginTop: Indent.XS.toNumber(),
						},
						onClick: () => {
							this.analytics.sendChooseContactsEvent();
							this.#openSmartphoneContactsList();
						},
					}),
				);
			}

			return View();
		}

		renderDepartmentCard()
		{
			return new DepartmentChooser({
				layout: this.props.layout,
				department: this.department,
				selectedDepartmentChanged: (selectedDepartment) => {
					this.analytics.setDepartmentParam(selectedDepartment !== null);
					this.department = selectedDepartment;
				},
			});
		}

		#openSmartphoneContactsList()
		{
			const controlInstance = new SmartphoneContactSelector({
				allowMultipleSelection: this.multipleInvite,
				parentLayout: this.props.layout,
				closeAfterSendButtonClick: false,
				onSendButtonClickHandler: this.onContactsSelectorSendButtonClickHandler,
				onRequestContactsSuccess: this.onRequestContactsSuccessHandler,
				onSelectionChanged: this.onSelectionChanged,
			});

			void controlInstance.open();
		}

		onSelectionChanged = () => {
			this.analytics.sendSelectFromContactListEvent();
		};

		onRequestContactsSuccessHandler = () => {
			this.analytics.sendAllowContactsEvent();
		};

		onContactsSelectorSendButtonClickHandler = async (selectedContacts, selectorInstance) => {
			if (!Array.isArray(selectedContacts) || selectedContacts.length === 0)
			{
				return;
			}

			this.analytics.sendContactListContinueEvent(selectedContacts.length > 1);

			const phoneNumbers = selectedContacts.map((contact) => {
				return {
					phone: contact.phone,
					countryCode: contact.countryCode,
				};
			});

			const response = await this.getPhoneNumbersInviteStatus(phoneNumbers);
			if (Array.isArray(response.errors) && response.errors.length > 0)
			{
				await showErrorMessage(response.errors[0]);
				selectorInstance.enableSendButtonLoadingIndicator(false);
				console.error(response.errors);

				return;
			}

			const equalsSelectedContacts = this.getEqualContactsFromSelected(selectedContacts, response.data);
			if (equalsSelectedContacts.length > 0)
			{
				await this.showEqualContactsMessage(equalsSelectedContacts);
				selectorInstance.enableSendButtonLoadingIndicator(false);

				return;
			}

			if (
				!Array.isArray(response.data)
				|| response.data.length === 0
				|| selectedContacts.length !== response.data.length
			)
			{
				selectorInstance.enableSendButtonLoadingIndicator(false);

				return;
			}

			const invalidContacts = [];
			const notInvitedContacts = [];
			const invitedContacts = [];

			response.data.forEach((contact) => {
				const targetSelectedContact = selectedContacts.find((item) => item.phone === contact.phone);
				if (!targetSelectedContact)
				{
					return;
				}

				const preparedContact = {
					...targetSelectedContact,
					...contact,
				};

				if (!contact.isValidPhoneNumber)
				{
					invalidContacts.push(preparedContact);

					return;
				}

				preparedContact.id = preparedContact.formattedPhone;

				if (contact.inviteStatus === EmployeeStatus.NOT_REGISTERED.getValue()
					|| contact.inviteStatus === EmployeeStatus.INVITED.getValue())
				{
					notInvitedContacts.push(preparedContact);

					return;
				}

				invitedContacts.push(preparedContact);
			});

			if (invalidContacts.length > 0)
			{
				const existsAnotherContactsToProcess = invitedContacts.length > 0 || notInvitedContacts.length > 0;
				await this.showInvalidContactsMessage(invalidContacts, existsAnotherContactsToProcess);
			}

			if (invitedContacts.length > 0)
			{
				const existsAnotherContactsToProcess = notInvitedContacts.length > 0;
				await this.showInvitedContactsMessage(invitedContacts, existsAnotherContactsToProcess);
			}

			if (notInvitedContacts.length > 0)
			{
				selectorInstance.close();
				setTimeout(() => {
					openNameChecker({
						parentLayout: this.props.layout,
						usersToInvite: notInvitedContacts,
						department: this.department,
						analytics: this.analytics,
						onInviteSentHandler: this.onInviteSentHandler,
						onInviteError: this.onInviteError,
					});
				}, 500);

				return;
			}

			selectorInstance.enableSendButtonLoadingIndicator(false);
		};

		getEqualContactsFromSelected(selectedContacts, contactsFromServer)
		{
			if (Array.isArray(selectedContacts) && selectedContacts.length > 0
				&& Array.isArray(contactsFromServer) && contactsFromServer.length > 0)
			{
				const uniqueFormattedPhoneNumbers = {};
				for (const selectedContact of selectedContacts)
				{
					const targetContactFromServer = contactsFromServer.find((item) => item.phone === selectedContact.phone);
					if (targetContactFromServer)
					{
						if (uniqueFormattedPhoneNumbers[targetContactFromServer.formattedPhone])
						{
							return [uniqueFormattedPhoneNumbers[targetContactFromServer.formattedPhone], selectedContact];
						}

						uniqueFormattedPhoneNumbers[targetContactFromServer.formattedPhone] = selectedContact;
					}
				}
			}

			return [];
		}

		showEqualContactsMessage(equalContacts)
		{
			return new Promise((resolve) => {
				let phonesNumbersString = '';
				equalContacts.forEach((contact, index) => {
					phonesNumbersString += `${contact.name} (${contact.phone})${index === equalContacts.length - 1 ? '' : ', '}`;
				});

				Alert.confirm(
					Loc.getMessage('INTRANET_INVITE_EQUAL_PHONE_NUMBERS_ALERT_TITLE'),
					Loc.getMessage('INTRANET_INVITE_EQUAL_PHONE_NUMBERS_ALERT_DESCRIPTION', {
						'#phonesNumbersString#': phonesNumbersString,
					}),
					[{
						text: Loc.getMessage('INTRANET_INVITE_EQUAL_PHONE_NUMBERS_ALERT_OK_BUTTON_TEXT'),
						type: 'default',
						onPress: () => {
							resolve();
						},
					}],
				);
			});
		}

		showInvalidContactsMessage(invalidContacts)
		{
			return new Promise((resolve) => {
				let phonesNumbersString = '';
				invalidContacts.forEach((contact, index) => {
					phonesNumbersString += `${contact.name} (${contact.phone})${index === invalidContacts.length - 1 ? '' : ', '}`;
				});

				Alert.confirm(
					Loc.getMessage('INTRANET_INVITE_INVALID_PHONE_NUMBER_ALERT_TITLE'),
					Loc.getMessage('INTRANET_INVITE_INVALID_PHONE_NUMBER_ALERT_DESCRIPTION', {
						'#phonesNumbersString#': phonesNumbersString,
					}),
					[{
						text: Loc.getMessage('INTRANET_INVITE_INVALID_PHONE_NUMBER_ALERT_OK_BUTTON_TEXT'),
						type: 'default',
						onPress: () => {
							resolve();
						},
					}],
				);
			});
		}

		showInvitedContactsMessage(invitedContacts)
		{
			return new Promise((resolve) => {
				let phonesNumbersString = '';
				invitedContacts.forEach((contact, index) => {
					phonesNumbersString += `${contact.name} (${contact.phone})${index === invitedContacts.length - 1 ? '' : ', '}`;
				});

				Alert.confirm(
					Loc.getMessage('INTRANET_INVITE_INVITED_PHONE_NUMBER_ALERT_TITLE'),
					Loc.getMessage('INTRANET_INVITE_INVITED_PHONE_NUMBER_ALERT_DESCRIPTION', {
						'#phonesNumbersString#': phonesNumbersString,
					}),
					[{
						text: Loc.getMessage('INTRANET_INVITE_INVITED_PHONE_NUMBER_ALERT_OK_BUTTON_TEXT'),
						type: 'default',
						onPress: () => {
							resolve();
						},
					}],
				);
			});
		}

		getPhoneNumbersInviteStatus(phoneNumbers)
		{
			return new Promise((resolve) => {
				new RunActionExecutor('intranetmobile.invite.getPhoneNumbersInviteStatus', {
					phoneNumbers,
				})
					.setHandler((result) => resolve(result))
					.call(false);
			});
		}
	}

	module.exports = { PhoneTab };
});
