/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/lib/item-action
 */
jn.define('im/messenger/controller/recent/lib/item-action', (require, exports, module) => {
	/* global InAppNotifier  */
	const { Loc } = require('loc');
	const { clone } = require('utils/object');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType } = require('im/messenger/const');
	const { Counters } = require('im/messenger/lib/counters');
	const { isOnline } = require('device/connection');
	const {
		RecentRest,
		ChatRest,
		UserRest,
	} = require('im/messenger/provider/rest');
	const { ProfileView } = require('user/profile');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const {
		RecentDataProvider,
		ChatDataProvider,
	} = require('im/messenger/provider/data');

	const logger = LoggerManager.getInstance().getLogger('recent--item-action');

	/**
	 * @class ItemAction
	 */
	class ItemAction
	{
		/* region Init */
		constructor()
		{
			this.store = serviceLocator.get('core').getStore();
		}

		do(action, itemId)
		{
			logger.info('Recent item action: ', action, `dialogId: ${itemId}`);

			if (!isOnline())
			{
				Notification.showOfflineToast();

				return false;
			}

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
				.catch((result) => {
					logger.error('Recent item hide error: ', result.error());

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
			const recentProvider = new RecentDataProvider();

			recentProvider.deleteFromSource(RecentDataProvider.source.model, { dialogId: recentItem.id })
				.then(() => Counters.update())
				.catch((err) => logger.error('Recent item leave error: ', err))
			;

			ChatRest.leave({ dialogId: recentItem.id })
				.then(() => {
					const chatProvider = new ChatDataProvider();

					return recentProvider.delete({ dialogId: recentItem.id })
						.then(() => chatProvider.delete({ dialogId: recentItem.id }))
						.catch((error) => {
							logger.error('ChatRest.leave delete error', error);
						})
					;
				})
				.catch((result) => {
					logger.error('Recent item leave error: ', result.error());

					this.store.dispatch('recentModel/set', [recentItem])
						.then(() => Counters.update())
						.catch((err) => logger.error('ChatRest.leave.recentModel/set.catch', err))
					;
				})
			;
		}

		call(itemId)
		{
			logger.log('call itemId:', itemId);
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
				.catch((result) => {
					logger.error('Recent item pin error: ', result.error());

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
			const dialogItem = this.getDialogById(itemId);

			this.store.dispatch('dialoguesModel/update', {
				dialogId: itemId,
				fields: {
					counter: 0,
				},
			}).then(() => this.store.dispatch('recentModel/set', [{
				id: itemId,
				unread: false,
				counter: 0,
			}]))
				.then(() => {
					this.renderRecent();

					Counters.update();
				});

			RecentRest.readChat({
				dialogId: itemId,
			})
				.catch((result) => {
					logger.error('Recent item read error: ', result.error());

					this.store.dispatch('dialoguesModel/update', {
						dialogId: itemId,
						fields: {
							counter: dialogItem.counter,
						},
					})
						.then(() => this.store.dispatch('recentModel/set', [recentItem]))
						.then(() => {
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
				.catch((result) => {
					logger.error('Recent item unread error: ', result.error());

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

			this.store.dispatch('dialoguesModel/set', [{
				dialogId: itemId,
				muteList: [...muteList],
			}]).then(() => {
				Counters.update();
			});

			ChatRest.mute({
				dialogId: itemId,
				shouldMute,
			})
				.catch((result) => {
					logger.error('Recent item mute error: ', result.error());

					this.store.dispatch('dialoguesModel/set', [dialog]).then(() => {
						this.renderRecent();

						Counters.update();
					});
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
						backgroundColor: '#E6000000',
						message: Loc.getMessage('IMMOBILE_INVITE_RESEND_DONE'),
					});
				})
				.catch((response) => {
					if (response.status === 'error')
					{
						InAppNotifier.showNotification({
							backgroundColor: '#E6000000',
							message: response.errors.map((element) => element.message).join('. '),
						});
					}
					else
					{
						InAppNotifier.showNotification({
							backgroundColor: '#E6000000',
							message: Loc.getMessage('IMMOBILE_COMMON_MESSENGER_REFRESH_ERROR'),
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
							message: response.errors.map((element) => element.message).join('. '),
						});
					}
					else
					{
						InAppNotifier.showNotification({
							backgroundColor: '#E6000000',
							message: Loc.getMessage('IM_LIST_ACTION_ERROR'),
						});
					}

					logger.error('Recent item inviteCancel error: ', response);

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
