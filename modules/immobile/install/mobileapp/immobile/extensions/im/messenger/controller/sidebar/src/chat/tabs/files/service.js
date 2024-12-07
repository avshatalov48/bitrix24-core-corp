/**
 * @module im/messenger/controller/sidebar/chat/tabs/files/service
 */

jn.define('im/messenger/controller/sidebar/chat/tabs/files/service', (require, exports, module) => {
	const { RestMethod } = require('im/messenger/const/rest');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('sidebar--files-service');
	const { SidebarFileType } = require('im/messenger/const');

	const REQUEST_ITEMS_LIMIT = 50;

	/**
	 * @class SidebarFilesService
	 */
	class SidebarFilesService
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
				SUBTYPE: SidebarFileType.document,
			};
		}

		getBatchResponseHandler(response)
		{
			void this.updateModels(response.data());
		}

		/**
		 * @desc Rest call files
		 * @return {Promise<object>}
		 */
		requestPage(queryParams)
		{
			return new Promise((resolve, reject) => {
				void BX.rest.callMethod(RestMethod.imChatFileGet, queryParams)
					.then((response) => {
						const data = response.data();

						void this.updateModels(data);
						resolve(data);
					})
					.catch((error) => {
						logger.error(`${this.constructor.name}.requestPage:`, error, queryParams);

						return reject(error);
					});
			});
		}

		/**
		 * @param {?number | ?null} lastId
		 * @return Promise
		 */
		loadNextPage(lastId)
		{
			const { REQUEST_ITEMS_LIMIT: LIMIT } = this.getRequestParams();

			const config = {
				CHAT_ID: this.chatId,
				LIMIT,
				SUBTYPE: SidebarFileType.document,
			};

			if (lastId)
			{
				config.LAST_ID = lastId;
			}

			return this.requestPage(config);
		}

		/**
		 * @private
		 * @param {SidebarFilesUpdateModel} data
		 * @return Promise
		 */
		updateModels(data)
		{
			const { list, users, files, tariffRestrictions } = data;
			const { REQUEST_ITEMS_LIMIT: LIMIT } = this.getRequestParams();
			const isHistoryLimitExceeded = Boolean(tariffRestrictions?.isHistoryLimitExceeded);

			void this.store.dispatch('usersModel/set', users);
			void this.store.dispatch('filesModel/set', files);

			const promises = [];
			const setFilesPromise = this.store.dispatch('sidebarModel/sidebarFilesModel/setFromPagination', {
				chatId: this.chatId,
				files: list,
				subType: SidebarFileType.document,
				hasNextPage: isHistoryLimitExceeded ? true : list?.length === LIMIT,
				isHistoryLimitExceeded,
			});
			promises.push(setFilesPromise);

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
		SidebarFilesService,
	};
});
