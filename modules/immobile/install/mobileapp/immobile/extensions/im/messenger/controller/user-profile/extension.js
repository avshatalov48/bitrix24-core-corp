/**
 * @module im/messenger/controller/user-profile
 */
jn.define('im/messenger/controller/user-profile', (require, exports, module) => {
	const { UserProfile: MobileUserProfile } = require('user-profile');

	const { EventType } = require('im/messenger/const');

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
			this.subscribeExternalEvents();

			this.layoutWidget = await MobileUserProfile.open({
				ownerId: this.userId,
				parentWidget: this.parentWidget,
			});

			layoutWidget.on(EventType.view.close, () => {
				this.unsubscribeExternalEvents();
			});
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
