/**
 * @module im/messenger/controller/sidebar-v2/services/common-chats-service
 */
jn.define('im/messenger/controller/sidebar-v2/services/common-chats-service', (require, exports, module) => {
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { RestMethod } = require('im/messenger/const/rest');
	const { SidebarDataProvider } = require('im/messenger/controller/sidebar-v2/services/data-provider');

	const LIMIT = 50;

	class SidebarCommonChatsService extends SidebarDataProvider
	{
		constructor(props)
		{
			super(props);

			this.props = props;
		}

		getInitialQueryMethod()
		{
			return RestMethod.imV2ChatListShared;
		}

		getInitialQueryParams()
		{
			return {
				limit: LIMIT,
				filter: {
					userId: this.getUserId(),
				},
			};
		}

		getInitialQueryHandler()
		{
			return (response) => this.#handlePage(response.data());
		}

		loadPage(offset = 0)
		{
			const startFrom = Number(offset);
			const config = {
				...this.getInitialQueryParams(),
				offset: startFrom > 0 ? startFrom : undefined,
			};

			if (!config.filter.userId)
			{
				const error = 'User ID is not defined';
				this.logger.error('loadPage', error, config);

				return Promise.reject(new Error(error));
			}

			return new Promise((resolve, reject) => {
				BX.rest.callMethod(RestMethod.imV2ChatListShared, config)
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

		#handlePage(data = {})
		{
			const { chats } = data;
			const { chatId } = this.props;

			return this.store.dispatch('sidebarModel/sidebarCommonChatsModel/setFromPagination', {
				chats,
				chatId,
				hasNextPage: chats?.length === LIMIT,
			});
		}

		getUserId()
		{
			const { dialogId } = this.props;

			const dialogHelper = DialogHelper.createByDialogId(dialogId);

			if (!dialogHelper.isDirect)
			{
				return null;
			}

			return Number(dialogId);
		}
	}

	module.exports = {
		SidebarCommonChatsService,
	};
});
