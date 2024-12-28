/**
 * @module im/messenger/controller/dialog-selector
 */
jn.define('im/messenger/controller/dialog-selector', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { ChatSelector } = require('im/chat/selector/chat');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');
	const { EventType } = require('im/messenger/const');
	const { SearchConverter } = require('im/messenger/lib/converter');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');

	/**
	 * @class DialogSelector
	 */
	class DialogSelector
	{
		/**
		 *
		 * @param {object} options
		 * @param {SelectorDialogListAdapter} options.view
		 * @param {Array<object>} [options.entities]
		 * @param {Function} [options.onRecentResult]
		 */
		constructor(options = {})
		{
			if (options.view)
			{
				this.view = options.view;
			}
			else
			{
				throw new Error('DialogSelector: options.view is required');
			}

			this.store = serviceLocator.get('core').getStore();

			if (options.entities)
			{
				this.entities = options.entities;
			}
			this.onRecentResult = options.onRecentResult;

			this.onScopeSelectedHandler = this.view.onScopeSelected.bind(this.view);
			this.onUserTypeTextHandler = this.view.onUserTypeText.bind(this.view);
			this.onSearchItemSelectedHandler = this.view.onSearchItemSelected.bind(this.view);
			this.onSearchSectionButtonClickHandler = this.view.searchSectionButtonClick.bind(this.view);

			this.subscribeEvents();
		}

		subscribeEvents()
		{
			this.view.on(EventType.recent.scopeSelected, this.onScopeSelectedHandler);
			this.view.on(EventType.recent.userTypeText, this.onUserTypeTextHandler);
			this.view.on(EventType.recent.searchItemSelected, this.onSearchItemSelectedHandler);
			this.view.on(EventType.recent.searchSectionButtonClick, this.onSearchSectionButtonClickHandler);
		}

		unsubscribeEvents()
		{
			this.view.off(EventType.recent.scopeSelected, this.onScopeSelectedHandler);
			this.view.off(EventType.recent.userTypeText, this.onUserTypeTextHandler);
			this.view.off(EventType.recent.searchItemSelected, this.onSearchItemSelectedHandler);
			this.view.off(EventType.recent.searchSectionButtonClick, this.onSearchSectionButtonClickHandler);
		}

		open()
		{
			const chatSelectorOptions = {
				context: 'IM_CHAT_SEARCH',
				ui: this.view,
				providerOptions: {
					minSearchSize: MessengerParams.get('SEARCH_MIN_SIZE', 3),
				},
				entities: this.entities,
				isNetworkSearchAvailable: MessengerParams.get('IS_NETWORK_SEARCH_AVAILABLE', false),
			};

			const userCarouselItem = this.getUserCarouselItem();
			if (Type.isArrayFilled(userCarouselItem.childItems))
			{
				chatSelectorOptions.providerOptions.customItems = [userCarouselItem];
			}

			this.selector = new ChatSelector(chatSelectorOptions);
			if (this.onRecentResult)
			{
				this.selector.onRecentResult = this.onRecentResult;
			}

			this.selector
				.setSingleChoose(true)
				.open()
			;

			// hack to work on old android clients
			this.selector.onResult = (chat) => {
				this.selector.resolve(chat);

				Logger.info('Chat selected', chat);
				const data = {
					dialogId: chat.id,
					dialogTitleParams: {
						name: chat.name,
						description: chat.description,
						avatar: chat.avatar,
						color: chat.color,
					},
				};

				if (chat.customData.imChat)
				{
					data.dialogTitleParams.chatType = chat.customData.imChat.TYPE;

					// TODO: delete when the mobile chat learns about open lines, call chats and others.
					if (data.dialogTitleParams.chatType === 'lines')
					{
						data.dialogTitleParams.description = Loc.getMessage('MOBILE_EXT_CHAT_SELECTOR_LINES_SUBTITLE');
					}
					else if (chat.customData.imChat.TYPE === 'open')
					{
						data.dialogTitleParams.description = Loc.getMessage('MOBILE_EXT_CHAT_SELECTOR_CHANNEL_SUBTITLE_MSGVER_1');
					}
					else
					{
						data.dialogTitleParams.description = Loc.getMessage('MOBILE_EXT_CHAT_SELECTOR_GROUP_SUBTITLE_MSGVER_1');
					}
				}

				MessengerEmitter.emit(EventType.messenger.openDialog, data);
			};
		}

		/**
		 * @return {RecentUserCarouselItem}
		 */
		getUserCarouselItem()
		{
			/** @type {RecentModelState[]} */
			const recentUserList = clone(this.store.getters['recentModel/getUserList']());
			const recentUserListIndex = {};
			const recentUserListRemoveIndex = {};

			/** @type {Array<RecentCarouselItem>} */
			const userItems = [];
			if (Type.isArrayFilled(recentUserList))
			{
				recentUserList.forEach((recentUserChat) => {
					const userStateModel = this.store.getters['usersModel/getById'](recentUserChat.id);
					if (userStateModel)
					{
						if (
							userStateModel.id === MessengerParams.getUserId()
							|| userStateModel.bot
							|| userStateModel.invited
						)
						{
							recentUserListRemoveIndex[recentUserChat.id] = true;

							return;
						}

						recentUserListIndex[recentUserChat.id] = true;

						userItems.push(SearchConverter.toUserCarouselItem(userStateModel));
					}
				});
			}

			const colleaguesList = clone(this.store.getters['usersModel/getList']());
			if (Type.isArrayFilled(colleaguesList))
			{
				colleaguesList.forEach((user) => {
					if (
						recentUserListIndex[user.id]
						|| user.id === MessengerParams.getUserId()
						|| user.bot
						|| recentUserListRemoveIndex[user.id]
					)
					{
						return;
					}

					userItems.push(SearchConverter.toUserCarouselItem(user));
				});
			}

			return {
				type: 'carousel',
				sectionCode: 'custom',
				childItems: userItems,
				hideBottomLine: true,
			};
		}

		close()
		{
			// stub for reverse dependence with RecentSelector;
		}
	}

	module.exports = { DialogSelector };
});
