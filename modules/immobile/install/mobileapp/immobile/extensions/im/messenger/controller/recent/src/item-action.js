/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/item-action
 */
jn.define('im/messenger/controller/recent/item-action', (require, exports, module) => {

	const { Loc } = jn.require('loc');
	const { Controller } = jn.require('im/messenger/controller/base');
	const { Logger } = jn.require('im/messenger/lib/logger');
	const { MessengerParams } = jn.require('im/messenger/lib/params');
	const { MessengerEvent } = jn.require('im/messenger/lib/event');
	const { EventType } = jn.require('im/messenger/const');
	const { Counters } = jn.require('im/messenger/lib/counters');
	const {
		RecentService,
		ChatService,
		UserService,
	} = jn.require('im/messenger/service');

	/**
	 * @class ItemAction
	 *
	 * @property {RecentView} view
	 */
	class ItemAction extends Controller
	{
		/* region Init */

		constructor(options = {})
		{
			super(options);
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

			MessengerStore.dispatch('recentModel/delete', { id: recentItem.id })
				.then(() => {
					this.renderRecent();

					Counters.update();
				})
			;

			RecentService.hideChat({ dialogId: recentItem.id })
				.catch((result) =>
				{
					Logger.error('Recent item hide error: ', result.error());

					MessengerStore.dispatch('recentModel/set', [recentItem])
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

			MessengerStore.dispatch('recentModel/delete', { id: recentItem.id })
				.then(() => Counters.update())
			;

			ChatService.leave({ dialogId: recentItem.id })
				.then(() => {
					MessengerStore.dispatch('dialoguesModel/delete', { id: itemId });
				})
				.catch((result) =>
				{
					Logger.error('Recent item leave error: ', result.error());

					MessengerStore.dispatch('recentModel/set', [recentItem])
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
			MessengerStore.dispatch('recentModel/set', [{
				id: itemId,
				pinned: shouldPin,
			}]).then(() => this.renderRecent());

			RecentService.pinChat({
				dialogId: itemId,
				shouldPin,
			})
				.catch((result) =>
				{
					Logger.error('Recent item pin error: ', result.error());

					MessengerStore.dispatch('recentModel/set', [{
						id: itemId,
						pinned: !shouldPin,
					}]).then(() => this.renderRecent());
				})
			;
		}

		read(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);

			MessengerStore.dispatch('recentModel/set', [{
				id: itemId,
				unread: false,
				counter: 0,
			}]).then(() => {
				this.renderRecent();

				Counters.update();
			});

			RecentService.readChat({
					dialogId: itemId,
				})
				.catch((result) =>
				{
					Logger.error('Recent item read error: ', result.error());

					MessengerStore.dispatch('recentModel/set', [recentItem]).then(() => {
						this.renderRecent();

						Counters.update();
					});
				})
			;
		}

		unread(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);

			MessengerStore.dispatch('recentModel/set', [{
				id: itemId,
				unread: true,
				counter: recentItem.counter,
			}]).then(() => {
				this.renderRecent();

				Counters.update();
			});

			RecentService.unreadChat({ dialogId: itemId })
				.catch((result) =>
				{
					Logger.error('Recent item unread error: ', result.error());

					MessengerStore.dispatch('recentModel/set', [recentItem]).then(() => {
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
			MessengerStore.dispatch('recentModel/set', [recentItem]).then(() => this.renderRecent());

			MessengerStore.dispatch('dialoguesModel/set', [{
				dialogId: itemId,
				muteList: Array.from(muteList),
			}]);

			ChatService.mute({
					dialogId: itemId,
					shouldMute,
				})
				.catch((result) =>
				{
					Logger.error('Recent item mute error: ', result.error());

					MessengerStore.dispatch('dialoguesModel/set', [dialog]);
					MessengerStore.dispatch('recentModel/set', [recentItem]).then(() => this.renderRecent());
				})
			;
		}

		inviteResend(itemId)
		{
			UserService.resendInvite({
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

			MessengerStore.dispatch('recentModel/delete', { id: recentItem.id })
				.then(() => this.renderRecent())
			;

			UserService.cancelInvite({
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

					MessengerStore.dispatch('recentModel/set', [recentItem])
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
			return ChatUtils.objectClone(MessengerStore.getters['recentModel/getById'](id));
		}

		getDialogById(id)
		{
			return ChatUtils.objectClone(MessengerStore.getters['dialoguesModel/getById'](id));
		}

		renderRecent()
		{
			new MessengerEvent(EventType.messenger.renderRecent).send();
		}
	}

	module.exports = { ItemAction };
});
