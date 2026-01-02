/**
 * @module more-menu/block/company
 */
jn.define('more-menu/block/company', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Indent, Color } = require('tokens');
	const { isModuleInstalled } = require('module');
	const { inAppUrl } = require('in-app-url');

	const { Text2, Text6 } = require('ui-system/typography/text');
	const { Icon } = require('ui-system/blocks/icon');
	const { ChipButton, ChipButtonDesign, ChipButtonMode } = require('ui-system/blocks/chips/chip-button');

	const { ProfileButton } = require('more-menu/ui/profile-button');
	const { DemoBanner } = require('more-menu/block/company/demo-banner');
	const { WhatsNewButton } = require('more-menu/block/company/whats-new');
	const { MoreMenuUsers } = require('more-menu/block/company/users');

	const { PropTypes } = require('utils/validation');
	const { createTestIdGenerator } = require('utils/test');
	const { hexToArgbNumber } = require('utils/color');
	const { Moment } = require('utils/date');
	const { date } = require('utils/date/formats');

	const PAGINATION_SIZE = 5;

	/**
	 * @typedef {Object} CompanyInfo
	 * @property {array} users
	 * @property {number} totalUsersCount
	 * @property {boolean} canInvite
	 */

	/**
	 * @typedef {Object} LicenseInfo
	 * @property {string} licenseName
	 * @property {?number} tillDate Unix timestamp or null
	 * @property {boolean} isDemo
	 * @property {boolean} isLicenseExpired
	 * @property {boolean} isLicenseAlmostExpired
	 * @property {string} type
	 * @property {boolean} isFreeLicense
	 * @property {boolean} isEnterprise
	 * @property {boolean} isDemoTrialAvailable
	 */

	/**
	 * @class MoreMenuCompany
	 */
	class MoreMenuCompany extends LayoutComponent
	{
		/**
		 *
		 * @param props
		 * @param {CompanyInfo} props.company
		 * @param {LicenseInfo} props.license
		 * @param {number} props.supportBotId
		 * @param {object} props.layout
		 * @param {boolean} props.canUseSupport
		 * @param {boolean} props.canInvite
		 * @param {boolean} props.canUseTelephony
		 * @param {boolean} props.shouldShowWhatsNew
		 * @param {string} props.testId
		 * @param {string} props.helpdeskUrl
		 */
		constructor(props)
		{
			super(props);

			this.getTestId = createTestIdGenerator({
				prefix: props.testId,
			});

			// eslint-disable-next-line no-undef
			this.scrollOffset = Animated.newCalculatedValue2D(0, 0);
			this.scrollOffsetX = this.scrollOffset.getValue1();
		}

		render()
		{
			const { license } = this.props;

			if (!license?.isDemoTrialAvailable)
			{
				return View(
					{
						style: {
							marginHorizontal: Indent.XL2.toNumber(),
						},
					},
					this.renderTitle(),
					this.renderContent(),
				);
			}

			return View(
				{},
				this.renderTitle(),
				ScrollView(
					{
						horizontal: true,
						showsHorizontalScrollIndicator: false,
						style: {
							height: 126,
						},
						onScrollCalculated: {
							contentOffset: this.scrollOffset,
						},
					},
					View(
						{
							style: {
								alignItems: 'center',
								flexDirection: 'row',
								paddingHorizontal: Indent.XL2.toNumber(),
							},
						},
						this.renderContent(),
						new DemoBanner({
							testId: this.getTestId('demo-banner'),
							license,
							layout,
						}),
					),
				),
				this.renderPagination(),
			);
		}

		renderTitle()
		{
			const { license } = this.props;

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'space-between',
						marginBottom: Indent.XL2.toNumber(),
						marginHorizontal: license?.isDemoTrialAvailable && Indent.XL2.toNumber(),
					},
				},
				Text2({
					text: Loc.getMessage('MORE_MENU_COMPANY_TITLE'),
					color: Color.base1,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						marginRight: Indent.XL2.toNumber(),
					},
				}),
				this.renderLicenseButton(),
			);
		}

		renderLicenseButton()
		{
			const { license } = this.props;

			if (!license || !(license?.isDemo || license.isFreeLicense))
			{
				return null;
			}

			const name = license?.licenseName;
			const tillDate = license?.tillDate;

			let backgroundColor = Color.accentSoftBlue1;
			let tillColor = Color.base3;

			if (license?.isLicenseExpired)
			{
				backgroundColor = Color.accentSoftRed1;
			}
			else if (license?.isLicenseAlmostExpired)
			{
				backgroundColor = Color.accentSoftOrange2;
				tillColor = Color.accentExtraOrange;
			}
			else if (license?.isFreeLicense)
			{
				backgroundColor = Color.accentSoftGreen2;
			}

			return ChipButton({
				testId: this.getTestId('license-button'),
				compact: true,
				icon: this.getLicenceIcon(),
				rounded: false,
				design: ChipButtonDesign.SUCCESS,
				mode: ChipButtonMode.SOLID,
				backgroundColor,
				iconColor: Color.base1,
				content: View(
					{
						style: {
							flexDirection: 'row',
							alignItems: 'center',
							flexShrink: 2,
						},
					},
					Text6({
						text: name,
						color: Color.base1,
						style: {
							marginRight: Indent.XS2.toNumber(),
							flexShrink: 2,
						},
						numberOfLines: 1,
						ellipsize: 'middle',
					}),
					!license.isFreeLicense && this.renderTillDate(tillDate, tillColor),
				),
				onClick: this.onLicenceButtonClick,
			});
		}

		onLicenceButtonClick = async () => {
			const { layout, license } = this.props;

			if (license?.isDemo && license?.isLicenseExpired)
			{
				const { PlanRestriction } = await requireLazy('layout/ui/plan-restriction');

				PlanRestriction.open(
					{},
					layout,
				);
			}
		};

		/**
		 * @param {number} tillDate
		 * @param {Color} color
		 * @return {*|null}
		 */
		renderTillDate(tillDate, color)
		{
			if (!tillDate || (tillDate * 1000 < Date.now()))
			{
				return null;
			}

			return Text6({
				testId: this.getTestId('license-till-date'),
				text: Loc.getMessage('MORE_MENU_COMPANY_DEMO_TILL_DATE', {
					'#TIME#': Moment.createFromTimestamp(tillDate).format(date()),
				}),
				color,
				numberOfLines: 1,
				ellipsize: 'end',
			});
		}

		getLicenceIcon()
		{
			const {
				isLicenseAlmostExpired,
				isLicenseExpired,
				isDemo,
				isFreeLicense,
				type,
				isEnterprise,
			} = this.props.license || {};

			if (isLicenseAlmostExpired)
			{
				return Icon.BATTERY_1_STICK;
			}

			if (isLicenseExpired)
			{
				return Icon.BATTERY_NO_CHARGE;
			}

			if (isDemo)
			{
				return Icon.DEMONSTRATION_ON;
			}

			if (isFreeLicense)
			{
				return Icon.ROCKET;
			}

			if (type === 'basic')
			{
				return Icon.HOME_STAR;
			}

			if (type === 'std')
			{
				return Icon.THREE_PERSONS;
			}

			if (type === 'pro100')
			{
				return Icon.SUITCASE;
			}

			if (type === 'nfr')
			{
				return Icon.PARTNER_NFC;
			}

			if (isEnterprise)
			{
				return Icon.ENTERPRISE;
			}

			return null;
		}

		renderContent()
		{
			const {
				layout,
				company,
				canInvite,
				canUseTelephony,
				shouldShowWhatsNew,
				canUseSupport,
				helpdeskUrl,
			} = this.props;

			return View(
				{},
				isModuleInstalled('intranetmobile') && new MoreMenuUsers({
					company,
					layout,
					styles: {
						marginBottom: Indent.M.toNumber(),
					},
					canInvite,
					canUseTelephony,
					testId: this.getTestId('users'),
				}),
				View(
					{
						style: {
							flexDirection: 'row',
							justifyContent: 'center',
							alignItems: 'center',
						},
					},
					shouldShowWhatsNew && WhatsNewButton({
						testId: this.getTestId('whats-new-button'),
					}),
					(canUseSupport || helpdeskUrl) && ProfileButton({
						testId: this.getTestId('support-button'),
						text: Loc.getMessage('MORE_MENU_COMPANY_SUPPORT'),
						onClick: this.openSupport,
						badge: 0,
						leftIcon: !shouldShowWhatsNew && Icon.MESSAGES,
						shouldShowChevron: !shouldShowWhatsNew,
					}),
				),
			);
		}

		openSupport = () => {
			const { canUseSupport, helpdeskUrl, supportBotId } = this.props;

			if (canUseSupport)
			{
				inAppUrl.open('/support/', {
					title: Loc.getMessage('MORE_MENU_COMPANY_SUPPORT'),
					botId: supportBotId,
				});
			}
			else
			{
				helpdesk.openHelp(helpdeskUrl);
			}
		};

		renderPagination()
		{
			const activeColor = hexToArgbNumber(Color.accentSoftBlue1.toHex());
			const defaultColor = hexToArgbNumber(Color.bgSeparatorPrimary.toHex());

			return View(
				{
					testId: this.getTestId('pagination'),
					style: {
						flexDirection: 'row',
						alignSelf: 'center',
						paddingTop: Indent.XL2.toNumber(),
						paddingBottom: Indent.XS2.toNumber(),
					},
				},
				View(
					{
						testId: this.getTestId('pagination-dot-1'),
						style: {
							width: PAGINATION_SIZE,
							height: PAGINATION_SIZE,
							borderRadius: PAGINATION_SIZE / 2,
							backgroundColor: this.scrollOffsetX.interpolate({
								inputRange: [-1, 300],
								outputRange: [activeColor, defaultColor],
								outputType: 'color',
							}),
						},
					},
				),
				View(
					{
						testId: this.getTestId('pagination-dot-2'),
						style: {
							width: PAGINATION_SIZE,
							height: PAGINATION_SIZE,
							borderRadius: PAGINATION_SIZE / 2,
							backgroundColor: this.scrollOffsetX.interpolate({
								inputRange: [-1, 300],
								outputRange: [defaultColor, activeColor],
								outputType: 'color',
							}),
							marginLeft: Indent.S.toNumber(),
						},
					},
				),
			);
		}
	}

	MoreMenuCompany.propTypes = {
		company: PropTypes.object,
		license: PropTypes.object,
		supportBotId: PropTypes.number,
		layout: PropTypes.object.isRequired,
		canUseSupport: PropTypes.bool,
		canInvite: PropTypes.bool,
		canUseTelephony: PropTypes.bool,
		shouldShowWhatsNew: PropTypes.bool,
		testId: PropTypes.string,
		helpdeskUrl: PropTypes.string,
	};

	module.exports = {
		MoreMenuCompany,
	};
});
