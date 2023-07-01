/* eslint-disable bitrix-rules/no-pseudo-private */
/* eslint-disable flowtype/require-return-type */
/* eslint-disable bitrix-rules/no-bx */
/* eslint-disable bitrix-rules/no-bx-message */

/**
 * @module im/messenger/provider/pull/dialog
 */
jn.define('im/messenger/provider/pull/dialog', (require, exports, module) => {

	const { clone } = require('utils/object');
	const { PullHandler } = require('im/messenger/provider/pull/base');
	const { EventType } = require('im/messenger/const');
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Counters } = require('im/messenger/lib/counters');

	/**
	 * @class DialogPullHandler
	 */
	class DialogPullHandler extends PullHandler
	{
		handleReadAllChats(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleReadAllChats');

			this.store.dispatch('recentModel/clearAllCounters')
				.then(() => Counters.update())
			;
		}

		handleChatPin(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleChatPin', params);

			this.store.dispatch('recentModel/set', [{
				id: params.dialogId,
				pinned: params.active,
			}]);
		}

		handleChatMuteNotify(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleChatMuteNotify', params);

			if (params.lines)
			{
				Logger.info('DialogPullHandler.handleChatMuteNotify skip openline mute', params);
				return;
			}

			const dialog = clone(this.store.getters['dialoguesModel/getById'](params.dialogId));
			const recentItem = clone(this.store.getters['recentModel/getById'](params.dialogId));

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
			this.store.dispatch('recentModel/set', [recentItem])
				.then(() => Counters.update())
			;

			this.store.dispatch('dialoguesModel/set', [{
				dialogId: params.dialogId,
				muteList: Array.from(muteList),
			}]);
		}

		handleChatHide(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleChatHide', params);

			this.store.dispatch('recentModel/delete', { id: params.dialogId })
				.then(() => Counters.updateDelayed())
			;
		}

		handleChatRename(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleChatRename', params);

			const dialogId = 'chat' + params.chatId;
			const name = params.name;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.title = name;
			recentItem.chat.name = name;

			this.store.dispatch('recentModel/set', [recentItem]);

			this.store.dispatch('dialoguesModel/set', [{
				dialogId,
				name,
			}]);
		}

		handleDialogChange(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleDialogChange', params);

			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: params.dialogId });
		}

		handleGeneralChatId(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleGeneralChatId', params);

			//TODO: Remove after converter implementation
			if (ChatDataConverter)
			{
				ChatDataConverter.generalChatId = params.id;
			}

			MessengerParams.setGeneralChatId(params.id);
		}

		handleChatUnread(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleChatUnread', params);

			const dialogId = params.dialogId;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			let counter = 0;
			if (!params.muted && params.counter)
			{
				counter = params.counter;
			}

			this.store.dispatch('recentModel/set', [{
				id: dialogId,
				unread: params.active,
				counter,
			}]).then(() => Counters.update());
		}

		handleChatUserLeave(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleChatUserLeave', params);

			const dialogId = params.dialogId;

			delete Counters.chatCounter.detail[params.dialogId];
			delete Counters.openlinesCounter.detail[params.dialogId];

			if (Number(params.userId) === MessengerParams.getUserId())
			{
				this.store.dispatch('recentModel/delete', { id: dialogId })
					.then(() => Counters.update())
				;

				this.store.dispatch('dialoguesModel/delete', { id: dialogId });
			}
		}

		handleChatAvatar(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleChatAvatar', params);

			const dialogId = 'chat' + params.chatId;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.avatar = params.avatar;
			recentItem.chat.avatar = params.avatar;

			this.store.dispatch('recentModel/set', [ recentItem ]);
		}

		handleChatChangeColor(params, extra, command)
		{
			Logger.info('DialogPullHandler.handleChatChangeColor', params);

			const dialogId = 'chat' + params.chatId;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.color = params.color;
			recentItem.chat.color = params.color;

			this.store.dispatch('recentModel/set', [ recentItem ]);
		}
	}

	module.exports = {
		DialogPullHandler,
	};
});
