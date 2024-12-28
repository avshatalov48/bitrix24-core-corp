/**
 * @module im/messenger/controller/sidebar/chat/sidebar-view
 */
jn.define('im/messenger/controller/sidebar/chat/sidebar-view', (require, exports, module) => {
	const { SidebarProfileBtn } = require('im/messenger/controller/sidebar/chat/sidebar-profile-btn');
	const { SidebarTabView } = require('im/messenger/controller/sidebar/chat/tabs/tab-view');
	const { SidebarProfileInfo } = require('im/messenger/controller/sidebar/chat/sidebar-profile-info');
	const { SidebarPlanLimitBanner } = require('im/messenger/controller/sidebar/chat/sidebar-plan-limit-banner');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { Type } = require('type');
	const { Theme } = require('im/lib/theme');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--sidebar-view');
	const { Analytics } = require('im/messenger/const');
	const { AnalyticsEvent } = require('analytics');

	/**
	 * @class SidebarView
	 * @typedef {LayoutComponent<SidebarViewProps, SidebarViewState>} SidebarView
	 */
	class SidebarView extends LayoutComponent
	{
		/**
		 * @constructor
		 * @param {SidebarViewProps} props
		 */
		constructor(props)
		{
			super(props);
			this.store = serviceLocator.get('core').getStore();
			const isHistoryLimitExceeded = MessengerParams.isFullChatHistoryAvailable() ? false : this.store.getters['sidebarModel/isHistoryLimitExceeded'](this.props.dialogId);
			this.dialog = this.store.getters['dialoguesModel/getById'](this.props.dialogId);
			this.state = {
				userData: props.userData,
				isHistoryLimitExceeded,
			};
		}

		componentDidMount()
		{
			logger.log(`${this.constructor.name}.componentDidMount`);
			this.bindMethods();
			this.subscribeStoreEvents();
		}

		componentWillUnmount()
		{
			logger.log(`${this.constructor.name}.componentWillUnmount`);
			this.unsubscribeStoreEvents();
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: Theme.colors.bgContentPrimary,
						justifyContent: 'flex-start',
						alignItems: 'center',
						flexDirection: 'column',
					},
				},
				this.renderProfile(),
				this.renderPlanLimitBanner(),
				this.renderTabs(),
			);
		}

		renderProfile()
		{
			return View(
				{
					style: {
						alignItems: 'center',
						flexDirection: 'column',
						alignSelf: 'stretch',
					},
				},
				this.renderInfoBlock(),
				this.renderButtonsBlock(),
			);
		}

		renderPlanLimitBanner()
		{
			if (!this.state.isHistoryLimitExceeded)
			{
				return null;
			}

			this.sendAnalyticsShowSidebarPlanLimitBanner();

			return new SidebarPlanLimitBanner();
		}

		sendAnalyticsShowSidebarPlanLimitBanner()
		{
			const dialogType = this.dialog.type;
			const analytics = new AnalyticsEvent()
				.setTool(Analytics.Tool.im)
				.setCategory(Analytics.Category.limitBanner)
				.setEvent(Analytics.Event.view)
				.setType(Analytics.Type.limitOfficeChatingHistory)
				.setSection(Analytics.Section.sidebar)
				.setElement(Analytics.Element.main)
				.setP1(Analytics.P1[dialogType]);

			analytics.send();
		}

		renderInfoBlock()
		{
			return View(
				{
					style: {
						justifyContent: 'center',
						alignItems: 'center',
						flexDirection: 'column',
						width: '100%',
						paddingHorizontal: 18,
						marginTop: 12,
					},
				},
				new SidebarProfileInfo(this.props),
			);
		}

		renderButtonsBlock()
		{
			return new SidebarProfileBtn({ buttonElements: this.props.buttonElements });
		}

		renderTabs()
		{
			return new SidebarTabView({
				dialogId: this.props.dialogId,
				hideParticipants: this.props.isNotes,
				isCopilot: this.props.isCopilot,
			});
		}

		/**
		 * @param {object} mutation
		 * @param {MutationPayload<SidebarSetHistoryLimitExceededData, SidebarSetHistoryLimitExceededActions>} mutation.payload
		 */
		onSetPlanLimitsStore(mutation)
		{
			logger.info(`${this.constructor.name}.onSetPlanLimitsStore---------->`, mutation);

			const { isHistoryLimitExceeded, dialogId } = mutation.payload.data;
			const isCurrentDialog = this.props.dialogId === dialogId;
			const needToUpdateBanner = this.state.isHistoryLimitExceeded !== isHistoryLimitExceeded;

			if (isCurrentDialog && needToUpdateBanner)
			{
				this.setState({ isHistoryLimitExceeded });
			}
		}

		/**
		 * @desc Method binding this for use in handlers
		 * @void
		 */
		bindMethods()
		{
			this.onSetPlanLimitsStore = this.onSetPlanLimitsStore.bind(this);
		}

		subscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.subscribeStoreEvents`);

			serviceLocator.get('core').getStoreManager().on('sidebarModel/setHistoryLimitExceeded', this.onSetPlanLimitsStore);
		}

		unsubscribeStoreEvents()
		{
			logger.log(`${this.constructor.name}.unsubscribeStoreEvents`);

			serviceLocator.get('core').getStoreManager().off('sidebarModel/setHistoryLimitExceeded', this.onSetPlanLimitsStore);
		}
	}

	module.exports = { SidebarView };
});
