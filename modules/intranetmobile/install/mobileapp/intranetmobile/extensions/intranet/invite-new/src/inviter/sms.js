/**
 * @module intranet/invite-new/src/inviter/sms
 */
jn.define('intranet/invite-new/src/inviter/sms', (require, exports, module) => {
	const { BaseInviter } = require('intranet/invite-new/src/inviter/base');
	const { EmployeeStatus } = require('intranet/enum');
	const { openNameChecker } = require('layout/ui/name-checker-box');
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { SmartphoneContactSelector } = require('layout/ui/smartphone-contact-selector');
	const { showErrorMessage } = require('intranet/invite-new/src/error');
	const { getFormattedNumber } = require('utils/phone');
	const { InviteCases } = require('intranet/invite-new/src/inviter/type');
	const { Icon } = require('assets/icons');

	/**
	 * @param props
	 * @class SMSInviter
	 */
	class SMSInviter extends BaseInviter
	{
		async open()
		{
			this.getAnalytics().sendChooseContactsEvent?.();
			const controlInstance = new SmartphoneContactSelector({
				allowMultipleSelection: this.isMultipleInviteAllowed(),
				parentLayout: this.getLayout(),
				closeAfterSendButtonClick: false,
				onSendButtonClickHandler: this.onContactsSelectorSendButtonClickHandler,
				onRequestContactsSuccess: this.onRequestContactsSuccessHandler,
				onSelectionChanged: this.onSelectionChanged,
				initialContacts: this.props.initialContacts ?? null,
				dismissAlert: {
					title: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_TITLE'),
					description: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_DESCRIPTION'),
					destructiveButtonText: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_DESTRUCTIVE_BUTTON'),
					defaultButtonText: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_CONTINUE_BUTTON'),
				},
			});

			await controlInstance.open();
		}

		onSelectionChanged = () => {
			this.getAnalytics().sendSelectFromContactListEvent();
		};

		onRequestContactsSuccessHandler = () => {
			this.getAnalytics().sendAllowContactsEvent();
		};

		onContactsSelectorSendButtonClickHandler = async (
			selectedContacts,
			selectorInstance,
			isManuallyTypedPhoneNumber,
		) => {
			if (!Array.isArray(selectedContacts) || selectedContacts.length === 0)
			{
				return;
			}

			if (isManuallyTypedPhoneNumber)
			{
				this.getAnalytics().sendTypeNumberWithoutContactListEvent();
			}
			else
			{
				this.getAnalytics().sendContactListContinueEvent(selectedContacts.length > 1);
			}

			const phoneNumbers = selectedContacts.map((contact) => contact.phone);
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

			const {
				invalidContacts,
				contactsToInvite,
				alreadyInvitedContacts,
			} = this.#getPhonesGroupsFromResponse(response, selectedContacts);

			if (invalidContacts.length > 0)
			{
				await this.showInvalidContactsMessage(invalidContacts);
			}

			if (contactsToInvite.length > 0)
			{
				selectorInstance.close(async () => {
					this.nameChecker = await openNameChecker({
						parentLayout: this.getLayout(),
						usersToInvite: contactsToInvite,
						alreadyInvitedUsers: alreadyInvitedContacts,
						analytics: this.getAnalytics(),
						labelText: this.renderNameCheckerDepartmentChooser,
						inviteButtonText: Loc.getMessage('INTRANET_INVITE_BUTTON_TEXT'),
						boxTitle: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_TITLE_MSGVER_1'),
						description: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_DESCRIPTION_MSGVER_1'),
						onSendInviteButtonClick: this.onSendInviteButtonClick,
						getItemFormattedSubDescription: this.getItemFormattedSubDescription,
						getAlreadyInvitedUsersStringForSubtitle: this.#getAlreadyInvitedUsersStringForSubtitle,
						getAlreadyInvitedUsersLabelTextForSubtitle: this.getAlreadyInvitedUsersLabelTextForSubtitle,
						dismissAlert: {
							title: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_TITLE'),
							description: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_DESCRIPTION'),
							destructiveButtonText: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_DESTRUCTIVE_BUTTON'),
							defaultButtonText: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_CONTINUE_BUTTON'),
						},
					});
				});

				return;
			}

			if (alreadyInvitedContacts.length > 0)
			{
				await this.showInvitedContactsMessage(alreadyInvitedContacts);
			}

			selectorInstance.enableSendButtonLoadingIndicator(false);
		};

		#getAlreadyInvitedUsersStringForSubtitle = (users) => {
			return users.map((user) => `${user.name} ${getFormattedNumber(user.formattedPhone)}`).join(', ');
		};

		#getPhonesGroupsFromResponse(response, selectedContacts)
		{
			const invalidContacts = [];
			const contactsToInvite = [];
			const alreadyInvitedContacts = [];

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

				if (contact.inviteStatus === EmployeeStatus.NOT_REGISTERED.getName()
					|| contact.inviteStatus === EmployeeStatus.INVITED.getName())
				{
					contactsToInvite.push(preparedContact);

					return;
				}

				alreadyInvitedContacts.push(preparedContact);
			});

			return {
				invalidContacts,
				contactsToInvite,
				alreadyInvitedContacts,
			};
		}

		getItemFormattedSubDescription = (user) => {
			return Loc.getMessage('INTRANET_INVITE_FORMATTED_PHONE_TEXT', {
				'#phone#': getFormattedNumber(user.formattedPhone),
			});
		};

		onSendInviteButtonClick = async (usersToInvite) => {
			const multipleInvitation = usersToInvite.length > 1;
			const response = await this.inviteUsersByPhoneNumbers(usersToInvite);
			await this.handleInviteResponse(response, multipleInvitation, InviteCases.SMS);
		};

		inviteUsersByPhoneNumbers = (usersToInvite) => {
			const preparedUsers = usersToInvite.map((user) => {
				return {
					phoneNumber: user.phone,
					name: user.firstName ?? null,
					lastName: user.secondName ?? null,
					phoneCountry: user.countryCode,
				};
			});

			return new Promise((resolve) => {
				new RunActionExecutor('intranet.invite.inviteUsersByPhoneNumber', {
					phoneInvitations: preparedUsers,
					departmentIds: this.getDepartmentIds(),
				})
					.setHandler((result) => resolve(result))
					.call(false);
			});
		};

		getEqualContactsFromSelected(selectedContacts, contactsFromServer)
		{
			if (Array.isArray(selectedContacts) && selectedContacts.length > 0
				&& Array.isArray(contactsFromServer) && contactsFromServer.length > 0)
			{
				const preparedContacts = contactsFromServer.map((contact) => (contact.isValidPhoneNumber ? contact : {
					...contact,
					formattedPhone: getFormattedNumber(contact.phone),
				}));
				const uniqueFormattedPhoneNumbers = {};
				for (const selectedContact of selectedContacts)
				{
					const targetContactFromServer = preparedContacts.find((item) => item.phone === selectedContact.phone);
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

		showContactsMessage(contacts, titleKey, descriptionKey, buttonTextKey) {
			return new Promise((resolve) => {
				const phonesNumbersString = contacts.map((contact) => (
					contact.name
						? `${contact.name} ${contact.phone}`
						: contact.phone
				)).join(', ');

				Alert.confirm(
					Loc.getMessage(titleKey),
					Loc.getMessage(descriptionKey, {
						'#phonesNumbersString#': phonesNumbersString,
					}),
					[{
						text: Loc.getMessage(buttonTextKey),
						type: 'default',
						onPress: resolve,
					}],
				);
			});
		}

		showEqualContactsMessage(equalContacts) {
			return this.showContactsMessage(
				equalContacts,
				'INTRANET_INVITE_EQUAL_PHONE_NUMBERS_ALERT_TITLE',
				'INTRANET_INVITE_EQUAL_PHONE_NUMBERS_ALERT_DESCRIPTION',
				'INTRANET_INVITE_EQUAL_PHONE_NUMBERS_ALERT_OK_BUTTON_TEXT',
			);
		}

		showInvalidContactsMessage(invalidContacts) {
			return this.showContactsMessage(
				invalidContacts,
				'INTRANET_INVITE_INVALID_PHONE_NUMBER_ALERT_TITLE',
				'INTRANET_INVITE_INVALID_PHONE_NUMBER_ALERT_DESCRIPTION',
				'INTRANET_INVITE_INVALID_PHONE_NUMBER_ALERT_OK_BUTTON_TEXT',
			);
		}

		showInvitedContactsMessage(invitedContacts) {
			return this.showContactsMessage(
				invitedContacts,
				'INTRANET_INVITE_INVITED_PHONE_NUMBER_ALERT_TITLE',
				'INTRANET_INVITE_INVITED_PHONE_NUMBER_ALERT_DESCRIPTION',
				'INTRANET_INVITE_INVITED_PHONE_NUMBER_ALERT_OK_BUTTON_TEXT',
			);
		}

		getPhoneNumbersInviteStatus(phones)
		{
			return new Promise((resolve) => {
				new RunActionExecutor('intranet.invite.getPhoneNumbersInviteStatus', {
					phones,
				})
					.setHandler((result) => resolve(result))
					.call(false);
			});
		}

		isDisabledInviteMethod()
		{
			return !this.canInviteBySMS();
		}

		getIconNameForUIMenu()
		{
			return Icon.CONTACT;
		}

		getSortOrder()
		{
			return 20;
		}

		getImageFileName()
		{
			return 'invite-by-phone.svg';
		}
	}

	module.exports = { SMSInviter };
});
