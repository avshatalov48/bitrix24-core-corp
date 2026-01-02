/**
 * @module intranet/qualification/fields/user-invitation
 */
jn.define('intranet/qualification/fields/user-invitation', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { PropTypes } = require('utils/validation');
	const { Loc } = require('loc');
	const { Position } = require('toast');
	const { Indent } = require('tokens');
	const { createTestIdGenerator } = require('utils/test');
	const { getSmartphoneContacts } = require('layout/ui/smartphone-contact-selector');
	const { UIMenu } = require('layout/ui/menu');
	const { Button, ButtonDesign, ButtonSize } = require('ui-system/form/buttons');
	const { Icon } = require('ui-system/blocks/icon');
	const { IntranetInviteAnalytics, InviterFactory, InviteCases } = require('intranet/invite-new');
	const { getInviteSettings, setUserVisitedInvitations } = require('intranet/invite-opener-new/api');

	class UserInvitation extends PureComponent
	{
		/**
		 * @param {Object} props
		 * @param {string} props.testId
		 * @param {Function} [props.onChange=null]
		 * @param {PageManager} [props.parentWidget=PageManager]
		 */
		constructor(props)
		{
			super(props);

			this.smartphoneContacts = null;
			this.getTestId = createTestIdGenerator({ context: this });

			this.state = {
				isDataLoaded: false,
			};
		}

		async componentDidMount()
		{
			const inviteSettings = await getInviteSettings();
			if (inviteSettings)
			{
				void setUserVisitedInvitations();
				this.smartphoneContacts = await getSmartphoneContacts();
				this.#initInviters(inviteSettings);
				this.setState({ isDataLoaded: true });
			}
		}

		#initInviters(data)
		{
			const allowedInviteCases = new Set([InviteCases.LINK, InviteCases.QR, InviteCases.SMS]);
			const inviterProps = this.#getInviterProps(data);

			this.inviters = (
				Object.values(InviteCases)
					.filter((inviteCase) => allowedInviteCases.has(inviteCase))
					.map((inviteCase) => InviterFactory.createInviterByCase(inviteCase, inviterProps))
					.sort((a, b) => a.getSortOrder() - b.getSortOrder())
			);
			this.inviters.forEach((inviter) => inviter.updateDepartment(null));
		}

		#getInviterProps(data)
		{
			const inviteAnalytics = new IntranetInviteAnalytics();
			inviteAnalytics.setDepartmentParam(false);

			return {
				canInviteByLink: data.canInviteByLink,
				canInviteByEmail: data.canInviteByEmail,
				canInviteBySMS: data.canInviteBySMS,
				adminConfirm: data.adminConfirm,
				isBitrix24Included: data.isBitrix24Included,
				isInviteWithLocalEmailAppEnabled: data.isInviteWithLocalEmailAppEnabled,
				creatorEmailConfirmed: data.creatorEmailConfirmed,
				analytics: inviteAnalytics,
				layout: this.props.parentWidget,
				multipleInvite: true,
				shouldShowDepartmentChooser: false,
				onInviteSentHandler: null,
				onInviteError: null,
				initialContacts: this.smartphoneContacts,
				successInvitationToastPosition: Position.TOP,
				onUIMenuItemSelected: () => {
					this.menu?.hide();
					this.props.onChange?.(true);
				},
				getDepartment: () => null,
				onInviteLinkChanged: (newLink) => {
					this.inviters
						.filter((inviter) => inviter.shouldUpdateInviteLink())
						.forEach((inviter) => inviter.setInviteLink?.(newLink))
					;
				},
			};
		}

		render()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						paddingTop: Indent.S.toNumber(),
					},
					testId: this.getTestId('invite-button-container'),
					ref: (ref) => {
						this.containerRef = ref;
					},
				},
				Button({
					testId: this.getTestId('invite-button'),
					leftIcon: Icon.ADD_PERSON,
					text: Loc.getMessage('QUALIFICATION_FIELDS_USER_INVITATION_BUTTON'),
					disabled: !this.state.isDataLoaded,
					design: ButtonDesign.OUTLINE_ACCENT_2,
					size: ButtonSize.L,
					onClick: () => this.#showMenu(),
				}),
			);
		}

		#showMenu()
		{
			if (!this.menu)
			{
				this.menu = new UIMenu(this.#getInviteCasesItems());
			}

			this.menu.show({ target: this.containerRef });
		}

		#getInviteCasesItems()
		{
			return (
				this.inviters
					.filter((inviter) => inviter.isAvailableInviteMethod())
					.map((inviter) => inviter.getItemForUIMenu())
			);
		}
	}

	UserInvitation.defaultProps = {
		onChange: null,
		parentWidget: PageManager,
	};

	UserInvitation.propTypes = {
		testId: PropTypes.string.isRequired,
		onChange: PropTypes.func,
		parentWidget: PropTypes.object,
	};

	module.exports = { UserInvitation };
});
