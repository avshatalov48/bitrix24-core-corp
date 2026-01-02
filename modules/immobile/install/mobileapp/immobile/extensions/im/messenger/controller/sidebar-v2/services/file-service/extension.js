/**
 * @module im/messenger/controller/sidebar-v2/services/file-service
 */
jn.define('im/messenger/controller/sidebar-v2/services/file-service', (require, exports, module) => {
	const { RestMethod } = require('im/messenger/const');
	const { SidebarDataProvider } = require('im/messenger/controller/sidebar-v2/services/data-provider');

	const { getLogger } = require('im/messenger/lib/logger');
	const logger = getLogger('sidebar--file-service');

	const LIMIT = 50;

	class SidebarFileService extends SidebarDataProvider
	{
		/**
		 * @param props
		 * @param {string} props.chatId
		 * @param {number} props.dialogId
		 * @param {string[]} props.subtypes
		 */
		constructor(props)
		{
			super(props);

			this.chatId = props.chatId;
			this.dialogId = props.dialogId;
			this.subtypes = props.subtypes || [];
		}

		getInitialQueryHandler()
		{
			return (response) => this.#handlePage(response.data());
		}

		getInitialQueryMethod()
		{
			return RestMethod.imChatFileGet;
		}

		getStoreKey()
		{
			return this.subtypes.join('&');
		}

		getInitialQueryParams()
		{
			return {
				CHAT_ID: this.chatId,
				SUBTYPE: { ...this.subtypes },
			};
		}

		getQueryParams()
		{
			const lastLoadFileId = this.store.getters['sidebarModel/sidebarFilesModel/getLastLoadFileId'](
				this.chatId,
				this.getStoreKey(),
			);

			return {
				CHAT_ID: this.chatId,
				LAST_ID: lastLoadFileId,
				SUBTYPE: { ...this.subtypes },
			};
		}

		loadPage()
		{
			const dialogModel = this.store.getters['dialoguesModel/getById'](this.dialogId);

			if (!dialogModel)
			{
				return new Promise().resolve(false);
			}

			return new Promise((resolve, reject) => {
				BX.rest.callMethod(
					this.getInitialQueryMethod(),
					this.getQueryParams(),
				).then((response) => {
					if (response.error() || response.status !== 200)
					{
						logger.error('getFileList.error', response.error(), response.ex);
						reject(response.error());

						return;
					}

					const data = response.data();
					void this.#handlePage(data);

					resolve(data);
				}).catch((error) => {
					logger.error('loadPage', error);

					reject(error);
				})
				;
			});
		}

		/**
		 * @param data
		 * @param {Object[]} data.list
		 * @param {Object[]} data.users
		 * @param {Object[]} data.files
		 * @param {Object} data.tariffRestrictions
		 * @param {boolean} data.tariffRestrictions.isHistoryLimitExceeded
		 * @returns {Promise}
		 */
		#handlePage(data)
		{
			logger.info('getFileList:', data);

			const { list, users, files, tariffRestrictions } = data;
			const isHistoryLimitExceeded = Boolean(tariffRestrictions?.isHistoryLimitExceeded);

			void this.store.dispatch('usersModel/set', users);
			void this.store.dispatch('filesModel/set', files);

			const mutations = [];

			if (list.length > 0)
			{
				mutations.push(
					this.store.dispatch('sidebarModel/sidebarFilesModel/setFromPagination', {
						chatId: this.chatId,
						files: list,
						subType: this.getStoreKey(),
						hasNextPage: isHistoryLimitExceeded ? false : list?.length === LIMIT,
						isHistoryLimitExceeded,
						lastLoadFileId: list[list.length - 1].id,
					}),
				);
			}
			else
			{
				mutations.push(
					this.store.dispatch('sidebarModel/sidebarFilesModel/setFromPagination', {
						chatId: this.chatId,
						files: [],
						subType: this.getStoreKey(),
						hasNextPage: false,
						isHistoryLimitExceeded,
					}),
				);
			}

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

	module.exports = {
		SidebarFileService,
	};
});
