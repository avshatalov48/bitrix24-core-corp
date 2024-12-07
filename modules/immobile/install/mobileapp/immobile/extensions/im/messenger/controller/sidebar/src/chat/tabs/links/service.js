/**
 * @module im/messenger/controller/sidebar/chat/tabs/links/service
 */

jn.define('im/messenger/controller/sidebar/chat/tabs/links/service', (require, exports, module) => {
	const { Type } = require('type');
	const { RestMethod } = require('im/messenger/const/rest');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--links-service');

	const REQUEST_ITEMS_LIMIT = 50;

	/**
	 * @class SidebarLinksService
	 */
	class SidebarLinksService
	{
		constructor(chatId)
		{
			this.core = serviceLocator.get('core');
			this.store = this.core.getStore();
			this.chatId = chatId;
		}

		getRequestParams()
		{
			return {
				REQUEST_ITEMS_LIMIT,
			};
		}

		getInitialBatchParams()
		{
			return {
				CHAT_ID: this.chatId,
				LIMIT: this.getRequestParams().REQUEST_ITEMS_LIMIT,
			};
		}

		getBatchResponseHandler(response)
		{
			this.updateModels(response.data());
		}

		/**
		 * @desc Rest call files
		 * @return {Promise<object>}
		 */
		requestPage(queryParams)
		{
			return new Promise((resolve, reject) => {
				void BX.rest.callMethod(RestMethod.imChatUrlGet, queryParams)
					.then((response) => {
						const data = response.data();

						this.updateModels(data);
						resolve(data);
					})
					.catch((error) => {
						logger.error(`${this.constructor.name}.requestPage:`, error, queryParams);

						reject(error);
					});
			});
		}

		/**
		 * @desc Rest call files
		 * @return {Promise<object>}
		 */
		deleteLink(linkId)
		{
			return new Promise((resolve) => {
				void BX.rest.callMethod(RestMethod.imChatUrlDelete, { LINK_ID: linkId })
					.then(() => {
						this.store.dispatch('sidebarModel/sidebarLinksModel/delete', {
							chatId: this.chatId,
							id: linkId,
						});
						resolve();
					})
					.catch((error) => {
						logger.error(`${this.constructor.name}.deleteLink:`, error, linkId);
					});
			});
		}

		loadNextPage(offset = 0)
		{
			const { REQUEST_ITEMS_LIMIT: LIMIT } = this.getRequestParams();

			const config = {
				CHAT_ID: this.chatId,
				LIMIT,
			};

			if (Type.isNumber(offset) && offset > 0)
			{
				config.OFFSET = offset;
			}

			return this.requestPage(config);
		}

		updateModels(data)
		{
			const { list, users, tariffRestrictions } = data;
			const { REQUEST_ITEMS_LIMIT: LIMIT } = this.getRequestParams();
			const isHistoryLimitExceeded = Boolean(tariffRestrictions?.isHistoryLimitExceeded);

			void this.store.dispatch('usersModel/set', users);

			const promises = [];
			const setLinksPromise = this.store.dispatch('sidebarModel/sidebarLinksModel/setFromPagination', {
				chatId: this.chatId,
				links: list,
				hasNextPage: isHistoryLimitExceeded ? true : list?.length === LIMIT,
				isHistoryLimitExceeded,
			});

			promises.push(setLinksPromise);

			if (tariffRestrictions?.isHistoryLimitExceeded)
			{
				const setPlanLimits = this.store.dispatch('sidebarModel/setHistoryLimitExceeded', {
					chatId: this.chatId,
					isHistoryLimitExceeded: true,
				});
				promises.push(setPlanLimits);
			}

			return Promise.all(promises);
		}
	}

	module.exports = {
		SidebarLinksService,
	};
});
