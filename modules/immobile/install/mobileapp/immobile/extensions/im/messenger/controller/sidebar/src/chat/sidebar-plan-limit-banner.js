/**
 * @module im/messenger/controller/sidebar/chat/sidebar-plan-limit-banner
 */
jn.define('im/messenger/controller/sidebar/chat/sidebar-plan-limit-banner', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { Loc } = require('loc');
	const { planLimitLock } = require('im/messenger/assets/common');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { AnalyticsEvent } = require('analytics');
	const { openPlanLimitsWidget } = require('im/messenger/lib/plan-limit');
	const { Analytics } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');

	/**
	 * @class SidebarPlanLimitBanner
	 */
	class SidebarPlanLimitBanner extends LayoutComponent
	{
		render()
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
						backgroundColor: Theme.colors.accentSoftBlue2,
					},
					onClick: () => this.onClickBannerView(),
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
						color: Theme.colors.accentMainLink,
						fontWeight: 500,
						fontSize: 15,
						flex: 1,
					},
					numberOfLines: 3,
					ellipsize: 'end',
					text: Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PLAN_LIMIT_BANNER_TITLE'),
				}),
				IconView({
					size: 24,
					icon: Icon.CHEVRON_TO_THE_RIGHT,
					color: Theme.color.accentMainLink,
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
						backgroundColor: Theme.colors.accentSoftBlue1,
					},
				},
			);
		}

		renderDescription()
		{
			const defaultLimitDays = 30;
			const planLimits = MessengerParams.getPlanLimits();
			const days = planLimits?.fullChatHistory?.limitDays || defaultLimitDays;
			const descPart1 = Loc.getMessagePlural('IMMOBILE_DIALOG_SIDEBAR_PLAN_LIMIT_BANNER_DESCRIPTION_1', days, { '#COUNT#': days });
			const descPart2 = Loc.getMessage('IMMOBILE_DIALOG_SIDEBAR_PLAN_LIMIT_BANNER_DESCRIPTION_2');
			const description = `${descPart1} ${descPart2}`;

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
						color: Theme.colors.chatMyBase0_2,
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

		onClickBannerView()
		{
			const analytics = new AnalyticsEvent()
				.setSection(Analytics.Section.sidebar)
				.setElement(Analytics.Element.main);

			openPlanLimitsWidget(analytics)
				.catch((err) => Logger.error(`${this.constructor.name}.onClickBannerView catch: ${err.message}`, err));
		}
	}

	module.exports = { SidebarPlanLimitBanner };
});
