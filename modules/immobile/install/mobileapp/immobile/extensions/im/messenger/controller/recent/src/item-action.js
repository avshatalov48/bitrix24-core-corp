/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/item-action
 */
jn.define('im/messenger/controller/recent/item-action', (require, exports, module) => {

	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const { core } = require('im/messenger/core');
	const { Logger } = require('im/messenger/lib/logger');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType } = require('im/messenger/const');
	const { Counters } = require('im/messenger/lib/counters');
	const {
		RecentRest,
		ChatRest,
		UserRest,
	} = require('im/messenger/provider/rest');
	const { ProfileView } = require("user/profile");

	/**
	 * @class ItemAction
	 *
	 * @property {RecentView} view
	 */
	class ItemAction
	{
		/* region Init */
		constructor()
		{
			this.store = core.getStore();
		}

		do(action, itemId)
		{
			Logger.info('Recent item action: ', action, 'dialogId: ' + itemId);

			switch (action)
			{
				case 'hide':
					this.hide(itemId);
					break;

				case 'leave':
					this.leave(itemId);
					break;

				case 'call':
					this.call(itemId);
					break;

				case 'pin':
					this.pin(itemId, true);
					break;

				case 'unpin':
					this.pin(itemId, false);
					break;

				case 'read':
					this.read(itemId);
					break;

				case 'unread':
					this.unread(itemId);
					break;

				case 'mute':
					this.mute(itemId, true);
					break;

				case 'unmute':
					this.mute(itemId, false);
					break;

				case 'inviteResend':
					this.inviteResend(itemId);
					break;

				case 'inviteCancel':
					this.inviteCancel(itemId);
					break;

				case 'profile':
					this.openUserProfile(itemId);
					break;
			}
		}

		hide(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);

			this.store.dispatch('recentModel/delete', { id: recentItem.id })
				.then(() => {
					this.renderRecent();

					Counters.update();
				})
			;

			RecentRest.hideChat({ dialogId: recentItem.id })
				.catch((result) =>
				{
					Logger.error('Recent item hide error: ', result.error());

					this.store.dispatch('recentModel/set', [recentItem])
						.then(() => {
							this.renderRecent();

							Counters.update();
						})
					;
				})
			;
		}

		leave(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);

			this.store.dispatch('recentModel/delete', { id: recentItem.id })
				.then(() => Counters.update())
			;

			ChatRest.leave({ dialogId: recentItem.id })
				.then(() => {
					this.store.dispatch('dialoguesModel/delete', { id: itemId });
				})
				.catch((result) =>
				{
					Logger.error('Recent item leave error: ', result.error());

					this.store.dispatch('recentModel/set', [recentItem])
						.then(() => Counters.update())
					;
				})
			;
		}

		call(itemId)
		{
			console.log('call');
		}

		pin(itemId, shouldPin)
		{
			this.store.dispatch('recentModel/set', [{
				id: itemId,
				pinned: shouldPin,
			}]).then(() => this.renderRecent());

			RecentRest.pinChat({
				dialogId: itemId,
				shouldPin,
			})
				.catch((result) =>
				{
					Logger.error('Recent item pin error: ', result.error());

					this.store.dispatch('recentModel/set', [{
						id: itemId,
						pinned: !shouldPin,
					}]).then(() => this.renderRecent());
				})
			;
		}

		read(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);

			this.store.dispatch('recentModel/set', [{
				id: itemId,
				unread: false,
				counter: 0,
			}]).then(() => {
				this.renderRecent();

				Counters.update();
			});

			RecentRest.readChat({
					dialogId: itemId,
				})
				.catch((result) =>
				{
					Logger.error('Recent item read error: ', result.error());

					this.store.dispatch('recentModel/set', [recentItem]).then(() => {
						this.renderRecent();

						Counters.update();
					});
				})
			;
		}

		unread(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);

			this.store.dispatch('recentModel/set', [{
				id: itemId,
				unread: true,
				counter: recentItem.counter,
			}]).then(() => {
				this.renderRecent();

				Counters.update();
			});

			RecentRest.unreadChat({ dialogId: itemId })
				.catch((result) =>
				{
					Logger.error('Recent item unread error: ', result.error());

					this.store.dispatch('recentModel/set', [recentItem]).then(() => {
						this.renderRecent();

						Counters.update();
					});
				})
			;
		}

		mute(itemId, shouldMute)
		{
			const dialog = this.getDialogById(itemId);
			const recentItem = this.getRecentItemById(itemId);

			const userId = MessengerParams.getUserId();
			const muteList = new Set(dialog.muteList);

			if (shouldMute)
			{
				muteList.add(userId);
			}
			else
			{
				muteList.delete(userId);
			}

			//TODO remove after RecentConverter implementation, only the dialoguesModel should change
			const recentMuteList = {};
			muteList.forEach(userId => recentMuteList[userId] = true);
			recentItem.chat.mute_list = recentMuteList;
			this.store.dispatch('recentModel/set', [recentItem]).then(() => this.renderRecent());

			this.store.dispatch('dialoguesModel/set', [{
				dialogId: itemId,
				muteList: Array.from(muteList),
			}]);

			ChatRest.mute({
					dialogId: itemId,
					shouldMute,
				})
				.catch((result) =>
				{
					Logger.error('Recent item mute error: ', result.error());

					this.store.dispatch('dialoguesModel/set', [dialog]);
					this.store.dispatch('recentModel/set', [recentItem]).then(() => this.renderRecent());
				})
			;
		}

		inviteResend(itemId)
		{
			UserRest.resendInvite({
				userId: itemId,
			})
				.then((response) => {
					InAppNotifier.showNotification({
						backgroundColor: "#E6000000",
						message: Loc.getMessage('IMMOBILE_INVITE_RESEND_DONE'),
					});
				})
				.catch((response) => {
					if (response.status === 'error')
					{
						InAppNotifier.showNotification({
							backgroundColor: '#E6000000',
							message: response.errors.map(element => element.message).join('. '),
						});
					}
					else
					{
						InAppNotifier.showNotification({
							backgroundColor: '#E6000000',
							message: Loc.getMessage('IMMOBILE_MESSENGER_REFRESH_ERROR'),
						});
					}
				})
			;
		}

		inviteCancel(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);

			this.store.dispatch('recentModel/delete', { id: recentItem.id })
				.then(() => this.renderRecent())
			;

			UserRest.cancelInvite({
				userId: itemId,
			})
				.catch((response) => {
					if (response.status === 'error')
					{
						InAppNotifier.showNotification({
							backgroundColor: '#E6000000',
							message: response.errors.map(element => element.message).join('. '),
						});
					}
					else
					{
						InAppNotifier.showNotification({
							backgroundColor: '#E6000000',
							message: Loc.getMessage('IM_LIST_ACTION_ERROR'),
						});
					}

					Logger.error('Recent item inviteCancel error: ', response);

					this.store.dispatch('recentModel/set', [recentItem])
						.then(() => this.renderRecent())
					;
				})
			;
		}

		openUserProfile(itemId)
		{
			ProfileView.open({ userId: itemId });
		}

		getRecentItemById(id)
		{
			return clone(this.store.getters['recentModel/getById'](id));
		}

		getDialogById(id)
		{
			return clone(this.store.getters['dialoguesModel/getById'](id));
		}

		renderRecent()
		{
			MessengerEmitter.emit(EventType.messenger.renderRecent);
		}
	}

	module.exports = { ItemAction };
});
