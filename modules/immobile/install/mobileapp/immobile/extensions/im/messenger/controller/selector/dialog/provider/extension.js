/**
 * @module im/messenger/controller/selector/dialog/provider
 */

jn.define('im/messenger/controller/selector/dialog/provider', (require, exports, module) => {
	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const { withCurrentDomain } = require('utils/url');

	const { BaseSelectorProvider } = require('selector/providers/base');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	const { RecentProvider: SearchProvider } = require('im/messenger/controller/search/experimental');
	const { ChatTitle } = require('im/messenger/lib/element/chat-title');
	const { ChatAvatar } = require('im/messenger/lib/element/chat-avatar');
	const { DialogType } = require('im/messenger/const');
	const { UserHelper } = require('im/messenger/lib/helper');

	const ASSET_PATH = '/bitrix/mobileapp/immobile/extensions/im/messenger/assets/common/png/';

	/**
	 * @class DialogSelectorProvider
	 */
	class DialogSelectorProvider extends BaseSelectorProvider
	{
		/**
		 * @param {String} context
		 * @param {Object} options
		 * @param {Boolean} options.allowMultipleSelection=false
		 * @param {Boolean} options.withFavorite=false
		 * @param {Boolean} options.withCurrentUser=true
		 * @param {Boolean} options.onlyUsers=false
		 */
		constructor(context, options = {})
		{
			super(context);
			this.queryString = '';
			this.items = [];

			this.isMultipleSelection = options?.allowMultipleSelection ?? false;
			this.withFavorite = options?.withFavorite ?? false;

			this.withCurrentUser = options?.withCurrentUser ?? true;
			this.onlyUsers = options?.onlyUsers ?? false;

			this.store = serviceLocator.get('core').getMessengerStore();

			this.searchProvider = new SearchProvider({
				loadSearchProcessed: this.#onLocalSearchComplete,
				loadSearchComplete: this.#onServerSearchComplete,
			});
		}

		loadRecent()
		{
			const recentDialogs = this.#getRecentDialogs();

			const preparedItems = this.prepareItemsForDrawing(recentDialogs);
			this.items = this.withFavorite ? this.#withFavoriteItem(preparedItems) : preparedItems;
			this.listener.onRecentResult(this.items, true);
		}

		prepareItemsForDrawing(items)
		{
			return items.map((item) => this.prepareItemForDrawing(item));
		}

		prepareItemForDrawing(item)
		{
			if (UserHelper.isCurrentUser(item.dialogId) && this.withFavorite)
			{
				return this.#getFavoriteItem();
			}

			const chatAvatar = ChatAvatar.createFromDialogId(item.dialogId);
			const chatTitle = ChatTitle.createFromDialogId(item.dialogId);

			const title = chatTitle.getTitle({ useNotes: false }) ?? item.name;

			return {
				id: item.dialogId,
				title,
				subtitle: chatTitle.getDescription(),
				useLetterImage: false,
				sectionCode: 'common',
				avatar: chatAvatar.getListItemAvatarProps(),
				type: this.isMultipleSelection ? null : 'info',
				params: {
					id: item.dialogId,
					title,
					type: 'dialog',
				},
			};
		}

		doSearch(query)
		{
			this.queryString = query;
			const currentQuery = query.trim().toLocaleLowerCase(env.languageId);
			if (currentQuery.length === 0)
			{
				this.processedQuery = '';
				this.listener.onFetchResult(this.items, true);

				return;
			}

			if (currentQuery === this.processedQuery)
			{
				return;
			}

			this.processedQuery = currentQuery;

			void this.searchProvider.doSearch(currentQuery);
		}

		#getDialogsByIds(itemIdList)
		{
			const getDialogById = this.store.getters['dialoguesModel/getById'];
			let dialogs = itemIdList.map((id) => getDialogById(id));

			if (this.onlyUsers)
			{
				// for copilot see 'im/messenger/lib/helper/dialog' DialogHelper.isDirect,
				// need MessengerStore, not Store from Helper
				const isDirect = (dialog) => {
					return Type.isNumber(Number(dialog.chatId))
						&& [DialogType.user, DialogType.private].includes(dialog.type);
				};

				dialogs = dialogs.filter((dialog) => isDirect(dialog));
			}

			if (!this.withCurrentUser)
			{
				dialogs = dialogs.filter((dialog) => {
					return Number(dialog.dialogId) !== serviceLocator.get('core').getUserId();
				});
			}

			return dialogs;
		}

		#getRecentDialogs()
		{
			const recentDialogIds = this.store.getters['recentModel/getSortedCollection']()
				.map(({ id }) => id)
			;

			return this.#getDialogsByIds(recentDialogIds);
		}

		#withFavoriteItem(items)
		{
			return [this.#getFavoriteItem(), ...items];
		}

		#onLocalSearchComplete = (itemIdList, needSearchOnServer) => {
			if (itemIdList.length === 0 && !needSearchOnServer)
			{
				this.listener.onFetchResult([], true);

				return;
			}

			this.items = this.prepareItemsForDrawing(this.#getDialogsByIds(itemIdList));
			if (needSearchOnServer)
			{
				this.items.push(this.#getLoadingItem());
			}

			this.listener.onFetchResult(this.items, true);
		};

		#onServerSearchComplete = (itemIdList) => {
			if (itemIdList.length === 0)
			{
				this.listener.onFetchResult([], false);

				return;
			}

			this.items = this.prepareItemsForDrawing(this.#getDialogsByIds(itemIdList));

			this.listener.onFetchResult(this.items, false);
		};

		#getFavoriteItem()
		{
			return {
				id: env.userId,
				title: Loc.getMessage('IMMOBILE_MESSENGER_FORWARD_SELECTOR_FAVORITE_ITEM'),
				useLetterImage: false,
				avatar: {
					hideOutline: true,
					uri: withCurrentDomain(`${ASSET_PATH}favorite_avatar.png`),
				},
				type: this.isMultipleSelection ? 'user' : 'info',
			};
		}

		#getLoadingItem()
		{
			return {
				id: 'loading',
				title: Loc.getMessage('IMMOBILE_MESSENGER_FORWARD_SELECTOR_LOADING_ITEM'),
				type: 'loading',
				unselectable: true,
				sectionCode: 'common',
			};
		}
	}

	module.exports = { DialogSelectorProvider };
});
