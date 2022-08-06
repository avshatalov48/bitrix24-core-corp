/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/pull-handler/dialog
 */
jn.define('im/messenger/pull-handler/dialog', (require, exports, module) => {

	const { PullHandler } = jn.require('im/messenger/pull-handler/base');
	const { EventType } = jn.require('im/messenger/const');
	const { Logger } = jn.require('im/messenger/lib/logger');
	const { MessengerParams } = jn.require('im/messenger/lib/params');
	const { Counters } = jn.require('im/messenger/lib/counters');

	/**
	 * @class DialogPullHandler
	 */
	class DialogPullHandler extends PullHandler
	{
		handleReadAllChats(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleReadAllChats');

			MessengerStore.dispatch('recentModel/clearAllCounters')
				.then(() => Counters.update())
			;
		}

		handleChatPin(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleChatPin', params);

			MessengerStore.dispatch('recentModel/set', [{
				id: params.dialogId,
				pinned: params.active,
			}]);
		}

		handleChatMuteNotify(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleChatMuteNotify', params);

			if (params.lines)
			{
				Logger.info('MessagePullHandler.handleChatMuteNotify skip openline mute', params);
				return;
			}

			const dialog = ChatUtils.objectClone(MessengerStore.getters['dialoguesModel/getById'](params.dialogId));
			const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](params.dialogId));

			const muteList = new Set(dialog.muteList);
			if (params.muted)
			{
				muteList.add(MessengerParams.getUserId());
			}
			else
			{
				muteList.delete(MessengerParams.getUserId());
			}

			//TODO remove after RecentConverter implementation, only the dialoguesModel should change
			const recentMuteList = {};
			muteList.forEach(userId => recentMuteList[userId] = true);
			recentItem.chat.mute_list = recentMuteList;
			MessengerStore.dispatch('recentModel/set', [recentItem])
				.then(() => Counters.update())
			;

			MessengerStore.dispatch('dialoguesModel/set', [{
				dialogId: params.dialogId,
				muteList: Array.from(muteList),
			}]);
		}

		handleChatHide(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleChatHide', params);

			MessengerStore.dispatch('recentModel/delete', { id: params.dialogId })
				.then(() => Counters.updateDelayed())
			;
		}

		handleChatRename(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleChatRename', params);

			const dialogId = 'chat' + params.chatId;
			const name = params.name;

			const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.title = name;
			recentItem.chat.name = name;

			MessengerStore.dispatch('recentModel/set', [recentItem]);

			MessengerStore.dispatch('dialoguesModel/set', [{
				dialogId,
				name,
			}]);
		}

		handleDialogChange(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleDialogChange', params);

			this.emitMessengerEvent(EventType.messenger.openDialog, { dialogId: params.dialogId });
		}

		handleGeneralChatId(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleGeneralChatId', params);

			//TODO: Remove after converter implementation
			if (ChatDataConverter)
			{
				ChatDataConverter.generalChatId = params.id;
			}

			MessengerParams.setGeneralChatId(params.id);
		}

		handleChatUnread(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleChatUnread', params);

			const dialogId = params.dialogId;

			const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			let counter = 0;
			if (!params.muted && params.counter)
			{
				counter = params.counter;
			}

			MessengerStore.dispatch('recentModel/set', [{
				id: dialogId,
				unread: params.active,
				counter,
			}]).then(() => Counters.update());
		}

		handleChatUserLeave(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleChatUserLeave', params);

			const dialogId = params.dialogId;

			delete Counters.chatCounter.detail[params.dialogId];
			delete Counters.openlinesCounter.detail[params.dialogId];

			if (Number(params.userId) === MessengerParams.getUserId())
			{
				MessengerStore.dispatch('recentModel/delete', { id: dialogId })
					.then(() => Counters.update())
				;

				MessengerStore.dispatch('dialoguesModel/delete', { id: dialogId });
			}
		}

		handleChatAvatar(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleChatAvatar', params);

			const dialogId = 'chat' + params.chatId;

			const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.avatar = params.avatar;
			recentItem.chat.avatar = params.avatar;

			MessengerStore.dispatch('recentModel/set', [ recentItem ]);
		}

		handleChatChangeColor(params, extra, command)
		{
			Logger.info('MessagePullHandler.handleChatChangeColor', params);

			const dialogId = 'chat' + params.chatId;

			const recentItem = ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.color = params.color;
			recentItem.chat.color = params.color;

			MessengerStore.dispatch('recentModel/set', [ recentItem ]);
		}
	}

	module.exports = {
		DialogPullHandler,
	};
});
