/* eslint-disable promise/catch-or-return */

/**
 * @module im/messenger/provider/pull/lib/dialog-base
 */
jn.define('im/messenger/provider/pull/lib/dialog-base', (require, exports, module) => {
	const { BasePullHandler } = require('im/messenger/provider/pull/lib/pull-handler-base');
	const { Counters } = require('im/messenger/lib/counters');
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType, DialogType, ComponentCode } = require('im/messenger/const');

	/**
	 * @class DialogBasePullHandler
	 */
	class DialogBasePullHandler extends BasePullHandler
	{
		handleReadAllChats(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleReadAllChats`);

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

			this.logger.info(`${this.getClassName()}.handleChatPin`, params);

			this.store.dispatch('recentModel/update', [{
				id: params.dialogId,
				pinned: params.active,
			}]).catch((err) => this.logger.error(`${this.getClassName()}.handleChatPin.recentModel/update.catch:`, err));
		}

		handleChatMuteNotify(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatMuteNotify`, params);

			if (params.lines)
			{
				this.logger.info(`${this.getClassName()}.handleChatMuteNotify skip openline mute`, params);

				return;
			}

			const dialog = clone(this.store.getters['dialoguesModel/getById'](params.dialogId));
			if (Type.isUndefined(dialog))
			{
				return;
			}

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
			}]).catch((err) => this.logger.error(`${this.getClassName()}.handleChatMuteNotify.dialoguesModel/set.catch:`, err));

			this.store.dispatch('sidebarModel/changeMute', {
				dialogId: params.dialogId,
				isMute: params.muted,
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleChatMuteNotify.sidebarModel/changeMute.catch:`, err));
		}

		handleChatHide(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatHide`, params);

			this.store.dispatch('recentModel/delete', { id: params.dialogId })
				.then(() => Counters.updateDelayed())
				.catch((err) => this.logger.error(`${this.getClassName()}.handleChatHide.updateDelayed().catch:`, err))
			;
		}

		handleChatRename(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatRename`, params);

			const dialogId = `chat${params.chatId}`;
			const name = params.name;

			this.store.dispatch('dialoguesModel/update', {
				dialogId,
				fields: { name },
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleChatRename.dialoguesModel/update.catch:`, err));
		}

		handleDialogChange(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleDialogChange`, params);
			const dialogModelState = this.store.getters['dialoguesModel/getById'](params.dialogId);
			if (Type.isUndefined(dialogModelState))
			{
				return;
			}

			const componentCode = dialogModelState.type === DialogType.copilot
				? ComponentCode.imCopilotMessenger : ComponentCode.imMessenger;

			MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: params.dialogId }, componentCode);
		}

		handleGeneralChatId(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleGeneralChatId`, params);

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

			this.logger.info(`${this.getClassName()}.handleChatUnread`, params);

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

			this.logger.info(`${this.getClassName()}.handleChatUserAdd`, params, extra);
			const dialogId = params.dialogId;

			this.store.dispatch('usersModel/set', Object.values(params.users))
				.catch((err) => this.logger.error(`${this.getClassName()}.handleChatUserAdd.usersModel/set.catch:`, err));
			this.store.dispatch('dialoguesModel/addParticipants', {
				dialogId,
				participants: params.newUsers,
				userCounter: params.userCount,
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleChatUserAdd.dialoguesModel/addParticipants.catch:`, err));
		}

		async handleChatUserLeave(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatUserLeave`, params);

			const dialogId = params.dialogId;
			const chatId = params.chatId;

			this.deleteCounters(params.dialogId);

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
			}).catch((err) => this.logger.error(`${this.getClassName()}.handleChatUserLeave.dialoguesModel/removeParticipants.catch:`, err));
		}

		/**
		 * @param {String} dialogId
		 * @void
		 */
		deleteCounters(dialogId)
		{
			delete Counters.chatCounter.detail[dialogId];
			delete Counters.openlinesCounter.detail[dialogId];
		}

		handleChatAvatar(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatAvatar`, params);

			const dialogId = `chat${params.chatId}`;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.avatar = params.avatar;

			this.store.dispatch('recentModel/set', [recentItem])
				.catch((err) => this.logger.error(`${this.getClassName()}.handleChatAvatar.recentModel/set.catch:`, err));
			this.store.dispatch('dialoguesModel/update', { dialogId, fields: { avatar: recentItem.avatar } })
				.catch((err) => this.logger.error(`${this.getClassName()}.handleChatAvatar.dialoguesModel/update.catch:`, err));
		}

		handleChatChangeColor(params, extra, command)
		{
			if (this.interceptEvent(params, extra, command))
			{
				return;
			}

			this.logger.info(`${this.getClassName()}.handleChatChangeColor`, params);

			const dialogId = `chat${params.chatId}`;

			const recentItem = clone(this.store.getters['recentModel/getById'](dialogId));
			if (!recentItem)
			{
				return;
			}

			recentItem.color = params.color;

			this.store.dispatch('recentModel/set', [recentItem])
				.catch((err) => this.logger.error(`${this.getClassName()}.handleChatChangeColor.recentModel/set.catch:`, err));
		}

		/**
		 * @desc get class name for logger
		 * @return {string}1
		 */
		getClassName()
		{
			return this.constructor.name;
		}
	}

	module.exports = {
		DialogBasePullHandler,
	};
});
