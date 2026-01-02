/**
 * @module im/messenger-v2/controller/recent/controller
 */
jn.define('im/messenger-v2/controller/recent/controller', (require, exports, module) => {
	const { Type } = require('type');
	const { getLoggerWithContext } = require('im/messenger/lib/logger');
	const { createPromiseWithResolvers } = require('im/messenger/lib/utils');
	const { runAction } = require('im/messenger/lib/rest');
	const {
		RestMethod,
		RefreshMode,
	} = require('im/messenger/const');
	const { RecentEventType } = require('im/messenger-v2/controller/recent/const');

	/**
	 * @class RecentController
	 */
	class RecentController
	{
		/**
		 * @param {RecentLocator} locator
		 */
		constructor(locator)
		{
			/** @type {RecentLocator} */
			this.locator = locator;
			this.id = locator.get('id');

			const loggerContext = `${locator.get('id')} ${this.constructor.name}`;
			this.logger = getLoggerWithContext('recent--controller', loggerContext);
			this.initPromise = Promise.resolve(null);
			this.isActived = false;
			this.resumeMode = null;
		}

		async init(applicationStartUp = false)
		{
			this.locator.get('emitter').emit(RecentEventType.onInit, []);
			const { promise, resolve } = createPromiseWithResolvers();
			this.initPromise = promise;

			if (this.locator.has('quick-recent'))
			{
				await this.locator.get('quick-recent').renderList();
			}

			if (this.locator.has('database-load'))
			{
				try
				{
					await this.locator.get('database-load').loadFirstPage();
				}
				catch (error)
				{
					this.logger.error('init with load first page from db error', error);
				}
			}

			if (!applicationStartUp && this.locator.has('server-load'))
			{
				try
				{
					await this.#loadFirstPageFromServer(RefreshMode.startUp);
					this.locator.get('emitter').emit(RecentEventType.render.updateUIByRecentCollectionSizeIfNeeded, []);
				}
				catch (error)
				{
					this.logger.error('init with load first page from server error', error);
				}
			}

			this.markAsActive();

			resolve();
		}

		async resume()
		{
			if (this.isActive())
			{
				return;
			}

			if (Type.isNull(this.resumeMode))
			{
				return;
			}

			if (!this.locator.has('server-load'))
			{
				return;
			}

			this.logger.log('resume');

			try
			{
				this.isActived = true;
				await this.#loadFirstPageFromServer(this.resumeMode);
			}
			catch (error)
			{
				this.logger.error('resume error', error);
			}
		}

		isActive()
		{
			return this.isActived;
		}

		markAsInactive(mode = RefreshMode.resume)
		{
			if (this.locator.has('database-load'))
			{
				return;
			}
			this.logger.log('mark recent is inactive', this.id);

			this.isActived = false;
			this.resumeMode = mode;
		}

		markAsActive()
		{
			this.isActived = true;
			this.resumeMode = null;
		}

		openSearch()
		{
			if (this.locator.has('search'))
			{
				this.locator.get('search').openSearch();
			}
		}

		/**
		 * @param {string} mode
		 * @return {null|string}
		 */
		getRefreshMethod(mode)
		{
			if (this.locator.has('server-load'))
			{
				return this.locator.get('server-load').getInitRequestMethod(mode);
			}

			return null;
		}

		/**
		 * @param {RefreshModeType} mode
		 * @return {(function(*): Promise<void>)}
		 */
		getRefreshHandler(mode)
		{
			return async (refreshResult) => {
				await this.initPromise;

				if (this.locator.has('server-load'))
				{
					try
					{
						await this.locator.get('server-load').handleInitResult(mode, refreshResult);
					}
					catch (error)
					{
						this.logger.error(`refresh by mode ${mode} error`, error);
					}
				}
				this.markAsActive();
			};
		}

		async #loadFirstPageFromServer(mode)
		{
			try
			{
				const method = this.locator.get('server-load').getInitRequestMethod(mode);

				if (Type.isNull(method))
				{
					return;
				}

				const result = await runAction(RestMethod.immobileMessengerLoad, {
					data: {
						methodList: [method],
					},
				});

				await this.locator.get('server-load').handleInitResult(mode, result);
			}
			catch (error)
			{
				this.logger.error('loadFirstPageFromServer error', error);
			}
		}
	}

	module.exports = { RecentController };
});
