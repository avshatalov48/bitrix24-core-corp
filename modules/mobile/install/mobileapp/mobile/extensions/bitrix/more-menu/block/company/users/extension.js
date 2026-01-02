/**
 * @module more-menu/block/company/users
 */
jn.define('more-menu/block/company/users', (require, exports, module) => {
	const { AnalyticsEvent } = require('analytics');
	const { Loc } = require('loc');
	const { Indent, Color } = require('tokens');
	const { Tourist } = require('tourist');
	const { inAppUrl } = require('in-app-url');

	const { Text3, Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { Card, CardDesign } = require('ui-system/layout/card');
	const { AvatarStack } = require('ui-system/blocks/avatar-stack');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');

	const { PropTypes } = require('utils/validation');
	const { createTestIdGenerator } = require('utils/test');

	/**
	 * @typedef {Object} CompanyInfo
	 * @property {array} users
	 * @property {number} totalUsersCount
	 * @property {boolean} canInvite
	 */
	/**
	 * @class MoreMenuUsers
	 */
	class MoreMenuUsers extends LayoutComponent
	{
		/**
		 * @param props
		 * @param {CompanyInfo} props.company
		 * @param {object} props.layout
		 * @param {object} props.styles
		 * @param {boolean} props.canInvite
		 * @param {boolean} props.canUseTelephony
		 * @param {string} props.testId
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: props.testId,
			});

			this.state = {
				totalInvitation: 0,
				inviteButtonCounter: Tourist.firstTime('visit_invitations') ? 1 : 0,
			};

			this.openUsers = this.openUsers.bind(this);
			this.openInvite = this.openInvite.bind(this);

			this.subscribeToSetUserCounters = this.subscribeToSetUserCounters.bind(this);
			this.subscribeToPullEvent = this.subscribeToPullEvent.bind(this);
		}

		getTotalInvitation()
		{
			const cachedCounters = Application.sharedStorage().get('userCounters');
			try
			{
				const counters = cachedCounters ? JSON.parse(cachedCounters) : {};
				const totalInvitation = counters[env.siteId]?.total_invitation;
				if (totalInvitation > 0)
				{
					return totalInvitation;
				}

				return 0;
			}
			catch (error)
			{
				console.error(error);

				return 0;
			}
		}

		componentDidMount()
		{
			BX.addCustomEvent('onSetUserCounters', this.subscribeToSetUserCounters);
			BX.addCustomEvent('onUpdateUserCounters', this.subscribeToPullEvent);

			const totalInvitation = this.getTotalInvitation();
			if (totalInvitation !== 0)
			{
				this.setState({
					totalInvitation,
				});
			}
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('onSetUserCounters', this.subscribeToSetUserCounters);
			BX.removeCustomEvent('onUpdateUserCounters', this.subscribeToPullEvent);
		}

		subscribeToSetUserCounters(counters)
		{
			if (
				counters[env.siteId]
				&& Number.isInteger(counters[env.siteId].menu_invite)
				&& this.state.inviteButtonCounter !== counters[env.siteId].menu_invite
			)
			{
				this.setState({
					inviteButtonCounter: counters[env.siteId].menu_invite,
				});
			}
		}

		subscribeToPullEvent(counters)
		{
			const currentCounters = counters[env.siteId];
			if (
				currentCounters
				&& this.state.totalInvitation !== currentCounters?.total_invitation
			)
			{
				this.setState({
					totalInvitation: currentCounters?.total_invitation,
				});
			}
		}

		render()
		{
			const { totalInvitation } = this.state;
			const { company, canInvite, styles = {} } = this.props;
			if (!company)
			{
				return View();
			}

			const users = company?.users || [];
			const totalUsersCount = company?.totalUsersCount || 0;

			const usersIds = users.map((user) => user.id);

			if (usersIds.length === 4 && totalUsersCount > 4)
			{
				const restCount = totalUsersCount - users.length;
				usersIds.push(...Array.from({ length: restCount }).fill(0)); // add fake items to show rest count
			}

			return Card(
				{
					testId: this.getTestId('card'),
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
						borderColor: this.shouldHighlight() ? Color.bgSeparatorSecondary.toHex() : Color.accentSoftBlue1.toHex(),
						...styles,
					},
					design: CardDesign.PRIMARY,
					border: true,
				},
				View(
					{
						testId: this.getTestId('card-content'),
						onClick: this.openUsers,
					},
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
							},
						},
						Text3({
							testId: this.getTestId('title'),
							text: Loc.getMessage('MORE_MENU_COMPANY_USERS_TITLE'),
							color: Color.base1,
							numberOfLines: 1,
							ellipsize: 'end',
						}),
						totalInvitation && BadgeCounter({
							testId: this.getTestId('total-invitation-counter'),
							value: totalInvitation,
							design: BadgeCounterDesign.ALERT,
							style: {
								marginLeft: Indent.XS.toNumber(),
							},
						}),
						IconView({
							testId: this.getTestId('title-chevron'),
							size: 20,
							icon: Icon.CHEVRON_TO_THE_RIGHT,
							color: Color.base1,
							resizeMode: 'cover',
							style: {
								marginTop: 2,
								width: 10,
								height: 16,
								marginLeft: Indent.XS.toNumber(),
							},
						}),
					),
					View(
						{
							style: {
								flexDirection: 'row',
								alignItems: 'center',
								marginTop: Indent.S.toNumber(),
							},
						},
						AvatarStack({
							testId: this.getTestId('avatar-stack'),
							entities: usersIds,
							size: 18,
							withRedux: true,
							visibleEntityCount: 4,
						}),
						usersIds.length === 1 && Text4({
							testId: this.getTestId('single-user-title'),
							text: Loc.getMessage('MORE_MENU_COMPANY_USERS_DESCRIPTION'),
							color: Color.base3,
							numberOfLines: 1,
							ellipsize: 'end',
							style: {
								marginLeft: Indent.S.toNumber(),
							},
						}),
					),
				),
				canInvite && Button({
					testId: this.getTestId('invite-button'),
					size: ButtonSize.M,
					design: this.shouldHighlight() ? ButtonDesign.OUTLINE : ButtonDesign.FILLED,
					style: {
						marginLeft: Indent.XL2.toNumber(),
					},
					onClick: this.openInvite,
					text: Loc.getMessage('MORE_MENU_COMPANY_USERS_INVITE'),
					backgroundColor: this.shouldHighlight() ? Color.bgContentPrimary : Color.accentMainPrimary,
					badge: this.getInviteButtonBadge(),
				}),
				!canInvite && Button({
					testId: this.getTestId('open-button'),
					size: ButtonSize.M,
					design: ButtonDesign.OUTLINE,
					style: {
						marginLeft: Indent.XL2.toNumber(),
					},
					onClick: this.openUsers,
					text: Loc.getMessage('MORE_MENU_COMPANY_USERS_OPEN'),
					backgroundColor: Color.bgContentPrimary,
				}),
			);
		}

		shouldHighlight()
		{
			const { company } = this.props;
			const users = company?.users || [];

			return Array.isArray(users) && users.length >= 2;
		}

		getInviteButtonBadge()
		{
			const { inviteButtonCounter, totalInvitation } = this.state;

			if (!inviteButtonCounter || totalInvitation > 0)
			{
				return null;
			}

			return BadgeCounter({
				testId: this.getTestId('invite-button-counter'),
				value: inviteButtonCounter,
				design: BadgeCounterDesign.ALERT,
			});
		}

		openUsers()
		{
			const {
				canInvite,
				canUseTelephony,
			} = this.props;

			inAppUrl.open('/intranetmobile/users', {
				title: Loc.getMessage('MORE_MENU_COMPANY_USERS_TITLE'),
				canInvite,
				canUseTelephony,
			});
		}

		async openInvite()
		{
			const { layout } = this.props;
			const { openIntranetInviteWidget } = await requireLazy('intranet:invite-opener-new') || {};
			if (openIntranetInviteWidget)
			{
				openIntranetInviteWidget({
					analytics: new AnalyticsEvent().setSection('ava_menu'),
					parentLayout: layout,
				});
			}
			else
			{
				console.error('Invite opener not found');
			}
		}
	}

	MoreMenuUsers.propTypes = {
		company: PropTypes.object,
		layout: PropTypes.object.isRequired,
		styles: PropTypes.object,
		canInvite: PropTypes.bool,
		canUseTelephony: PropTypes.bool,
		testId: PropTypes.string.isRequired,
	};

	module.exports = {
		MoreMenuUsers,
	};
});
