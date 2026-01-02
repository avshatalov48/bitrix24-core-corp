/**
 * @module im/messenger/controller/sidebar-v2/services/links-service
 */
jn.define('im/messenger/controller/sidebar-v2/services/links-service', (require, exports, module) => {
	const { SidebarDataProvider } = require('im/messenger/controller/sidebar-v2/services/data-provider');
	const { RestMethod } = require('im/messenger/const/rest');

	const LIMIT = 50;

	class SidebarLinksService extends SidebarDataProvider
	{
		constructor(chatId)
		{
			super();
			this.chatId = chatId;
		}

		getInitialQueryMethod()
		{
			return RestMethod.imChatUrlGet;
		}

		getInitialQueryParams()
		{
			return {
				LIMIT,
				CHAT_ID: this.chatId,
			};
		}

		getInitialQueryHandler()
		{
			return (response) => this.#handlePage(response.data());
		}

		/**
		 * @param {number} offset
		 */
		loadPage(offset = 0)
		{
			const startFrom = Number(offset);

			const config = {
				LIMIT,
				CHAT_ID: this.chatId,
				OFFSET: startFrom > 0 ? startFrom : undefined,
			};

			return new Promise((resolve, reject) => {
				BX.rest.callMethod(RestMethod.imChatUrlGet, config)
					.then((response) => {
						const data = response.data();
						this.#handlePage(data);
						resolve(data);
					})
					.catch((error) => {
						this.logger.error('loadPage', error, config);

						reject(error);
					});
			});
		}

		deleteLink(linkId)
		{
			return new Promise((resolve) => {
				BX.rest.callMethod(RestMethod.imChatUrlDelete, { LINK_ID: linkId })
					.then(() => {
						this.store.dispatch('sidebarModel/sidebarLinksModel/delete', {
							chatId: this.chatId,
							id: linkId,
						});
						resolve();
					})
					.catch((error) => {
						this.logger.error('deleteLink:', error, linkId);
					});
			});
		}

		#handlePage(data = {})
		{
			const { list, users, tariffRestrictions } = data;
			const isHistoryLimitExceeded = Boolean(tariffRestrictions?.isHistoryLimitExceeded);

			void this.store.dispatch('usersModel/set', users);

			const mutations = [];

			mutations.push(
				this.store.dispatch('sidebarModel/sidebarLinksModel/setFromPagination', {
					chatId: this.chatId,
					links: list,
					hasNextPage: isHistoryLimitExceeded ? false : list?.length === LIMIT,
					isHistoryLimitExceeded,
				}),
			);

			if (isHistoryLimitExceeded)
			{
				mutations.push(
					this.store.dispatch('sidebarModel/setHistoryLimitExceeded', {
						chatId: this.chatId,
						isHistoryLimitExceeded: true,
					}),
				);
			}

			return Promise.all(mutations);
		}
	}

	module.exports = { SidebarLinksService };
});
