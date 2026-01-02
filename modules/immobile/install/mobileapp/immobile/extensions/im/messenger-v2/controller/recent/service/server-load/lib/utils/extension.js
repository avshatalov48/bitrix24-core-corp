/**
 * @module im/messenger-v2/controller/recent/service/server-load/lib/utils
 */
jn.define('im/messenger-v2/controller/recent/service/server-load/lib/utils', (require, exports, module) => {
	const { Type } = require('type');
	const { CounterHelper } = require('im/messenger/lib/helper');

	class ServerLoadUtils
	{
		/**
		 * @param {Array<messagesAutoDeleteConfigs>} configs
		 * @return {Object}
		 */
		static prepareAutoDeleteConfigs(configs)
		{
			const map = {};
			if (Type.isArray(configs))
			{
				configs.forEach((item) => {
					map[item.chatId] = item;
				});
			}

			return map;
		}

		/**
		 * @param {Array<ChatsCopilotDataItem>} chats
		 * @return {Object}
		 */
		static prepareCopilotChats(chats)
		{
			const map = {};
			if (Type.isArray(chats))
			{
				chats.forEach((copilotChat) => {
					map[copilotChat.dialogId] = true;
				});
			}

			return map;
		}

		/**
		 * @param {object} dialogItem
		 * @return {CounterModelState}
		 */
		static buildCounterState(dialogItem)
		{
			return {
				chatId: dialogItem.chatId ?? dialogItem.id ?? 0,
				type: CounterHelper.getCounterTypeByDialogType(dialogItem.type),
				parentChatId: dialogItem.parent_chat_id ?? dialogItem.parentChatId ?? 0,
				counter: dialogItem.counter,
				disabled: CounterHelper.getDisabledByMuteList(dialogItem.muteList ?? dialogItem.mute_list),
			};
		}

		/**
		 * @param {Object} params
		 * @param {Object} params.copilotChats
		 * @param {immobileTabChatLoadResultRecentList
		 * |imV2RecentChatsResult|immobileTabCopilotLoadResultCopilotList
		 * |imV2RecentCopilotResult} params.recentData
		 * @return {Array}
		 */
		static buildCopilotResults({ copilotChats, recentData })
		{
			const copilotResult = [];
			const copilotChatsArr = recentData.copilot?.chats || [];
			const copilotRoles = recentData.copilot?.roles;
			const copilotMessagesArr = recentData.copilot?.messages || [];

			Object.entries(copilotChats)
				.forEach(([dialogId, chat]) => {
					const chats = copilotChatsArr.find((c) => c.dialogId === dialogId);
					const copilotMessages = copilotMessagesArr.find((message) => chat && message.id === chat.message?.id);

					const copilotItem = {
						dialogId,
						chats: [chats],
						messages: [copilotMessages],
						aiProvider: '',
						roles: copilotRoles,
					};

					copilotResult.push(copilotItem);
				});

			return copilotResult;
		}

		/**
		 * @param {RecentItemData|any} recenItem
		 * @return {RecentItemData}
		 */
		static prepareRecentItem(recenItem)
		{
			const preparedRecentItem = {
				...recenItem,
				avatar: recenItem.avatar?.url,
				color: recenItem.avatar?.color,
				counter: recenItem.counter,
			};

			if (Type.isPlainObject(preparedRecentItem.message?.file) && preparedRecentItem.message.file?.id)
			{
				preparedRecentItem.message.params = { withFile: [String(preparedRecentItem.message.file.id)] };
			}

			return preparedRecentItem;
		}

		/**
		 * @param {Array<RecentItemData>} restItems
		 * @param {Logger} logger
		 * @return {RecentItemData}
		 */
		static getLastItem(restItems, logger)
		{
			if (!Type.isArrayFilled(restItems))
			{
				return null;
			}
			const lastOriginalFromServer = restItems[restItems.length - 1];

			const filteredPined = restItems.filter((item) => !item.pinned);
			if (filteredPined.length > 0)
			{
				return lastOriginalFromServer;
			}

			const sorted = [...filteredPined].sort((a, b) => {
				const dateA = new Date(a.date_last_activity).getTime();
				const dateB = new Date(b.date_last_activity).getTime();

				return dateB - dateA;
			});

			const lastSorted = sorted[sorted.length - 1];
			if (!lastOriginalFromServer || !lastSorted)
			{
				return lastSorted || lastOriginalFromServer || null;
			}

			if (lastOriginalFromServer.id !== lastSorted.id)
			{
				logger.error(
					'getLastItem: last item from server restItems array differs from sorted by date_last_activity',
					{
						lastOriginalFromServer,
						lastSorted,
						restItems,
					},
				);
			}

			return lastSorted;
		}
	}

	module.exports = { ServerLoadUtils };
});
