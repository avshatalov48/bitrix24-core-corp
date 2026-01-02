/**
 * @module im/messenger-v2/controller/recent/manager
 */
jn.define('im/messenger-v2/controller/recent/manager', (require, exports, module) => {
	const { Type } = require('type');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { EventType } = require('im/messenger/const');

	const { RecentUiGetter } = require('im/messenger-v2/controller/recent/recent-ui-getter');
	const { RecentConfigurator } = require('im/messenger-v2/controller/recent/configurator');
	const { RecentConfig } = require('im/messenger-v2/controller/recent/config');
	const { createLocator } = require('im/messenger-v2/controller/recent/locator');
	const { waitViewLoaded } = require('im/messenger-v2/lib/wait-view-loaded');

	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const logger = getLoggerWithContext('recent--manager', 'RecentManager');

	/**
	 * @class RecentManager
	 */
	class RecentManager
	{
		/** @type {RecentManager} */
		static #instance = null;
		#recentGetter = null;

		/**
		 * @return {RecentManager}
		 */
		static getInstance()
		{
			if (!this.#instance)
			{
				this.#instance = new RecentManager();
			}

			return this.#instance;
		}

		constructor()
		{
			this.initializedLists = new Set();
			/** @type {string} */
			this.currentListId = MessengerParams.get('FIRST_TAB_ID', 'chats');
			/** @type {Map<string, RecentController>} */
			this.controllerCollection = new Map();

			this.setActiveRecent(this.currentListId, true);
		}

		/**
		 * @return {RecentUiGetter}
		 */
		get recentGetter()
		{
			this.#recentGetter = this.#recentGetter ?? new RecentUiGetter();

			return this.#recentGetter;
		}

		/**
		 * @return {RecentController}
		 */
		getActiveRecent()
		{
			return this.controllerCollection.get(this.currentListId);
		}

		/**
		 * @return {string}
		 */
		getActiveRecentId()
		{
			return this.getActiveRecent().id;
		}

		setActiveRecent(recentId, applicationStartUp = false)
		{
			logger.warn(`setActiveRecent: ${recentId}; applicationStartUp: ${applicationStartUp}`);

			if (!this.initializedLists.has(recentId))
			{
				const controller = this.#initController(recentId, applicationStartUp);

				this.emit(EventType.recentManager.initController, recentId, controller);

				return controller;
			}

			const controller = this.#resumeController(recentId);

			this.emit(EventType.recentManager.resumeController, recentId, controller);

			return controller;
		}

		inactiveRecents(mode)
		{
			for (const controller of this.controllerCollection.values())
			{
				controller.markAsInactive(mode);
			}
		}

		#initController(recentId, applicationStartUp)
		{
			const uiPromise = this.#getRecentWidgetPromise(recentId);

			const controller = this.#initializeRecentList(recentId, uiPromise);

			controller.init(applicationStartUp)
				.catch((error) => {
					logger.error(`setActiveRecent: controller ${recentId} init error`, error);
				})
			;

			return controller;
		}

		#resumeController(recentId)
		{
			this.currentListId = recentId;
			const controller = this.controllerCollection.get(recentId);

			controller.resume()
				.catch((error) => {
					logger.error(`setActiveRecent: controller ${recentId} resume error`, error);
				})
			;

			return controller;
		}

		#initializeRecentList(recentId, ui)
		{
			const config = RecentConfig[recentId];
			if (!Type.isPlainObject(config))
			{
				throw new TypeError(`undefined recentId: ${recentId}`);
			}

			const locator = createLocator(recentId, ui);
			const controller = RecentConfigurator.createRecentController(config, locator);
			this.initializedLists.add(recentId);
			this.controllerCollection.set(recentId, controller);
			this.currentListId = recentId;

			return controller;
		}

		#getRecentWidgetPromise(recentId)
		{
			return new Promise((resolve) => {
				waitViewLoaded()
					.then(() => {
						const widget = this.recentGetter.getRecentListByTabId(recentId);

						resolve(widget);
					})
					.catch((error) => {
						logger.error(`getRecentWidgetPromise ${recentId} error`, error);
					});
			});
		}

		emit(eventName, ...args)
		{
			serviceLocator.get('emitter').emit(eventName, args);
		}
	}

	module.exports = { RecentManager };
});
