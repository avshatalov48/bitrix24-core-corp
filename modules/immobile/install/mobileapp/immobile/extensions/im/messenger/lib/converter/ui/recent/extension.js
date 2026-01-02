/**
 * @module im/messenger/lib/converter/ui/recent
 */
jn.define('im/messenger/lib/converter/ui/recent', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { Feature } = require('im/messenger/lib/feature');
	const {
		RecentItem,
		ChatItem,
		CollabItem,
		CopilotItem,
		UserItem,
		CallItem,
		AnnouncementItem,
		ExtranetItem,
		Support24NotifierItem,
		Support24QuestionItem,
		TaskCommentItem,
		CurrentUserItem,
		BotItem,
		SupportBotItem,
		ConnectorUserItem,
		ExtranetUserItem,
		CollaberUserItem,
		InvitedUserItem,
		NetworkUserItem,
		ChannelItem,
	} = require('im/messenger/lib/element/recent');
	const {
		BotType,
		ComponentCode,
		UserType,
	} = require('im/messenger/const');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('converter--ui-recent');
	/**
	 * @class RecentUiConverter
	 */
	class RecentUiConverter
	{
		/**
		 * @param {Array<RecentModelState>} recentItems
		 * @param {object} options
		 * @return {Array<RecentItem>}
		 */
		toList(recentItems, options = {})
		{
			const listItems = [];

			recentItems.forEach((item) => {
				listItems.push(this.toItem(item, options));
			});

			return listItems;
		}

		/**
		 * @param {RecentModelState} item
		 * @param {object} options
		 * @return {RecentItem}
		 */
		toItem(item, options = {})
		{
			const modelItem = serviceLocator.get('core').getStore().getters['recentModel/getById'](item.id);
			// eslint-disable-next-line no-param-reassign
			options = {
				...options,
				...this.#createOldMessengerOptions(),
			};

			if (DialogHelper.isChatId(modelItem.id))
			{
				return this.#toUserItem(modelItem, options);
			}

			return this.#toChatItem(modelItem, options);
		}

		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 * @return {RecentItem}
		 */
		#toChatItem(modelItem, options = {})
		{
			const dialogHelper = DialogHelper.createByDialogId(modelItem.id);

			if (!dialogHelper)
			{
				logger.error(`${this.constructor.name}.toChatItem: there is no dialog "${modelItem.id}" in model`);

				return new RecentItem(modelItem, options);
			}

			if (dialogHelper.isCollab)
			{
				return new CollabItem(modelItem, options);
			}

			if (dialogHelper.isChannel)
			{
				return new ChannelItem(modelItem, options);
			}

			if (dialogHelper.isCopilot)
			{
				return new CopilotItem(modelItem, options);
			}

			if (dialogHelper.isAnnouncement)
			{
				return new AnnouncementItem(modelItem, options);
			}

			if (dialogHelper.isSupport24Notifier)
			{
				return new Support24NotifierItem(modelItem, options);
			}

			if (dialogHelper.isSupport24Question)
			{
				return new Support24QuestionItem(modelItem, options);
			}

			if (dialogHelper.isExtranet)
			{
				return new ExtranetItem(modelItem, options);
			}

			if (dialogHelper.isTaskComment)
			{
				return new TaskCommentItem(modelItem, options);
			}

			return new ChatItem(modelItem, options);
		}

		/**
		 * @param {RecentModelState} modelItem
		 * @param {object} options
		 * @return {RecentItem}
		 */
		#toUserItem(modelItem, options = {})
		{
			const user = serviceLocator.get('core').getStore().getters['usersModel/getById'](modelItem.id);
			if (!user)
			{
				logger.error(`${this.constructor.name}.toUserItem: there is no user "${modelItem.id}" in model`);

				return new RecentItem(modelItem, options);
			}

			if (user.id === serviceLocator.get('core').getUserId())
			{
				return new CurrentUserItem(modelItem, options);
			}

			// eslint-disable-next-line es/no-optional-chaining
			if (modelItem?.invitation?.isActive === true && user.lastActivityDate === false)
			{
				return new InvitedUserItem(modelItem, options);
			}

			if (user.botData.type === BotType.support24)
			{
				return new SupportBotItem(modelItem, options);
			}

			if (user.network === true)
			{
				return new NetworkUserItem(modelItem, options);
			}

			if (user.bot === true)
			{
				return new BotItem(modelItem, options);
			}

			if (user.type === UserType.collaber)
			{
				return new CollaberUserItem(modelItem, options);
			}

			if (user.extranet === true)
			{
				return new ExtranetUserItem(modelItem, options);
			}

			if (user.connector === true)
			{
				return new ConnectorUserItem(modelItem, options);
			}

			return new UserItem(modelItem, options);
		}

		/**
		 * @param {RecentCallData} call
		 * @param {RecentCallStatus} callStatus
		 * @return {CallItem}
		 */
		toCallItem(callStatus, call)
		{
			return new CallItem(callStatus, call);
		}

		#createOldMessengerOptions()
		{
			if (Feature.isMessengerV2Enabled)
			{
				return {};
			}

			return {
				showActions: MessengerParams.getComponentCode() !== ComponentCode.imChannelMessenger,
				showCounter: MessengerParams.getComponentCode() !== ComponentCode.imChannelMessenger,
			};
		}
	}

	module.exports = { RecentUiConverter: new RecentUiConverter() };
});
