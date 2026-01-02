/**
 * @module im/messenger/controller/sidebar-v2/ui/plan-limit-banner
 */
jn.define('im/messenger/controller/sidebar-v2/ui/plan-limit-banner', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('im/messenger/loc');
	const { AnalyticsEvent } = require('analytics');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { planLimitLock } = require('im/messenger/assets/common');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Analytics } = require('im/messenger/const');
	const { openPlanLimitsWidget } = require('im/messenger/lib/plan-limit');
	const { Logger } = require('im/messenger/lib/logger');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	class SidebarPlanLimitBanner extends LayoutComponent
	{
		/**
		 * @param {{ testId: string, dialogId: string, onChangeVisibility: function }} props
		 */
		constructor(props)
		{
			super(props);

			this.dialogId = this.props.dialogId;

			const core = serviceLocator.get('core');
			this.store = core.getStore();
			this.storeManager = core.getStoreManager();

			this.analyticsAlreadySent = false;

			this.state = {
				visible: this.store.getters['sidebarModel/isHistoryLimitExceeded'](this.dialogId),
			};
		}

		componentDidMount()
		{
			this.storeManager.on('sidebarModel/setHistoryLimitExceeded', this.#onHistoryLimitExceeded);
		}

		componentWillUnmount()
		{
			this.storeManager.off('sidebarModel/setHistoryLimitExceeded', this.#onHistoryLimitExceeded);
		}

		#onHistoryLimitExceeded = (mutation) => {
			const updatedDialogId = mutation?.payload?.data?.dialogId;
			if (updatedDialogId !== this.dialogId)
			{
				return;
			}

			const visible = mutation?.payload?.data?.isHistoryLimitExceeded;

			if (visible)
			{
				this.#show();
			}
			else
			{
				this.#hide();
			}
		};

		/**
		 * @param {string|string[]} suffix
		 * @return {string}
		 */
		#getTestId(suffix)
		{
			// eslint-disable-next-line no-param-reassign
			suffix = Array.isArray(suffix) ? suffix : [suffix];

			return [this.props.testId, ...suffix]
				.filter(Boolean)
				.join('-');
		}

		#show()
		{
			if (this.state.visible === true)
			{
				return;
			}

			this.setState({ visible: true }, () => {
				this.props.onChangeVisibility?.({ visible: true });
				this.#sendAnalyticsShowSidebarPlanLimitBanner();
			});
		}

		#hide()
		{
			if (this.state.visible === false)
			{
				return;
			}

			this.setState({ visible: false }, () => {
				this.props.onChangeVisibility?.({ visible: false });
			});
		}

		#sendAnalyticsShowSidebarPlanLimitBanner()
		{
			if (this.analyticsAlreadySent)
			{
				return;
			}

			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);
			if (!dialogModel)
			{
				return;
			}

			this.analyticsAlreadySent = true;

			(new AnalyticsEvent())
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.limitBanner)
				.setEvent(Analytics.Event.view)
				.setType(Analytics.Type.limitOfficeChatingHistory)
				.setSection(Analytics.Section.sidebar)
				.setElement(Analytics.Element.main)
				.setP1(Analytics.P1[dialogModel.type])
				.send();
		}

		#onClickBannerView()
		{
			const analytics = new AnalyticsEvent()
				.setSection(Analytics.Section.sidebar)
				.setElement(Analytics.Element.main);

			openPlanLimitsWidget(analytics)
				.catch((err) => Logger.error(`SidebarPlanLimitBanner.onClickBannerView catch: ${err.message}`, err));
		}

		render()
		{
			const { visible } = this.state;

			return View(
				{
					testId: this.#getTestId(['container', visible ? 'visible' : 'hidden']),
				},
				visible && this.renderBannerContent(),
			);
		}

		renderBannerContent()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						flexDirection: 'column',
						alignSelf: 'stretch',
						height: 'auto',
						paddingHorizontal: 14,
						paddingVertical: 12,
						marginHorizontal: 18,
						borderRadius: 10,
						backgroundColor: Color.accentSoftBlue2.toHex(),
					},
					onClick: () => this.#onClickBannerView(),
				},
				this.renderTitle(),
				this.renderSeparator(),
				this.renderDescription(),
			);
		}

		renderTitle()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						alignSelf: 'stretch',
						height: 'auto',
						width: '100%',
					},
				},
				Image({
					style: {
						width: 32,
						height: 32,
						marginRight: 8,
						alignSelf: 'center',
					},
					svg: {
						content: planLimitLock,
					},
				}),
				Text({
					style: {
						color: Color.accentMainLink.toHex(),
						fontWeight: 500,
						fontSize: 15,
						flex: 1,
					},
					numberOfLines: 3,
					ellipsize: 'end',
					text: Loc.getMessage('IMMOBILE_SIDEBAR_V2_PLAN_LIMIT_BANNER_TITLE'),
				}),
				IconView({
					size: 24,
					icon: Icon.CHEVRON_TO_THE_RIGHT,
					color: Color.accentMainLink,
				}),
			);
		}

		renderSeparator()
		{
			return View(
				{
					style: {
						marginVertical: 12,
						width: '100%',
						height: 1,
						backgroundColor: Color.accentSoftBlue1.toHex(),
					},
				},
			);
		}

		renderDescription()
		{
			const defaultLimitDays = 30;
			const planLimits = MessengerParams.getPlanLimits();
			const days = planLimits?.fullChatHistory?.limitDays || defaultLimitDays;
			const part1 = Loc.getMessagePlural(
				'IMMOBILE_SIDEBAR_V2_PLAN_LIMIT_BANNER_DESCRIPTION_1',
				days,
				{ '#COUNT#': days },
			);
			const part2 = Loc.getMessage('IMMOBILE_SIDEBAR_V2_PLAN_LIMIT_BANNER_DESCRIPTION_2');
			const description = `${part1} ${part2}`;

			return View(
				{
					style: {
						flexDirection: 'row',
						justifyContent: 'space-between',
						alignItems: 'center',
						alignSelf: 'stretch',
						width: '100%',
					},
				},
				Text({
					style: {
						color: Color.chatMyBase0_2.toHex(),
						fontWeight: 400,
						fontSize: 12,
						marginRight: 4,
						flex: 1,
					},
					numberOfLines: 5,
					ellipsize: 'end',
					text: description,
				}),
			);
		}
	}

	module.exports = { SidebarPlanLimitBanner };
});
