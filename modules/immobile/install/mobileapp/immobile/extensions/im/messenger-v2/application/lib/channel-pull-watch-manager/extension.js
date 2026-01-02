/**
 * @module im/messenger-v2/application/lib/channel-pull-watch-manager
 */
jn.define('im/messenger-v2/application/lib/channel-pull-watch-manager', (require, exports, module) => {
	const { Type } = require('type');
	const {
		RestMethod,
		EventType,
		NavigationTabId,
	} = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { runAction } = require('im/messenger/lib/rest');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');

	const logger = getLoggerWithContext('pull--channel-watch-manager', 'PullWatchManager');

	/**
	 * @class ChannelPullWatchManager
	 */
	class ChannelPullWatchManager
	{
		static instance = null;
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new ChannelPullWatchManager();
			}

			return this.instance;
		}

		/**
		 * @returns {NavigationController|null}
		 */
		get navigationController()
		{
			return serviceLocator.get('navigation-controller') ?? null;
		}

		get emitter()
		{
			return serviceLocator.get('emitter');
		}

		constructor()
		{
			this.timerId = null;
			/** @type {RecentController} */
			this.controller = null;

			this.subscribeEvents();
		}

		subscribeEvents()
		{
			this.emitter.on(EventType.recentManager.initController, this.initRecentControllerHandler);
			this.emitter.on(EventType.recentManager.resumeController, this.resumeRecentControllerHandler);
		}

		initRecentControllerHandler = (recentId, controller) => {
			if (recentId !== NavigationTabId.channel)
			{
				return;
			}
			logger.log('initRecentControllerHandler: extendPullWatch');
			this.controller = controller;

			this.extendPullWatch(false)
				.catch((error) => {
					logger.error('initRecentControllerHandler error', error);
				});
		};

		resumeRecentControllerHandler = (recentId) => {
			if (recentId !== NavigationTabId.channel)
			{
				return;
			}
			logger.log('resumeRecentControllerHandler: extendPullWatch');

			this.extendPullWatch(false)
				.catch((error) => {
					logger.error('initRecentControllerHandler error', error);
				});
		};

		async extendPullWatch(checkActiveTab = false)
		{
			if (checkActiveTab)
			{
				const isCurrentTabChannel = await this.#isTabChannelActive();

				if (!isCurrentTabChannel)
				{
					this.controller.markAsInactive();
					this.#clearInterval();

					return;
				}
			}

			try
			{
				await this.#extendWatch();
			}
			catch (error)
			{
				logger.error('extendPullWatch error', error);
				this.#clearInterval();
			}

			this.#setWatchTimer();
		}

		async #isTabChannelActive()
		{
			if (Type.isNull(this.navigationController))
			{
				return false;
			}

			const isMessengerActive = await this.navigationController.isMessengerTabActive();
			const isChannelTabActive = await this.navigationController.getActiveTab();

			return isMessengerActive && isChannelTabActive;
		}

		#setWatchTimer()
		{
			if (this.timerId)
			{
				return;
			}

			this.timerId = setInterval(() => this.extendPullWatch(), 600_000);
		}

		#clearInterval()
		{
			clearInterval(this.timerId);
			this.timerId = null;
		}

		async #extendWatch()
		{
			return runAction(RestMethod.imV2RecentChannelExtendPullWatch, {
				data: {},
			});
		}
	}

	module.exports = { ChannelPullWatchManager };
});
