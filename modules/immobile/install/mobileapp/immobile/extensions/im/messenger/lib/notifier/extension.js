/**
 * @module im/messenger/lib/notifier
 */
jn.define('im/messenger/lib/notifier', (require, exports, module) => {

	const { Type } = require('type');
	const { MessengerEmitter } = require('im/messenger/lib/emitter');
	const { EventType } = require('im/messenger/const');

	/**
	 * @class Notifier
	 */
	class Notifier
	{
		constructor()
		{
			include('InAppNotifier');

			this.delayShow = {};

			this.isInitialized = !Type.isUndefined(InAppNotifier);
			if (this.isInitialized)
			{
				InAppNotifier.setHandler(data => {
					if (data && data.dialogId)
					{
						if (data.dialogId === 'notify')
						{
							MessengerEmitter.emit(EventType.messenger.openNotifications);
							return;
						}

						MessengerEmitter.emit(EventType.messenger.openDialog, { dialogId: data.dialogId });
					}
				});
			}
		}

		/**
		 * Sends an in-app notification
		 *
		 * @param {Object} options
		 * @param {string} options.dialogId
		 * @param {string} options.title
		 * @param {string} options.text
		 * @param {string} [options.avatar]
		 * @param delay
		 *
		 * @returns {boolean} has a notification been sent
		 */
		notify(options, delay)
		{
			if (!this.isInitialized || !options.dialogId)
			{
				return false;
			}

			clearTimeout(this.delayShow[options.dialogId]);
			if (delay !== false)
			{
				this.delayShow[options.dialogId] =
					setTimeout(
						() => this.notify(options, false),
						1500
					)
				;

				return true;
			}

			if (PageManager.getNavigator().isActiveTab())
			{
				const page = PageManager.getNavigator().getVisible();
				if (page.type !== 'Web')
				{
					return false;
				}

				if (page.type === 'Web' && page.pageId === 'im-' + options.dialogId)
				{
					return false;
				}
			}

			const notification = {
				title: ChatUtils.htmlspecialcharsback(options.title),
				backgroundColor: '#E6000000',
				message: ChatUtils.htmlspecialcharsback(options.text),
				data: options
			};

			const avatar = ChatUtils.getAvatar(options.avatar);
			if (avatar)
			{
				notification.imageUrl = avatar;
			}

			InAppNotifier.showNotification(notification);

			return true;
		}
	}

	module.exports = {
		Notifier: new Notifier(),
	};
});
