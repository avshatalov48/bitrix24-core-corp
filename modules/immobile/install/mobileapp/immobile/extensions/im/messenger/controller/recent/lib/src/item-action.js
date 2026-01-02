/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/controller/recent/lib/item-action
 */
jn.define('im/messenger/controller/recent/lib/item-action', (require, exports, module) => {
	/* global InAppNotifier  */
	const { Alert, ButtonType } = require('alert');
	const { clone } = require('utils/object');
	const { isOnline } = require('device/connection');
	const { Loc } = require('im/messenger/loc');
	const { Type } = require('type');
	const { UserProfile } = require('user-profile');

	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType, ErrorType } = require('im/messenger/const');
	const {
		RecentRest,
		ChatRest,
		UserRest,
	} = require('im/messenger/provider/rest');
	const { getLogger } = require('im/messenger/lib/logger');
	const { Notification } = require('im/messenger/lib/ui/notification');
	const {
		RecentDataProvider,
		ChatDataProvider,
	} = require('im/messenger/provider/data');

	const logger = getLogger('recent--item-action');

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

		// eslint-disable-next-line consistent-return
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
					this.pin(itemId);
					break;

				case 'unpin':
					this.unpin(itemId);
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

				default: return false;
			}
		}

		hide(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);
			if (Type.isUndefined(recentItem))
			{
				return;
			}

			this.store.dispatch('recentModel/delete', { id: recentItem.id })
				.then(() => {
					this.renderRecent();

					serviceLocator.get('tab-counters').update();
				})
			;

			RecentRest.hideChat({ dialogId: recentItem.id })
				.catch((result) => {
					logger.error('Recent item hide error: ', result.error());

					this.store.dispatch('recentModel/set', [recentItem])
						.then(() => {
							this.renderRecent();

							serviceLocator.get('tab-counters').update();
						})
					;
				})
			;
		}

		leave(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);
			if (Type.isUndefined(recentItem))
			{
				return;
			}

			const recentProvider = new RecentDataProvider();

			recentProvider.deleteFromSource(RecentDataProvider.source.model, { dialogId: recentItem.id })
				.then(() => serviceLocator.get('tab-counters').update())
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
						.then(() => serviceLocator.get('tab-counters').update())
						.catch((err) => logger.error('ChatRest.leave.recentModel/set.catch', err))
					;
				})
			;
		}

		call(itemId)
		{
			logger.log('call itemId:', itemId);
		}

		/**
		 * @param {DialogId} dialogId
		 */
		pin(dialogId)
		{
			this.#updateRecentPin(dialogId, true);

			RecentRest.pinChat(dialogId)
				.catch((error) => {
					logger.error('Recent item pin error: ', error);

					const errorCode = error?.[0]?.code;
					if (errorCode === ErrorType.recent.maxPin)
					{
						Alert.confirm(
							null,
							Loc.getMessage('IMMOBILE_RECENT_PIN_ERROR_MAX_PINNED'),
							[
								{
									type: ButtonType.DEFAULT,
								},
							],
						);
					}

					this.#updateRecentPin(dialogId, false);
				})
			;
		}

		/**
		 * @param {DialogId} dialogId
		 */
		unpin(dialogId)
		{
			this.#updateRecentPin(dialogId, false);

			RecentRest.unpinChat(dialogId)
				.catch((error) => {
					logger.error('Recent item unpin error: ', error);

					this.#updateRecentPin(dialogId, true);
				})
			;
		}

		/**
		 * @param {DialogId} dialogId
		 * @param {boolean} pinned
		 */
		#updateRecentPin(dialogId, pinned)
		{
			this.store.dispatch('recentModel/set', [{
				id: dialogId,
				pinned,
			}]);
			this.renderRecent();
		}

		read(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);
			if (Type.isUndefined(recentItem))
			{
				return;
			}

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

					serviceLocator.get('tab-counters').update();
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

							serviceLocator.get('tab-counters').update();
						});
				})
			;
		}

		unread(itemId)
		{
			const recentItem = this.getRecentItemById(itemId);
			if (Type.isUndefined(recentItem))
			{
				return;
			}

			this.store.dispatch('recentModel/set', [{
				id: itemId,
				unread: true,
				counter: recentItem.counter,
			}]).then(() => {
				this.renderRecent();

				serviceLocator.get('tab-counters').update();
			});

			RecentRest.unreadChat({ dialogId: itemId })
				.catch((result) => {
					logger.error('Recent item unread error: ', result.error());

					this.store.dispatch('recentModel/set', [recentItem]).then(() => {
						this.renderRecent();

						serviceLocator.get('tab-counters').update();
					});
				})
			;
		}

		mute(itemId, shouldMute)
		{
			const dialog = this.getDialogById(itemId);
			if (Type.isUndefined(dialog))
			{
				return;
			}

			const userId = MessengerParams.getUserId();
			const muteList = new Set(dialog.muteList);

			if (shouldMute)
			{
				muteList.add(userId);
				serviceLocator.get('tab-counters').addChatToMutedCollection(dialog.chatId);
			}
			else
			{
				muteList.delete(userId);
				serviceLocator.get('tab-counters').deleteChatFromMutedCollection(dialog.chatId);
			}

			this.store.dispatch('dialoguesModel/set', [{
				dialogId: itemId,
				muteList: [...muteList],
			}]).then(() => {
				serviceLocator.get('tab-counters').update();
			});

			ChatRest.mute({
				dialogId: itemId,
				shouldMute,
			})
				.catch((result) => {
					logger.error('Recent item mute error: ', result.error());

					this.store.dispatch('dialoguesModel/set', [dialog]).then(() => {
						this.renderRecent();

						serviceLocator.get('tab-counters').update();
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
			if (Type.isUndefined(recentItem))
			{
				return;
			}

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
			void UserProfile.open({
				ownerId: itemId,
			});
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
