/**
 * @module im/messenger/controller/dialog-selector
 */
jn.define('im/messenger/controller/dialog-selector', (require, exports, module) => {

	const { Type } = require('type');
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { ChatSelector } = require('im/chat/selector/chat');
	const { core } = require('im/messenger/core');
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

			this.store = core.getStore();

			if (options.entities)
			{
				this.entities = options.entities;
			}
			this.onRecentResult = options.onRecentResult;

			this.view.on(EventType.recent.scopeSelected, this.view.onScopeSelected.bind(this.view));
			this.view.on(EventType.recent.userTypeText, this.view.onUserTypeText.bind(this.view));
			this.view.on(EventType.recent.searchItemSelected, this.view.onSearchItemSelected.bind(this.view));
			this.view.on(EventType.recent.searchSectionButtonClick, this.view.searchSectionButtonClick.bind(this.view));
		}

		open()
		{
			const chatSelectorOptions = {
				context: 'IM_CHAT_SEARCH',
				ui: this.view,
				providerOptions: {
					minSearchSize : MessengerParams.get('MIN_SEARCH_SIZE', 3),
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

			//hack to work on old android clients
			this.selector.onResult = chat => {
				this.selector.resolve(chat);

				Logger.info('Chat selected', chat);
				const data = {
					dialogId: chat.id,
					dialogTitleParams: {
						name: chat.name,
						description: chat.description,
						avatar: chat.avatar,
						color: chat.color,
					}
				};

				if (chat.customData['imChat'])
				{
					data.dialogTitleParams.chatType = chat.customData['imChat'].TYPE;

					// TODO: delete when the mobile chat learns about open lines, call chats and others.
					if (data.dialogTitleParams.chatType === 'lines')
					{
						data.dialogTitleParams.description = Loc.getMessage('MOBILE_EXT_CHAT_SELECTOR_LINES_SUBTITLE');
					}
					else if (chat.customData['imChat'].TYPE === 'open')
					{
						data.dialogTitleParams.description = Loc.getMessage('MOBILE_EXT_CHAT_SELECTOR_CHANNEL_SUBTITLE');
					}
					else
					{
						data.dialogTitleParams.description = Loc.getMessage('MOBILE_EXT_CHAT_SELECTOR_GROUP_SUBTITLE');
					}
				}

				MessengerEmitter.emit(EventType.messenger.openDialog, data);
			}
		}

		getUserCarouselItem()
		{
			const recentUserList = clone(this.store.getters['recentModel/getUserList']);
			const recentUserListIndex = {};
			const recentUserListRemoveIndex = {};

			const userItems = [];
			if (Type.isArrayFilled(recentUserList))
			{
				recentUserList.forEach(recentUserChat => {
					if (
						recentUserChat.user.id === MessengerParams.getUserId()
						|| recentUserChat.user.bot
						|| recentUserChat.invited
					)
					{
						recentUserListRemoveIndex[recentUserChat.user.id] = true;

						return;
					}

					recentUserListIndex[recentUserChat.user.id] = true;

					userItems.push(SearchConverter.toUserCarouselItem(recentUserChat.user));
				});
			}

			const colleaguesList = clone(this.store.getters['usersModel/getUserList']);
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
	}

	module.exports = { DialogSelector };
});
