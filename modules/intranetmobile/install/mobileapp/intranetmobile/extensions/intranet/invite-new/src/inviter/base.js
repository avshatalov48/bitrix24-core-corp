/**
 * @module intranet/invite-new/src/inviter/base
 */
jn.define('intranet/invite-new/src/inviter/base', (require, exports, module) => {
	const { Loc } = require('loc');
	const { showToast, Position } = require('toast');
	const { Icon } = require('assets/icons');
	const { showErrorMessage } = require('intranet/invite-new/src/error');
	const store = require('statemanager/redux/store');
	const { usersUpserted } = require('statemanager/redux/slices/users');
	const { NameCheckerDepartmentChooser } = require('intranet/invite-new/src/name-checker-department-chooser');
	const { showAdminEmailNotConfirmedBox } = require('intranet/invite-new/src/admin-email-not-confirmed-box');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons/button');
	const { Indent } = require('tokens');
	const { createTestIdGenerator } = require('utils/test');
	const { makeLibraryImagePath } = require('asset-manager');
	/**
	 * @abstract
	 * @class BaseInviter
	 */
	class BaseInviter
	{
		/**
		 * @param {Object} props
		 * @param {Function} props.onInviteSentHandler
		 * @param {Function} props.onInviteError
		 * @param {Object} props.analytics
		 * @param {boolean} props.isBitrix24Included
		 * @param {boolean} props.canInviteByLink
		 * @param {boolean} props.canInviteByEmail
		 * @param {boolean} props.canInviteBySMS
		 * @param {boolean} props.creatorEmailConfirmed
		 * @param {boolean} props.adminConfirm
		 * @param {Object} props.inviteCase
		 * @param {Object} props.layout
		 * @param {Function} props.onUIMenuItemSelected
		 * @param {Function} props.getDepartment
		 * @param {boolean} [props.shouldShowDepartmentChooser=true]
		 * @param {Position} [props.successInvitationToastPosition=Position.BOTTOM]
		 */
		constructor(props)
		{
			this.props = props;
			this.nameChecker = null;
			this.getTestId = createTestIdGenerator({
				prefix: 'invite',
			});
		}

		get onInviteSentHandler()
		{
			return this.props.onInviteSentHandler ?? null;
		}

		get onInviteError()
		{
			return this.props.onInviteError ?? null;
		}

		getInviteCase()
		{
			return this.props.inviteCase;
		}

		getAnalytics()
		{
			return this.props.analytics ?? {};
		}

		getLayout()
		{
			return this.props.layout;
		}

		canInviteBySMS()
		{
			return Boolean(this.props.canInviteBySMS) && this.isCreatorEmailConfirmed();
		}

		canInviteByLink()
		{
			return Boolean(this.props.canInviteByLink);
		}

		canInviteByEmail()
		{
			return Boolean(this.props.canInviteByEmail) && this.isCreatorEmailConfirmed();
		}

		isCreatorEmailConfirmed()
		{
			return Boolean(this.props.creatorEmailConfirmed);
		}

		shouldUpdateInviteLink()
		{
			return false;
		}

		isAdminConfirm()
		{
			return Boolean(this.props.adminConfirm);
		}

		isAvailableInviteMethod()
		{
			return this.props.isBitrix24Included;
		}

		isDisabledInviteMethod()
		{
			return false;
		}

		getGraphicsUri()
		{
			const fileName = this.getImageFileName();

			return makeLibraryImagePath(fileName, 'invite', 'intranet');
		}

		getImageFileName()
		{
			return null;
		}

		getAlreadyInvitedUsersLabelTextForSubtitle = (usersString) => {
			return Loc.getMessage('INTRANET_INVITE_NAME_CHECKER_INVITED_USERS_LABEL_TEXT', {
				'#users#': usersString,
			});
		};

		getDepartmentIds = () => {
			const selectedDepartment = this.props.getDepartment();

			return selectedDepartment ? [selectedDepartment.id] : null;
		};

		closeInviteBox = () => this.getLayout()?.close?.();

		closeNameChecker = () => this.nameChecker?.close();

		getSuccessInvitationToastPosition()
		{
			return this.props.successInvitationToastPosition ?? Position.BOTTOM;
		}

		showSuccessInvitationToast = (multipleInvitation) => {
			showToast(
				{
					message: (
						multipleInvitation
							? Loc.getMessage('INTRANET_INVITE_MULTIPLE_SEND_SUCCESS_TOAST_TEXT')
							: Loc.getMessage('INTRANET_INVITE_SINGLE_SEND_SUCCESS_TOAST_TEXT')
					),
					icon: Icon.CHECK,
					position: this.getSuccessInvitationToastPosition(),
				},
			);
		};

		getUsersFromResponse = (response) => {
			if (response.data?.length > 0)
			{
				return response.data.map((user) => {
					const newUser = {
						id: user.id,
						name: user.name,
						lastName: user.lastName,
					};

					newUser.fullName = user.fullName ?? `${user.name} ${user.lastName}`;

					if (user.authPhoneNumber)
					{
						newUser.personalMobile = user.authPhoneNumber;
					}

					if (user.email)
					{
						newUser.email = user.email;
					}

					return newUser;
				});
			}

			return [];
		};

		handleInviteResponse = async (response, multipleInvitation, inviteCase) => {
			const preparedUsers = this.getUsersFromResponse(response);

			if (
				response.status === 'success'
				&& preparedUsers.length > 0
			)
			{
				this.getAnalytics().sendInvitationSuccessEvent(
					multipleInvitation,
					inviteCase,
					preparedUsers.map((user) => user.id),
				);
				this.showSuccessInvitationToast(multipleInvitation);
				store.dispatch(usersUpserted(preparedUsers));
				this.onInviteSentHandler?.(preparedUsers);
				this.closeNameChecker();
				this.closeInviteBox();

				return;
			}

			this.getAnalytics().sendInvitationFailedEvent(
				multipleInvitation,
				inviteCase,
				preparedUsers.map((user) => user.id),
			);
			if (response && response.errors && response.errors.length > 0)
			{
				await showErrorMessage(response.errors[0]);
				this.onInviteError?.(response.errors);

				this.nameChecker?.enableSendButtonLoadingIndicator(false);
			}
		};

		renderNameCheckerDepartmentChooser = (layout) => {
			if (!this.shouldShowDepartmentChooser())
			{
				return null;
			}

			return NameCheckerDepartmentChooser({
				layout,
				department: this.props.getDepartment(),
				onChange: this.selectedDepartmentChanged,
			});
		};

		shouldShowDepartmentChooser()
		{
			return Boolean(this.props.shouldShowDepartmentChooser ?? true);
		}

		selectedDepartmentChanged = (department) => {
			this.props.selectedDepartmentChanged?.(department);
		};

		showAdminEmailNotConfirmedBoxByInviteCase(inviteCase)
		{
			showAdminEmailNotConfirmedBox({
				layout: this.getLayout(),
				analytics: this.getAnalytics(),
				inviteCase,
			});
		}

		isMultipleInviteAllowed()
		{
			return this.props.multipleInvite ?? true;
		}

		renderMainButton = () => {
			const inviteCase = this.getInviteCase();

			return Button(
				{
					testId: this.getTestId(`by-${inviteCase}-button`),
					text: Loc.getMessage(`INTRANET_INVITE_BY_${inviteCase.toUpperCase()}_BUTTON_TEXT`),
					size: ButtonSize.L,
					design: ButtonDesign.PRIMARY,
					leftIcon: Icon.LINK,
					stretched: true,
					style: {
						marginBottom: Indent.L.toNumber(),
					},
					onClick: () => {
						this.openInviter();
					},
				},
			);
		};

		renderSecondaryButton = () => {
			const inviteCase = this.getInviteCase();

			return Button(
				{
					testId: this.getTestId(`by-${inviteCase}-button`),
					text: Loc.getMessage(`INTRANET_INVITE_BY_${inviteCase.toUpperCase()}_BUTTON_TEXT`),
					size: ButtonSize.M,
					design: ButtonDesign.PLAN_ACCENT,
					stretched: true,
					onClick: () => {
						this.openInviter();
					},
				},
			);
		};

		getItemForUIMenu = () => {
			const inviteCase = this.getInviteCase();

			return {
				id: inviteCase,
				testId: this.getTestId(`case-menu-item-${inviteCase.toLowerCase()}`),
				title: Loc.getMessage(`INTRANET_INVITE_BY_${inviteCase.toUpperCase()}_MENU_ITEM_TEXT`),
				iconName: this.getIconNameForUIMenu(),
				disable: this.isDisabledInviteMethod(),
				onItemSelected: this.onUIMenuItemSelected,
			};
		};

		openInviter = () => {
			if (!this.isAvailableInviteMethod() || this.isDisabledInviteMethod())
			{
				this.showAdminEmailNotConfirmedBoxByInviteCase(this.getInviteCase());

				return;
			}

			this.open({
				department: this.props.getDepartment(),
			});
		};

		onUIMenuItemSelected = () => {
			this.props.onUIMenuItemSelected?.();
			this.openInviter();
		};

		getIconNameForUIMenu()
		{}

		/**
		 * @param department
		 */
		updateDepartment(department)
		{}

		getSortOrder()
		{
			return 100;
		}

		/**
		 * @abstract
		 * @param props
		 */
		open(props)
		{
			throw new Error('Method open should be implemented');
		}
	}

	module.exports = { BaseInviter };
});
