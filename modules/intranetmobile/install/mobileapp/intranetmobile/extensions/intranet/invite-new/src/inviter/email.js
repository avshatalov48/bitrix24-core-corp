/**
 * @module intranet/invite-new/src/inviter/email
 */
jn.define('intranet/invite-new/src/inviter/email', (require, exports, module) => {
	const { BaseInviter } = require('intranet/invite-new/src/inviter/base');
	const { EmployeeStatus } = require('intranet/enum');
	const { openNameChecker } = require('layout/ui/name-checker-box');
	const { Loc } = require('loc');
	const { Alert } = require('alert');
	const { Type } = require('type');
	const { openEmailInputBox, ChipDesign } = require('layout/ui/email-input-box');
	const { ajaxPublicErrorHandler } = require('error');
	const { RunActionExecutor } = require('rest/run-action-executor');
	const { InviteCases } = require('intranet/invite-new/src/inviter/type');
	const { Icon } = require('assets/icons');
	const { EmailInviteSwitcherBox } = require('intranet/invite-new/src/email-invite-switcher-box');

	/**
	 * @class EmailInviter
	 */
	class EmailInviter extends BaseInviter
	{
		async open()
		{
			this.getAnalytics().sendChooseEmailsEvent?.();
			if (
				this.props.isInviteWithLocalEmailAppEnabled
				&& this.props.canInviteByLink
				&& this.#canInviteByLocalEmailClient()
				&& this.inviteLink
			)
			{
				this.inviteSwitcherBoxInstance = await EmailInviteSwitcherBox.open({
					parentWidget: this.getLayout(),
					analytics: this.getAnalytics(),
					onInviteButtonClick: this.#onInviteWithLocalEmailClientButtonClick,
					onInviteByBitrix24ButtonClick: this.#onInviteByBitrix24ButtonClick,
					onClose: this.#onInviteSwitcherBoxClose,
				});
				this.getAnalytics().sendInvitationLocalDrawerOpenEvent?.(this.isAdminConfirm());

				return;
			}

			await this.#openEmailInputBox();
		}

		setInviteLink(inviteLink)
		{
			this.inviteLink = inviteLink;
		}

		#onInviteSwitcherBoxClose = () => {
			this.inviteSwitcherBoxInstance = null;
		};

		#onInviteByBitrix24ButtonClick = async () => {
			this.getAnalytics().sendInvitationLocalMailSkipEvent?.();
			await this.#openEmailInputBox();
		};

		#onInviteWithLocalEmailClientButtonClick = () => {
			if (!this.inviteLink)
			{
				this.showAdminEmailNotConfirmedBoxByInviteCase(InviteCases.LINK);

				return;
			}
			this.getAnalytics().sendInvitationLocalMailEvent?.(this.isAdminConfirm());
			this.#openLocalEmailClient();
		};

		#canInviteByLocalEmailClient = () => {
			return Application.canOpenUrl('mailto:%20?subject=subjectText&body=bodyText');
		};

		#openLocalEmailClient = () => {
			const subject = encodeURIComponent(Loc.getMessage('INTRANET_INVITE_SUBJECT_FOR_LOCAL_EMAIL_CLIENT'));
			const body = encodeURIComponent(Loc.getMessage('INTRANET_INVITE_SHARING_MESSAGE_FOR_LOCAL_EMAIL_CLIENT', {
				'#link#': this.inviteLink,
			}));
			const mailto = `mailto:%20?subject=${subject}&body=${body}`;
			Application.openUrl(mailto);
		};

		#openEmailInputBox = async () => {
			this.inviteByEmailBoxRef = await openEmailInputBox({
				testId: 'invite-employees',
				parentLayout: this.getLayout(),
				description: Loc.getMessage('INTRANET_EMAIL_INPUT_BOX_DESCRIPTION'),
				title: Loc.getMessage('INTRANET_INVITE_BY_EMAIL_BOX_TITLE_MSGVER_1'),
				bottomButtonText: Loc.getMessage('INTRANET_INVITE_BY_EMAIL_CONTINUE_BUTTON_TEXT'),
				inputPlaceholder: Loc.getMessage('INTRANET_INVITE_BY_EMAIL_NEW_EMAIL_INPUT_PLACEHOLDER_MSGVER_1'),
				onButtonClick: this.#onEmailInputBoxInviteButtonClick,
				emailChipDesign: ChipDesign.DEFAULT,
				dismissAlert: {
					title: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_TITLE'),
					description: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_DESCRIPTION'),
					destructiveButtonText: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_DESTRUCTIVE_BUTTON'),
					defaultButtonText: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_CONTINUE_BUTTON'),
				},
			});
		};

		getLayout()
		{
			return this.inviteSwitcherBoxInstance?.getLayout() ?? this.props.layout;
		}

		#onEmailInputBoxInviteButtonClick = async (emails) => {
			this.getAnalytics().sendEmailsContinueEvent?.();
			await this.#processEmails(emails);
		};

		#removeDuplicateEmails = (emails) => {
			return [...new Set(emails)];
		};

		#processEmails = async (emails) => {
			if (!Type.isArrayFilled(emails))
			{
				return;
			}

			const uniqueEmails = this.#removeDuplicateEmails(emails);
			const response = await this.#getEmailsInviteStatus(uniqueEmails);
			if (!this.#isInviteStatusResponseValid(response, uniqueEmails.length))
			{
				this.inviteByEmailBoxRef?.disableButtonLoading();

				return;
			}

			const {
				invalidEmails,
				notInvitedEmails,
				invitedEmails,
			} = this.#getEmailsGroupsFromResponse(response, uniqueEmails);

			if (invalidEmails.length > 0)
			{
				await this.showInvalidEmailsMessage(invalidEmails);
			}

			if (invitedEmails.length > 0 && notInvitedEmails.length === 0)
			{
				await this.showInvitedEmailsMessage(invitedEmails);
			}

			const usersToInvite = this.#addIdFieldToUserItems(notInvitedEmails, 'email');
			const invitedUsers = this.#addIdFieldToUserItems(invitedEmails, 'email');
			if (usersToInvite.length > 0)
			{
				this.inviteByEmailBoxRef?.close(async () => {
					this.nameChecker = await this.#openNameCheckerForEmails(usersToInvite, invitedUsers);
				});

				return;
			}

			this.inviteByEmailBoxRef?.disableButtonLoading();
		};

		showEmailsMessage(contacts, titleKey, descriptionKey, buttonTextKey) {
			return new Promise((resolve) => {
				const emailsString = contacts.map((contact) => contact.email?.toLowerCase()).join(', ');

				Alert.confirm(
					Loc.getMessage(titleKey),
					Loc.getMessage(descriptionKey, {
						'#emailString#': emailsString,
						'#emailsString#': emailsString,
					}),
					[{
						text: Loc.getMessage(buttonTextKey),
						type: 'default',
						onPress: resolve,
					}],
				);
			});
		}

		showInvalidEmailsMessage(invalidEmails) {
			return this.showEmailsMessage(
				invalidEmails,
				'INTRANET_INVITE_INVALID_EMAIL_ALERT_TITLE',
				'INTRANET_INVITE_INVALID_EMAIL_ALERT_DESCRIPTION',
				'INTRANET_INVITE_INVALID_EMAIL_ALERT_OK_BUTTON_TEXT',
			);
		}

		showInvitedEmailsMessage(invitedContacts) {
			return this.showEmailsMessage(
				invitedContacts,
				'INTRANET_INVITE_INVITED_EMAIL_ALERT_TITLE',
				'INTRANET_INVITE_INVITED_EMAIL_ALERT_DESCRIPTION',
				'INTRANET_INVITE_INVITED_PHONE_NUMBER_ALERT_OK_BUTTON_TEXT',
			);
		}

		#openNameCheckerForEmails = async (usersToInvite, alreadyInvitedUsers) => {
			return openNameChecker({
				parentLayout: this.getLayout(),
				usersToInvite,
				alreadyInvitedUsers,
				labelText: this.renderNameCheckerDepartmentChooser,
				inviteButtonText: Loc.getMessage('INTRANET_INVITE_BUTTON_TEXT'),
				boxTitle: Loc.getMessage('INTRANET_INVITE_BY_EMAIL_NAME_CHECKER_TITLE'),
				description: Loc.getMessage('INTRANET_INVITE_BY_EMAIL_NAME_CHECKER_DESCRIPTION'),
				onSendInviteButtonClick: this.#onSendInviteButtonClick,
				getItemFormattedSubDescription: this.#getEmailItemFormattedSubDescription,
				getAlreadyInvitedUsersStringForSubtitle: this.#getAlreadyInvitedUsersFormattedEmails,
				getAlreadyInvitedUsersLabelTextForSubtitle: this.getAlreadyInvitedUsersLabelTextForSubtitle,
				dismissAlert: {
					title: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_TITLE'),
					description: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_DESCRIPTION'),
					destructiveButtonText: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_DESTRUCTIVE_BUTTON'),
					defaultButtonText: Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_CLOSE_ALERT_CONTINUE_BUTTON'),
				},
			});
		};

		#getAlreadyInvitedUsersFormattedEmails = (users) => {
			return users.map((user) => user.email).join(', ');
		};

		#onSendInviteButtonClick = async (usersToInvite) => {
			const multipleInvitation = usersToInvite.length > 1;
			const response = await this.#inviteUsersByEmails(usersToInvite);
			await this.handleInviteResponse(response, multipleInvitation, InviteCases.EMAIL);
		};

		#inviteUsersByEmails = (usersToInvite) => {
			const preparedUsers = usersToInvite.map((user) => {
				return {
					email: user.email,
					name: user.firstName ?? null,
					lastName: user.secondName ?? null,
				};
			});

			return new Promise((resolve) => {
				new RunActionExecutor('intranet.invite.inviteUsersByEmail', {
					emailInvitations: preparedUsers,
					departmentIds: this.getDepartmentIds(),
				})
					.setHandler((result) => resolve(result))
					.call(false);
			});
		};

		#getEmailItemFormattedSubDescription = (user) => {
			return Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_EMAIL_ITEM_SUBDESCRIPTION_TEXT', {
				'#email#': user.email?.toLowerCase(),
			});
		};

		#addIdFieldToUserItems = (users, idField) => {
			return users.map((user) => ({
				...user,
				id: user[idField],
			}));
		};

		#getEmailsGroupsFromResponse(response, emails)
		{
			const invalidEmails = [];
			const notInvitedEmails = [];
			const invitedEmails = [];

			response.data.forEach((user) => {
				const targetEmail = emails.find((email) => email.toLowerCase() === user.email.toLowerCase());
				if (!targetEmail)
				{
					return;
				}

				if (!user.isValidEmail)
				{
					invalidEmails.push(user);

					return;
				}

				if (user.inviteStatus === EmployeeStatus.NOT_REGISTERED.getName()
					|| user.inviteStatus === EmployeeStatus.INVITED.getName())
				{
					notInvitedEmails.push(user);

					return;
				}

				invitedEmails.push(user);
			});

			return {
				invalidEmails,
				notInvitedEmails,
				invitedEmails,
			};
		}

		#isInviteStatusResponseValid(response, requestItemsCount)
		{
			return !(response.status !== 'success'
				|| !Array.isArray(response.data)
				|| requestItemsCount !== response.data.length);
		}

		#getEmailsInviteStatus = async (emails) => {
			return BX.ajax.runAction('intranet.invite.getEmailsInviteStatus', {
				data: { emails },
			})
				.catch(ajaxPublicErrorHandler);
		};

		getIconNameForUIMenu()
		{
			return Icon.MAIL;
		}

		isDisabledInviteMethod()
		{
			return !this.canInviteByEmail();
		}

		getSortOrder()
		{
			return 30;
		}

		isAvailableInviteMethod()
		{
			return true;
		}

		getImageFileName()
		{
			return 'invite-by-email.svg';
		}

		shouldUpdateInviteLink()
		{
			return true;
		}
	}

	module.exports = { EmailInviter };
});
