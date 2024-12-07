/**
 * @module im/messenger/controller/user-profile
 */
jn.define('im/messenger/controller/user-profile', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { openUserProfile } = require('user/profile');

	class UserProfile
	{
		static async show(userId, options)
		{
			const widget = new UserProfile(userId, options);

			widget.open();
		}

		constructor(userId, options)
		{
			this.userId = userId;
			this.parentWidget = options.parentWidget;
			this.openingDialogId = options.openingDialogId;

			this.bindMethods();
		}

		async open()
		{
			if (Application.getApiVersion() >= 27)
			{
				this.subscribeExternalEvents();
				const layoutWidget = await openUserProfile({
					userId: this.userId,
					parentWidget: this.parentWidget,
				});

				this.layoutWidget = layoutWidget;

				layoutWidget.on(EventType.view.close, () => {
					this.unsubscribeExternalEvents();
				});
			}
			else
			{
				PageManager.openPage({ url: `/mobile/users/?user_id=${this.userId}` });
			}
		}

		bindMethods()
		{
			this.deleteDialogHandler = this.deleteDialogHandler.bind(this);
		}

		subscribeExternalEvents()
		{
			BX.addCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		unsubscribeExternalEvents()
		{
			BX.removeCustomEvent(EventType.dialog.external.delete, this.deleteDialogHandler);
		}

		deleteDialogHandler({ dialogId })
		{
			if (String(this.openingDialogId) !== String(dialogId))
			{
				return;
			}

			this.layoutWidget.close();
		}
	}

	module.exports = { UserProfile };
});
