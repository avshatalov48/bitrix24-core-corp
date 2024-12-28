/**
 * @module intranet/invite-new
 */
jn.define('intranet/invite-new', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const { Area } = require('ui-system/layout/area');
	const { Loc } = require('loc');
	const { Text2 } = require('ui-system/typography/text');
	const { Color } = require('tokens');
	const { Box } = require('ui-system/layout/box');
	const { PhoneTab } = require('intranet/invite-new/src/tab/phone');
	const { LinkTab } = require('intranet/invite-new/src/tab/link');
	const { IntranetInviteAnalytics } = require('intranet/invite-new/src/analytics');

	const Tabs = {
		PHONE: 'phone',
		LINK: 'link',
	};

	const IS_SAFE_AREA_BOTTOM_AVAILABLE = device.screen.safeArea.bottom > 0;
	const SAFE_AREA = 38;

	class Invite extends PureComponent
	{
		constructor(props) {
			super(props);
			this.department = null;
			this.selectedTabInstance = null;
			this.state = {
				selectedTabType: this.getDefaultSelectedTab(),
			};
		}

		static getAjaxErrorText(errors)
		{
			return errors.map((errorMessage) => {
				if (errorMessage.message)
				{
					return errorMessage.message.replace('<br/>:', '\n').replace('<br/>', '\n');
				}

				return errorMessage.replace('<br/>:', '\n').replace('<br/>', '\n');
			}).filter((errorMessage) => {
				return errorMessage.length > 0;
			}).join('\n');
		}

		get canInviteByPhone()
		{
			return Boolean(this.props.canInviteByPhone);
		}

		get canInviteByLink()
		{
			return Boolean(this.props.canInviteByLink);
		}

		get testId()
		{
			return 'invite';
		}

		get inviteLink()
		{
			return this.props.inviteLink ?? '';
		}

		get sharingMessage()
		{
			return this.props.sharingMessage ?? '';
		}

		get isSingleInviteTypeAvailable()
		{
			return (!this.canInviteByPhone && this.canInviteByLink)
				|| (this.canInviteByPhone && !this.canInviteByLink);
		}

		get layout()
		{
			return this.props.layout ?? null;
		}

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
			return this.props.creatorEmailConfirmed ?? {};
		}

		get adminConfirm()
		{
			return this.props.adminConfirm ?? '';
		}

		get onInviteSentHandler()
		{
			return this.props.onInviteSentHandler ?? null;
		}

		get onInviteError()
		{
			return this.props.onInviteError ?? null;
		}

		getDefaultSelectedTab()
		{
			if (this.canInviteByPhone)
			{
				return Tabs.PHONE;
			}

			if (this.canInviteByLink)
			{
				return Tabs.LINK;
			}

			return null;
		}

		updateSelectedTab(TabType)
		{
			if (!Object.values(Tabs).includes(TabType))
			{
				return;
			}

			this.setState({
				selectedTabType: TabType,
			});
		}

		#getTabsItems()
		{
			return [
				{
					id: Tabs.PHONE,
					title: Loc.getMessage('INTRANET_INVITE_BY_PHONE_TEXT_MSGVER_1'),
					isSelected: this.state.selectedTabType === Tabs.PHONE,
				},
				{
					id: Tabs.LINK,
					title: Loc.getMessage('INTRANET_INVITE_BY_LINK_TEXT_MSGVER_1'),
					isSelected: this.state.selectedTabType === Tabs.LINK,
				},
			];
		}

		render()
		{
			this.initSelectedTabInstance();

			return Box(
				{
					safeArea: { bottom: true },
					style: {
						flex: 1,
						width: '100%',
						paddingBottom: IS_SAFE_AREA_BOTTOM_AVAILABLE ? 0 : SAFE_AREA,
					},
				},
				Area(
					{
						isFirst: true,
						style: {
							flex: 1,
						},
					},
					this.isSingleInviteTypeAvailable && this.#renderSingleTab(),
					!this.isSingleInviteTypeAvailable && this.#renderTabs(),
					this.selectedTabInstance.renderTabContent(),
				),
				this.selectedTabInstance.renderButton(),
			);
		}

		initSelectedTabInstance = () => {
			switch (this.state.selectedTabType)
			{
				case Tabs.PHONE:
					this.selectedTabInstance = new PhoneTab({
						layout: this.layout,
						multipleInvite: this.multipleInvite,
						analytics: this.analytics,
						creatorEmailConfirmed: this.creatorEmailConfirmed,
						onInviteSentHandler: this.onInviteSentHandler,
						onInviteError: this.onInviteError,
					});
					break;
				case Tabs.LINK:
					this.selectedTabInstance = new LinkTab({
						layout: this.layout,
						sharingMessage: this.sharingMessage,
						inviteLink: this.inviteLink,
						analytics: this.analytics,
						adminConfirm: this.adminConfirm,
					});
					break;
				default:
			}
		};

		#renderSingleTab()
		{
			const text = this.state.selectedTabType === Tabs.PHONE
				? Loc.getMessage('INTRANET_INVITE_BY_PHONE_TEXT_MSGVER_1')
				: Loc.getMessage('INTRANET_INVITE_BY_LINK_TEXT_MSGVER_1');

			return View(
				{
					style: {
						width: '100%',
						justifyContent: 'center',
						alignItems: 'center',
						height: 36,
					},
				},
				Text2({
					testId: `${this.testId}-single-tab-text`,
					text,
					color: Color.base2,
					numberOfLines: 0,
					ellipsize: 'end',
					style: {
						textAlign: 'center',
					},
				}),
			);
		}

		#renderTabs()
		{
			return SegmentedControlView({
				style: {
					height: 36,
				},
				items: this.#getTabsItems(),
				onSegmentChanged: (item) => {
					this.updateSelectedTab(item.id);
				},
			});
		}
	}

	module.exports = { Invite, IntranetInviteAnalytics };
});
