/**
 * @module collab/invite/src/guests-tab-content
 */
jn.define('collab/invite/src/guests-tab-content', (require, exports, module) => {
	const { Box } = require('ui-system/layout/box');
	const { AreaList } = require('ui-system/layout/area-list');
	const { Area } = require('ui-system/layout/area');
	const { makeLibraryImagePath } = require('asset-manager');
	const { Color, Indent, Component } = require('tokens');
	const { H4 } = require('ui-system/typography/heading');
	const { Loc } = require('loc');
	const { Text3 } = require('ui-system/typography/text');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons');
	const { BoxFooter } = require('ui-system/layout/dialog-footer');
	const { SmartphoneContactSelector } = require('layout/ui/smartphone-contact-selector');
	const { openEmailInputBox } = require('layout/ui/email-input-box');
	const { openNameChecker } = require('layout/ui/name-checker-box');
	const { Alert, ButtonType } = require('alert');
	const { EmployeeStatus } = require('collab/invite/src/enum');
	const { Avatar } = require('ui-system/blocks/avatar');
	const { getFormattedNumber } = require('utils/phone');
	const { Type } = require('type');
	const { Link4, LinkMode, Ellipsize } = require('ui-system/blocks/link');
	const { ajaxPublicErrorHandler } = require('error');
	const {
		inviteGuestsToCollab,
		addEmployeeToCollab,
		getLinkByCollabId,
		getSharingMessageText,
	} = require('collab/invite/src/api');
	const { AvatarEntityType } = require('ui-system/blocks/avatar');
	const { QRInvite, QrEntity } = require('layout/ui/qr-invite');
	const {
		showSuccessInvitationToast,
		openGuestsInviteRestrictionsBox,
	} = require('collab/invite/src/utils');
	const { Notify } = require('notify');
	const { Line } = require('utils/skeleton');
	const { isNil } = require('utils/type');
	const { BBCodeText } = require('ui-system/typography/bbcodetext');
	const { UIMenu } = require('layout/ui/menu');
	const { createTestIdGenerator } = require('utils/test');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { AhaMoment } = require('ui-system/popups/aha-moment');

	const detailsButtonArticleCode = '22706828';

	/**
	 * @typedef {Object} GuestsTabContentProps
	 * @property {PageManager} parentLayout
	 * @property {PageManager} layout
	 * @property {boolean} pending
	 * @property {boolean} isBitrix24Included
	 * @property {CollabInviteAnalytics} analytics
	 * @property {boolean} canInviteCollabersInPortalSettings
	 * @property {object} languages

	 * @class GuestsTabContent
	 */
	class GuestsTabContent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			const {
				pending,
				isBitrix24Included,
			} = this.props;

			this.state = {
				pending,
				isBitrix24Included,
				languageCode: this.#getDefaultLanguageCode(),
			};

			this.getTestId = createTestIdGenerator({
				prefix: 'collab-invite-guests-tab',
			});
		}

		get isBitrix24Included()
		{
			return this.state.isBitrix24Included ?? false;
		}

		render()
		{
			const { pending } = this.state;

			if (pending)
			{
				return this.#renderSkeleton();
			}

			return Box(
				{
					testId: this.getTestId(),
					safeArea: {
						bottom: true,
					},
					footer: this.#renderButtons(),
				},
				AreaList(
					{
						testId: this.getTestId('area-list'),
					},
					this.#renderGraphicsWithDescription(),
				),
			);
		}

		#renderSkeleton = () => {
			return Box(
				{
					testId: this.getTestId(),
					safeArea: {
						bottom: true,
					},
					footer: BoxFooter(
						{
							safeArea: true,
							testId: this.getTestId('buttons'),
							style: {
								width: '100%',
							},
						},
						View(
							{
								style: {
									alignItems: 'center',
								},
							},
							Line(320, 42, 0, 0, 8),
							Line(160, 18, 18, 0, 8),
						),
					),
				},
				AreaList(
					{
						testId: this.getTestId('area-list'),
					},
					Area(
						{},
						View(
							{
								style: {
									paddingHorizontal: Component.paddingLr.toNumber(),
									alignItems: 'center',
								},
							},
							Line(190, 135, 24, Indent.XL3.toNumber(), 16),
							Line(260, 21, 13, 8, 8),
							Line(270, 16, 8, 0, 8),
							Line(250, 16, 8, 0, 8),
							Line(310, 16, 8, 0, 8),
							Line(230, 16, 8, 0, 8),
							Line(110, 18, 22, 0, 8),
						),
					),
				),
			);
		};

		update = ({ pending, isBitrix24Included }) => {
			this.setState({
				pending,
				isBitrix24Included,
			});
		};

		#renderButtons()
		{
			const { canInviteCollabersInPortalSettings, boxLayout } = this.props;

			return BoxFooter(
				{
					safeArea: true,
					testId: this.getTestId('buttons'),
				},
				Button(
					{
						testId: this.getTestId('by-link-button'),
						text: Loc.getMessage('COLLAB_INVITE_TAB_GUESTS_BY_LINK_BUTTON'),
						size: ButtonSize.L,
						design: ButtonDesign.PRIMARY,
						leftIcon: Icon.LINK,
						stretched: true,
						style: {
							marginBottom: Indent.L.toNumber(),
						},
						onClick: async () => {
							if (!canInviteCollabersInPortalSettings)
							{
								await openGuestsInviteRestrictionsBox({
									parentWidget: boxLayout,
								});

								return;
							}

							const {
								inviteLink,
								sharingMessageText,
							} = await this.#loadInviteLinkAndSharingMessageText();

							dialogs.showSharingDialog({
								title: Loc.getMessage('INTRANET_SHARING_LINK_DIALOG_TITLE'),
								message: this.#getSharingMessageWithLink(inviteLink, sharingMessageText),
							});
						},
					},
				),
				Button(
					{
						testId: this.getTestId('by-other-button'),
						forwardRef: (ref) => {
							this.inviteCasesButtonRef = ref;
						},
						text: Loc.getMessage('COLLAB_INVITE_TAB_GUESTS_BY_OTHER_BUTTON'),
						size: ButtonSize.S,
						design: ButtonDesign.PLAN_ACCENT,
						stretched: true,
						onClick: async () => {
							await this.#openOtherInviteCasesMenu();
						},
					},
				),
			);
		}

		#loadInviteLinkAndSharingMessageText = async (showLoadingIndicator = true) => {
			const { languageCode } = this.state;
			const { collabId } = this.props;

			if (showLoadingIndicator)
			{
				void Notify.showIndicatorLoading();
			}

			const results = await Promise.allSettled([
				getLinkByCollabId(collabId, languageCode),
				getSharingMessageText(languageCode),
			]);

			if (showLoadingIndicator)
			{
				Notify.hideCurrentIndicator();
			}

			const inviteLink = results[0]?.value?.status === 'success'
				? results[0].value.data
				: null;

			const sharingMessageText = results[1]?.value?.status === 'success'
				? results[1].value.data
				: null;

			return { inviteLink, sharingMessageText };
		};

		#getSharingMessageWithLink = (inviteLink, sharingMessage) => {
			if (inviteLink && sharingMessage)
			{
				return sharingMessage.replaceAll('#link#', inviteLink);
			}

			return '';
		};

		#openOtherInviteCasesMenu = async () => {
			const { canInviteCollabersInPortalSettings, boxLayout } = this.props;
			if (!canInviteCollabersInPortalSettings)
			{
				await openGuestsInviteRestrictionsBox({
					parentWidget: boxLayout,
				});

				return;
			}

			this.menu = new UIMenu(this.#getInviteCasesItems());
			this.menu.show({
				target: this.inviteCasesButtonRef,
			});
		};

		#openSmartphoneContactsList()
		{
			const controlInstance = new SmartphoneContactSelector({
				avatarType: AvatarEntityType.COLLAB,
				allowMultipleSelection: true,
				parentLayout: this.props.layout,
				closeAfterSendButtonClick: false,
				onSendButtonClickHandler: this.#onContactsSelectorSendButtonClick,
				dismissAlert: {
					title: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_TITLE'),
					description: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_DESCRIPTION'),
					destructiveButtonText: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_DESTRUCTIVE_BUTTON'),
					defaultButtonText: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_CONTINUE_BUTTON'),
				},
			});

			void controlInstance.open();
		}

		#onContactsSelectorSendButtonClick = (selectedContacts, selectorInstance) => {
			void this.#processContacts(selectedContacts, selectorInstance);
		};

		#processContacts = async (selectedContacts, selectorInstance) => {
			if (!Type.isArrayFilled(selectedContacts))
			{
				return;
			}

			if (this.#isCollaberOrExtranet())
			{
				const usersToInvite = selectedContacts.map((contact) => {
					const { phone } = contact;

					return {
						phone,
						isValidPhoneNumber: true,
						inviteStatus: EmployeeStatus.NOT_REGISTERED.getValue(),
						formattedPhone: getFormattedNumber(phone),
						id: phone,
					};
				});

				await this.#closeInviteBox();
				await Notify.showIndicatorLoading();
				await this.#inviteUsers(usersToInvite, []);
				Notify.hideCurrentIndicator();

				return;
			}

			const phoneNumbers = selectedContacts.map((contact) => contact.phone);
			const response = await this.#getPhoneNumbersInviteStatus(phoneNumbers);
			if (!this.#isInviteStatusResponseValid(response, selectedContacts.length))
			{
				selectorInstance.enableSendButtonLoadingIndicator(false);

				return;
			}

			const {
				invalidContacts,
				notInvitedContacts,
				invitedContacts,
			} = this.#getContactGroupsFromResponse(response, selectedContacts);

			if (invalidContacts.length > 0)
			{
				const existsAnotherContactsToProcess = invitedContacts.length > 0 || notInvitedContacts.length > 0;
				await this.#showInvalidContactsMessage(invalidContacts, existsAnotherContactsToProcess);
			}

			const usersToInvite = this.#addIdFieldToUserItems(notInvitedContacts, 'phone');
			const invitedUsers = this.#addIdFieldToUserItems(invitedContacts, 'phone');
			if (usersToInvite.length > 0)
			{
				selectorInstance.close(async () => {
					this.nameCheckerInstance = await this.#openNameCheckerForPhones(usersToInvite, invitedUsers);
				});

				return;
			}

			if (invitedUsers.length > 0)
			{
				selectorInstance.close();

				const multipleInvitation = invitedUsers.length > 1;
				const addResponse = await addEmployeeToCollab(
					this.props.collabId,
					invitedUsers.map((user) => user.userId),
				);
				await this.#handleInviteResponse(addResponse, multipleInvitation);

				return;
			}

			selectorInstance.enableSendButtonLoadingIndicator(false);
		};

		#isInviteStatusResponseValid(response, requestItemsCount)
		{
			return !(response.status !== 'success'
				|| !Array.isArray(response.data)
				|| requestItemsCount !== response.data.length);
		}

		#openNameCheckerForPhones = async (usersToInvite, alreadyInvitedUsers) => {
			return openNameChecker({
				parentLayout: this.props.layout,
				usersToInvite,
				alreadyInvitedUsers,
				inviteButtonText: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_INVITE_BUTTON_TEXT'),
				boxTitle: this.getNameCheckerTitle(usersToInvite, alreadyInvitedUsers),
				description: this.getNameCheckerDescription(usersToInvite, alreadyInvitedUsers),
				subdescription: '',
				renderAvatar: this.#renderNameCheckerAvatar,
				infoAreaGraphicsUri: makeLibraryImagePath('description-graphics.svg', 'collab/invite'),
				getItemFormattedSubDescription: this.#getPhoneItemFormattedSubDescription,
				getAlreadyInvitedUsersStringForSubtitle: this.#getAlreadyInvitedUsersFormattedPhones,
				onSendInviteButtonClick: this.#onSendInviteButtonClick,
				avatarEntityType: AvatarEntityType.COLLAB,
				dismissAlert: {
					title: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_TITLE'),
					description: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_DESCRIPTION'),
					destructiveButtonText: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_DESTRUCTIVE_BUTTON'),
					defaultButtonText: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_CONTINUE_BUTTON'),
				},
			});
		};

		getNameCheckerTitle = (usersToInvite, alreadyInvitedUsers) => {
			return this.isUserWithNameExists([...(usersToInvite ?? []), ...(alreadyInvitedUsers ?? [])])
				? Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_TITLE_PHONE')
				: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_TITLE_EMAIL');
		};

		getNameCheckerDescription = (usersToInvite, alreadyInvitedUsers) => {
			return this.isUserWithNameExists([...(usersToInvite ?? []), ...(alreadyInvitedUsers ?? [])])
				? Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_DESCRIPTION_PHONE')
				: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_DESCRIPTION_EMAIL');
		};

		isUserWithNameExists = (users) => {
			return users.some((user) => user.firstName || user.secondName);
		};

		#getContactGroupsFromResponse(response, selectedContacts)
		{
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

				if (contact.inviteStatus === EmployeeStatus.NOT_REGISTERED.getName()
					|| contact.inviteStatus === EmployeeStatus.INVITED.getName())
				{
					notInvitedContacts.push(preparedContact);

					return;
				}

				invitedContacts.push(preparedContact);
			});

			return {
				invalidContacts,
				notInvitedContacts,
				invitedContacts,
			};
		}

		#showInvalidContactsMessage(invalidContacts)
		{
			return new Promise((resolve) => {
				const phonesNumbersString = invalidContacts.map((contact) => {
					return contact.name ? `${contact.name} (${contact.phone})` : contact.phone;
				}).join(', ');

				Alert.confirm(
					Loc.getMessage('COLLAB_INVITE_INVALID_PHONE_NUMBER_ALERT_TITLE'),
					Loc.getMessage('COLLAB_INVITE_INVALID_PHONE_NUMBER_ALERT_DESCRIPTION', {
						'#phonesNumbersString#': phonesNumbersString,
					}),
					[{
						type: ButtonType.DEFAULT,
						onPress: resolve,
					}],
				);
			});
		}

		#handleInviteResponse = async (response, multipleInvitation) => {
			if (response?.status === 'success')
			{
				await this.#closeInviteBox();
				showSuccessInvitationToast({
					collabId: this.props.collabId,
					analytics: this.props.analytics,
					multipleInvitation,
					isTextForInvite: true,
				});

				return;
			}

			this.nameCheckerInstance?.close();
		};

		#inviteUsers = async (usersToInvite, alreadyInvitedUsers) => {
			const multipleInvitation = [...usersToInvite, ...alreadyInvitedUsers].length > 1;
			const inviteResponse = await this.#inviteGuests(usersToInvite);
			if (inviteResponse.status === 'error')
			{
				this.nameCheckerInstance?.enableSendButtonLoadingIndicator(false);

				return;
			}

			const inviteByPhone = !isNil(usersToInvite[0]?.phone) || !isNil(alreadyInvitedUsers[0]?.phone);
			const invitedUsersIds = inviteResponse.data;
			this.props.analytics.sendInviteEvent(inviteByPhone, invitedUsersIds);
			if (alreadyInvitedUsers.length === 0)
			{
				await this.#handleInviteResponse(inviteResponse, multipleInvitation);

				return;
			}

			if (inviteResponse.status === 'success' && alreadyInvitedUsers.length > 0)
			{
				const addResponse = await addEmployeeToCollab(
					this.props.collabId,
					alreadyInvitedUsers.map((user) => user.userId),
				);

				await this.#handleInviteResponse(addResponse, multipleInvitation);
			}
		};

		#inviteGuests = (usersToInvite) => {
			const { languageCode } = this.state;
			const preparedUsers = usersToInvite.map((user) => {
				const { phone, email, firstName = null, secondName = null } = user;

				if (phone)
				{
					return {
						phone,
						name: firstName,
						lastName: secondName,
						languageId: languageCode,
					};
				}

				return {
					email,
					name: firstName,
					lastName: secondName,
					languageId: languageCode,
				};
			});

			return inviteGuestsToCollab(this.props.collabId, preparedUsers);
		};

		#getPhoneItemFormattedSubDescription = (user) => {
			return Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_PHONE_ITEM_SUBDESCRIPTION_TEXT', {
				'#phone#': getFormattedNumber(user.formattedPhone),
			});
		};

		#getAlreadyInvitedUsersFormattedPhones = (users) => {
			return users.map((user) => `${user.name} ${getFormattedNumber(user.formattedPhone)}`).join(', ');
		};

		#getAlreadyInvitedUsersFormattedEmails = (users) => {
			return users.reduce((acc, user, index) => {
				const separator = index === 0 ? '' : ', ';

				return `${acc}${separator}${user.email}`;
			}, '');
		};

		#getInviteCasesItems = () => {
			const [
				contactListItem,
				emailItem,
				qrItem,
			] = this.#getAllInviteCasesItems();

			if (!this.isBitrix24Included)
			{
				return [emailItem, qrItem];
			}

			return [contactListItem, emailItem, qrItem];
		};

		#getAllInviteCasesItems = () => {
			return [{
				id: 'contactlist',
				testId: this.getTestId('case-menu-item-fromContactsList'),
				title: Loc.getMessage('COLLAB_INVITE_CASE_ITEM_CONTACTS_LIST'),
				iconName: Icon.CONTACT,
				onItemSelected: () => {
					this.#openSmartphoneContactsList();
				},
			},
			{
				id: 'mail',
				testId: this.getTestId('case-menu-item-mail'),
				title: Loc.getMessage('COLLAB_INVITE_CASE_ITEM_MAIL'),
				iconName: Icon.MAIL,
				onItemSelected: async () => {
					await this.#openEmailInputBox();
				},
			},
			{
				id: 'qr',
				testId: this.getTestId('case-menu-item-qr'),
				title: Loc.getMessage('COLLAB_INVITE_CASE_ITEM_QR'),
				iconName: Icon.QR_CODE,
				onItemSelected: async () => {
					await this.#openQRInviteBox();
				},
			}];
		};

		#openEmailInputBox = async () => {
			const { canInviteCollabersInPortalSettings, boxLayout } = this.props;
			if (!canInviteCollabersInPortalSettings)
			{
				await openGuestsInviteRestrictionsBox({
					parentWidget: boxLayout,
				});

				return;
			}

			this.inviteByEmailBoxRef = await openEmailInputBox({
				testId: this.getTestId(),
				parentLayout: this.props.layout,
				title: Loc.getMessage('COLLAB_INVITE_EMAIL_BOX_TITLE'),
				bottomButtonText: Loc.getMessage('COLLAB_INVITE_EMAIL_BOX_INVITE_BUTTON_TEXT'),
				inputPlaceholder: Loc.getMessage('COLLAB_INVITE_EMAIL_BOX_INPUT_PLACEHOLDER'),
				onButtonClick: this.#onInviteByEmailButtonClick,
				dismissAlert: {
					title: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_TITLE'),
					description: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_DESCRIPTION'),
					destructiveButtonText: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_DESTRUCTIVE_BUTTON'),
					defaultButtonText: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_CONTINUE_BUTTON'),
				},
			});
		};

		#getCollab = async () => {
			return BX.ajax.runAction('socialnetwork.collab.Collab.get', {
				data: {
					id: this.props.collabId,
				},
			})
				.then((response) => {
					return response.data;
				})
				.catch(ajaxPublicErrorHandler);
		};

		#getDataForQRInvite = (response) => {
			const avatarUri = response?.additionalInfo?.image?.src ?? null;
			const entityName = response?.name ?? null;

			return { avatarUri, entityName };
		};

		#openQRInviteBox = async () => {
			const collabResponse = await this.#getCollab();
			const { avatarUri, entityName } = this.#getDataForQRInvite(collabResponse);

			await QRInvite.open({
				entityType: QrEntity.COLLAB,
				parentWidget: this.props.layout,
				loadUri: async () => {
					const { collabId } = this.props;
					const { languageCode } = this.state;

					const response = await getLinkByCollabId(collabId, languageCode);
					if (!response || response?.status === 'error')
					{
						return null;
					}

					return response.data;
				},
				entityName,
				avatarUri,
			});
		};

		#onInviteByEmailButtonClick = async (emails) => {
			await this.#processEmails(emails);
		};

		#processEmails = async (emails) => {
			if (!Type.isArrayFilled(emails))
			{
				return;
			}

			const uniqueEmails = [...new Set(emails)];

			if (this.#isCollaberOrExtranet())
			{
				const usersToInvite = uniqueEmails.map((email) => ({
					email,
					isValidEmail: true,
					inviteStatus: EmployeeStatus.NOT_REGISTERED.getValue(),
					id: email,
				}));

				await this.#closeInviteBox();
				await Notify.showIndicatorLoading();
				await this.#inviteUsers(usersToInvite, []);
				Notify.hideCurrentIndicator();

				return;
			}

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
				await this.#showInvalidEmailsMessage(invalidEmails);
			}

			const usersToInvite = this.#addIdFieldToUserItems(notInvitedEmails, 'email');
			const invitedUsers = this.#addIdFieldToUserItems(invitedEmails, 'email');
			if (usersToInvite.length > 0)
			{
				this.inviteByEmailBoxRef?.close(async () => {
					this.nameCheckerInstance = await this.#openNameCheckerForEmails(usersToInvite, invitedUsers);
				});

				return;
			}

			if (invitedUsers.length > 0)
			{
				this.inviteByEmailBoxRef?.close();

				const multipleInvitation = invitedUsers.length > 1;
				const addResponse = await addEmployeeToCollab(
					this.props.collabId,
					invitedUsers.map((user) => user.userId),
				);
				await this.#handleInviteResponse(addResponse, multipleInvitation);

				return;
			}

			this.inviteByEmailBoxRef?.disableButtonLoading();
		};

		#addIdFieldToUserItems = (users, idField) => {
			return users.map((user) => ({
				...user,
				id: user[idField],
			}));
		};

		#showInvalidEmailsMessage(invalidEmails)
		{
			return new Promise((resolve) => {
				const emailsString = invalidEmails.map((email) => email.toLowerCase()).join(', ');

				Alert.confirm(
					Loc.getMessage('COLLAB_INVITE_INVALID_EMAIL_ALERT_TITLE'),
					Loc.getMessage('COLLAB_INVITE_INVALID_EMAIL_ALERT_DESCRIPTION', {
						'#emailString#': emailsString,
					}),
					[{
						type: ButtonType.DEFAULT,
						onPress: () => {
							resolve();
						},
					}],
				);
			});
		}

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
					invalidEmails.push(user.email);

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

		#openNameCheckerForEmails = async (usersToInvite, alreadyInvitedUsers) => {
			return openNameChecker({
				parentLayout: this.props.layout,
				usersToInvite,
				alreadyInvitedUsers,
				inviteButtonText: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_INVITE_BUTTON_TEXT'),
				boxTitle: this.getNameCheckerTitle(usersToInvite, alreadyInvitedUsers),
				description: this.getNameCheckerDescription(usersToInvite, alreadyInvitedUsers),
				subdescription: '',
				renderAvatar: this.#renderNameCheckerAvatar,
				infoAreaGraphicsUri: makeLibraryImagePath('description-graphics.svg', 'collab/invite'),
				onSendInviteButtonClick: this.#onSendInviteButtonClick,
				getItemFormattedSubDescription: this.#getEmailItemFormattedSubDescription,
				getAlreadyInvitedUsersStringForSubtitle: this.#getAlreadyInvitedUsersFormattedEmails,
				avatarEntityType: AvatarEntityType.COLLAB,
				dismissAlert: {
					title: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_TITLE'),
					description: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_DESCRIPTION'),
					destructiveButtonText: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_DESTRUCTIVE_BUTTON'),
					defaultButtonText: Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_CLOSE_ALERT_CONTINUE_BUTTON'),
				},
			});
		};

		#onSendInviteButtonClick = async (usersToInvite, alreadyInvitedUsers) => {
			await this.#inviteUsers(usersToInvite, alreadyInvitedUsers);
		};

		#closeInviteBox = async () => {
			const { boxLayout } = this.props;
			if (!this.onClose)
			{
				this.onClose = new Promise((resolve) => {
					boxLayout?.close?.(resolve);
				});
			}

			return this.onClose;
		};

		#renderNameCheckerAvatar()
		{
			return Avatar({
				testId: this.getTestId('empty-avatar'),
				entityType: AvatarEntityType.COLLAB,
				size: 36,
				style: {
					marginRight: Indent.L.toNumber(),
				},
			});
		}

		#getEmailItemFormattedSubDescription = (user) => {
			return Loc.getMessage('COLLAB_INVITE_NAME_CHECKER_EMAIL_ITEM_SUBDESCRIPTION_TEXT', {
				'#email#': user.email?.toLowerCase(),
			});
		};

		#isCollaberOrExtranet = () => {
			return env.isCollaber || env.extranet;
		};

		#getEmailsInviteStatus = async (emails) => {
			return BX.ajax.runAction('intranet.invite.getEmailsInviteStatus', {
				data: { emails },
			})
				.catch(ajaxPublicErrorHandler);
		};

		#getPhoneNumbersInviteStatus = async (phones) => {
			return BX.ajax.runAction('intranet.invite.getPhoneNumbersInviteStatus', {
				data: { phones },
			})
				.catch(ajaxPublicErrorHandler);
		};

		#renderHeader()
		{
			return H4({
				testId: this.getTestId('header'),
				text: this.#isCollaberOrExtranet()
					? Loc.getMessage('COLLAB_INVITE_TAB_GUESTS_TEXT_HEADER_FOR_COLLABER')
					: Loc.getMessage('COLLAB_INVITE_TAB_GUESTS_TEXT_HEADER_FOR_EMPLOYEE'),
				color: Color.base1,
				style: {
					textAlign: 'center',
					marginBottom: Indent.M.toNumber(),
				},
			});
		}

		#renderText()
		{
			return Text3({
				testId: this.getTestId('text'),
				text: this.#isCollaberOrExtranet()
					? Loc.getMessage('COLLAB_INVITE_TAB_GUESTS_TEXT_FOR_COLLABER')
					: Loc.getMessage('COLLAB_INVITE_TAB_GUESTS_TEXT_FOR_EMPLOYEE'),
				color: Color.base2,
				numberOfLines: 0,
				ellipsize: 'end',
				style: {
					textAlign: 'center',
				},
			});
		}

		#renderGraphicsWithDescription()
		{
			return Area(
				{},
				View(
					{
						style: {
							paddingHorizontal: Component.paddingLr.toNumber(),
						},
					},
					this.#renderGraphics(),
					this.#renderHeader(),
					this.#renderText(),
					this.#renderDetailsLink(),
					this.isBitrix24Included && this.#renderLanguageChooser(),
				),
			);
		}

		#renderLanguageChooser()
		{
			const { languages } = this.props;
			const { languageCode } = this.state;

			return View(
				{
					style: {
						justifyContent: 'center',
						flexDirection: 'row',
					},
				},
				IconView({
					testId: this.getTestId('language-chooser-question-icon'),
					size: 20,
					icon: Icon.QUESTION,
					color: Color.base4,
					forwardRef: this.#bindQuestionIconRef,
					onClick: this.#onQuestionIconClick,
				}),
				BBCodeText({
					testId: this.getTestId('language-chooser'),
					linksUnderline: true,
					size: 4,
					color: Color.base3,
					value: Loc.getMessage('COLLAB_GUEST_INVITE_LANGUAGE_CHOOSER_TEXT', {
						'#LANGUAGE#': languages[languageCode].NAME,
						'#COLOR#': Color.base3.toHex(),
					}),
					onLinkClick: this.#onLanguageLinkClick,
					ref: this.#bindLanguageChooserRef,
				}),
			);
		}

		#onQuestionIconClick = () => {
			this.#showLanguageAhaMoment();
		};

		#showLanguageAhaMoment = () => {
			AhaMoment.show({
				testId: this.getTestId('language-aha-moment'),
				targetRef: this.questionIconRef,
				description: Loc.getMessage('COLLAB_INVITE_LANGUAGE_CHOOSER_AHA_MOMENT_TEXT'),
				closeButton: false,
				disableHideByOutsideClick: false,
			});
		};

		#bindQuestionIconRef = (ref) => {
			this.questionIconRef = ref;
		};

		#bindLanguageChooserRef = (ref) => {
			this.languageChooserRef = ref;
		};

		#onLanguageLinkClick = () => {
			this.#openLanguageContextMenu();
		};

		#getLanguageMenuItems = () => {
			const { languages } = this.props;
			const { languageCode } = this.state;
			const defaultLanguageCode = this.#getDefaultLanguageCode();

			return Object.entries(languages).map(
				([code, data]) => ({
					id: code,
					testId: this.getTestId(`language-menu-item-choose-${code}`),
					title: data.NAME,
					subtitle: code === defaultLanguageCode
						? Loc.getMessage('COLLAB_INVITE_LANGUAGE_CHOOSER_MENU_SECTION_TITLE')
						: null,
					checked: code === languageCode,
					onItemSelected: this.#onLanguageMenuItemSelected,
				}),
			);
		};

		#onLanguageMenuItemSelected = (event, item) => {
			this.setState({
				languageCode: item.id,
			});
		};

		#openLanguageContextMenu()
		{
			this.menu = new UIMenu(this.#getLanguageMenuItems());
			this.menu.show({
				target: this.languageChooserRef,
			});
		}

		#getDefaultLanguageCode()
		{
			const { languages } = this.props;
			if (!Type.isNil(languages))
			{
				const [code] = Object.entries(languages).find(
					([languageCode, languageData]) => languageData.IS_DEFAULT_LANGUAGE === true,
				);

				return code;
			}

			return null;
		}

		#renderDetailsLink()
		{
			return View(
				{
					style: {
						paddingTop: Indent.M.toNumber(),
						paddingBottom: Indent.XL4.toNumber(),
						width: '100%',
						alignContent: 'center',
						alignItems: 'center',
					},
				},
				Link4({
					testId: this.getTestId('department-card-link'),
					text: Loc.getMessage('COLLAB_INVITE_DETAILS_LINK_TEXT'),
					ellipsize: Ellipsize.END,
					mode: LinkMode.SOLID,
					color: Color.base3,
					numberOfLines: 1,
					textDecorationLine: 'underline',
					onClick: this.#onDetailLinkClick,
				}),
			);
		}

		#onDetailLinkClick = () => {
			helpdesk.openHelpArticle(detailsButtonArticleCode, null);
		};

		#renderGraphics()
		{
			const fileName = 'invite-guests.svg';
			const uri = makeLibraryImagePath(fileName, 'collab/invite');

			return View(
				{
					style: {
						width: '100%',
						alignItems: 'center',
						paddingHorizontal: Component.paddingLr.toNumber(),
						marginBottom: Indent.XL3.toNumber(),
					},
				},
				Image({
					style: {
						width: 208,
						height: 162,
					},
					svg: {
						resizeMode: 'contain',
						uri,
					},
				}),
			);
		}
	}

	module.exports = {
		GuestsTabContent: (props) => new GuestsTabContent(props),
	};
});
