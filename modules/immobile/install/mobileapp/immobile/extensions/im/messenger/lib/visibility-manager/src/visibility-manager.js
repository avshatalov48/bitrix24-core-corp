/**
 * @module im/messenger/lib/visibility-manager/visibility-manager
 */
jn.define('im/messenger/lib/visibility-manager/visibility-manager', (require, exports, module) => {
	const { Type } = require('type');

	const { Logger } = require('im/messenger/lib/logger');

	class VisibilityManager
	{
		/**
		 * @return {VisibilityManager}
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		/**
		 * @return {Promise<NavigationContext>}
		 * @private
		 */
		static getNavigationContext()
		{
			return new Promise((resolve, reject) => {
				if (!Type.isFunction(PageManager.getNavigator().getNavigationContext))
				{
					reject(new Error('getNavigationContext is not supported'));
				}

				PageManager.getNavigator().getNavigationContext()
					.then((context) => resolve(context))
					.catch((error) => reject(error))
				;
			});
		}

		/**
		 * @return {Promise}
		 */
		checkIsDialogVisible(dialogId)
		{
			return new Promise((resolve) => {
				if (!Type.isStringFilled(dialogId))
				{
					resolve(false);

					return;
				}

				VisibilityManager.getNavigationContext()
					.then((context) => {
						const isCurrentTabVisible = context.navigationIsVisible;
						const hasItemsInStack = Type.isArrayFilled(context.itemsInStack);
						if (!isCurrentTabVisible || !hasItemsInStack)
						{
							resolve(false);

							return;
						}

						const topItem = context.itemsInStack[0];
						const isDialogWidgetOnTop = topItem.name && topItem.name === 'chat.dialog';
						if (!isDialogWidgetOnTop)
						{
							resolve(false);

							return;
						}

						const widgetSettings = topItem.settings;
						if (!Type.isObject(widgetSettings))
						{
							resolve(false);

							return;
						}

						if (widgetSettings.dialogId === dialogId)
						{
							resolve(true);

							return;
						}

						resolve(false);
					})
					.catch((error) => {
						Logger.error(error);

						resolve(false);
					})
				;
			});
		}
	}

	module.exports = {
		VisibilityManager,
	};
});
