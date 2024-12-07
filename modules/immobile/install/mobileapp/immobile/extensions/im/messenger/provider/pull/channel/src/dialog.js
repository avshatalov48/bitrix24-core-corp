/**
 * @module im/messenger/provider/pull/channel/dialog
 */
jn.define('im/messenger/provider/pull/channel/dialog', (require, exports, module) => {
	const { Type } = require('type');
	const { EventType, UserRole } = require('im/messenger/const');
	const { BaseDialogPullHandler } = require('im/messenger/provider/pull/base');
	const { DialogHelper } = require('im/messenger/lib/helper');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { ChatDataProvider } = require('im/messenger/provider/data');

	/**
	 * @class ChannelDialogPullHandler
	 */
	class ChannelDialogPullHandler extends BaseDialogPullHandler
	{
		constructor()
		{
			super();
			this.supportSharedEvents = true;
		}

		async handleChatUserLeave(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatUserLeave`, params);

			const dialogId = params.dialogId;

			if (Number(params.userId) === MessengerParams.getUserId())
			{
				const chatProvider = new ChatDataProvider();
				const chatDataResult = await chatProvider.get({ dialogId });

				if (!chatDataResult.hasData())
				{
					return;
				}

				const chatData = chatDataResult.getData();
				const chatHelper = DialogHelper.createByModel(chatData);

				if (!chatHelper.isOpenChannel)
				{
					return;
				}

				this.store.dispatch('dialoguesModel/update', {
					dialogId,
					fields: {
						role: UserRole.guest,
					},
				});

				// close the comment chat linked to this channel
				const commentChatData = this.store.getters['dialoguesModel/getByParentChatId'](chatData.chatId);
				if (
					Type.isPlainObject(commentChatData)
					&& this.store.getters['applicationModel/isDialogOpen'](commentChatData.dialogId)
				)
				{
					MessengerEmitter.emit(EventType.dialog.external.delete, {
						dialogId: commentChatData.dialogId,
						shouldShowAlert: false,
						chatType: chatDataResult.getData().type,
						shouldSendDeleteAnalytics: false,
					});
				}

				chatProvider.deleteFromSource(ChatDataProvider.source.database, {
					dialogId,
				}).catch((error) => {
					this.logger.error(`${this.constructor.name}.handleChatUserLeave delete chat error`, error);
				});

				MessengerEmitter.emit(EventType.dialog.external.delete, {
					dialogId,
					shouldShowAlert: true,
					chatType: chatDataResult.getData().type,
					shouldSendDeleteAnalytics: false,
				});
			}

			this.store.dispatch('dialoguesModel/removeParticipants', {
				dialogId,
				participants: [params.userId],
				userCounter: params.userCount,
			})
				.catch((error) => {
					this.logger.error(
						`${this.getClassName()}.handleChatUserLeave.dialoguesModel/removeParticipants.catch:`,
						error,
					);
				})
			;
		}

		/**
		 * @param {DialoguesModelState} chatData
		 * @return {boolean}
		 */
		shouldDeleteChat(chatData)
		{
			const helper = DialogHelper.createByModel(chatData);

			if (!helper?.isOpenChannel)
			{
				return false;
			}

			return true;
		}
	}

	module.exports = { ChannelDialogPullHandler };
});
