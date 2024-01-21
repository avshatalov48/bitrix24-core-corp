/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/dialog
 */
jn.define('im/messenger/provider/pull/dialog', (require, exports, module) => {
	const { clone } = require('utils/object');
	const { PullHandler } = require('im/messenger/provider/pull/base');
	const { EventType } = require('im/messenger/const');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { Counters } = require('im/messenger/lib/counters');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('pull-handler--dialog');

	/**
	 * @class DialogPullHandler
	 */
	class DialogPullHandler extends PullHandler
	{
		handleReadAllChats(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleReadAllChats');

			this.store.dispatch('dialoguesModel/clearAllCounters')
				.then(() => this.store.dispatch('recentModel/clearAllCounters'))
				.then(() => Counters.update())
			;
		}

		handleChatPin(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleChatPin', params);

			this.store.dispatch('recentModel/set', [{
				id: params.dialogId,
				pinned: params.active,
			}]);
		}

		handleChatMuteNotify(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleChatMuteNotify', params);

			if (params.lines)
			{
				logger.info('DialogPullHandler.handleChatMuteNotify skip openline mute', params);

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

			this.store.dispatch('dialoguesModel/set', [{
				dialogId: params.dialogId,
				muteList: [...muteList],
			}]);

			this.store.dispatch('sidebarModel/changeMute', {
				dialogId: params.dialogId,
				isMute: params.muted,
			});
		}

		handleChatHide(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleChatHide', params);

			this.store.dispatch('recentModel/delete', { id: params.dialogId })
				.then(() => Counters.updateDelayed())
			;
		}

		handleChatRename(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleChatRename', params);

			const dialogId = `chat${params.chatId}`;
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
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleDialogChange', params);

			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: params.dialogId });
		}

		handleGeneralChatId(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleGeneralChatId', params);

			// TODO: Remove after converter implementation
			if (ChatDataConverter)
			{
				ChatDataConverter.generalChatId = params.id;
			}

			MessengerParams.setGeneralChatId(params.id);
		}

		handleChatUnread(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleChatUnread', params);

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

		handleChatUserAdd(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleChatUserAdd', params, extra);
			const dialogId = params.dialogId;

			this.store.dispatch('usersModel/set', Object.values(params.users));
			this.store.dispatch('dialoguesModel/addParticipants', {
				dialogId,
				participants: params.newUsers,
				userCounter: params.userCount,
			});
		}

		async handleChatUserLeave(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleChatUserLeave', params);

			const dialogId = params.dialogId;
			const chatId = params.chatId;

			delete Counters.chatCounter.detail[params.dialogId];
			delete Counters.openlinesCounter.detail[params.dialogId];

			if (Number(params.userId) === MessengerParams.getUserId())
			{
				await this.store.dispatch('recentModel/delete', { id: dialogId });
				Counters.update();

				MessengerEmitter.emit(EventType.dialog.external.close, {
					dialogId,
				});

				await this.store.dispatch('dialoguesModel/delete', { dialogId });
				await this.store.dispatch('messagesModel/deleteByChatId', { chatId });
			}

			this.store.dispatch('dialoguesModel/removeParticipants', {
				dialogId,
				participants: [params.userId],
				userCounter: params.userCount,
			});
		}

		handleChatAvatar(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleChatAvatar', params);

			const dialogId = `chat${params.chatId}`;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.avatar = params.avatar;
			recentItem.chat.avatar = params.avatar;

			this.store.dispatch('recentModel/set', [recentItem]);
			this.store.dispatch('dialoguesModel/update', { dialogId, fields: { avatar: recentItem.avatar } });
		}

		handleChatChangeColor(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			logger.info('DialogPullHandler.handleChatChangeColor', params);

			const dialogId = `chat${params.chatId}`;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.color = params.color;
			recentItem.chat.color = params.color;

			this.store.dispatch('recentModel/set', [recentItem]);
		}
	}

	module.exports = {
		DialogPullHandler,
	};
});
